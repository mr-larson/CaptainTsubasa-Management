<?php

namespace Database\Seeders\Players\International;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de le Brésil.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans CaptainTsubasaInternationalPlayers::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class Brazil
{
    /**
     * Joueurs de le Brésil, gardien(s) en premier puis défenseurs, milieux, attaquants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return [
            // --- Gardien ---
            [
                '', 'Salinas', 13, 'Goalkeeper', 340,
                [
                    'speed' => 60, 'stamina' => 76, 'defense' => 38, 'attack' => 16,
                    'shot' => 13, 'pass' => 18, 'dribble' => 16,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 30, 'punch_save' => 29,
                ],
                'Gardien brésilien agile et spectaculaire, dernier rempart de la Seleção.',
            ],

            // --- Défenseurs ---
            [
                '', 'Alberto', 13, 'Defender', 410,
                [
                    'speed' => 73, 'stamina' => 82, 'defense' => 39, 'attack' => 26,
                    'shot' => 20, 'pass' => 26, 'dribble' => 24,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine et patron de la défense brésilienne, leader autoritaire au marquage implacable.',
            ],
            [
                '', 'Casa Grande', 13, 'Defender', 400,
                [
                    'speed' => 71, 'stamina' => 81, 'defense' => 39, 'attack' => 24,
                    'shot' => 19, 'pass' => 24, 'dribble' => 22,
                    'block' => 30, 'intercept' => 27, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Roc défensif de la Seleção, puissant dans les duels et impérial dans le jeu aérien.',
            ],
            [
                '', 'Giorgi', 13, 'Defender', 370,
                [
                    'speed' => 70, 'stamina' => 79, 'defense' => 37, 'attack' => 22,
                    'shot' => 18, 'pass' => 23, 'dribble' => 22,
                    'block' => 29, 'intercept' => 27, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur véloce et accrocheur, fidèle soutien de l’arrière-garde brésilienne.',
            ],
            [
                '', 'Senardo', 13, 'Defender', 350,
                [
                    'speed' => 69, 'stamina' => 78, 'defense' => 36, 'attack' => 21,
                    'shot' => 17, 'pass' => 22, 'dribble' => 21,
                    'block' => 28, 'intercept' => 26, 'tackle' => 28,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur discipliné et endurant, il verrouille son couloir sans relâche.',
            ],

            // --- Milieux ---
            [
                '', 'Natureza', 13, 'Midfielder', 470,
                [
                    'speed' => 76, 'stamina' => 82, 'defense' => 26, 'attack' => 38,
                    'shot' => 28, 'pass' => 30, 'dribble' => 30,
                    'block' => 21, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Prodige de la Seleção, meneur surdoué aux dribbles et passes magiques. L’un des plus grands talents du World Youth.',
                ['Forward'],
            ],
            [
                '', 'Silva', 13, 'Midfielder', 400,
                [
                    'speed' => 73, 'stamina' => 80, 'defense' => 27, 'attack' => 33,
                    'shot' => 26, 'pass' => 29, 'dribble' => 28,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu samba créatif, il fait circuler le ballon avec une aisance déconcertante.',
            ],
            [
                '', 'Dugo', 13, 'Midfielder', 390,
                [
                    'speed' => 73, 'stamina' => 79, 'defense' => 27, 'attack' => 32,
                    'shot' => 25, 'pass' => 28, 'dribble' => 28,
                    'block' => 22, 'intercept' => 24, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Relayeur technique au jeu fluide, moteur du milieu de terrain brésilien.',
            ],
            [
                '', 'Blanco', 13, 'Midfielder', 380,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 29, 'attack' => 31,
                    'shot' => 24, 'pass' => 28, 'dribble' => 27,
                    'block' => 24, 'intercept' => 26, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu récupérateur infatigable, premier rempart devant la défense brésilienne.',
            ],
            [
                '', 'Marcio', 13, 'Midfielder', 360,
                [
                    'speed' => 71, 'stamina' => 78, 'defense' => 26, 'attack' => 30,
                    'shot' => 24, 'pass' => 27, 'dribble' => 26,
                    'block' => 22, 'intercept' => 24, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu polyvalent et travailleur, précieux équilibre dans l’entrejeu brésilien.',
            ],

            // --- Attaquants ---
            [
                'Carlos', 'Santana', 13, 'Forward', 500,
                [
                    'speed' => 79, 'stamina' => 84, 'defense' => 25, 'attack' => 42,
                    'shot' => 31, 'pass' => 27, 'dribble' => 30,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Star de la Seleção et grand rival de Tsubasa. Technique féline et finition redoutable, l’attaquant vedette de Flamengo.',
            ],
            [
                '', 'Pepe', 13, 'Forward', 480,
                [
                    'speed' => 78, 'stamina' => 83, 'defense' => 24, 'attack' => 41,
                    'shot' => 31, 'pass' => 25, 'dribble' => 29,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Buteur surpuissant de São Paulo, sa frappe dévastatrice en fait l’une des terreurs de la Seleção.',
            ],
            [
                'Luciano', 'Leo', 13, 'Forward', 460,
                [
                    'speed' => 80, 'stamina' => 82, 'defense' => 23, 'attack' => 38,
                    'shot' => 29, 'pass' => 26, 'dribble' => 30,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier foudroyant de Flamengo, sa vitesse et ses débordements affolent les défenses.',
            ],
        ];
    }
}
