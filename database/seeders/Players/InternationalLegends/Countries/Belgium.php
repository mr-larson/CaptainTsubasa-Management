<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

use Illuminate\Support\Str;

/**
 * Sélection de 41 joueurs issue du tableau 56_footballeurs_belges.xlsx,
 * feuille « Joueurs belges » : ordre, postes et rangs d’origine conservés.
 * Profils adaptés au jeu, pas des notes historiques de leur carrière.
 * Paliers : 1–3 élite, 4–11 performants, 12–24 au-dessus de la moyenne,
 * 25–40 moyens, 41–56 en dessous de la moyenne.
 */
final class Belgium
{
    // Le dernier champ est le rang initial : retirer un joueur ne change pas les paliers.
    private const ROSTER = [
        ['Kevin', 'De Bruyne', 'MC', 'MOC', 1],
        ['Eden', 'Hazard', 'AG', 'MOC', 2],
        ['Thibaut', 'Courtois', 'GB', null, 3],
        ['Vincent', 'Kompany', 'DC', 'MDC', 4],
        ['Enzo', 'Scifo', 'MOC', 'MC', 5],
        ['Jan', 'Ceulemans', 'BU', 'MOC', 6],
        ['Jean-Marie', 'Pfaff', 'GB', null, 7],
        ['Romelu', 'Lukaku', 'BU', null, 8],
        ['Michel', 'Preud\'homme', 'GB', null, 9],
        ['Éric', 'Gerets', 'DD', 'MD', 10],
        ['Luc', 'Nilis', 'BU', 'AT', 11],
        ['Jan', 'Vertonghen', 'DC', 'DG', 12],
        ['Marc', 'Degryse', 'MOC', 'AT', 14],
        ['Franky', 'Van der Elst', 'MDC', 'DC', 16],
        ['Marc', 'Wilmots', 'MOC', 'AT', 17],
        ['Georges', 'Grün', 'DC', 'DD', 18],
        ['Philippe', 'Albert', 'DC', null, 21],
        ['François', 'Van der Elst', 'AD', 'AT', 24],
        ['Émile', 'Mpenza', 'BU', null, 25],
        ['Daniel', 'Van Buyten', 'DC', null, 26],
        ['Wesley', 'Sonck', 'BU', 'AT', 27],
        ['Nico', 'Claesen', 'BU', 'AD', 28],
        ['Thomas', 'Vermaelen', 'DC', 'DG', 29],
        ['Youri', 'Tielemans', 'MC', 'MDC', 30],
        ['René', 'Vandereycken', 'MDC', 'MC', 31],
        ['Frank', 'Vercauteren', 'MG', 'AG', 32],
        ['Erwin', 'Vandenbergh', 'BU', null, 33],
        ['Stéphane', 'Demol', 'DC', null, 35],
        ['Lorenzo', 'Staelens', 'MDC', 'DC', 36],
        ['Danny', 'Boffin', 'MG', 'AG', 37],
        ['Luis', 'Oliveira', 'BU', 'AD', 38],
        ['Gilles', 'De Bilde', 'BU', 'AT', 39],
        ['Johan', 'Walem', 'MC', 'MOC', 40],
        ['Bart', 'Goor', 'MG', 'AG', 41],
        ['Mbo', 'Mpenza', 'AD', 'BU', 42],
        ['Timmy', 'Simons', 'MDC', 'DC', 43],
        ['Thomas', 'Buffel', 'MOC', 'AD', 44],
        ['Gert', 'Verheyen', 'AD', 'BU', 45],
        ['Olivier', 'Deschacht', 'DG', 'DC', 46],
        ['Jérémy', 'Doku', 'AG', 'AD', 52],
        ['Leandro', 'Trossard', 'AG', 'AT', 53],
    ];

