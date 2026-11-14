<?php

namespace App\Services\Game;

use App\Contracts\Randomizer;
use App\Enums\GamePhase;
use App\Helpers\HeadingRating;
use App\Helpers\InitialPlayerStats;
use App\Models\Contract;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\PeriodPackage;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Draft\DraftService;
use App\Services\Season\SeasonBootstrapService;
use App\Services\Season\SeasonService;
use App\Services\Transfers\AmateurMarketService;

/**
 * Construit une nouvelle partie « Ligue » (mode pré-fait ou draft) à partir des
 * données de référence : tables globales (teams / players / contracts) ou, si
 * l'utilisateur en a choisi un, un package de période importé.
 *
 * Une partie est une COPIE de ces données dans les tables game_* : la partie
 * peut ensuite évoluer (stats, contrats, budgets) sans toucher à la référence.
 *
 * Déroulé de build(), en une seule transaction (tout ou rien) :
 *   1. créer la sauvegarde (game_saves) ;
 *   2. copier les équipes, puis fixer budgets de départ et sièges humains ;
 *   3. copier les joueurs (insertion en masse, avec leur numéro de maillot) ;
 *   4. copier les contrats (pré-fait seulement) et composer les onze de départ ;
 *   5. garnir le marché d'amateurs libres ;
 *   6. mode draft : préparer le draft / mode pré-fait : préparer la semaine 1.
 *
 * Les Coupes du Monde ne passent pas par ici (cf. NationalTeamAssembler).
 */
class NewGameBuilder
{
    /** Formation par défaut d'une équipe qui n'en précise pas. */
    private const DEFAULT_FORMATION = '3-2-3-2';

    /** Taille des paquets d'insertion en masse (limite le poids d'une requête SQL). */
    private const PLAYER_CHUNK   = 200;
    private const CONTRACT_CHUNK = 500;

    /** Injecte l'aléatoire, le draft, le marché d'amateurs et l'amorçage de saison. */
    public function __construct(
        private Randomizer $random,
        private DraftService $draftService,
        private AmateurMarketService $amateurMarket,
        private SeasonBootstrapService $seasonBootstrap,
    ) {
    }

    /**
     * Crée la partie complète et la renvoie. Si une étape échoue, rien n'est
     * enregistré (pas de partie à moitié construite dans la liste).
     *
     * @param  User               $user     propriétaire de la partie
     * @param  array              $data     données validées (label, period, game_mode, competition_type,
     *                                      team_id / team_ids, game_config déjà nettoyée)
     * @param  PeriodPackage|null $package  package de période choisi, null = données de référence globales
     * @param  int                $seed     graine des stats initiales (même graine = mêmes stats qu'à l'aperçu)
     */
    public function build(User $user, array $data, ?PeriodPackage $package, int $seed): GameSave
    {
        return DB::transaction(function () use ($user, $data, $package, $seed) {
            $isDraft       = ($data['game_mode'] ?? 'prebuilt') === 'draft';
            $humanTeamIds  = $this->humanTeamIds($data, $package !== null);
            $now           = now();

            // 1. La sauvegarde elle-même.
            $gameSave = $this->createSave($user, $data, $package, $isDraft, $humanTeamIds);

            // 2. Équipes : copie, puis budgets de départ et sièges des managers humains.
            [$teams, $seasonLength] = $this->createTeams($gameSave, $package);
            $this->applyStartingBudgetsAndSeats($gameSave, $teams, $humanTeamIds);

            // 3. Joueurs : on prépare d'abord les contrats (ils fixent les numéros de maillot),
            //    puis on insère les joueurs d'un coup avec leur numéro.
            $playerSources = $this->playerSources($package);
            [$contractPlan, $jerseyNumbers] = $isDraft
                ? [[], []]
                : $this->planContracts($package, $playerSources, $teams);

            $playerIdByKey = $this->insertPlayers($gameSave, $package, $playerSources, $jerseyNumbers, $seed, $now);

            // 4. Contrats et composition de départ (pas en draft : les effectifs se montent au draft).
            if (! $isDraft) {
                $this->insertContracts($gameSave, $contractPlan, $playerIdByKey, $seasonLength, $now);
                $this->organizeStartingLineups($gameSave, $teams);
            }

            // 5. Marché : quelques amateurs libres dès la semaine 1 (filet de sécurité).
            $this->amateurMarket->ensureFloor($gameSave);

            // 6. Dernière étape selon le mode.
            if ($isDraft) {
                $this->startDraft($gameSave, $teams);
            } else {
                // Semaine 1 : mandat de la direction, défis, calendrier, compositions IA.
                $this->seasonBootstrap->prepare($gameSave);
            }

            return $gameSave;
        });
    }

