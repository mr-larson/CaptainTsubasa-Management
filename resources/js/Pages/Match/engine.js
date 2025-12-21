// resources/js/Pages/Match/engine.js

// ==========================
//   CONSTANTES GÉNÉRALES
// ==========================

const ANIM_MS     = 700; // ralenti pour suivre les transitions CSS
const AI_THINK_MS = 400; // délai "réflexion IA"
const DIE_SIDES   = 20;  // nombre de faces du dé utilisé dans les calculs de duel

// ==========================
//   RÈGLES & CONFIG GLOBALES
// ==========================

const GAME_RULES = {
    MAX_TURNS: 40,
};

// Bonus / malus des duels
const DUEL_RULES = {
    GOOD_COUNTER_BONUS: 2,               // bonus pour la "bonne" défense (intercept, tackle, block...)
    GENERIC_ATTACK_BONUS: 2,             // petit bonus pour l'attaque si la défense n'est pas optimale
    SHOT_DISTANCE_PENALTY_PER_LINE: 1,   // malus par ligne de distance sur les tirs de loin
};

// Seuils d'endurance (affichage & calculs)
const STAMINA_THRESHOLDS = {
    HIGH: 75,
    MID:  50,
    LOW:  25,
};

const STAMINA_FACTORS = {
    HIGH:      1.0,
    MID:       0.9,
    LOW:       0.8,
    CRIT:      0.7,
    EXHAUSTED: 0.6,
};

// Règles de placement terrain (but / face au gardien)
const FIELD_RULES = {
    GOAL_X:     { internal: 97, external: 3 },
    GOAL_Y:     50,
    GK_FRONT_X: { internal: 88, external: 12 },
};

// Règles IA (probabilités de choix)
const AI_RULES = {
    ATTACK: {
        EARLY_PASS_PROB:   0.7,   // zones 0–1 : prob de passe
        MID_DRIBBLE_PROB:  0.4,   // zone 2 : proba dribble
        MID_PASS_PROB:     0.8,   // zone 2 : si > DRIBBLE, on passe, sinon tir
        LATE_SHOT_PROB:    0.6,   // zone 3 : proba tir
        LATE_DRIBBLE_PROB: 0.85,  // zone 3 : si > SHOT, dribble, sinon passe
        FRONT_GK_SPECIAL_PROB: 0.15, // ✅ FIX: utilisé dans computeAIAttackChoice()
    },
    DEFENSE: {
        MAIN_CHOICE_PROB:   0.6,  // prob de prendre le 1er choix
        SECOND_CHOICE_PROB: 0.85, // prob de prendre le 2e choix
    },
};

// ==========================
//   TEXTES CENTRALISÉS
// ==========================

const TEXTS = {
    teams: {
        internal: "Domicile",
        external: "Exterieur",
    },

    ui: {
        gameStartMain: "Le match commence !",
        gameStartSub:  "Internal a la balle. Remise en jeu : passe obligatoire.",
        chooseAttackSub: "Choisis Pass / Dribble / Shot.",
        dribbleForbiddenMain: "Face au gardien !",
        dribbleForbiddenSub:  "Tu dois tirer, plus de dribble possible.",
        aiAttackTurn:  "Tour de l'IA (attaque)",
        aiDefenseTurn: "Tour de l'IA (défense)",
        aiThinkingDefault: "Tour de l'IA…",
        matchEndMain: "Fin du match !",
        matchEndPrefix: "Score final ",
    },

    logs: {
        kickoffTitle:  "Coup d'envoi",
        kickoffDetail: "Internal engage (pass obligatoire)",

        kickoffPassSuccessTitle: "Coup d'envoi – passe courte",
        kickoffPassFailTitle:    "Remise en jeu ratée",

        passSuccessTitle: "Passe réussie",
        passFailTitle:    "Passe interceptée",

        dribbleRefusedTitle:      "Dribble refusé (face au gardien)",
        dribbleRefusedDetail:     "Action non autorisée",
        dribbleSuccessTitle:      "Dribble réussi",
        dribbleSuccessGKTitle:    "Dribble réussi – face au gardien",
        dribbleFailTitle:         "Dribble raté",

        shotGoalTitle:            "Tir – BUT",
        shotGoalSpecialTitle:     "Tir spécial – BUT",
        shotSavedTitle:           "Tir – arrêté",
        shotSavedSpecialTitle:    "Tir spécial – arrêté",
        shotBlockedTitle:         "Tir contré par la défense",
        shotThroughDefenseTitle:  "Tir cadré – passe la défense",

        longShotGoalTitle:        "Tir de loin – BUT",
        longShotGoalSpecialTitle: "Tir spécial de loin – BUT",
        longShotSavedTitle:       "Tir de loin – arrêté par le gardien",

        longShotKeeperSaveTitle:  "Arrêt du gardien !",
        matchEndTitle:            "Fin du match",
    },

    cards: {
        attack: {
            shot:    { icon: "⚽️", title: "Shot", sub: "Puissant tir" },
            pass:    { icon: "➡️", title: "Pass", sub: "Passe avant" },
            dribble: { icon: "🌀", title: "Dribble", sub: "Dribble un adversaire" },
            special: { icon: "🔥", title: "Special", sub: "Action spéciale" },
        },
        defenseField: {
            block: {
                icon: "🧱", title: "Block", sub: "Contre de tir",
            },
            intercept: {
                icon: "✋", title: "Intercept", sub: "Couper une passe",
            },
            tackle: {
                icon: "⚔️", title: "Tackle", sub: "Intervenir un dribble",
            },
            "field-special": {
                icon: "🔥", title: "Special", sub: "Action spéciale",
            },
        },
        defenseGK: {
            hands: {
                icon: "🧤", title: "Arrêt main", sub: "Capte le tir proprement",
            },
            punch: {
                icon: "👊", title: "Dégagement poing", sub: "Repousse le ballon au loin",
            },
            "gk-special": {
                icon: "🔥", title: "Special", sub: "Action spéciale",
            },
        },
    },
};

// ==========================
//   CONSTANTES & STRUCTURE
// ==========================

let TEAMS = null;

// Bornes des zones (en % de largeur terrain, côté Internal)
const ZONE_BOUNDS_INTERNAL = [5, 25, 45, 65, 85];

// centres calculés automatiquement à partir des bornes (pas utilisé partout mais utile)
const zoneXInternal = ZONE_BOUNDS_INTERNAL.slice(0, -1).map(
    (left, i) => (left + ZONE_BOUNDS_INTERNAL[i + 1]) / 2,
);

// 3 lignes (haut, centre, bas)
const laneY = [25, 50, 75];

// ==========================
//   CONFIG STATS
// ==========================

const STATS = {
    attack: {
        shot:    { power: 10, cost: 10 },
        pass:    { power: 10, cost: 5  },
        dribble: { power: 10, cost: 5  },
        special: { power: 12, cost: 20 },
    },
    defenseField: {
        block:           { power: 10, cost: 5  },
        intercept:       { power: 10, cost: 5  },
        tackle:          { power: 10, cost: 5  },
        "field-special": { power: 12, cost: 20 },
    },
    defenseGK: {
        hands:        { power: 10, cost: 10 },
        punch:        { power: 8,  cost: 5  },
        "gk-special": { power: 12, cost: 20 },
    },
};

// ==========================
//   EXPORT PRINCIPAL
// ==========================

