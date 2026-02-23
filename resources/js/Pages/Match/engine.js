// resources/js/Pages/Match/engine.js

// ==========================
//   CONSTANTES
// ==========================
const ANIM_MS     = 300; // ralenti pour suivre les transitions CSS
const AI_THINK_MS = 200; // délai "réflexion IA"
const GK_HOLD_MS = 400; // ralenti pour suivre la récupèration de balle du gardien
const ACTION_BAR_FADE_MS  = 100 // Durée des transitions fade-in/out de la barre
const DIE_SIDES   = 20;  // nombre de faces du dé utilisé dans les calculs de duel


// ==========================
//   RÈGLES DE PARTIE
// ==========================
const GAME_RULES = { MAX_TURNS: 40 };

// ==========================
//   RÈGLES DE DUEL
// ==========================
// Bonus / malus des duels
const DUEL_RULES = {
    GOOD_COUNTER_BONUS: 2,               // bonus pour la "bonne" défense (intercept, tackle, block...)
    GENERIC_ATTACK_BONUS: 2,             // petit bonus pour l'attaque si la défense n'est pas optimale
    SHOT_DISTANCE_PENALTY_PER_LINE: 1,   // malus par ligne de distance sur les tirs de loin
};

// ==========================
//   ENDURANCE
// ==========================
const ENDURANCE_DEFAULT = 100;

const STAMINA_FACTORS = {
    HIGH: 1.0,
    MID: 0.95,
    LOW: 0.88,
    CRIT: 0.8,
    EXHAUSTED: 0.75,
};

const STAMINA_COST_GLOBAL_SCALE = 0.85;

// ==========================
//   RÈGLES TERRAIN
// ==========================
const FIELD_RULES = {
    GOAL_X: { internal: 97, external: 3 },
    GOAL_Y: 50,
    GK_FRONT_X: { internal: 88, external: 12 },
};

// ==========================
//   RÈGLES IA
// ==========================
const AI_RULES = {
    ATTACK: {
        EARLY_PASS_PROB: 0.7,
        FRONT_GK_SPECIAL_PROB: 0.15,
    },
    DEFENSE: {},
};

// ==========================
//   BONUS PAR POSTE
// ==========================
const POSITION_BONUS = {
    GK: { gk: 0.03 },
    DF: { defend: 0.03, tackle: 0.02, block: 0.03 },
    MF: { pass: 0.03, dribble: 0.02 },
    FW: { shot: 0.04, attack: 0.03 },
};

// ==========================
//   TEXTES (MAJ + clés manquantes)
// ==========================
const TEXTS = {
    teams: { internal: "Domicile", external: "Extérieur" },
    ui: {
        gameStartMain: "Le match commence !",
        gameStartSub: "Internal a la balle. Remise en jeu : passe obligatoire.",
        chooseAttackSub: "Choisis Pass / Dribble / Shot.",

        dribbleForbiddenMain: "Face au gardien !",
        dribbleForbiddenSub: "Tu dois tirer, plus de dribble possible.",

        aiAttackTurn: "Tour de l'IA (attaque)",
        aiDefenseTurn: "Tour de l'IA (défense)",
        aiThinkingDefault: "Tour de l'IA…",

        matchEndMain: "Fin du match !",
        matchEndPrefix: "Score final ",

        specialCooldownMain: "Special indisponible",
        specialCooldownSub: "Attends le cooldown avant de relancer un Special.",

        keeperRestartMain: "Relance du gardien",
        keeperRestartSub: "Passe obligatoire après une relance.",

        duelTieMain: "Duel équilibré !",
        duelTieSub: "{team} récupère (égalité).",

        frontOfKeeperMain: "Face au gardien !",
        frontOfKeeperSub: "Prochaine action : tir ou tir spécial.",

        // ✅ SHOT flow
        shotBlockedMain: "Tir contré !",
        shotBlockedSub: "{team} récupère avec le n°{number}.",
        shotRecoveredMain: "Tir récupéré !",
        shotRecoveredSub: "{team} récupère avec le n°{number}.",

        shotOnTargetMain: "Tir cadré !",
        shotOnTargetSub: "{team} : le gardien va intervenir.",
        shotGKChoiceSub: "{team} : Arrêt main / Dégagement poing / Special.",

        // ✅ GOAL
        goalMain: "BUT pour {team} !",
        goalSpecialMain: "BUT SPÉCIAL pour {team} !",
        goalSub: "Score : {scoreInternal} - {scoreExternal}.",
    },
    logs: {
        kickoffTitle: "Coup d'envoi",
        kickoffDetail: "Internal engage (pass obligatoire)",

        passSuccessTitle: "Passe réussie",
        passFailTitle: "Passe interceptée",
        passRecoveredTitle: "Passe récupérée",

        dribbleRefusedTitle: "Dribble refusé (face au gardien)",
        dribbleRefusedDetail: "Action non autorisée",
        dribbleRecoveredTitle: "Dribble récupéré",
        dribbleSuccessTitle: "Dribble réussi",
        dribbleFailTitle: "Dribble stoppé",

        longShotGoalTitle: "Tir de loin – BUT",
        longShotSavedTitle: "Tir de loin – arrêté",

        shotGoalTitle: "Tir – BUT",
        shotSavedTitle: "Tir – arrêté",
        shotRecoveredTitle: "Tir récupéré",
        shotBlockedTitle: "Tir contré",
        specialRecoveredTitle: "Special récupéré",

        matchEndTitle: "Fin du match",
        frontOfKeeperTitle: "Dribble réussi – face au gardien",

        // ✅ optionnels (si tu veux les utiliser)
        shotGKEqualTitle: "Tir vs gardien — égalité",
        shotGoalSpecialTitle: "Tir spécial – BUT",
        keeperRestartMain: "Relance du gardien",
    },
    cards: {
        attack: {
            shot: { icon: "⚽️", title: "Shot", sub: "Puissant tir" },
            pass: { icon: "➡️", title: "Pass", sub: "Passe avant" },
            dribble: { icon: "🌀", title: "Dribble", sub: "Dribble un adversaire" },
            special: { icon: "🔥", title: "Special", sub: "Action spéciale" },
        },
        defenseField: {
            block: { icon: "🧱", title: "Block", sub: "Contre de tir" },
            intercept: { icon: "✋", title: "Intercept", sub: "Couper une passe" },
            tackle: { icon: "⚔️", title: "Tackle", sub: "Intervenir un dribble" },
            "field-special": { icon: "🔥", title: "Special", sub: "Action spéciale" },
        },
        defenseGK: {
            hands: { icon: "🧤", title: "Arrêt main", sub: "Capte le tir proprement" },
            punch: { icon: "👊", title: "Dégagement poing", sub: "Repousse le ballon au loin" },
            "gk-special": { icon: "🔥", title: "Special", sub: "Action spéciale" },
        },
    },
};

// ==========================
//   GRILLE
// ==========================
// 5 zones (Z0..Z4) qui couvrent tout le terrain (0% -> 100%)
const ZONE_BOUNDS_INTERNAL = [0, 20, 40, 60, 80, 100]; // 5 zones => index 0..4
const laneY = [25, 50, 75]; // 3 lanes => index 0..2

// Helper central : dernier index de zone (ex: 4)
const MAX_ZONE_INDEX = ZONE_BOUNDS_INTERNAL.length - 2;


// ==========================
//   STATS "MATCH" (UI costs + base powers)
// ==========================
const STATS = {
    attack: {
        shot: { power: 10, cost: 10 },
        pass: { power: 10, cost: 5 },
        dribble: { power: 10, cost: 5 },
        special: { power: 12, cost: 20 },
    },
    defenseField: {
        block: { power: 10, cost: 5 },
        intercept: { power: 10, cost: 5 },
        tackle: { power: 10, cost: 5 },
        "field-special": { power: 12, cost: 20 },
    },
    defenseGK: {
        hands: { power: 10, cost: 10 },
        punch: { power: 10, cost: 5 },
        "gk-special": { power: 12, cost: 20 },
    },
};

let TEAMS = null;

// ==========================
//   EXPORT
// ==========================

