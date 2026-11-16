<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\Cards\MalusCardEffects;

/**
 * Carte « Rage de l'outsider » : boost pour le prochain match, d'autant plus fort que
 * l'adversaire a d'avance au classement (1 point de stat par point de retard, entre `min` et `max`).
 * Le montant est calculé à l'activation puis armé comme une carte d'avant-match classique
 * (state.active_pre_match_cards), lue par le simulateur et l'écran de match.
 */
class UnderdogStatBoostEffect implements CardEffect
{
    /** Reçoit le service des malus, qui sait trouver l'adversaire du prochain match. */
    public function __construct(private MalusCardEffects $malus)
    {
    }

    /** Calcule le boost selon l'écart de points avec le prochain adversaire et l'arme pour le prochain match. Refusé s'il n'y a aucun match à venir. */
    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $teamId     = (int) $gameBonusCard->game_team_id;
        $opponentId = $this->malus->resolveNextOpponentTeamId($gameSave, $teamId);

        $teams    = GameTeam::whereIn('id', [$teamId, $opponentId])->get()->keyBy('id');
        $points   = fn (?GameTeam $t) => (int) (($t->wins ?? 0) * 3 + ($t->draws ?? 0));
        $opponent = $teams->get($opponentId);
        $gap      = $points($opponent) - $points($teams->get($teamId));

        $value  = $gameBonusCard->bonusCard->effect_value ?? [];
        $min    = (int) ($value['min'] ?? 3);
        $max    = (int) ($value['max'] ?? 15);
        $amount = max($min, min($max, $gap));

        $stats = array_fill_keys((array) ($value['boosted_stats'] ?? ['attack', 'defense']), $amount);

        $gameSave->gameState()->addActivePreMatchCard([
            'game_bonus_card_id' => $gameBonusCard->id,
            'game_team_id'       => $teamId,
            'effect_type'        => 'stat_boost',
            'effect_value'       => ['stats' => $stats],
        ]);
        $gameSave->save();

        $name = $opponent?->name ?? 'votre adversaire';

        return ['message' => "🔥 Rage de l'outsider : +{$amount} en attaque et en défense contre {$name} au prochain match."];
    }
}
