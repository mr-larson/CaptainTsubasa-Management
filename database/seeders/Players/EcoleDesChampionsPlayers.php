<?php

namespace Database\Seeders\Players;

/**
 * Données joueurs (et special moves) — groupe : EcoleDesChampionsPlayers.
 * Extrait de PlayerSeeder pour alléger et organiser par source.
 */
class EcoleDesChampionsPlayers
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            // L'École des Champions – Not contract

// Columbus / Gênes / Ailes de Jupiter
            [
                'Benjamin', 'Lefranc', 13, 'Forward', 420,
                [
                    'speed' => 68, 'stamina' => 74, 'defense' => 20, 'attack' => 36,
                    'shot' => 28, 'pass' => 26, 'dribble' => 27,
                    'block' => 17, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine charismatique des Ailes de Jupiter. Benjamin se distingue par sa détermination, sa vision du jeu et sa capacité à élever son niveau dans les grands moments.',
                ['Midfielder']
            ],
            [
                'Eric', 'Townsend', 13, 'Forward', 390,
                [
                    'speed' => 72, 'stamina' => 70, 'defense' => 19, 'attack' => 33,
                    'shot' => 26, 'pass' => 24, 'dribble' => 28,
                    'block' => 16, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de Columbus devenu lieutenant fidèle de Benjamin. Il se dépasse régulièrement face aux frappes puissantes de Cesare, payant de sa personne pour sauver son équipe.',
                ['Midfielder']
            ],
            [
                'Lucas', 'Rondi', 13, 'Defender', 385,
                [
                    'speed' => 70, 'stamina' => 68, 'defense' => 26, 'attack' => 22,
                    'shot' => 17, 'pass' => 20, 'dribble' => 28,
                    'block' => 24, 'intercept' => 22, 'tackle' => 23,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Libéro acrobate, fils d\'une famille de circassiens. Dribbleur hors pair, il se distingue par ses acrobaties spectaculaires et rivalise d\'inventivité avec Papan.'
            ],
            [
                'Cesare', 'Gatti', 13, 'Forward', 440,
                [
                    'speed' => 70, 'stamina' => 76, 'defense' => 22, 'attack' => 38,
                    'shot' => 30, 'pass' => 22, 'dribble' => 26,
                    'block' => 17, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant puissant au sang chaud, rival et partenaire de Benjamin. Son tir dévastateur est l\'un des plus redoutables de sa génération.'
            ],
            [
                'Roberto', 'Concini', 13, 'Midfielder', 350,
                [
                    'speed' => 62, 'stamina' => 68, 'defense' => 23, 'attack' => 28,
                    'shot' => 21, 'pass' => 26, 'dribble' => 23,
                    'block' => 19, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu de Columbus devenu un joueur fiable au contact de Benjamin. Sympathique et impliqué, il soutient activement le jeu collectif.'
            ],
            [
                'Macaroni', 'Giotti', 13, 'Defender', 345,
                [
                    'speed' => 58, 'stamina' => 70, 'defense' => 28, 'attack' => 18,
                    'shot' => 14, 'pass' => 17, 'dribble' => 16,
                    'block' => 26, 'intercept' => 22, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseur de Columbus qui a progressé grâce à Benjamin. Ses efforts ont été récompensés par une sélection à Gênes. Il parvient même à repousser les tirs de l\'aigle en finale.'
            ],
            [
                'Giorgio', 'Fornari', 13, 'Goalkeeper', 280,
                [
                    'speed' => 50, 'stamina' => 62, 'defense' => 22, 'attack' => 12,
                    'shot' => 10, 'pass' => 14, 'dribble' => 12,
                    'block' => 20, 'intercept' => 18, 'tackle' => 16,
                    'hand_save' => 24, 'punch_save' => 22,
                ],
                'Gardien de Columbus, peu sûr de lui mais qui progresse grâce aux conseils de Benjamin. Il apprend à surmonter sa peur face aux tirs puissants de Cesare.'
            ],

// San Podesta Jr
            [
                'Mario', 'Santis', 13, 'Midfielder', 395,
                [
                    'speed' => 64, 'stamina' => 72, 'defense' => 26, 'attack' => 30,
                    'shot' => 22, 'pass' => 28, 'dribble' => 24,
                    'block' => 20, 'intercept' => 24, 'tackle' => 22,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Meneur de jeu de San Podesta Jr, lieutenant de Julian. Son intelligence tactique hors norme lui permet d\'anticiper les plans adverses et de lancer ses partenaires dans les meilleures conditions.'
            ],
            [
                'Renato', 'Salgari', 13, 'Forward', 400,
                [
                    'speed' => 70, 'stamina' => 64, 'defense' => 18, 'attack' => 34,
                    'shot' => 27, 'pass' => 23, 'dribble' => 26,
                    'block' => 16, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant incroyablement doué capable de reproduire les tirs de Julian et Hikaru même en déséquilibre. Manque encore d\'expérience et se fatigue vite dans les grands matchs.'
            ],
            [
                'Renzo', 'Rotta', 13, 'Forward', 370,
                [
                    'speed' => 66, 'stamina' => 68, 'defense' => 18, 'attack' => 31,
                    'shot' => 25, 'pass' => 22, 'dribble' => 23,
                    'block' => 16, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant de San Podesta Jr, remplaçant de Julian. Sélectionné à Gênes, il marque plusieurs buts précieux avant de s\'imposer comme titulaire à son retour.'
            ],
            [
                'Coloni', 'Bottini', 13, 'Goalkeeper', 320,
                [
                    'speed' => 52, 'stamina' => 66, 'defense' => 24, 'attack' => 12,
                    'shot' => 10, 'pass' => 15, 'dribble' => 12,
                    'block' => 22, 'intercept' => 20, 'tackle' => 17,
                    'hand_save' => 27, 'punch_save' => 25,
                ],
                'Gardien de San Podesta Jr, fiable et régulier dans ses interventions. Il soutient solidement son équipe lors des grands matchs.'
            ],
            [
                'Bruno', 'Moricone', 13, 'Midfielder', 360,
                [
                    'speed' => 66, 'stamina' => 68, 'defense' => 24, 'attack' => 30,
                    'shot' => 23, 'pass' => 26, 'dribble' => 25,
                    'block' => 19, 'intercept' => 22, 'tackle' => 21,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Joueur vedette de Margherita, individualiste au départ. Il adopte l\'esprit d\'équipe après le premier match de Gênes et devient un atout collectif précieux.'
            ],
            [
                'Nino', 'Biancchi', 13, 'Forward', 355,
                [
                    'speed' => 64, 'stamina' => 68, 'defense' => 18, 'attack' => 30,
                    'shot' => 26, 'pass' => 24, 'dribble' => 22,
                    'block' => 16, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Jumeau attaquant de Corvette. Grand et doté d\'une excellente détente, il excelle dans le jeu de tête et les une-deux foudroyants avec son frère Riki.',
                ['Midfielder']
            ],
            [
                'Riki', 'Biancchi', 13, 'Forward', 355,
                [
                    'speed' => 64, 'stamina' => 68, 'defense' => 18, 'attack' => 30,
                    'shot' => 26, 'pass' => 24, 'dribble' => 22,
                    'block' => 16, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Jumeau attaquant de Corvette. En duo avec Nino, leur synchronisation parfaite et leur jeu aérien font d\'eux une menace constante sur les phases arrêtées.',
                ['Midfielder']
            ],
            [
                'Woltz', 'Hoffmann', 13, 'Goalkeeper', 420,
                [
                    'speed' => 62, 'stamina' => 74, 'defense' => 30, 'attack' => 14,
                    'shot' => 12, 'pass' => 16, 'dribble' => 14,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 24, 'punch_save' => 25,
                ],
                'Gardien allemand grand et massif mais très agile. Il anticipe la direction des tirs en observant les pieds des tireurs, ce qui lui permet des réactions fulgurantes.'
            ],
            [
                'Papan', 'Correia Da Silva', 13, 'Midfielder', 430,
                [
                    'speed' => 74, 'stamina' => 62, 'defense' => 20, 'attack' => 36,
                    'shot' => 26, 'pass' => 28, 'dribble' => 32,
                    'block' => 17, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu acrobatique au talent immense mais à la discipline aléatoire. Renvoyé de 14 équipes, il rejoint les Ailes de Jupiter et se lie d\'amitié avec Lucas après leur rivalité acrobatique.'
            ],
            [
                'Ash', 'Rodrigues', 13, 'Defender', 375,
                [
                    'speed' => 68, 'stamina' => 70, 'defense' => 28, 'attack' => 20,
                    'shot' => 16, 'pass' => 19, 'dribble' => 18,
                    'block' => 26, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Libéro brésilien évoluant en Allemagne. Il souffre d\'un blocage psychologique suite à un accident mais surmonte sa peur grâce à Yann, devenant un défenseur solide des Ailes de Jupiter.'
            ],
            [
                'Yann', 'Haarden', 13, 'Forward', 420,
                [
                    'speed' => 70, 'stamina' => 76, 'defense' => 24, 'attack' => 36,
                    'shot' => 28, 'pass' => 22, 'dribble' => 26,
                    'block' => 19, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant très physique des Pays-Bas. Stratège redoutable, il utilise Cesare à son insu pour faire le ménage dans les défenses.',
                ['Midfielder']
            ],
            [
                'Marcel', 'Beauregard', 13, 'Midfielder', 395,
                [
                    'speed' => 65, 'stamina' => 72, 'defense' => 24, 'attack' => 31,
                    'shot' => 22, 'pass' => 30, 'dribble' => 25,
                    'block' => 19, 'intercept' => 23, 'tackle' => 21,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de l\'équipe de France, réputé pour la précision millimétrée de ses passes. Il rejoint les Ailes de Jupiter après avoir surpassé Eric, qui l\'avait humilié par le passé.'
            ],

// Rome
            [
                'Nero', 'Martella', 13, 'Forward', 370,
                [
                    'speed' => 66, 'stamina' => 68, 'defense' => 19, 'attack' => 31,
                    'shot' => 24, 'pass' => 26, 'dribble' => 23,
                    'block' => 16, 'intercept' => 18, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de Rome, attaquant jouant collectivement. Avec Erio et Bento, il pratique un jeu de passes très efficace qui déstabilise les défenses adverses.'
            ],
            [
                'Erio', 'Caruso', 13, 'Forward', 355,
                [
                    'speed' => 64, 'stamina' => 66, 'defense' => 18, 'attack' => 29,
                    'shot' => 22, 'pass' => 25, 'dribble' => 22,
                    'block' => 15, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant de Rome faisant partie du trio offensif avec Nero et Bento. Leur jeu collectif et leurs passes rapides sont leur principale force.'
            ],
            [
                'Bento', 'Capone', 13, 'Forward', 355,
                [
                    'speed' => 64, 'stamina' => 66, 'defense' => 18, 'attack' => 29,
                    'shot' => 22, 'pass' => 25, 'dribble' => 22,
                    'block' => 15, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant de Rome, troisième élément du trio offensif avec Nero et Erio. Ensemble ils pratiquent un football collectif basé sur des échanges rapides et précis.'
            ],

// Falcon Jr
            [
                'Carlos', 'Oliveira Tavares', 13, 'Forward', 395,
                [
                    'speed' => 68, 'stamina' => 72, 'defense' => 18, 'attack' => 34,
                    'shot' => 28, 'pass' => 20, 'dribble' => 24,
                    'block' => 16, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de Falcon Jr. Attaquant brésilien spécialiste des tirs à effet, redouté dans les duels offensifs et les situations de coup de pied arrêté.'
            ],
            [
                'Giovanni', 'Barbossa-Perreira', 13, 'Forward', 360,
                [
                    'speed' => 66, 'stamina' => 68, 'defense' => 17, 'attack' => 30,
                    'shot' => 24, 'pass' => 22, 'dribble' => 22,
                    'block' => 15, 'intercept' => 16, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Lieutenant de Carlos au sein de Falcon Jr. Attaquant combatif et fidèle second de son capitaine.'
            ],

            [
                'Anna', 'Liegi', 13, 'Defender', 24,
                [
                    'speed' => 26, 'stamina' => 32, 'defense' => 28, 'attack' => 20,
                    'shot' => 16, 'pass' => 23, 'dribble' => 18,
                    'block' => 20, 'intercept' => 22, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseuse de L\'École des Champions au profil encore modeste, mais déjà précieuse grâce à son tacle franc et à sa qualité de passe pour relancer le jeu.'
            ],
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function specialMoves(): array
    {
        return [
            // INTERNATIONAUX — L’ÉCOLE DES CHAMPIONS
            // =======================

            'benjamin-lefranc' => [[
                'key'         => 'lefranc_captain_shot',
                'mode'        => 'attack',
                'label'       => 'Frappe du capitaine',
                'short_label' => 'Capitaine',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Capitaine charismatique, il élève son niveau dans les grands moments par une frappe décisive.',
            ]],

            'eric-townsend' => [[
                'key'         => 'townsend_winger_dash',
                'mode'        => 'attack',
                'label'       => 'Débordement',
                'short_label' => 'Débordement',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Ailier travailleur qui se dépasse et déborde son vis-à-vis par la vitesse.',
            ]],

            'lucas-rondi' => [[
                'key'         => 'rondi_acrobatic_dribble',
                'mode'        => 'attack',
                'label'       => 'Dribble acrobatique',
                'short_label' => 'Acrobate',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Libéro acrobate aux dribbles spectaculaires hérités de sa famille de circassiens.',
            ]],

            'cesare-gatti' => [[
                'key'         => 'gatti_thunder_shot',
                'mode'        => 'attack',
                'label'       => 'Tir tonnerre',
                'short_label' => 'Tonnerre',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Attaquant au sang chaud dont la frappe dévastatrice est l’une des plus redoutées.',
            ]],

            'macaroni-giotti' => [[
                'key'         => 'giotti_relentless_block',
                'mode'        => 'defense',
                'label'       => 'Blocage acharné',
                'short_label' => 'Acharné',
                'cooldown'    => 2,
                'base_action' => 'block',
                'description' => 'Défenseur tenace, capable de repousser même les tirs les plus puissants.',
            ]],

            'mario-santis' => [[
                'key'         => 'santis_tactical_pass',
                'mode'        => 'attack',
                'label'       => 'Passe du stratège',
                'short_label' => 'Stratège',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Intelligence tactique hors norme : il anticipe les plans adverses et lance idéalement ses partenaires.',
            ]],

            'renato-salgari' => [[
                'key'         => 'salgari_copy_shot',
                'mode'        => 'attack',
                'label'       => 'Tir mimétique',
                'short_label' => 'Mimétique',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Incroyablement doué, il reproduit les tirs les plus puissants même en déséquilibre.',
            ]],

            'bruno-moricone' => [[
                'key'         => 'moricone_solo_run',
                'mode'        => 'attack',
                'label'       => 'Percée individuelle',
                'short_label' => 'Solo',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Joueur vedette et technique, capable d’une percée tranchante balle au pied.',
            ]],

            'alfredo-pettri' => [[
                'key'         => 'pettri_limpet_marking',
                'mode'        => 'defense',
                'label'       => 'Marquage sangsue',
                'short_label' => 'Sangsue',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Petit défenseur d’une ténacité acharnée, capable de museler les meilleurs joueurs.',
            ]],

            'nino-biancchi' => [[
                'key'         => 'biancchi_twin_header',
                'mode'        => 'attack',
                'label'       => 'Tête des jumeaux',
                'short_label' => 'Jumeaux',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Grande détente et une-deux foudroyant avec son frère pour conclure de la tête.',
            ]],

            'riki-biancchi' => [[
                'key'         => 'biancchi_twin_combo',
                'mode'        => 'attack',
                'label'       => 'Combo des jumeaux',
                'short_label' => 'Combo',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Synchronisation parfaite avec Nino, redoutable sur les phases arrêtées aériennes.',
            ]],

            // =======================
            // INTERNATIONAUX — NAPLES
            // =======================

            'woltz-hoffmann' => [[
                'key'         => 'hoffmann_anticipation_save',
                'mode'        => 'defense',
                'label'       => 'Arrêt d’anticipation',
                'short_label' => 'Anticipation',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Gardien massif mais agile, il anticipe la trajectoire en observant les pieds du tireur.',
            ]],

            'ricardo-costello' => [[
                'key'         => 'costello_hard_press',
                'mode'        => 'defense',
                'label'       => 'Pressing rugueux',
                'short_label' => 'Pressing',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Joueur au sang chaud, il harcèle physiquement le porteur jusqu’à lui prendre le ballon.',
            ]],

            // =======================
            // INTERNATIONAUX — AILES DE JUPITER
            // =======================

            'papan-correia-da-silva' => [[
                'key'         => 'papan_acrobatic_play',
                'mode'        => 'attack',
                'label'       => 'Jeu acrobatique',
                'short_label' => 'Acrobate',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Milieu au talent immense et aux dribbles acrobatiques totalement imprévisibles.',
            ]],

            'ash-rodrigues' => [[
                'key'         => 'ash_libero_cover',
                'mode'        => 'defense',
                'label'       => 'Couverture du libéro',
                'short_label' => 'Libéro',
                'cooldown'    => 2,
                'base_action' => 'intercept',
                'description' => 'Libéro brésilien qui lit le jeu et coupe les trajectoires de passe adverses.',
            ]],

            'yann-haarden' => [[
                'key'         => 'haarden_power_forward',
                'mode'        => 'attack',
                'label'       => 'Buteur de puissance',
                'short_label' => 'Puissance',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Attaquant très physique et stratège, il force la finition au cœur de la surface.',
            ]],

            'peter-shilton' => [[
                'key'         => 'shilton_iron_wall',
                'mode'        => 'defense',
                'label'       => 'Muraille anglaise',
                'short_label' => 'Muraille',
                'cooldown'    => 2,
                'base_action' => 'block',
                'description' => 'Défenseur imposant et autoritaire, capitaine de l’Angleterre, infranchissable dans l’axe.',
            ]],

            'marcel-beauregard' => [[
                'key'         => 'beauregard_precision_pass',
                'mode'        => 'attack',
                'label'       => 'Passe millimétrée',
                'short_label' => 'Précision',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Capitaine de la France réputé pour la précision millimétrée de ses passes.',
            ]],

            // =======================
            // INTERNATIONAUX — ROME
            // =======================

            'nero-martella' => [[
                'key'         => 'martella_one_touch_combo',
                'mode'        => 'attack',
                'label'       => 'Jeu en une touche',
                'short_label' => 'Une-touche',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Avec Erio et Bento, il déstabilise les défenses par un jeu de passes rapides et précises.',
            ]],

            // =======================
            // INTERNATIONAUX — FALCON JR
            // =======================

            'carlos-oliveira-tavares' => [[
                'key'         => 'carlos_curve_shot',
                'mode'        => 'attack',
                'label'       => 'Tir brossé',
                'short_label' => 'Brossé',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Attaquant brésilien spécialiste des tirs à effet, redoutable sur coups de pied arrêtés.',
            ]],
        ];
    }

    /**
     * Exceptions de nationalité (slug "prenom-nom" → pays). Le reste de
     * l'effectif est italien par défaut (cf. PlayerSeeder).
     * @return array<string, string>
     */
    public static function nationalities(): array
    {
        return [
            'benjamin-lefranc'           => 'France',
            'marcel-beauregard'          => 'France',
            'eric-townsend'              => 'Angleterre',
            'woltz-hoffmann'             => 'Allemagne',
            'yann-haarden'               => 'Pays-Bas',
            'papan-correia-da-silva'     => 'Brésil',
            'ash-rodrigues'              => 'Brésil',
            'bento-capone'               => 'Brésil',
            'carlos-oliveira-tavares'    => 'Brésil',
            'giovanni-barbossa-perreira' => 'Brésil',
        ];
    }
}
