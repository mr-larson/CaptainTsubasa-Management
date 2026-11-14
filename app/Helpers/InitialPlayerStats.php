<?php

namespace App\Helpers;

use App\Models\GameSaves\GameSave;
use App\Support\GameConfig;
use App\Services\Players\MoraleService;

/**
 * Calcule les valeurs de départ d'un joueur (vitesse, endurance, moral, chance) selon les options de la configuration de la partie.
 */
class InitialPlayerStats
{
    /**
     * Renvoie vitesse, endurance, moral et chance initiaux.
     * Les tirages aléatoires sont dérivés de la graine et de l'identifiant du joueur : même graine, mêmes valeurs.
     */
    public static function forPlayer(GameSave $save, int $seed, int|string $playerId, int $baseSpeed): array
    {
        $draw = static fn (string $stat, int $min, int $max): int =>
            $min + crc32("{$seed}:{$playerId}:{$stat}") % ($max - $min + 1);

        $speedVariation = GameConfig::INITIAL_SPEED_VARIATION;

        return [
            'speed' => $save->config()->initialSpeed($baseSpeed, $draw('speed', -$speedVariation, $speedVariation)),
            'stamina' => $save->getConfig('initial_stamina_random') ? $draw('stamina', 60, 100) : 100,
            'morale' => $save->getConfig('initial_morale_random')
                ? MoraleService::initialMoraleFromSeed($seed, is_int($playerId) ? $playerId : crc32($playerId))
                : MoraleService::NEUTRAL_MORALE,
            'luck' => $save->getConfig('luck_random')
                ? (int) round(($draw('luck:1', PlayerLuck::MIN, PlayerLuck::MAX)
                    + $draw('luck:2', PlayerLuck::MIN, PlayerLuck::MAX)) / 2)
                : PlayerLuck::NEUTRAL,
        ];
    }
}
