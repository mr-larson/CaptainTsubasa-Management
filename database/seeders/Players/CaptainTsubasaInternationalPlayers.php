<?php

namespace Database\Seeders\Players;

use Database\Seeders\Players\International\Germany;
use Database\Seeders\Players\International\France;
use Database\Seeders\Players\International\Brazil;
use Database\Seeders\Players\International\Argentina;
use Database\Seeders\Players\International\Uruguay;
use Database\Seeders\Players\International\Netherlands;
use Database\Seeders\Players\International\Mexico;
use Database\Seeders\Players\International\Italy;

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
    /** Sélections représentées, dans l'ordre qui fixe les ids en base. */
    private const NATIONS = [
        Germany::class,
        France::class,
        Brazil::class,
        Argentina::class,
        Uruguay::class,
        Netherlands::class,
        Mexico::class,
        Italy::class,
    ];

    /**
     * Toutes les stars internationales : une sélection après l'autre.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return array_merge(...array_map(fn (string $nation) => $nation::players(), self::NATIONS));
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
            'giorgi'                => 'Brésil',
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
            'alan-pascal'       => 'Argentine',
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
            'ruud-klisman'         => 'Pays-Bas',
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
