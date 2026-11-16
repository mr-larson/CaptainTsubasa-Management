<?php

namespace App\Services\Cards;

use App\Contracts\Randomizer;
use App\Helpers\FormationHelper;
use App\Models\BonusCard;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Concerns\LogsCoachAffinity;
use App\Services\Season\SeasonService;

/**
 * Effets des cartes malus (offensives, ciblant l'adversaire du prochain match,
 * une équipe choisie, le leader ou l'équipe classée juste devant) et des boucliers anti-malus. Extrait de
 * BonusCardActivationService, qui lui délègue l'activation.
 */
class MalusCardEffects
{
    use LogsCoachAffinity;

    /** Nom de l'équipe qui vient de renvoyer un malus avec un piège (lu puis effacé par pullReflection). */
    private ?string $reflectedBy = null;

    /** Injecte le générateur aléatoire (tirage du joueur consigné). */
    public function __construct(private Randomizer $random)
    {
    }

    /**
     * Carte « Cellule de crise » : pose un bouclier qui annule le prochain malus
     * subi par l'équipe (consommé lors d'une tentative de malus adverse).
     */
    public function applyMalusShield(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $teamId = (int) $gameBonusCard->game_team_id;

        $gameSave->gameState()->addMalusShield($teamId);
        $gameSave->save();

        return ['message' => '🛡️ Cellule de crise active : le prochain malus subi sera annulé.'];
    }

    /**
     * Carte « Piège » : le prochain malus qui vise l'équipe est renvoyé à son expéditeur
     * (consommé dans resolveMalusTarget, à la tentative de malus adverse).
     */
    public function applyMalusReflect(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $gameSave->gameState()->addMalusTrap((int) $gameBonusCard->game_team_id);
        $gameSave->save();

        return ['message' => '🍌 Piège posé : le prochain malus qui vous vise sera renvoyé à son expéditeur.'];
    }

    /**
     * Si le dernier malus appliqué a été renvoyé par un piège, retourne le nom de l'équipe qui
     * l'a renvoyé (une seule fois), sinon null. Lu par MalusEffect pour prévenir l'expéditeur.
     */
    public function pullReflection(): ?string
    {
        $name = $this->reflectedBy;
        $this->reflectedBy = null;

        return $name;
    }

    // ──────────────────────────────────────────────────────────
    //   MALUS (target = opponent / team)
    // ──────────────────────────────────────────────────────────

