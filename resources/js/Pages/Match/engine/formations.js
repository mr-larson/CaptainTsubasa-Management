// resources/js/Pages/Match/engine/formations.js
//
// Nomenclature : DEF-MDF-MOF-ATT (hors GK, total = 10)
// Zones : 0=GK, 1=DEF, 2=MDF, 3=MOF, 4=ATT
// Lanes : 0=haut(15%), 1=centre-haut(38%), 2=centre-bas(62%), 3=bas(85%)
// GK    : zone 0, laneIndex null → Y=50% (toujours centré)

export const LANE_Y = { 0: 10, 1: 28, 2: 50, 3: 72, 4: 90 };
export const GK_LANE_Y = 50;

export const FORMATIONS = {

    // ================================
    //   3 DÉFENSEURS
    // ================================

    // 3-2-3-2 — formation historique
    '3-2-3-2': {
        label: '3-2-3-2',
        description: "Formation d'origine du jeu. Équilibre milieu/attaque.",
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 2    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 4    }, // DF  bas
            5:  { zone: 2, laneIndex: 1    }, // MDF centre-haut
            6:  { zone: 2, laneIndex: 3    }, // MDF centre-bas
            7:  { zone: 3, laneIndex: 0    }, // MOF haut
            8:  { zone: 3, laneIndex: 2    }, // MOF centre-haut
            9:  { zone: 3, laneIndex: 4    }, // MOF bas
            10: { zone: 4, laneIndex: 1    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 3    }, // FW  centre-bas
        },
    },

    // 3-3-2-2 — milieu dominant
    '3-3-2-2': {
        label: '3-3-2-2',
        description: '3 défenseurs, milieu défensif dominant, 2 attaquants.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 1    }, // DF  haut
            3:  { zone: 1, laneIndex: 2    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 3    }, // DF  bas
            5:  { zone: 2, laneIndex: 0    }, // MDF haut
            6:  { zone: 2, laneIndex: 2    }, // MDF centre-haut
            7:  { zone: 2, laneIndex: 4    }, // MDF bas
            8:  { zone: 3, laneIndex: 1    }, // MOF centre-haut
            9:  { zone: 3, laneIndex: 3    }, // MOF centre-bas
            10: { zone: 4, laneIndex: 1    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 3    }, // FW  centre-bas
        },
    },

    // 3-2-2-3 — très offensif
    '3-2-2-3': {
        label: '3-2-2-3',
        description: 'Très offensif. 3 attaquants, milieu léger.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 2    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 4    }, // DF  bas
            5:  { zone: 2, laneIndex: 1    }, // MDF centre-haut
            6:  { zone: 2, laneIndex: 3    }, // MDF centre-bas
            7:  { zone: 3, laneIndex: 0    }, // MOF centre-haut
            8:  { zone: 3, laneIndex: 4    }, // MOF centre-bas
            9:  { zone: 4, laneIndex: 1    }, // FW  haut
            10: { zone: 4, laneIndex: 2    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 3    }, // FW  bas
        },
    },

    // 3-4-2-1 — trident offensif + pointe
    '3-4-2-1': {
        label: '3-4-2-1',
        description: '3 défenseurs, 4 milieux défensifs, 2 ailiers, 1 buteur.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 1    }, // DF  haut
            3:  { zone: 1, laneIndex: 2    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 3    }, // DF  bas
            5:  { zone: 2, laneIndex: 0    }, // MDF haut
            6:  { zone: 2, laneIndex: 1    }, // MDF centre-haut
            7:  { zone: 2, laneIndex: 3    }, // MDF centre-bas
            8:  { zone: 2, laneIndex: 4    }, // MDF bas
            9:  { zone: 3, laneIndex: 1    }, // MOF haut (ailier)
            10: { zone: 3, laneIndex: 3    }, // MOF bas (ailier)
            11: { zone: 4, laneIndex: 2    }, // FW  unique
        },
    },

    // 3-1-3-3 — ultra offensif
    '3-1-3-3': {
        label: '3-1-3-3',
        description: 'Ultra offensif. 1 sentinelle, 3 milieux offensifs, 3 attaquants.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 2    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 4    }, // DF  bas
            5:  { zone: 2, laneIndex: 2    }, // MDF sentinelle centre-haut
            6:  { zone: 3, laneIndex: 1    }, // MOF haut
            7:  { zone: 3, laneIndex: 2    }, // MOF centre-haut
            8:  { zone: 3, laneIndex: 3    }, // MOF bas
            9:  { zone: 4, laneIndex: 0    }, // FW  haut
            10: { zone: 4, laneIndex: 2    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 4    }, // FW  bas
        },
    },

    // ================================
    //   4 DÉFENSEURS
    // ================================

    // 4-2-2-2 — classique équilibré
    '4-2-2-2': {
        label: '4-2-2-2',
        description: '4 défenseurs, double pivot, 2 milieux offensifs, 2 attaquants.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 3    }, // DF  centre-bas
            5:  { zone: 1, laneIndex: 4    }, // DF  bas
            6:  { zone: 2, laneIndex: 1    }, // MDF centre-haut
            7:  { zone: 2, laneIndex: 3    }, // MDF centre-bas
            8:  { zone: 3, laneIndex: 0    }, // MOF centre-haut
            9:  { zone: 3, laneIndex: 4    }, // MOF centre-bas
            10: { zone: 4, laneIndex: 1    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 3    }, // FW  centre-bas
        },
    },

    // 4-3-2-1 — pyramide offensive
    '4-3-2-1': {
        label: '4-3-2-1',
        description: '3 milieux défensifs, 2 ailiers offensifs, 1 avant-centre.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 3    }, // DF  centre-bas
            5:  { zone: 1, laneIndex: 4    }, // DF  bas
            6:  { zone: 2, laneIndex: 1    }, // MDF haut
            7:  { zone: 2, laneIndex: 2    }, // MDF centre-haut
            8:  { zone: 2, laneIndex: 3    }, // MDF bas
            9:  { zone: 3, laneIndex: 0    }, // MOF haut (ailier)
            10: { zone: 3, laneIndex: 4    }, // MOF bas (ailier)
            11: { zone: 4, laneIndex: 2    }, // FW  unique
        },
    },

    // 4-1-3-2 — sentinelle + trident
    '4-1-3-2': {
        label: '4-1-3-2',
        description: '1 sentinelle, trident offensif, 2 attaquants.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 3    }, // DF  centre-bas
            5:  { zone: 1, laneIndex: 4    }, // DF  bas
            6:  { zone: 2, laneIndex: 2    }, // MDF sentinelle centre-haut
            7:  { zone: 3, laneIndex: 0    }, // MOF haut
            8:  { zone: 3, laneIndex: 2    }, // MOF centre-haut (meneur)
            9:  { zone: 3, laneIndex: 4    }, // MOF bas
            10: { zone: 4, laneIndex: 1    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 3    }, // FW  centre-bas
        },
    },

    // 4-3-1-2 — meneur de jeu
    '4-3-1-2': {
        label: '4-3-1-2',
        description: '3 milieux défensifs, 1 meneur de jeu, 2 attaquants.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 3    }, // DF  centre-bas
            5:  { zone: 1, laneIndex: 4    }, // DF  bas
            6:  { zone: 2, laneIndex: 1    }, // MDF haut
            7:  { zone: 2, laneIndex: 2    }, // MDF centre-haut
            8:  { zone: 2, laneIndex: 3    }, // MDF bas
            9:  { zone: 3, laneIndex: 2    }, // Meneur centre-haut
            10: { zone: 4, laneIndex: 1    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 3    }, // FW  centre-bas
        },
    },

    // 4-2-1-3 — trident offensif
    '4-2-1-3': {
        label: '4-2-1-3',
        description: 'Double pivot, 1 meneur, 3 attaquants. Très offensif.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 3    }, // DF  centre-bas
            5:  { zone: 1, laneIndex: 4    }, // DF  bas
            6:  { zone: 2, laneIndex: 1    }, // MDF centre-haut
            7:  { zone: 2, laneIndex: 3    }, // MDF centre-bas
            8:  { zone: 3, laneIndex: 2    }, // Meneur centre-haut
            9:  { zone: 4, laneIndex: 0    }, // FW  haut
            10: { zone: 4, laneIndex: 2    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 4    }, // FW  bas
        },
    },

    // ================================
    //   5 DÉFENSEURS
    // ================================

    // 5-2-2-1 — très défensif
    '5-2-2-1': {
        label: '5-2-2-1',
        description: 'Bloc défensif compact. Contre-attaque sur 1 buteur.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 2    }, // DF  centre-bas (libéro)
            5:  { zone: 1, laneIndex: 3    }, // DF  centre-bas (stopper)
            6:  { zone: 1, laneIndex: 4    }, // DF  bas
            7:  { zone: 2, laneIndex: 1    }, // MDF centre-haut
            8:  { zone: 2, laneIndex: 3    }, // MDF centre-bas
            9:  { zone: 3, laneIndex: 0    }, // MOF centre-haut
            10: { zone: 3, laneIndex: 4    }, // MOF centre-bas
            11: { zone: 4, laneIndex: 2    }, // FW  unique
        },
    },

    // 5-3-1-1 — ultra défensif
    '5-3-1-1': {
        label: '5-3-1-1',
        description: 'Ultra défensif. Milieu dense, 1 meneur, 1 buteur.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 2    }, // DF  centre-bas
            5:  { zone: 1, laneIndex: 3    }, // DF  centre-bas (libéro)
            6:  { zone: 1, laneIndex: 4    }, // DF  bas
            7:  { zone: 2, laneIndex: 0    }, // MDF haut
            8:  { zone: 2, laneIndex: 2    }, // MDF centre-haut
            9:  { zone: 2, laneIndex: 4    }, // MDF bas
            10: { zone: 3, laneIndex: 1    }, // Meneur centre-haut
            11: { zone: 4, laneIndex: 3    }, // FW  unique
        },
    },

    // 5-2-1-2 — défensif avec pointes
    '5-2-1-2': {
        label: '5-2-1-2',
        description: '5 défenseurs, double pivot, 1 meneur, 2 attaquants.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 2    }, // DF  centre-bas
            5:  { zone: 1, laneIndex: 3    }, // DF  centre-bas (libéro)
            6:  { zone: 1, laneIndex: 4    }, // DF  bas
            7:  { zone: 2, laneIndex: 1    }, // MDF centre-haut
            8:  { zone: 2, laneIndex: 3    }, // MDF centre-bas
            9:  { zone: 3, laneIndex: 2    }, // Meneur centre-haut
            10: { zone: 4, laneIndex: 1    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 3    }, // FW  centre-bas
        },
    },

    // 5-1-2-2 — piège défensif
    '5-1-2-2': {
        label: '5-1-2-2',
        description: '5 défenseurs, 1 sentinelle, 2 milieux offensifs, 2 attaquants.',
        slots: {
            1:  { zone: 0, laneIndex: null },
            2:  { zone: 1, laneIndex: 0    }, // DF  haut
            3:  { zone: 1, laneIndex: 1    }, // DF  centre-haut
            4:  { zone: 1, laneIndex: 2    }, // DF  centre-bas
            5:  { zone: 1, laneIndex: 3    }, // DF  centre-bas (libéro)
            6:  { zone: 1, laneIndex: 4    }, // DF  bas
            7:  { zone: 2, laneIndex: 2    }, // MDF sentinelle
            8:  { zone: 3, laneIndex: 1    }, // MOF haut
            9:  { zone: 3, laneIndex: 3    }, // MOF bas
            10: { zone: 4, laneIndex: 0    }, // FW  centre-haut
            11: { zone: 4, laneIndex: 4    }, // FW  centre-bas
        },

    },
};

export const DEFAULT_FORMATION = '3-2-3-2';

export const FORMATION_LIST = Object.entries(FORMATIONS).map(([key, f]) => ({
    key,
    label:       f.label,
    description: f.description,
}));