export function initMatchEngine(rootEl, config = {}) {
    if (!rootEl) return;
    const matchConfig = config || {};

    TEAMS = {
        internal: {
            id: "internal",
            label: matchConfig.teams?.internal?.name ?? TEXTS.teams.internal,
        },
        external: {
            id: "external",
            label: matchConfig.teams?.external?.name ?? TEXTS.teams.external,
        },
    };

    // côté humain contrôlé par défaut : ce qu’on a mis dans la config
    let controlledTeam = matchConfig.controlledSide ?? "internal";

    // Helpers de sélection scoped au root
    const $  = (sel) => rootEl.querySelector(sel);
    const $$ = (sel) => rootEl.querySelectorAll(sel);

    // ==========================
    //   ROSTERS (slot -> player)
    // ==========================
    const rosters = {
        internal: new Map(), // key: slotNumber (1..11) => player DTO
        external: new Map(),
    };

    function normalizePlayers(list = []) {
        return Array.isArray(list) ? list : [];
    }

    function seedRosterFromConfig(teamKey) {
        const players = normalizePlayers(matchConfig.teams?.[teamKey]?.players);
        const take = players.slice(0, 11);

        for (let slot = 1; slot <= 11; slot++) {
            const p = take[slot - 1] ?? null;

            rosters[teamKey].set(slot, p ? {
                id: p.id,
                number: p.number ?? slot,
                firstname: p.firstname ?? "",
                lastname: p.lastname ?? "",
                position: p.position ?? "",
                // ✅ FIX: fallback stats si elles ne sont pas dans p.stats
                stats: p.stats ?? {
                    shot: p.shot ?? 0,
                    pass: p.pass ?? 0,
                    dribble: p.dribble ?? 0,
                    tackle: p.tackle ?? 0,
                    intercept: p.intercept ?? 0,
                    block: p.block ?? 0,
                    attack: p.attack ?? 0,
                    defense: p.defense ?? 0,
                    speed: p.speed ?? 0,
                    stamina: p.stamina ?? 0,
                    hand_save: p.hand_save ?? 0,
                    punch_save: p.punch_save ?? 0,
                },
            } : {
                id: null,
                number: slot,
                firstname: "Joueur",
                lastname: `#${slot}`,
                position: "",
                stats: null,
            });
        }
    }

    seedRosterFromConfig("internal");
    seedRosterFromConfig("external");

    function getPlayerInfo(team, slotNumber) {
        return rosters[team]?.get(slotNumber) ?? null;
    }

    function clampStat(v) {
        const n = Number(v ?? 0);
        return Number.isFinite(n) ? Math.max(0, n) : 0;
    }

    function getStat(team, slotNumber, key) {
        const info = getPlayerInfo(team, slotNumber);
        const stats = info?.stats ?? {};
        return clampStat(stats[key]);
    }

    const STAT_COEF = 0.6;

    function attackBaseFor(actionKey, team, slotNumber) {
        const base = STATS.attack[actionKey]?.power ?? 10;

        if (actionKey === "pass")    return base + getStat(team, slotNumber, "pass") * STAT_COEF;
        if (actionKey === "dribble") return base + getStat(team, slotNumber, "dribble") * STAT_COEF;
        if (actionKey === "shot")    return base + getStat(team, slotNumber, "shot") * STAT_COEF;

        // special offensif => ATTACK
        if (actionKey === "special") return base + getStat(team, slotNumber, "attack") * STAT_COEF;

        return base;
    }

    function defenseBaseFor(defenseAction, defenseTeam, defenseSlotNumber, isKeeper = false) {
        const baseField = STATS.defenseField[defenseAction]?.power;
        const baseGk    = STATS.defenseGK[defenseAction]?.power;
        const base      = (baseField ?? baseGk ?? 10);

        if (isKeeper) {
            if (defenseAction === "hands") return base + getStat(defenseTeam, defenseSlotNumber, "hand_save") * STAT_COEF;
            if (defenseAction === "punch") return base + getStat(defenseTeam, defenseSlotNumber, "punch_save") * STAT_COEF;
            if (defenseAction === "gk-special") return base + getStat(defenseTeam, defenseSlotNumber, "defense") * STAT_COEF;
            return base;
        }

        if (defenseAction === "block")     return base + getStat(defenseTeam, defenseSlotNumber, "block") * STAT_COEF;
        if (defenseAction === "intercept") return base + getStat(defenseTeam, defenseSlotNumber, "intercept") * STAT_COEF;
        if (defenseAction === "tackle")    return base + getStat(defenseTeam, defenseSlotNumber, "tackle") * STAT_COEF;

        if (defenseAction === "field-special") return base + getStat(defenseTeam, defenseSlotNumber, "defense") * STAT_COEF;

        return base;
    }

    function applyRosterToDOM() {
        for (const team of ["internal", "external"]) {
            for (let slot = 1; slot <= 11; slot++) {
                const id = (team === "internal" ? "I" : "E") + String(slot);
                const el = rootEl.querySelector(`.player[data-player="${id}"]`);
                if (!el) continue;

                const info = getPlayerInfo(team, slot);
                if (!info) continue;

                el.textContent = String(info.number);

                el.dataset.slot = String(slot);
                el.dataset.jersey = String(info.number);
                el.dataset.firstname = info.firstname;
                el.dataset.lastname = info.lastname;
                el.dataset.position = info.position;
            }
        }
    }

    // ==========================
    //   ÉTAT DU MATCH
    // ==========================

    let ball = {
        team: "internal",
        zoneIndex: 1,
        laneIndex: 1,
        number: 8,
        frontOfKeeper: false,
    };

    let currentTeam        = "internal";
    let score              = { internal: 0, external: 0 };
    let turns              = 0;

    let phase              = "attack";
    let pendingAttack      = null;
    let isAnimating        = false;
    let isKickoff          = true;
    let isGameOver         = false;
    let pendingShotContext = null;
    let pendingClearanceBonus = 0;

    const basePositions = {};
    let lastDribblerId  = null;

    const ENDURANCE_MAX  = 100;
    const stamina        = {};

    let onePlayerMode  = false;
    const controlMode = matchConfig.controlMode ?? "both";
    onePlayerMode = (controlMode === "single");
    controlledTeam = matchConfig.controlledSide ?? "internal";

    const actionHistory = [];
    const MAX_HISTORY   = 15;

    // ==========================
    //   RÉFÉRENCES DOM
    // ==========================

    const ballEl          = $("#ball");
    const scoreInternalEl = $("#score-internal");
    const scoreExternalEl = $("#score-external");
    const turnsDisplayEl  = $("#turns-display");
    const turnIndicatorEl = $("#turn-indicator");
    const msgMainEl       = $("#message-main");
    const msgSubEl        = $("#message-sub");
    const actionBarEl     = $("#action-bar");
    const defenderEnergyFillEl = $("#defender-energy-fill");

    const teamNameInternalEl = $("#team-name-internal");
    const teamNameExternalEl = $("#team-name-external");
    const matchEndActionsEl = $("#match-end-actions");
    const finishMatchBtn    = $("#btn-finish-match");

    const currentActionTitleEl  = $("#current-action-title");
    const currentActionDetailEl = $("#current-action-detail");
    const duelDiceEl            = $("#duel-dice-display");
    const historyListEl         = $("#history-list");

    const defenderTeamEl    = $("#defender-team");
    const defenderNumberEl  = $("#defender-number");
    const defenderNameEl    = $("#defender-name");
    const defenderRoleEl    = $("#defender-role");

    const defStatBlockEl     = $("#def-stat-block");
    const defStatInterceptEl = $("#def-stat-intercept");
    const defStatTackleEl    = $("#def-stat-tackle");
    const defStatDefenseEl   = $("#def-stat-defense");
    const defStatHandSaveEl  = $("#def-stat-hand_save");
    const defStatPunchSaveEl = $("#def-stat-punch_save");

    const modeOnePlayerBtn     = $("#mode-one-player");
    const controlledTeamSelect = $("#controlled-team-select");

    const aiOverlayEl = $("#ai-turn-overlay");

    // ==========================
    //   HELPERS GÉNÉRAUX
    // ==========================

    const otherTeam = (t) => (t === "internal" ? "external" : "internal");

    function isAITeam(team) {
        return onePlayerMode && team !== controlledTeam;
    }

    function rollDie() {
        return 1 + Math.floor(Math.random() * DIE_SIDES);
    }

    function setMessage(main, sub) {
        if (msgMainEl && main) msgMainEl.textContent = main;
        if (msgSubEl && sub !== undefined) msgSubEl.textContent = sub;
    }

    function setAIOverlay(visible, text) {
        if (!aiOverlayEl) return;
        if (visible) {
            aiOverlayEl.classList.add("visible");
            aiOverlayEl.textContent = text || TEXTS.ui.aiThinkingDefault;
        } else {
            aiOverlayEl.classList.remove("visible");
        }
    }

    // Message de logs
    function pushLogEntry(main, detailsLines = [], diceTag = null) {
        if (currentActionTitleEl) currentActionTitleEl.textContent = main || "–";
        if (currentActionDetailEl) {
            currentActionDetailEl.textContent =
                detailsLines.length ? detailsLines.join(" | ") : "";
        }

        const turnLabel = `T${String(turns + 1).padStart(2, "0")}`;

        // Historique : ajoute 🎲 a-b si dispo
        const shortLine = diceTag
            ? `${turnLabel} — ${main}  (${diceTag})`
            : `${turnLabel} — ${main}`;

        actionHistory.push(shortLine);
        if (actionHistory.length > MAX_HISTORY) actionHistory.shift();

        if (historyListEl) {
            historyListEl.innerHTML = actionHistory
                .map((line) => `<li>${line}</li>`)
                .join("");
        }
    }

    function isGoodDefenseChoice(attackAction, defenseAction) {
        const a = String(attackAction || "").toLowerCase();
        const d = String(defenseAction || "").toLowerCase();

        // Duel gardien
        if (d === "hands" || d === "punch" || d === "gk-special") {
            return d === "hands" || d === "gk-special";
        }

        switch (a) {
            case "pass":    return d === "intercept";
            case "dribble": return d === "tackle";
            case "shot":    return d === "block";
            case "special": return d === "field-special" || d === "block";
            default:        return false;
        }
    }

    function buildActionResultText({ attackAction, defenseAction, duelResult }) {
        const goodChoice = isGoodDefenseChoice(attackAction, defenseAction);

        if (attackAction === "pass") {
            if (duelResult === "defense") return goodChoice ? "Passe interceptée" : "Passe échouée";
            if (duelResult === "attack")  return goodChoice ? "Passe non interceptée" : "Passe réussie";
            return "Passe déviée";
        }

        if (attackAction === "dribble") {
            if (duelResult === "defense") return goodChoice ? "Dribble stoppé" : "Dribble raté";
            if (duelResult === "attack")  return goodChoice ? "Dribble réussi malgré le tacle" : "Dribble réussi";
            return "Dribble déséquilibré";
        }

        if (attackAction === "shot" || attackAction === "special") {
            const label = attackAction === "special" ? "Tir spécial" : "Tir";
            if (duelResult === "defense") return goodChoice ? `${label} contré` : `${label} imprécis`;
            if (duelResult === "attack")  return goodChoice ? `${label} cadré malgré la défense` : `${label} cadré`;
            return `${label} dévié`;
        }

        return "Action disputée";
    }

    function showDuelDice(attackScore, defenseScore) {
        if (!duelDiceEl) return;
        const a = attackScore.toFixed(1);
        const d = defenseScore.toFixed(1);
        duelDiceEl.textContent = `🎲 ${a} - ${d}`;
        duelDiceEl.classList.add("visible");
        duelDiceEl.classList.remove("pop");
        void duelDiceEl.offsetWidth;
        duelDiceEl.classList.add("pop");
    }

    function clearDuelDice() {
        if (!duelDiceEl) return;
        duelDiceEl.classList.remove("visible", "pop");
    }

    // ==========================
    //   BALLE LIBRE (égalité duel)
    // ==========================

    function sendLooseBallRandomFieldPlayer() {
        const fieldEls = Array.from(rootEl.querySelectorAll(".player"))
            .filter(el => !el.classList.contains("goalkeeper"));

        if (!fieldEls.length) return;

        const picked = fieldEls[Math.floor(Math.random() * fieldEls.length)];
        const pid = picked.dataset.player; // "I8" ou "E6"
        if (!pid) return;

        const t = pid.startsWith("I") ? "internal" : "external";
        const num = parseInt(pid.slice(1), 10);

        moveBallToPlayer(t, num);

        setMessage("Duel équilibré !", "Ballon dévié : possession aléatoire.");
        pushLogEntry("Duel équilibré — ballon dévié", [`Possession : ${TEAMS[t].label} #${num}`]);
    }

    function updateScoreUI() {
        if (scoreInternalEl) scoreInternalEl.textContent = score.internal;
        if (scoreExternalEl) scoreExternalEl.textContent = score.external;
        const t = String(turns).padStart(2, "0");
        if (turnsDisplayEl) turnsDisplayEl.textContent  = t;
        if (turnIndicatorEl) turnIndicatorEl.textContent = t;
    }

    function getPlayerId(team, number) {
        const prefix = team === "internal" ? "I" : "E";
        return prefix + String(number);
    }

    // ==========================
    //   ENDURANCE
    // ==========================

    function getStamina(playerId) {
        if (!(playerId in stamina)) stamina[playerId] = ENDURANCE_MAX;
        return stamina[playerId];
    }

    function staminaFactor(playerId) {
        const value = getStamina(playerId);
        if (value >= STAMINA_THRESHOLDS.HIGH) return STAMINA_FACTORS.HIGH;
        if (value >= STAMINA_THRESHOLDS.MID)  return STAMINA_FACTORS.MID;
        if (value >= STAMINA_THRESHOLDS.LOW)  return STAMINA_FACTORS.LOW;
        if (value > 0)                        return STAMINA_FACTORS.CRIT;
        return STAMINA_FACTORS.EXHAUSTED;
    }

    function staminaCostMultiplierFor(category) {
        if (category === "defenseGK") return 0.6;
        return 1.0;
    }

    function applyStaminaCost(playerId, category, actionKey) {
        if (!playerId) return;

        const cfgCategory = STATS[category];
        const cfg         = cfgCategory && cfgCategory[actionKey];
        const baseCost    = cfg ? cfg.cost : 0;
        const cost        = Math.round(baseCost * staminaCostMultiplierFor(category));

        const curr        = getStamina(playerId);
        const next        = Math.max(0, curr - cost);
        stamina[playerId] = next;

        updateStaminaUI(playerId);
    }

    function updateStaminaUI(playerId) {
        const value = getStamina(playerId);
        const ratio = value / ENDURANCE_MAX;

        const el = rootEl.querySelector(`.player[data-player="${playerId}"]`);
        if (el) {
            el.classList.add("show-endurance");
            const shell = el.querySelector(".endurance-shell");
            if (shell) {
                const bar = shell.querySelector(".endurance-bar");
                if (bar) bar.style.width = `${Math.max(10, ratio * 100)}%`;
            }
            el.classList.remove("e-high","e-mid","e-low","e-crit");

            if (value >= STAMINA_THRESHOLDS.HIGH) el.classList.add("e-high");
            else if (value >= STAMINA_THRESHOLDS.MID) el.classList.add("e-mid");
            else if (value >= STAMINA_THRESHOLDS.LOW) el.classList.add("e-low");
            else el.classList.add("e-crit");
        }

        const ballId = getPlayerId(ball.team, ball.number);
        if (ballId === playerId) updateTeamCard();
    }

    function initStamina() {
        $$(".player").forEach((el) => {
            const id = el.dataset.player;
            stamina[id] = ENDURANCE_MAX;

            const shell = document.createElement("div");
            shell.className = "endurance-shell";
            const bar = document.createElement("div");
            bar.className = "endurance-bar";
            shell.appendChild(bar);
            el.appendChild(shell);

            updateStaminaUI(id);
        });
    }

    // ==========================
    //   GRILLE & POSITIONS
    // ==========================

    function getCellCenter(team, zoneIndex, laneIndex) {
        const left  = ZONE_BOUNDS_INTERNAL[zoneIndex];
        const right = ZONE_BOUNDS_INTERNAL[zoneIndex + 1];
        const xInternal = (left + right) / 2;

        const x = team === "internal" ? xInternal : 100 - xInternal;
        const y = laneY[laneIndex];
        return { x, y };
    }

    function getFacingZoneIndex(zoneIndex) {
        return Math.max(0, Math.min(3, 3 - zoneIndex));
    }

    function getCarrierElement(team, number) {
        const prefix = team === "internal" ? "I" : "E";
        const id = prefix + String(number);
        return rootEl.querySelector(`[data-player="${id}"]`);
    }

    function getClosestPlayerInCell(team, zoneIndex, laneIndex) {
        const selector = team === "internal" ? ".player.internal" : ".player.external";
        const center   = getCellCenter(team, zoneIndex, laneIndex);

        let bestDist       = Infinity;
        let bestCandidates = [];

        rootEl.querySelectorAll(selector).forEach((el) => {
            const x = parseFloat(el.style.left);
            const y = parseFloat(el.style.top);
            if (isNaN(x) || isNaN(y)) return;

            const dx = x - center.x;
            const dy = y - center.y;
            const d2 = dx*dx + dy*dy;

            if (d2 < bestDist - 1e-6) {
                bestDist       = d2;
                bestCandidates = [el.dataset.player];
            } else if (Math.abs(d2 - bestDist) <= 1e-6) {
                bestCandidates.push(el.dataset.player);
            }
        });

        if (!bestCandidates.length) return null;
        const randIdx = Math.floor(Math.random() * bestCandidates.length);
        return bestCandidates[randIdx];
    }

    function pickReceiverInCell(team, zoneIndex, laneIndex, fallbackNumber, excludeNumber = null) {
        let receiverId = getClosestPlayerInCell(team, zoneIndex, laneIndex);

        if (receiverId) {
            const num = parseInt(receiverId.slice(1), 10);
            if (excludeNumber !== null && num === excludeNumber) receiverId = null;
        }

        if (!receiverId) {
            const selector = team === "internal" ? ".player.internal" : ".player.external";
            const all = Array.from(rootEl.querySelectorAll(selector))
                .filter(el => !el.classList.contains("goalkeeper"));

            if (!all.length) return fallbackNumber;

            const filtered = excludeNumber === null
                ? all
                : all.filter(el => parseInt(el.dataset.player.slice(1), 10) !== excludeNumber);

            const pool = filtered.length ? filtered : all;
            const rand = pool[Math.floor(Math.random() * pool.length)];
            return parseInt(rand.dataset.player.slice(1), 10);
        }

        return parseInt(receiverId.slice(1), 10);
    }

    function getRandomFieldPlayer(team) {
        const selector = team === "internal" ? ".player.internal" : ".player.external";
        const candidates = Array.from(rootEl.querySelectorAll(selector))
            .filter(el => !el.classList.contains("goalkeeper"));
        if (!candidates.length) return null;
        const idx = Math.floor(Math.random()*candidates.length);
        return candidates[idx].dataset.player;
    }

    function getKeeperId(team) {
        const selector = team === "internal"
            ? ".player.internal.goalkeeper"
            : ".player.external.goalkeeper";
        const el = rootEl.querySelector(selector);
        return el ? el.dataset.player : null;
    }

    function moveBallToPlayer(team, number) {
        if (!ballEl) return;
        const el = getCarrierElement(team, number);
        if (!el) return;

        const x = parseFloat(el.style.left);
        const y = parseFloat(el.style.top);

        ballEl.style.left  = x + "%";
        ballEl.style.top   = y + "%";
        const info = getPlayerInfo(team, number);
        ballEl.textContent = info ? String(info.number) : String(number);

        ball.team          = team;
        ball.number        = number;
        ball.frontOfKeeper = false;

        const xInternal = team === "internal" ? x : 100 - x;
        let zoneIndex = 0;

        for (let i = 0; i < ZONE_BOUNDS_INTERNAL.length - 1; i++) {
            const left  = ZONE_BOUNDS_INTERNAL[i];
            const right = ZONE_BOUNDS_INTERNAL[i + 1];
            if (xInternal >= left && xInternal <= right) {
                zoneIndex = i;
                break;
            }
        }

        let bestLane = 0, bestLaneDist = Infinity;
        laneY.forEach((vy, i) => {
            const d = Math.abs(vy - y);
            if (d < bestLaneDist) { bestLaneDist = d; bestLane = i; }
        });

        ball.zoneIndex = zoneIndex;
        ball.laneIndex = bestLane;

        updateTeamCard();
        updateCardsPower();
    }

    // ==========================
    //   CARDS HOME/AWAY
    // ==========================

    function updateSideCard(prefix, team, slotNumber) {
        const info = getPlayerInfo(team, slotNumber);

        const setText = (selector, value) => {
            const el = rootEl.querySelector(selector);
            if (el) el.textContent = value ?? "—";
        };

        const fullName = info ? `${info.firstname ?? ""} ${info.lastname ?? ""}`.trim() : "";
        setText(`#${prefix}-name`, fullName || `Joueur #${info?.number ?? slotNumber}`);
        setText(`#${prefix}-role`, info?.position || "—");
        setText(`#${prefix}-number`, info ? String(info.number) : String(slotNumber));
        setText(
            `#${prefix}-team`,
            TEAMS?.[team]?.label ?? (team === "internal" ? "Domicile" : "Extérieur")
        );

        const s = info?.stats ?? {};
        const v = (k) => {
            const n = Number(s?.[k] ?? 0);
            return Number.isFinite(n) ? n : 0;
        };

        setText(`#${prefix}-stat-shot`,       String(v("shot")));
        setText(`#${prefix}-stat-pass`,       String(v("pass")));
        setText(`#${prefix}-stat-dribble`,    String(v("dribble")));
        setText(`#${prefix}-stat-attack`,     String(v("attack")));

        setText(`#${prefix}-stat-block`,      String(v("block")));
        setText(`#${prefix}-stat-intercept`,  String(v("intercept")));
        setText(`#${prefix}-stat-tackle`,     String(v("tackle")));
        setText(`#${prefix}-stat-defense`,    String(v("defense")));

        setText(`#${prefix}-stat-hand_save`,  String(v("hand_save")));
        setText(`#${prefix}-stat-punch_save`, String(v("punch_save")));

        const isGoalkeeper = (info?.position ?? "").toLowerCase() === "goalkeeper";

        const showRow = (id) => {
            const el = rootEl.querySelector(id);
            if (el?.parentElement) el.parentElement.classList.remove("hidden");
        };
        const hideRow = (id) => {
            const el = rootEl.querySelector(id);
            if (el?.parentElement) el.parentElement.classList.add("hidden");
        };

        const fieldRows = [
            `#${prefix}-stat-block`,
            `#${prefix}-stat-intercept`,
            `#${prefix}-stat-tackle`,
            `#${prefix}-stat-dribble`,
        ];
        const gkRows = [
            `#${prefix}-stat-hand_save`,
            `#${prefix}-stat-punch_save`,
        ];

        if (isGoalkeeper) {
            fieldRows.forEach(hideRow);
            gkRows.forEach(showRow);
        } else {
            gkRows.forEach(hideRow);
            fieldRows.forEach(showRow);
        }

        const playerId = getPlayerId(team, slotNumber);
        const value = getStamina(playerId);
        const ratio = value / ENDURANCE_MAX;

        const fillEl = rootEl.querySelector(`#${prefix}-energy-fill`);
        if (fillEl) {
            fillEl.style.width = `${Math.max(0, ratio * 100)}%`;
            fillEl.classList.remove("e-high", "e-mid", "e-low", "e-crit");

            if (value >= STAMINA_THRESHOLDS.HIGH) fillEl.classList.add("e-high");
            else if (value >= STAMINA_THRESHOLDS.MID) fillEl.classList.add("e-mid");
            else if (value >= STAMINA_THRESHOLDS.LOW) fillEl.classList.add("e-low");
            else fillEl.classList.add("e-crit");
        }
        // ==========================
        //   UI: TEAM BORDER + BALL ICON
        // ==========================
        const cardEl = rootEl.querySelector(`#${prefix}-card`);
        if (cardEl) {
            cardEl.classList.remove("team-internal", "team-external");
            cardEl.classList.add(team === "internal" ? "team-internal" : "team-external");
        }

        const ballIconEl = rootEl.querySelector(`#${prefix}-ball-icon`);
        if (ballIconEl) {
            const isBallCarrier =
                (team === ball.team) && (Number(slotNumber) === Number(ball.number));
            ballIconEl.classList.toggle("hidden", !isBallCarrier);
        }

    }

    // ==========================
    //   DEFENDER CARD (CENTRAL)
    // ==========================

    function clearDefenderCard() {
        if (defenderTeamEl)   defenderTeamEl.textContent = "—";
        if (defenderNumberEl) defenderNumberEl.textContent = "—";
        if (defenderNameEl)   defenderNameEl.textContent = "—";
        if (defenderRoleEl)   defenderRoleEl.textContent = "—";

        const dash = (el) => { if (el) el.textContent = "—"; };
        dash(defStatBlockEl);
        dash(defStatInterceptEl);
        dash(defStatTackleEl);
        dash(defStatDefenseEl);
        dash(defStatHandSaveEl);
        dash(defStatPunchSaveEl);

        if (defenderEnergyFillEl) {
            defenderEnergyFillEl.style.width = `100%`;
            defenderEnergyFillEl.classList.remove("e-high","e-mid","e-low","e-crit");
            defenderEnergyFillEl.classList.add("e-high");
        }
    }

    function updateDefenderCard(team, slotNumber) {
        const info = getPlayerInfo(team, slotNumber);

        if (defenderTeamEl) defenderTeamEl.textContent = TEAMS[team].label;
        if (defenderNumberEl) defenderNumberEl.textContent = info ? String(info.number) : String(slotNumber);

        if (defenderNameEl) {
            const full = info ? `${info.firstname ?? ""} ${info.lastname ?? ""}`.trim() : "";
            defenderNameEl.textContent = full || `Joueur #${info?.number ?? slotNumber}`;
        }
        if (defenderRoleEl) defenderRoleEl.textContent = info?.position || "—";

        const s = info?.stats ?? {};
        const v = (k) => {
            const n = Number(s?.[k] ?? 0);
            return Number.isFinite(n) ? n : 0;
        };

        if (defStatBlockEl)     defStatBlockEl.textContent = String(v("block"));
        if (defStatInterceptEl) defStatInterceptEl.textContent = String(v("intercept"));
        if (defStatTackleEl)    defStatTackleEl.textContent = String(v("tackle"));
        if (defStatDefenseEl)   defStatDefenseEl.textContent = String(v("defense"));

        if (defStatHandSaveEl)  defStatHandSaveEl.textContent = String(v("hand_save"));
        if (defStatPunchSaveEl) defStatPunchSaveEl.textContent = String(v("punch_save"));

        const playerId = getPlayerId(team, slotNumber);
        const value = getStamina(playerId);
        const ratio = value / ENDURANCE_MAX;

        if (defenderEnergyFillEl) {
            defenderEnergyFillEl.style.width = `${ratio * 100}%`;
            defenderEnergyFillEl.classList.remove("e-high","e-mid","e-low","e-crit");

            if (value >= STAMINA_THRESHOLDS.HIGH) defenderEnergyFillEl.classList.add("e-high");
            else if (value >= STAMINA_THRESHOLDS.MID) defenderEnergyFillEl.classList.add("e-mid");
            else if (value >= STAMINA_THRESHOLDS.LOW) defenderEnergyFillEl.classList.add("e-low");
            else defenderEnergyFillEl.classList.add("e-crit");
        }
    }

    function setDefenderPreviewFor(action, defenseTeam) {
        const defensePrefix = (defenseTeam === "internal") ? "home" : "away";

        const isCloseRange = ball.frontOfKeeper && (action === "shot" || action === "special");
        if (isCloseRange) {
            updateSideCard(defensePrefix, defenseTeam, 1);
            return;
        }

        const defZone = getFacingZoneIndex(ball.zoneIndex);
        const defLane = ball.laneIndex;

        const defenderId =
            getClosestPlayerInCell(defenseTeam, defZone, defLane) ||
            getRandomFieldPlayer(defenseTeam);

        const defenderSlot = defenderId ? parseInt(defenderId.slice(1), 10) : 6;
        updateSideCard(defensePrefix, defenseTeam, defenderSlot);
    }

    function updateTeamCard() {
        const prefix = (ball.team === "internal") ? "home" : "away";
        updateSideCard(prefix, ball.team, ball.number);
        updateCardsPower();
    }

    // ==========================
    //   UI ACTION BAR
    // ==========================

    function buildSkillCard(actionKey, cfg) {
        return `
        <button class="skill-card" data-action="${actionKey}">
          <div class="skill-icon">${cfg.icon}</div>
          <div class="skill-title">${cfg.title}</div>
          <div class="skill-sub">${cfg.sub}</div>
          <div class="skill-bottom">
            <div class="skill-power"></div>
            <div class="skill-cost">Énergie <span></span></div>
          </div>
        </button>
      `;
    }

    function buildDefCard(defKey, cfg) {
        return `
        <button class="def-card" data-defense="${defKey}">
          <div class="def-icon">${cfg.icon}</div>
          <div class="def-title">${cfg.title}</div>
          <div class="def-sub">${cfg.sub}</div>
          <div class="def-bottom">
            <div class="def-power"></div>
            <div class="def-cost">Énergie <span></span></div>
          </div>
        </button>
      `;
    }

    function buildAttackActionsHTML() {
        const cfg = TEXTS.cards.attack;
        return `
        <div id="attack-strip">
          ${buildSkillCard("shot",    cfg.shot)}
          ${buildSkillCard("pass",    cfg.pass)}
          ${buildSkillCard("dribble", cfg.dribble)}
          ${buildSkillCard("special", cfg.special)}
        </div>
      `;
    }

    function buildDefenseFieldHTML() {
        const cfg = TEXTS.cards.defenseField;
        return `
        <div id="defense-strip">
          ${buildDefCard("block",          cfg.block)}
          ${buildDefCard("intercept",      cfg.intercept)}
          ${buildDefCard("tackle",         cfg.tackle)}
          ${buildDefCard("field-special",  cfg["field-special"])}
        </div>
      `;
    }

    function buildDefenseGKHTML() {
        const cfg = TEXTS.cards.defenseGK;
        return `
        <div id="defense-strip">
          ${buildDefCard("hands",       cfg.hands)}
          ${buildDefCard("punch",       cfg.punch)}
          ${buildDefCard("gk-special",  cfg["gk-special"])}
        </div>
      `;
    }

    function initUIFromStats() {
        const attackStrip = rootEl.querySelector("#attack-strip");
        if (attackStrip) {
            attackStrip.querySelectorAll(".skill-card").forEach((btn) => {
                const action = btn.dataset.action;
                const cfg = STATS.attack[action];
                if (!cfg) return;
                const costEl = btn.querySelector(".skill-cost span");
                if (costEl) costEl.textContent = cfg.cost;
            });
        }

        const defenseStrip = rootEl.querySelector("#defense-strip");
        if (defenseStrip) {
            defenseStrip.querySelectorAll(".def-card").forEach((btn) => {
                const def = btn.dataset.defense;
                const cfgField = STATS.defenseField[def];
                const cfgGk    = STATS.defenseGK[def];
                const cfg      = cfgField || cfgGk;
                if (!cfg) return;
                const costEl = btn.querySelector(".def-cost span");
                if (costEl) costEl.textContent = cfg.cost;
            });
        }
    }

    function updateCardsPower() {
        if (!actionBarEl) return;

        // ---- Attack cards (ball carrier)
        const info = getPlayerInfo(ball.team, ball.number);
        const s = info?.stats ?? null;

        actionBarEl.querySelectorAll(".skill-card").forEach(btn => {
            const action = btn.dataset.action;
            let value = 0;

            if (s) {
                if (action === "pass") value = Number(s.pass ?? 0);
                if (action === "dribble") value = Number(s.dribble ?? 0);
                if (action === "shot") value = Number(s.shot ?? 0);
                if (action === "special") value = Number(s.attack ?? 0);
            }

            const powerEl = btn.querySelector(".skill-power");
            if (powerEl) powerEl.textContent = String(value);
        });

        // ---- Defense cards
        const modeClass = Array.from(actionBarEl.classList).find(c => c.startsWith("mode-defense-"));
        if (!modeClass) return;

        const defenseTeam = modeClass.includes("mode-defense-external") ? "external" : "internal";

        // GK bar ?
        const hasHands = !!actionBarEl.querySelector('.def-card[data-defense="hands"]');
        const hasPunch = !!actionBarEl.querySelector('.def-card[data-defense="punch"]');

        if (hasHands || hasPunch) {
            const gkSlot = 1;
            const gkInfo = getPlayerInfo(defenseTeam, gkSlot);
            const gk = gkInfo?.stats ?? {};

            actionBarEl.querySelectorAll(".def-card").forEach(btn => {
                const def = btn.dataset.defense;
                let value = 0;

                if (def === "hands") value = Number(gk.hand_save ?? 0);
                if (def === "punch") value = Number(gk.punch_save ?? 0);
                if (def === "gk-special") value = Number(gk.defense ?? 0);

                const powerEl = btn.querySelector(".def-power");
                if (powerEl) powerEl.textContent = String(value);
            });

            return;
        }

        const originZone = ball.zoneIndex;
        const originLane = ball.laneIndex;
        const defZone = getFacingZoneIndex(originZone);
        const defLane = originLane;

        const defenderId =
            getClosestPlayerInCell(defenseTeam, defZone, defLane) ||
            getRandomFieldPlayer(defenseTeam);

        const defenderSlot = defenderId ? parseInt(defenderId.slice(1), 10) : 6;
        const dInfo = getPlayerInfo(defenseTeam, defenderSlot);
        const d = dInfo?.stats ?? {};

        actionBarEl.querySelectorAll(".def-card").forEach(btn => {
            const def = btn.dataset.defense;
            let value = 0;

            if (def === "block") value = Number(d.block ?? 0);
            if (def === "intercept") value = Number(d.intercept ?? 0);
            if (def === "tackle") value = Number(d.tackle ?? 0);
            if (def === "field-special") value = Number(d.defense ?? 0);

            const powerEl = btn.querySelector(".def-power");
            if (powerEl) powerEl.textContent = String(value);
        });
    }

    function bindActionButtons() {
        if (!actionBarEl) return;

        actionBarEl.querySelectorAll(".skill-card").forEach((btn) => {
            btn.addEventListener("click", () => {
                handleAttackClick(btn.dataset.action);
            });
        });

        actionBarEl.querySelectorAll(".def-card").forEach((btn) => {
            btn.addEventListener("click", () => {
                handleDefenseClick(btn.dataset.defense);
            });
        });
    }

    function setActionBar(html, modeClass) {
        if (!actionBarEl) return;

        actionBarEl.classList.add("fade-out");
        setTimeout(() => {
            actionBarEl.innerHTML = html;

            actionBarEl.className = actionBarEl.className.replace(/\bmode-[^\s]+/g, "");
            if (modeClass) actionBarEl.classList.add(modeClass);

            initUIFromStats();
            updateCardsPower();

            if (isKickoff && html.includes("attack-strip")) {
                const cards = actionBarEl.querySelectorAll(".skill-card");
                cards.forEach((btn) => {
                    if (btn.dataset.action !== "pass") btn.style.display = "none";
                });
            }

            actionBarEl.classList.remove("fade-out");
            actionBarEl.classList.add("fade-in");

            bindActionButtons();
        }, 200);
    }

    function showAttackBarForCurrentTeam() {
        if (isGameOver) return;
        const mode = `mode-attack-${currentTeam}`;
        setActionBar(buildAttackActionsHTML(), mode);

        const defTeam = otherTeam(currentTeam);
        const defaultAction = isKickoff ? "pass" : (ball.frontOfKeeper ? "shot" : "pass");
        setDefenderPreviewFor(defaultAction, defTeam);

        if (isAITeam(currentTeam)) scheduleAIAttack();
        updateCardsPower();
    }

    // ==========================
    //   ANIM & TOURS
    // ==========================

    function animateAndThen(cb) {
        if (!ballEl) { if (cb) cb(); return; }
        isAnimating = true;
        ballEl.classList.add("ball-kick");
        setTimeout(() => {
            ballEl.classList.remove("ball-kick");
            isAnimating = false;
            if (cb) cb();
        }, ANIM_MS);
    }

    function advanceTurn(newTeam) {
        if (isGameOver) return;

        currentTeam = newTeam;
        turns++;

        if (turns >= GAME_RULES.MAX_TURNS) {
            isGameOver = true;

            setMessage(
                TEXTS.ui.matchEndMain,
                `${TEXTS.ui.matchEndPrefix}${score.internal} - ${score.external}`,
            );
            pushLogEntry(
                TEXTS.logs.matchEndTitle,
                [`Score final ${score.internal} - ${score.external}`],
            );

            if (actionBarEl) {
                actionBarEl.classList.remove("fade-in");
                actionBarEl.classList.add("fade-out");
                setTimeout(() => { actionBarEl.innerHTML = ""; }, 200);
            }

            refreshUI();
            if (matchEndActionsEl) matchEndActionsEl.classList.remove("hidden");

            if (finishMatchBtn) {
                finishMatchBtn.onclick = () => {
                    const internalTeamId = matchConfig?.sides?.internalTeamId;
                    const externalTeamId = matchConfig?.sides?.externalTeamId;

                    if (!internalTeamId || !externalTeamId) {
                        console.error("Missing sides mapping in engineConfig", matchConfig?.sides);
                        return;
                    }

                    const payload = {
                        matchId: matchConfig.matchId,
                        gameSaveId: matchConfig.gameSaveId,
                        scoresByTeamId: {
                            [internalTeamId]: score.internal,
                            [externalTeamId]: score.external,
                        },
                    };

                    if (typeof matchConfig.onMatchEnd === "function") {
                        matchConfig.onMatchEnd(payload);
                    }
                };
            }

            return;
        }

        setMessage(`${TEAMS[currentTeam].label} a la balle`, TEXTS.ui.chooseAttackSub);
        phase = "attack";
        pendingAttack = null;
    }

    // ==========================
    //   POSITIONS DE BASE
    // ==========================

    function resetLastDribbler() {
        if (!lastDribblerId) return;
        const pos = basePositions[lastDribblerId];
        const el  = rootEl.querySelector(`[data-player="${lastDribblerId}"]`);
        if (pos && el) {
            el.style.left = pos.x + "%";
            el.style.top  = pos.y + "%";
        }
        lastDribblerId = null;
    }

    function initBasePositions() {
        $$(".player").forEach((el) => {
            basePositions[el.dataset.player] = {
                x: parseFloat(el.style.left),
                y: parseFloat(el.style.top),
            };
        });
    }

    function applyKickoffPositions() {
        $$(".player").forEach((el) => {
            const base = basePositions[el.dataset.player];
            if (!base) return;
            let x = base.x;
            let y = base.y;

            if (el.classList.contains("internal")) {
                if (x > 50) x = 48;
            } else {
                if (x < 50) x = 52;
            }
            el.style.left = x + "%";
            el.style.top  = y + "%";
        });
    }

    function restoreBasePositions() {
        $$(".player").forEach((el) => {
            const base = basePositions[el.dataset.player];
            if (!base) return;
            el.style.left = base.x + "%";
            el.style.top  = base.y + "%";
        });
    }

    // ==========================
    //   IA
    // ==========================

    function computeAIAttackChoice() {
        if (isKickoff) return "pass";

        if (ball.frontOfKeeper) {
            const r = Math.random();
            const specialProb = AI_RULES.ATTACK.FRONT_GK_SPECIAL_PROB ?? 0.15;
            return (r < specialProb) ? "special" : "shot";
        }

        const z = ball.zoneIndex;

        const aiCarrierId = getPlayerId(currentTeam, ball.number);
        const st = getStamina(aiCarrierId);

        if (st < 20) return "pass";

        if (z <= 1) {
            const r = Math.random();
            if (r < (AI_RULES.ATTACK.EARLY_PASS_PROB ?? 0.7)) return "pass";
            return "dribble";
        }

        if (z === 2) {
            const r = Math.random();
            if (st >= 30) {
                if (r < 0.70) return "dribble";
                if (r < 0.95) return "pass";
                return "shot";
            }
            if (r < 0.80) return "pass";
            return "dribble";
        }

        if (z === 3) {
            const r = Math.random();
            if (st >= 35) {
                if (r < 0.85) return "dribble";
                if (r < 0.95) return "shot";
                return "pass";
            }
            if (r < 0.55) return "dribble";
            if (r < 0.85) return "shot";
            return "pass";
        }

        return "pass";
    }

    function scheduleAIAttack() {
        if (!isAITeam(currentTeam) || phase !== "attack" || isGameOver) return;

        setAIOverlay(true, TEXTS.ui.aiAttackTurn);
        const action = computeAIAttackChoice();

        setTimeout(() => {
            setAIOverlay(false);
            handleAttackClick(action);
        }, AI_THINK_MS);
    }

    function computeAIDefenseChoice(attackAction, defendingTeam, opts = {}) {
        const { isKeeperDuel = false } = opts;
        const r = Math.random();

        if (isKeeperDuel) {
            const attackerTeam = otherTeam(defendingTeam);
            const shooterSlot  = ball.number;

            const shooterShot   = getStat(attackerTeam, shooterSlot, "shot");
            const shooterAttack = getStat(attackerTeam, shooterSlot, "attack");
            const threat        = Math.max(shooterShot, shooterAttack);

            const keeperId = getKeeperId(defendingTeam);
            const gkStam  = keeperId ? getStamina(keeperId) : 100;

            if (threat >= 35) {
                if (gkStam >= 30) return (r < 0.25) ? "gk-special" : "hands";
                if (gkStam >= 15) return "hands";
                return "punch";
            }

            if (threat >= 25) {
                if (gkStam >= 30) return (r < 0.65) ? "hands" : "punch";
                if (gkStam >= 15) return (r < 0.55) ? "hands" : "punch";
                return "punch";
            }

            if (gkStam >= 30) return (r < 0.70) ? "punch" : "hands";
            return "punch";
        }

        switch (attackAction) {
            case "pass":
                if (r < 0.7) return "intercept";
                if (r < 0.9) return "tackle";
                return "block";

            case "dribble":
                if (r < 0.7) return "tackle";
                if (r < 0.9) return "intercept";
                return "block";

            case "shot":
                if (r < 0.75) return "block";
                return "intercept";

            case "special":
                if (r < 0.65) return "field-special";
                if (r < 0.85) return "block";
                return "intercept";

            default:
                return "intercept";
        }
    }

    function scheduleAIDefense(attack, defendingTeam) {
        if (!isAITeam(defendingTeam) || phase !== "defense" || !pendingAttack || isGameOver) return;

        setAIOverlay(true, TEXTS.ui.aiDefenseTurn);

        const isKeeperDuel =
            (pendingShotContext && pendingShotContext.stage === "keeper") ||
            ball.frontOfKeeper;

        const defense = computeAIDefenseChoice(attack, defendingTeam, { isKeeperDuel });

        setTimeout(() => {
            setAIOverlay(false);
            handleDefenseClick(defense);
        }, AI_THINK_MS);
    }

    // ==========================
    //   REMISE EN JEU
    // ==========================

    function resolveKickoffPass(attackTeam) {
        if (!isKickoff) return;
        isKickoff = false;

        const dmNumbers = [5, 6];
        const number    = dmNumbers[Math.floor(Math.random() * dmNumbers.length)];

        const attackerId = getPlayerId(attackTeam, ball.number);
        applyStaminaCost(attackerId, "attack", "pass");

        const defTeam     = otherTeam(attackTeam);
        const defFieldId  = getRandomFieldPlayer(defTeam);
        if (defFieldId) applyStaminaCost(defFieldId, "defenseField", "intercept");

        setMessage("Remise en jeu réussie !", `${TEAMS[attackTeam].label} joue court vers le n°${number}.`);
        pushLogEntry(TEXTS.logs.kickoffPassSuccessTitle, [`Vers n°${number}`, "Remise en jeu automatique (pas de duel)"]);

        animateAndThen(() => {
            restoreBasePositions();
            moveBallToPlayer(attackTeam, number);
            advanceTurn(attackTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   RÉSOLUTION : PASS
    // ==========================

    function resolvePass(attackTeam, defenseTeam, defenseAction) {
        const wasKickoff = isKickoff;
        isKickoff = false;

        const attackerId = getPlayerId(attackTeam, ball.number);

        const originZone = ball.zoneIndex;
        const originLane = ball.laneIndex;

        const defZone = getFacingZoneIndex(originZone);
        const defLane = originLane;

        const defenderId =
            getClosestPlayerInCell(defenseTeam, defZone, defLane) ||
            getRandomFieldPlayer(defenseTeam);

        const defenderSlot = defenderId ? parseInt(defenderId.slice(1), 10) : 6;

        let attackScore = attackBaseFor("pass", attackTeam, ball.number) * staminaFactor(attackerId);

        if (pendingClearanceBonus > 0) {
            attackScore += pendingClearanceBonus;
            pendingClearanceBonus = 0;
        }

        const defFactor = defenderId ? staminaFactor(defenderId) : 1.0;
        let defenseScore = defenseBaseFor(defenseAction, defenseTeam, defenderSlot, false) * defFactor;

        attackScore += rollDie();
        defenseScore += rollDie();

        showDuelDice(attackScore, defenseScore);

        const diff = attackScore - defenseScore;

        if (diff === 0) {
            pushLogEntry(
                "Duel équilibré — ballon libre",
                [
                    `Attaque: pass`,
                    `Défense: ${defenseAction}`,
                    `Duel stats : ${attackScore.toFixed(1)} - ${defenseScore.toFixed(1)} (0.0)`,
                    "Ballon envoyé aléatoirement à un joueur de champ",
                ],
                `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`
            );

            sendLooseBallRandomFieldPlayer();
            phase = "attack";
            pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(ball.team);
                showAttackBarForCurrentTeam();
                refreshUI();
            });

            return;
        }

        const ok = diff > 0;
        const duelResult = ok ? "attack" : "defense";

        const resultText = buildActionResultText({ attackAction: "pass", defenseAction, duelResult });

        const duelText =
            `Duel stats : ${attackScore.toFixed(1)} - ${defenseScore.toFixed(1)} ` +
            `(${diff > 0 ? "+" + diff.toFixed(1) : diff.toFixed(1)})`;

        const diceTag = `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`;

        applyStaminaCost(attackerId, "attack", "pass");
        if (defenderId) applyStaminaCost(defenderId, "defenseField", defenseAction);

        if (wasKickoff) {
            if (ok) {
                const dmNumbers = [5, 6];
                const number = dmNumbers[Math.floor(Math.random() * dmNumbers.length)];

                setMessage("Remise en jeu réussie !", `${TEAMS[attackTeam].label} joue court vers le n°${number}.`);
                pushLogEntry("Remise en jeu réussie", [`Attaque: pass`, `Défense: ${defenseAction}`, duelText], diceTag);

                animateAndThen(() => {
                    restoreBasePositions();
                    moveBallToPlayer(attackTeam, number);
                    advanceTurn(attackTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
            } else {
                const dmNumbersDef = [5, 6];
                const number = dmNumbersDef[Math.floor(Math.random() * dmNumbersDef.length)];

                setMessage("Remise en jeu ratée !", `${TEAMS[defenseTeam].label} intercepte avec le n°${number}.`);
                pushLogEntry(TEXTS.logs.kickoffPassFailTitle, [`Attaque: pass`, `Défense: ${defenseAction}`, duelText], diceTag);

                animateAndThen(() => {
                    restoreBasePositions();
                    moveBallToPlayer(defenseTeam, number);
                    advanceTurn(defenseTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
            }
            return;
        }

        let targetZone = ball.zoneIndex;
        let targetLane = ball.laneIndex;

        if (ball.zoneIndex < 3) {
            targetZone = ball.zoneIndex + 1;

            const laneOptions = [ball.laneIndex];
            if (ball.laneIndex > 0) laneOptions.push(ball.laneIndex - 1);
            if (ball.laneIndex < laneY.length - 1) laneOptions.push(ball.laneIndex + 1);

            targetLane = laneOptions[Math.floor(Math.random() * laneOptions.length)];
        } else {
            const lanes = [0, 1, 2];
            targetLane = lanes[Math.floor(Math.random() * lanes.length)];
        }

        if (ok) {
            resetLastDribbler();

            const receiverNumber = pickReceiverInCell(
                attackTeam, targetZone, targetLane, ball.number, ball.number,
            );

            moveBallToPlayer(attackTeam, receiverNumber);

            setMessage(
                `${resultText} !`,
                `${TEAMS[attackTeam].label} trouve le n°${receiverNumber} en zone ${targetZone + 1}, ligne ${targetLane + 1}.`,
            );

            pushLogEntry(
                TEXTS.logs.passSuccessTitle,
                [`Vers n°${receiverNumber} (zone ${targetZone + 1}, ligne ${targetLane + 1})`, `Défense: ${defenseAction}`, duelText],
                diceTag
            );

            animateAndThen(() => {
                advanceTurn(attackTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
        } else {
            resetLastDribbler();

            const number = defenderId ? parseInt(defenderId.slice(1), 10) : 6;
            moveBallToPlayer(defenseTeam, number);

            setMessage(
                `${resultText} !`,
                `${TEAMS[defenseTeam].label} récupère avec le n°${number} (zone ${ball.zoneIndex + 1}, ligne ${ball.laneIndex + 1}).`,
            );

            pushLogEntry(
                TEXTS.logs.passFailTitle,
                [`Attaque: pass depuis zone ${originZone + 1}`, `Défense: ${defenseAction}`, duelText],
                diceTag
            );

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
        }
    }

    // ==========================
    //   RÉSOLUTION : DRIBBLE
    // ==========================

    function resolveDribble(attackTeam, defenseTeam, defenseAction) {
        if (ball.frontOfKeeper) {
            setMessage(TEXTS.ui.dribbleForbiddenMain, TEXTS.ui.dribbleForbiddenSub);
            pushLogEntry(TEXTS.logs.dribbleRefusedTitle, [TEXTS.logs.dribbleRefusedDetail]);
            phase = "attack";
            pendingAttack = null;
            return;
        }

        const attackerId = getPlayerId(attackTeam, ball.number);

        const oldZone = ball.zoneIndex;
        const lane = ball.laneIndex;

        const defZone = getFacingZoneIndex(oldZone);
        const defLane = lane;

        const defenderId =
            getClosestPlayerInCell(defenseTeam, defZone, defLane) ||
            getRandomFieldPlayer(defenseTeam);

        const defenderSlot = defenderId ? parseInt(defenderId.slice(1), 10) : 6;

        let attackScore = attackBaseFor("dribble", attackTeam, ball.number) * staminaFactor(attackerId);

        if (pendingClearanceBonus > 0) {
            attackScore += pendingClearanceBonus;
            pendingClearanceBonus = 0;
        }

        const defFactor = defenderId ? staminaFactor(defenderId) : 1.0;
        let defenseScore = defenseBaseFor(defenseAction, defenseTeam, defenderSlot, false) * defFactor;

        attackScore += rollDie();
        defenseScore += rollDie();

        showDuelDice(attackScore, defenseScore);

        const diff = attackScore - defenseScore;

        const duelText =
            `Duel stats : ${attackScore.toFixed(1)} - ${defenseScore.toFixed(1)} ` +
            `(${diff > 0 ? "+" + diff.toFixed(1) : diff.toFixed(1)})`;

        const diceTag = `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`;

        if (diff === 0) {
            pushLogEntry(
                "Duel équilibré — ballon dévié",
                [`Attaque: dribble`, `Défense: ${defenseAction}`, duelText],
                diceTag
            );

            sendLooseBallRandomFieldPlayer();

            phase = "attack";
            pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(ball.team);
                showAttackBarForCurrentTeam();
                refreshUI();
            });

            return;
        }

        const ok = diff > 0;
        const duelResult = ok ? "attack" : "defense";

        const resultText = buildActionResultText({ attackAction: "dribble", defenseAction, duelResult });

        applyStaminaCost(attackerId, "attack", "dribble");
        if (defenderId) applyStaminaCost(defenderId, "defenseField", defenseAction);

        const prefix = attackTeam === "internal" ? "I" : "E";
        const carrierId = prefix + String(ball.number);
        const carrierEl = rootEl.querySelector(`[data-player="${carrierId}"]`);

        if (ok) {
            if (oldZone < 3) {
                const newZone = Math.min(3, oldZone + 1);
                lastDribblerId = carrierId;

                if (carrierEl && ballEl) {
                    const currentY = parseFloat(carrierEl.style.top);
                    const center = getCellCenter(attackTeam, newZone, lane);

                    carrierEl.style.left = center.x + "%";
                    carrierEl.style.top = currentY + "%";

                    ballEl.style.left = center.x + "%";
                    ballEl.style.top = currentY + "%";
                }

                ball.zoneIndex = newZone;
                ball.laneIndex = lane;

                setMessage(
                    `${resultText} !`,
                    `${TEAMS[attackTeam].label} avance en zone ${newZone + 1} sur la même ligne.`
                );

                pushLogEntry(
                    `${resultText} !`,
                    [`Vers zone ${newZone + 1}`, `Défense: ${defenseAction}`, duelText],
                    diceTag
                );

                animateAndThen(() => {
                    advanceTurn(attackTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
            } else {
                lastDribblerId = carrierId;

                const y = laneY[lane];
                const xFront = FIELD_RULES.GK_FRONT_X[attackTeam];

                if (carrierEl && ballEl) {
                    carrierEl.style.left = xFront + "%";
                    carrierEl.style.top = y + "%";
                    ballEl.style.left = xFront + "%";
                    ballEl.style.top = y + "%";
                }

                ball.frontOfKeeper = true;

                setMessage(
                    `${resultText} !`,
                    "Face au gardien ! Prochaine action : tir ou tir spécial."
                );

                pushLogEntry(
                    `${resultText} !`,
                    [`Défense: ${defenseAction}`, duelText],
                    diceTag
                );

                animateAndThen(() => {
                    advanceTurn(attackTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
            }
        } else {
            resetLastDribbler();

            const number = defenderId ? parseInt(defenderId.slice(1), 10) : 6;
            moveBallToPlayer(defenseTeam, number);

            setMessage(
                `${resultText} !`,
                `${TEAMS[defenseTeam].label} récupère avec le n°${number} (zone ${ball.zoneIndex + 1}, ligne ${ball.laneIndex + 1}).`
            );

            pushLogEntry(
                `${resultText} !`,
                [`Attaque depuis zone ${oldZone + 1}`, `Défense: ${defenseAction}`, duelText],
                diceTag
            );

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
        }
    }

    // ==========================
    //   ANIM TIR & GK
    // ==========================

    function animateGoalThenReset(attackTeam, afterGoalCallback) {
        if (!ballEl) { if (afterGoalCallback) afterGoalCallback(); return; }

        const goalX = FIELD_RULES.GOAL_X[attackTeam];
        const goalY = FIELD_RULES.GOAL_Y;

        ballEl.style.left = goalX + "%";
        ballEl.style.top  = goalY + "%";

        animateAndThen(() => { if (afterGoalCallback) afterGoalCallback(); });
    }

    function animateShotToKeeper(defenseTeam, afterAnimation) {
        const keeperEl = rootEl.querySelector(
            defenseTeam === "internal"
                ? ".player.internal.goalkeeper"
                : ".player.external.goalkeeper",
        );

        if (!keeperEl || !ballEl) {
            if (afterAnimation) afterAnimation();
            return;
        }

        const x = parseFloat(keeperEl.style.left);
        const y = parseFloat(keeperEl.style.top);

        ballEl.style.left = x + "%";
        ballEl.style.top  = y + "%";

        animateAndThen(() => { if (afterAnimation) afterAnimation(); });
    }

    // ==========================
    //   RELANCE / DEGAGEMENT GK
    // ==========================

    function performKeeperClearance(defenseTeam, defenseAction) {
        const originZone = ball.zoneIndex;
        const originLane = ball.laneIndex;

        const forwardZone = (lines) => Math.max(0, originZone - lines);

        pendingClearanceBonus =
            defenseAction === "hands" ? 7 :
                defenseAction === "punch" ? 4 :
                    5;

        let targetZone = originZone;
        const r = Math.random();

        if (defenseAction === "hands") {
            targetZone = forwardZone(r < 0.65 ? 1 : 2);
        } else if (defenseAction === "punch") {
            targetZone = forwardZone(r < 0.75 ? 1 : 2);
        } else {
            if (r < 0.55) targetZone = forwardZone(2);
            else if (r < 0.90) targetZone = forwardZone(3);
            else targetZone = forwardZone(4);
        }

        const laneOptions = [originLane];
        if (originLane > 0) laneOptions.push(originLane - 1);
        if (originLane < laneY.length - 1) laneOptions.push(originLane + 1);
        const targetLane = laneOptions[Math.floor(Math.random() * laneOptions.length)];

        const receiverNumber = pickReceiverInCell(defenseTeam, targetZone, targetLane, 6, null);

        moveBallToPlayer(defenseTeam, receiverNumber);

        const label = TEAMS[defenseTeam].label;
        const actionLabel =
            defenseAction === "hands" ? "capte et relance" :
                defenseAction === "punch" ? "repousse au poing" :
                    "repousse en spécial";

        setMessage("Relance du gardien !", `${label} ${actionLabel} vers la zone ${targetZone + 1}.`);

        pushLogEntry(
            "Relance du gardien",
            [
                `Action: ${defenseAction}`,
                `Bonus relance: +${pendingClearanceBonus}`,
                `Vers zone ${targetZone + 1}, ligne ${targetLane + 1}`,
                `Receveur: #${receiverNumber}`,
            ]
        );
    }

    // ==========================
    //   DUEL GARDIEN (TIR CADRÉ/DE LOIN)
    // ==========================

    function resolveShotKeeperDuel(ctx, defenseAction) {
        const { attackTeam, defenseTeam, originZone, isSpecial, gkAttackBase, logParts } = ctx;

        const attackerId = getPlayerId(attackTeam, ball.number);
        const keeperId   = getKeeperId(defenseTeam);

        // ✅ FIX: gkAttackBase est “base” sans stamina (sinon double stamina)
        let attackScore  = gkAttackBase * staminaFactor(attackerId);

        const gkFactor   = keeperId ? staminaFactor(keeperId) : 1.0;
        let defenseScore = defenseBaseFor(defenseAction, defenseTeam, 1, true) * gkFactor;

        attackScore  += rollDie();
        defenseScore += rollDie();

        showDuelDice(attackScore, defenseScore);

        const diff = attackScore - defenseScore;

        if (diff === 0) {
            ball.frontOfKeeper = false;
            resetLastDribbler();

            applyStaminaCost(attackerId, "attack", isSpecial ? "special" : "shot");
            if (keeperId) applyStaminaCost(keeperId, "defenseGK", defenseAction);

            pushLogEntry(
                "Duel tir vs gardien — équilibre",
                [
                    `Tir depuis zone ${originZone + 1}`,
                    `Duel stats : ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)} (0.0)`,
                    "Ballon dévié — possession aléatoire",
                ],
                `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`
            );

            sendLooseBallRandomFieldPlayer();

            phase = "attack";
            pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(ball.team);
                showAttackBarForCurrentTeam();
                refreshUI();
            });

            return;
        }

        const ok = diff > 0;

        const duelText =
            `Duel tir vs gardien : ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)} ` +
            `(${diff > 0 ? "+" + diff.toFixed(1) : diff.toFixed(1)})`;

        const diceTag = `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`;

        logParts.push(duelText);

        ball.frontOfKeeper = false;
        resetLastDribbler();

        applyStaminaCost(attackerId, "attack", isSpecial ? "special" : "shot");
        if (keeperId) applyStaminaCost(keeperId, "defenseGK", defenseAction);

        if (ok) {
            score[attackTeam]++;

            setMessage(
                isSpecial
                    ? `BUT SPÉCIAL à distance pour ${TEAMS[attackTeam].label} !`
                    : `BUT de loin pour ${TEAMS[attackTeam].label} !`,
                `Le tir bat le gardien. Score : ${score.internal} - ${score.external}.`
            );

            pushLogEntry(
                isSpecial ? TEXTS.logs.longShotGoalSpecialTitle : TEXTS.logs.longShotGoalTitle,
                [`Tir depuis zone ${originZone + 1}`, ...logParts],
                diceTag
            );

            const newTeam   = defenseTeam;
            const newNumber = 8;

            animateGoalThenReset(attackTeam, () => {
                isKickoff = true;
                applyKickoffPositions();
                moveBallToPlayer(newTeam, newNumber);
                advanceTurn(newTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });

            phase = "attack";
            pendingAttack = null;
            return;
        }

        pushLogEntry(
            TEXTS.logs.longShotSavedTitle,
            [`Tir depuis zone ${originZone + 1}`, ...logParts],
            diceTag
        );

        performKeeperClearance(defenseTeam, defenseAction);

        phase = "attack";
        pendingAttack = null;

        animateAndThen(() => {
            advanceTurn(defenseTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   RÉSOLUTION : TIR
    // ==========================

    function resolveShot(attackTeam, defenseTeam, defenseAction, isSpecial = false) {
        const originZone = ball.zoneIndex;
        const originLane = ball.laneIndex;
        const attackerId = getPlayerId(attackTeam, ball.number);
        const attackType = isSpecial ? "special" : "shot";

        // CAS 1 : FACE AU GARDIEN
        if (ball.frontOfKeeper) {
            const keeperId = getKeeperId(defenseTeam);

            let attackScore =
                attackBaseFor(attackType, attackTeam, ball.number) * staminaFactor(attackerId);

            if (pendingClearanceBonus > 0) {
                attackScore += pendingClearanceBonus;
                pendingClearanceBonus = 0;
            }

            const gkFactor = keeperId ? staminaFactor(keeperId) : 1.0;

            let defenseScore =
                defenseBaseFor(defenseAction, defenseTeam, 1, true) * gkFactor;

            attackScore += rollDie();
            defenseScore += rollDie();

            showDuelDice(attackScore, defenseScore);

            const diff = attackScore - defenseScore;

            if (diff === 0) {
                sendLooseBallRandomFieldPlayer();

                phase = "attack";
                pendingAttack = null;

                animateAndThen(() => {
                    advanceTurn(ball.team);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });

                return;
            }

            const ok = diff > 0;

            const duelResult = ok ? "attack" : "defense";
            const resultText = buildActionResultText({
                attackAction: attackType,
                defenseAction,
                duelResult,
            });

            const duelText =
                `Duel tir vs gardien : ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)} ` +
                `(${diff > 0 ? "+" + diff.toFixed(1) : diff.toFixed(1)})`;

            const diceTag = `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`;

            ball.frontOfKeeper = false;
            resetLastDribbler();
            phase = "attack";
            pendingAttack = null;

            applyStaminaCost(attackerId, "attack", attackType);
            if (keeperId) applyStaminaCost(keeperId, "defenseGK", defenseAction);

            if (ok) {
                score[attackTeam]++;

                setMessage(
                    isSpecial
                        ? `BUT SPÉCIAL pour ${TEAMS[attackTeam].label} !`
                        : `BUT pour ${TEAMS[attackTeam].label} !`,
                    `Le tir ${isSpecial ? "spécial " : ""}trompe le gardien. Score : ${score.internal} - ${score.external}.`,
                );

                pushLogEntry(resultText, [`Zone ${originZone + 1}`, duelText], diceTag);

                const newTeam = defenseTeam;
                const newNumber = 8;

                animateGoalThenReset(attackTeam, () => {
                    isKickoff = true;
                    applyKickoffPositions();
                    moveBallToPlayer(newTeam, newNumber);
                    advanceTurn(newTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });

                return;
            }

            pushLogEntry(resultText, [`Zone ${originZone + 1}`, duelText], diceTag);

            performKeeperClearance(defenseTeam, defenseAction);

            animateShotToKeeper(defenseTeam, () => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });

            return;
        }

        // CAS 2 : TIR AVEC DÉFENSE DE CHAMP
        const logParts = [];
        const facingZone = getFacingZoneIndex(originZone);

        const defenderId =
            getClosestPlayerInCell(defenseTeam, facingZone, originLane) ||
            getRandomFieldPlayer(defenseTeam);

        const defenderSlot = defenderId ? parseInt(defenderId.slice(1), 10) : 6;

        let fieldAttackScore =
            attackBaseFor(isSpecial ? "special" : "shot", attackTeam, ball.number) *
            staminaFactor(attackerId);

        if (pendingClearanceBonus > 0) {
            fieldAttackScore += pendingClearanceBonus;
            pendingClearanceBonus = 0;
        }

        const defFactor = defenderId ? staminaFactor(defenderId) : 1.0;

        let fieldDefenseScore =
            defenseBaseFor(defenseAction, defenseTeam, defenderSlot, false) * defFactor;

        fieldAttackScore += rollDie();
        fieldDefenseScore += rollDie();

        showDuelDice(fieldAttackScore, fieldDefenseScore);

        const diffField = fieldAttackScore - fieldDefenseScore;

        if (diffField === 0) {
            sendLooseBallRandomFieldPlayer();

            phase = "attack";
            pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(ball.team);
                showAttackBarForCurrentTeam();
                refreshUI();
            });

            return;
        }

        const passField = diffField > 0;

        const duelResultField = passField ? "attack" : "defense";
        const resultTextField = buildActionResultText({
            attackAction: isSpecial ? "special" : "shot",
            defenseAction,
            duelResult: duelResultField,
        });

        const fieldText =
            `Duel tir vs défense : ${fieldAttackScore.toFixed(1)}-${fieldDefenseScore.toFixed(1)} ` +
            `(${diffField > 0 ? "+" + diffField.toFixed(1) : diffField.toFixed(1)})`;

        const diceTagField = `🎲 ${fieldAttackScore.toFixed(1)}-${fieldDefenseScore.toFixed(1)}`;

        logParts.push(fieldText);

        applyStaminaCost(attackerId, "attack", isSpecial ? "special" : "shot");
        if (defenderId) applyStaminaCost(defenderId, "defenseField", defenseAction);

        if (!passField) {
            const number = defenderId ? parseInt(defenderId.slice(1), 10) : 6;
            moveBallToPlayer(defenseTeam, number);

            setMessage(
                "Tir contré !",
                `${TEAMS[defenseTeam].label} contre le tir et récupère avec le n°${number} (zone ${ball.zoneIndex + 1}, ligne ${ball.laneIndex + 1}).`,
            );

            pushLogEntry(
                resultTextField,
                [`Tir depuis zone ${originZone + 1}`, `Défense: ${defenseAction}`, fieldText],
                diceTagField
            );

            phase = "attack";
            pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });

            return;
        }

        pushLogEntry(resultTextField, [`Tir depuis zone ${originZone + 1}`, fieldText], diceTagField);

        setMessage("Tir cadré !", `${TEAMS[defenseTeam].label} : le gardien va devoir intervenir.`);

        // ✅ FIX: gkAttackBase SANS staminaFactor (sinon double appliqué dans resolveShotKeeperDuel)
        const linesBehind = facingZone;
        const gkAttackBase =
            attackBaseFor(attackType, attackTeam, ball.number) -
            (linesBehind * DUEL_RULES.SHOT_DISTANCE_PENALTY_PER_LINE);

        const targetZone = 3;
        const center = getCellCenter(attackTeam, targetZone, originLane);

        ball.zoneIndex = targetZone;
        ball.laneIndex = originLane;

        if (ballEl) {
            ballEl.style.left = center.x + "%";
            ballEl.style.top = center.y + "%";
        }

        pendingShotContext = {
            stage: "keeper",
            attackTeam,
            defenseTeam,
            originZone,
            originLane,
            isSpecial,
            gkAttackBase,
            logParts,
        };

        animateShotToKeeper(defenseTeam, () => {
            const mode = `mode-defense-${defenseTeam}`;
            setActionBar(buildDefenseGKHTML(), mode);

            setMessage(
                "Tir cadré !",
                `${TEAMS[defenseTeam].label} : le gardien choisit Arrêt main / Dégagement poing / Special.`,
            );

            phase = "defense";
            pendingAttack = attackType;

            if (isAITeam(defenseTeam)) scheduleAIDefense(attackType, defenseTeam);
        });
    }

    // ==========================
    //   HANDLERS
    // ==========================

    function handleAttackClick(action) {
        if (isGameOver || isAnimating) return;
        if (turns >= GAME_RULES.MAX_TURNS || phase !== "attack") return;
        if (!["shot","pass","dribble","special"].includes(action)) return;

        if (isKickoff) {
            if (action !== "pass") return;
            resolveKickoffPass(currentTeam);
            return;
        }

        if (ball.frontOfKeeper && action !== "shot" && action !== "special") return;

        pendingAttack = action;
        phase = "defense";

        const defTeam = otherTeam(currentTeam);
        setDefenderPreviewFor(action, defTeam);

        const mode = `mode-defense-${defTeam}`;
        let html;

        if (action === "shot" || action === "special") {
            const isCloseRange = ball.frontOfKeeper;

            if (isCloseRange) {
                html = buildDefenseGKHTML();
                setMessage(
                    `${TEAMS[currentTeam].label} prépare un ${action === "special" ? "TIR SPÉCIAL" : "TIR"} !`,
                    `${TEAMS[defTeam].label} (gardien) : choisis Arrêt main / Dégagement poing / Special.`,
                );
            } else {
                html = buildDefenseFieldHTML();
                setMessage(
                    `${TEAMS[currentTeam].label} tente un ${action === "special" ? "TIR SPÉCIAL" : "TIR"} !`,
                    `${TEAMS[defTeam].label} : choisis Block / Intercept / Tackle / Special.`,
                );
            }
        } else {
            html = buildDefenseFieldHTML();
            setMessage(
                `${TEAMS[currentTeam].label} prépare un ${action.toUpperCase()} !`,
                `${TEAMS[defTeam].label} : choisis Block / Intercept / Tackle / Special.`,
            );
        }

        setActionBar(html, mode);

        if (isAITeam(defTeam)) scheduleAIDefense(action, defTeam);
    }

    function handleDefenseClick(defense) {
        if (turns >= GAME_RULES.MAX_TURNS || isAnimating || phase !== "defense" || !pendingAttack) return;

        const isKeeperDuel =
            (pendingShotContext && pendingShotContext.stage === "keeper") ||
            ball.frontOfKeeper;

        if (isKeeperDuel && !["hands", "punch", "gk-special"].includes(defense)) {
            defense = "hands";
        }

        const attackTeam  = currentTeam;
        const defenseTeam = otherTeam(currentTeam);
        const attack      = pendingAttack;

        if ((attack === "shot" || attack === "special") &&
            pendingShotContext &&
            pendingShotContext.stage === "keeper") {
            resolveShotKeeperDuel(pendingShotContext, defense);
            pendingShotContext = null;
            return;
        }

        if (attack === "pass") {
            phase = "attack";
            pendingAttack = null;
            resolvePass(attackTeam, defenseTeam, defense);
        } else if (attack === "dribble") {
            phase = "attack";
            pendingAttack = null;
            resolveDribble(attackTeam, defenseTeam, defense);
        } else if (attack === "shot") {
            resolveShot(attackTeam, defenseTeam, defense, false);
        } else if (attack === "special") {
            resolveShot(attackTeam, defenseTeam, defense, true);
        }
    }

    function showPlayerCard(playerId) {
        if (!playerId) return;
        const team   = playerId.startsWith("I") ? "internal" : "external";
        const number = parseInt(playerId.slice(1), 10);
        const prefix = (team === "internal") ? "home" : "away";
        updateSideCard(prefix, team, number);
    }

    function bindPlayerClickHandlers() {
        $$(".player").forEach((el) => {
            el.addEventListener("click", () => {
                showPlayerCard(el.dataset.player);
            });
        });
    }

    // ==========================
    //   REFRESH UI
    // ==========================

    function refreshUI() {
        updateScoreUI();
        updateTeamCard();
    }

    // ==========================
    //   Init
    // ==========================

    function init() {
        initBasePositions();
        applyRosterToDOM();
        initStamina();

        bindPlayerClickHandlers();

        turns              = 0;
        currentTeam        = "internal";
        score              = { internal: 0, external: 0 };
        phase              = "attack";
        pendingAttack      = null;
        isAnimating        = false;
        lastDribblerId     = null;
        isKickoff          = true;
        isGameOver         = false;
        pendingShotContext = null;
        pendingClearanceBonus = 0;

        applyKickoffPositions();
        moveBallToPlayer("internal", 8);
        updateSideCard("home", "internal", 8);
        updateSideCard("away", "external", 8);

        setMessage(TEXTS.ui.gameStartMain, TEXTS.ui.gameStartSub);
        showAttackBarForCurrentTeam();
        refreshUI();

        if (modeOnePlayerBtn) {
            const syncModeLabel = () => {
                modeOnePlayerBtn.classList.toggle("active", onePlayerMode);
                modeOnePlayerBtn.textContent = onePlayerMode ? "Mode 1 joueur" : "Mode 2 joueurs";
            };

            syncModeLabel();

            modeOnePlayerBtn.addEventListener("click", () => {
                onePlayerMode = !onePlayerMode;
                syncModeLabel();
                setAIOverlay(false);
            });
        }

        if (controlledTeamSelect) {
            controlledTeamSelect.value = controlledTeam;
            controlledTeamSelect.addEventListener("change", () => {
                controlledTeam = controlledTeamSelect.value === "external" ? "external" : "internal";
            });
        }

        if (teamNameInternalEl) teamNameInternalEl.textContent = TEAMS.internal.label;
        if (teamNameExternalEl) teamNameExternalEl.textContent = TEAMS.external.label;
    }

    init();
}
