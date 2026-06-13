// resources/js/Pages/Match/engine/constants.js

// ==========================
//   CONSTANTES TIMING
// ==========================
export const ANIM_MS            = 250;
export const AI_THINK_MS        = 200;
export const GK_HOLD_MS         = 300;
export const ACTION_BAR_FADE_MS = 150;
export const DIE_SIDES          = 20;

// ==========================
//   RÈGLES DE PARTIE
// ==========================
export const GAME_RULES = { MAX_TURNS: 45 };

// ==========================
//   RÈGLES DE DUEL
// ==========================
export const DUEL_RULES = {
    GOOD_COUNTER_BONUS: 2,
    GENERIC_ATTACK_BONUS: 2,
    SHOT_DISTANCE_PENALTY_PER_LINE: 1,
};

// ==========================
//   COUP DE PIED ARRÊTÉ (coup franc)
// ==========================
// Une faute grave (échec critique du défenseur) commise dans le tiers offensif
// donne un coup franc-tir à l'équipe lésée, résolu via le duel tir-vs-gardien.
export const FREE_KICK = {
    MIN_ZONE_INDEX: 3, // tiers offensif (zones 0..4) ; en deçà, pas de coup franc-tir
    ATTACK_BONUS:   3, // bonus offensif sur la base du tir de coup franc
};

// ==========================
//   ENDURANCE
// ==========================
export const ENDURANCE_DEFAULT = 100;

export const STAMINA_FACTORS = {
    HIGH:      1.0,   // ≥ 75%
    MID:       0.88,  // 50-75%
    LOW:       0.72,  // 25-50%
    CRIT:      0.55,  // >0-25%
    EXHAUSTED: 0.40,  // = 0
};

export const STAMINA_COST_CROSS     = 18;
export const STAMINA_COST_LONG_PASS = 15;
export const STAMINA_COST_GLOBAL_SCALE = 1.0;
export const CRIT_STAMINA_BOOST        = 10; // points récupérés sur un 20 naturel

// ==========================
//   RÈGLES TERRAIN
// ==========================
export const FIELD_RULES = {
    GOAL_X:     { internal: 97, external: 3 },
    GOAL_Y:     50,
    GK_FRONT_X: { internal: 88, external: 12 },
};

// ==========================
//   RÈGLES IA
// ==========================
export const AI_RULES = {
    ATTACK: {
        EARLY_PASS_PROB:       0.7,
        FRONT_GK_SPECIAL_PROB: 0.15,
    },
    DEFENSE: {},
};

// ==========================
//   BONUS PAR POSTE
// ==========================
export const POSITION_BONUS = {
    GK: { gk: 0.03 },
    DF: { defend: 0.03, tackle: 0.02, block: 0.03 },
    MF: { pass: 0.03, dribble: 0.02 },
    FW: { shot: 0.04, attack: 0.03 },
};

// Le bonus de poste dépend du rôle du SLOT occupé (pas du poste naturel) :
// - poste principal  → bonus plein
// - poste secondaire → bonus réduit (× SECONDARY_POSITION_BONUS_FACTOR)
// - hors poste       → malus global (× (1 - OFF_POSITION_MALUS))
export const SECONDARY_POSITION_BONUS_FACTOR = 0.5;
export const OFF_POSITION_MALUS = 0.08;

// ==========================
//   MORAL
// ==========================
// Paliers alignés sur MoraleService::matchFactor côté serveur.
export const MORALE_FACTORS = [
    { max: 20,  factor: 0.90 }, // Révolté
    { max: 40,  factor: 0.95 }, // Mécontent
    { max: 60,  factor: 1.0  }, // Neutre
    { max: 80,  factor: 1.02 }, // Satisfait
    { max: 100, factor: 1.05 }, // Très satisfait
];
export const MORALE_DEFAULT = 60;

// Moment héroïque : un joueur à plus de 85 de moral peut relancer
// gratuitement un duel perdu (une fois par match).
export const HEROIC_MORALE_THRESHOLD = 85;
export const HEROIC_CHANCE           = 0.05;

