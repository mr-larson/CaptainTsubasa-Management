<?php

namespace Database\Seeders\Players\InternationalLegends\Support;

/**
 * Individualise les légendes qui partageaient un profil type (WorldLegendProfiles).
 * Chaque joueur reçoit un rang de stature réelle (-2 à +2 ; 0 = niveau du profil) et
 * des points forts. Le rang décale les stats de son rôle ; les points forts ajoutent un bonus ciblé.
 * Les plafonds (CAPS) gardent chaque légende sous les héros de Captain Tsubasa
 * (Tsubasa : attaque 39, tir 30 ; Hyuga : attaque 42 ; Wakabayashi : gardien 39/30).
 * Les rangs sont une appréciation éditoriale à relire, pas une évaluation chiffrée de carrière.
 */
final class LegendTuning
{
    /** Bonus d'un point fort : stat visée => points ajoutés. */
    private const TAG_BONUS = [
        'speed' => 4, 'stamina' => 4, 'shot' => 3, 'pass' => 3, 'dribble' => 3,
        'tackle' => 3, 'intercept' => 3, 'block' => 3, 'attack' => 2, 'defense' => 2,
    ];

    /** Plafonds par stat et par rôle (FW, MF, DF, GK). */
    private const CAPS = [
        'FW' => ['attack' => 38, 'shot' => 30, 'pass' => 29, 'dribble' => 31, 'speed' => 82, 'stamina' => 82, 'heading' => 30],
        'MF' => ['attack' => 37, 'shot' => 28, 'pass' => 31, 'dribble' => 30, 'speed' => 80, 'stamina' => 82, 'defense' => 30, 'tackle' => 27, 'intercept' => 27],
        'DF' => ['defense' => 34, 'block' => 27, 'tackle' => 27, 'intercept' => 27, 'pass' => 28, 'shot' => 24, 'speed' => 78, 'stamina' => 80, 'heading' => 32],
        'GK' => ['defense' => 36, 'hand_save' => 29, 'punch_save' => 29, 'block' => 26, 'intercept' => 26, 'speed' => 65, 'stamina' => 78],
    ];

    /** Décalage par rang, selon le rôle : stat => points par pas de rang. */
    private const TIER_EFFECTS = [
        'FW' => ['attack' => 2, 'shot' => 2, 'dribble' => 1, 'pass' => 1],
        'MF' => ['attack' => 1, 'pass' => 2, 'dribble' => 1, 'shot' => 1, 'defense' => 1, 'tackle' => 1, 'intercept' => 1],
        'DF' => ['defense' => 2, 'block' => 2, 'tackle' => 2, 'intercept' => 2, 'pass' => 1],
        'GK' => ['defense' => 2, 'block' => 1, 'intercept' => 1, 'hand_save' => 2, 'punch_save' => 2],
    ];

