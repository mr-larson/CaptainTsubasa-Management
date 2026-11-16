<?php

namespace App\Services\Players;

use App\Enums\MoraleSource;
use App\Enums\PlayerRequestStatus;
use App\Enums\PromiseStatus;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GamePlayerRequest;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

/**
 * Demandes formulées par les joueurs à leur entraîneur.
 *
 * Types :
 *  - leave        : joueur épuisé qui demande une semaine de congé. Acceptée → promesse
 *    de repos (ni match ni entraînement cette semaine).
 *  - playing_time : remplaçant au moral bas qui réclame du temps de jeu. Acceptée →
 *    promesse de temps de jeu.
 *  - ailment      : souci physique (cheville foulée…) à arbitrer : repos (promesse),
 *    jouer avec le kiné (payant, meilleur gain de relation) ou jouer sans intervention
 *    (moral et relation en baisse).
 *
 * Accepter puis ne pas tenir coûte plus cher que refuser d'emblée : la promesse créée
 * porte l'origine « player » / « ailment » (cf. PromiseService::ORIGIN_DELTAS).
 * Une demande laissée sans réponse est traitée à la clôture de la semaine comme un
 * refus (ou comme « jouer sans intervention » pour un souci physique).
 * Les équipes IA répondent d'elles-mêmes dès la création de la demande.
 */
class PlayerRequestService
{
    /** Semaines minimales entre deux demandes d'un même joueur. */
    const COOLDOWN_WEEKS = 4;

    /** Endurance à partir de laquelle un joueur peut demander congé. */
    const LEAVE_STAMINA_MAX = 40;
    /** Moral à partir duquel un remplaçant peut réclamer du temps de jeu. */
    const PLAYING_TIME_MORALE_MAX = 50;

    /** Chances (sur 100, par équipe et par semaine) qu'une demande soit émise. */
    const AILMENT_CHANCE      = 8;
    const LEAVE_CHANCE        = 50;
    const PLAYING_TIME_CHANCE = 30;

    /** Coût de l'intervention du kiné (billets). */
    const PHYSIO_COST = 400;
    /** L'IA ne paie le kiné que si son budget couvre ce multiple du coût. */
    private const AI_PHYSIO_BUDGET_FACTOR = 3;

    /** Refus (ou demande ignorée) : [moral, relation coach]. */
    const DECLINE = [-4, -3];
    /** Souci physique, choix à effet immédiat : [moral, relation coach]. */
    const AILMENT_EFFECTS = [
        'physio' => [3, 8],
        'play'   => [-6, -6],
    ];

    private const AILMENTS = [
        'Cheville foulée',
        'Gêne musculaire à la cuisse',
        'Douleur au genou',
        'Contracture au mollet',
        'Mal de dos',
    ];

    /** Reçoit le service de promesses (une demande acceptée devient une promesse). */
    public function __construct(private PromiseService $promises)
    {
    }

    /**
     * Ouverture de semaine : au plus une nouvelle demande par équipe ayant un match
     * programmé. Sont écartés les joueurs indisponibles, ceux qui ont déjà une promesse
     * en cours et ceux qui ont formulé une demande récemment. Les équipes IA y
     * répondent aussitôt ; celles des managers humains restent en attente.
     */
    public function generateForWeek(GameSave $gameSave): void
    {
        if (! $gameSave->getConfig('player_requests_enabled', true) || ! $gameSave->isInMatchPhase()) {
            return;
        }

        $week   = (int) ($gameSave->week ?? 1);
        $season = (int) ($gameSave->season ?? 1);

        $fixtures = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)->where('status', 'scheduled')
            ->get(['id', 'home_team_id', 'away_team_id']);
        if ($fixtures->isEmpty()) return;

        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->whereIn('id', $fixtures->pluck('home_team_id')->merge($fixtures->pluck('away_team_id'))->unique())
            ->with(['contracts' => fn ($query) => $query->activeAt($week)->with('gamePlayer')])
            ->get();

