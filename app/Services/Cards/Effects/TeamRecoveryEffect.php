<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use Illuminate\Support\Facades\DB;

/**
 * Carte « État de grâce » : remet l'effectif d'aplomb d'un coup — endurance et moral
 * (plafonnés à 100) pour tous les joueurs de l'équipe, et, si la carte le prévoit, un
 * bouclier qui annulera le prochain malus subi.
 */
class TeamRecoveryEffect implements CardEffect
{
    /** Ajoute l'endurance et le moral de la carte à tout l'effectif (une seule mise à jour), puis pose le bouclier éventuel. */
    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $value   = $gameBonusCard->bonusCard->effect_value ?? [];
        $stamina = max(0, (int) ($value['stamina'] ?? 0));
        $morale  = max(0, (int) ($value['morale'] ?? 0));
        $teamId  = (int) $gameBonusCard->game_team_id;

        $updated = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $teamId))
            ->update([
                'stamina' => DB::raw("CASE WHEN COALESCE(stamina, 0) + {$stamina} > 100 THEN 100 ELSE COALESCE(stamina, 0) + {$stamina} END"),
                'morale'  => DB::raw("CASE WHEN COALESCE(morale, 0) + {$morale} > 100 THEN 100 ELSE COALESCE(morale, 0) + {$morale} END"),
            ]);

        $shield = ! empty($value['shield']);
        if ($shield) {
            $gameSave->gameState()->addMalusShield($teamId);
            $gameSave->save();
        }

        return [
            'message' => "⭐ État de grâce : endurance et moral au plus haut pour {$updated} joueur(s)"
                . ($shield ? ', et le prochain malus subi sera annulé.' : '.'),
            'updated' => $updated,
        ];
    }
}