// ==========================
//   TEXTES UI / LOGS / CARTES
// ==========================
export const TEXTS = {
    teams: { internal: "Domicile", external: "Extérieur" },
    ui: {
        gameStartMain: "Le match commence !",
        gameStartSub:  "Internal a la balle. Remise en jeu : passe obligatoire.",
        chooseAttackSub: "Choisis Pass / Dribble / Shot.",

        dribbleForbiddenMain: "Face au gardien !",
        dribbleForbiddenSub:  "Tu dois tirer, plus de dribble possible.",

        aiAttackTurn:     "Tour de l'IA (attaque)",
        aiDefenseTurn:    "Tour de l'IA (défense)",
        aiThinkingDefault:"Tour de l'IA…",

        matchEndMain:   "Fin du match !",
        matchEndPrefix: "Score final ",

        specialCooldownMain: "Special indisponible",
        specialCooldownSub:  "Attends le cooldown avant de relancer un Special.",

        keeperRestartMain: "Relance du gardien",
        keeperRestartSub:  "Passe obligatoire après une relance.",

        duelTieMain: "Duel équilibré !",
        duelTieSub:  "{team} récupère (égalité).",

        frontOfKeeperMain: "Face au gardien !",
        frontOfKeeperSub:  "Prochaine action : tir ou tir spécial.",

        shotBlockedMain:   "Tir contré !",
        shotBlockedSub:    "{team} récupère avec le n°{number}.",
        shotRecoveredMain: "Tir récupéré !",
        shotRecoveredSub:  "{team} récupère avec le n°{number}.",

        shotOnTargetMain: "Tir cadré !",
        shotOnTargetSub:  "{team} : le gardien va intervenir.",
        shotGKChoiceSub:  "{team} : Arrêt main / Dégagement poing / Special.",

        goalMain:        "BUT pour {team} !",
        goalSpecialMain: "BUT SPÉCIAL pour {team} !",
        goalSub:         "Score : {scoreInternal} - {scoreExternal}.",

        freeKickMain: "⚽ Coup franc !",
        freeKickSub:  "{team} obtient un coup franc — le n°{number} va frapper.",
    },
    logs: {
        kickoffTitle:  "Coup d'envoi",
        kickoffDetail: "Internal engage (pass obligatoire)",

        passSuccessTitle:  "Passe réussie",
        passFailTitle:     "Passe interceptée",
        passRecoveredTitle:"Passe récupérée",

        dribbleRefusedTitle:  "Dribble refusé (face au gardien)",
        dribbleRefusedDetail: "Action non autorisée",
        dribbleRecoveredTitle:"Dribble récupéré",
        dribbleSuccessTitle:  "Dribble réussi",
        dribbleFailTitle:     "Dribble stoppé",

        longShotGoalTitle:  "Tir de loin – BUT",
        longShotSavedTitle: "Tir de loin – arrêté",

        shotGoalTitle:       "Tir – BUT",
        shotSavedTitle:      "Tir – arrêté",
        shotRecoveredTitle:  "Tir récupéré",
        shotBlockedTitle:    "Tir contré",
        specialRecoveredTitle:"Special récupéré",

        matchEndTitle:      "Fin du match",
        frontOfKeeperTitle: "Dribble réussi – face au gardien",

        shotGKEqualTitle:    "Tir vs gardien — égalité",
        shotGoalSpecialTitle:"Tir spécial – BUT",
        keeperRestartMain:   "Relance du gardien",
        substitutionTitle:   "Remplacement",

        foulCardTitle:    "Carton",
        foulTitle:        "Faute",
        foulInjuryTitle:  "Blessure",
        freeKickTitle:    "Coup franc",
    },
    cards: {
        attack: {
            shot:    { icon: "⚽️", title: "Shot",    sub: "Puissant tir" },
            pass:    { icon: "➡️",  title: "Pass",    sub: "Passe avant" },
            dribble: { icon: "🌀",  title: "Dribble", sub: "Dribble un adversaire" },
            special: { icon: "🔥",  title: "Special", sub: "Action spéciale" },
            cross:     { icon: "🎯", title: "Centre",       sub: "Centre dans la surface" },
            long_pass: { icon: "🚀", title: "Passe longue", sub: "Lancement vers le milieu offensif" },
        },
        defenseField: {
            block:          { icon: "🧱", title: "Block",     sub: "Contre de tir" },
            intercept:      { icon: "✋", title: "Intercept", sub: "Couper une passe" },
            tackle:         { icon: "⚔️", title: "Tackle",    sub: "Intervenir un dribble" },
            "field-special":{ icon: "🔥", title: "Special",   sub: "Action spéciale" },
        },
        defenseGK: {
            hands:      { icon: "🧤", title: "Arrêt main",        sub: "Capte le tir proprement" },
            punch:      { icon: "👊", title: "Dégagement poing",  sub: "Repousse le ballon au loin" },
            "gk-special":{ icon: "🔥", title: "Special",          sub: "Action spéciale" },
        },
    },
};

// ==========================
//   GRILLE TERRAIN
// ==========================
export const ZONE_BOUNDS_INTERNAL = [0, 20, 40, 60, 80, 100]; // 5 zones, index 0..4
export const laneY                = [25, 50, 75];              // 3 lanes, index 0..2
export const MAX_ZONE_INDEX       = ZONE_BOUNDS_INTERNAL.length - 2; // = 4

// ==========================
//   STATS MATCH (coûts + puissances de base)
// ==========================
export const STATS = {
    attack: {
        shot:    { power: 10, cost: 10 },
        pass:    { power: 10, cost: 6 },
        dribble: { power: 10, cost: 4  },
        special: { power: 12, cost: 15 },
        cross:     { power: 10, cost: 18 },
        long_pass: { power: 10, cost: 15 },
    },
    defenseField: {
        block:           { power: 10, cost: 5  },
        intercept:       { power: 10, cost: 3  },
        tackle:          { power: 10, cost: 3  },
        heading:         { power: 10, cost: 5  },
        "field-special": { power: 12, cost: 10 },
    },
    defenseGK: {
        hands:       { power: 10, cost: 5 },
        punch:       { power: 10, cost: 3 },
        "gk-special":{ power: 12, cost: 10 },
    },
};
