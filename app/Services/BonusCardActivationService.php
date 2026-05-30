<?php

namespace App\Services;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePlayer;
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
        ?int $targetPlayerId = null
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
            'revenue_boost'         => $this->applyRevenueBoost($gameSave, $gameBonusCard),
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

    private function applyRevenueBoost(GameSave $gameSave, GameBonusCard $gameBonusCard): array
    {
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);
        $teamId = $gameBonusCard->game_team_id;

        $team = GameTeam::find($teamId);
        if (!$team) {
            throw ValidationException::withMessages(['card' => 'Équipe introuvable.']);
        }

        $team->budget = ($team->budget ?? 0) + $amount;
        $team->save();

        return ['message' => "+{$amount} € ajoutés au budget.", 'new_budget' => $team->budget];
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
     * Consomme (retire) les cartes pre_match après le match.
     * Appelé dans GameMatchController@finishMatch.
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
}
