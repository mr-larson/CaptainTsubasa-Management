<?php

namespace Database\Seeders\Players;

/**
 * Données joueurs (et special moves) — groupe : RandomPlayers.
 * Extrait de PlayerSeeder pour alléger et organiser par source.
 */
class RandomPlayers
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            //-------------------------------
            // Other – Not contract (filler players)
            //-------------------------------
            ['Kenji', 'Nakashima', 13, 'Goalkeeper', 240, [
                'speed' => 52, 'stamina' => 62, 'defense' => 28, 'attack' => 15,
                'shot' => 15, 'pass' => 17, 'dribble' => 15, 'block' => 23,
                'intercept' => 22, 'tackle' => 18, 'hand_save' => 26, 'punch_save' => 24
            ], 'Gardien athlétique au bon sens du placement, fiable dans les situations de un contre un.'],

            ['Sota', 'Suzuki', 13, 'Goalkeeper', 225, [
                'speed' => 50, 'stamina' => 60, 'defense' => 26, 'attack' => 15,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 22,
                'intercept' => 21, 'tackle' => 17, 'hand_save' => 25, 'punch_save' => 23
            ], 'Gardien calme et constant, apprécié pour sa communication avec sa défense.'],

            ['Hiroki', 'Matsunaga', 13, 'Goalkeeper', 210, [
                'speed' => 48, 'stamina' => 58, 'defense' => 25, 'attack' => 15,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21,
                'intercept' => 20, 'tackle' => 17, 'hand_save' => 24, 'punch_save' => 22
            ], 'Gardien réflexe, capable de sortir des arrêts décisifs dans les moments importants.'],

            ['Daiki', 'Kuroiwa', 13, 'Goalkeeper', 195, [
                'speed' => 46, 'stamina' => 56, 'defense' => 24, 'attack' => 15,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21,
                'intercept' => 20, 'tackle' => 16, 'hand_save' => 23, 'punch_save' => 21
            ], 'Gardien travailleur qui compense un manque d\'explosivité par son sérieux et sa rigueur.'],

            ['Wataru', 'Aoyama', 12, 'Goalkeeper', 175, [
                'speed' => 42, 'stamina' => 52, 'defense' => 22, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 19,
                'intercept' => 19, 'tackle' => 16, 'hand_save' => 21, 'punch_save' => 19
            ], 'Jeune gardien encore en développement, prometteur sur les phases de réflexe.'],

            ['Tomoki', 'Fujioka', 12, 'Goalkeeper', 162, [
                'speed' => 40, 'stamina' => 50, 'defense' => 21, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 19,
                'intercept' => 18, 'tackle' => 15, 'hand_save' => 20, 'punch_save' => 19
            ], 'Gardien de complément, appliqué et discipliné dans son couloir.'],

            ['Isamu', 'Terada', 12, 'Goalkeeper', 132, [
                'speed' => 36, 'stamina' => 45, 'defense' => 19, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17,
                'intercept' => 17, 'tackle' => 15, 'hand_save' => 18, 'punch_save' => 17
            ], 'Gardien en début de formation, montre de la volonté mais manque encore d\'expérience.'],

            ['Haruki', 'Enomoto', 12, 'Goalkeeper', 118, [
                'speed' => 34, 'stamina' => 43, 'defense' => 18, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17,
                'intercept' => 17, 'tackle' => 15, 'hand_save' => 18, 'punch_save' => 17
            ], 'Gardien débutant, encore loin du niveau requis mais plein de bonne volonté.'],

            ['Ayato', 'Nakatsuji', 13, 'Defender', 235, [
                'speed' => 56, 'stamina' => 64, 'defense' => 28, 'attack' => 19,
                'shot' => 16, 'pass' => 18, 'dribble' => 16, 'block' => 24,
                'intercept' => 22, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur central robuste, excelle dans les duels aériens et le marquage serré.'],

            ['Ayumu', 'Fukazawa', 12, 'Defender', 205, [
                'speed' => 54, 'stamina' => 60, 'defense' => 26, 'attack' => 18,
                'shot' => 15, 'pass' => 17, 'dribble' => 15, 'block' => 22,
                'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur appliqué et discipliné, sécurise son couloir et coupe les trajectoires adverses.'],

            ['Keita', 'Hara', 13, 'Midfielder', 240, [
                'speed' => 58, 'stamina' => 64, 'defense' => 24, 'attack' => 26,
                'shot' => 22, 'pass' => 24, 'dribble' => 22, 'block' => 19,
                'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu box-to-box actif, capable de participer aussi bien à la récupération qu\'à la finition.'],

            ['Ayano', 'Uchiyama', 13, 'Midfielder', 222, [
                'speed' => 60, 'stamina' => 62, 'defense' => 22, 'attack' => 25,
                'shot' => 21, 'pass' => 23, 'dribble' => 23, 'block' => 18,
                'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu offensif technique, aime s\'infiltrer entre les lignes et décaler ses partenaires.'],

            ['Nico', 'Perera', 12, 'Defender', 205, [
                'speed' => 48, 'stamina' => 54, 'defense' => 26, 'attack' => 20, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 22, 'intercept' => 21, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Gauthier', 'Leblanc', 13, 'Midfielder', 210, [
                'speed' => 68, 'stamina' => 55, 'defense' => 24, 'attack' => 24, 'shot' => 19, 'pass' => 23, 'dribble' => 20, 'block' => 15, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Aito', 'Katayama', 13, 'Forward', 210, [
                'speed' => 53, 'stamina' => 55, 'defense' => 18, 'attack' => 26, 'shot' => 23, 'pass' => 19, 'dribble' => 21, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ao', 'Sakuma', 12, 'Midfielder', 200, [
                'speed' => 48, 'stamina' => 52, 'defense' => 22, 'attack' => 22, 'shot' => 21, 'pass' => 21, 'dribble' => 20, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hazuki', 'Takahashi', 12, 'Forward', 205, [
                'speed' => 49, 'stamina' => 53, 'defense' => 20, 'attack' => 24, 'shot' => 22, 'pass' => 20, 'dribble' => 21, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Sakura', 'Ikoma', 12, 'Goalkeeper', 165, [
                'speed' => 38, 'stamina' => 47, 'defense' => 25, 'attack' => 15, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20, 'intercept' => 20, 'tackle' => 17, 'hand_save' => 22, 'punch_save' => 20
            ]],
            ['Yuma', 'Ishikawa', 12, 'Goalkeeper', 135, [
                'speed' => 36, 'stamina' => 42, 'defense' => 22, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 19, 'intercept' => 19, 'tackle' => 16, 'hand_save' => 20, 'punch_save' => 19
            ]],
            ['Shu', 'Hiura', 12, 'Forward', 120, [
                'speed' => 46, 'stamina' => 37, 'defense' => 15, 'attack' => 22, 'shot' => 21, 'pass' => 17, 'dribble' => 20, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryota', 'Kondo', 12, 'Midfielder', 115, [
                'speed' => 39, 'stamina' => 35, 'defense' => 15, 'attack' => 18, 'shot' => 17, 'pass' => 17, 'dribble' => 17, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Sho', 'Suzuki', 12, 'Defender', 110, [
                'speed' => 35, 'stamina' => 36, 'defense' => 18, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kensuke', 'Hara', 12, 'Goalkeeper', 105, [
                'speed' => 33, 'stamina' => 35, 'defense' => 21, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 18, 'intercept' => 18, 'tackle' => 16, 'hand_save' => 19, 'punch_save' => 18
            ]],
            ['Haruki', 'Mori', 12, 'Midfielder', 100, [
                'speed' => 32, 'stamina' => 34, 'defense' => 15, 'attack' => 16, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Daichi', 'Inafune', 12, 'Defender', 95, [
                'speed' => 30, 'stamina' => 32, 'defense' => 16, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17, 'intercept' => 16, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kai', 'Kaga', 12, 'Forward', 90, [
                'speed' => 35, 'stamina' => 30, 'defense' => 15, 'attack' => 17, 'shot' => 17, 'pass' => 15, 'dribble' => 16, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryosuke', 'Tanaka', 12, 'Midfielder', 80, [
                'speed' => 31, 'stamina' => 30, 'defense' => 15, 'attack' => 16, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Naoki', 'Fujimoto', 12, 'Defender', 75, [
                'speed' => 28, 'stamina' => 28, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koji', 'Nakamura', 12, 'Midfielder', 65, [
                'speed' => 30, 'stamina' => 26, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shun', 'Sato', 12, 'Defender', 60, [
                'speed' => 27, 'stamina' => 25, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroto', 'Watanabe', 12, 'Midfielder', 50, [
                'speed' => 29, 'stamina' => 24, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kenta', 'Kimura', 12, 'Defender', 100, [
                'speed' => 26, 'stamina' => 22, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Riku', 'Ito', 12, 'Forward', 95, [
                'speed' => 32, 'stamina' => 21, 'defense' => 15, 'attack' => 16, 'shot' => 16, 'pass' => 15, 'dribble' => 16, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuta', 'Sakai', 12, 'Midfielder', 90, [
                'speed' => 28, 'stamina' => 20, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Itsuki', 'Sugiura', 12, 'Defender', 85, [
                'speed' => 25, 'stamina' => 18, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koki', 'Yoshida', 12, 'Goalkeeper', 80, [
                'speed' => 23, 'stamina' => 19, 'defense' => 16, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 15, 'hand_save' => 17, 'punch_save' => 16
            ]],
            ['Takuya', 'Hasegawa', 12, 'Defender', 70, [
                'speed' => 24, 'stamina' => 16, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ren', 'Yamashita', 12, 'Forward', 65, [
                'speed' => 30, 'stamina' => 15, 'defense' => 15, 'attack' => 16, 'shot' => 16, 'pass' => 15, 'dribble' => 16, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Haru', 'Ogawa', 12, 'Midfielder', 60, [
                'speed' => 26, 'stamina' => 14, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function specialMoves(): array
    {
        return [
        ];
    }
}