        $excluded = $this->excludedPlayerIds($gameSave, $week, $season);
        $humanIds = collect($gameSave->controlledGameTeamIds())
            ->push((int) $gameSave->controlled_game_team_id)
            ->map(fn ($id) => (int) $id)->flip();

        // Joueurs ayant joué la semaine précédente (un remplaçant qui vient de jouer ne réclame pas).
        $playedLastWeek = [];
        GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week - 1)->where('status', 'played')
            ->with('playerStats')->get()
            ->each(function (GameMatch $match) use (&$playedLastWeek) {
                $playedLastWeek += MoraleService::resolvePlayedPlayerIds($match);
            });

        foreach ($teams as $team) {
            $candidates = $team->contracts->filter(fn ($contract) =>
                $contract->gamePlayer && ! $excluded->has((int) $contract->game_player_id)
            );
            if ($candidates->isEmpty()) continue;

            $picked = $this->pickRequest($candidates, $playedLastWeek);
            if ($picked === null) continue;

            [$type, $player] = $picked;
            $request = $this->open($gameSave, $team->id, $player, $type);

            if (! $humanIds->has((int) $team->id)) {
                $this->autoRespond($gameSave, $request, $team, $excluded);
            }
        }
    }

    /**
     * Crée une demande en attente pour ce joueur (sans tirage ni vérification
     * d'éligibilité : c'est generateForWeek qui choisit qui demande quoi).
     */
    public function open(GameSave $gameSave, int $teamId, GamePlayer $player, string $type): GamePlayerRequest
    {
        $ailment = $type === 'ailment';

        return GamePlayerRequest::create([
            'game_save_id'   => $gameSave->id,
            'game_team_id'   => $teamId,
            'game_player_id' => $player->id,
            'type'           => $type,
            'description'    => $ailment ? self::AILMENTS[$this->roll() % count(self::AILMENTS)] : null,
            'cost'           => $ailment ? self::PHYSIO_COST : 0,
            'week'           => (int) ($gameSave->week ?? 1),
            'season'         => (int) ($gameSave->season ?? 1),
        ]);
    }

    /**
     * Réponse de l'entraîneur. $answer vaut accept / decline (congé, temps de jeu) ou
     * rest / physio / play (souci physique). Applique les effets immédiats (moral,
     * relation, budget) ou crée la promesse correspondante. Retourne la demande mise à
     * jour, ou un message d'erreur (string) si la réponse est impossible.
     */
    public function respond(GameSave $gameSave, GamePlayerRequest $request, string $answer): GamePlayerRequest|string
    {
        if ($request->status !== PlayerRequestStatus::Pending) {
            return 'Cette demande a déjà reçu une réponse.';
        }

        $player = $request->gamePlayer;
        if (! $player) {
            return 'Ce joueur n\'existe plus.';
        }

        $valid = $request->type === 'ailment' ? ['rest', 'physio', 'play'] : ['accept', 'decline'];
        if (! in_array($answer, $valid, true)) {
            return 'Réponse inconnue pour cette demande.';
        }

        $teamId = (int) $request->game_team_id;

        if ($answer === 'decline') {
            $this->applyEffects($gameSave, $player, self::DECLINE, $this->subject($request) . ' refusée');
            $request->update(['status' => PlayerRequestStatus::Declined]);

            return $request;
        }

        if ($answer === 'physio' || $answer === 'play') {
            if ($answer === 'physio') {
                $team = GameTeam::find($teamId);
                if (! $team || (int) ($team->budget ?? 0) < $request->cost) {
                    return 'Budget insuffisant pour faire intervenir le kiné.';
                }
                $team->budget = (int) $team->budget - $request->cost;
                $team->save();
            }

            $this->applyEffects($gameSave, $player, self::AILMENT_EFFECTS[$answer], $answer === 'physio'
                ? $request->description . ' : soigné par le kiné'
                : $request->description . ' : doit jouer sans soins');
            $request->update(['status' => PlayerRequestStatus::Accepted, 'choice' => $answer]);

            return $request;
        }

        // accept / rest : la demande devient une promesse, jugée à la clôture de semaine.
        $rest = $request->type !== 'playing_time';
        if ($rest && $this->matchAlreadyPlayed($gameSave, $teamId)) {
            return 'Le match de la semaine est déjà joué : il est trop tard pour lui accorder du repos.';
        }

        $promise = $this->promises->create(
            $gameSave, $player, $teamId,
            $rest ? 'rest' : 'playing_time',
            $request->type === 'ailment' ? 'ailment' : 'player',
        );
        if (is_string($promise)) {
            return $promise;
        }

        $request->update([
            'status'          => PlayerRequestStatus::Accepted,
            'choice'          => $request->type === 'ailment' ? 'rest' : null,
            'game_promise_id' => $promise->id,
        ]);

        return $request;
    }

    /**
     * Clôture de semaine : les demandes restées sans réponse sont ignorées. Un congé ou
     * du temps de jeu ignoré vaut refus ; un souci physique ignoré vaut « jouer sans
     * intervention ».
     */
    public function expireForWeek(GameSave $gameSave, int $week): void
    {
        $requests = GamePlayerRequest::where('game_save_id', $gameSave->id)
            ->where('status', PlayerRequestStatus::Pending)
            ->where('week', '<=', $week)
            ->with('gamePlayer')
            ->get();

        foreach ($requests as $request) {
            $ailment = $request->type === 'ailment';

            if ($request->gamePlayer) {
                $this->applyEffects(
                    $gameSave, $request->gamePlayer,
                    $ailment ? self::AILMENT_EFFECTS['play'] : self::DECLINE,
                    $ailment ? $request->description . ' : laissé sans réponse' : $this->subject($request) . ' restée sans réponse',
                );
            }

            $request->update([
                'status' => PlayerRequestStatus::Ignored,
                'choice' => $ailment ? 'play' : null,
            ]);
        }
    }

    /**
     * Demandes en attente d'une équipe, prêtes pour l'interface : message du joueur et
     * réponses possibles avec leurs conséquences annoncées (barème unique, côté serveur).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingForTeam(GameSave $gameSave, ?int $teamId): array
    {
        if (! $teamId) return [];

        $restBlocked = $this->matchAlreadyPlayed($gameSave, $teamId);

        return GamePlayerRequest::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $teamId)
            ->where('status', PlayerRequestStatus::Pending)
            ->with('gamePlayer')
            ->orderBy('id')
            ->get()
            ->filter(fn (GamePlayerRequest $request) => $request->gamePlayer !== null)
            ->map(fn (GamePlayerRequest $request) => [
                'id'          => $request->id,
                'type'        => $request->type,
                'player_id'   => $request->game_player_id,
                'player_name' => trim($request->gamePlayer->firstname . ' ' . $request->gamePlayer->lastname),
                'week'        => $request->week,
                'title'       => $this->subject($request),
                'message'     => $this->message($request),
                'options'     => $this->options($request, $restBlocked),
            ])
            ->values()
            ->all();
    }

    /** Tirage d'un entier de 1 à 100 (isolé pour pouvoir le remplacer en test). */
    protected function roll(): int
    {
        return random_int(1, 100);
    }

    /**
     * Choisit la demande de la semaine pour une équipe, ou null : d'abord un souci
     * physique (rare, chez un titulaire), sinon le congé du joueur le plus épuisé, sinon
     * le temps de jeu du remplaçant le plus démoralisé.
     *
     * @param array<string, mixed> $playedLastWeek ids (clés) des joueurs ayant joué la semaine précédente
     * @return array{0: string, 1: GamePlayer}|null
     */
    private function pickRequest(Collection $candidates, array $playedLastWeek): ?array
    {
        $starters = $candidates->where('is_starter', true)->values();
        if ($starters->isNotEmpty() && $this->roll() <= self::AILMENT_CHANCE) {
            return ['ailment', $starters[$this->roll() % $starters->count()]->gamePlayer];
        }

        $tired = $candidates->pluck('gamePlayer')
            ->filter(fn (GamePlayer $player) => (int) $player->stamina <= self::LEAVE_STAMINA_MAX)
            ->sortBy('stamina')->first();
        if ($tired && $this->roll() <= self::LEAVE_CHANCE) {
            return ['leave', $tired];
        }

        $sidelined = $candidates->where('is_starter', false)->pluck('gamePlayer')
            ->filter(fn (GamePlayer $player) => (int) $player->morale <= self::PLAYING_TIME_MORALE_MAX
                && ! isset($playedLastWeek[(string) $player->id]))
            ->sortBy('morale')->first();
        if ($sidelined && $this->roll() <= self::PLAYING_TIME_CHANCE) {
            return ['playing_time', $sidelined];
        }

        return null;
    }

    /**
     * Réponse automatique d'une équipe IA : congé accordé s'il y a un remplaçant au
     * poste (sinon refusé), temps de jeu toujours promis, souci physique soigné par le
     * kiné si le budget est confortable, sinon repos s'il y a un remplaçant, sinon il joue.
     */
    private function autoRespond(GameSave $gameSave, GamePlayerRequest $request, GameTeam $team, Collection $excluded): void
    {
        $player = $team->contracts->firstWhere('game_player_id', $request->game_player_id)?->gamePlayer;
        $request->setRelation('gamePlayer', $player);

        $hasCover = $player !== null && $team->contracts->contains(fn ($contract) =>
            ! $contract->is_starter && $contract->gamePlayer
            && $contract->gamePlayer->position === $player->position
            && (int) $contract->gamePlayer->stamina > self::LEAVE_STAMINA_MAX
            && ! $excluded->has((int) $contract->game_player_id)
        );

        $answer = match ($request->type) {
            'leave'        => $hasCover ? 'accept' : 'decline',
            'playing_time' => 'accept',
            default        => (int) ($team->budget ?? 0) >= $request->cost * self::AI_PHYSIO_BUDGET_FACTOR
                ? 'physio'
                : ($hasCover ? 'rest' : 'play'),
        };

        $this->respond($gameSave, $request, $answer);
    }

    /**
     * Joueurs qui ne peuvent pas formuler de demande cette semaine : blessés, suspendus,
     * en formation de poste, déjà liés par une promesse en cours, ou ayant demandé
     * quelque chose récemment. Collection indexée par id de joueur.
     */
    private function excludedPlayerIds(GameSave $gameSave, int $week, int $season): Collection
    {
        return GameInjury::where('game_save_id', $gameSave->id)->where('week_return', '>', $week)->pluck('game_player_id')
            ->merge(GameSanction::where('game_save_id', $gameSave->id)->where('week_return', '>', $week)
                ->where('weeks_suspended', '>', 0)->pluck('game_player_id'))
            ->merge(GamePositionTraining::unavailablePlayerIds($gameSave->id, $week))
            ->merge(GamePromise::where('game_save_id', $gameSave->id)
                ->where('status', PromiseStatus::Pending)->pluck('game_player_id'))
            ->merge(GamePlayerRequest::where('game_save_id', $gameSave->id)->where('season', $season)
                ->where('week', '>', $week - self::COOLDOWN_WEEKS)->pluck('game_player_id'))
            ->map(fn ($id) => (int) $id)
            ->flip();
    }

    /** L'équipe a-t-elle déjà joué son match de la semaine en cours ? (trop tard pour promettre du repos) */
    private function matchAlreadyPlayed(GameSave $gameSave, int $teamId): bool
    {
        return GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', (int) ($gameSave->week ?? 1))
            ->where('status', 'played')
            ->where(fn ($query) => $query->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->exists();
    }

    /**
     * Applique un effet [moral, relation coach] au joueur (valeurs bornées) et
     * journalise la variation de moral (comme les promesses et les déclarations).
     *
     * @param array{0: int, 1: int} $deltas
     */
    private function applyEffects(GameSave $gameSave, GamePlayer $player, array $deltas, string $label): void
    {
        [$moraleDelta, $affinityDelta] = $deltas;

        $player->morale = max(0, min(100, (int) $player->morale + $moraleDelta));
        $player->coach_affinity = max(PromiseService::AFFINITY_MIN, min(PromiseService::AFFINITY_MAX,
            (int) $player->coach_affinity + $affinityDelta
        ));
        $player->save();

        if ($moraleDelta !== 0) {
            GamePlayerMoraleLog::create([
                'game_save_id'   => $gameSave->id,
                'game_player_id' => $player->id,
                'source'         => MoraleSource::Promise,
                'value'          => $moraleDelta,
                'label'          => $label,
                'week'           => (int) ($gameSave->week ?? 1),
                'season'         => (int) ($gameSave->season ?? 1),
            ]);
        }
    }

    /** Intitulé court de la demande (sert de titre et de base aux libellés du journal). */
    private function subject(GamePlayerRequest $request): string
    {
        return match ($request->type) {
            'leave'        => 'Demande de congé',
            'playing_time' => 'Demande de temps de jeu',
            default        => 'Souci physique',
        };
    }

    /** Ce que le joueur dit à son entraîneur. */
    private function message(GamePlayerRequest $request): string
    {
        $player = $request->gamePlayer;

        return match ($request->type) {
            'leave'        => "Coach, je suis épuisé (endurance {$player->stamina}). J'aimerais souffler une semaine : ni match ni entraînement.",
            'playing_time' => 'Coach, je ne joue presque plus. Je veux ma chance au prochain match.',
            default        => "{$request->description}. Coach, qu'est-ce qu'on fait pour le match de cette semaine ?",
        };
    }

    /**
     * Réponses proposées à l'entraîneur, avec les effets annoncés. Pour une réponse
     * qui crée une promesse, « morale / affinity » sont les gains si elle est tenue et
     * « broken_* » les pertes si elle est rompue.
     *
     * @return array<int, array<string, mixed>>
     */
    private function options(GamePlayerRequest $request, bool $restBlocked): array
    {
        $decline = [
            'key' => 'decline', 'label' => 'Refuser', 'tone' => 'negative',
            'morale' => self::DECLINE[0], 'affinity' => self::DECLINE[1],
        ];

        if ($request->type === 'leave') {
            return [
                $this->promiseOption('accept', 'Accorder le congé', 'player',
                    'Ni match ni entraînement cette semaine.', $restBlocked),
                $decline,
            ];
        }

        if ($request->type === 'playing_time') {
            $turns = PromiseService::playingTimeTarget($request->gameSave ?? GameSave::find($request->game_save_id));

            return [
                $this->promiseOption('accept', 'Promettre du temps de jeu', 'player',
                    "Au moins {$turns} tours à son prochain match.", false),
                $decline,
            ];
        }

        return [
            $this->promiseOption('rest', 'Le mettre au repos', 'ailment',
                'Ni match ni entraînement cette semaine.', $restBlocked),
            [
                'key' => 'physio', 'label' => 'Jouer avec le kiné', 'tone' => 'positive',
                'morale' => self::AILMENT_EFFECTS['physio'][0], 'affinity' => self::AILMENT_EFFECTS['physio'][1],
                'cost' => $request->cost,
            ],
            [
                'key' => 'play', 'label' => 'Jouer sans intervention', 'tone' => 'negative',
                'morale' => self::AILMENT_EFFECTS['play'][0], 'affinity' => self::AILMENT_EFFECTS['play'][1],
            ],
        ];
    }

    /** Décrit une réponse qui engage une promesse : gains si tenue, pertes si rompue, et blocage éventuel (match déjà joué). */
    private function promiseOption(string $key, string $label, string $origin, string $note, bool $blocked): array
    {
        $deltas = PromiseService::ORIGIN_DELTAS[$origin];

        return [
            'key' => $key, 'label' => $label, 'tone' => 'positive', 'promise' => true, 'note' => $note,
            'morale' => $deltas['kept'][0], 'affinity' => $deltas['kept'][1],
            'broken_morale' => $deltas['broken'][0], 'broken_affinity' => $deltas['broken'][1],
            'disabled' => $blocked,
            'disabled_reason' => $blocked ? 'Match de la semaine déjà joué' : null,
        ];
    }
}