    /**
     * Carte malus « fatigue » : retire de la stamina aux titulaires de
     * l'adversaire du prochain match de l'équipe émettrice. La stamina étant
     * un pool régénéré chaque semaine, l'effet est temporaire et impacte
     * directement le match à venir.
     */
    public function applyOpponentStaminaDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        $opponentId = $target->id;
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }

        $amount = BonusCard::teamStaminaDrainAmount($gameBonusCard->tier);

        // Joueurs titulaires de l'adversaire (une seule fois chacun), puis une seule mise à jour.
        $drained = $this->subtractFromPlayers(
            GamePlayer::query()->where('game_save_id', $gameSave->id)->whereIn(
                'id',
                GameContract::where('game_team_id', $opponentId)
                    ->activeAt((int) ($gameSave->week ?? 1))
                    ->where('is_starter', true)
                    ->select('game_player_id')
            ),
            'stamina',
            $amount,
        );

        $opponentName = $target->team()?->name ?? 'L\'adversaire';

        return [
            'message' => "😴 {$opponentName} : −{$amount} stamina infligés à {$drained} titulaire(s) avant le prochain match.",
            'drained' => $drained,
        ];
    }

    /**
     * Carte malus « Croc-en-jambe » : fatigue légère, limitée à quelques titulaires de champ
     * tirés au hasard (`count`), et non à tout le onze. L'endurance est longue à reconstituer :
     * ce malus gêne la cible sans ruiner sa préparation. Le gardien est épargné.
     */
    public function applyOpponentPartialStaminaDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }

        $value  = $gameBonusCard->bonusCard->effect_value ?? [];
        $amount = (int) ($value['amount'] ?? 0);
        $count  = max(1, (int) ($value['count'] ?? 3));
        $week   = (int) ($gameSave->week ?? 1);

        $candidates = $this->opponentStarters($target->id)
            ->filter(fn (GameContract $contract) => $contract->isActive($week)
                && FormationHelper::roleFromPosition($contract->gamePlayer->position) !== 'GK')
            ->values()
            ->all();

        // Tirage sans remise de $count titulaires de champ.
        $chosenIds = [];
        while (count($chosenIds) < $count && $candidates !== []) {
            $pick = $this->random->pick($candidates);
            $chosenIds[] = (int) $pick->game_player_id;
            $candidates = array_values(array_filter($candidates, fn ($c) => $c !== $pick));
        }

        $drained = $this->subtractFromPlayers(
            GamePlayer::query()->where('game_save_id', $gameSave->id)->whereIn('id', $chosenIds),
            'stamina',
            $amount,
        );

        $name = $target->team()?->name ?? 'L\'adversaire';

        return [
            'message' => "🦵 {$name} : −{$amount} stamina infligés à {$drained} titulaire(s) de champ.",
            'drained' => $drained,
        ];
    }

    /**
     * Retire $amount à une colonne de stat (stamina, moral) d'un ensemble de joueurs, sans passer sous 0,
     * en UNE requête de mise à jour (au lieu d'une par joueur). Retourne le nombre de joueurs visés.
     * Le test CASE évite le dépassement des colonnes non signées.
     *
     * @param \Illuminate\Database\Eloquent\Builder<GamePlayer> $players joueurs visés
     * @param 'stamina'|'morale' $column
     */
    private function subtractFromPlayers(\Illuminate\Database\Eloquent\Builder $players, string $column, int $amount): int
    {
        $ids = $players->pluck('id');
        if ($ids->isEmpty()) return 0;

        GamePlayer::whereIn('id', $ids)->update([
            $column => DB::raw("CASE WHEN {$column} > {$amount} THEN {$column} - {$amount} ELSE 0 END"),
        ]);

        return $ids->count();
    }

    /**
     * Carte malus « titulaire consigné » : désigne un titulaire de l'adversaire
     * qui ne pourra pas débuter son prochain match. Le joueur est choisi
     * immédiatement (stable), puis le malus est stocké dans state.pending_malus
     * et appliqué à la construction du prochain match de la cible (écran de
     * match pour un humain, MatchSimulator pour une équipe IA).
     */
    public function applyOpponentBenchStarter(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        $opponentId = $target->id;
        $week = (int) ($gameSave->week ?? 1);
        $starters = $this->opponentStarters($opponentId)
            ->filter(fn (GameContract $contract) => $contract->isActive($week));

        // Candidats : titulaires de champ (on épargne le gardien pour ne pas
        // laisser la cible sans dernier rempart).
        $outfield = $starters
            ->filter(fn ($c) => FormationHelper::roleFromPosition($c->gamePlayer->position) !== 'GK');

        $alreadyBenched = $this->getBenchedPlayerIds($gameSave, $opponentId);
        $candidates = ($outfield->isNotEmpty() ? $outfield : $starters)
            ->reject(fn ($c) => in_array((int) $c->game_player_id, $alreadyBenched, true))
            ->values();

        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages(['card' => 'Aucun autre titulaire adverse ne peut être consigné.']);
        }
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }

        $pick = (string) ($gameBonusCard->bonusCard->effect_value['pick'] ?? 'random');
        $chosen = $pick === 'key'
            ? $candidates->sortByDesc(fn ($c) => $this->playerRating($c->gamePlayer))->first()
            : $this->random->pick($candidates->all());

        $player = $chosen->gamePlayer;
        $name   = trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')) ?: ($player->lastname ?? 'Joueur');

        $gameSave->gameState()->addPendingMalus([
            'game_bonus_card_id' => $gameBonusCard->id,
            'source_team_id'     => (int) $gameBonusCard->game_team_id,
            'target_team_id'     => $opponentId,
            'target_player_id'   => (int) $player->id,
            'player_name'        => $name,
            'effect_type'        => 'opponent_bench_starter',
            'card_name'          => $gameBonusCard->bonusCard->name,
            'icon'               => $gameBonusCard->bonusCard->icon,
            'created_season'     => $gameSave->season,
            'created_week'       => $gameSave->week,
        ]);
        $gameSave->save();

        $opponentName = $target->team()?->name ?? 'l\'adversaire';

        return [
            'message' => "🚫 {$name} ({$opponentName}) est consigné : il ne débutera pas le prochain match.",
        ];
    }

    /**
     * Carte malus « Guerre psychologique » : −moral à tout l'effectif cible.
     */
    public function applyOpponentMoraleDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        $targetId = $target->id;
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $updated = $this->subtractFromPlayers(
            GamePlayer::query()
                ->where('game_save_id', $gameSave->id)
                ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $targetId)),
            'morale',
            $amount,
        );

        $name = $target->team()?->name ?? 'L\'adversaire';

        return ['message' => "😤 {$name} : −{$amount} de moral infligé à {$updated} joueur(s)."];
    }

    /**
     * Carte malus « Tapage médiatique » : −relation coach du cadre adverse
     * (meilleur titulaire de champ). Peut le pousser vers la sortie.
     */
    public function applyOpponentAffinityDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        $targetId = $target->id;
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $starters   = $this->opponentStarters($targetId);
        $candidates = $starters
            ->filter(fn ($c) => $c->gamePlayer && FormationHelper::roleFromPosition($c->gamePlayer->position) !== 'GK')
            ->values();
        if ($candidates->isEmpty()) {
            $candidates = $starters->filter(fn ($c) => $c->gamePlayer)->values();
        }
        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages(['card' => 'L\'adversaire n\'a aucun titulaire à cibler.']);
        }

        $star   = $candidates->sortByDesc(fn ($c) => $this->playerRating($c->gamePlayer))->first();
        $player = $star->gamePlayer;
        $before = (int) ($player->coach_affinity ?? 0);
        $player->coach_affinity = max(-100, $before - $amount);
        $player->save();
        $this->logCoachAffinity($gameSave, $player, $player->coach_affinity - $before, $gameBonusCard->bonusCard->name);

        $name     = trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')) ?: ($player->lastname ?? 'Joueur');
        $teamName = $target->team()?->name ?? 'l\'adversaire';

        return ['message' => "🗞️ {$name} ({$teamName}) : −{$amount} de relation avec son coach (désormais {$player->coach_affinity})."];
    }

    /**
     * Carte malus « Contrôle fiscal » : −budget à l'équipe cible.
     */
    public function applyOpponentBudgetDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        $targetId = $target->id;
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $team = $target->team();
        if (!$team) {
            throw ValidationException::withMessages(['card' => 'Équipe cible introuvable.']);
        }

        $before = (int) ($team->budget ?? 0);
        $team->budget = max(0, $before - $amount);
        $team->save();
        $lost = $before - $team->budget;

        return ['message' => "💸 {$team->name} : −{$lost} GL de budget (désormais {$team->budget} GL)."];
    }

    /**
     * Carte malus « Robin des Bois » : prend jusqu'à `amount` au budget de l'équipe cible et le
     * verse à l'émetteur. Si la cible a moins, on ne prend que ce qu'elle a (jamais de budget
     * négatif) et l'émetteur ne reçoit que ce montant. Renvoyée par un piège, la carte coûte
     * simplement ce montant à son émetteur.
     */
    public function applyOpponentBudgetSteal(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }
        $amount   = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);
        $sourceId = (int) $gameBonusCard->game_team_id;

        $team = $target->team();
        if (!$team) {
            throw ValidationException::withMessages(['card' => 'Équipe cible introuvable.']);
        }

        $stolen = max(0, min($amount, (int) ($team->budget ?? 0)));
        $team->budget = (int) ($team->budget ?? 0) - $stolen;
        $team->save();

        if ($target->id === $sourceId) {
            return ['message' => "💰 Le vol se retourne contre vous : −{$stolen} GL de budget."];
        }

        GameTeam::where('id', $sourceId)->increment('budget', $stolen);

        return ['message' => $stolen > 0
            ? "💰 {$stolen} GL pris à {$team->name} et versés dans vos caisses."
            : "💰 Les caisses de {$team->name} sont vides : rien à prendre."];
    }

    /**
     * Carte malus « Pelouse sabotée » : debuff de stat appliqué à TOUTE l'équipe
     * cible pour son prochain match (stocké dans state.pending_stat_debuffs).
     */
    public function applyOpponentTeamStatDebuff(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        $targetId = $target->id;
        $card   = $gameBonusCard->bonusCard;
        $this->assertDebuffNotAlreadyPending($gameSave, $targetId, (string) $card->name);
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }

        $deltas = $this->parseDebuffDeltas($card->effect_value);

        $this->storeStatDebuff($gameSave, [
            'source_team_id'   => (int) $gameBonusCard->game_team_id,
            'target_team_id'   => $targetId,
            'target_player_id' => null,
            'deltas'           => $deltas,
            'card_name'        => $card->name,
            'icon'             => $card->icon,
            'created_week'     => $gameSave->week,
        ]);

        $name = $target->team()?->name ?? 'L\'adversaire';

        return ['message' => "🌧️ {$name} : {$this->deltaLabel($deltas)} à toute l'équipe pour son prochain match."];
    }

    /**
     * Carte malus « Marquage à la culotte » : debuff de stat sur le cadre adverse
     * (meilleur titulaire de champ) pour son prochain match.
     */
    public function applyOpponentKeyStatDebuff(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $target = $this->resolveMalusTarget($gameSave, $gameBonusCard, $targetTeamId);
        $targetId = $target->id;
        if ($blocked = $this->shieldBlock($gameSave, $target)) {
            return $blocked;
        }

        $starters   = $this->opponentStarters($targetId);
        $candidates = $starters
            ->filter(fn ($c) => $c->gamePlayer && FormationHelper::roleFromPosition($c->gamePlayer->position) !== 'GK')
            ->values();
        if ($candidates->isEmpty()) {
            $candidates = $starters->filter(fn ($c) => $c->gamePlayer)->values();
        }
        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages(['card' => 'L\'adversaire n\'a aucun titulaire à museler.']);
        }

        $star   = $candidates->sortByDesc(fn ($c) => $this->playerRating($c->gamePlayer))->first();
        $player = $star->gamePlayer;
        $card   = $gameBonusCard->bonusCard;
        $deltas = $this->parseDebuffDeltas($card->effect_value);

        $this->storeStatDebuff($gameSave, [
            'source_team_id'   => (int) $gameBonusCard->game_team_id,
            'target_team_id'   => $targetId,
            'target_player_id' => (int) $player->id,
            'deltas'           => $deltas,
            'card_name'        => $card->name,
            'icon'             => $card->icon,
            'created_week'     => $gameSave->week,
        ]);

        $name     = trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')) ?: ($player->lastname ?? 'Joueur');
        $teamName = $target->team()?->name ?? 'l\'adversaire';

        return ['message' => "🥅 {$name} ({$teamName}) sera muselé : {$this->deltaLabel($deltas)} pour son prochain match."];
    }

    /**
     * Convertit l'effect_value d'une carte debuff en deltas NÉGATIFS de stats.
     * Supporte {stat,amount} et {stats:{stat=>amount,...}}.
     *
     * @return array<string,int>
     */
    private function parseDebuffDeltas(array $ev): array
    {
        $deltas = [];
        if (isset($ev['stat'], $ev['amount'])) {
            $deltas[$ev['stat']] = -abs((int) $ev['amount']);
        }
        if (isset($ev['stats']) && is_array($ev['stats'])) {
            foreach ($ev['stats'] as $stat => $amount) {
                $deltas[$stat] = ($deltas[$stat] ?? 0) - abs((int) $amount);
            }
        }
        return $deltas;
    }

    /** Libellé lisible d'un jeu de deltas (ex : « −12 vitesse, −15 attaque »). */
    private function deltaLabel(array $deltas): string
    {
        $labels = [
            'speed' => 'vitesse', 'stamina' => 'stamina', 'attack' => 'attaque',
            'defense' => 'défense', 'shot' => 'tir', 'pass' => 'passe', 'dribble' => 'dribble',
        ];
        $parts = [];
        foreach ($deltas as $stat => $v) {
            $parts[] = ($v >= 0 ? '+' : '−') . abs($v) . ' ' . ($labels[$stat] ?? $stat);
        }
        return implode(', ', $parts) ?: 'aucun effet';
    }

    /**
     * Refuse d'empiler deux fois le même debuff d'équipe sur une cible avant qu'elle ait joué
     * (sinon plusieurs équipes pourraient cumuler la même carte sur le leader). Vérifié AVANT le
     * bouclier, pour ne pas le consommer sur une activation de toute façon refusée.
     */
    private function assertDebuffNotAlreadyPending(GameSave $gameSave, int $targetId, string $cardName): void
    {
        foreach ($gameSave->gameState()->pendingStatDebuffs() as $debuff) {
            if ((int) ($debuff['target_team_id'] ?? 0) === $targetId
                && empty($debuff['target_player_id'])
                && ($debuff['card_name'] ?? null) === $cardName) {
                throw ValidationException::withMessages([
                    'card' => 'Cette équipe subit déjà cette carte pour son prochain match.',
                ]);
            }
        }
    }

    /** Ajoute un debuff de stats en attente dans l'état de la partie et sauvegarde. */
    private function storeStatDebuff(GameSave $gameSave, array $entry): void
    {
        $gameSave->gameState()->addPendingStatDebuff($entry);
        $gameSave->save();
    }

    /**
     * Bouclier anti-malus (carte « Cellule de crise ») : si l'équipe cible a un
     * bouclier actif, le consomme et annule le malus. Retourne le message de
     * blocage, ou null si pas de bouclier.
     */
    private function shieldBlock(GameSave $gameSave, MalusTarget $target): ?array
    {
        if (!$this->consumeMalusShield($gameSave, $target->id)) {
            return null;
        }

        $name = $target->team()?->name ?? 'L\'adversaire';

        return [
            'message' => "🛡️ {$name} a paré le malus avec une cellule de crise ! Effet annulé.",
            'blocked' => true,
        ];
    }

    /** Consomme un bouclier anti-malus de l'équipe s'il existe (sauvegarde si oui) ; retourne true si un bouclier a été consommé. */
    private function consumeMalusShield(GameSave $gameSave, int $targetId): bool
    {
        if (! $gameSave->gameState()->consumeMalusShield($targetId)) {
            return false;
        }

        $gameSave->save();

        return true;
    }

    /**
     * Identifie l'équipe adverse du prochain match programmé de $teamId.
     */
    public function resolveNextOpponentTeamId(GameSave $gameSave, int $teamId): int
    {
        $match = GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'scheduled')
            ->where('week', '>=', $gameSave->week ?? 1)
            ->where(fn ($q) => $q
                ->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId))
            ->orderBy('week')
            ->first();

        if (!$match) {
            throw ValidationException::withMessages(['card' => 'Aucun match à venir : impossible de cibler un adversaire.']);
        }

        return (int) $match->home_team_id === $teamId
            ? (int) $match->away_team_id
            : (int) $match->home_team_id;
    }

    /**
     * Résout l'équipe cible d'un malus selon le mode de la carte :
     *   - target=opponent              → adversaire du prochain match (auto)
     *   - target=team, mode=leader     → 1er du classement (hors soi)
     *   - target=team, mode=ahead      → équipe classée juste devant l'émetteur
     *   - target=team, mode=select     → équipe explicitement choisie (humain)
     *                                    ; repli sur le leader si aucune cible
     *                                    fournie (cas IA).
     */
    private function resolveMalusTarget(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $explicitTargetTeamId): MalusTarget
    {
        $this->reflectedBy = null;
        $target   = $this->resolveIntendedTarget($gameSave, $gameBonusCard, $explicitTargetTeamId);
        $sourceId = (int) $gameBonusCard->game_team_id;

        // Carte « Piège » : la cible renvoie le malus à l'expéditeur, qui le subit à sa place.
        if ($target->id !== $sourceId && $gameSave->gameState()->consumeMalusTrap($target->id)) {
            $gameSave->save();
            $this->reflectedBy = $target->team()?->name ?? 'L\'adversaire';

            return new MalusTarget($sourceId);
        }

        return $target;
    }

    /** Équipe que la carte vise d'après son mode de ciblage, avant tout renvoi par un piège. */
    private function resolveIntendedTarget(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $explicitTargetTeamId): MalusTarget
    {
        $card         = $gameBonusCard->bonusCard;
        $sourceTeamId = (int) $gameBonusCard->game_team_id;

        if ($card->target !== \App\Enums\BonusCardTarget::Team) {
            return new MalusTarget($this->resolveNextOpponentTeamId($gameSave, $sourceTeamId));
        }

        $mode = (string) ($card->effect_value['target_mode'] ?? 'select');

        if ($mode === 'leader') {
            return $this->standingsLeader($gameSave->gameTeams()->get(), $sourceTeamId);
        }

        if ($mode === 'ahead') {
            return $this->teamAhead($gameSave->gameTeams()->get(), $sourceTeamId);
        }

        // mode 'select'
        if ($explicitTargetTeamId) {
            $valid = GameTeam::where('game_save_id', $gameSave->id)
                ->where('id', $explicitTargetTeamId)
                ->where('id', '!=', $sourceTeamId)
                ->exists();

            if (!$valid) {
                throw ValidationException::withMessages(['card' => 'Équipe cible invalide.']);
            }

            return new MalusTarget($explicitTargetTeamId);
        }

        // Aucune cible fournie : un humain doit choisir, l'IA tape le leader. Les équipes sont
        // lues une seule fois pour savoir si l'émetteur est humain ET pour trouver le leader.
        $teams = $gameSave->gameTeams()->get();
        if ($teams->firstWhere('id', $sourceTeamId)?->is_controlled) {
            throw ValidationException::withMessages(['card' => 'Vous devez choisir une équipe cible.']);
        }

        return $this->standingsLeader($teams, $sourceTeamId);
    }

    /**
     * Équipe en tête du classement officiel (points, différence de buts…), hors $excludeTeamId, parmi
     * les équipes déjà lues. L'équipe trouvée est transmise à la cible : pas de relecture.
     *
     * @param \Illuminate\Support\Collection<int, GameTeam> $teams toutes les équipes de la partie
     */
    private function standingsLeader(\Illuminate\Support\Collection $teams, int $excludeTeamId): MalusTarget
    {
        $leader = app(SeasonService::class)->sortStandings($teams)
            ->first(fn ($t) => (int) $t->id !== $excludeTeamId);

        if (!$leader) {
            throw ValidationException::withMessages(['card' => 'Aucune équipe cible disponible.']);
        }

        return new MalusTarget((int) $leader->id, $leader);
    }

    /**
     * Équipe classée juste devant $sourceTeamId (la « carapace rouge »). Refusé si l'émetteur
     * est déjà en tête : il n'a personne devant lui.
     *
     * @param \Illuminate\Support\Collection<int, GameTeam> $teams toutes les équipes de la partie
     */
    private function teamAhead(\Illuminate\Support\Collection $teams, int $sourceTeamId): MalusTarget
    {
        $sorted = app(SeasonService::class)->sortStandings($teams);
        $index  = $sorted->search(fn ($t) => (int) $t->id === $sourceTeamId);

        if ($index === false || $index === 0) {
            throw ValidationException::withMessages(['card' => 'Aucune équipe n\'est classée devant vous.']);
        }

        $ahead = $sorted[$index - 1];

        return new MalusTarget((int) $ahead->id, $ahead);
    }

    /**
     * Contrats titulaires (avec joueur chargé) d'une équipe.
     *
     * @return \Illuminate\Support\Collection<int, GameContract>
     */
    private function opponentStarters(int $teamId): \Illuminate\Support\Collection
    {
        return GameContract::where('game_team_id', $teamId)
            ->where('is_starter', true)
            ->with('gamePlayer')
            ->get()
            ->filter(fn ($c) => $c->gamePlayer)
            ->values();
    }

    /**
     * Note synthétique d'un joueur de champ (proxy pour désigner un « cadre »).
     */
    private function playerRating(GamePlayer $p): int
    {
        return (int) (($p->attack ?? 0) + ($p->shot ?? 0) + ($p->dribble ?? 0)
            + ($p->pass ?? 0) + ($p->defense ?? 0) + ($p->speed ?? 0));
    }

    /**
     * IDs des joueurs d'une équipe à priver de titularisation au prochain match
     * (malus « titulaire consigné » en attente). Lu à la construction du match.
     *
     * @return array<int, int>
     */
    public function getBenchedPlayerIds(GameSave $gameSave, int $teamId): array
    {
        $entries = $gameSave->gameState()->pendingMalus();

        return array_values(array_unique(array_map(
            fn ($m) => (int) $m['target_player_id'],
            array_filter($entries, fn ($m) =>
                (int) ($m['target_team_id'] ?? 0) === $teamId
                && ($m['effect_type'] ?? '') === 'opponent_bench_starter'
                && !empty($m['target_player_id'])
            )
        )));
    }

    /**
     * Retire les malus « titulaire consigné » dont l'équipe cible a disputé son
     * match cette semaine. Appelé une seule fois en fin de semaine, sur
     * l'instance GameSave du contrôleur (pour éviter les conflits d'instances
     * dans la boucle de simulation des matchs IA).
     */
    public function consumeMalusForPlayedWeek(GameSave $gameSave, int $week): void
    {
        $state   = $gameSave->gameState();
        $entries = $state->pendingMalus();
        if (empty($entries)) return;

        $playedTeamIds = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'played')
            ->get(['home_team_id', 'away_team_id'])
            ->flatMap(fn ($m) => [(int) $m->home_team_id, (int) $m->away_team_id])
            ->unique()
            ->all();

        if (empty($playedTeamIds)) return;

        $remaining = array_filter($entries, fn ($m) =>
            !(($m['effect_type'] ?? '') === 'opponent_bench_starter'
              && in_array((int) ($m['target_team_id'] ?? 0), $playedTeamIds, true))
        );

        $state->putPendingMalus($remaining);
        $gameSave->save();
    }

}