// Initialise le moteur de match (state, UI, IA, handlers) et lance le kickoff.
export function initMatchEngine(rootEl, config = {}) {
    if (!rootEl) return;

    const matchConfig = config || {};

    TEAMS = {
        internal: { id: "internal", label: matchConfig.teams?.internal?.name ?? TEXTS.teams.internal },
        external: { id: "external", label: matchConfig.teams?.external?.name ?? TEXTS.teams.external },
    };

    const controlMode = matchConfig.controlMode ?? "both";
    let onePlayerMode = (controlMode === "single");
    let controlledTeam = matchConfig.controlledSide ?? "internal";

    // Helpers DOM query scoped au root.
    const $ = (sel) => rootEl.querySelector(sel);
    // Helpers DOM queryAll scoped au root.
    const $$ = (sel) => rootEl.querySelectorAll(sel);

    // ==========================
    //   ROSTERS / STATS SERVICE
    // ==========================
    class RosterService {
        // Construit un service de stats/rosters prêt à calculer les bases de duel.
        constructor({ rosters, statCoef, positionBonus }) {
            this.rosters = rosters;
            this.STAT_COEF = statCoef;
            this.POSITION_BONUS = positionBonus;
        }

        // Construit le roster à partir du matchConfig et normalise photos + stats.
        static create(matchConfig, { statCoef, positionBonus }) {
            const rosters = { internal: new Map(), external: new Map() };

            // Garantit une liste de joueurs exploitable (array).
            const normalizePlayers = (list = []) => (Array.isArray(list) ? list : []);

            // Résout la meilleure source de photo parmi plusieurs clés possibles.
            const resolvePhoto = (p) =>
                p?.photo_url ??
                p?.photo ??
                p?.image_url ??
                p?.avatar_url ??
                p?.photo_path ??
                p?.portrait_url ??
                p?.portrait ??
                p?.picture_url ??
                p?.picture ??
                null;

            // Normalise la structure des stats joueur (nested stats ou champs plats).
            const resolveStats = (p) => {
                const s = p?.stats ?? null;
                return s
                    ? {
                        shot: s.shot ?? 0,
                        pass: s.pass ?? 0,
                        dribble: s.dribble ?? 0,
                        tackle: s.tackle ?? 0,
                        intercept: s.intercept ?? 0,
                        block: s.block ?? 0,
                        attack: s.attack ?? 0,
                        defense: s.defense ?? 0,
                        speed: s.speed ?? 0,
                        stamina: s.stamina ?? 0,
                        hand_save: s.hand_save ?? 0,
                        punch_save: s.punch_save ?? 0,
                    }
                    : {
                        shot: p?.shot ?? 0,
                        pass: p?.pass ?? 0,
                        dribble: p?.dribble ?? 0,
                        tackle: p?.tackle ?? 0,
                        intercept: p?.intercept ?? 0,
                        block: p?.block ?? 0,
                        attack: p?.attack ?? 0,
                        defense: p?.defense ?? 0,
                        speed: p?.speed ?? 0,
                        stamina: p?.stamina ?? 0,
                        hand_save: p?.hand_save ?? 0,
                        punch_save: p?.punch_save ?? 0,
                    };
            };

            // Alimente un roster (internal/external) avec 11 slots (fallback si manquant).
            const seedTeam = (teamKey) => {
                const players = normalizePlayers(matchConfig.teams?.[teamKey]?.players);
                const take = players.slice(0, 11);

                for (let slot = 1; slot <= 11; slot++) {
                    const p = take[slot - 1] ?? null;

                    rosters[teamKey].set(
                        slot,
                        p
                            ? {
                                id: p.id ?? null,
                                number: p.number ?? slot,
                                firstname: p.firstname ?? "",
                                lastname: p.lastname ?? "",
                                position: p.position ?? "",
                                photo: resolvePhoto(p),
                                stats: resolveStats(p),
                            }
                            : {
                                id: null,
                                number: slot,
                                firstname: "Joueur",
                                lastname: `#${slot}`,
                                position: "",
                                photo: null,
                                stats: null,
                            }
                    );
                }
            };

            seedTeam("internal");
            seedTeam("external");

            return new RosterService({ rosters, statCoef, positionBonus });
        }

        // Retourne l’objet joueur du roster (team + slot) ou null.
        getPlayerInfo(team, slotNumber) {
            return this.rosters[team]?.get(slotNumber) ?? null;
        }

        // Clamp une stat à un nombre >= 0 (et 0 si invalide).
        clampStat(v) {
            const n = Number(v ?? 0);
            return Number.isFinite(n) ? Math.max(0, n) : 0;
        }

        // Retourne une stat normalisée pour un joueur donné.
        getStat(team, slotNumber, key) {
            const info = this.getPlayerInfo(team, slotNumber);
            const stats = info?.stats ?? {};
            return this.clampStat(stats[key]);
        }

        // Convertit la string de position en rôle (GK/DF/MF/FW).
        getRoleFromPositionString(pos) {
            const p = String(pos || "").toLowerCase();
            if (p.includes("goalkeeper") || p === "gk") return "GK";
            if (p.includes("def") || p === "df") return "DF";
            if (p.includes("mid") || p === "mf") return "MF";
            if (p.includes("for") || p.includes("att") || p === "fw") return "FW";
            return null;
        }

        // Retourne le rôle du joueur (via sa position).
        getPlayerRole(team, slotNumber) {
            const info = this.getPlayerInfo(team, slotNumber);
            return this.getRoleFromPositionString(info?.position);
        }

        // Calcule le multiplicateur de bonus de poste (1 + bonus).
        positionBonusMultiplier(role, tag) {
            if (!role) return 1.0;
            const r = this.POSITION_BONUS[role];
            if (!r) return 1.0;
            const b = Number(r[tag] ?? 0);
            return 1.0 + (Number.isFinite(b) ? b : 0);
        }

        // Calcule la base d’attaque (power UI + stat*coef + bonus poste).
        attackBaseFor(actionKey, team, slotNumber) {
            const base = STATS.attack[actionKey]?.power ?? 10;
            const role = this.getPlayerRole(team, slotNumber);

            const map = {
                pass: { stat: "pass", bonus: "pass" },
                dribble: { stat: "dribble", bonus: "dribble" },
                shot: { stat: "shot", bonus: "shot" },
                special: { stat: "attack", bonus: "attack" },
            };

            const m = map[actionKey] ?? null;
            let raw = base;
            if (m) raw = base + this.getStat(team, slotNumber, m.stat) * this.STAT_COEF;
            if (m) raw *= this.positionBonusMultiplier(role, m.bonus);

            return raw;
        }

        // Calcule la base de défense (field ou GK) avec stat*coef + bonus poste.
        defenseBaseFor(defenseAction, defenseTeam, defenseSlotNumber, isKeeper = false) {
            const baseField = STATS.defenseField[defenseAction]?.power;
            const baseGk = STATS.defenseGK[defenseAction]?.power;
            const base = (baseField ?? baseGk ?? 10);

            const role = this.getPlayerRole(defenseTeam, defenseSlotNumber);

            if (isKeeper) {
                const mapGK = {
                    hands: "hand_save",
                    punch: "punch_save",
                    "gk-special": "defense",
                };
                const statKey = mapGK[defenseAction] ?? null;

                let raw = base + (statKey ? this.getStat(defenseTeam, defenseSlotNumber, statKey) * this.STAT_COEF : 0);
                raw *= this.positionBonusMultiplier(role, "gk");
                return raw;
            }

            const mapField = {
                block: "block",
                intercept: "intercept",
                tackle: "tackle",
                "field-special": "defense",
            };
            const statKey = mapField[defenseAction] ?? null;

            let raw = base + (statKey ? this.getStat(defenseTeam, defenseSlotNumber, statKey) * this.STAT_COEF : 0);
            const bonusTag = (defenseAction === "block") ? "block" : "defend";
            raw *= this.positionBonusMultiplier(role, bonusTag);

            return raw;
        }
    }

    const roster = RosterService.create(matchConfig, {
        statCoef: 0.6,
        positionBonus: POSITION_BONUS,
    });

    // ==========================
    //   DOM roster binding
    // ==========================

    // Injecte numéro/metadata du roster dans le DOM des joueurs.
    function applyRosterToDOM() {
        for (const team of ["internal", "external"]) {
            for (let slot = 1; slot <= 11; slot++) {
                const id = (team === "internal" ? "I" : "E") + String(slot);
                const el = rootEl.querySelector(`.player[data-player="${id}"]`);
                if (!el) continue;

                const info = roster.getPlayerInfo(team, slot);
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
    //   STATE
    // ==========================
    const state = {
        ball: { team: "internal", zoneIndex: 1, laneIndex: 1, number: 8, frontOfKeeper: false },

        currentTeam: "internal",
        score: { internal: 0, external: 0 },
        turns: 0,

        phase: "attack",
        pendingAttack: null,
        isAnimating: false,
        isKickoff: true,
        keeperRestartMustPass: false,
        isGameOver: false,

        pendingShotContext: null,
        pendingClearanceBonus: 0,
        pendingDefenseContext: null,

        basePositions: {},
        lastDribblerId: null,

        stamina: {},
        staminaMax: {},

        specialCooldown: {},
        SPECIAL_COOLDOWN_TURNS: 2,

        touchHeat: {},
        lastDuelBreakdown: null,
        defensePreview: null,
    };

    // Accès court à l’objet ballon.
    const ball = state.ball;
    // Accès court aux positions de base.
    const basePositions = state.basePositions;

    // ==========================
    //   UI refs
    // ==========================
    const ui = {
        ballEl: $("#ball"),
        scoreInternalEl: $("#score-internal"),
        scoreExternalEl: $("#score-external"),
        turnsDisplayEl: $("#turns-display"),
        turnIndicatorEl: $("#turn-indicator"),
        msgMainEl: $("#message-main"),
        msgSubEl: $("#message-sub"),
        actionBarEl: $("#action-bar"),

        teamNameInternalEl: $("#team-name-internal"),
        teamNameExternalEl: $("#team-name-external"),
        homeBallIconEl: $("#home-ball-icon"),
        awayBallIconEl: $("#away-ball-icon"),
        matchEndActionsEl: $("#match-end-actions"),
        finishMatchBtn: $("#btn-finish-match"),

        currentActionTitleEl: $("#current-action-title"),
        currentActionDetailEl: $("#current-action-detail"),
        duelDiceEl: $("#duel-dice-display"),
        historyListEl: $("#history-list"),

        modeOnePlayerBtn: $("#mode-one-player"),
        controlledTeamSelect: $("#controlled-team-select"),
        aiOverlayEl: $("#ai-turn-overlay"),

        duelTooltipEl: null,
    };

    // ==========================
    //   UI helpers
    // ==========================

    // Met à jour le message principal + sous-message dans l’UI.
    function setMessage(main, sub) {
        if (ui.msgMainEl && main) ui.msgMainEl.textContent = main;
        if (ui.msgSubEl && sub !== undefined) ui.msgSubEl.textContent = sub;
    }

    // Affiche/masque l’overlay “tour IA” et son texte.
    function setAIOverlay(visible, text) {
        if (!ui.aiOverlayEl) return;
        if (visible) {
            ui.aiOverlayEl.classList.add("visible");
            ui.aiOverlayEl.textContent = text || TEXTS.ui.aiThinkingDefault;
        } else {
            ui.aiOverlayEl.classList.remove("visible");
        }
    }

    // ==========================
    //   HELPERS
    // ==========================

    // Retourne l’équipe adverse.
    function otherTeam(t) {
        return t === "internal" ? "external" : "internal";
    }

    // Indique si une équipe est contrôlée par l’IA en mode 1 joueur.
    function isAITeam(team) {
        return onePlayerMode && team !== controlledTeam;
    }

    // Construit l’identifiant DOM d’un joueur (I8 / E6).
    function getPlayerId(team, number) {
        return (team === "internal" ? "I" : "E") + String(number);
    }

    // Indique si l’id correspond à un gardien (I1/E1).
    function isGoalkeeperId(playerId) {
        return playerId === "I1" || playerId === "E1";
    }

    // ==========================
    //   HEAT (anti-biais touches)
    // ==========================

    // Retourne le “heat” d’un joueur (utilisé pour diversifier les touches).
    function heatOf(playerId) {
        return state.touchHeat[playerId] ?? 0;
    }

    // Incrémente le “heat” d’un joueur à chaque touche.
    function markTouch(playerId) {
        if (!playerId) return;
        state.touchHeat[playerId] = (state.touchHeat[playerId] ?? 0) + 1;
    }

    // Fait décroître progressivement le “heat” de tous les joueurs.
    function decayHeat() {
        for (const k in state.touchHeat) state.touchHeat[k] = Math.max(0, state.touchHeat[k] - 0.35);
    }

    // ==========================
    //   LOGS
    // ==========================
    const actionHistory = [];
    const MAX_HISTORY = 15;

    // Ajoute une entrée d’historique (UI + liste) avec détails et tag dés.
    function pushLogEntry(logKeyOrText, details = [], diceTag = null) {
        const main = TEXTS.logs[logKeyOrText] ?? logKeyOrText;

        const d = (details || [])
            .map((x) => (typeof x === "string" ? (TEXTS.logs[x] ?? x) : x))
            .filter(Boolean);

        if (ui.currentActionTitleEl) ui.currentActionTitleEl.textContent = main || "–";
        if (ui.currentActionDetailEl) ui.currentActionDetailEl.textContent = d.length ? d.join(" | ") : "";

        const turnLabel = `T${String(state.turns + 1).padStart(2, "0")}`;
        const shortLine = diceTag ? `${turnLabel} — ${main} (${diceTag})` : `${turnLabel} — ${main}`;

        actionHistory.push(shortLine);
        if (actionHistory.length > MAX_HISTORY) actionHistory.shift();

        if (ui.historyListEl) {
            ui.historyListEl.innerHTML = actionHistory.map((line) => `<li>${line}</li>`).join("");
        }
    }

    // ==========================
    //   DÉS + TOOLTIP
    // ==========================

    // Lance un dé (1..DIE_SIDES).
    function rollDie() {
        return 1 + Math.floor(Math.random() * DIE_SIDES);
    }

    // Lance un d20 avec bonus “cinématique” et flags crit.
    function rollD20WithCrit() {
        const roll = rollDie();
        return {
            roll,
            bonus: roll / 2,
            critSuccess: roll === 20,
            critFail: roll === 1,
        };
    }

    // Résout l’issue automatique si crit (attaque/défense) sinon null.
    function resolveCritOutcome(attackRoll, defenseRoll) {
        if (attackRoll.critSuccess && !defenseRoll.critSuccess) return "attack";
        if (defenseRoll.critSuccess && !attackRoll.critSuccess) return "defense";
        if (attackRoll.critFail && !defenseRoll.critFail) return "defense";
        if (defenseRoll.critFail && !attackRoll.critFail) return "attack";
        return null;
    }

    // Crée le tooltip des dés une fois et le retourne.
    function ensureDuelTooltip() {
        if (ui.duelTooltipEl) return ui.duelTooltipEl;

        const el = document.createElement("div");
        el.id = "duel-dice-tooltip";
        el.className = "dice-tooltip hidden";
        el.setAttribute("role", "tooltip");
        document.body.appendChild(el);

        ui.duelTooltipEl = el;
        return ui.duelTooltipEl;
    }

    // Remplit le tooltip des dés avec du HTML.
    function setDuelTooltipContent(html) {
        const el = ensureDuelTooltip();
        el.innerHTML = html || "";
    }

    // Formate l’objet breakdown en HTML lisible (jets/bases/bonus/résultat).
    function formatDuelBreakdownHTML(b) {
        if (!b) return "";

        const row = (label, value) => `
            <div class="dt-row">
                <div class="dt-label">${label}</div>
                <div class="dt-value">${value}</div>
            </div>
        `;

        const section = (title, inner) => `
            <div class="dt-section">
                <div class="dt-title">${title}</div>
                ${inner}
            </div>
        `;

        const resultLine = b.result?.critWinner
            ? `Crit: <b>${String(b.result.critWinner).toUpperCase()}</b>`
            : `Diff: <b>${Number(b.result?.diff ?? 0).toFixed(2)}</b> → <b>${String(b.result?.winner ?? "—").toUpperCase()}</b>`;

        return `
            <div class="dt-wrap">
                ${section("🎲 Jets", [
            row("Attaque d20", `${b.rolls.aTag} (bonus +${Number(b.rolls.aBonus ?? 0).toFixed(1)})`),
            row("Défense d20", `${b.rolls.dTag} (bonus +${Number(b.rolls.dBonus ?? 0).toFixed(1)})`),
        ].join(""))}

                ${section("⚔️ Attaque", [
            row("Base", Number(b.attack.base ?? 0).toFixed(2)),
            row("Stamina factor", `× ${Number(b.attack.staminaFactor ?? 1).toFixed(2)}`),
            ...(b.attack.additions || []).map(x => row(x.label, x.value)).join(""),
            row("Total attaque", `<b>${Number(b.attack.total ?? 0).toFixed(2)}</b>`),
        ].join(""))}

                ${section("🛡️ Défense", [
            row("Base", Number(b.defense.base ?? 0).toFixed(2)),
            row("Stamina factor", `× ${Number(b.defense.staminaFactor ?? 1).toFixed(2)}`),
            ...(b.defense.additions || []).map(x => row(x.label, x.value)).join(""),
            row("Total défense", `<b>${Number(b.defense.total ?? 0).toFixed(2)}</b>`),
        ].join(""))}

                ${section("✅ Résultat", [
            row("Règle bonus", b.result?.bonusRuleLabel || "—"),
            row("Issue", resultLine),
        ].join(""))}
            </div>
        `;
    }

    // Positionne le tooltip près du bloc “dés” en évitant les bords d’écran.
    function positionTooltipNearDice() {
        const tip = ui.duelTooltipEl;
        const dice = ui.duelDiceEl;
        if (!tip || !dice) return;

        const margin = 12;
        const gap = 10;

        const diceRect = dice.getBoundingClientRect();

        tip.style.position = "fixed";
        tip.style.zIndex = "9999";
        tip.style.transform = "none";
        tip.style.right = "auto";
        tip.style.bottom = "auto";

        const wasHidden = tip.classList.contains("hidden");
        if (wasHidden) {
            tip.style.visibility = "hidden";
            tip.classList.remove("hidden");
        }

        const tipRect = tip.getBoundingClientRect();

        let left = diceRect.left + (diceRect.width / 2) - (tipRect.width / 2);
        let top = diceRect.bottom + gap;

        left = Math.max(margin, Math.min(left, window.innerWidth - tipRect.width - margin));

        let placement = "bottom";
        if (top + tipRect.height + margin > window.innerHeight) {
            top = diceRect.top - tipRect.height - gap;
            placement = "top";
        }

        top = Math.max(margin, Math.min(top, window.innerHeight - tipRect.height - margin));

        tip.style.left = `${Math.round(left)}px`;
        tip.style.top = `${Math.round(top)}px`;
        tip.setAttribute("data-placement", placement);

        if (wasHidden) {
            tip.classList.add("hidden");
            tip.style.visibility = "";
        }
    }

    // Affiche le tooltip si un breakdown est disponible.
    function showDuelTooltip() {
        if (!ui.duelTooltipEl || !state.lastDuelBreakdown) return;
        positionTooltipNearDice();
        ui.duelTooltipEl.classList.remove("hidden");
    }

    // Masque le tooltip des dés.
    function hideDuelTooltip() {
        if (!ui.duelTooltipEl) return;
        ui.duelTooltipEl.classList.add("hidden");
    }

    // Bind les events hover/focus + resize/scroll pour le tooltip des dés.
    function bindDuelTooltipEvents() {
        if (!ui.duelDiceEl) return;

        ensureDuelTooltip();

        ui.duelDiceEl.addEventListener("mouseenter", showDuelTooltip);
        ui.duelDiceEl.addEventListener("mouseleave", hideDuelTooltip);

        ui.duelDiceEl.setAttribute("tabindex", "0");
        ui.duelDiceEl.addEventListener("focus", showDuelTooltip);
        ui.duelDiceEl.addEventListener("blur", hideDuelTooltip);

        window.addEventListener("scroll", () => {
            if (!ui.duelTooltipEl || ui.duelTooltipEl.classList.contains("hidden")) return;
            positionTooltipNearDice();
        }, { passive: true });

        window.addEventListener("resize", () => {
            if (!ui.duelTooltipEl || ui.duelTooltipEl.classList.contains("hidden")) return;
            positionTooltipNearDice();
        });
    }

    // Affiche le score duel + jets d20 dans l’UI et stocke le breakdown.
    function showDuelDice(attackScore, defenseScore, aRoll = null, dRoll = null, breakdown = null) {
        if (!ui.duelDiceEl) return;

        const a = Number(attackScore).toFixed(1);
        const d = Number(defenseScore).toFixed(1);

        let extra = "";
        if (aRoll && dRoll) {
            const aTag = aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll));
            const dTag = dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll));
            extra = `  (d20: ${aTag}-${dTag})`;
        }

        ui.duelDiceEl.textContent = `🎲 ${a} - ${d}${extra}`;
        ui.duelDiceEl.classList.add("visible");
        ui.duelDiceEl.classList.remove("pop");
        void ui.duelDiceEl.offsetWidth;
        ui.duelDiceEl.classList.add("pop");

        if (breakdown) {
            state.lastDuelBreakdown = breakdown;
            setDuelTooltipContent(formatDuelBreakdownHTML(breakdown));
        }
    }

    // ==========================
    //   STAMINA helpers
    // ==========================

    // Retourne l’endurance courante d’un joueur (init si absent).
    function getStamina(playerId) {
        if (!playerId) return 0;
        if (!(playerId in state.stamina)) state.stamina[playerId] = ENDURANCE_DEFAULT;
        return state.stamina[playerId];
    }

    // Retourne l’endurance max d’un joueur (init si absent).
    function getStaminaMax(playerId) {
        if (!playerId) return ENDURANCE_DEFAULT;
        if (!(playerId in state.staminaMax)) state.staminaMax[playerId] = ENDURANCE_DEFAULT;
        return state.staminaMax[playerId];
    }

    // Retourne le ratio endurance (0..1).
    function getStaminaRatio(playerId) {
        const v = getStamina(playerId);
        const m = getStaminaMax(playerId);
        return m > 0 ? (v / m) : 0;
    }

    // Classe le joueur dans un palier d’endurance (high/mid/low/crit).
    function getStaminaTier(playerId) {
        const r = getStaminaRatio(playerId);
        if (r >= 0.75) return "high";
        if (r >= 0.50) return "mid";
        if (r >= 0.25) return "low";
        return "crit";
    }

    // Calcule le multiplicateur de performance selon l’endurance.
    function staminaFactor(playerId) {
        const r = getStaminaRatio(playerId);
        if (r >= 0.75) return STAMINA_FACTORS.HIGH;
        if (r >= 0.50) return STAMINA_FACTORS.MID;
        if (r >= 0.25) return STAMINA_FACTORS.LOW;
        if (r > 0) return STAMINA_FACTORS.CRIT;
        return STAMINA_FACTORS.EXHAUSTED;
    }

    // Ajuste la consommation d’endurance selon la catégorie d’action.
    function staminaCostMultiplierFor(category) {
        if (category === "defenseGK") return 0.60;
        return 1.0;
    }

    // Applique un coût d’endurance à un joueur pour une action donnée.
    function applyStaminaCost(playerId, category, actionKey) {
        if (!playerId) return;

        const cfgCategory = STATS?.[category];
        const cfg = cfgCategory?.[actionKey];
        const baseCost = cfg ? cfg.cost : 0;

        const scaled =
            baseCost *
            staminaCostMultiplierFor(category) *
            STAMINA_COST_GLOBAL_SCALE;

        const cost = Math.max(0, Math.round(scaled));
        state.stamina[playerId] = Math.max(0, getStamina(playerId) - cost);

        updateStaminaUI(playerId);
    }

    // Met à jour la barre d’endurance au-dessus du joueur + classes visuelles.
    function updateStaminaUI(playerId) {
        if (!playerId) return;

        const ratio = getStaminaRatio(playerId);
        const el = rootEl.querySelector(`.player[data-player="${playerId}"]`);

        if (el) {
            el.classList.add("show-endurance");

            const shell = el.querySelector(".endurance-shell");
            if (shell) {
                const bar = shell.querySelector(".endurance-bar");
                if (bar) bar.style.width = `${Math.max(10, ratio * 100)}%`;
            }

            el.classList.remove("e-high", "e-mid", "e-low", "e-crit");

            const tier = getStaminaTier(playerId);
            if (tier === "high") el.classList.add("e-high");
            else if (tier === "mid") el.classList.add("e-mid");
            else if (tier === "low") el.classList.add("e-low");
            else el.classList.add("e-crit");
        }

        if (getPlayerId(ball.team, ball.number) === playerId) updateTeamCard();
    }

    // Initialise stamina max/current pour tous les joueurs et crée le DOM de la barre si absent.
    function initStamina() {
        $$(".player").forEach((el) => {
            const id = el.dataset.player;
            if (!id) return;

            const team = id.startsWith("I") ? "internal" : "external";
            const slot = parseInt(id.slice(1), 10);

            const max = roster.clampStat(roster.getStat(team, slot, "stamina")) || ENDURANCE_DEFAULT;
            state.staminaMax[id] = max;
            state.stamina[id] = max;

            if (!el.querySelector(".endurance-shell")) {
                const shell = document.createElement("div");
                shell.className = "endurance-shell";
                const bar = document.createElement("div");
                bar.className = "endurance-bar";
                shell.appendChild(bar);
                el.appendChild(shell);
            }

            updateStaminaUI(id);
        });
    }

    // ==========================
    //   Special cooldown
    // ==========================

    // Vérifie si le joueur peut utiliser Special selon le cooldown.
    function canUseSpecial(playerId) {
        if (!playerId) return false;
        const availableAt = state.specialCooldown[playerId] ?? 0;
        return state.turns >= availableAt;
    }

    // Marque Special utilisé et programme son prochain tour disponible.
    function markSpecialUsed(playerId) {
        if (!playerId) return;
        state.specialCooldown[playerId] = state.turns + state.SPECIAL_COOLDOWN_TURNS;
    }

    // ==========================
    //   Terrain helpers
    // ==========================

    // Retourne le centre (x,y) d’une cellule (zone/lane) en miroir selon l’équipe.
    function getCellCenter(team, zoneIndex, laneIndex) {

        // 🔵 NOUVELLES COORDONNÉES (tu peux les ajuster)
        const ZONE_X = {
            internal: {
                0: 10,
                1: 20,
                2: 35,
                3: 55,
                4: 75,
            },
            external: {
                0: 90,
                1: 80,
                2: 65,
                3: 45,
                4: 25,
            }
        };

        const x = ZONE_X[team][zoneIndex];
        const y = laneY[laneIndex];

        return { x, y };
    }

    // Convertit une zone en zone “face” (miroir 0..3).
    function getFacingZoneIndex(zoneIndex) {
        const zi = Math.max(0, Math.min(MAX_ZONE_INDEX, zoneIndex));
        return MAX_ZONE_INDEX - zi;
    }

    // Récupère l’élément DOM du porteur (team + numéro).
    function getCarrierElement(team, number) {
        return rootEl.querySelector(`[data-player="${getPlayerId(team, number)}"]`);
    }

    // Retourne la zone (0..4) d'un joueur à partir de son data-zone.
    // Fallback : si pas de data-zone, on tente de déduire depuis xInternal (compat old).
    function getPlayerZoneFromDOM(playerId) {
        const el = rootEl.querySelector(`.player[data-player="${playerId}"]`);
        if (!el) return 0;

        const zAttr = el.dataset.zone;
        if (zAttr !== undefined) {
            const z = parseInt(zAttr, 10);
            if (Number.isFinite(z)) return Math.max(0, Math.min(MAX_ZONE_INDEX, z));
        }

        // 🔁 Fallback compat (au cas où tu aurais oublié un data-zone)
        const x = parseFloat(el.style.left);
        if (Number.isNaN(x)) return 0;

        const team = playerId.startsWith("I") ? "internal" : "external";
        const xInternal = team === "internal" ? x : 100 - x;

        let zoneIndex = 0;
        const xClamped = Math.max(0, Math.min(100, xInternal));
        for (let i = 0; i < ZONE_BOUNDS_INTERNAL.length - 1; i++) {
            const left = ZONE_BOUNDS_INTERNAL[i];
            const right = ZONE_BOUNDS_INTERNAL[i + 1];
            const isLast = (i === ZONE_BOUNDS_INTERNAL.length - 2);
            const inside = isLast
                ? (xClamped >= left && xClamped <= right)
                : (xClamped >= left && xClamped < right);
            if (inside) { zoneIndex = i; break; }
        }
        return zoneIndex;
    }

    // ✅ Retourne X "attaque-side" (internal = x, external = 100-x)
    function getXInternal(team, x) {
        return team === "internal" ? x : (100 - x);
    }

