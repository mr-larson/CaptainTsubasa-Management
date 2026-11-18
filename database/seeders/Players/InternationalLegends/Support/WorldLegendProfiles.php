<?php

namespace Database\Seeders\Players\InternationalLegends\Support;

final class WorldLegendProfiles
{
    // Vitesse, endurance, attaque, défense, tir, passe, dribble, contre,
    // interception, tacle, arrêt main, arrêt poing. Les profils privilégient
    // une spécialité et gardent des faiblesses, sous les stars internationales.
    private const PROFILES = [
        'keeper' => ['Goalkeeper', [57, 70, 14, 29, 11, 18, 13, 22, 21, 17, 25, 24], 'Gardien de soutien, réactif et fiable sur sa ligne.'],
        'stopper' => ['Defender', [57, 72, 18, 29, 15, 19, 16, 26, 24, 26, 10, 10], 'Défenseur de soutien, puissant au marquage et dans les duels.'],
        'libero' => ['Defender', [62, 72, 23, 29, 20, 26, 21, 24, 26, 24, 10, 10], 'Défenseur organisateur, spécialisé dans la lecture du jeu et la relance.'],
        'fullback' => ['Defender', [68, 75, 21, 27, 16, 23, 21, 22, 24, 24, 10, 10], 'Latéral de soutien, appliqué défensivement et disponible dans son couloir.'],
        'wingback' => ['Defender', [73, 77, 24, 25, 20, 24, 23, 20, 22, 22, 10, 10], 'Latéral offensif et endurant, spécialisé dans les montées et les centres.'],
        'holding' => ['Midfielder', [61, 76, 21, 28, 17, 24, 19, 23, 26, 26, 10, 10], 'Milieu récupérateur, spécialiste du placement et de la protection de la défense.'],
        'box_to_box' => ['Midfielder', [66, 78, 26, 25, 22, 25, 22, 20, 23, 23, 10, 10], 'Milieu de soutien endurant, capable de relier défense et attaque.'],
        'playmaker' => ['Midfielder', [64, 69, 28, 20, 21, 27, 25, 16, 20, 18, 10, 10], 'Milieu créatif, spécialisé dans la dernière passe et la conservation du ballon.'],
        'winger' => ['Midfielder', [74, 68, 27, 18, 21, 24, 26, 14, 18, 16, 10, 10], 'Milieu de couloir, rapide et habile dans les débordements.'],
        'winger_forward' => ['Forward', [75, 70, 29, 17, 24, 22, 26, 14, 17, 16, 10, 10], 'Attaquant de couloir, spécialiste des appels et des accélérations.'],
        'striker' => ['Forward', [68, 72, 30, 17, 25, 20, 23, 14, 16, 16, 10, 10], 'Attaquant de soutien, mobile et efficace dans la surface.'],
        'power_forward' => ['Forward', [65, 74, 31, 18, 26, 19, 21, 16, 17, 17, 10, 10], 'Avant-centre puissant, privilégiant la frappe aux combinaisons techniques.'],
        'target' => ['Forward', [62, 74, 29, 19, 25, 21, 19, 18, 18, 18, 10, 10], 'Avant-centre de soutien, spécialiste du jeu aérien et des remises.'],
        'second_striker' => ['Forward', [66, 70, 29, 19, 24, 25, 24, 16, 19, 17, 10, 10], 'Attaquant de liaison, capable de préparer une occasion autant que de la conclure.'],
        'worker_forward' => ['Forward', [67, 79, 27, 23, 23, 22, 21, 18, 22, 22, 10, 10], 'Attaquant collectif et endurant, utile au pressing et au repli défensif.'],
    ];

    public static function players(array $roster): array
    {
        $players = [];
        $keys = ['speed', 'stamina', 'attack', 'defense', 'shot', 'pass', 'dribble', 'block', 'intercept', 'tackle', 'hand_save', 'punch_save'];
        foreach ($roster as $entry) {
            [$position, $values, $description] = self::PROFILES[$entry['profile']];
            $stats = array_combine($keys, $values);
            if (in_array($entry['profile'], ['target', 'stopper'], true)) $stats['heading'] = 26;
            if ($entry['profile'] === 'wingback') $stats['cross'] = 26;
            $stats = array_replace($stats, $entry['stats'] ?? []);
            $position = isset($entry['position']) ? self::position($entry['position']) : $position;
            $secondary = isset($entry['secondary']) ? [self::position($entry['secondary'])] : [];
            $secondary = array_values(array_diff($secondary, [$position]));
            $detail = 'Légendes internationales — profil adapté au jeu. '.$description;
            if (isset($entry['world_cup_rank'])) $detail .= ' Rang éditorial Coupe du monde : '.$entry['world_cup_rank'].'.';
            if (isset($entry['position'])) $detail .= ' Poste : '.$entry['position'].(isset($entry['secondary']) ? ', poste secondaire : '.$entry['secondary'] : '').'.';
            if (isset($entry['note'])) $detail .= ' '.$entry['note'];
            $players[] = [$entry['firstname'], $entry['lastname'], 13, $position, 0, $stats, $detail, $secondary];
        }
        return $players;
    }

    private static function position(string $code): string
    {
        return match ($code) {
            'GB' => 'Goalkeeper',
            'DC', 'LIB', 'DD', 'DG' => 'Defender',
            'MDC', 'MC', 'MOC', 'MD', 'MG' => 'Midfielder',
            'AD', 'AG', 'AT', 'BU' => 'Forward',
        };
    }
}