    // Ordre des statistiques : vitesse, endurance, attaque, défense, tir,
    // passe, dribble, contre, interception, tacle, arrêt main, arrêt poing, tête.
    // Les postes détaillés du fichier sont conservés dans la description ; les
    // postes du moteur restent les quatre groupes usuels, sans doublon secondaire.
    private const PROFILES = [
        'Goalkeeper' => [57, 72, 14, 30, 11, 18, 13, 23, 21, 17, 26, 25, 13],
        'Defender' => [62, 72, 20, 30, 17, 22, 18, 26, 25, 26, 10, 10, 27],
        'Midfielder' => [65, 72, 28, 24, 22, 26, 24, 19, 22, 21, 10, 10, 19],
        'Forward' => [68, 71, 31, 18, 25, 22, 24, 15, 17, 16, 10, 10, 23],
    ];

    // Les stars ont un profil individuel : elles rivalisent avec les références
    // japonaises sans toutes devenir des joueurs complets comme Tsubasa.
    private const STARS = [
        'Kevin De Bruyne' => [75, 80, 37, 26, 27, 30, 27, 19, 24, 22, 10, 10, 18],
        'Eden Hazard' => [78, 76, 38, 18, 28, 27, 30, 14, 18, 16, 10, 10, 18],
        'Thibaut Courtois' => [62, 78, 15, 38, 12, 19, 14, 25, 24, 18, 30, 29, 15],
    ];

    // Spécialités au sein du palier, sans modifier l'ordre de la liste source.
    private const SPECIALTIES = [
        'Vincent Kompany' => ['defense' => 34, 'block' => 28, 'tackle' => 28, 'heading' => 29],
        'Enzo Scifo' => ['attack' => 34, 'pass' => 28, 'dribble' => 27],
        'Jan Ceulemans' => ['attack' => 35, 'shot' => 27, 'heading' => 28],
        'Jean-Marie Pfaff' => ['hand_save' => 28, 'punch_save' => 27],
        'Romelu Lukaku' => ['attack' => 34, 'shot' => 27, 'heading' => 27, 'dribble' => 22],
        "Michel Preud'homme" => ['hand_save' => 28, 'punch_save' => 26],
        'Éric Gerets' => ['speed' => 71, 'stamina' => 77, 'pass' => 24],
        'Luc Nilis' => ['attack' => 33, 'shot' => 27, 'dribble' => 25],
        'Marc Degryse' => ['pass' => 27, 'dribble' => 25],
        'Franky Van der Elst' => ['defense' => 29, 'intercept' => 27, 'tackle' => 26],
        'François Van der Elst' => ['speed' => 72],
        'Émile Mpenza' => ['speed' => 72],
        'Daniel Van Buyten' => ['heading' => 27, 'block' => 25],
        'Youri Tielemans' => ['pass' => 25, 'shot' => 23],
        'Frank Vercauteren' => ['pass' => 25],
        'Danny Boffin' => ['speed' => 71],
        'Timmy Simons' => ['defense' => 23, 'intercept' => 23, 'tackle' => 22],
        'Jérémy Doku' => ['speed' => 75, 'dribble' => 24],
    ];

    private static function position(string $code): string
    {
        return match ($code) {
            'GB' => 'Goalkeeper',
            'DC', 'DD', 'DG' => 'Defender',
            'MDC', 'MC', 'MOC', 'MD', 'MG' => 'Midfielder',
            'AD', 'AG', 'AT', 'BU' => 'Forward',
        };
    }

