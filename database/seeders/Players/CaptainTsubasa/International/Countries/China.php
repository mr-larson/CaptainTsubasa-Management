<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/** Personnages du World Youth ; statistiques adaptées au niveau du catalogue. */
final class China
{
    public static function players(): array
    {
        return [
            ['Junguang', 'Xiao', 13, 'Midfielder', 0,
                ['speed' => 75, 'stamina' => 83, 'attack' => 35, 'defense' => 35, 'shot' => 28, 'pass' => 28, 'dribble' => 29, 'block' => 33, 'intercept' => 31, 'tackle' => 30, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 26],
                'Xiao Junguang (Sho Shunko), milieu défensif du World Youth. Lecture des trajectoires et renvois de balle exceptionnels ; profil de référence défensive, sans atteindre la finition des meilleurs avant-centres.', ['Defender']],
            ['Zhongming', 'Wang', 13, 'Forward', 0,
                ['speed' => 74, 'stamina' => 76, 'attack' => 30, 'defense' => 19, 'shot' => 21, 'pass' => 28, 'dribble' => 27, 'block' => 16, 'intercept' => 19, 'tackle' => 18, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 23, 'cross' => 29],
                'Wang Zhongming, partenaire de Fei Xiang. Attaquant de couloir précis dans les centres et les passes, utile à la création plutôt qu’à la finition.', ['Midfielder']],
            ['Xiang', 'Fei', 13, 'Forward', 0,
                ['speed' => 60, 'stamina' => 75, 'attack' => 34, 'defense' => 20, 'shot' => 24, 'pass' => 21, 'dribble' => 21, 'block' => 20, 'intercept' => 18, 'tackle' => 19, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 33],
                'Fei Xiang, l’Oiseau mystérieux du World Youth chinois. Spécialiste des têtes et des ballons aériens servis par Wang, moins agile dans le jeu au sol.', []],
        ];
    }
}
