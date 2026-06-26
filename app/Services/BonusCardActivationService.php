<?php

namespace App\Services;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Validation\ValidationException;

class BonusCardActivationService
{
    /**
     * Active une carte depuis le tab Bonus (cartes immediate).
     * Les cartes pre_match sont activées automatiquement dans MatchSimulator.
     *
     * @param  GameBonusCard  $gameBonusCard
     * @param  GameSave       $gameSave
     * @param  int|null       $targetPlayerId  (requis pour target=player)
     */
    public function activate(
        GameBonusCard $gameBonusCard,
        GameSave $gameSave,
        ?int $targetPlayerId = null,
        ?int $targetTeamId = null
    ): array {
        if (!$gameBonusCard->isAvailable()) {
            throw ValidationException::withMessages(['card' => 'Cette carte a déjà été utilisée.']);
        }

        $card = $gameBonusCard->bonusCard;

        // Valider le joueur cible si nécessaire
        if ($card->target === 'player') {
            if (!$targetPlayerId) {
                throw ValidationException::withMessages(['card' => 'Vous devez sélectionner un joueur cible.']);
            }
            $gameBonusCard->target_player_id = $targetPlayerId;
        }

        $result = match ($card->effect_type) {
            'stamina_boost'         => $this->applyStaminaBoost($gameSave, $gameBonusCard),
            'injury_reduce',
            'injury_cure'           => $this->applyInjuryReduce($gameSave, $gameBonusCard, $targetPlayerId),
            'revenue_gamble'        => $this->applyRevenueGamble($gameSave, $gameBonusCard),
            'revenue_challenge'     => $this->registerSponsorChallenge($gameSave, $gameBonusCard),
            'morale_boost'          => $this->applyMoraleBoost($gameSave, $gameBonusCard, $targetPlayerId),
            'coach_affinity_boost'  => $this->applyCoachAffinityBoost($gameSave, $gameBonusCard, $targetPlayerId),
            'team_morale_boost'     => $this->applyTeamMoraleBoost($gameSave, $gameBonusCard),
            'revenue_performance'   => $this->applyRevenuePerformance($gameSave, $gameBonusCard),
            'malus_shield'          => $this->applyMalusShield($gameSave, $gameBonusCard),
            // malus : ciblent l'adversaire du prochain match, une équipe choisie ou le leader
            'opponent_stamina_drain' => $this->applyOpponentStaminaDrain($gameSave, $gameBonusCard, $targetTeamId),
            'opponent_bench_starter' => $this->applyOpponentBenchStarter($gameSave, $gameBonusCard, $targetTeamId),
            'opponent_morale_drain'  => $this->applyOpponentMoraleDrain($gameSave, $gameBonusCard, $targetTeamId),
            'opponent_affinity_drain'=> $this->applyOpponentAffinityDrain($gameSave, $gameBonusCard, $targetTeamId),
            'opponent_budget_drain'  => $this->applyOpponentBudgetDrain($gameSave, $gameBonusCard, $targetTeamId),
            'opponent_team_stat_debuff' => $this->applyOpponentTeamStatDebuff($gameSave, $gameBonusCard, $targetTeamId),
            'opponent_key_stat_debuff'  => $this->applyOpponentKeyStatDebuff($gameSave, $gameBonusCard, $targetTeamId),
            // pre_match : juste marquer comme activée, MatchSimulator la lira
            'stat_boost',
            'stat_boost_and_stamina'=> $this->markPreMatchCard($gameBonusCard, $gameSave),
            default                 => throw ValidationException::withMessages(['card' => 'Type d\'effet inconnu.']),
        };

        // Marquer comme utilisée
        $gameBonusCard->status     = 'used';
        $gameBonusCard->used_season = $gameSave->season;
        $gameBonusCard->used_week   = $gameSave->week;
        $gameBonusCard->save();

        return $result;
    }

    // ──────────────────────────────────────────────────────────
    //   EFFETS
    // ──────────────────────────────────────────────────────────

