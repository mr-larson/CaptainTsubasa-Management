<?php

namespace App\Helpers;

class ReferenceRosterBalance
{
    // Seules les valeurs du catalogue d'origine sont retouchées.
    private const MINAWI = [
        'Hajime Asakura' => ['speed' => [55, 51], 'defense' => [30, 26], 'hand_save' => [25, 21], 'punch_save' => [22, 18], 'block' => [21, 18], 'intercept' => [22, 19]],
        'Daichi Azuma' => ['speed' => [60, 55], 'defense' => [28, 23], 'block' => [24, 20], 'intercept' => [23, 19], 'tackle' => [24, 20]],
        'Shinji Takahama' => ['speed' => [58, 53], 'defense' => [29, 24], 'block' => [24, 20], 'intercept' => [23, 19], 'tackle' => [25, 21]],
        'Ryu Kawanoe' => ['speed' => [57, 52], 'defense' => [29, 24], 'block' => [25, 21], 'intercept' => [24, 20], 'tackle' => [25, 21]],
        'Takashi Iyo' => ['speed' => [56, 51], 'defense' => [28, 23], 'block' => [24, 20], 'intercept' => [23, 19], 'tackle' => [25, 21]],
        'Koji Tosa' => ['speed' => [60, 55], 'defense' => [24, 20], 'attack' => [26, 21], 'pass' => [24, 19], 'dribble' => [23, 19], 'shot' => [23, 20]],
        'Hiroto Shintani' => ['speed' => [61, 56], 'defense' => [24, 20], 'attack' => [27, 22], 'pass' => [25, 20], 'dribble' => [24, 20], 'shot' => [24, 21]],
        'Masaru Hirayama' => ['speed' => [62, 57], 'defense' => [24, 20], 'attack' => [26, 21], 'pass' => [24, 19], 'dribble' => [23, 19], 'shot' => [23, 20]],
        'Kazuki Seto' => ['speed' => [63, 58], 'attack' => [30, 24], 'shot' => [26, 20], 'dribble' => [24, 20], 'pass' => [22, 20]],
        'Kazuto Takei' => ['speed' => [64, 59], 'attack' => [31, 25], 'shot' => [27, 21], 'dribble' => [24, 20], 'pass' => [22, 20]],
    ];

    public const CENTRE_SPECIALS = [
        'captain_tsubasa' => [
            'Shingo Takasugi' => ['takasugi_power_cross', 'Centre puissant', 'Puissant', 'Un ballon aérien puissant envoyé dans la surface, inspiré de ses longues remises en touche.'],
            'Hajime Taki' => ['taki_wing_cross', 'Centre de débordement', 'Débordement', 'Taki prend de vitesse son vis-à-vis sur le couloir avant de centrer.'],
            'Gert Keizer' => ['keizer_precision_cross', 'Centre millimétré', 'Millimétré', 'Keizer dépose son centre dans la course du receveur.'],
        ],
        'ao_ashi' => [
            'Jun Asari' => ['asari_overlap_cross', 'Centre de couloir', 'Couloir', 'Asari exploite son dédoublement pour centrer dans la surface.'],
            'Ashito Aoi' => ['aoi_space_cross', 'Centre dans l’espace', 'Espace', 'Aoi repère l’espace libre et y place son centre pour son partenaire.'],
        ],
    ];

    public static function names(string $origin): array
    {
        return array_unique([
            ...array_keys(self::CENTRE_SPECIALS[$origin] ?? []),
            ...($origin === 'captain_tsubasa' ? [...array_keys(self::MINAWI), 'Mamoru Izawa'] : []),
        ]);
    }

    public static function apply(string $origin, string $name, ?string $position, array $stats, array $moves): array
    {
        if ($origin === 'captain_tsubasa' && isset(self::MINAWI[$name])) {
            $previousHeading = HeadingRating::estimate($position, $stats);
            foreach (self::MINAWI[$name] as $stat => [$before, $after]) {
                if (($stats[$stat] ?? null) === $before) {
                    $stats[$stat] = $after;
                }
            }
            if (($stats['heading'] ?? $previousHeading) === $previousHeading) {
                $stats['heading'] = HeadingRating::estimate($position, $stats);
            }
        }

        if ($origin === 'captain_tsubasa' && $name === 'Mamoru Izawa'
            && ($stats['heading'] ?? null) === HeadingRating::estimate($position, $stats)) {
            $stats['heading'] = 29;
        }

        $special = self::CENTRE_SPECIALS[$origin][$name] ?? null;
        if ($special && !in_array('cross', array_column($moves, 'base_action'), true)) {
            [$key, $label, $shortLabel, $description] = $special;
            $moves[] = [
                'key' => $key,
                'label' => $label,
                'short_label' => $shortLabel,
                'mode' => 'attack',
                'base_action' => 'cross',
                'cooldown' => 3,
                'description' => $description,
            ];
        }

        return [$stats, $moves];
    }
}
