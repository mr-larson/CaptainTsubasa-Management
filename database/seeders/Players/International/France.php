<?php

namespace Database\Seeders\Players\International;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de la France.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans CaptainTsubasaInternationalPlayers::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class France
{
    /**
     * Joueurs de la France, gardien(s) en premier puis défenseurs, milieux, attaquants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return [
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
        ];
    }
}
