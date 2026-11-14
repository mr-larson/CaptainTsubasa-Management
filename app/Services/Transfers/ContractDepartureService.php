<?php

namespace App\Services\Transfers;

use App\Enums\PromiseStatus;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSave;
use App\Services\Players\MoraleService;
use App\Services\Players\PromiseService;

/**
 * Départs de joueurs mécontents : toutes les deux semaines (hors Coupe du Monde), un joueur au moral très bas ou en mauvaise relation avec le coach peut résilier son contrat.
 * La probabilité va de 5 % à 45 % selon l'ampleur du mal-être. Le joueur quitte l'effectif, perd son numéro, sort de la composition et ses promesses en attente expirent.
 */
class ContractDepartureService
{
    public const MORALE_THRESHOLD = 30;
    public const AFFINITY_THRESHOLD = MoraleService::AFFINITY_REFUSAL_THRESHOLD;

    private const MIN_CHANCE = 5;
    private const MAX_CHANCE = 45;

    /** Probabilité (en %) qu'un joueur parte : le pire des deux risques (moral et affinité). Nulle au-dessus des seuils, puis de 5 % à 45 % selon la distance au seuil. */
    public static function chance(int $morale, int $affinity): int
    {
        $moraleRisk = $morale > self::MORALE_THRESHOLD ? 0
            : self::scaledChance(self::MORALE_THRESHOLD - $morale, self::MORALE_THRESHOLD);
        $affinityRisk = $affinity > self::AFFINITY_THRESHOLD ? 0
            : self::scaledChance(self::AFFINITY_THRESHOLD - $affinity, self::AFFINITY_THRESHOLD - PromiseService::AFFINITY_MIN);

        return max($moraleRisk, $affinityRisk);
    }

    /** Convertit la distance au seuil en probabilité linéaire entre le minimum (5 %) et le maximum (45 %), plafonnée à $range. */
    private static function scaledChance(int $distance, int $range): int
    {
        return self::MIN_CHANCE + (int) round((self::MAX_CHANCE - self::MIN_CHANCE) * min($distance, $range) / $range);
    }

    /** À appeler sous le verrou de sauvegarde, après le bilan du moral et des promesses. */
    public function resolveForWeek(GameSave $gameSave, int $week): void
    {
        if ($week % 2 !== 0 || $gameSave->competition_type === 'world_cup') return;

        $season = (int) ($gameSave->season ?? 1);
        if ($gameSave->gameState()->contractDeparturesDoneFor($season, $week)) return;

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
            $gameSave->gameState()->removeFromLineup($teamId, (int) $player->id);

            GamePromise::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $player->id)
                ->where('game_team_id', $teamId)
                ->where('status', PromiseStatus::Pending)
                ->update(['status' => PromiseStatus::Expired]);

            $departures[] = [
                'player_name' => $player->full_name,
                'team_id' => $teamId,
                'reason' => self::chance($morale, 0) >= self::chance(100, $affinity) ? 'moral bas' : 'relation avec le coach',
            ];
        }

        $gameSave->gameState()->putContractDepartures($season, $week, $departures);
        $gameSave->save();
    }

    /** Tirage d'un entier de 1 à 100 (isolé pour pouvoir le remplacer en test). */
    protected function roll(): int
    {
        return random_int(1, 100);
    }
}