    /**
     * Nom complet => [rang, points forts]. Points forts : speed, stamina, shot, pass,
     * dribble, tackle, intercept, block, attack, defense, heading, save.
     */
    public const TUNING = [
        // Attaquants — buteurs
        'Jürgen Klinsmann' => [1, ['shot']], 'Rudi Völler' => [1, ['shot']],
        'Sylvain Wiltord' => [-1, ['speed']], 'Luis Suárez' => [2, ['shot', 'dribble']],
        'Luis Hernández' => [0, ['shot']], 'Karl-Heinz Rummenigge' => [2, ['shot', 'speed']],
        'Mario Kempes' => [2, ['shot', 'stamina']], 'Vavá' => [1, ['shot']],
        'Leônidas' => [2, ['dribble', 'shot']], 'Paolo Rossi' => [1, ['shot']],
        'Karl-Heinz Riedle' => [0, []], 'David Trezeguet' => [1, ['shot']],
        'Nicolas Frutos' => [-2, []], 'Patrick Kluivert' => [1, []],
        'John Bosman' => [-1, ['shot']], 'Foeke Booy' => [-2, []],
        'Filippo Inzaghi' => [1, ['shot']], 'Andy Cole' => [0, ['speed']],
        'Boško Balaban' => [-1, []], 'Robert Špehar' => [-2, []], 'Mario Stanić' => [-1, ['stamina']],
        'Milan Jovanović' => [-1, []], 'Dieumerci Mbokani' => [-1, []], 'Rune Lange' => [-2, []],
        'Andrés Mendoza' => [-1, []], 'Joseph Akpala' => [-2, []], 'Souleymane Oularé' => [-2, []],
        'Predrag Mijatović' => [1, ['shot', 'dribble']],
        'Mémé Tchité' => [-1, []], 'François Sterchele' => [-1, ['shot']], 'Luigi Pieroni' => [-1, []],
        'Cédric Roussel' => [-1, []], 'Branko Strupar' => [0, ['shot']], 'Josip Weber' => [0, ['shot']],
        'Alexandre Czerniatynski' => [0, []], 'Michaël Goossens' => [-2, []],
        'Youri Djorkaeff' => [0, ['pass']], 'Diego Forlán' => [1, ['shot']], 'Cuauhtémoc Blanco' => [0, ['dribble']],
        'Thomas Müller' => [1, ['stamina']], 'Juan Alberto Schiaffino' => [2, ['pass', 'dribble']],
        'Giuseppe Meazza' => [2, ['shot']], 'Roberto Baggio' => [2, ['dribble', 'pass']],
        'Alessandro Del Piero' => [2, ['dribble', 'shot']], 'Aurelio Vidmar' => [-2, []], 'Dwight Yorke' => [-1, []],
        'Dominique Rocheteau' => [1, ['speed', 'dribble']], 'Claudio Caniggia' => [1, ['speed']],
        'Marc Overmars' => [1, ['speed']], 'Helmut Rahn' => [1, ['shot']], 'Jairzinho' => [2, ['speed', 'shot']],
        'Aruna Dindane' => [-1, []], 'Tomasz Radzinski' => [-1, ['speed']],
        'Wesley Sonck' => [0, ['shot']], 'Nico Claesen' => [1, ['shot']], 'Erwin Vandenbergh' => [1, ['shot']],
        'Luis Oliveira' => [0, []], 'Gilles De Bilde' => [0, []],
        'Lukas Podolski' => [0, ['shot']], 'Gabriel Batistuta' => [2, ['shot']], 'Christian Vieri' => [1, ['shot']],
        'Alan Shearer' => [2, ['shot']], 'Miroslav Klose' => [2, ['shot']], 'Jorge Valdano' => [1, []],
        'Martín Palermo' => [0, []], 'Mbo Mpenza' => [0, ['speed']], 'Gert Verheyen' => [0, []],
        'Leandro Trossard' => [1, ['dribble']],

        // Défenseurs centraux
        'Jürgen Kohler' => [1, ['tackle']], 'Guido Buchwald' => [0, ['stamina']], 'Marcel Desailly' => [2, ['tackle', 'stamina']],
        'Frank Leboeuf' => [0, ['pass']], 'Oscar Ruggeri' => [1, []], 'Roberto Ayala' => [1, []],
        'Jaap Stam' => [2, ['tackle', 'speed']], 'Diego Godín' => [2, ['tackle']], 'Diego Lugano' => [1, []],
        'Fabio Cannavaro' => [2, ['intercept', 'tackle']], 'José Nasazzi' => [1, ['tackle']], 'Dante' => [0, []],
        'Ciro Ferrara' => [1, []], 'Oguchi Onyewu' => [-1, []], 'Roland Juhász' => [-1, []],
        'Jelle Van Damme' => [-1, ['tackle']], 'Nicolas Lombaerts' => [-1, []], 'Birger Maertens' => [-2, []],
        'Philippe Clement' => [0, ['stamina']], 'Carl Hoefkens' => [-1, []], 'Rudi Smidts' => [-2, []],
        'Regi Van Acker' => [-2, []], 'Glen De Boeck' => [-1, []], 'Bertrand Crasson' => [-1, []], 'Régis Genaux' => [-1, []],
        'Franz Beckenbauer' => [2, ['pass']], 'Laurent Blanc' => [1, ['pass']], 'Daniel Passarella' => [1, ['shot']],
        'Ronald Koeman' => [2, ['pass', 'shot']], 'Rafael Márquez' => [1, []], 'Matthias Sammer' => [2, ['intercept']],
        'Frank de Boer' => [1, ['pass']], 'Fernando Hierro' => [1, ['pass']],
        'Jan Vertonghen' => [1, ['pass']], 'Georges Grün' => [0, []], 'Philippe Albert' => [1, ['pass']],
        'Thomas Vermaelen' => [1, ['pass']], 'Stéphane Demol' => [0, []],

        // Latéraux
        'Philipp Lahm' => [2, ['intercept']], 'Lilian Thuram' => [2, ['tackle', 'speed']], 'Javier Zanetti' => [2, ['stamina']],
        'Michael Reiziger' => [0, []], 'Carlos Salcido' => [0, []], 'Gianluca Zambrotta' => [1, ['stamina']],
        'Christian Panucci' => [0, []], 'Gary Neville' => [1, []], 'Marcin Wasilewski' => [-1, []],
        'Philippe Léonard' => [-1, []], 'Éric Deflandre' => [-1, ['stamina']], 'Vital Borkelmans' => [-1, []],
        'Nico Van Kerckhoven' => [-1, []],
        'Andreas Brehme' => [1, ['shot']], 'Bixente Lizarazu' => [1, ['stamina']], 'Juan Pablo Sorín' => [0, []],
        'Giovanni van Bronckhorst' => [1, ['shot']], 'Marcos Cafu' => [2, ['stamina', 'speed']],
        'Roberto Carlos' => [2, ['shot', 'speed']], 'Tibor Selymes' => [-1, []], 'Celestine Babayaro' => [-1, ['speed']],

        // Milieux
        'Lothar Matthäus' => [2, ['shot', 'stamina']], 'Patrick Vieira' => [2, ['tackle', 'stamina']], 'Jorge Burruchaga' => [0, ['dribble']],
        'Ricardo Giusti' => [-1, []], 'Edgar Davids' => [1, ['tackle', 'stamina']], 'Andrés Guardado' => [0, ['pass']],
        'Didi' => [2, ['pass']], 'Michael Ballack' => [1, ['shot']], 'Andreas Möller' => [0, ['dribble']],
        'Juan Sebastián Verón' => [1, ['pass']], 'Clarence Seedorf' => [1, ['pass', 'stamina']],
        'Pavel Nedvěd' => [2, ['stamina', 'shot']], 'Nastja Čeh' => [-2, []], 'Steven Defour' => [0, ['tackle']],
        'Yves Vanderhaeghe' => [0, ['intercept']], 'Bernd Thijs' => [-1, []], 'Gaëtan Englebert' => [-1, []],
        'Sven Vermant' => [-1, []], 'Robby Van de Weyer' => [-2, []], 'Roberto Bisconti' => [-2, []],
        'Emmanuel Petit' => [0, []], 'Claude Makélélé' => [2, ['tackle', 'intercept']], 'Javier Mascherano' => [1, ['tackle']],
        'Diego Simeone' => [1, ['tackle', 'stamina']], 'Frank Rijkaard' => [2, ['intercept']], 'Mark van Bommel' => [0, ['tackle']],
        'Egidio Arévalo Ríos' => [0, []], 'Diego Pérez' => [0, []], 'Gerardo Torrado' => [-1, []],
        'Carlos Dunga' => [1, ['stamina']], 'Gennaro Gattuso' => [1, ['tackle', 'stamina']], 'Didier Deschamps' => [1, ['intercept']],
        'Lucas Biglia' => [0, ['pass']], 'Fernando Redondo' => [2, ['pass', 'dribble']], 'Pep Guardiola' => [1, ['pass']],
        'Roy Keane' => [2, ['stamina', 'tackle']], 'Paul Okon' => [-1, []], 'Besnik Hasi' => [-2, []],
        'Thomas Hässler' => [0, ['dribble']], 'Robert Pirès' => [1, ['dribble']], 'Gheorghe Hagi' => [2, ['shot', 'pass']],
        'Hidetoshi Nakata' => [1, ['pass']], 'Pär Zetterberg' => [0, []], 'Bryan Ruiz' => [-1, []],
        'Jari Litmanen' => [1, ['pass']], 'Dejan Savićević' => [1, ['dribble']], 'Walter Baseggio' => [-1, []],
        'Pierre Littbarski' => [1, ['dribble']], 'Sérgio Conceição' => [0, ['speed']], 'Milan Rapaić' => [-1, []],
        'Ryan Giggs' => [2, ['speed', 'dribble']], 'René Vandereycken' => [1, ['stamina']], 'Lorenzo Staelens' => [0, ['stamina']],

        // Gardiens
        'Fabien Barthez' => [1, []], 'Hugo Lloris' => [1, []], 'Ubaldo Fillol' => [1, []], 'Nery Pumpido' => [0, []],
        'Edwin van der Sar' => [2, []], 'Fernando Muslera' => [0, []], 'Guillermo Ochoa' => [1, []],
        'Cláudio Taffarel' => [1, []], 'Bodo Illgner' => [0, []], 'Angelo Peruzzi' => [1, []],
        'Sebastiano Rossi' => [0, []], 'Vedran Runje' => [-1, []], 'Daniel Zítka' => [0, []],
        'Stijn Stijnen' => [-1, []], 'Silvio Proto' => [0, []], 'Logan Bailly' => [-2, []], 'Filip De Wilde' => [0, []],
        'Dany Verlinden' => [-1, []], 'Gilbert Bodart' => [0, []], 'Geert De Vlieger' => [0, []],
        'Jean-Marie Pfaff' => [1, []], "Michel Preud'homme" => [2, []], 'Dino Zoff' => [2, []], 'Oliver Kahn' => [2, []],
    ];

