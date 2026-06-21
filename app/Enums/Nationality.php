<?php

namespace App\Enums;

/**
 * Source de vérité des nationalités sélectionnables (mode Coupe du Monde).
 *
 * Les valeurs SONT les chaînes stockées en base (`players.nationality`,
 * `game_players.nationality`) — en français, pour rester cohérent dans tout
 * le projet. Le PlayerSeeder valide chaque nationalité contre self::ALL :
 * une valeur inconnue (faute de frappe, langue mélangée) fait échouer le seed.
 *
 * Ajouter un futur pays = une seule ligne ici (constante + ALL + FLAGS).
 * Pré-taguer des joueurs d'un pays encore peu fourni est sans risque : la
 * nation reste "dormante" tant qu'elle n'a pas assez de joueurs pour aligner
 * un onze (l'assembleur de sélections ne la propose pas).
 */
class Nationality
{
    public const JAPON           = 'Japon';
    public const ALLEMAGNE       = 'Allemagne';
    public const FRANCE          = 'France';
    public const BRESIL          = 'Brésil';
    public const ARGENTINE       = 'Argentine';
    public const ITALIE          = 'Italie';
    public const PAYS_BAS        = 'Pays-Bas';
    public const URUGUAY         = 'Uruguay';
    public const ANGLETERRE      = 'Angleterre';
    public const SUEDE           = 'Suède';
    public const ESPAGNE         = 'Espagne';
    public const MEXIQUE         = 'Mexique';
    public const ETATS_UNIS      = 'États-Unis';
    public const ARABIE_SAOUDITE = 'Arabie saoudite';
    public const COREE_DU_SUD    = 'Corée du Sud';
    public const NIGERIA         = 'Nigéria';
    public const AUSTRALIE       = 'Australie';
    public const GHANA           = 'Ghana';
    public const THAILANDE       = 'Thaïlande';

    /** Toutes les nationalités reconnues. */
    public const ALL = [
        self::JAPON,
        self::ALLEMAGNE,
        self::FRANCE,
        self::BRESIL,
        self::ARGENTINE,
        self::ITALIE,
        self::PAYS_BAS,
        self::URUGUAY,
        self::ANGLETERRE,
        self::SUEDE,
        self::ESPAGNE,
        self::MEXIQUE,
        self::ETATS_UNIS,
        self::ARABIE_SAOUDITE,
        self::COREE_DU_SUD,
        self::NIGERIA,
        self::AUSTRALIE,
        self::GHANA,
        self::THAILANDE,
    ];

    /** Drapeau emoji par nationalité (affichage). */
    public const FLAGS = [
        self::JAPON           => '🇯🇵',
        self::ALLEMAGNE       => '🇩🇪',
        self::FRANCE          => '🇫🇷',
        self::BRESIL          => '🇧🇷',
        self::ARGENTINE       => '🇦🇷',
        self::ITALIE          => '🇮🇹',
        self::PAYS_BAS        => '🇳🇱',
        self::URUGUAY         => '🇺🇾',
        self::ANGLETERRE      => '🏴󠁧󠁢󠁥󠁮󠁧󠁿',
        self::SUEDE           => '🇸🇪',
        self::ESPAGNE         => '🇪🇸',
        self::MEXIQUE         => '🇲🇽',
        self::ETATS_UNIS      => '🇺🇸',
        self::ARABIE_SAOUDITE => '🇸🇦',
        self::COREE_DU_SUD    => '🇰🇷',
        self::NIGERIA         => '🇳🇬',
        self::AUSTRALIE       => '🇦🇺',
        self::GHANA           => '🇬🇭',
        self::THAILANDE       => '🇹🇭',
    ];

    /** La nationalité fait-elle partie des valeurs reconnues ? */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::ALL, true);
    }

    /** Drapeau d'une nationalité (drapeau blanc par défaut si inconnue/null). */
    public static function flag(?string $value): string
    {
        return self::FLAGS[$value] ?? '🏳️';
    }
}
