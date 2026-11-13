<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;

/**
 * Instantané des effectifs au moment du match, stocké dans
 * match_stats['lineups'] : l'historique (stats joueurs, replay) reste juste
 * même après un transfert, une résiliation ou un changement de formation.
 */
class MatchLineupSnapshot
{
    /**
     * @return array{home: array, away: array}
     */
    public function build(GameMatch $match): array
    {
        $week  = (int) $match->week;
        $gameState = $match->gameSave?->gameState();

        return [
            'home' => $this->side($match->home_team_id, $match->homeTeam?->formation, (int) $match->game_save_id, $week, $gameState),
            'away' => $this->side($match->away_team_id, $match->awayTeam?->formation, (int) $match->game_save_id, $week, $gameState),
        ];
    }

    /** Effectif d'une équipe à la semaine donnée : formation + contrats actifs avec poste (slot), titulaire et capitaine. */
    private function side(int $teamId, ?string $formation, int $saveId, int $week, ?\App\Support\GameState $gameState = null): array
    {
        // Poste occupé sur le terrain (slot 1..11 de la formation) : composition enregistrée.
        $slotByPlayer = [];
        foreach ($gameState?->lineupSlots($teamId) ?? [] as $slot => $playerId) {
            if ($playerId) {
                $slotByPlayer[(int) $playerId] = (int) $slot;
            }
        }

        $players = GameContract::query()
            ->where('game_save_id', $saveId)
            ->where('game_team_id', $teamId)
            ->activeAt($week)
            ->with('gamePlayer')
            ->get()
            ->filter(fn ($c) => $c->gamePlayer)
            ->map(fn ($c) => [
                'id'         => $c->gamePlayer->id,
                'firstname'  => $c->gamePlayer->firstname,
                'lastname'   => $c->gamePlayer->lastname,
                'number'     => $c->gamePlayer->number,
                'position'   => $c->gamePlayer->position,
                'photo_path' => $c->gamePlayer->photo_path,
                'is_starter' => (bool) $c->is_starter,
                'is_captain' => (bool) $c->is_captain,
                'slot'       => $slotByPlayer[(int) $c->gamePlayer->id] ?? null,
            ])
            ->values()
            ->all();

        return ['formation' => $formation, 'players' => $players];
    }
}