    /**
     * Stats ajustées d'un joueur d'après son rang et ses points forts, ou les stats
     * d'origine s'il n'est pas dans le tableau.
     *
     * @param  array<string, int>  $stats
     * @return array<string, int>
     */
    public static function adjust(string $fullName, string $position, array $stats): array
    {
        if (! isset(self::TUNING[$fullName])) {
            return $stats;
        }
        [$tier, $tags] = self::TUNING[$fullName];
        $role = match ($position) {
            'Goalkeeper' => 'GK', 'Defender' => 'DF', 'Midfielder' => 'MF', default => 'FW',
        };

        foreach (self::TIER_EFFECTS[$role] as $key => $perStep) {
            $stats[$key] = ($stats[$key] ?? 0) + $perStep * $tier;
        }
        foreach ($tags as $tag) {
            if ($tag === 'save') {
                $stats['hand_save'] = ($stats['hand_save'] ?? 0) + 3;
                $stats['punch_save'] = ($stats['punch_save'] ?? 0) + 3;
            } elseif ($tag === 'heading') {
                $stats['heading'] = ($stats['heading'] ?? 18) + 5;
            } else {
                $stats[$tag] = ($stats[$tag] ?? 0) + self::TAG_BONUS[$tag];
            }
        }
        foreach (self::CAPS[$role] as $key => $cap) {
            if (isset($stats[$key])) {
                $stats[$key] = min($cap, $stats[$key]);
            }
        }
        // Plancher commun : jamais sous 10.
        foreach ($stats as $key => $value) {
            $stats[$key] = max(10, (int) $value);
        }

        return $stats;
    }
}
