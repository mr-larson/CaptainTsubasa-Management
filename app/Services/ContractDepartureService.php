<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSave;

class ContractDepartureService
{
    public const MORALE_THRESHOLD = 30;
    public const AFFINITY_THRESHOLD = MoraleService::AFFINITY_REFUSAL_THRESHOLD;

    private const MIN_CHANCE = 5;
    private const MAX_CHANCE = 45;

    public static function chance(int $morale, int $affinity): int
    {
        $moraleRisk = $morale > self::MORALE_THRESHOLD ? 0
            : self::scaledChance(self::MORALE_THRESHOLD - $morale, self::MORALE_THRESHOLD);
        $affinityRisk = $affinity > self::AFFINITY_THRESHOLD ? 0
            : self::scaledChance(self::AFFINITY_THRESHOLD - $affinity, self::AFFINITY_THRESHOLD - PromiseService::AFFINITY_MIN);

        return max($moraleRisk, $affinityRisk);
    }

    private static function scaledChance(int $distance, int $range): int
    {
        return self::MIN_CHANCE + (int) round((self::MAX_CHANCE - self::MIN_CHANCE) * min($distance, $range) / $range);
    }

    /** À appeler sous le verrou de sauvegarde, après le bilan du moral et des promesses. */
    public function resolveForWeek(GameSave $gameSave, int $week): void
    {
        if ($week % 2 !== 0 || $gameSave->competition_type === 'world_cup') return;

        $state = $gameSave->state ?? [];
        $season = (int) ($gameSave->season ?? 1);
        $lastCheck = $state['contract_departures'] ?? [];
        if (($lastCheck['season'] ?? null) === $season && ($lastCheck['week'] ?? null) === $week) return;

        $departures = [];
        $contracts = GameContract::where('game_save_id', $gameSave->id)
            ->activeAt($week)
            ->where(fn ($q) => $q->whereNull('end_week')->orWhere('end_week', '>', $week))
            ->whereHas('gamePlayer', fn ($q) => $q->where('morale', '<=', self::MORALE_THRESHOLD)
                ->orWhere('coach_affinity', '<=', self::AFFINITY_THRESHOLD))
            ->with('gamePlayer')
            ->orderBy('id')
            ->get()
            ->unique('game_player_id');

        foreach ($contracts as $contract) {
            $player = $contract->gamePlayer;
            $morale = (int) ($player->morale ?? MoraleService::NEUTRAL_MORALE);
            $affinity = (int) ($player->coach_affinity ?? 0);
            if ($this->roll() > self::chance($morale, $affinity)) continue;

            // La semaine jouée reste couverte. L'ancien contrat permet de retrouver le club d'origine.
            $contract->update(['end_week' => $week]);
            $player->number = null;
            $player->save();

            $teamId = (int) $contract->game_team_id;
            foreach ($state['lineup'][$teamId]['slots'] ?? [] as $slot => $playerId) {
                if ((int) $playerId === (int) $player->id) {
                    $state['lineup'][$teamId]['slots'][$slot] = null;
                }
            }

            GamePromise::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $player->id)
                ->where('game_team_id', $teamId)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            $departures[] = [
                'player_name' => $player->full_name,
                'team_id' => $teamId,
                'reason' => self::chance($morale, 0) >= self::chance(100, $affinity) ? 'moral bas' : 'relation avec le coach',
            ];
        }

        $state['contract_departures'] = ['season' => $season, 'week' => $week, 'entries' => $departures];
        $gameSave->state = $state;
        $gameSave->save();
    }

    protected function roll(): int
    {
        return random_int(1, 100);
    }
}
