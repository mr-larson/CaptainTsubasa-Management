<?php

namespace Database\Seeders\Support;

/** Notes de jeu aérien (heading) fixées à la main pour des joueurs connus. */
final class HeadingOverrides
{
    /**
     * Overrides manuels de la stat heading pour des joueurs connus
     * (slug exact "prenom-nom"), au-delà du calcul automatique.
     *
     * On utilise une correspondance EXACTE (et non str_contains) pour éviter
     * qu'une clé courte ne touche plusieurs joueurs par accident (ex. « soda »
     * matchait Makoto ET Kazuaki Soda). Notes spécifiques au jeu aérien du joueur.
     */
    public static function for(string $slug): ?int
    {
        $overrides = [
            // --- Élite du jeu aérien (29-30) ---
            'masao-tachibana'      => 30, // frères Tachibana : tête acrobatique légendaire
            'kazuo-tachibana'      => 30,
            'hiroshi-jito'         => 30, // défenseur, spécialiste des duels aériens
            'makoto-soda'          => 29, // Musashi, mur défensif dominant dans les airs

            // --- Très forts de la tête (25-28) ---
            'kojiro-hyuga'         => 28, // avant-centre puissant et impitoyable
            'hikaru-matsuyama'     => 26, // capitaine de Furano, fort sur les centres
            'kazuaki-soda'         => 25, // défenseur robuste

            // --- Forts / au-dessus de la moyenne (22-24) ---
            'karl-heinz-schneider' => 24, // « Kaiser » allemand, complet et physique
            'hermann-kaltz'        => 24, // arrière allemand, jeu aérien solide
            'juan-diaz'            => 24, // buteur argentin puissant
            'louis-napoleon'       => 23, // libéro français athlétique
            'shingo-takasugi'      => 22, // défenseur solide dans les duels
            'carlos-santana'       => 22, // crack brésilien, complet
            'salvatore-gentile'    => 22, // défenseur italien rugueux
            'shun-nitta'           => 22, // attaquant explosif, bon dans la surface

            // --- Moyens (18-20) ---
            'el-sid-pierre'        => 20, // meneur français athlétique
            'gino-hernandez'       => 20, // ailier physique
            'ryo-ishizaki'         => 20, // défenseur travailleur, généreux dans les duels
            'jun-misugi'           => 19, // « prince du terrain », élégant mais technique
            'teppei-kisugi'        => 18, // attaquant complet de Nankatsu

            // --- Faibles : techniciens / petits gabarits / meneurs au sol (11-14) ---
            'taro-misaki'          => 14, // « magicien du terrain », jeu au sol
            'takeshi-sawada'       => 12, // jeune surdoué technique, petit gabarit
            'tsubasa-ozora'        => 11, // génie technique au sol, faible de la tête
        ];

        return $overrides[$slug] ?? null;
    }
}