    // ──────────────────────────────────────────────────────────────
    //   1. SAUVEGARDE
    // ──────────────────────────────────────────────────────────────

    /**
     * Identifiants des équipes dirigées par des humains, dans l'ordre des sièges.
     *
     * Hot-seat : plusieurs équipes via team_ids ; sinon une seule via team_id.
     * Avec un package de période, ce sont des clés texte (team_key) internes au
     * package ; sans package, ce sont des id entiers de la table globale teams.
     *
     * @return array<int, int|string>
     */
    private function humanTeamIds(array $data, bool $hasPackage): array
    {
        $ids = $data['team_ids'] ?? array_filter([$data['team_id'] ?? null]);

        return $hasPackage
            ? array_values(array_unique($ids))
            : array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Insère la ligne game_saves : saison 1, semaine 1, phase « draft » ou « season »
     * selon le mode, et la configuration choisie à la création (state['config']).
     */
    private function createSave(User $user, array $data, ?PeriodPackage $package, bool $isDraft, array $humanTeamIds): GameSave
    {
        // Équipe propriétaire = siège 1. Avec un package il n'y a pas d'équipe
        // correspondante dans la table globale teams : la colonne reste vide (null).
        $primaryTeamId = $package ? null : ($humanTeamIds[0] ?? $data['team_id']);

        $initialState = ! empty($data['game_config']) ? ['config' => $data['game_config']] : null;

        return GameSave::create([
            'user_id'          => $user->id,
            'team_id'          => $primaryTeamId,
            'period'           => $data['period'],
            'season'           => 1,
            'week'             => 1,
            'phase'            => $isDraft ? GamePhase::Draft : GamePhase::Season,
            'game_mode'        => $data['game_mode'] ?? 'prebuilt',
            'competition_type' => $data['competition_type'] ?? 'college_league',
            'label'            => $data['label'] ?? null,
            'state'            => $initialState,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //   2. ÉQUIPES
    // ──────────────────────────────────────────────────────────────

    /**
     * Copie les équipes de référence (ou du package) dans game_teams.
     *
     * @return array{0: array<int|string, GameTeam>, 1: int}
     *         [équipes de la partie indexées par clé source (team_key ou id de référence),
     *          nombre de semaines d'une saison]
     */
    private function createTeams(GameSave $gameSave, ?PeriodPackage $package): array
    {
        $teams = [];

        if ($package) {
            $packageTeams = $package->teams_json;
            $seasonLength = SeasonService::lengthForTeamCount(count($packageTeams));

            foreach ($packageTeams as $t) {
                $teams[$t['team_key']] = GameTeam::create(
                    ['game_save_id' => $gameSave->id] + $this->teamAttributesFromPackage($t)
                );
            }
        } else {
            $baseTeams    = Team::orderBy('id')->get();
            $seasonLength = SeasonService::lengthForTeamCount($baseTeams->count());

            foreach ($baseTeams as $team) {
                $teams[$team->id] = GameTeam::create(
                    ['game_save_id' => $gameSave->id] + $this->teamAttributesFromBase($team)
                );
            }
        }

        return [$teams, $seasonLength];
    }

    /** Attributs d'une équipe de la partie, lus dans un élément teams_json d'un package. */
    private function teamAttributesFromPackage(array $t): array
    {
        return [
            'base_team_id'          => null, // pas de ligne correspondante dans la table globale
            'name'                  => $t['name'],
            'description'           => $t['description'] ?? null,
            'budget'                => $t['budget'] ?? 0,
            'wins'                  => 0,
            'draws'                 => 0,
            'losses'                => 0,
            'logo_path'             => $t['logo_path'] ?? null,
            'formation'             => $t['formation'] ?? self::DEFAULT_FORMATION,
            'tactical_style'        => $t['tactical_style'] ?? 'balanced',
            'management_philosophy' => $t['management_philosophy'] ?? 'collective',
        ];
    }

    /** Attributs d'une équipe de la partie, lus dans une équipe de la table globale teams. */
    private function teamAttributesFromBase(Team $team): array
    {
        return [
            'base_team_id'          => $team->id,
            'name'                  => $team->name,
            'description'           => $team->description,
            'budget'                => $team->budget,
            'wins'                  => 0,
            'draws'                 => 0,
            'losses'                => 0,
            'logo_path'             => $team->logo_path,
            'formation'             => $team->default_formation ?? self::DEFAULT_FORMATION,
            'tactical_style'        => $team->tactical_style ?? 'balanced',
            'management_philosophy' => $team->management_philosophy ?? 'collective',
        ];
    }

    /**
     * Fixe les budgets de départ et marque les équipes humaines.
     *
     *  - clubs IA : budget « ai_start_budget » de la configuration ;
     *  - équipes humaines : budget « human_start_budget », drapeau is_controlled
     *    et numéro de siège (1, 2, 3… dans l'ordre choisi) ;
     *  - le siège 1 devient l'équipe active de la partie (controlled_game_team_id).
     *
     * @param array<int|string, GameTeam> $teams équipes indexées par clé source
     */
    private function applyStartingBudgetsAndSeats(GameSave $gameSave, array $teams, array $humanTeamIds): void
    {
        // Clubs IA.
        $aiStartBudget = (int) $gameSave->getConfig('ai_start_budget', 2500);
        foreach ($teams as $sourceKey => $team) {
            if (! in_array($sourceKey, $humanTeamIds, false)) {
                $team->update(['budget' => $aiStartBudget]);
            }
        }

        // Équipes humaines, dans l'ordre des sièges.
        $humanStartBudget = (int) $gameSave->getConfig('human_start_budget');
        $seat             = 1;
        $firstControlled  = null;

        foreach ($humanTeamIds as $sourceKey) {
            $team = $teams[$sourceKey] ?? null;
            if (! $team) {
                continue; // identifiant inconnu : ignoré
            }

            $team->is_controlled = true;
            $team->human_seat    = $seat++;
            $team->budget        = $humanStartBudget;
            $team->save();

            $firstControlled ??= $team;
        }

        // Le joueur actif au départ est celui du siège 1.
        if ($firstControlled) {
            $gameSave->controlled_game_team_id = $firstControlled->id;
            $gameSave->save();
        }
    }

    // ──────────────────────────────────────────────────────────────
    //   3. JOUEURS
    // ──────────────────────────────────────────────────────────────

    /**
     * Joueurs à copier, indexés par leur clé source : player_key (package)
     * ou id de la table globale players.
     */
    private function playerSources(?PeriodPackage $package): Collection
    {
        return $package
            ? collect($package->players_json)->keyBy('player_key')
            : Player::orderBy('id')->get()->keyBy('id');
    }

    /**
     * Prépare les contrats (mode pré-fait) sans encore rien écrire en base.
     *
     * Dans chaque équipe, les 11 premiers contrats sont titulaires (maillots 1 à 11)
     * et les suivants remplaçants (maillots 12 et plus). Les numéros sont calculés
     * ici pour pouvoir insérer les joueurs avec leur numéro en une seule passe.
     *
     * @param  array<int|string, GameTeam> $teams équipes de la partie, indexées par clé source
     * @return array{0: array<int, array>, 1: array<int|string, int>}
     *         [plan de contrats, numéro de maillot par clé de joueur]
     */
    private function planContracts(?PeriodPackage $package, Collection $playerSources, array $teams): array
    {
        $contractsByTeam = $package
            ? collect($package->contracts_json)->groupBy('team_key')
            : Contract::orderBy('id')->get()->groupBy('team_id');

        $plan    = [];
        $numbers = [];

        foreach ($contractsByTeam as $teamKey => $teamContracts) {
            if (! isset($teams[$teamKey])) {
                continue; // contrat d'une équipe absente de la partie
            }

            $starterNumber = 1;
            $subNumber     = 12;

            foreach ($teamContracts->values() as $index => $c) {
                $playerKey = $package ? $c['player_key'] : $c->player_id;
                if (! $playerSources->has($playerKey)) {
                    continue; // contrat d'un joueur absent des données copiées
                }

                $isStarter = $index < 11;
                $numbers[$playerKey] = $isStarter ? $starterNumber++ : $subNumber++;

                $plan[] = [
                    'game_team_id' => $teams[$teamKey]->id,
                    'player_key'   => $playerKey,
                    'salary'       => ($package ? ($c['salary'] ?? 0) : $c->salary) ?? 0,
                    'is_starter'   => $isStarter,
                    'is_captain'   => ($package ? ($c['is_captain'] ?? false) : $c->is_captain) ?? false,
                ];
            }
        }

        return [$plan, $numbers];
    }

    /**
     * Insère tous les joueurs de la partie par paquets et renvoie leur id.
     *
     * Insertion en masse : elle contourne l'événement GamePlayer::creating, mais
     * la chance (luck) et le jeu de tête (heading) sont toujours fournis ici
     * (InitialPlayerStats / HeadingRating).
     *
     * @param  array<int|string, int> $jerseyNumbers numéro de maillot par clé de joueur
     * @return array<int|string, int> id du GamePlayer créé, par clé de joueur
     */
    private function insertPlayers(GameSave $gameSave, ?PeriodPackage $package, Collection $playerSources, array $jerseyNumbers, int $seed, $now): array
    {
        $rows = [];
        foreach ($playerSources as $key => $source) {
            $attributes = $package
                ? $this->playerAttributesFromPackage($gameSave, $seed, $source)
                : $this->playerAttributesFromBase($gameSave, $seed, $source);

            $rows[] = ['game_save_id' => $gameSave->id, 'number' => $jerseyNumbers[$key] ?? null]
                + $attributes
                + ['created_at' => $now, 'updated_at' => $now];
        }

        foreach (array_chunk($rows, self::PLAYER_CHUNK) as $chunk) {
            GamePlayer::insert($chunk);
        }

        // insert() ne renvoie pas les id. La partie est neuve et ne contient que ces
        // joueurs : l'ordre des id suit donc l'ordre d'insertion.
        $ids = GamePlayer::where('game_save_id', $gameSave->id)->orderBy('id')->pluck('id')->all();
        if (count($ids) !== count($rows)) {
            throw new \RuntimeException('Création de partie : nombre de joueurs inséré incohérent.');
        }

        return array_combine($playerSources->keys()->all(), $ids);
    }

    /** Attributs d'un joueur de la partie, lus dans un élément players_json d'un package. */
    private function playerAttributesFromPackage(GameSave $gameSave, int $seed, array $p): array
    {
        return [
            'base_player_id'      => null, // pas de ligne correspondante dans la table globale
            'firstname'           => $p['firstname'],
            'lastname'            => $p['lastname'],
            'position'            => $p['position'],
            'origin'              => $p['origin'] ?? null,
            'nationality'         => $p['nationality'] ?? null,
            'secondary_positions' => json_encode($p['secondary_positions'] ?? []),
            'description'         => $p['description'] ?? null,
            'photo_path'          => $p['photo_path'] ?? null,
            // Vitesse, endurance, moral et chance de départ (tirés d'après la graine de la partie).
            ...InitialPlayerStats::forPlayer($gameSave, $seed, $p['player_key'], (int) ($p['speed'] ?? 50)),
            'attack'              => $p['attack'] ?? 50,
            'defense'             => $p['defense'] ?? 50,
            'shot'                => $p['shot'] ?? 50,
            'pass'                => $p['pass'] ?? 50,
            'dribble'             => $p['dribble'] ?? 50,
            'block'               => $p['block'] ?? 50,
            'intercept'           => $p['intercept'] ?? 50,
            'tackle'              => $p['tackle'] ?? 50,
            'heading'             => $p['heading'] ?? HeadingRating::forProfile($p['position'], $p),
            'hand_save'           => $p['hand_save'] ?? 0,
            'punch_save'          => $p['punch_save'] ?? 0,
            'special_moves'       => json_encode($p['special_moves'] ?? []),
            'cost'                => $p['cost'] ?? 0,
        ];
    }

    /** Attributs d'un joueur de la partie, lus dans un joueur de la table globale players. */
    private function playerAttributesFromBase(GameSave $gameSave, int $seed, Player $player): array
    {
        // Anciennes données : certaines stats vivent encore dans la colonne JSON « stats ».
        $s = $player->stats ?? [];

        return [
            'base_player_id'      => $player->id,
            'firstname'           => $player->firstname,
            'lastname'            => $player->lastname,
            'position'            => $player->position,
            'origin'              => $player->origin,
            'nationality'         => $player->nationality,
            'secondary_positions' => json_encode($player->secondary_positions ?? []),
            'description'         => $player->description,
            'photo_path'          => $player->photo_path,
            ...InitialPlayerStats::forPlayer($gameSave, $seed, $player->id, (int) ($player->speed ?? $s['speed'] ?? 50)),
            'attack'              => $player->attack ?? $s['attack'] ?? 50,
            'defense'             => $player->defense ?? $s['defense'] ?? 50,
            'shot'                => $player->shot ?? $s['shot'] ?? 50,
            'pass'                => $player->pass ?? $s['pass'] ?? 50,
            'dribble'             => $player->dribble ?? $s['dribble'] ?? 50,
            'block'               => $player->block ?? $s['block'] ?? 50,
            'intercept'           => $player->intercept ?? $s['intercept'] ?? 50,
            'tackle'              => $player->tackle ?? $s['tackle'] ?? 50,
            'heading'             => $player->headingStat(),
            'hand_save'           => $player->hand_save ?? $s['hand_save'] ?? 0,
            'punch_save'          => $player->punch_save ?? $s['punch_save'] ?? 0,
            'special_moves'       => json_encode($player->special_moves ?? []),
            'cost'                => $player->cost ?? 0,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //   4. CONTRATS ET COMPOSITION DE DÉPART
    // ──────────────────────────────────────────────────────────────

    /**
     * Insère les contrats par paquets : de la semaine 1 à la dernière semaine
     * de la saison, avec trois relances de capitaine disponibles.
     *
     * Insertion en masse : elle contourne GameContract::created (défi de saison
     * « Recruter X joueurs »), ce qui est sans effet ici puisque les défis ne
     * sont pas encore tirés à la création.
     *
     * @param array<int, array>    $plan          plan préparé par planContracts()
     * @param array<int|string,int> $playerIdByKey id du GamePlayer par clé de joueur
     */
    private function insertContracts(GameSave $gameSave, array $plan, array $playerIdByKey, int $seasonLength, $now): void
    {
        $rows = array_map(fn (array $c) => [
            'game_save_id'                    => $gameSave->id,
            'game_team_id'                    => $c['game_team_id'],
            'game_player_id'                  => $playerIdByKey[$c['player_key']],
            'salary'                          => $c['salary'],
            'start_week'                      => 1,
            'end_week'                        => $seasonLength,
            'is_starter'                      => $c['is_starter'],
            'is_captain'                      => $c['is_captain'],
            'captain_rerolls_remaining'       => 3,
            'captain_reroll_used_this_action' => false,
            'created_at'                      => $now,
            'updated_at'                      => $now,
        ], $plan);

        foreach (array_chunk($rows, self::CONTRACT_CHUNK) as $chunk) {
            GameContract::insert($chunk);
        }
    }

    /**
     * Compose le onze de départ de chaque équipe.
     *
     * Les contrats ont été créés dans l'ordre du package ou de la base, sans notion
     * de poste : on réorganise chaque équipe selon sa formation et le niveau de ses
     * joueurs à leur poste (principal ou secondaire).
     *
     * @param array<int|string, GameTeam> $teams
     */
    private function organizeStartingLineups(GameSave $gameSave, array $teams): void
    {
        $state = $gameSave->gameState();

        foreach ($teams as $team) {
            $team->load('contracts.gamePlayer');
            $this->draftService->organizeTeamLineup($team, $gameSave, $state);
        }

        $gameSave->save();
    }

    // ──────────────────────────────────────────────────────────────
    //   6. DRAFT
    // ──────────────────────────────────────────────────────────────

    /**
     * Mode draft : tire au sort l'ordre de passage des équipes et prépare les
     * enveloppes de recrutement (DraftService::initDraft).
     *
     * @param array<int|string, GameTeam> $teams
     */
    private function startDraft(GameSave $gameSave, array $teams): void
    {
        $teamIds = $this->random->shuffle(array_map(fn (GameTeam $team) => $team->id, $teams));

        $this->draftService->initDraft($gameSave, $teamIds);
    }
}