    private function applyStaminaBoost(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $amount  = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);
        $teamId  = $gameBonusCard->game_team_id;

        $players = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $teamId))
            ->get();

        $updated = 0;
        foreach ($players as $player) {
            $player->stamina = min(100, ($player->stamina ?? 0) + $amount);
            $player->save();
            $updated++;
        }

        return ['message' => "+{$amount} stamina appliqué à {$updated} joueurs.", 'updated' => $updated];
    }

    private function applyInjuryReduce(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId): array
    {
        if (!$targetPlayerId) {
            throw ValidationException::withMessages(['card' => 'Joueur cible requis.']);
        }

        $weeks = (int) ($gameBonusCard->bonusCard->effect_value['weeks'] ?? 2);
        $isCure = $gameBonusCard->bonusCard->effect_type === 'injury_cure';

        $injury = GameInjury::query()
            ->where('game_save_id', $gameSave->id)
            ->where('game_player_id', $targetPlayerId)
            ->where('week_return', '>', $gameSave->week)
            ->orderByDesc('week_return')
            ->first();

        if (!$injury) {
            throw ValidationException::withMessages(['card' => 'Ce joueur n\'est pas blessé.']);
        }

        if ($isCure) {
            $injury->week_return = $gameSave->week; // retour immédiat
        } else {
            $injury->week_return = max($gameSave->week, $injury->week_return - $weeks);
        }
        $injury->save();

        $msg = $isCure
            ? 'Joueur guéri immédiatement !'
            : "Retour avancé de {$weeks} semaine(s). Retour semaine {$injury->week_return}.";

        return ['message' => $msg, 'week_return' => $injury->week_return];
    }

    /**
     * Carte Pub : pari à résolution immédiate. Le gain est tiré au hasard entre
     * min et max (peut être négatif si la campagne fait un flop).
     */
    private function applyRevenueGamble(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $min = (int) ($gameBonusCard->bonusCard->effect_value['min'] ?? 0);
        $max = (int) ($gameBonusCard->bonusCard->effect_value['max'] ?? 0);
        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        $gain = random_int($min, $max);

        $team = GameTeam::find($gameBonusCard->game_team_id);
        if (!$team) {
            throw ValidationException::withMessages(['card' => 'Équipe introuvable.']);
        }

        $team->budget = max(0, ($team->budget ?? 0) + $gain);
        $team->save();

        $abs = abs($gain);
        $msg = $gain >= 0
            ? "📈 La campagne a payé : +{$abs} € au budget (désormais {$team->budget} €)."
            : "📉 La campagne a fait un flop : −{$abs} € sur le budget (désormais {$team->budget} €).";

        return ['message' => $msg, 'gain' => $gain, 'new_budget' => $team->budget];
    }

    /**
     * Carte Sponsor : enregistre un défi à atteindre au prochain match.
     * La récompense est versée (ou non) lors de la résolution post-match.
     */
    private function registerSponsorChallenge(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $card      = $gameBonusCard->bonusCard;
        $challenge = (string) ($card->effect_value['challenge'] ?? '');
        $reward    = (int) ($card->effect_value['reward'] ?? 0);

        $state = $gameSave->state ?? [];
        $state['pending_sponsor_challenges'][] = [
            'game_bonus_card_id' => $gameBonusCard->id,
            'game_team_id'       => $gameBonusCard->game_team_id,
            'challenge'          => $challenge,
            'reward'             => $reward,
            'card_name'          => $card->name,
            'icon'               => $card->icon,
            'season'             => $gameSave->season,
            'week'               => $gameSave->week,
        ];
        $gameSave->state = $state;
        $gameSave->save();

        return ['message' => "Défi sponsor activé : {$card->name}. Récompense de {$reward} € au prochain match si l'objectif est atteint."];
    }

    private function applyMoraleBoost(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId): array
    {
        $player = $this->resolveTargetPlayer($gameSave, $gameBonusCard, $targetPlayerId);
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $before          = (int) ($player->morale ?? 0);
        $player->morale  = min(100, max(0, $before + $amount));
        $player->save();

        $gain = $player->morale - $before;

        GamePlayerMoraleLog::create([
            'game_save_id'   => $gameSave->id,
            'game_player_id' => $player->id,
            'source'         => 'bonus_card',
            'value'          => $gain,
            'label'          => $gameBonusCard->bonusCard->name,
            'week'           => $gameSave->week,
            'season'         => $gameSave->season,
        ]);

        return [
            'message'    => "+{$gain} de moral pour {$player->lastname} (désormais {$player->morale}).",
            'new_morale' => $player->morale,
        ];
    }

    private function applyCoachAffinityBoost(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId): array
    {
        $player = $this->resolveTargetPlayer($gameSave, $gameBonusCard, $targetPlayerId);
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $before                 = (int) ($player->coach_affinity ?? 0);
        $player->coach_affinity = min(100, max(-100, $before + $amount));
        $player->save();

        $gain = $player->coach_affinity - $before;

        return [
            'message'            => "+{$gain} de relation avec le coach pour {$player->lastname} (désormais {$player->coach_affinity}).",
            'new_coach_affinity' => $player->coach_affinity,
        ];
    }

    /**
     * Carte « Esprit d'équipe » : +moral à tout l'effectif.
     */
    private function applyTeamMoraleBoost(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);
        $teamId = (int) $gameBonusCard->game_team_id;

        $players = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $teamId))
            ->get();

        $updated = 0;
        foreach ($players as $player) {
            $player->morale = min(100, max(0, (int) ($player->morale ?? 0) + $amount));
            $player->save();
            $updated++;
        }

        return ['message' => "💛 +{$amount} de moral pour {$updated} joueur(s) de l'effectif.", 'updated' => $updated];
    }

    /**
     * Carte « Vente de maillots » : revenu indexé sur le dernier match joué de
     * l'équipe (bonus par but marqué + prime de victoire). Récompense la forme.
     */
    private function applyRevenuePerformance(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $ev      = $gameBonusCard->bonusCard->effect_value ?? [];
        $base    = (int) ($ev['base'] ?? 200);
        $perGoal = (int) ($ev['per_goal'] ?? 150);
        $winBon  = (int) ($ev['win_bonus'] ?? 500);

        $teamId = (int) $gameBonusCard->game_team_id;
        $team   = GameTeam::find($teamId);
        if (!$team) {
            throw ValidationException::withMessages(['card' => 'Équipe introuvable.']);
        }

        $match = GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'played')
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->orderByDesc('week')
            ->first();

        $scored = 0; $won = false;
        if ($match) {
            $isHome  = (int) $match->home_team_id === $teamId;
            $scored  = (int) ($isHome ? $match->home_score : $match->away_score);
            $against = (int) ($isHome ? $match->away_score : $match->home_score);
            $won     = $scored > $against;
        }

        $gain = $base + $scored * $perGoal + ($won ? $winBon : 0);
        $team->budget = ($team->budget ?? 0) + $gain;
        $team->save();

        $detail = $match
            ? "(dernier match : {$scored} but(s)" . ($won ? ', victoire' : '') . ')'
            : '(aucun match joué récemment)';

        return ['message' => "👕 Vente de maillots : +{$gain} € au budget {$detail}.", 'gain' => $gain];
    }

    /**
     * Carte « Cellule de crise » : pose un bouclier qui annule le prochain malus
     * subi par l'équipe (consommé lors d'une tentative de malus adverse).
     */
    private function applyMalusShield(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $teamId = (int) $gameBonusCard->game_team_id;

        $state = $gameSave->state ?? [];
        $state['malus_shields'][] = $teamId;
        $gameSave->state = $state;
        $gameSave->save();

        return ['message' => '🛡️ Cellule de crise active : le prochain malus subi sera annulé.'];
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
    private function applyOpponentStaminaDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $opponentId = $this->resolveMalusTargetTeamId($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $opponentId)) {
            return $blocked;
        }
        $amount     = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $starters = $this->opponentStarters($opponentId);

        $drained = 0;
        foreach ($starters as $contract) {
            $player = $contract->gamePlayer;
            if (!$player) continue;
            $player->stamina = max(0, (int) ($player->stamina ?? 0) - $amount);
            $player->save();
            $drained++;
        }

        $opponentName = GameTeam::find($opponentId)?->name ?? 'L\'adversaire';

        return [
            'message' => "😴 {$opponentName} : −{$amount} stamina infligés à {$drained} titulaire(s) avant le prochain match.",
            'drained' => $drained,
        ];
    }

    /**
     * Carte malus « titulaire consigné » : désigne un titulaire de l'adversaire
     * qui ne pourra pas débuter son prochain match. Le joueur est choisi
     * immédiatement (stable), puis le malus est stocké dans state.pending_malus
     * et appliqué à la construction du prochain match de la cible (écran de
     * match pour un humain, MatchSimulator pour une équipe IA).
     */
    private function applyOpponentBenchStarter(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $opponentId = $this->resolveMalusTargetTeamId($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $opponentId)) {
            return $blocked;
        }
        $pick       = (string) ($gameBonusCard->bonusCard->effect_value['pick'] ?? 'random');

        // Candidats : titulaires de champ (on épargne le gardien pour ne pas
        // laisser la cible sans dernier rempart).
        $candidates = $this->opponentStarters($opponentId)
            ->filter(fn ($c) => $c->gamePlayer && strtoupper((string) $c->gamePlayer->position) !== 'GK')
            ->values();

        if ($candidates->isEmpty()) {
            // Repli : tous les titulaires (effectif réduit / que des gardiens).
            $candidates = $this->opponentStarters($opponentId)->filter(fn ($c) => $c->gamePlayer)->values();
        }

        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages(['card' => 'L\'adversaire n\'a aucun titulaire à consigner.']);
        }

        $chosen = $pick === 'key'
            ? $candidates->sortByDesc(fn ($c) => $this->playerRating($c->gamePlayer))->first()
            : $candidates->random();

        $player = $chosen->gamePlayer;
        $name   = trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')) ?: ($player->lastname ?? 'Joueur');

        $state = $gameSave->state ?? [];
        $state['pending_malus'][] = [
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
        ];
        $gameSave->state = $state;
        $gameSave->save();

        $opponentName = GameTeam::find($opponentId)?->name ?? 'l\'adversaire';

        return [
            'message' => "🚫 {$name} ({$opponentName}) est consigné : il ne débutera pas le prochain match.",
        ];
    }

    /**
     * Carte malus « Guerre psychologique » : −moral à tout l'effectif cible.
     */
    private function applyOpponentMoraleDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $targetId = $this->resolveMalusTargetTeamId($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $targetId)) {
            return $blocked;
        }
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $players = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $targetId))
            ->get();

        $updated = 0;
        foreach ($players as $player) {
            $player->morale = max(0, (int) ($player->morale ?? 0) - $amount);
            $player->save();
            $updated++;
        }

        $name = GameTeam::find($targetId)?->name ?? 'L\'adversaire';

        return ['message' => "😤 {$name} : −{$amount} de moral infligé à {$updated} joueur(s)."];
    }

    /**
     * Carte malus « Tapage médiatique » : −relation coach du cadre adverse
     * (meilleur titulaire de champ). Peut le pousser vers la sortie.
     */
    private function applyOpponentAffinityDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $targetId = $this->resolveMalusTargetTeamId($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $targetId)) {
            return $blocked;
        }
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $candidates = $this->opponentStarters($targetId)
            ->filter(fn ($c) => $c->gamePlayer && strtoupper((string) $c->gamePlayer->position) !== 'GK')
            ->values();
        if ($candidates->isEmpty()) {
            $candidates = $this->opponentStarters($targetId)->filter(fn ($c) => $c->gamePlayer)->values();
        }
        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages(['card' => 'L\'adversaire n\'a aucun titulaire à cibler.']);
        }

        $star   = $candidates->sortByDesc(fn ($c) => $this->playerRating($c->gamePlayer))->first();
        $player = $star->gamePlayer;
        $player->coach_affinity = max(-100, (int) ($player->coach_affinity ?? 0) - $amount);
        $player->save();

        $name     = trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')) ?: ($player->lastname ?? 'Joueur');
        $teamName = GameTeam::find($targetId)?->name ?? 'l\'adversaire';

        return ['message' => "🗞️ {$name} ({$teamName}) : −{$amount} de relation avec son coach (désormais {$player->coach_affinity})."];
    }

    /**
     * Carte malus « Contrôle fiscal » : −budget à l'équipe cible.
     */
    private function applyOpponentBudgetDrain(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $targetId = $this->resolveMalusTargetTeamId($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $targetId)) {
            return $blocked;
        }
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $team = GameTeam::find($targetId);
        if (!$team) {
            throw ValidationException::withMessages(['card' => 'Équipe cible introuvable.']);
        }

        $before = (int) ($team->budget ?? 0);
        $team->budget = max(0, $before - $amount);
        $team->save();
        $lost = $before - $team->budget;

        return ['message' => "💸 {$team->name} : −{$lost} € de budget (désormais {$team->budget} €)."];
    }

    /**
     * Carte malus « Pelouse sabotée » : debuff de stat appliqué à TOUTE l'équipe
     * cible pour son prochain match (stocké dans state.pending_stat_debuffs).
     */
    private function applyOpponentTeamStatDebuff(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $targetId = $this->resolveMalusTargetTeamId($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $targetId)) {
            return $blocked;
        }

        $card   = $gameBonusCard->bonusCard;
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

        $name = GameTeam::find($targetId)?->name ?? 'L\'adversaire';

        return ['message' => "🌧️ {$name} : {$this->deltaLabel($deltas)} à toute l'équipe pour son prochain match."];
    }

    /**
     * Carte malus « Marquage à la culotte » : debuff de stat sur le cadre adverse
     * (meilleur titulaire de champ) pour son prochain match.
     */
    private function applyOpponentKeyStatDebuff(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetTeamId = null): array
    {
        $targetId = $this->resolveMalusTargetTeamId($gameSave, $gameBonusCard, $targetTeamId);
        if ($blocked = $this->shieldBlock($gameSave, $targetId)) {
            return $blocked;
        }

        $candidates = $this->opponentStarters($targetId)
            ->filter(fn ($c) => $c->gamePlayer && strtoupper((string) $c->gamePlayer->position) !== 'GK')
            ->values();
        if ($candidates->isEmpty()) {
            $candidates = $this->opponentStarters($targetId)->filter(fn ($c) => $c->gamePlayer)->values();
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
        $teamName = GameTeam::find($targetId)?->name ?? 'l\'adversaire';

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

    private function storeStatDebuff(GameSave $gameSave, array $entry): void
    {
        $state = $gameSave->state ?? [];
        $state['pending_stat_debuffs'][] = $entry;
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Bouclier anti-malus (carte « Cellule de crise ») : si l'équipe cible a un
     * bouclier actif, le consomme et annule le malus. Retourne le message de
     * blocage, ou null si pas de bouclier.
     */
    private function shieldBlock(GameSave $gameSave, int $targetId): ?array
    {
        if (!$this->consumeMalusShield($gameSave, $targetId)) {
            return null;
        }

        $name = GameTeam::find($targetId)?->name ?? 'L\'adversaire';

        return [
            'message' => "🛡️ {$name} a paré le malus avec une cellule de crise ! Effet annulé.",
            'blocked' => true,
        ];
    }

    private function consumeMalusShield(GameSave $gameSave, int $targetId): bool
    {
        $state   = $gameSave->state ?? [];
        $shields = array_map('intval', $state['malus_shields'] ?? []);

        $idx = array_search($targetId, $shields, true);
        if ($idx === false) {
            return false;
        }

        unset($shields[$idx]);
        $state['malus_shields'] = array_values($shields);
        $gameSave->state = $state;
        $gameSave->save();

        return true;
    }

    /**
     * Identifie l'équipe adverse du prochain match programmé de $teamId.
     */
    private function resolveNextOpponentTeamId(GameSave $gameSave, int $teamId): int
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
     *   - target=team, mode=select     → équipe explicitement choisie (humain)
     *                                    ; repli sur le leader si aucune cible
     *                                    fournie (cas IA).
     */
    private function resolveMalusTargetTeamId(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $explicitTargetTeamId): int
    {
        $card         = $gameBonusCard->bonusCard;
        $sourceTeamId = (int) $gameBonusCard->game_team_id;

        if ($card->target !== 'team') {
            return $this->resolveNextOpponentTeamId($gameSave, $sourceTeamId);
        }

        $mode = (string) ($card->effect_value['target_mode'] ?? 'select');

        if ($mode === 'leader') {
            return $this->resolveStandingsLeaderTeamId($gameSave, $sourceTeamId);
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

            return $explicitTargetTeamId;
        }

        // Aucune cible fournie : un humain doit choisir, l'IA tape le leader.
        $controlledIds = $gameSave->controlledGameTeamIds();
        if (in_array($sourceTeamId, $controlledIds, true)) {
            throw ValidationException::withMessages(['card' => 'Vous devez choisir une équipe cible.']);
        }

        return $this->resolveStandingsLeaderTeamId($gameSave, $sourceTeamId);
    }

    /**
     * Équipe en tête du classement (points = 3·victoires + nuls), hors $excludeTeamId.
     */
    private function resolveStandingsLeaderTeamId(GameSave $gameSave, int $excludeTeamId): int
    {
        $leader = $gameSave->gameTeams()->get()
            ->sortByDesc(fn ($t) => ($t->wins ?? 0) * 3 + ($t->draws ?? 0))
            ->first(fn ($t) => (int) $t->id !== $excludeTeamId);

        if (!$leader) {
            throw ValidationException::withMessages(['card' => 'Aucune équipe cible disponible.']);
        }

        return (int) $leader->id;
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
        $entries = $gameSave->state['pending_malus'] ?? [];

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
        $state   = $gameSave->state ?? [];
        $entries = $state['pending_malus'] ?? [];
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

        $state['pending_malus'] = array_values($remaining);
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Récupère et valide le joueur cible d'une carte target=player.
     */
    private function resolveTargetPlayer(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId): GamePlayer
    {
        if (!$targetPlayerId) {
            throw ValidationException::withMessages(['card' => 'Joueur cible requis.']);
        }

        $player = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->where('id', $targetPlayerId)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $gameBonusCard->game_team_id))
            ->first();

        if (!$player) {
            throw ValidationException::withMessages(['card' => 'Ce joueur ne fait pas partie de votre effectif.']);
        }

        return $player;
    }

    /**
     * Les cartes pre_match ne s'appliquent pas immédiatement :
     * on les marque dans state.active_pre_match_cards pour que MatchSimulator les lise.
     */
    private function markPreMatchCard(GameBonusCard $gameBonusCard, GameSave $gameSave): array
    {
        $state = $gameSave->state ?? [];
        $state['active_pre_match_cards'][] = [
            'game_bonus_card_id' => $gameBonusCard->id,
            'game_team_id'       => $gameBonusCard->game_team_id,
            'effect_type'        => $gameBonusCard->bonusCard->effect_type,
            'effect_value'       => $gameBonusCard->bonusCard->effect_value,
        ];
        $gameSave->state = $state;
        $gameSave->save();

        return ['message' => 'Carte activée pour le prochain match.'];
    }

    // ──────────────────────────────────────────────────────────
    //   APPLIQUÉ DANS LE MOTEUR DE MATCH (appelé par MatchSimulator)
    // ──────────────────────────────────────────────────────────

    /**
     * Retourne les boosts actifs pour une équipe donnée (pour le moteur JS via engineConfig).
     * Appelé dans GameMatchController@show avant de passer engineConfig.
     */
    public function getActivePreMatchBoosts(GameSave $gameSave, int $teamId): array
    {
        $cards = $gameSave->state['active_pre_match_cards'] ?? [];
        return array_filter($cards, fn ($c) => (int) $c['game_team_id'] === $teamId);
    }

    /**
     * Agrège les boosts pre_match actifs d'une équipe en deltas de stats, prêts
     * à être ajoutés aux stats des joueurs pour le match (cumul si plusieurs
     * cartes actives). Ex. : ['attack' => 15, 'stamina' => 20].
     * Lu par GameMatchController::mapPlayers (match joué) et MatchSimulator (IA).
     *
     * @return array<string, int>
     */
    public function getPreMatchStatBoosts(GameSave $gameSave, int $teamId): array
    {
        $delta = [];
        foreach ($this->getActivePreMatchBoosts($gameSave, $teamId) as $card) {
            $ev   = $card['effect_value'] ?? [];
            $stat = $ev['stat'] ?? null;
            if ($stat && isset($ev['amount'])) {
                $delta[$stat] = ($delta[$stat] ?? 0) + (int) $ev['amount'];
            }
            if (isset($ev['stamina_bonus'])) {
                $delta['stamina'] = ($delta['stamina'] ?? 0) + (int) $ev['stamina_bonus'];
            }
        }
        return $delta;
    }

    /**
     * Modificateurs de stats pour le match d'une équipe : boosts propres
     * (positifs) + debuffs reçus (négatifs). Sépare l'effet d'équipe (appliqué
     * à tous) de l'effet par joueur (cadre muselé).
     * Lu par mapPlayers et MatchSimulator.
     *
     * @return array{team: array<string,int>, players: array<int,array<string,int>>}
     */
    public function getMatchStatModifiers(GameSave $gameSave, int $teamId): array
    {
        $team    = $this->getPreMatchStatBoosts($gameSave, $teamId); // boosts propres (positifs)
        $players = [];

        foreach ($gameSave->state['pending_stat_debuffs'] ?? [] as $d) {
            if ((int) ($d['target_team_id'] ?? 0) !== $teamId) continue;

            $deltas = $d['deltas'] ?? [];
            $pid    = $d['target_player_id'] ?? null;

            if ($pid) {
                foreach ($deltas as $stat => $v) {
                    $players[(int) $pid][$stat] = ($players[(int) $pid][$stat] ?? 0) + (int) $v;
                }
            } else {
                foreach ($deltas as $stat => $v) {
                    $team[$stat] = ($team[$stat] ?? 0) + (int) $v;
                }
            }
        }

        return ['team' => $team, 'players' => $players];
    }

    /**
     * Consomme (retire) les cartes pre_match d'une équipe après le match.
     */
    public function consumePreMatchCards(GameSave $gameSave, int $teamId): void
    {
        $state = $gameSave->state ?? [];
        $remaining = array_filter(
            $state['active_pre_match_cards'] ?? [],
            fn ($c) => (int) $c['game_team_id'] !== $teamId
        );
        $state['active_pre_match_cards'] = array_values($remaining);
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Consomme les cartes pre_match des équipes ayant joué cette semaine
     * (humaines ET IA). Appelé une fois en fin de semaine sur l'instance du
     * contrôleur — corrige aussi le cas des cartes IA jamais consommées.
     */
    public function consumePreMatchCardsForPlayedWeek(GameSave $gameSave, int $week): void
    {
        $state = $gameSave->state ?? [];
        $cards   = $state['active_pre_match_cards'] ?? [];
        $debuffs = $state['pending_stat_debuffs'] ?? [];
        if (empty($cards) && empty($debuffs)) return;

        $playedTeamIds = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'played')
            ->get(['home_team_id', 'away_team_id'])
            ->flatMap(fn ($m) => [(int) $m->home_team_id, (int) $m->away_team_id])
            ->unique()
            ->all();

        if (empty($playedTeamIds)) return;

        // Boosts propres : consommés quand l'équipe propriétaire a joué.
        $state['active_pre_match_cards'] = array_values(array_filter(
            $cards,
            fn ($c) => !in_array((int) ($c['game_team_id'] ?? 0), $playedTeamIds, true)
        ));

        // Debuffs reçus : consommés quand l'équipe CIBLE a joué.
        $state['pending_stat_debuffs'] = array_values(array_filter(
            $debuffs,
            fn ($d) => !in_array((int) ($d['target_team_id'] ?? 0), $playedTeamIds, true)
        ));

        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Résout les défis sponsor (revenue_challenge) après les matchs de la semaine.
     * Pour chaque défi en attente, on cherche le match joué de l'équipe cette
     * semaine, on évalue l'objectif et on crédite la récompense si réussi.
     * Un défi dont l'équipe n'a pas joué reste en attente.
     * Appelé dans GameMatchController (finishMatch / simulateWeek).
     *
     * @return array<int, array> les résultats de la semaine (pour affichage)
     */
    public function resolveSponsorChallenges(GameSave $gameSave, int $week): array
    {
        $state   = $gameSave->state ?? [];
        $pending = $state['pending_sponsor_challenges'] ?? [];

        // Toujours réinitialiser le récap de la semaine, même sans défi.
        if (empty($pending)) {
            if (!empty($state['sponsor_results'])) {
                $state['sponsor_results'] = [];
                $gameSave->state = $state;
                $gameSave->save();
            }
            return [];
        }

        $results   = [];
        $remaining = [];

        foreach ($pending as $challenge) {
            $teamId = (int) ($challenge['game_team_id'] ?? 0);

            $match = GameMatch::where('game_save_id', $gameSave->id)
                ->where('week', $week)
                ->where('status', 'played')
                ->where(function ($q) use ($teamId) {
                    $q->where('home_team_id', $teamId)
                      ->orWhere('away_team_id', $teamId);
                })
                ->first();

            // Pas de match joué cette semaine pour cette équipe → le défi reste en attente.
            if (!$match) {
                $remaining[] = $challenge;
                continue;
            }

            $isHome  = (int) $match->home_team_id === $teamId;
            $scored  = (int) ($isHome ? $match->home_score : $match->away_score);
            $against = (int) ($isHome ? $match->away_score : $match->home_score);

            $success = self::evaluateChallenge((string) ($challenge['challenge'] ?? ''), $scored, $against);
            $reward  = (int) ($challenge['reward'] ?? 0);

            if ($success) {
                $team = GameTeam::find($teamId);
                if ($team) {
                    $team->budget = ($team->budget ?? 0) + $reward;
                    $team->save();
                }
            }

            $results[] = [
                'game_team_id' => $teamId,
                'card_name'    => $challenge['card_name'] ?? 'Défi sponsor',
                'icon'         => $challenge['icon'] ?? '🤝',
                'challenge'    => $challenge['challenge'] ?? '',
                'reward'       => $reward,
                'success'      => $success,
                'scored'       => $scored,
                'against'      => $against,
                'week'         => $week,
            ];
        }

        $state['pending_sponsor_challenges'] = array_values($remaining);
        $state['sponsor_results']            = $results; // récap de la dernière clôture
        $gameSave->state = $state;
        $gameSave->save();

        return $results;
    }

    /**
     * Évalue un objectif de défi sponsor à partir du score du match
     * (du point de vue de l'équipe qui a souscrit le défi).
     */
    public static function evaluateChallenge(string $challenge, int $scored, int $against): bool
    {
        return match ($challenge) {
            'win'         => $scored > $against,
            'score_3'     => $scored >= 3,
            'clean_sheet' => $against === 0,
            'win_by_2'    => ($scored - $against) >= 2,
            'win_score_3' => $scored > $against && $scored >= 3,
            default       => false,
        };
    }
}
