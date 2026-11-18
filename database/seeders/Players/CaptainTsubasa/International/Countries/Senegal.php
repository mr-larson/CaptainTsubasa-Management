<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/** Personnages de Rise of New Champions, confirmés par Bandai Namco dans World Fighters. */
final class Senegal
{
    public static function players(): array
    {
        return [
            ['Ismail', 'Senghor', 13, 'Midfielder', 0,
                ['speed' => 67, 'stamina' => 77, 'attack' => 32, 'defense' => 24,
                    'shot' => 25, 'pass' => 32, 'dribble' => 30, 'block' => 19,
                    'intercept' => 24, 'tackle' => 22, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 20],
                'Capitaine et meneur de jeu du Sénégal, introduit dans Rise of New Champions et présent dans World Fighters. Technicien formé dans un club français, il organise le jeu pour mettre ses partenaires en valeur.', []],
            ['Moussa Malick', 'Diallo', 13, 'Forward', 0,
                ['speed' => 83, 'stamina' => 78, 'attack' => 33, 'defense' => 19,
                    'shot' => 28, 'pass' => 21, 'dribble' => 25, 'block' => 17,
                    'intercept' => 18, 'tackle' => 19, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 27],
                'Attaquant sénégalais de Rise of New Champions, également présent dans World Fighters. Rapide, puissant et passionné, il cherche sans cesse le but et profite des passes de Senghor.', []],
        ];
    }
}
