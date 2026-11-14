<?php

namespace App\Services\Matches;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

/**
 * Instantané des effectifs au moment du match, stocké dans
 * match_stats['lineups'] : l'historique (stats joueurs, replay) reste juste
 * même après un transfert, une résiliation ou un changement de formation.
 */
class MatchLineupSnapshot
{
    /**
     * Instantané des deux effectifs du match.
     *
     * @param bool $contractsPreloaded vrai si les équipes du match ont déjà leurs contrats actifs
     *                                 (avec les joueurs) chargés pour la semaine du match, comme le fait
     *                                 MatchSimulator : on les réutilise au lieu de les relire en base.
     * @return array{home: array, away: array}
     */
    public function build(GameMatch $match, bool $contractsPreloaded = false): array
    {
        $week      = (int) $match->week;
        $gameState = $match->gameSave?->gameState();
        $saveId    = (int) $match->game_save_id;

        return [
            'home' => $this->side($match->home_team_id, $match->homeTeam, $saveId, $week, $gameState, $contractsPreloaded),
            'away' => $this->side($match->away_team_id, $match->awayTeam, $saveId, $week, $gameState, $contractsPreloaded),
        ];
    }

    /** Effectif d'une équipe à la semaine donnée : formation + contrats actifs avec poste (slot), titulaire et capitaine. */
    private function side(int $teamId, ?GameTeam $team, int $saveId, int $week, ?\App\Support\GameState $gameState, bool $contractsPreloaded): array
    {
        // Poste occupé sur le terrain (slot 1..11 de la formation) : composition enregistrée.
        $slotByPlayer = [];
        foreach ($gameState?->lineupSlots($teamId) ?? [] as $slot => $playerId) {
            if ($playerId) {
                $slotByPlayer[(int) $playerId] = (int) $slot;
            }
        }

        $players = $this->activeContracts($team, $teamId, $saveId, $week, $contractsPreloaded)
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

        return ['formation' => $team?->formation, 'players' => $players];
    }

    /**
     * Contrats actifs de l'équipe à la semaine donnée, avec leur joueur. Réutilise les
     * contrats déjà chargés sur l'équipe quand l'appelant l'indique ET qu'ils sont bien
     * présents avec leurs joueurs (sinon relecture en base, comportement d'origine).
     */
    private function activeContracts(?GameTeam $team, int $teamId, int $saveId, int $week, bool $preloaded): Collection
    {
        if ($preloaded && $team && $team->relationLoaded('contracts')
            && $team->contracts->every(fn ($c) => $c->relationLoaded('gamePlayer'))) {
            return $team->contracts;
        }

        return GameContract::query()
            ->where('game_save_id', $saveId)
            ->where('game_team_id', $teamId)
            ->activeAt($week)
            ->with('gamePlayer')
            ->get();
    }
}
