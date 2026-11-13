<?php

namespace Database\Seeders\Support;

use App\Helpers\HeadingRating;

/**
 * Stats détaillées d'un joueur de référence (tir, passe, défense, gardien…)
 * dérivées de ses stats de base et de son poste.
 */
final class PlayerSkills
{
    /**
     * Génère les stats détaillées à partir des stats de base + position.
     */
    public static function build(array $baseStats, string $position): array
    {
        $speed = $baseStats['speed'];
        $stamina = $baseStats['stamina'];
        $defense = $baseStats['defense'];
        $attack = $baseStats['attack'];

        // valeurs "génériques"
        $shot = (int)round($attack);
        $pass = (int)round($attack * 0.7 + $speed * 0.3);
        $dribble = (int)round($attack * 0.7 + $speed * 0.3);
        $block = (int)round($defense * 0.8 + $stamina * 0.2);
        $intercept = (int)round($defense * 0.7 + $speed * 0.3);
        $tackle = (int)round($defense * 0.9 + $stamina * 0.1);

        // par défaut, les joueurs de champ ne sont pas bons gardiens
        $handSave = max(5, (int)round($defense * 0.2));
        $punchSave = max(5, (int)round($defense * 0.15));

        switch ($position) {
            case 'Forward':
                $shot = (int)round(min(100, $attack * 1.05));
                $dribble = (int)round(min(100, ($attack * 0.8 + $speed * 0.4) / 1.1));
                $pass = (int)round(($attack * 0.75 + $speed * 0.35) / 1.1);
                break;

            case 'Midfielder':
                $pass = (int)round(min(100, ($attack * 0.9 + $speed * 0.4) / 1.1));
                $dribble = (int)round(min(100, ($attack * 0.85 + $speed * 0.4) / 1.1));
                break;

            case 'Defender':
                $block = (int)round(min(100, $block * 1.05));
                $tackle = (int)round(min(100, $tackle * 1.05));
                $intercept = (int)round(($defense * 0.8 + $speed * 0.3) / 1.1);
                break;

            case 'Goalkeeper':
                // les GK sont spéciaux
                $shot = (int)round($attack * 0.8);
                $pass = (int)round($attack * 0.7 + $speed * 0.3);
                $dribble = (int)round($speed * 0.7 + $defense * 0.3);

                $block = (int)round(($defense * 0.9 + $stamina * 0.3) / 1.2);
                $intercept = (int)round($defense * 0.8 + $speed * 0.2);
                $tackle = (int)round($defense * 0.7 + $stamina * 0.3);

                $handSave = (int)round(min(100, ($defense * 1.3 + $stamina * 0.5) / 1.5));
                $punchSave = (int)round(min(100, ($defense * 1.1 + $stamina * 0.7) / 1.5));
                break;
        }

        $stats = $baseStats + [
                'shot' => $shot,
                'pass' => $pass,
                'dribble' => $dribble,
                'block' => $block,
                'intercept' => $intercept,
                'tackle' => $tackle,
                'hand_save' => $handSave,
                'punch_save' => $punchSave,
            ];

        return $stats + ['heading' => HeadingRating::estimate($position, $stats)];
    }
}
