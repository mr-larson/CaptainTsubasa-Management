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
    public const TRINITE_TOBAGO = 'Trinité-et-Tobago';
    public const DANEMARK = 'Danemark';
    public const PARAGUAY = 'Paraguay';
    public const REPUBLIQUE_TCHEQUE = 'République tchèque';
    public const IRLANDE = 'Irlande';
    public const ROUMANIE = 'Roumanie';
    public const UKRAINE = 'Ukraine';
    public const BULGARIE = 'Bulgarie';
    public const LIBERIA = 'Liberia';
    public const HONGRIE = 'Hongrie';
    public const POLOGNE = 'Pologne';
    public const MAROC = 'Maroc';
    public const SLOVENIE = 'Slovénie';
    public const COSTA_RICA = 'Costa Rica';
    public const COTE_IVOIRE = 'Côte d\'Ivoire';
    public const SERBIE = 'Serbie';
    public const CONGO_RD = 'RD Congo';
    public const CANADA = 'Canada';
    public const NORVEGE = 'Norvège';
    public const PEROU = 'Pérou';
    public const ALBANIE = 'Albanie';
    public const GUINEE = 'Guinée';
    public const FINLANDE = 'Finlande';
    public const PAYS_GALLES = 'Pays de Galles';
    public const YOUGOSLAVIE = 'Yougoslavie';
    public const PORTUGAL        = 'Portugal';
    public const CROATIE         = 'Croatie';
    public const BELGIQUE        = 'Belgique';
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
    public const CHINE          = 'Chine';
    public const OUZBEKISTAN    = 'Ouzbékistan';
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
        self::CHINE,
        self::OUZBEKISTAN,
        self::NIGERIA,
        self::AUSTRALIE,
        self::GHANA,
        self::THAILANDE,
        self::BELGIQUE,
        self::PORTUGAL,
        self::CROATIE,
        self::TRINITE_TOBAGO,
        self::DANEMARK,
        self::PARAGUAY,
        self::REPUBLIQUE_TCHEQUE,
        self::IRLANDE,
        self::ROUMANIE,
        self::UKRAINE,
        self::BULGARIE,
        self::LIBERIA,
        self::HONGRIE,
        self::POLOGNE,
        self::MAROC,
        self::SLOVENIE,
        self::COSTA_RICA,
        self::COTE_IVOIRE,
        self::SERBIE,
        self::CONGO_RD,
        self::CANADA,
        self::NORVEGE,
        self::PEROU,
        self::ALBANIE,
        self::GUINEE,
        self::FINLANDE,
        self::PAYS_GALLES,
        self::YOUGOSLAVIE,
    ];

    /** Drapeau emoji par nationalité (affichage). */
    public const FLAGS = [
        self::TRINITE_TOBAGO => '🇹🇹',
        self::DANEMARK => '🇩🇰',
        self::PARAGUAY => '🇵🇾',
        self::REPUBLIQUE_TCHEQUE => '🇨🇿',
        self::IRLANDE => '🇮🇪',
        self::ROUMANIE => '🇷🇴',
        self::UKRAINE => '🇺🇦',
        self::BULGARIE => '🇧🇬',
        self::LIBERIA => '🇱🇷',
        self::HONGRIE => '🇭🇺',
        self::POLOGNE => '🇵🇱',
        self::MAROC => '🇲🇦',
        self::SLOVENIE => '🇸🇮',
        self::COSTA_RICA => '🇨🇷',
        self::COTE_IVOIRE => '🇨🇮',
        self::SERBIE => '🇷🇸',
        self::CONGO_RD => '🇨🇩',
        self::CANADA => '🇨🇦',
        self::NORVEGE => '🇳🇴',
        self::PEROU => '🇵🇪',
        self::ALBANIE => '🇦🇱',
        self::GUINEE => '🇬🇳',
        self::FINLANDE => '🇫🇮',
        self::PAYS_GALLES => '🏴󠁧󠁢󠁷󠁬󠁳󠁿',
        self::YOUGOSLAVIE => '🏳️',
        self::PORTUGAL        => '🇵🇹',
        self::CROATIE         => '🇭🇷',
        self::BELGIQUE        => '🇧🇪',
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
        self::CHINE          => '🇨🇳',
        self::OUZBEKISTAN    => '🇺🇿',
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
