// resources/js/Pages/Match/engine/constants.js

// ==========================
//   CONSTANTES TIMING
// ==========================
export const ANIM_MS            = 250;
export const AI_THINK_MS        = 150;
export const GK_HOLD_MS         = 300;
export const ACTION_BAR_FADE_MS = 150;
export const DIE_SIDES          = 20;

// ==========================
//   RÈGLES DE PARTIE
// ==========================
export const GAME_RULES = { MAX_TURNS: 40 };

// ==========================
//   RÈGLES DE DUEL
// ==========================
export const DUEL_RULES = {
    GOOD_COUNTER_BONUS: 2,
    GENERIC_ATTACK_BONUS: 2,
    SHOT_DISTANCE_PENALTY_PER_LINE: 1,
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

export const STAMINA_COST_GLOBAL_SCALE = 1.0;

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
    },
    cards: {
        attack: {
            shot:    { icon: "⚽️", title: "Shot",    sub: "Puissant tir" },
            pass:    { icon: "➡️",  title: "Pass",    sub: "Passe avant" },
            dribble: { icon: "🌀",  title: "Dribble", sub: "Dribble un adversaire" },
            special: { icon: "🔥",  title: "Special", sub: "Action spéciale" },
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
        shot:    { power: 10, cost: 14 },
        pass:    { power: 10, cost: 10 },
        dribble: { power: 10, cost: 8  },
        special: { power: 12, cost: 25 },
    },
    defenseField: {
        block:           { power: 10, cost: 5  },
        intercept:       { power: 10, cost: 5  },
        tackle:          { power: 10, cost: 5  },
        "field-special": { power: 12, cost: 15 },
    },
    defenseGK: {
        hands:       { power: 10, cost: 14 },
        punch:       { power: 10, cost: 10 },
        "gk-special":{ power: 12, cost: 20 },
    },
};