    public static function players(): array
    {
        $players = [];
        $keys = ['speed', 'stamina', 'attack', 'defense', 'shot', 'pass', 'dribble', 'block', 'intercept', 'tackle', 'hand_save', 'punch_save', 'heading'];
        foreach (self::ROSTER as [$first, $last, $primary, $secondary, $rank]) {
            $name = $first.' '.$last;
            $position = self::position($primary);
            $stats = array_combine($keys, self::PROFILES[$position]);
            [$tier, $skillAdjustment, $physicalAdjustment] = match (true) {
                $rank <= 3 => ['élite', 0, 0],
                $rank <= 11 => ['performant', 2, 3],
                $rank <= 24 => ['au-dessus de la moyenne', 0, 0],
                $rank <= 40 => ['moyen', -3, -5],
                default => ['en dessous de la moyenne', -6, -10],
            };
            foreach ($stats as $key => &$value) {
                // Les joueurs de champ ne gagnent pas des qualités de gardien
                // et les gardiens ne deviennent pas des attaquants par le palier.
                if (in_array($key, ['speed', 'stamina'], true)) $value += $physicalAdjustment;
                elseif ($position !== 'Goalkeeper' && !in_array($key, ['hand_save', 'punch_save'], true)) $value += $skillAdjustment;
                elseif ($position === 'Goalkeeper' && in_array($key, ['defense', 'block', 'intercept', 'hand_save', 'punch_save'], true)) $value += $skillAdjustment;
            }
            unset($value);
            // Les ailes et les latéraux gardent leur mobilité ; le milieu
            // défensif met l'accent sur la récupération plutôt que la frappe.
            if (in_array($primary, ['DD', 'DG', 'MD', 'MG', 'AD', 'AG'], true)) $stats['speed'] += 5;
            if (in_array($primary, ['DD', 'DG'], true)) $stats['heading'] -= 3;
            if ($primary === 'MDC') {
                foreach (['defense', 'intercept', 'tackle'] as $key) $stats[$key] += 3;
                foreach (['attack', 'shot', 'dribble'] as $key) $stats[$key] -= 2;
            }
            $stats = array_replace($stats, self::SPECIALTIES[$name] ?? []);
            if (isset(self::STARS[$name])) $stats = array_combine($keys, self::STARS[$name]);
            $secondaryPositions = $secondary ? [self::position($secondary)] : [];
            $secondaryPositions = array_values(array_diff($secondaryPositions, [$position]));
            $description = "Légendes internationales — Belgique. Profil adapté au jeu : {$tier}. Poste : {$primary}"
                .($secondary ? ", poste secondaire : {$secondary}." : '.');
            $players[] = [$first, $last, 13, $position, 0, $stats, $description, $secondaryPositions];
        }
        return $players;
    }

    public static function nationalities(): array
    {
        $map = [];
        foreach (self::ROSTER as [$first, $last]) $map[Str::slug($first.' '.$last)] = 'Belgique';
        foreach (self::HISTORICAL_ADDITIONS as $entry) $map[Str::slug($entry['firstname'].' '.$entry['lastname'])] = 'Belgique';
        return $map;
    }


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Stijn', 'lastname' => 'Stijnen', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Silvio', 'lastname' => 'Proto', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Logan', 'lastname' => 'Bailly', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Philippe', 'lastname' => 'Léonard', 'profile' => 'fullback', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Jelle', 'lastname' => 'Van Damme', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Éric', 'lastname' => 'Deflandre', 'profile' => 'fullback', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Nicolas', 'lastname' => 'Lombaerts', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Birger', 'lastname' => 'Maertens', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Philippe', 'lastname' => 'Clement', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Carl', 'lastname' => 'Hoefkens', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Steven', 'lastname' => 'Defour', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Walter', 'lastname' => 'Baseggio', 'profile' => 'playmaker', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Yves', 'lastname' => 'Vanderhaeghe', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Bernd', 'lastname' => 'Thijs', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Gaëtan', 'lastname' => 'Englebert', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Mémé', 'lastname' => 'Tchité', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'François', 'lastname' => 'Sterchele', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Luigi', 'lastname' => 'Pieroni', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Cédric', 'lastname' => 'Roussel', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Filip', 'lastname' => 'De Wilde', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Dany', 'lastname' => 'Verlinden', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Gilbert', 'lastname' => 'Bodart', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Geert', 'lastname' => 'De Vlieger', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Rudi', 'lastname' => 'Smidts', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Regi', 'lastname' => 'Van Acker', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Vital', 'lastname' => 'Borkelmans', 'profile' => 'fullback', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Glen', 'lastname' => 'De Boeck', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Bertrand', 'lastname' => 'Crasson', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Nico', 'lastname' => 'Van Kerckhoven', 'profile' => 'fullback', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Régis', 'lastname' => 'Genaux', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Sven', 'lastname' => 'Vermant', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Robby', 'lastname' => 'Van de Weyer', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Roberto', 'lastname' => 'Bisconti', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Branko', 'lastname' => 'Strupar', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Josip', 'lastname' => 'Weber', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Alexandre', 'lastname' => 'Czerniatynski', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Michaël', 'lastname' => 'Goossens', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
    ];
}
