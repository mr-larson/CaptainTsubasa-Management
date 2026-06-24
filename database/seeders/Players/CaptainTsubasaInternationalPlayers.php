<?php

namespace Database\Seeders\Players;

/**
 * Données joueurs (et special moves) — groupe : stars internationales de
 * l'univers Captain Tsubasa (rivaux étrangers du World Youth / films).
 *
 * Ces joueurs n'ont PAS de contrat de club : ils rejoignent le marché des
 * agents libres de l'origine `captain_tsubasa` et, surtout, ils sont
 * sélectionnables en équipe nationale (mode Coupe du Monde) via leur
 * `nationality` (cf. nationalities()).
 *
 * NB : effectifs « noyau canonique » destinés à être complétés — l'assembleur
 * de sélection nationale (Phase 3) comble les places manquantes. Stats calées
 * sur l'échelle des meilleurs joueurs collège (Tsubasa ~attack 39 / Hyuga 42).
 */
class CaptainTsubasaInternationalPlayers
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            // =======================
            // ALLEMAGNE
            // =======================
            [
                'Karl-Heinz', 'Schneider', 13, 'Forward', 500,
                [
                    'speed' => 77, 'stamina' => 84, 'defense' => 25, 'attack' => 42,
                    'shot' => 31, 'pass' => 24, 'dribble' => 28,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Le « Kaiser » du football allemand. Buteur d’exception au tir surpuissant, il incarne la rigueur et la puissance de la sélection.',
            ],
            [
                'Hermann', 'Kaltz', 13, 'Defender', 430,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 39, 'attack' => 28,
                    'shot' => 21, 'pass' => 27, 'dribble' => 24,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral offensif infatigable, il monte sans cesse pour soutenir l’attaque et combine avec Schneider.',
                ['Midfielder'],
            ],
            [
                'Manfred', 'Margus', 13, 'Forward', 380,
                [
                    'speed' => 66, 'stamina' => 76, 'defense' => 38, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 18,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur central solide et discipliné, pilier de l’arrière-garde allemande.',
            ],
            [
            'Franz', 'Schester', 13, 'Midfielder', 380,
                [
                    'speed' => 66, 'stamina' => 76, 'defense' => 38, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 18,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur central solide et discipliné, pilier de l’arrière-garde allemande.',
            ],
            [
                'Deuter', 'Müller', 13, 'Goalkeeper', 340,
                [
                    'speed' => 57, 'stamina' => 74, 'defense' => 36, 'attack' => 15,
                    'shot' => 12, 'pass' => 17, 'dribble' => 15,
                    'block' => 25, 'intercept' => 23, 'tackle' => 19,
                    'hand_save' => 30, 'punch_save' => 30,
                ],
                'Gardien rigoureux et bien placé, dernier rempart fiable de la sélection allemande.',
            ],

            // =======================
            // FRANCE
            // =======================
            [
                'El Sid', 'Pierre', 13, 'Midfielder', 470,
                [
                    'speed' => 75, 'stamina' => 80, 'defense' => 25, 'attack' => 35,
                    'shot' => 26, 'pass' => 30, 'dribble' => 30,
                    'block' => 21, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Meneur virevoltant des « Jumeaux de l’aigle ». Dribble et vision exceptionnels, il orchestre le jeu français avec Napoléon.',
                ['Forward'],
            ],
            [
                'Louis', 'Napoleon', 13, 'Forward', 460,
                [
                    'speed' => 74, 'stamina' => 78, 'defense' => 23, 'attack' => 40,
                    'shot' => 31, 'pass' => 27, 'dribble' => 29,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant tranchant, partenaire de Pierre. Leur une-deux « de l’aigle » déchire les défenses.',
            ],

            // =======================
            // BRÉSIL
            // =======================
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
                '', 'radunga', 13, 'Defender', 370,
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

            // =======================
            // ARGENTINE
            // =======================
            [
                'Juan', 'Diaz', 13, 'Forward', 480,
                [
                    'speed' => 75, 'stamina' => 80, 'defense' => 23, 'attack' => 41,
                    'shot' => 31, 'pass' => 25, 'dribble' => 29,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Buteur argentin au sang-froid glacial, son tir foudroyant fait trembler les filets.',
            ],
            [
                'Ramon', 'Galvan', 13, 'Defender', 370,
                [
                    'speed' => 69, 'stamina' => 77, 'defense' => 38, 'attack' => 22,
                    'shot' => 18, 'pass' => 23, 'dribble' => 22,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur accrocheur et roublard, spécialiste du marquage individuel.',
            ],
            [
                'Juan', 'Babington', 13, 'Midfielder', 390,
                [
                    'speed' => 72, 'stamina' => 79, 'defense' => 27, 'attack' => 32,
                    'shot' => 26, 'pass' => 30, 'dribble' => 29,
                    'block' => 23, 'intercept' => 25, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu complet, relais entre défense et attaque, moteur du collectif argentin.',
            ],

            // =======================
            // URUGUAY
            // =======================
            // NB : Ryoma Hino (FW #9) existe déjà comme agent libre dans
            // CaptainTsubasaFreePlayers ; il est rattaché à l'Uruguay via
            // nationalities() ci-dessous (pas de doublon ici).
            // --- Gardien ---
            [
                '', 'Conzales', 13, 'Goalkeeper', 330,
                [
                    'speed' => 60, 'stamina' => 76, 'defense' => 38, 'attack' => 16,
                    'shot' => 13, 'pass' => 18, 'dribble' => 16,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 29, 'punch_save' => 28,
                ],
                'Gardien uruguayen courageux et explosif, dernier rempart de la Celeste.',
            ],

            // --- Défenseurs ---
            [
                '', 'Amerigo', 13, 'Defender', 400,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 38, 'attack' => 22,
                    'shot' => 17, 'pass' => 23, 'dribble' => 22,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Libéro uruguayen au sang-froid, organisateur d’une défense imperméable.',
            ],
            [
                '', 'Pazu', 13, 'Defender', 380,
                [
                    'speed' => 69, 'stamina' => 80, 'defense' => 38, 'attack' => 21,
                    'shot' => 17, 'pass' => 22, 'dribble' => 21,
                    'block' => 29, 'intercept' => 27, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur tout en « garra charrúa », il harcèle l’attaquant jusqu’au bout.',
            ],
            [
                '', 'Olivares', 13, 'Defender', 370,
                [
                    'speed' => 68, 'stamina' => 79, 'defense' => 37, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 29, 'intercept' => 27, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur dur au mal et bon relanceur, fiable dans l’adversité.',
            ],
            [
                '', 'Filippo', 13, 'Defender', 360,
                [
                    'speed' => 67, 'stamina' => 78, 'defense' => 36, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 28, 'intercept' => 26, 'tackle' => 28,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur appliqué, précieux dans la couverture et le marquage.',
            ],

            // --- Milieux ---
            [
                '', 'Dionisi', 13, 'Midfielder', 420,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 28, 'attack' => 33,
                    'shot' => 26, 'pass' => 29, 'dribble' => 28,
                    'block' => 23, 'intercept' => 25, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Meneur uruguayen technique et combatif, plaque tournante de la Celeste.',
            ],
            [
                '', 'Enrico', 13, 'Midfielder', 410,
                [
                    'speed' => 71, 'stamina' => 79, 'defense' => 27, 'attack' => 32,
                    'shot' => 25, 'pass' => 28, 'dribble' => 27,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif inspiré, il illumine le jeu uruguayen de ses passes.',
            ],
            [
                '', 'Pedro', 13, 'Midfielder', 390,
                [
                    'speed' => 71, 'stamina' => 80, 'defense' => 29, 'attack' => 31,
                    'shot' => 25, 'pass' => 28, 'dribble' => 27,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu box-to-box infatigable, poumon de l’entrejeu uruguayen.',
            ],
            [
                '', 'Fengas', 13, 'Midfielder', 380,
                [
                    'speed' => 70, 'stamina' => 79, 'defense' => 29, 'attack' => 30,
                    'shot' => 24, 'pass' => 27, 'dribble' => 26,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Récupérateur tenace, il gratte tous les ballons devant la défense.',
            ],

            // --- Attaquant (capitaine) ---
            [
                'Ramon', 'Victorino', 13, 'Forward', 470,
                [
                    'speed' => 74, 'stamina' => 82, 'defense' => 25, 'attack' => 41,
                    'shot' => 31, 'pass' => 25, 'dribble' => 28,
                    'block' => 21, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine et avant-centre de la Celeste, puissant et combatif, sa frappe lourde est une arme de destruction.',
            ],

            // =======================
            // PAYS-BAS
            // =======================
            // --- Gardien ---
            [
                'Hans', 'Dolman', 13, 'Goalkeeper', 340,
                [
                    'speed' => 60, 'stamina' => 76, 'defense' => 38, 'attack' => 16,
                    'shot' => 13, 'pass' => 19, 'dribble' => 16,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 30, 'punch_save' => 29,
                ],
                'Gardien de l’Ajax, sûr sur sa ligne et excellent dans le jeu au pied à la mode oranje.',
            ],

            // --- Défenseur ---
            [
                'Leon', 'Dick', 13, 'Defender', 400,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 38, 'attack' => 23,
                    'shot' => 18, 'pass' => 24, 'dribble' => 23,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur de l’Ajax adepte du football total, solide et très à l’aise balle au pied.',
            ],

            // --- Milieux ---
            [
                'Ruud', 'Krisman', 13, 'Midfielder', 460,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 27, 'attack' => 35,
                    'shot' => 28, 'pass' => 30, 'dribble' => 29,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Vice-capitaine et meneur de l’Ajax, technicien complet et inspirateur du jeu oranje.',
            ],
            [
                '', 'Haan', 13, 'Midfielder', 400,
                [
                    'speed' => 71, 'stamina' => 80, 'defense' => 29, 'attack' => 31,
                    'shot' => 25, 'pass' => 29, 'dribble' => 27,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu complet à la frappe lointaine redoutable, métronome de l’entrejeu néerlandais.',
            ],
            [
                'Brian', 'Cruyfford', 13, 'Midfielder', 470,
                [
                    'speed' => 77, 'stamina' => 80, 'defense' => 23, 'attack' => 40,
                    'shot' => 31, 'pass' => 29, 'dribble' => 30,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine et génie du football total néerlandais, à l’aise partout devant, fin technicien et meneur d’hommes.',
                ['Forward'],
            ],

            // --- Attaquants ---
            [
                'Johan', 'Rensenbrink', 13, 'Forward', 450,
                [
                    'speed' => 77, 'stamina' => 79, 'defense' => 23, 'attack' => 38,
                    'shot' => 29, 'pass' => 26, 'dribble' => 30,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier néerlandais virevoltant, dribbleur élégant et finisseur tout en finesse.',
            ],
            [
                'Gert', 'Keizer', 13, 'Forward', 435,
                [
                    'speed' => 75, 'stamina' => 78, 'defense' => 23, 'attack' => 37,
                    'shot' => 28, 'pass' => 26, 'dribble' => 29,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier rapide et technique, spécialiste des débordements et des centres millimétrés.',
            ],

            // =======================
            // MEXIQUE
            // =======================
            // --- Gardien ---
            [
                'Ricardo', 'Espadas', 13, 'Goalkeeper', 320,
                [
                    'speed' => 58, 'stamina' => 74, 'defense' => 36, 'attack' => 15,
                    'shot' => 12, 'pass' => 17, 'dribble' => 15,
                    'block' => 25, 'intercept' => 23, 'tackle' => 19,
                    'hand_save' => 28, 'punch_save' => 27,
                ],
                'Capitaine et gardien émérite du Mexique, vif sur sa ligne et meneur d’hommes respecté.',
            ],

            // --- Défenseurs ---
            [
                '', 'Medina', 13, 'Defender', 360,
                [
                    'speed' => 67, 'stamina' => 76, 'defense' => 35, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 27, 'intercept' => 25, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur solide et combatif, taulier de l’arrière-garde mexicaine.',
            ],
            [
                '', 'Carvajal', 13, 'Defender', 360,
                [
                    'speed' => 68, 'stamina' => 76, 'defense' => 35, 'attack' => 21,
                    'shot' => 16, 'pass' => 22, 'dribble' => 21,
                    'block' => 27, 'intercept' => 25, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral offensif endurant, il apporte le surnombre sur son couloir.',
            ],
            [
                '', 'Gomez', 13, 'Defender', 350,
                [
                    'speed' => 66, 'stamina' => 75, 'defense' => 34, 'attack' => 19,
                    'shot' => 15, 'pass' => 20, 'dribble' => 19,
                    'block' => 26, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur rugueux au sens du placement, difficile à déborder.',
            ],
            [
                '', 'Espino', 13, 'Defender', 345,
                [
                    'speed' => 66, 'stamina' => 74, 'defense' => 34, 'attack' => 20,
                    'shot' => 15, 'pass' => 20, 'dribble' => 20,
                    'block' => 26, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur discipliné, précieux dans l’organisation de la ligne mexicaine.',
            ],
            [
                '', 'Rivera', 13, 'Defender', 340,
                [
                    'speed' => 65, 'stamina' => 75, 'defense' => 33, 'attack' => 19,
                    'shot' => 15, 'pass' => 20, 'dribble' => 19,
                    'block' => 26, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur appliqué et généreux dans l’effort, fiable dans les duels.',
            ],

            // --- Milieux ---
            [
                '', 'Zaragoza', 13, 'Midfielder', 410,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 27, 'attack' => 31,
                    'shot' => 25, 'pass' => 28, 'dribble' => 27,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Meneur de jeu mexicain à la technique soignée, cerveau de la Tricolor.',
            ],
            [
                '', 'Garcia', 13, 'Midfielder', 385,
                [
                    'speed' => 69, 'stamina' => 77, 'defense' => 28, 'attack' => 29,
                    'shot' => 23, 'pass' => 27, 'dribble' => 26,
                    'block' => 23, 'intercept' => 25, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu travailleur et précis, liant solide entre défense et attaque.',
            ],
            [
                '', 'Suarez', 13, 'Midfielder', 380,
                [
                    'speed' => 68, 'stamina' => 76, 'defense' => 26, 'attack' => 28,
                    'shot' => 23, 'pass' => 26, 'dribble' => 26,
                    'block' => 22, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu vif et créatif, il accélère le jeu par ses passes incisives.',
            ],

            // --- Attaquants ---
            [
                '', 'Alvez', 13, 'Forward', 440,
                [
                    'speed' => 75, 'stamina' => 79, 'defense' => 23, 'attack' => 37,
                    'shot' => 28, 'pass' => 25, 'dribble' => 28,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Buteur vedette du Mexique, opportuniste redoutable dans la surface.',
            ],
            [
                '', 'Lopez', 13, 'Forward', 425,
                [
                    'speed' => 74, 'stamina' => 78, 'defense' => 23, 'attack' => 36,
                    'shot' => 27, 'pass' => 24, 'dribble' => 27,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant remuant et adroit, partenaire de pointe complémentaire d’Alvez.',
            ],

            // =======================
            // ITALIE
            // =======================
            // NB : la sélection italienne s'appuie aussi sur les joueurs de
            // l'École des Champions (origin ecole_des_champions, italiens par
            // défaut), qui restent dans le vivier de la Squadra Azzurra.
            // --- Gardiens ---
            [
                'Gino', 'Hernandez', 13, 'Goalkeeper', 340,
                [
                    'speed' => 60, 'stamina' => 76, 'defense' => 38, 'attack' => 16,
                    'shot' => 13, 'pass' => 18, 'dribble' => 16,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 30, 'punch_save' => 29,
                ],
                'Capitaine et gardien de l’Inter, réflexes d’élite et autorité dans la surface azzurra.',
            ],
            [
                '', 'Amoruso', 13, 'Goalkeeper', 300,
                [
                    'speed' => 57, 'stamina' => 73, 'defense' => 35, 'attack' => 14,
                    'shot' => 12, 'pass' => 16, 'dribble' => 15,
                    'block' => 24, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 27, 'punch_save' => 26,
                ],
                'Gardien remplaçant fiable, solide doublure dans les buts italiens.',
            ],

            // --- Défenseurs (catenaccio) ---
            [
                'Salvatore', 'Gentile', 13, 'Defender', 410,
                [
                    'speed' => 69, 'stamina' => 81, 'defense' => 39, 'attack' => 21,
                    'shot' => 17, 'pass' => 22, 'dribble' => 21,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur de la Juventus, marqueur intraitable et roc du catenaccio italien.',
            ],
            [
                '', 'Tacchinardi', 13, 'Defender', 390,
                [
                    'speed' => 68, 'stamina' => 80, 'defense' => 38, 'attack' => 21,
                    'shot' => 16, 'pass' => 22, 'dribble' => 21,
                    'block' => 30, 'intercept' => 27, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur athlétique et combatif, précieux dans la récupération haute.',
            ],
            [
                '', 'Fresi', 13, 'Defender', 375,
                [
                    'speed' => 67, 'stamina' => 79, 'defense' => 37, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 29, 'intercept' => 27, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur central rigoureux, fort dans le jeu aérien et le placement.',
            ],
            [
                '', 'Galante', 13, 'Defender', 370,
                [
                    'speed' => 67, 'stamina' => 79, 'defense' => 37, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 29, 'intercept' => 26, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur élégant et sûr, il relance proprement depuis l’arrière.',
            ],
            [
                '', 'Pagotto', 13, 'Defender', 355,
                [
                    'speed' => 66, 'stamina' => 78, 'defense' => 36, 'attack' => 19,
                    'shot' => 15, 'pass' => 20, 'dribble' => 19,
                    'block' => 28, 'intercept' => 26, 'tackle' => 28,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral discipliné et endurant, fiable sur son couloir.',
            ],

            // --- Milieux ---
            [
                '', 'Totti', 13, 'Midfielder', 460,
                [
                    'speed' => 73, 'stamina' => 80, 'defense' => 27, 'attack' => 36,
                    'shot' => 28, 'pass' => 30, 'dribble' => 30,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Fantasista de la Squadra Azzurra, génie créatif aux passes et dribbles d’exception.',
                ['Forward'],
            ],
            [
                '', 'Branca', 13, 'Midfielder', 400,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 26, 'attack' => 32,
                    'shot' => 27, 'pass' => 27, 'dribble' => 26,
                    'block' => 22, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif au tir précis, redoutable arrivée de la deuxième ligne.',
            ],
            [
                '', 'Delvecchio', 13, 'Midfielder', 390,
                [
                    'speed' => 70, 'stamina' => 79, 'defense' => 28, 'attack' => 31,
                    'shot' => 26, 'pass' => 27, 'dribble' => 26,
                    'block' => 23, 'intercept' => 25, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu travailleur et généreux, il abat un volume de jeu considérable.',
            ],
            [
                '', 'Panucci', 13, 'Midfielder', 385,
                [
                    'speed' => 71, 'stamina' => 80, 'defense' => 30, 'attack' => 29,
                    'shot' => 23, 'pass' => 27, 'dribble' => 25,
                    'block' => 25, 'intercept' => 27, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu défensif à vocation de récupération, charnière entre les lignes.',
            ],

            // --- Attaquants ---
            [
                '', 'Lentini', 13, 'Forward', 450,
                [
                    'speed' => 76, 'stamina' => 79, 'defense' => 23, 'attack' => 37,
                    'shot' => 28, 'pass' => 26, 'dribble' => 29,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier vif et percutant, ses débordements et centres font des ravages.',
            ],
            [
                '', 'Bianchi', 13, 'Forward', 435,
                [
                    'speed' => 74, 'stamina' => 78, 'defense' => 23, 'attack' => 36,
                    'shot' => 28, 'pass' => 24, 'dribble' => 27,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Avant-centre opportuniste, buteur d’instinct dans la surface.',
            ],
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function specialMoves(): array
    {
        return [
            'karl-heinz-schneider' => [[
                'key'         => 'schneider_fire_shot',
                'mode'        => 'attack',
                'label'       => 'Tir de feu',
                'short_label' => 'Feu',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une frappe d’une puissance phénoménale qui semble embraser le ballon.',
            ]],

            'el-sid-pierre' => [[
                'key'         => 'pierre_eagle_dribble',
                'mode'        => 'attack',
                'label'       => 'Dribble de l’aigle',
                'short_label' => 'Aigle',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Une accélération aérienne qui efface l’adversaire comme un aigle fond sur sa proie.',
            ]],

            'louis-napoleon' => [[
                'key'         => 'napoleon_eagle_shoot',
                'mode'        => 'attack',
                'label'       => 'Tir de l’aigle',
                'short_label' => 'Aigle',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'La conclusion de la combinaison des jumeaux de l’aigle, imparable lancée à pleine vitesse.',
            ]],

            'carlos-santana' => [[
                'key'         => 'santana_samba_strike',
                'mode'        => 'attack',
                'label'       => 'Frappe samba',
                'short_label' => 'Samba',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir acrobatique d’une technique brésilienne stupéfiante, impossible à anticiper.',
            ]],

            'salinas' => [[
                'key'         => 'salinas_jaguar_save',
                'mode'        => 'defense',
                'label'       => 'Arrêt du jaguar',
                'short_label' => 'Jaguar',
                'cooldown'    => 3,
                'base_action' => 'hand_save',
                'description' => 'Un plongeon félin d’une détente foudroyante qui dévie l’imparable.',
            ]],

            'alberto' => [[
                'key'         => 'alberto_captain_tackle',
                'mode'        => 'defense',
                'label'       => 'Tacle du capitaine',
                'short_label' => 'Capitão',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Une interception autoritaire du capitaine, qui étouffe l’attaque et relance aussitôt.',
            ]],

            'casa-grande' => [[
                'key'         => 'casa_grande_wall_block',
                'mode'        => 'defense',
                'label'       => 'Mur brésilien',
                'short_label' => 'Mur',
                'cooldown'    => 3,
                'base_action' => 'block',
                'description' => 'Un bloc d’une puissance colossale qui repousse même les frappes les plus lourdes.',
            ]],

            'natureza' => [[
                'key'         => 'natureza_wild_dribble',
                'mode'        => 'attack',
                'label'       => 'Dribble sauvage',
                'short_label' => 'Nature',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Une succession de feintes imprévisibles, instinctives comme la nature elle-même.',
            ]],

            'pepe' => [[
                'key'         => 'pepe_skylab_shot',
                'mode'        => 'attack',
                'label'       => 'Tir Skylab',
                'short_label' => 'Skylab',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une frappe surpuissante venue de São Paulo, un véritable boulet de canon.',
            ]],

            'juan-diaz' => [[
                'key'         => 'diaz_condor_shot',
                'mode'        => 'attack',
                'label'       => 'Tir du condor',
                'short_label' => 'Condor',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une frappe sèche et fulgurante qui fond sur le but comme un condor.',
            ]],

            'ramon-victorino' => [[
                'key'         => 'victorino_garra_shot',
                'mode'        => 'attack',
                'label'       => 'Tir de la garra',
                'short_label' => 'Garra',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une frappe lourde et rageuse portée par la hargne uruguayenne.',
            ]],

            'brian-cruyfford' => [[
                'key'         => 'cruyfford_total_turn',
                'mode'        => 'attack',
                'label'       => 'Pivot total',
                'short_label' => 'Pivot',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Un crochet venu du football total qui renverse l’orientation du jeu en un instant.',
            ]],

            'ricardo-espadas' => [[
                'key'         => 'espadas_aztec_save',
                'mode'        => 'defense',
                'label'       => 'Arrêt aztèque',
                'short_label' => 'Aztèque',
                'cooldown'    => 3,
                'base_action' => 'hand_save',
                'description' => 'Une parade spectaculaire du capitaine mexicain, mur infranchissable sur sa ligne.',
            ]],
        ];
    }

    /**
     * Nationalité de chaque star internationale (slug "prenom-nom" → pays).
     * Toujours renseignée ici (aucun défaut d'origin ne s'applique).
     * @return array<string, string>
     */
    public static function nationalities(): array
    {
        return [
            // Allemagne
            'karl-heinz-schneider' => 'Allemagne',
            'hermann-kaltz'        => 'Allemagne',
            'manfred-margus'       => 'Allemagne',
            'franz-schester'      => 'Allemagne',
            'deuter-muller'      => 'Allemagne',
            // France
            'el-sid-pierre'        => 'France',
            'louis-napoleon'       => 'France',
            // Brésil
            'salinas'              => 'Brésil',
            'alberto'              => 'Brésil',
            'casa-grande'          => 'Brésil',
            'radunga'                => 'Brésil',
            'senardo'              => 'Brésil',
            'natureza'             => 'Brésil',
            'silva'                => 'Brésil',
            'dugo'                 => 'Brésil',
            'blanco'               => 'Brésil',
            'marcio'               => 'Brésil',
            'carlos-santana'       => 'Brésil',
            'pepe'                 => 'Brésil',
            'luciano-leo'          => 'Brésil',
            // Argentine
            'juan-diaz'            => 'Argentine',
            'ramon-galvan'         => 'Argentine',
            'juan-babington'       => 'Argentine',
            // Uruguay
            'conzales'             => 'Uruguay',
            'amerigo'              => 'Uruguay',
            'pazu'                 => 'Uruguay',
            'olivares'             => 'Uruguay',
            'filippo'              => 'Uruguay',
            'dionisi'              => 'Uruguay',
            'enrico'               => 'Uruguay',
            'pedro'                => 'Uruguay',
            'fengas'               => 'Uruguay',
            'ramon-victorino'      => 'Uruguay',
            'ryoma-hino'           => 'Uruguay', // existe dans CaptainTsubasaFreePlayers
            // Pays-Bas
            'hans-dolman'          => 'Pays-Bas',
            'leon-dick'            => 'Pays-Bas',
            'ruud-krisman'         => 'Pays-Bas',
            'haan'                 => 'Pays-Bas',
            'brian-cruyfford'      => 'Pays-Bas',
            'johan-rensenbrink'    => 'Pays-Bas',
            'gert-keizer'          => 'Pays-Bas',
            // Mexique
            'ricardo-espadas'      => 'Mexique',
            'medina'               => 'Mexique',
            'carvajal'             => 'Mexique',
            'gomez'                => 'Mexique',
            'espino'               => 'Mexique',
            'rivera'               => 'Mexique',
            'zaragoza'             => 'Mexique',
            'garcia'               => 'Mexique',
            'suarez'               => 'Mexique',
            'alvez'                => 'Mexique',
            'lopez'                => 'Mexique',
            // Italie (la Squadra Azzurra puise aussi dans l'École des Champions)
            'gino-hernandez'       => 'Italie',
            'amoruso'              => 'Italie',
            'salvatore-gentile'    => 'Italie',
            'tacchinardi'          => 'Italie',
            'fresi'                => 'Italie',
            'galante'              => 'Italie',
            'pagotto'              => 'Italie',
            'totti'                => 'Italie',
            'branca'               => 'Italie',
            'delvecchio'           => 'Italie',
            'panucci'              => 'Italie',
            'lentini'              => 'Italie',
            'bianchi'              => 'Italie',
        ];
    }
}