// ✅ Retourne le XInternal d'un joueur DOM
    function getPlayerXInternal(team, el) {
        const x = parseFloat(el.style.left);
        return Number.isNaN(x) ? null : getXInternal(team, x);
    }

    // Sélectionne un joueur proche du centre (pondéré distance + stamina + heat) dans une zone.
    function pickWeightedPlayerInZone(team, zoneIndex, laneIndex, opts = {}) {
        const {
            excludeIds = [],
            topK = 4,
            ignoreLane = false,
        } = opts;

        const selector = team === "internal" ? ".player.internal" : ".player.external";
        const center = ignoreLane
            ? getCellCenter(team, zoneIndex, 1)
            : getCellCenter(team, zoneIndex, laneIndex);

        const candidates = [];
        rootEl.querySelectorAll(selector).forEach((el) => {
            // On ne veut jamais choisir le gardien pour un duel de champ
            if (el.classList.contains("goalkeeper")) return;

            const id = el.dataset.player;
            if (!id || excludeIds.includes(id)) return;

            // 🔒 Respect strict de la zone par data-zone
            const zAttr = el.dataset.zone;
            if (zAttr !== undefined) {
                const z = parseInt(zAttr, 10);
                if (Number.isFinite(z) && z !== zoneIndex) return;
            }

            const x = parseFloat(el.style.left);
            const y = parseFloat(el.style.top);
            if (Number.isNaN(x) || Number.isNaN(y)) return;

            const dx = x - center.x;
            const dy = ignoreLane ? 0 : (y - center.y);
            const d2 = dx * dx + dy * dy;

            candidates.push({ id, d2 });
        });

        if (!candidates.length) return null;

        candidates.sort((a, b) => a.d2 - b.d2);
        const pool = candidates.slice(0, Math.min(topK, candidates.length));

        const EPS = 1e-6;
        const weights = pool.map(({ id, d2 }) => {
            const distW = 1 / (d2 + EPS);

            const st = getStamina(id);
            const stMax = getStaminaMax(id) || 100;
            const staminaRatio = stMax > 0 ? st / stMax : 1;

            const heat = heatOf(id);
            const heatPenalty = 1 / (1 + heat * 0.75);

            return distW * (0.85 + 0.15 * staminaRatio) * heatPenalty;
        });

        const sum = weights.reduce((a, b) => a + b, 0);
        let r = Math.random() * (sum || 1);

        for (let i = 0; i < pool.length; i++) {
            r -= weights[i];
            if (r <= 0) return pool[i].id;
        }

        return pool[pool.length - 1].id;
    }

    // Choisit un receveur dans une cellule cible (fallback aléatoire si aucun).
    function pickReceiverInCell(team, zoneIndex, laneIndex, fallbackNumber, excludeNumber = null, opts = {}) {
        const {
            forwardOnly = false,
            forwardFromXInternal = null,
            ignoreLaneForInitialPick = false,
        } = opts;

        const excludeId = excludeNumber ? getPlayerId(team, excludeNumber) : null;

        // 1) tentative stricte (zone+lane)
        let receiverId = pickWeightedPlayerInZone(team, zoneIndex, laneIndex, {
            topK: 6,
            excludeIds: excludeId ? [excludeId] : [],
            ignoreLane: ignoreLaneForInitialPick,
        });


        // 2) si pas trouvé, on élargit (ignoreLane) AVANT fallback random
        if (!receiverId) {
            receiverId = pickWeightedPlayerInZone(team, zoneIndex, laneIndex, {
                topK: 8,
                excludeIds: excludeId ? [excludeId] : [],
                ignoreLane: true,
            });
        }

        // 3) forward-only : si receveur derrière, on invalide et on retente en élargissant
        if (forwardOnly && receiverId && forwardFromXInternal !== null) {
            const el = getCarrierElement(team, parseInt(receiverId.slice(1), 10));
            if (el) {
                const rx = getPlayerXInternal(team, el);
                if (rx !== null && rx < forwardFromXInternal - 0.01) {
                    receiverId = null;
                }
            }
        }

        if (receiverId && excludeNumber !== null) {
            const num = parseInt(receiverId.slice(1), 10);
            if (num === excludeNumber) receiverId = null;
        }

        // fallback : seulement si vraiment personne
        if (!receiverId) {
            const selector = team === "internal" ? ".player.internal" : ".player.external";
            const all = Array.from(rootEl.querySelectorAll(selector)).filter(el => !el.classList.contains("goalkeeper"));
            if (!all.length) return fallbackNumber;

            let pool = all;

            if (excludeNumber !== null) {
                pool = pool.filter(el => parseInt(el.dataset.player.slice(1), 10) !== excludeNumber);
                if (!pool.length) pool = all;
            }

            // ✅ forward-only même sur fallback (sinon ça repasse derrière)
            if (forwardOnly && forwardFromXInternal !== null) {
                const forwardPool = pool.filter(el => {
                    const x = getPlayerXInternal(team, el);
                    return x !== null && x >= forwardFromXInternal - 0.01;
                });
                if (forwardPool.length) pool = forwardPool;
            }

            const rand = pool[Math.floor(Math.random() * pool.length)];
            return parseInt(rand.dataset.player.slice(1), 10);
        }

        return parseInt(receiverId.slice(1), 10);
    }

    // Retourne l’id du gardien (DOM) pour une équipe.
    function getKeeperId(team) {
        const selector = team === "internal" ? ".player.internal.goalkeeper" : ".player.external.goalkeeper";
        const el = rootEl.querySelector(selector);
        return el ? el.dataset.player : null;
    }

    // Déplace le ballon sur un joueur et met à jour zone/lane/frontOfKeeper + UI.
    function moveBallToPlayer(team, number) {
        if (!ui.ballEl) return;

        // 1) Récupération de l'élément DOM du joueur ciblé
        let targetNumber = number;
        let el = getCarrierElement(team, targetNumber);
        if (!el) return;

        let x = parseFloat(el.style.left);
        let y = parseFloat(el.style.top);
        if (Number.isNaN(x) || Number.isNaN(y)) return;

        // 2) Mise à jour de base : visuel sur ce joueur
        ui.ballEl.style.left = x + "%";
        ui.ballEl.style.top = y + "%";

        const info = roster.getPlayerInfo(team, targetNumber);
        ui.ballEl.textContent = info ? String(info.number) : String(targetNumber);

        // 3) Mise à jour de l'état interne du ballon
        ball.team = team;
        ball.number = targetNumber;

        // 4) Règle spéciale : si gardien reçoit la balle alors qu'il n'est pas frontal → passe forcée
        if (ball.number === 1 && !ball.frontOfKeeper) {
            const safe = pickReceiverInCell(team, ball.zoneIndex, ball.laneIndex, 6, 1);

            // On redirige la possession vers le joueur "safe"
            const safeEl = getCarrierElement(team, safe);
            if (safeEl) {
                const sx = parseFloat(safeEl.style.left);
                const sy = parseFloat(safeEl.style.top);

                ui.ballEl.style.left = sx + "%";
                ui.ballEl.style.top = sy + "%";

                const safeInfo = roster.getPlayerInfo(team, safe);
                ui.ballEl.textContent = safeInfo ? String(safeInfo.number) : String(safe);

                ball.number = safe;   // état cohérent avec l'affichage
                el = safeEl;
                x = sx;
                y = sy;
                targetNumber = safe;
            }
        }

        // 5) Marquage heat pour diversifier les touches
        markTouch(getPlayerId(team, ball.number));
        ball.frontOfKeeper = false;

        // 6) NOUVEAU : zone depuis data-zone du joueur finalement porteur
        const playerId = getPlayerId(team, ball.number);
        const zoneIndex = getPlayerZoneFromDOM(playerId);

        // 7) Lane déterminée via position verticale
        let bestLane = 0;
        let bestLaneDist = Infinity;
        laneY.forEach((vy, i) => {
            const d = Math.abs(vy - y);
            if (d < bestLaneDist) {
                bestLaneDist = d;
                bestLane = i;
            }
        });

        ball.zoneIndex = zoneIndex;
        ball.laneIndex = bestLane;
        state.defensePreview = null;

        // 8) Mise à jour des cartes et de l'UI
        updateTeamCard();
        updateCardsPower();
    }

    // Place visuellement le ballon sur le gardien (sans changer l’état ball).
    function setBallToKeeperVisual(defenseTeam) {
        const selector = defenseTeam === "internal"
            ? '.player.internal.goalkeeper[data-player="I1"]'
            : '.player.external.goalkeeper[data-player="E1"]';

        const keeperEl = rootEl.querySelector(selector);
        if (!keeperEl || !ui.ballEl) return;

        const x = parseFloat(keeperEl.style.left);
        const y = parseFloat(keeperEl.style.top);

        ui.ballEl.style.left = `${x}%`;
        ui.ballEl.style.top = `${y}%`;
        ui.ballEl.textContent = "1";
    }

    // ==========================
    //   Égalité (jamais vers GK)
    // ==========================

    // Donne la possession après une égalité, en évitant le gardien si possible.
    function givePossessionOnTie(defenseTeam, defenderIdMaybe = null) {
        if (defenderIdMaybe && !isGoalkeeperId(defenderIdMaybe)) {
            const slot = parseInt(defenderIdMaybe.slice(1), 10);
            moveBallToPlayer(defenseTeam, slot);
            setMessage(TEXTS.ui.duelTieMain, TEXTS.ui.duelTieSub.replace("{team}", TEAMS[defenseTeam].label));
            return { team: defenseTeam, number: slot };
        }

        const fallbackId = pickWeightedPlayerInZone(defenseTeam, ball.zoneIndex, ball.laneIndex);
        if (fallbackId && !isGoalkeeperId(fallbackId)) {
            const slot = parseInt(fallbackId.slice(1), 10);
            moveBallToPlayer(defenseTeam, slot);
            setMessage(TEXTS.ui.duelTieMain, TEXTS.ui.duelTieSub.replace("{team}", TEAMS[defenseTeam].label));
            return { team: defenseTeam, number: slot };
        }

        return { team: ball.team, number: ball.number };
    }

    // ==========================
    //   CARDS photos
    // ==========================

    // Crée/retourne le calque image “photo joueur” dans une card.
    function ensureCardPhotoLayer(cardEl) {
        if (!cardEl) return null;

        if (getComputedStyle(cardEl).position === "static") cardEl.style.position = "relative";
        cardEl.style.overflow = "hidden";

        let img = cardEl.querySelector("img.player-card-photo");
        if (!img) {
            img = document.createElement("img");
            img.className = "player-card-photo hidden";
            img.alt = "";
            img.loading = "lazy";
            img.decoding = "async";
            img.style.position = "absolute";
            img.style.inset = "0";
            img.style.width = "100%";
            img.style.height = "100%";
            img.style.objectFit = "cover";
            img.style.pointerEvents = "none";
            img.style.zIndex = "50";

            img.addEventListener("error", () => {
                img.classList.add("hidden");
                img.removeAttribute("src");
            });

            cardEl.appendChild(img);
        }

        return img;
    }

    // Affecte une URL photo à la card (normalisation storage/relative/http).
    function setCardPhoto(cardEl, photoUrl) {
        const img = ensureCardPhotoLayer(cardEl);
        if (!img) return;

        const raw = String(photoUrl || "").trim();
        if (!raw) {
            img.classList.add("hidden");
            img.removeAttribute("src");
            return;
        }

        let url = raw;
        if (url.startsWith("storage/")) url = `/${url}`;
        if (!/^https?:\/\//.test(url) && !url.startsWith("/")) url = `/${url}`;

        img.src = url;
        img.classList.remove("hidden");
    }

    // ==========================
    //   CARDS update
    // ==========================

    // Met à jour une card (home/away) avec infos joueur, stats, stamina et portrait.
    function updateSideCard(prefix, team, slotNumber) {
        const info = roster.getPlayerInfo(team, slotNumber);

        // Met du texte dans un élément DOM si trouvé.
        const setText = (id, value) => {
            const el = rootEl.querySelector(id);
            if (el) el.textContent = value ?? "—";
        };

        const fullName = info ? `${info.firstname} ${info.lastname}`.trim() : "";
        setText(`#${prefix}-name`, fullName || `#${slotNumber}`);
        setText(`#${prefix}-role`, info?.position || "—");
        setText(`#${prefix}-number`, info ? String(info.number) : String(slotNumber));
        setText(`#${prefix}-team`, TEAMS[team].label);

        const stat = (k) => Number(info?.stats?.[k] ?? 0) || 0;
        ["shot","pass","dribble","attack","block","intercept","tackle","defense","hand_save","punch_save"]
            .forEach(k => setText(`#${prefix}-stat-${k}`, String(stat(k))));

        const isGK = (info?.position || "").toLowerCase().includes("goalkeeper");

        const fieldRows = ["block","intercept","tackle","dribble"];
        const gkRows = ["hand_save","punch_save"];

        fieldRows.forEach(k => {
            const el = rootEl.querySelector(`#${prefix}-stat-${k}`)?.parentElement;
            if (el) el.classList.toggle("hidden", isGK);
        });

        gkRows.forEach(k => {
            const el = rootEl.querySelector(`#${prefix}-stat-${k}`)?.parentElement;
            if (el) el.classList.toggle("hidden", !isGK);
        });

        const playerId = getPlayerId(team, slotNumber);
        const ratio = getStaminaRatio(playerId);

        const fillEl = rootEl.querySelector(`#${prefix}-energy-fill`);
        if (fillEl) {
            fillEl.style.width = `${Math.max(0, ratio * 100)}%`;
            fillEl.className = `energy-fill e-${getStaminaTier(playerId)}`;
        }

        const portraitEl = rootEl.querySelector(`#${prefix}-portrait`);
        if (portraitEl) setCardPhoto(portraitEl, info?.photo);
    }

    // Met à jour la card du récupérateur (défenseur qui gagne).
    function syncRecovererCard(defenseTeam, slot) {
        const defenderPrefix = (defenseTeam === "internal") ? "home" : "away";
        updateSideCard(defenderPrefix, defenseTeam, slot);
    }

    // Met à jour le score + compteur de tours dans l’UI.
    function updateScoreUI() {
        if (ui.scoreInternalEl) ui.scoreInternalEl.textContent = state.score.internal;
        if (ui.scoreExternalEl) ui.scoreExternalEl.textContent = state.score.external;

        const t = String(state.turns).padStart(2, "0");
        if (ui.turnsDisplayEl) ui.turnsDisplayEl.textContent = t;
        if (ui.turnIndicatorEl) ui.turnIndicatorEl.textContent = t;
    }

    // Met à jour la card du porteur de balle (home/away) et les puissances actions.
    function updateTeamCard() {
        ui.homeBallIconEl?.classList.toggle("hidden", ball.team !== "internal");
        ui.awayBallIconEl?.classList.toggle("hidden", ball.team !== "external");

        const prefix = ball.team === "internal" ? "home" : "away";
        updateSideCard(prefix, ball.team, ball.number);
        updateCardsPower();
    }

    // ==========================
    //   Good defense + bonuses
    // ==========================

    // Détermine si le choix défensif est “optimal” contre l’action offensive.
    function isGoodDefenseChoice(attackAction, defenseAction) {
        const a = String(attackAction).toLowerCase();
        const d = String(defenseAction).toLowerCase();

        if (["hands","punch","gk-special"].includes(d)) {
            return true;
        }

        return (
            (a === "pass"    && d === "intercept") ||
            (a === "dribble" && d === "tackle")   ||
            (a === "shot"    && d === "block")    ||
            (a === "special" && (d === "field-special" || d === "block"))
        );
    }

    // Retourne un titre de log cohérent avec l'action défensive (intercept/tackle/block) et le RPS.
    function getLogTitleForDuel(attackAction, defenseAction, duelWinner) {
        // duelWinner: "attack" | "defense" | "tie"
        if (duelWinner === "tie") return "Duel équilibré";

        // Si l'attaque gagne, on garde les titres "succès" classiques.
        if (duelWinner === "attack") {
            if (attackAction === "pass") return TEXTS.logs.passSuccessTitle;
            if (attackAction === "dribble") return "Dribble réussi";
            if (attackAction === "shot") return TEXTS.logs.shotGoalTitle;     // sur champ : "tir cadré" va suivre
            if (attackAction === "special") return TEXTS.logs.shotGoalTitle;  // idem
            return "";
        }

        // Si la défense gagne, on veut un titre qui reflète L'ACTION DEF (RPS), sinon "récupéré".
        if (attackAction === "pass") {
            return (defenseAction === "intercept") ? TEXTS.logs.passFailTitle : TEXTS.logs.passRecoveredTitle;
        }

        if (attackAction === "dribble") {
            // Bon contre = tackle -> "Dribble stoppé" ok, sinon récupéré.
            return (defenseAction === "tackle") ? "Dribble stoppé" : TEXTS.logs.dribbleRecoveredTitle;
        }

        if (attackAction === "shot") {
            return (defenseAction === "block") ? TEXTS.logs.shotBlockedTitle : TEXTS.logs.shotRecoveredTitle;
        }

        if (attackAction === "special") {
            // Bon contre = field-special ou block, sinon récupéré.
            if (defenseAction === "block") return TEXTS.logs.shotBlockedTitle;
            return TEXTS.logs.specialRecoveredTitle;
        }

        return "";
    }

    // Petit tag lisible pour l'historique, basé sur ton pierre-papier-ciseaux.
    function getCounterTag(attackAction, defenseAction) {
        const good = isGoodDefenseChoice(attackAction, defenseAction);
        return good ? "✅ Bon contre" : "❌ Mauvais choix";
    }

    // Applique bonus/malus selon “good counter” vs “generic attack” (GK ou field).
    function applyDuelBonuses({ attackAction, defenseAction, attackScore, defenseScore, context = {} }) {
        const good = context.isKeeperDuel
            ? ["hands", "punch", "gk-special"].includes(defenseAction)
            : isGoodDefenseChoice(attackAction, defenseAction);

        return good
            ? { attackScore, defenseScore: defenseScore + DUEL_RULES.GOOD_COUNTER_BONUS }
            : { attackScore: attackScore + DUEL_RULES.GENERIC_ATTACK_BONUS, defenseScore };
    }

    // Construit un texte de résultat (logs) selon action et vainqueur.
    function buildActionResultText({ attackAction, defenseAction, duelResult }) {
        const map = {
            pass: { attack: "passSuccessTitle", defense: "passFailTitle" },
            dribble: { attack: "dribbleSuccessTitle", defense: "dribbleFailTitle" }, // optionnel
            shot: { attack: "shotGoalTitle", defense: "shotSavedTitle" },
            special: { attack: "shotGoalTitle", defense: "shotSavedTitle" },
        };

        const key = map[attackAction]?.[duelResult];
        return TEXTS.logs[key] ?? "";
    }

    // ==========================
    //   Action bar HTML
    // ==========================

    // Génère le HTML d’une carte d’action offensive.
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
            </button>`;
    }

    // Génère le HTML d’une carte de défense (field ou GK).
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
            </button>`;
    }

    // Construit la barre d’actions offensives (shot/pass/dribble/special).
    function buildAttackActionsHTML() {
        const cfg = TEXTS.cards.attack;
        return `<div id="attack-strip">
            ${buildSkillCard("shot", cfg.shot)}
            ${buildSkillCard("pass", cfg.pass)}
            ${buildSkillCard("dribble", cfg.dribble)}
            ${buildSkillCard("special", cfg.special)}
        </div>`;
    }

    // Construit la barre de défense de champ (block/intercept/tackle/special).
    function buildDefenseFieldHTML() {
        const cfg = TEXTS.cards.defenseField;
        return `<div id="defense-strip">
            ${buildDefCard("block", cfg.block)}
            ${buildDefCard("intercept", cfg.intercept)}
            ${buildDefCard("tackle", cfg.tackle)}
            ${buildDefCard("field-special", cfg["field-special"])}
        </div>`;
    }

    // Construit la barre de défense gardien (hands/punch/gk-special).
    function buildDefenseGKHTML() {
        const cfg = TEXTS.cards.defenseGK;
        return `<div id="defense-strip">
            ${buildDefCard("hands", cfg.hands)}
            ${buildDefCard("punch", cfg.punch)}
            ${buildDefCard("gk-special", cfg["gk-special"])}
        </div>`;
    }

    // Initialise l’affichage des coûts d’énergie dans les cards depuis STATS.
    function initUIFromStats() {
        const attackStrip = rootEl.querySelector("#attack-strip");
        if (attackStrip) {
            attackStrip.querySelectorAll(".skill-card").forEach(btn => {
                const action = btn.dataset.action;
                const cfg = STATS.attack[action];
                const costEl = btn.querySelector(".skill-cost span");
                if (cfg && costEl) costEl.textContent = cfg.cost;
            });
        }

        const defenseStrip = rootEl.querySelector("#defense-strip");
        if (defenseStrip) {
            defenseStrip.querySelectorAll(".def-card").forEach(btn => {
                const def = btn.dataset.defense;
                const cfg = STATS.defenseField[def] || STATS.defenseGK[def];
                const costEl = btn.querySelector(".def-cost span");
                if (cfg && costEl) costEl.textContent = cfg.cost;
            });
        }
    }

    // Met à jour les puissances visibles des actions (attaquant + défenseur en cours).
    function updateCardsPower() {
        if (!ui.actionBarEl) return;

        const carrier = roster.getPlayerInfo(ball.team, ball.number);
        const carrierStats = carrier?.stats ?? {};

        ui.actionBarEl.querySelectorAll(".skill-card").forEach(btn => {
            const a = btn.dataset.action;
            const map = { pass: "pass", dribble: "dribble", shot: "shot", special: "attack" };
            const value = Number(carrierStats[map[a]] ?? 0);
            const el = btn.querySelector(".skill-power");
            if (el) el.textContent = String(value);
        });

        const mode = [...ui.actionBarEl.classList].find(c => c.startsWith("mode-defense-"));
        if (!mode) return;

        const defenseTeam = mode.includes("external") ? "external" : "internal";
        const isGK = !!ui.actionBarEl.querySelector('.def-card[data-defense="hands"]');

        if (isGK) {
            const gk = roster.getPlayerInfo(defenseTeam, 1)?.stats ?? {};
            const map = { hands: "hand_save", punch: "punch_save", "gk-special": "defense" };

            ui.actionBarEl.querySelectorAll(".def-card").forEach(btn => {
                const def = btn.dataset.defense;
                const el = btn.querySelector(".def-power");
                if (el) el.textContent = String(Number(gk[map[def]] ?? 0));
            });
            return;
        }

        const ctx = state.pendingDefenseContext;
        const slot = ctx?.defenderSlot;
        if (!slot) return;

        const dStats = roster.getPlayerInfo(defenseTeam, slot)?.stats ?? {};
        const map = { block: "block", intercept: "intercept", tackle: "tackle", "field-special": "defense" };

        ui.actionBarEl.querySelectorAll(".def-card").forEach(btn => {
            const def = btn.dataset.defense;
            const el = btn.querySelector(".def-power");
            if (el) el.textContent = String(Number(dStats[map[def]] ?? 0));
        });
    }

    // Bind les clics sur cartes d’attaque/défense vers les handlers.
    function bindActionButtons() {
        if (!ui.actionBarEl) return;

        ui.actionBarEl.querySelectorAll(".skill-card").forEach(btn =>
            btn.addEventListener("click", () => handleAttackClick(btn.dataset.action))
        );

        ui.actionBarEl.querySelectorAll(".def-card").forEach(btn =>
            btn.addEventListener("click", () => handleDefenseClick(btn.dataset.defense))
        );
    }

    // Remplace le contenu de l’action bar avec transition + init coûts + bind.
    function setActionBar(html, modeClass) {
        if (!ui.actionBarEl) return;

        ui.actionBarEl.classList.add("fade-out");

        setTimeout(() => {
            ui.actionBarEl.innerHTML = html;

            ui.actionBarEl.className = ui.actionBarEl.className.replace(/\bmode-[^\s]+/g, "");
            if (modeClass) ui.actionBarEl.classList.add(modeClass);

            initUIFromStats();
            updateCardsPower();

            if (state.isKickoff && html.includes("attack-strip")) {
                ui.actionBarEl.querySelectorAll(".skill-card").forEach(btn => {
                    if (btn.dataset.action !== "pass") btn.style.display = "none";
                });
            }

            ui.actionBarEl.classList.remove("fade-out");
            ui.actionBarEl.classList.add("fade-in");

            bindActionButtons();
        }, ACTION_BAR_FADE_MS);
    }

    // ==========================
    //   Anim / Tour
    // ==========================

    // Lance l’animation “kick” puis exécute un callback.
    function animateAndThen(cb) {
        if (!ui.ballEl) { if (cb) cb(); return; }
        state.isAnimating = true;
        ui.ballEl.classList.add("ball-kick");
        setTimeout(() => {
            ui.ballEl.classList.remove("ball-kick");
            state.isAnimating = false;
            if (cb) cb();
        }, ANIM_MS);
    }

    // Rafraîchit les widgets principaux (score + card porteur).
    function refreshUI() {
        updateScoreUI();
        updateTeamCard();
    }

    // Avance le tour, gère fin de match, reset phases, et prépare l’attaque.
    function advanceTurn(newTeam) {
        if (state.isGameOver) return;

        decayHeat();

        state.currentTeam = newTeam;
        state.turns++;

        if (state.turns >= GAME_RULES.MAX_TURNS) {
            state.isGameOver = true;

            setMessage(TEXTS.ui.matchEndMain, `${TEXTS.ui.matchEndPrefix}${state.score.internal} - ${state.score.external}`);
            pushLogEntry("matchEndTitle", [`Score final ${state.score.internal} - ${state.score.external}`]);

            if (ui.actionBarEl) {
                ui.actionBarEl.classList.remove("fade-in");
                ui.actionBarEl.classList.add("fade-out");
                setTimeout(() => { ui.actionBarEl.innerHTML = ""; }, ACTION_BAR_FADE_MS);
            }

            refreshUI();

            if (ui.matchEndActionsEl) ui.matchEndActionsEl.classList.remove("hidden");

            if (ui.finishMatchBtn) {
                ui.finishMatchBtn.onclick = () => {
                    const internalTeamId = matchConfig?.sides?.internalTeamId;
                    const externalTeamId = matchConfig?.sides?.externalTeamId;
                    if (!internalTeamId || !externalTeamId) return;

                    const payload = {
                        matchId: matchConfig.matchId,
                        gameSaveId: matchConfig.gameSaveId,
                        scoresByTeamId: {
                            [internalTeamId]: state.score.internal,
                            [externalTeamId]: state.score.external,
                        },
                    };

                    if (typeof matchConfig.onMatchEnd === "function") {
                        matchConfig.onMatchEnd(payload);
                    }
                };
            }
            return;
        }

        setMessage(`${TEAMS[state.currentTeam].label} a la balle`, TEXTS.ui.chooseAttackSub);

        state.phase = "attack";
        state.pendingAttack = null;
        state.pendingShotContext = null;
        state.pendingDefenseContext = null;

        showAttackBarForCurrentTeam();
        refreshUI();
    }

    // ==========================
    //   Positions de base
    // ==========================

    // Replace le dernier dribbler à sa position de base (fin d’action).
    function resetLastDribbler() {
        if (!state.lastDribblerId) return;
        const pos = basePositions[state.lastDribblerId];
        const el = rootEl.querySelector(`[data-player="${state.lastDribblerId}"]`);
        if (pos && el) {
            el.style.left = pos.x + "%";
            el.style.top = pos.y + "%";
        }
        state.lastDribblerId = null;
    }

    // Capture les positions initiales (x,y) de tous les joueurs.
    function initBasePositions() {
        $$(".player").forEach((el) => {
            basePositions[el.dataset.player] = {
                x: parseFloat(el.style.left),
                y: parseFloat(el.style.top),
            };
        });
    }

    // Ajuste les positions pour la remise en jeu (équipes de part et d’autre).
    function applyKickoffPositions() {
        $$(".player").forEach((el) => {
            const base = basePositions[el.dataset.player];
            if (!base) return;

            let x = base.x;
            const y = base.y;

            if (el.classList.contains("internal")) {
                if (x > 50) x = 48;
            } else {
                if (x < 50) x = 52;
            }

            el.style.left = x + "%";
            el.style.top = y + "%";
        });
    }

    // Restaure exactement les positions initiales (x,y) de tous les joueurs.
    function restoreBasePositions() {
        $$(".player").forEach((el) => {
            const base = basePositions[el.dataset.player];
            if (!base) return;
            el.style.left = base.x + "%";
            el.style.top = base.y + "%";
        });
    }

    // ==========================
    //   IA
    // ==========================

    // Choisit l’action d’attaque de l’IA selon contexte (kickoff, zones, stamina, GK).
    function computeAIAttackChoice() {
        if (state.isKickoff) return "pass";
        if (state.keeperRestartMustPass) return "pass";

        if (ball.frontOfKeeper) {
            const r = Math.random();
            const p = AI_RULES.ATTACK.FRONT_GK_SPECIAL_PROB ?? 0.15;
            return (r < p) ? "special" : "shot";
        }

        const z = ball.zoneIndex; // 0..MAX_ZONE_INDEX (0..4)
        const aiCarrierId = getPlayerId(state.currentTeam, ball.number);
        const stRatio = getStaminaRatio(aiCarrierId);

        if (stRatio < 0.20) return "pass";

        // Z0-Z1 : construction -> passe/dribble
        if (z <= 1) {
            return (Math.random() < (AI_RULES.ATTACK.EARLY_PASS_PROB ?? 0.7)) ? "pass" : "dribble";
        }

        // Z2 : progression -> dribble dominant
        if (z === 2) {
            const r = Math.random();
            if (stRatio >= 0.30) {
                if (r < 0.70) return "dribble";
                if (r < 0.95) return "pass";
                return "shot";
            }
            return (r < 0.80) ? "pass" : "dribble";
        }

        // Z3 : zone dangereuse -> dribble / shot / special parfois
        if (z === 3) {
            const r = Math.random();
            if (stRatio >= 0.35) {
                if (r < 0.60) return "dribble";
                if (r < 0.85) return "shot";
                if (r < 0.95) return "pass";
                return "special";
            }
            if (r < 0.50) return "pass";
            if (r < 0.85) return "shot";
            return "dribble";
        }

        // Z4 : très dangereux -> shot/special majoritaires
        if (z >= 4) {
            const r = Math.random();
            if (stRatio >= 0.30) {
                if (r < 0.55) return "shot";
                if (r < 0.85) return "special";
                return "pass";
            }
            return (r < 0.70) ? "shot" : "pass";
        }

        return "pass";
    }

    // Programme l’attaque IA avec overlay + délai “réflexion”.
    function scheduleAIAttack() {
        if (!isAITeam(state.currentTeam) || state.phase !== "attack" || state.isGameOver) return;

        setAIOverlay(true, TEXTS.ui.aiAttackTurn);
        const action = computeAIAttackChoice();

        setTimeout(() => {
            setAIOverlay(false);
            handleAttackClick(action);
        }, AI_THINK_MS);
    }

    // Choisit l’action défensive IA selon action attaque et contexte GK/field.
    function computeAIDefenseChoice(attackAction, defendingTeam, opts = {}) {
        const { isKeeperDuel = false } = opts;
        const r = Math.random();

        if (isKeeperDuel) {
            const attackerTeam = otherTeam(defendingTeam);
            const shooterSlot = ball.number;

            const threat = Math.max(
                roster.getStat(attackerTeam, shooterSlot, "shot"),
                roster.getStat(attackerTeam, shooterSlot, "attack")
            );

            const keeperId = getKeeperId(defendingTeam);
            const gkRatio = keeperId ? getStaminaRatio(keeperId) : 1.0;

            if (threat >= 35) {
                if (gkRatio >= 0.30) return (r < 0.25) ? "gk-special" : "hands";
                if (gkRatio >= 0.15) return "hands";
                return "punch";
            }

            if (gkRatio >= 0.20) return (r < 0.70) ? "hands" : "punch";
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

    // Programme la défense IA (overlay + délai) puis déclenche handleDefenseClick.
    function scheduleAIDefense(attackAction, defendingTeam) {
        if (!isAITeam(defendingTeam) || state.phase !== "defense" || !state.pendingAttack || state.isGameOver) return;

        setAIOverlay(true, TEXTS.ui.aiDefenseTurn);

        const isKeeperDuel =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            ball.frontOfKeeper;

        const defense = computeAIDefenseChoice(attackAction, defendingTeam, { isKeeperDuel });

        setTimeout(() => {
            setAIOverlay(false);
            handleDefenseClick(defense);
        }, AI_THINK_MS);
    }

    // ==========================
    //   Kickoff (passe auto)
    // ==========================

    // Résout la passe de kickoff automatiquement (vers 5/6) puis rend la main.
    function resolveKickoffPass(attackTeam) {
        if (!state.isKickoff) return;
        state.isKickoff = false;

        const dmNumbers = [5, 6];
        const number = dmNumbers[Math.floor(Math.random() * dmNumbers.length)];

        const attackerId = getPlayerId(attackTeam, ball.number);
        applyStaminaCost(attackerId, "attack", "pass");

        setMessage(TEXTS.logs.kickoffTitle, `${TEAMS[attackTeam].label} joue court vers le n°${number}.`);
        pushLogEntry("kickoffTitle", [`Vers n°${number}`, "Auto"]);

        animateAndThen(() => {
            restoreBasePositions();
            moveBallToPlayer(attackTeam, number);
            advanceTurn(attackTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   Defender pick (field)
    // ==========================

    // Sélectionne un défenseur “réaliste” dans la zone opposée à l’attaque.

    function mapAttackZoneToDefenseZone(originZone) {
        if (originZone <= 0) return 1;           // GK -> on considère la 1ère ligne défensive
        const mapped = 5 - originZone;          // 1->4, 2->3, 3->2, 4->1
        return Math.max(1, Math.min(4, mapped));
    }

    function pickFieldDefender(defenseTeam, originZone, originLane) {
        // 1) Déterminer la zone de défense à partir de la zone d'attaque (règle métier)
        const defZone = mapAttackZoneToDefenseZone(originZone);

        // 2) Choisir un défenseur dans cette zone, verticalement libre (ignoreLane = true)
        const defenderId = pickWeightedPlayerInZone(defenseTeam, defZone, originLane, {
            topK: 8,
            ignoreLane: true,   // ✅ variabilité haut / milieu / bas dans la même zone
        });

        if (!defenderId) return null;

        return {
            defenderId,
            defenderSlot: parseInt(defenderId.slice(1), 10),
            defZone,
        };
    }

    // ==========================
    //   Breakdown duel (field)
    // ==========================

    // Construit l’objet breakdown détaillé pour un duel de champ (tooltip dés).
    function buildFieldDuelBreakdown({
                                         attackBaseRaw,
                                         defenseBaseRaw,
                                         attackStamF,
                                         defenseStamF,
                                         aRoll,
                                         dRoll,
                                         isGood,
                                         attackScore,
                                         defenseScore,
                                         clearanceBonus = 0,
                                     }) {
        const aTag = aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll));
        const dTag = dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll));

        const goodBonus = DUEL_RULES.GOOD_COUNTER_BONUS ?? 0;
        const genericBonus = DUEL_RULES.GENERIC_ATTACK_BONUS ?? 0;

        return {
            rolls: { aTag, dTag, aBonus: aRoll.bonus, dBonus: dRoll.bonus },
            attack: {
                base: attackBaseRaw,
                staminaFactor: attackStamF,
                additions: [
                    ...(clearanceBonus ? [{ label: "Clearance bonus", value: `+ ${Number(clearanceBonus).toFixed(2)}` }] : []),
                    { label: "d20 bonus", value: `+ ${aRoll.bonus.toFixed(2)}` },
                    ...(!isGood ? [{ label: "Generic bonus", value: `+ ${genericBonus.toFixed(2)}` }] : []),
                ],
                total: attackScore,
            },
            defense: {
                base: defenseBaseRaw,
                staminaFactor: defenseStamF,
                additions: [
                    { label: "d20 bonus", value: `+ ${dRoll.bonus.toFixed(2)}` },
                    ...(isGood ? [{ label: "Good counter bonus", value: `+ ${goodBonus.toFixed(2)}` }] : []),
                ],
                total: defenseScore,
            },
            result: {
                bonusRuleLabel: isGood
                    ? `Good counter (+${goodBonus} defense)`
                    : `Generic attack (+${genericBonus} attack)`,
                critWinner: null,
                diff: attackScore - defenseScore,
                winner: (attackScore - defenseScore) > 0 ? "attack" : (attackScore - defenseScore) < 0 ? "defense" : "tie",
            },
        };
    }

    // ==========================
    //   Duel champ
    // ==========================

    // Exécute un duel de champ (attaque vs défenseur pické), applique stamina, et retourne l’issue.
    function runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick = null }) {
        const attackerId = getPlayerId(attackTeam, ball.number);

        const picked = defenderPick ?? pickFieldDefender(defenseTeam, ball.zoneIndex, ball.laneIndex);
        if (!picked) {
            givePossessionOnTie(defenseTeam);
            return { isTie: true, duelResult: "tie", diceTag: "" };
        }

        const { defenderId, defenderSlot } = picked;

        updateSideCard(attackTeam === "internal" ? "home" : "away", attackTeam, ball.number);
        updateSideCard(defenseTeam === "internal" ? "home" : "away", defenseTeam, defenderSlot);


        syncRecovererCard(defenseTeam, defenderSlot);
        updateTeamCard();

        const attackBaseRaw = roster.attackBaseFor(attackType, attackTeam, ball.number);
        const defenseBaseRaw = roster.defenseBaseFor(defenseAction, defenseTeam, defenderSlot, false);

        const attackStamF = staminaFactor(attackerId);
        const defenseStamF = staminaFactor(defenderId);

        let attackScore = attackBaseRaw * attackStamF;
        let defenseScore = defenseBaseRaw * defenseStamF;

        const clearanceBonus = state.pendingClearanceBonus > 0 ? state.pendingClearanceBonus : 0;
        if (state.pendingClearanceBonus > 0) {
            attackScore += state.pendingClearanceBonus;
            state.pendingClearanceBonus = 0;
        }

        const aRoll = rollD20WithCrit();
        const dRoll = rollD20WithCrit();
        const critWinner = resolveCritOutcome(aRoll, dRoll);

        attackScore += aRoll.bonus;
        defenseScore += dRoll.bonus;

        const isGood = isGoodDefenseChoice(attackType, defenseAction);
        if (isGood) defenseScore += DUEL_RULES.GOOD_COUNTER_BONUS;
        else attackScore += DUEL_RULES.GENERIC_ATTACK_BONUS;

        const breakdown = buildFieldDuelBreakdown({
            attackBaseRaw,
            defenseBaseRaw,
            attackStamF,
            defenseStamF,
            aRoll,
            dRoll,
            isGood,
            attackScore,
            defenseScore,
            clearanceBonus,
        });

        showDuelDice(attackScore, defenseScore, aRoll, dRoll, breakdown);

        applyStaminaCost(attackerId, "attack", attackType);
        applyStaminaCost(defenderId, "defenseField", defenseAction);

        if (attackType === "special") markSpecialUsed(attackerId);
        if (defenseAction === "field-special") markSpecialUsed(defenderId);

        const diceTag = `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`;

        if (critWinner) return { isTie: false, duelResult: critWinner, defenderId, defenderSlot, diceTag };

        const diff = attackScore - defenseScore;
        if (diff === 0) {
            givePossessionOnTie(defenseTeam, defenderId);
            return { isTie: true, duelResult: "tie", defenderId, defenderSlot, diceTag };
        }

        return {
            isTie: false,
            duelResult: diff > 0 ? "attack" : "defense",
            defenderId,
            defenderSlot,
            diceTag,
        };
    }

    // ==========================
    //   SHOT animations
    // ==========================

    // Anime le ballon dans le but puis exécute un callback de reset.
    function animateGoalThenReset(attackTeam, afterGoalCallback) {
        if (!ui.ballEl) { if (afterGoalCallback) afterGoalCallback(); return; }

        const goalX = FIELD_RULES.GOAL_X[attackTeam];
        const goalY = FIELD_RULES.GOAL_Y;

        ui.ballEl.style.left = `${goalX}%`;
        ui.ballEl.style.top = `${goalY}%`;

        animateAndThen(() => { if (afterGoalCallback) afterGoalCallback(); });
    }

    // Anime un tir “vers le gardien” (pose ballon sur GK + hold).
    function animateShotToKeeper(defenseTeam, afterAnimation) {
        setBallToKeeperVisual(defenseTeam);
        animateAndThen(() => {
            setTimeout(() => { if (afterAnimation) afterAnimation(); }, GK_HOLD_MS);
        });
    }

    // Anime le ballon vers une position (x,y) puis exécute un callback.
    function animateBallToXY(xPercent, yPercent, afterAnimation) {
        if (!ui.ballEl) { if (afterAnimation) afterAnimation(); return; }

        ui.ballEl.style.left = `${xPercent}%`;
        ui.ballEl.style.top = `${yPercent}%`;

        animateAndThen(() => { if (afterAnimation) afterAnimation(); });
    }

    // ==========================
    //   GK clearance (AUTO)
    // ==========================

    // Exécute une relance automatique du gardien (choix distance/lane/receveur + bonus).
    function performKeeperClearance(defenseTeam, defenseAction, afterClearance = null) {
        const keeperEl = rootEl.querySelector(
            defenseTeam === "internal"
                ? '.player.internal.goalkeeper[data-player="I1"]'
                : '.player.external.goalkeeper[data-player="E1"]'
        );

        if (!keeperEl) {
            const receiver = pickReceiverInCell(defenseTeam, ball.zoneIndex, ball.laneIndex, 6, null);
            ball.frontOfKeeper = false;
            resetLastDribbler();
            moveBallToPlayer(defenseTeam, receiver);
            state.keeperRestartMustPass = true;
            if (afterClearance) afterClearance();
            return;
        }

        const kx = parseFloat(keeperEl.style.left);
        const ky = parseFloat(keeperEl.style.top);

        const xInternal = defenseTeam === "internal" ? kx : (100 - kx);

        let originZone = 0;
        for (let i = 0; i < ZONE_BOUNDS_INTERNAL.length - 1; i++) {
            if (xInternal >= ZONE_BOUNDS_INTERNAL[i] && xInternal <= ZONE_BOUNDS_INTERNAL[i + 1]) {
                originZone = i;
                break;
            }
        }

        let originLane = 0;
        let best = Infinity;
        laneY.forEach((vy, i) => {
            const d = Math.abs(vy - ky);
            if (d < best) { best = d; originLane = i; }
        });

        state.pendingClearanceBonus =
            defenseAction === "hands" ? 5 :
                defenseAction === "punch" ? 4 : 7;

        const forwardZone = (n) =>
            defenseTeam === "internal"
                ? Math.min(3, originZone + n)
                : Math.max(0, originZone - n);

        const r = Math.random();
        let targetZone;

        if (defenseAction === "hands") {
            targetZone = forwardZone(r < 0.8 ? 1 : 2);
        } else if (defenseAction === "punch") {
            targetZone = forwardZone(r < 0.45 ? 1 : r < 0.9 ? 2 : 3);
        } else {
            targetZone = forwardZone(r < 0.15 ? 2 : r < 0.7 ? 3 : 4);
        }

        let laneOptions = [originLane];
        if (defenseAction !== "hands") {
            if (originLane > 0) laneOptions.push(originLane - 1);
            if (originLane < laneY.length - 1) laneOptions.push(originLane + 1);
        }
        if (defenseAction === "gk-special") laneOptions = [0, 1, 2];

        const targetLane = laneOptions[Math.floor(Math.random() * laneOptions.length)];
        const receiver = pickReceiverInCell(defenseTeam, targetZone, targetLane, 6, null);

        setMessage(TEXTS.ui.keeperRestartMain, `${TEXTS.ui.keeperRestartSub} (#${receiver})`);
        pushLogEntry("keeperRestartMain", [
            `Action: ${defenseAction}`,
            `Bonus +${state.pendingClearanceBonus}`,
            `Vers zone ${targetZone + 1}, ligne ${targetLane + 1}`,
            `Receveur: #${receiver}`,
        ]);

        ui.ballEl.style.left = `${kx}%`;
        ui.ballEl.style.top = `${ky}%`;
        ui.ballEl.textContent = "1";

        const receiverEl = getCarrierElement(defenseTeam, receiver);
        if (!receiverEl) {
            ball.frontOfKeeper = false;
            resetLastDribbler();
            moveBallToPlayer(defenseTeam, receiver);
            state.keeperRestartMustPass = true;
            if (afterClearance) afterClearance();
            return;
        }

        const rx = parseFloat(receiverEl.style.left);
        const ry = parseFloat(receiverEl.style.top);

        setTimeout(() => {
            animateBallToXY(rx, ry, () => {
                ball.frontOfKeeper = false;
                resetLastDribbler();
                moveBallToPlayer(defenseTeam, receiver);
                state.keeperRestartMustPass = true;
                if (afterClearance) afterClearance();
            });
        }, GK_HOLD_MS);
    }

    // ==========================
    //   RESOLVE: PASS
    // ==========================

    // Résout une passe (duel champ), puis déplace la balle et change de tour.
    function resolvePass(attackTeam, defenseTeam, defenseAction, defenderPick = null) {
        const wasKickoff = state.isKickoff;
        state.isKickoff = false;

        const duel = runFieldDuel({
            attackTeam,
            defenseTeam,
            attackType: "pass",
            defenseAction,
            defenderPick,
        });

        if (duel.isTie) {
            pushLogEntry("Duel équilibré (pass)", [`Défense: ${defenseAction}`], duel.diceTag);
            state.phase = "attack";
            state.pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        const duelResult = duel.duelResult;

        if (state.keeperRestartMustPass) state.keeperRestartMustPass = false;

        // ==========================
        //   CAS KICKOFF
        // ==========================
        if (wasKickoff) {
            if (duelResult === "attack") {
                const receiver = [5, 6][Math.floor(Math.random() * 2)];

                setMessage("Remise en jeu réussie !", `${TEAMS[attackTeam].label} joue vers le n°${receiver}.`);
                pushLogEntry(
                    "kickoffTitle",
                    [`Vers n°${receiver}`, `Défense: ${defenseAction}`, getCounterTag("pass", defenseAction)],
                    duel.diceTag
                );

                animateAndThen(() => {
                    restoreBasePositions();
                    moveBallToPlayer(attackTeam, receiver);
                    advanceTurn(attackTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
            } else {
                const receiver = duel.defenderSlot ?? 6;

                const verb = (defenseAction === "intercept") ? "intercepte" : "récupère";

                setMessage("Remise en jeu ratée !", `${TEAMS[defenseTeam].label} ${verb} avec le n°${receiver}.`);
                pushLogEntry(
                    "kickoffTitle",
                    [`Défense: ${defenseAction}`, getCounterTag("pass", defenseAction)],
                    duel.diceTag
                );

                animateAndThen(() => {
                    restoreBasePositions();
                    moveBallToPlayer(defenseTeam, receiver);
                    advanceTurn(defenseTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
            }
            return;
        }

        // ==========================
        //   CIBLE DE PASSE
        // ==========================
        let targetZone = ball.zoneIndex;
        let targetLane = ball.laneIndex;

        if (ball.zoneIndex < MAX_ZONE_INDEX) {
            targetZone = ball.zoneIndex + 1;

            const laneOptions = [ball.laneIndex];
            if (ball.laneIndex > 0) laneOptions.push(ball.laneIndex - 1);
            if (ball.laneIndex < laneY.length - 1) laneOptions.push(ball.laneIndex + 1);

            targetLane = laneOptions[Math.floor(Math.random() * laneOptions.length)];
        } else {
            // déjà en dernière zone : on varie la lane
            targetZone = MAX_ZONE_INDEX;
            targetLane = [0, 1, 2][Math.floor(Math.random() * 3)];
        }

        // ==========================
        //   ATTAQUE GAGNE
        // ==========================
        if (duelResult === "attack") {
            resetLastDribbler();

            const carrierEl = getCarrierElement(attackTeam, ball.number);
            const carrierXInternal = carrierEl ? getPlayerXInternal(attackTeam, carrierEl) : null;

            const receiver = pickReceiverInCell(
                attackTeam,
                targetZone,
                targetLane,
                ball.number,
                ball.number,
                {
                    forwardOnly: true,
                    forwardFromXInternal: carrierXInternal,
                    ignoreLaneForInitialPick: true,
                }
            );

            moveBallToPlayer(attackTeam, receiver);

            setMessage(TEXTS.logs.passSuccessTitle, `${TEAMS[attackTeam].label} trouve le n°${receiver}.`);
            pushLogEntry(
                "passSuccessTitle",
                [`Vers n°${receiver}`, `Défense: ${defenseAction}`, getCounterTag("pass", defenseAction)],
                duel.diceTag
            );

            animateAndThen(() => {
                advanceTurn(attackTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        // ==========================
        //   DÉFENSE GAGNE
        // ==========================
        resetLastDribbler();

        const receiver =
            duel.defenderSlot ??
            (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);

        moveBallToPlayer(defenseTeam, receiver);
        syncRecovererCard(defenseTeam, receiver);

        const logTitle = getLogTitleForDuel("pass", defenseAction, "defense");
        const msgTitle = (defenseAction === "intercept") ? TEXTS.logs.passFailTitle : TEXTS.logs.passRecoveredTitle;

        setMessage(msgTitle, `${TEAMS[defenseTeam].label} récupère avec le n°${receiver}.`);
        pushLogEntry(
            logTitle,
            [`Défense: ${defenseAction}`, getCounterTag("pass", defenseAction)],
            duel.diceTag
        );

        animateAndThen(() => {
            advanceTurn(defenseTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   RESOLVE: DRIBBLE
    // ==========================
    // Résout un dribble (duel champ) et gère l’avancée ou la perte de balle.
    function resolveDribble(attackTeam, defenseTeam, defenseAction, defenderPick = null) {
        // Si déjà face au gardien, on interdit le dribble
        if (ball.frontOfKeeper) {
            setMessage(TEXTS.ui.dribbleForbiddenMain, TEXTS.ui.dribbleForbiddenSub);
            pushLogEntry("dribbleRefusedTitle", ["dribbleRefusedDetail"]);
            state.phase = "attack";
            state.pendingAttack = null;
            return;
        }

        const oldZone = ball.zoneIndex;
        const lane = ball.laneIndex;

        const duel = runFieldDuel({
            attackTeam,
            defenseTeam,
            attackType: "dribble",
            defenseAction,
            defenderPick,
        });

        if (duel.isTie) {
            pushLogEntry("Duel équilibré (dribble)", [`Défense: ${defenseAction}`], duel.diceTag);
            state.phase = "attack";
            state.pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        const duelResult = duel.duelResult;
        const carrierId = getPlayerId(attackTeam, ball.number);
        const carrierEl = rootEl.querySelector(`[data-player="${carrierId}"]`);

        // ==========================
        //   ATTAQUE GAGNE
        // ==========================
        if (duelResult === "attack") {
            resetLastDribbler();
            state.lastDribblerId = carrierId;

            // Cas 1 : on n’est pas encore dans la dernière zone → on avance d’UNE zone
            if (oldZone < MAX_ZONE_INDEX) {
                const newZone = Math.min(MAX_ZONE_INDEX, oldZone + 1);

                // Simple avancée : on NE PASSE PAS encore face au gardien,
                // même si on entre dans la dernière zone.
                if (carrierEl && ui.ballEl) {
                    const currentY = parseFloat(carrierEl.style.top);
                    const center = getCellCenter(attackTeam, newZone, lane);

                    carrierEl.style.left = `${center.x}%`;
                    carrierEl.style.top = `${currentY}%`;
                    ui.ballEl.style.left = `${center.x}%`;
                    ui.ballEl.style.top = `${currentY}%`;
                }

                if (carrierEl) {
                    carrierEl.dataset.zone = String(newZone);
                }

                ball.zoneIndex = newZone;
                ball.laneIndex = lane;
                ball.frontOfKeeper = false; // ⚠ on reste "avant la défense"

                setMessage(
                    "Dribble réussi !",
                    `${TEAMS[attackTeam].label} avance en zone ${newZone + 1}.`
                );
                pushLogEntry(
                    "Dribble réussi",
                    [`Zone ${newZone + 1}`, `Défense: ${defenseAction}`, getCounterTag("dribble", defenseAction)],
                    duel.diceTag
                );

                animateAndThen(() => {
                    advanceTurn(attackTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
                return;
            }

            // Cas 2 : on est DÉJÀ dans la dernière zone → 2e dribble gagné => face au gardien
            if (oldZone === MAX_ZONE_INDEX) {
                const y = laneY[lane];
                const xFront = FIELD_RULES.GK_FRONT_X[attackTeam];

                if (carrierEl && ui.ballEl) {
                    carrierEl.style.left = `${xFront}%`;
                    carrierEl.style.top = `${y}%`;
                    ui.ballEl.style.left = `${xFront}%`;
                    ui.ballEl.style.top = `${y}%`;
                }

                // Zone inchangée, on reste dans la dernière zone
                if (carrierEl) {
                    carrierEl.dataset.zone = String(oldZone);
                }

                ball.zoneIndex = oldZone;
                ball.laneIndex = lane;
                ball.frontOfKeeper = true;

                setMessage(TEXTS.ui.frontOfKeeperMain, TEXTS.ui.frontOfKeeperSub);
                pushLogEntry(
                    "frontOfKeeperTitle",
                    [`Zone ${oldZone + 1}`, `Défense: ${defenseAction}`, getCounterTag("dribble", defenseAction)],
                    duel.diceTag
                );

                animateAndThen(() => {
                    advanceTurn(attackTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
                return;
            }
        }

        // ==========================
        //   DÉFENSE GAGNE
        // ==========================
        resetLastDribbler();

        const slot =
            duel.defenderSlot ??
            (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);

        moveBallToPlayer(defenseTeam, slot);
        syncRecovererCard(defenseTeam, slot);

        const logTitle = getLogTitleForDuel("dribble", defenseAction, "defense");
        const msgTitle = (defenseAction === "tackle") ? "Dribble stoppé" : TEXTS.logs.dribbleRecoveredTitle;

        setMessage(msgTitle, `${TEAMS[defenseTeam].label} récupère avec le n°${slot}.`);
        pushLogEntry(
            logTitle,
            [`Défense: ${defenseAction}`, getCounterTag("dribble", defenseAction)],
            duel.diceTag
        );

        animateAndThen(() => {
            advanceTurn(defenseTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   RESOLVE: SHOT
    // ==========================

    function resolveShot(attackTeam, defenseTeam, defenseAction, isSpecial = false, defenderPick = null) {
        const originZone = ball.zoneIndex;
        const originLane = ball.laneIndex;

        const attackerId = getPlayerId(attackTeam, ball.number);
        const attackType = isSpecial ? "special" : "shot";

        // CAS 1 : déjà face au gardien -> duel GK direct (inchangé)
        if (ball.frontOfKeeper) {
            resolveShotKeeperDuel({
                stage: "keeper",
                attackTeam,
                defenseTeam,
                originZone,
                originLane,
                isSpecial,
                gkAttackBase: roster.attackBaseFor(attackType, attackTeam, ball.number),
                logParts: [`Zone ${originZone + 1}`],
            }, defenseAction);
            return;
        }

        //  DUEL CHAMP (inchangé)
        const duel = runFieldDuel({
            attackTeam,
            defenseTeam,
            attackType,
            defenseAction,
            defenderPick
        });

        if (duel.isTie) {
            pushLogEntry(
                "Duel équilibré (shot)",
                [`Défense: ${defenseAction}`, getCounterTag(attackType, defenseAction)],
                duel.diceTag
            );
            state.phase = "attack";
            state.pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        const duelResult = duel.duelResult;

        // DÉFENSE GAGNE (inchangé)
        if (duelResult === "defense") {
            const number =
                duel.defenderSlot ??
                (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);

            moveBallToPlayer(defenseTeam, number);
            syncRecovererCard(defenseTeam, number);

            const isBlock = (defenseAction === "block");

            const main = isBlock ? TEXTS.ui.shotBlockedMain : TEXTS.ui.shotRecoveredMain;
            const subTpl = isBlock ? TEXTS.ui.shotBlockedSub : TEXTS.ui.shotRecoveredSub;

            setMessage(
                main,
                subTpl
                    .replace("{team}", TEAMS[defenseTeam].label)
                    .replace("{number}", number)
            );

            const logTitle = getLogTitleForDuel(attackType, defenseAction, "defense");
            pushLogEntry(
                logTitle,
                [`Défense: ${defenseAction}`, getCounterTag(attackType, defenseAction)],
                duel.diceTag
            );

            state.phase = "attack";
            state.pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        // ATTAQUE GAGNE (tir cadré -> passage gardien)
        pushLogEntry(
            TEXTS.ui.shotOnTargetMain,
            [`Zone ${originZone + 1}`, `Défense: ${defenseAction}`, getCounterTag(attackType, defenseAction)],
            duel.diceTag
        );
        setMessage(
            TEXTS.ui.shotOnTargetMain,
            TEXTS.ui.shotOnTargetSub.replace("{team}", TEAMS[defenseTeam].label)
        );

        const zonesToGoal = Math.max(0, MAX_ZONE_INDEX - originZone);
        const gkAttackBase =
            roster.attackBaseFor(attackType, attackTeam, ball.number) -
            (zonesToGoal * DUEL_RULES.SHOT_DISTANCE_PENALTY_PER_LINE);

        const targetZone = MAX_ZONE_INDEX;
        const center = getCellCenter(attackTeam, targetZone, originLane);
        ball.zoneIndex = targetZone;
        ball.laneIndex = originLane;

        if (ui.ballEl) {
            ui.ballEl.style.left = center.x + "%";
            ui.ballEl.style.top = center.y + "%";
        }

        // On marque qu'on entre en phase gardien
        state.pendingShotContext = {
            stage: "keeper",
            attackTeam,
            defenseTeam,
            originZone,
            originLane,
            isSpecial,
            gkAttackBase,
            logParts: [`Zone ${originZone + 1}`],
        };

        // 🧤 Animation vers le gardien, puis affichage des choix GK
        animateShotToKeeper(defenseTeam, () => {
            // ✅ NOUVEAU : la défense est désormais le gardien -> on met la carte à jour
            const defenderPrefix = (defenseTeam === "internal") ? "home" : "away";
            updateSideCard(defenderPrefix, defenseTeam, 1);

            // ✅ On aligne aussi le contexte défenseur sur le gardien (cohérence état)
            state.pendingDefenseContext = {
                defenseTeam,
                defenderId: getKeeperId(defenseTeam),
                defenderSlot: 1,
            };

            // Barre de défense gardien (inchangé)
            setActionBar(buildDefenseGKHTML(), `mode-defense-${defenseTeam}`);

            setMessage(
                TEXTS.ui.shotOnTargetMain,
                TEXTS.ui.shotGKChoiceSub.replace("{team}", TEAMS[defenseTeam].label)
            );

            state.phase = "defense";
            state.pendingAttack = attackType;

            if (isAITeam(defenseTeam)) scheduleAIDefense(attackType, defenseTeam);
        });
    }

    // ==========================
    //   DUEL GK
    // ==========================

    // Résout le duel tireur vs gardien (avec crit, bonus, stamina, but/relance).
    function resolveShotKeeperDuel(ctx, defenseAction) {
        const { attackTeam, defenseTeam, originZone, isSpecial, gkAttackBase, logParts } = ctx;

        const attackerId = getPlayerId(attackTeam, ball.number);
        const keeperId = getKeeperId(defenseTeam);

        let attackScore = gkAttackBase * staminaFactor(attackerId);
        const gkFactor = keeperId ? staminaFactor(keeperId) : 1.0;
        let defenseScore = roster.defenseBaseFor(defenseAction, defenseTeam, 1, true) * gkFactor;

        const aRoll = rollD20WithCrit();
        const dRoll = rollD20WithCrit();
        const critWinner = resolveCritOutcome(aRoll, dRoll);

        attackScore += aRoll.bonus;
        defenseScore += dRoll.bonus;

        ({ attackScore, defenseScore } = applyDuelBonuses({
            attackAction: isSpecial ? "special" : "shot",
            defenseAction,
            attackScore,
            defenseScore,
            context: { isKeeperDuel: true },
        }));

        const diceTag = `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`;

        showDuelDice(attackScore, defenseScore, aRoll, dRoll, {
            rolls: {
                aTag: aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll)),
                dTag: dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll)),
                aBonus: aRoll.bonus,
                dBonus: dRoll.bonus,
            },
            attack: { base: gkAttackBase, staminaFactor: staminaFactor(attackerId), additions: [], total: attackScore },
            defense: { base: roster.defenseBaseFor(defenseAction, defenseTeam, 1, true), staminaFactor: gkFactor, additions: [], total: defenseScore },
            result: {
                bonusRuleLabel: "",
                critWinner,
                diff: attackScore - defenseScore,
                winner: critWinner ? critWinner : (attackScore > defenseScore ? "attack" : attackScore < defenseScore ? "defense" : "tie"),
            }
        });

        applyStaminaCost(attackerId, "attack", isSpecial ? "special" : "shot");
        if (keeperId) applyStaminaCost(keeperId, "defenseGK", defenseAction);

        if (isSpecial) markSpecialUsed(attackerId);
        if (defenseAction === "gk-special" && keeperId) markSpecialUsed(keeperId);

        ball.frontOfKeeper = false;
        resetLastDribbler();

        if (!critWinner && attackScore === defenseScore) {
            pushLogEntry("shotGKEqualTitle", [`Zone ${originZone + 1}`], diceTag);

            performKeeperClearance(defenseTeam, "hands", () => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        const duelResult = critWinner ?? (attackScore > defenseScore ? "attack" : "defense");

        if (duelResult === "attack") {
            state.score[attackTeam]++;

            setMessage(
                (isSpecial ? TEXTS.ui.goalSpecialMain : TEXTS.ui.goalMain).replace("{team}", TEAMS[attackTeam].label),
                TEXTS.ui.goalSub
                    .replace("{scoreInternal}", state.score.internal)
                    .replace("{scoreExternal}", state.score.external)
            );

            pushLogEntry(
                isSpecial ? "shotGoalSpecialTitle" : "shotGoalTitle",
                [`Zone ${originZone + 1}`, ...logParts],
                diceTag
            );

            animateGoalThenReset(attackTeam, () => {
                state.isKickoff = true;
                state.keeperRestartMustPass = false;
                applyKickoffPositions();
                moveBallToPlayer(defenseTeam, 8);
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        pushLogEntry(isSpecial ? "shotSavedTitle" : "shotSavedTitle", [`Zone ${originZone + 1}`, ...logParts], diceTag);


        performKeeperClearance(defenseTeam, defenseAction, () => {
            advanceTurn(defenseTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   DEFENDER PREVIEW
    // ==========================

    // Met à jour la card défenseur “preview” selon l’action (GK ou field, pick figé si présent).
    function setDefenderPreviewFor(action, defenseTeam) {
        const defenderPrefix = (defenseTeam === "internal") ? "home" : "away";
        const isKeeperStage =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            (ball.frontOfKeeper && (action === "shot" || action === "special"));

        if (isKeeperStage) {
            updateSideCard(defenderPrefix, defenseTeam, 1);
            return;
        }

        if (state.pendingDefenseContext?.defenderSlot) {
            updateSideCard(defenderPrefix, defenseTeam, state.pendingDefenseContext.defenderSlot);
            return;
        }

        const picked = pickFieldDefender(defenseTeam, ball.zoneIndex, ball.laneIndex);

        state.defensePreview = picked
            ? {
                attackAction: action,
                defenseTeam,
                ballSnapshot: {
                    team: ball.team,
                    number: ball.number,
                    zoneIndex: ball.zoneIndex,
                    laneIndex: ball.laneIndex,
                    frontOfKeeper: ball.frontOfKeeper,
                },
                picked,
            }
            : null;

        updateSideCard(defenderPrefix, defenseTeam, picked?.defenderSlot || 6);
    }

    // ==========================
    //   BAR show
    // ==========================

    // Affiche la barre d’attaque pour l’équipe courante et prépare la preview défense.
    function showAttackBarForCurrentTeam() {
        if (state.isGameOver) return;

        setActionBar(buildAttackActionsHTML(), `mode-attack-${state.currentTeam}`);

        const defTeam = otherTeam(state.currentTeam);
        const defaultAction = state.isKickoff ? "pass" : (ball.frontOfKeeper ? "shot" : "pass");
        setDefenderPreviewFor(defaultAction, defTeam);

        if (isAITeam(state.currentTeam)) scheduleAIAttack();
        updateCardsPower();
    }

    // ==========================
    //   HANDLERS
    // ==========================

    // Handler clic attaque : valide contexte, fige défenseur, puis affiche choix défense.
    function handleAttackClick(action) {
        if (state.isGameOver || state.isAnimating) return;
        if (state.turns >= GAME_RULES.MAX_TURNS || state.phase !== "attack") return;
        if (!["shot","pass","dribble","special"].includes(action)) return;

        if (state.isKickoff) {
            if (action !== "pass") return;
            resolveKickoffPass(state.currentTeam);
            return;
        }

        if (ball.frontOfKeeper && action !== "shot" && action !== "special") return;

        if (action === "special") {
            const attackerId = getPlayerId(state.currentTeam, ball.number);
            if (!canUseSpecial(attackerId)) {
                setMessage(TEXTS.ui.specialCooldownMain, TEXTS.ui.specialCooldownSub);
                state.phase = "attack";
                state.pendingAttack = null;
                return;
            }
        }

        state.pendingAttack = action;
        state.phase = "defense";

        const defTeam = otherTeam(state.currentTeam);

        const isKeeperChoiceUI = (action === "shot" || action === "special") && ball.frontOfKeeper;

        if (!isKeeperChoiceUI) {
            const snapOk =
                state.defensePreview &&
                state.defensePreview.attackAction === action &&
                state.defensePreview.defenseTeam === defTeam &&
                state.defensePreview.ballSnapshot &&
                state.defensePreview.ballSnapshot.team === ball.team &&
                state.defensePreview.ballSnapshot.number === ball.number &&
                state.defensePreview.ballSnapshot.zoneIndex === ball.zoneIndex &&
                state.defensePreview.ballSnapshot.laneIndex === ball.laneIndex &&
                state.defensePreview.ballSnapshot.frontOfKeeper === ball.frontOfKeeper;

            const picked = snapOk
                ? state.defensePreview.picked
                : pickFieldDefender(defTeam, ball.zoneIndex, ball.laneIndex);

            state.pendingDefenseContext = { defenseTeam: defTeam, ...picked };

            state.defensePreview = null;
        } else {
            state.pendingDefenseContext = {
                defenseTeam: defTeam,
                defenderId: getKeeperId(defTeam),
                defenderSlot: 1
            };
        }

        const defenderPrefix = (defTeam === "internal") ? "home" : "away";
        updateSideCard(defenderPrefix, defTeam, state.pendingDefenseContext.defenderSlot || 6);

        let html;
        if (action === "shot" || action === "special") {
            if (ball.frontOfKeeper) {
                html = buildDefenseGKHTML();
                setMessage(
                    `${TEAMS[state.currentTeam].label} prépare un ${action === "special" ? "TIR SPÉCIAL" : "TIR"} !`,
                    `${TEAMS[defTeam].label} (gardien) : Arrêt main / Poing / Special.`,
                );
            } else {
                html = buildDefenseFieldHTML();
                setMessage(
                    `${TEAMS[state.currentTeam].label} tente un ${action === "special" ? "TIR SPÉCIAL" : "TIR"} !`,
                    `${TEAMS[defTeam].label} : Block / Intercept / Tackle / Special.`,
                );
            }
        } else {
            html = buildDefenseFieldHTML();
            setMessage(
                `${TEAMS[state.currentTeam].label} prépare un ${action.toUpperCase()} !`,
                `${TEAMS[defTeam].label} : Block / Intercept / Tackle / Special.`,
            );
        }

        setActionBar(html, `mode-defense-${defTeam}`);
        if (isAITeam(defTeam)) scheduleAIDefense(action, defTeam);
    }

    // Handler clic défense : normalise action, check cooldown, et déclenche le bon resolve*().
    function handleDefenseClick(defense) {
        if (state.turns >= GAME_RULES.MAX_TURNS || state.isAnimating || state.phase !== "defense" || !state.pendingAttack) return;

        const isKeeperDuel =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            ball.frontOfKeeper;

        if (isKeeperDuel && !["hands", "punch", "gk-special"].includes(defense)) {
            defense = "hands";
        }

        const attackTeam = state.currentTeam;
        const defenseTeam = otherTeam(state.currentTeam);
        const attack = state.pendingAttack;

        if (defense === "field-special") {
            const defenderId = state.pendingDefenseContext?.defenderId ?? null;
            if (defenderId && !canUseSpecial(defenderId)) defense = "block";
        }

        if (defense === "gk-special") {
            const keeperId = getKeeperId(defenseTeam);
            if (keeperId && !canUseSpecial(keeperId)) defense = "hands";
        }

        if ((attack === "shot" || attack === "special") &&
            state.pendingShotContext &&
            state.pendingShotContext.stage === "keeper") {
            resolveShotKeeperDuel(state.pendingShotContext, defense);
            state.pendingShotContext = null;
            return;
        }

        state.phase = "attack";
        state.pendingAttack = null;

        const defenderPick = state.pendingDefenseContext;
        state.pendingDefenseContext = null;

        if (attack === "pass") resolvePass(attackTeam, defenseTeam, defense, defenderPick);
        else if (attack === "dribble") resolveDribble(attackTeam, defenseTeam, defense, defenderPick);
        else if (attack === "shot") resolveShot(attackTeam, defenseTeam, defense, false, defenderPick);
        else if (attack === "special") resolveShot(attackTeam, defenseTeam, defense, true, defenderPick);
    }

    // ==========================
    //   Player card click
    // ==========================

    // Affiche la card du joueur cliqué (home/away) sans changer la possession.
    function showPlayerCard(playerId) {
        if (!playerId) return;
        const team = playerId.startsWith("I") ? "internal" : "external";
        const number = parseInt(playerId.slice(1), 10);
        const prefix = (team === "internal") ? "home" : "away";
        updateSideCard(prefix, team, number);
    }

    // Bind les clics sur les joueurs du terrain pour ouvrir leur card.
    function bindPlayerClickHandlers() {
        $$(".player").forEach((el) => {
            el.addEventListener("click", () => showPlayerCard(el.dataset.player));
        });
    }

    // ==========================
    //   INIT
    // ==========================

    // Initialise tout (positions, roster, stamina, handlers, UI) puis lance le match.
    function init() {
        initBasePositions();
        applyRosterToDOM();
        initStamina();

        bindPlayerClickHandlers();
        bindDuelTooltipEvents();

        state.turns = 0;
        state.currentTeam = "internal";
        state.score = { internal: 0, external: 0 };
        state.phase = "attack";
        state.pendingAttack = null;
        state.isAnimating = false;
        state.lastDribblerId = null;
        state.isKickoff = true;
        state.keeperRestartMustPass = false;
        state.isGameOver = false;
        state.pendingShotContext = null;
        state.pendingDefenseContext = null;
        state.pendingClearanceBonus = 0;

        applyKickoffPositions();
        moveBallToPlayer("internal", 8);

        updateSideCard("home", "internal", 8);
        updateSideCard("away", "external", 8);

        setMessage(TEXTS.ui.gameStartMain, TEXTS.ui.gameStartSub);
        showAttackBarForCurrentTeam();
        refreshUI();

        if (ui.modeOnePlayerBtn) {
            // Synchronise label + classe du bouton de mode (1 joueur / 2 joueurs).
            const syncModeLabel = () => {
                ui.modeOnePlayerBtn.classList.toggle("active", onePlayerMode);
                ui.modeOnePlayerBtn.textContent = onePlayerMode ? "Mode 1 joueur" : "Mode 2 joueurs";
            };

            syncModeLabel();
            ui.modeOnePlayerBtn.addEventListener("click", () => {
                onePlayerMode = !onePlayerMode;
                syncModeLabel();
                setAIOverlay(false);
            });
        }

        if (ui.controlledTeamSelect) {
            ui.controlledTeamSelect.value = controlledTeam;
            ui.controlledTeamSelect.addEventListener("change", () => {
                controlledTeam = ui.controlledTeamSelect.value === "external" ? "external" : "internal";
            });
        }

        if (ui.teamNameInternalEl) ui.teamNameInternalEl.textContent = TEAMS.internal.label;
        if (ui.teamNameExternalEl) ui.teamNameExternalEl.textContent = TEAMS.external.label;
    }

    const homeCard = rootEl.querySelector("#home-card");
    const awayCard = rootEl.querySelector("#away-card");

    if (homeCard) {
        homeCard.classList.remove("team-internal", "team-external");
        homeCard.classList.add("team-internal");
    }
    if (awayCard) {
        awayCard.classList.remove("team-internal", "team-external");
        awayCard.classList.add("team-external");
    }
    init();
}
