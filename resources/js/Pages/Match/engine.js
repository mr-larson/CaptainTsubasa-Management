
// ==========================
//   CONSTANTES
// ==========================
const ANIM_MS     = 700;
const AI_THINK_MS = 400;
const DIE_SIDES   = 20;
const GK_HOLD_MS = 1200;

const GAME_RULES = { MAX_TURNS: 40 };

const DUEL_RULES = {
    GOOD_COUNTER_BONUS: 2,
    GENERIC_ATTACK_BONUS: 2,
    SHOT_DISTANCE_PENALTY_PER_LINE: 1,
};

const ENDURANCE_DEFAULT = 100;

const STAMINA_FACTORS = {
    HIGH: 1.00,
    MID:  0.95,
    LOW:  0.88,
    CRIT: 0.80,
    EXHAUSTED: 0.75,
};

const STAMINA_COST_GLOBAL_SCALE = 0.85;

const FIELD_RULES = {
    GOAL_X:     { internal: 97, external: 3 },
    GOAL_Y:     50,
    GK_FRONT_X: { internal: 88, external: 12 },
};

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
    GK: { gk: 0.05 },                 // +5% sur duels GK
    DF: { defend: 0.04, block: 0.03 },// +4% defense, +3% block
    MF: { pass: 0.03, dribble: 0.02 },// +3% pass, +2% dribble
    FW: { shot: 0.04, attack: 0.03 }, // +4% shot, +3% special(attack)
};

// ==========================
//   TEXTES
// ==========================
const TEXTS = {
    teams: { internal: "Domicile", external: "Exterieur" },
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
        specialCooldownMain: "Special indisponible",
        specialCooldownSub:  "Attends le cooldown avant de relancer un Special.",
        keeperRestartMain: "Relance du gardien",
        keeperRestartSub:  "Passe obligatoire après une relance.",
    },
    logs: {
        kickoffTitle:  "Coup d'envoi",
        kickoffDetail: "Internal engage (pass obligatoire)",
        passSuccessTitle: "Passe réussie",
        passFailTitle:    "Passe interceptée",
        dribbleRefusedTitle: "Dribble refusé (face au gardien)",
        dribbleRefusedDetail: "Action non autorisée",
        shotGoalTitle: "Tir – BUT",
        shotSavedTitle: "Tir – arrêté",
        longShotGoalTitle: "Tir de loin – BUT",
        longShotSavedTitle: "Tir de loin – arrêté",
        matchEndTitle: "Fin du match",
    },
    cards: {
        attack: {
            shot:    { icon: "⚽️", title: "Shot", sub: "Puissant tir" },
            pass:    { icon: "➡️", title: "Pass", sub: "Passe avant" },
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
const ZONE_BOUNDS_INTERNAL = [5, 25, 45, 65, 85]; // 0..4 bornes, zones 0..3
const laneY = [25, 50, 75];

// ==========================
//   STATS "MATCH"
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

let TEAMS = null;

// ==========================
//   EXPORT
// ==========================
/**
 * @param {HTMLElement} rootEl
 * @param {object} config
 * @returns {void | (() => void)}
 */
export function initMatchEngine(rootEl, config = {}) {
    if (!rootEl) return;

    const matchConfig = config || {};

    TEAMS = {
        internal: { id: "internal", label: matchConfig.teams?.internal?.name ?? TEXTS.teams.internal },
        external: { id: "external", label: matchConfig.teams?.external?.name ?? TEXTS.teams.external },
    };

    // Mode
    const controlMode = matchConfig.controlMode ?? "both";
    let onePlayerMode = (controlMode === "single");
    let controlledTeam = matchConfig.controlledSide ?? "internal";

    // DOM helpers
    const $  = (sel) => rootEl.querySelector(sel);
    const $$ = (sel) => rootEl.querySelectorAll(sel);

    // ==========================
    //   ROSTERS
    // ==========================
    const rosters = { internal: new Map(), external: new Map() };

    function normalizePlayers(list = []) {
        return Array.isArray(list) ? list : [];
    }

    function seedRosterFromConfig(teamKey) {
        const players = normalizePlayers(matchConfig.teams?.[teamKey]?.players);
        const take = players.slice(0, 11);

        for (let slot = 1; slot <= 11; slot++) {
            const p = take[slot - 1] ?? null;

            rosters[teamKey].set(
                slot,
                p
                    ? {
                        id: p.id,
                        number: p.number ?? slot,
                        firstname: p.firstname ?? "",
                        lastname: p.lastname ?? "",
                        position: p.position ?? "",
                        photo:
                            p.photo_url ??
                            p.photo ??
                            p.image_url ??
                            p.avatar_url ??
                            p.photo_path ??
                            p.portrait_url ??
                            p.portrait ??
                            p.picture_url ??
                            p.picture ??
                            null,
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
                    }
                    : {
                        id: null,
                        number: slot,
                        firstname: "Joueur",
                        lastname: `#${slot}`,
                        position: "",
                        photo: null,
                        stats: null,
                    },
            );
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

    function getRoleFromPositionString(pos) {
        const p = String(pos || "").toLowerCase();
        if (p.includes("goalkeeper") || p === "gk") return "GK";
        if (p.includes("def") || p === "df") return "DF";
        if (p.includes("mid") || p === "mf") return "MF";
        if (p.includes("for") || p.includes("att") || p === "fw") return "FW";
        return null;
    }

    function getPlayerRole(team, slotNumber) {
        const info = getPlayerInfo(team, slotNumber);
        return getRoleFromPositionString(info?.position);
    }

    function positionBonusMultiplier(role, tag) {
        if (!role) return 1.0;
        const r = POSITION_BONUS[role];
        if (!r) return 1.0;
        const b = Number(r[tag] ?? 0);
        return 1.0 + (Number.isFinite(b) ? b : 0);
    }

    const STAT_COEF = 0.6;

    function attackBaseFor(actionKey, team, slotNumber) {
        const base = STATS.attack[actionKey]?.power ?? 10;
        const role = getPlayerRole(team, slotNumber);

        let raw = base;
        if (actionKey === "pass")    raw = base + getStat(team, slotNumber, "pass") * STAT_COEF;
        if (actionKey === "dribble") raw = base + getStat(team, slotNumber, "dribble") * STAT_COEF;
        if (actionKey === "shot")    raw = base + getStat(team, slotNumber, "shot") * STAT_COEF;
        if (actionKey === "special") raw = base + getStat(team, slotNumber, "attack") * STAT_COEF;

        if (actionKey === "pass")    raw *= positionBonusMultiplier(role, "pass");
        if (actionKey === "dribble") raw *= positionBonusMultiplier(role, "dribble");
        if (actionKey === "shot")    raw *= positionBonusMultiplier(role, "shot");
        if (actionKey === "special") raw *= positionBonusMultiplier(role, "attack");

        return raw;
    }

    function defenseBaseFor(defenseAction, defenseTeam, defenseSlotNumber, isKeeper = false) {
        const baseField = STATS.defenseField[defenseAction]?.power;
        const baseGk    = STATS.defenseGK[defenseAction]?.power;
        const base      = (baseField ?? baseGk ?? 10);

        const role = getPlayerRole(defenseTeam, defenseSlotNumber);
        let raw = base;

        if (isKeeper) {
            if (defenseAction === "hands")      raw = base + getStat(defenseTeam, defenseSlotNumber, "hand_save") * STAT_COEF;
            else if (defenseAction === "punch") raw = base + getStat(defenseTeam, defenseSlotNumber, "punch_save") * STAT_COEF;
            else if (defenseAction === "gk-special") raw = base + getStat(defenseTeam, defenseSlotNumber, "defense") * STAT_COEF;

            raw *= positionBonusMultiplier(role, "gk");
            return raw;
        }

        if (defenseAction === "block")          raw = base + getStat(defenseTeam, defenseSlotNumber, "block") * STAT_COEF;
        else if (defenseAction === "intercept") raw = base + getStat(defenseTeam, defenseSlotNumber, "intercept") * STAT_COEF;
        else if (defenseAction === "tackle")    raw = base + getStat(defenseTeam, defenseSlotNumber, "tackle") * STAT_COEF;
        else if (defenseAction === "field-special") raw = base + getStat(defenseTeam, defenseSlotNumber, "defense") * STAT_COEF;

        if (defenseAction === "block")          raw *= positionBonusMultiplier(role, "block");
        if (defenseAction === "field-special")  raw *= positionBonusMultiplier(role, "defend");
        if (defenseAction === "intercept")      raw *= positionBonusMultiplier(role, "defend");
        if (defenseAction === "tackle")         raw *= positionBonusMultiplier(role, "defend");

        return raw;
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
    //   ÉTAT MATCH
    // ==========================
    let ball = { team: "internal", zoneIndex: 1, laneIndex: 1, number: 8, frontOfKeeper: false };

    let currentTeam = "internal";
    let score = { internal: 0, external: 0 };
    let turns = 0;

    let phase = "attack"; // attack | defense
    let pendingAttack = null; // pass | dribble | shot | special
    let isAnimating = false;
    let isKickoff = true;
    let keeperRestartMustPass = false; // ✅ passe obligatoire après relance GK
    let isGameOver = false;

    let pendingShotContext = null;
    let pendingClearanceBonus = 0;
    let pendingDefenseContext = null;


    const basePositions = {};
    let lastDribblerId = null;

    // stamina par joueur
    const stamina = {};
    const staminaMax = {};

    // cooldown special par joueur (turn index)
    const specialCooldown = {};
    const SPECIAL_COOLDOWN_TURNS = 2;

    // ==========================
    //   Anti-biais touches (évite #8 omniprésent)
    // ==========================
    const touchHeat = {}; // playerId -> "a touch count"
    function heatOf(playerId) { return touchHeat[playerId] ?? 0; }
    function markTouch(playerId) {
        if (!playerId) return;
        touchHeat[playerId] = (touchHeat[playerId] ?? 0) + 1;
    }
    function decayHeat() {
        for (const k in touchHeat) touchHeat[k] = Math.max(0, touchHeat[k] - 0.35);
    }

    // ==========================
    //   DOM refs
    // ==========================
    const ballEl          = $("#ball");
    const scoreInternalEl = $("#score-internal");
    const scoreExternalEl = $("#score-external");
    const turnsDisplayEl  = $("#turns-display");
    const turnIndicatorEl = $("#turn-indicator");
    const msgMainEl       = $("#message-main");
    const msgSubEl        = $("#message-sub");
    const actionBarEl     = $("#action-bar");

    const teamNameInternalEl = $("#team-name-internal");
    const teamNameExternalEl = $("#team-name-external");
    const homeBallIconEl = $("#home-ball-icon");
    const awayBallIconEl = $("#away-ball-icon");
    const matchEndActionsEl = $("#match-end-actions");
    const finishMatchBtn    = $("#btn-finish-match");

    const currentActionTitleEl  = $("#current-action-title");
    const currentActionDetailEl = $("#current-action-detail");
    const duelDiceEl            = $("#duel-dice-display");
    const historyListEl         = $("#history-list");

    const modeOnePlayerBtn     = $("#mode-one-player");
    const controlledTeamSelect = $("#controlled-team-select");
    const aiOverlayEl          = $("#ai-turn-overlay");

    // ==========================
    //   Helpers généraux
    // ==========================
    const otherTeam = (t) => (t === "internal" ? "external" : "internal");

    function isAITeam(team) {
        return onePlayerMode && team !== controlledTeam;
    }

    function getPlayerId(team, number) {
        return (team === "internal" ? "I" : "E") + String(number);
    }

    function isGoalkeeperId(pid) {
        return pid === "I1" || pid === "E1";
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

    // ==========================
    //   LOGS
    // ==========================
    const actionHistory = [];
    const MAX_HISTORY = 15;

    function pushLogEntry(main, detailsLines = [], diceTag = null) {
        if (currentActionTitleEl) currentActionTitleEl.textContent = main || "–";
        if (currentActionDetailEl) currentActionDetailEl.textContent = detailsLines.length ? detailsLines.join(" | ") : "";

        const turnLabel = `T${String(turns + 1).padStart(2, "0")}`;
        const shortLine = diceTag ? `${turnLabel} — ${main} (${diceTag})` : `${turnLabel} — ${main}`;

        actionHistory.push(shortLine);
        if (actionHistory.length > MAX_HISTORY) actionHistory.shift();

        if (historyListEl) {
            historyListEl.innerHTML = actionHistory.map((line) => `<li>${line}</li>`).join("");
        }
    }

    function showDuelDice(attackScore, defenseScore, aRoll = null, dRoll = null, breakdown = null) {
        if (!duelDiceEl) return;

        const a = attackScore.toFixed(1);
        const d = defenseScore.toFixed(1);

        let extra = "";
        if (aRoll && dRoll) {
            const aTag = aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll));
            const dTag = dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll));
            extra = `  (d20: ${aTag}-${dTag})`;
        }

        duelDiceEl.textContent = `🎲 ${a} - ${d}${extra}`;
        duelDiceEl.classList.add("visible");
        duelDiceEl.classList.remove("pop");
        void duelDiceEl.offsetWidth;
        duelDiceEl.classList.add("pop");

        // ==========================
        //   TOOLTIP content update
        // ==========================
        if (breakdown) {
            lastDuelBreakdown = breakdown;
            setDuelTooltipContent(formatDuelBreakdownHTML(breakdown));
        }
    }

    // ==========================
    //   d20 + crits
    // ==========================
    function rollDie() {
        return 1 + Math.floor(Math.random() * DIE_SIDES);
    }

    function rollD20WithCrit() {
        const roll = rollDie();
        return {
            roll,
            bonus: roll / 2,
            critSuccess: roll === 20,
            critFail: roll === 1,
        };
    }

    function resolveCritOutcome(attackRoll, defenseRoll) {
        if (attackRoll.critSuccess && !defenseRoll.critSuccess) return "attack";
        if (defenseRoll.critSuccess && !attackRoll.critSuccess) return "defense";

        if (attackRoll.critFail && !defenseRoll.critFail) return "defense";
        if (defenseRoll.critFail && !attackRoll.critFail) return "attack";

        return null;
    }

    // ==========================
    //   TOOLTIP duel (calcul complet)
    // ==========================
    let duelTooltipEl = null;          // tooltip DOM
    let lastDuelBreakdown = null;      // dernier calcul stocké (objet)

    function ensureDuelTooltip() {
        if (duelTooltipEl) return duelTooltipEl;

        duelTooltipEl = document.createElement("div");
        duelTooltipEl.id = "duel-dice-tooltip";
        duelTooltipEl.className = "dice-tooltip hidden";
        duelTooltipEl.setAttribute("role", "tooltip");

        document.body.appendChild(duelTooltipEl);

        return duelTooltipEl;
    }

    function setDuelTooltipContent(html) {
        ensureDuelTooltip();
        duelTooltipEl.innerHTML = html;
    }

    function positionTooltipNearDice() {
        if (!duelTooltipEl || !duelDiceEl) return;

        const margin = 12; // marge écran
        const gap = 10;    // distance entre dice et tooltip

        const diceRect = duelDiceEl.getBoundingClientRect();

        // ✅ tooltip en fixed => indépendant du layout / overflow
        duelTooltipEl.style.position = "fixed";
        duelTooltipEl.style.zIndex = "9999";

        // ✅ IMPORTANT: neutralise tout CSS qui décale (transform/right/etc.)
        duelTooltipEl.style.transform = "none";
        duelTooltipEl.style.right = "auto";
        duelTooltipEl.style.bottom = "auto";

        // On "dé-cache" pour mesurer
        const wasHidden = duelTooltipEl.classList.contains("hidden");
        if (wasHidden) {
            duelTooltipEl.style.visibility = "hidden";
            duelTooltipEl.classList.remove("hidden");
        }

        // Mesure réelle
        const tipRect = duelTooltipEl.getBoundingClientRect();

        // Position par défaut : sous le dé (centré)
        let left = diceRect.left + (diceRect.width / 2) - (tipRect.width / 2);
        let top  = diceRect.bottom + gap;

        // Clamp horizontal
        left = Math.max(margin, Math.min(left, window.innerWidth - tipRect.width - margin));

        // Si ça dépasse en bas -> on passe au-dessus
        let placement = "bottom";
        if (top + tipRect.height + margin > window.innerHeight) {
            top = diceRect.top - tipRect.height - gap;
            placement = "top";
        }

        // Clamp vertical
        top = Math.max(margin, Math.min(top, window.innerHeight - tipRect.height - margin));

        // Applique
        duelTooltipEl.style.left = `${Math.round(left)}px`;
        duelTooltipEl.style.top  = `${Math.round(top)}px`;

        // ✅ sert pour orienter la flèche en CSS
        duelTooltipEl.setAttribute("data-placement", placement);

        // Restore hidden state
        if (wasHidden) {
            duelTooltipEl.classList.add("hidden");
            duelTooltipEl.style.visibility = "";
        }
    }

    function showDuelTooltip() {
        if (!duelTooltipEl || !lastDuelBreakdown) return;

        positionTooltipNearDice();
        duelTooltipEl.classList.remove("hidden");
    }

    function hideDuelTooltip() {
        if (!duelTooltipEl) return;
        duelTooltipEl.classList.add("hidden");
    }

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

        const resultLine =
            b.result.critWinner
                ? `Crit: <b>${b.result.critWinner.toUpperCase()}</b>`
                : `Diff: <b>${b.result.diff.toFixed(2)}</b> → <b>${b.result.winner.toUpperCase()}</b>`;

        return `
        <div class="dt-wrap">
            ${section("🎲 Jets", [
            row("Attaque d20", `${b.rolls.aTag} (bonus +${b.rolls.aBonus.toFixed(1)})`),
            row("Défense d20", `${b.rolls.dTag} (bonus +${b.rolls.dBonus.toFixed(1)})`),
        ].join(""))}

            ${section("⚔️ Attaque", [
            row("Base", b.attack.base.toFixed(2)),
            row("Stamina factor", `× ${b.attack.staminaFactor.toFixed(2)}`),
            ...(b.attack.additions || []).map(x => row(x.label, x.value)).join(""),
            row("Total attaque", `<b>${b.attack.total.toFixed(2)}</b>`),
        ].join(""))}

            ${section("🛡️ Défense", [
            row("Base", b.defense.base.toFixed(2)),
            row("Stamina factor", `× ${b.defense.staminaFactor.toFixed(2)}`),
            ...(b.defense.additions || []).map(x => row(x.label, x.value)).join(""),
            row("Total défense", `<b>${b.defense.total.toFixed(2)}</b>`),
        ].join(""))}

            ${section("✅ Résultat", [
            row("Règle bonus", b.result.bonusRuleLabel || "—"),
            row("Issue", resultLine),
        ].join(""))}
        </div>
    `;
    }

    function bindDuelTooltipEvents() {
        if (!duelDiceEl) return;

        ensureDuelTooltip();

        duelDiceEl.addEventListener("mouseenter", showDuelTooltip);
        duelDiceEl.addEventListener("mouseleave", hideDuelTooltip);

        // accessibilité clavier
        duelDiceEl.setAttribute("tabindex", "0");
        duelDiceEl.addEventListener("focus", showDuelTooltip);
        duelDiceEl.addEventListener("blur", hideDuelTooltip);

        // si scroll/resize, on recale
        window.addEventListener("scroll", () => {
            if (!duelTooltipEl || duelTooltipEl.classList.contains("hidden")) return;
            positionTooltipNearDice();
        }, { passive: true });

        window.addEventListener("resize", () => {
            if (!duelTooltipEl || duelTooltipEl.classList.contains("hidden")) return;
            positionTooltipNearDice();
        });
    }


    // ==========================
    //   Endurance (ratio + tiers)
    // ==========================
    function getStamina(playerId) {
        if (!(playerId in stamina)) stamina[playerId] = ENDURANCE_DEFAULT;
        return stamina[playerId];
    }

    function getStaminaMax(playerId) {
        if (!(playerId in staminaMax)) staminaMax[playerId] = ENDURANCE_DEFAULT;
        return staminaMax[playerId];
    }

    function getStaminaRatio(playerId) {
        const v = getStamina(playerId);
        const m = getStaminaMax(playerId);
        return m > 0 ? (v / m) : 0;
    }

    function getStaminaTier(playerId) {
        const r = getStaminaRatio(playerId);
        if (r >= 0.75) return "high";
        if (r >= 0.50) return "mid";
        if (r >= 0.25) return "low";
        return "crit";
    }

    function staminaFactor(playerId) {
        const r = getStaminaRatio(playerId);
        if (r >= 0.75) return STAMINA_FACTORS.HIGH;
        if (r >= 0.50) return STAMINA_FACTORS.MID;
        if (r >= 0.25) return STAMINA_FACTORS.LOW;
        if (r > 0)     return STAMINA_FACTORS.CRIT;
        return STAMINA_FACTORS.EXHAUSTED;
    }

    function staminaCostMultiplierFor(category) {
        if (category === "defenseGK") return 0.60; // GK moins drainé
        return 1.0;
    }

    function applyStaminaCost(playerId, category, actionKey) {
        if (!playerId) return;

        const cfgCategory = STATS[category];
        const cfg = cfgCategory && cfgCategory[actionKey];
        const baseCost = cfg ? cfg.cost : 0;

        const scaled =
            baseCost *
            staminaCostMultiplierFor(category) *
            STAMINA_COST_GLOBAL_SCALE;

        const cost = Math.max(0, Math.round(scaled));

        const curr = getStamina(playerId);
        stamina[playerId] = Math.max(0, curr - cost);

        updateStaminaUI(playerId);
    }

    function updateStaminaUI(playerId) {
        const ratio = getStaminaRatio(playerId);

        const el = rootEl.querySelector(`.player[data-player="${playerId}"]`);
        if (el) {
            el.classList.add("show-endurance");

            const shell = el.querySelector(".endurance-shell");
            if (shell) {
                const bar = shell.querySelector(".endurance-bar");
                if (bar) bar.style.width = `${Math.max(10, ratio * 100)}%`;
            }

            el.classList.remove("e-high","e-mid","e-low","e-crit");

            const tier = getStaminaTier(playerId);
            if (tier === "high") el.classList.add("e-high");
            else if (tier === "mid") el.classList.add("e-mid");
            else if (tier === "low") el.classList.add("e-low");
            else el.classList.add("e-crit");
        }

        if (getPlayerId(ball.team, ball.number) === playerId) updateTeamCard();
    }

    function initStamina() {
        $$(".player").forEach((el) => {
            const id = el.dataset.player;
            const team = id.startsWith("I") ? "internal" : "external";
            const slot = parseInt(id.slice(1), 10);

            const max = clampStat(getStat(team, slot, "stamina")) || ENDURANCE_DEFAULT;
            staminaMax[id] = max;
            stamina[id] = max;

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
    //   Special cooldown
    // ==========================
    function canUseSpecial(playerId) {
        if (!playerId) return false;
        const availableAt = specialCooldown[playerId] ?? 0;
        return turns >= availableAt;
    }

    function markSpecialUsed(playerId) {
        if (!playerId) return;
        specialCooldown[playerId] = turns + SPECIAL_COOLDOWN_TURNS;
    }

    // ==========================
    //   Terrain / positions
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
        return rootEl.querySelector(`[data-player="${getPlayerId(team, number)}"]`);
    }

    // ✅ Choix pondéré dans une cellule : évite que le même joueur soit toujours choisi
    function pickWeightedPlayerInCell(team, zoneIndex, laneIndex, opts = {}) {
        const { excludeIds = [], topK = 4 } = opts;

        const selector = team === "internal" ? ".player.internal" : ".player.external";
        const center = getCellCenter(team, zoneIndex, laneIndex);

        const candidates = [];
        rootEl.querySelectorAll(selector).forEach((el) => {
            if (el.classList.contains("goalkeeper")) return; // ✅ jamais GK
            const id = el.dataset.player;
            if (!id || excludeIds.includes(id)) return;

            const x = parseFloat(el.style.left);
            const y = parseFloat(el.style.top);
            if (Number.isNaN(x) || Number.isNaN(y)) return;

            const dx = x - center.x;
            const dy = y - center.y;
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
        let r = Math.random() * sum;

        for (let i = 0; i < pool.length; i++) {
            r -= weights[i];
            if (r <= 0) return pool[i].id;
        }

        return pool[pool.length - 1].id;
    }

    function getRandomFieldPlayer(team) {
        const selector = team === "internal" ? ".player.internal" : ".player.external";
        const candidates = Array.from(rootEl.querySelectorAll(selector))
            .filter(el => !el.classList.contains("goalkeeper"));
        if (!candidates.length) return null;
        return candidates[Math.floor(Math.random() * candidates.length)].dataset.player;
    }

    function pickReceiverInCell(team, zoneIndex, laneIndex, fallbackNumber, excludeNumber = null) {
        let receiverId = pickWeightedPlayerInCell(team, zoneIndex, laneIndex, {
            topK: 5,
            excludeIds: excludeNumber ? [getPlayerId(team, excludeNumber)] : [],
        });

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

        ballEl.style.left = x + "%";
        ballEl.style.top  = y + "%";

        const info = getPlayerInfo(team, number);
        ballEl.textContent = info ? String(info.number) : String(number);

        ball.team = team;
        ball.number = number;

        // ✅ anti GK porteur “field”
        if (ball.number === 1 && !ball.frontOfKeeper) {
            // si on arrive ici hors GK-duel, on reroute sur un joueur de champ (sécurité)
            const safe = pickReceiverInCell(team, ball.zoneIndex, ball.laneIndex, 6, 1);
            ball.number = safe;
        }

        markTouch(getPlayerId(team, ball.number));
        ball.frontOfKeeper = false;

        const xInternal = team === "internal" ? x : 100 - x;

        let zoneIndex = 0;
        for (let i = 0; i < ZONE_BOUNDS_INTERNAL.length - 1; i++) {
            const left = ZONE_BOUNDS_INTERNAL[i];
            const right = ZONE_BOUNDS_INTERNAL[i + 1];
            if (xInternal >= left && xInternal <= right) { zoneIndex = i; break; }
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
    //   BALL helpers (sync UI/state)
    // ==========================
    // ✅ visuel uniquement : on ne change PAS ball.team/ball.number ici
    function setBallToKeeperVisual(defenseTeam) {
        const keeperEl = rootEl.querySelector(
            defenseTeam === "internal"
                ? '.player.internal.goalkeeper[data-player="I1"]'
                : '.player.external.goalkeeper[data-player="E1"]'
        );
        if (!keeperEl || !ballEl) return;

        const x = parseFloat(keeperEl.style.left);
        const y = parseFloat(keeperEl.style.top);

        ballEl.style.left = x + "%";
        ballEl.style.top  = y + "%";
        ballEl.textContent = "1";
    }

    // ==========================
    //   Egalité (jamais vers GK)
    // ==========================
    function givePossessionOnTie(defenseTeam, defenderIdMaybe = null) {
        // ✅ si un id est fourni mais GK => on ignore
        if (defenderIdMaybe && !isGoalkeeperId(defenderIdMaybe)) {
            const slot = parseInt(defenderIdMaybe.slice(1), 10);
            moveBallToPlayer(defenseTeam, slot);
            setMessage("Duel équilibré !", `${TEAMS[defenseTeam].label} récupère (égalité).`);
            return { team: defenseTeam, number: slot };
        }

        // fallback : joueur de champ
        const randomId = getRandomFieldPlayer(defenseTeam);
        if (randomId) {
            const slot = parseInt(randomId.slice(1), 10);
            moveBallToPlayer(defenseTeam, slot);
            setMessage("Duel équilibré !", `${TEAMS[defenseTeam].label} récupère (égalité).`);
            return { team: defenseTeam, number: slot };
        }

        return { team: ball.team, number: ball.number };
    }

    // ==========================
    //   UI photos (cards)
    // ==========================
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
            img.style.display = "block";

            img.addEventListener("error", () => {
                img.classList.add("hidden");
                img.removeAttribute("src");
            });

            cardEl.appendChild(img);
        }

        return img;
    }

    function setCardPhoto(cardEl, photoUrl) {
        const img = ensureCardPhotoLayer(cardEl);
        if (!img) return;

        const raw = (photoUrl || "").trim();
        if (!raw) {
            img.src = "";
            img.classList.add("hidden");
            return;
        }

        let url = raw;
        if (url.startsWith("storage/")) url = "/" + url;
        if (!url.startsWith("http://") && !url.startsWith("https://") && !url.startsWith("/")) {
            url = "/" + url;
        }

        img.src = url;
        img.classList.remove("hidden");
    }

    // ==========================
    //   CARDS (home/away)
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
        setText(`#${prefix}-team`, TEAMS?.[team]?.label ?? (team === "internal" ? "Domicile" : "Extérieur"));

        const s = info?.stats ?? {};
        const v = (k) => {
            const n = Number(s?.[k] ?? 0);
            return Number.isFinite(n) ? n : 0;
        };

        setText(`#${prefix}-stat-shot`, String(v("shot")));
        setText(`#${prefix}-stat-pass`, String(v("pass")));
        setText(`#${prefix}-stat-dribble`, String(v("dribble")));
        setText(`#${prefix}-stat-attack`, String(v("attack")));

        setText(`#${prefix}-stat-block`, String(v("block")));
        setText(`#${prefix}-stat-intercept`, String(v("intercept")));
        setText(`#${prefix}-stat-tackle`, String(v("tackle")));
        setText(`#${prefix}-stat-defense`, String(v("defense")));

        setText(`#${prefix}-stat-hand_save`, String(v("hand_save")));
        setText(`#${prefix}-stat-punch_save`, String(v("punch_save")));

        const isGoalkeeper = (info?.position ?? "").toLowerCase().includes("goalkeeper");

        const showRow = (id) => {
            const el = rootEl.querySelector(id);
            if (el?.parentElement) el.parentElement.classList.remove("hidden");
        };
        const hideRow = (id) => {
            const el = rootEl.querySelector(id);
            if (el?.parentElement) el.parentElement.classList.add("hidden");
        };

        const fieldRows = [`#${prefix}-stat-block`, `#${prefix}-stat-intercept`, `#${prefix}-stat-tackle`, `#${prefix}-stat-dribble`];
        const gkRows = [`#${prefix}-stat-hand_save`, `#${prefix}-stat-punch_save`];

        if (isGoalkeeper) {
            fieldRows.forEach(hideRow);
            gkRows.forEach(showRow);
        } else {
            gkRows.forEach(hideRow);
            fieldRows.forEach(showRow);
        }

        const playerId = getPlayerId(team, slotNumber);
        const ratio = getStaminaRatio(playerId);

        const fillEl = rootEl.querySelector(`#${prefix}-energy-fill`);
        if (fillEl) {
            fillEl.style.width = `${Math.max(0, ratio * 100)}%`;
            fillEl.classList.remove("e-high","e-mid","e-low","e-crit");

            const tier = getStaminaTier(playerId);
            if (tier === "high") fillEl.classList.add("e-high");
            else if (tier === "mid") fillEl.classList.add("e-mid");
            else if (tier === "low") fillEl.classList.add("e-low");
            else fillEl.classList.add("e-crit");
        }

        const portraitEl = rootEl.querySelector(`#${prefix}-portrait`);
        if (portraitEl) setCardPhoto(portraitEl, info?.photo ?? null);

        const cardEl = rootEl.querySelector(`#${prefix}-card`);
        if (cardEl) {
            cardEl.classList.remove("team-internal", "team-external");
            cardEl.classList.add(team === "internal" ? "team-internal" : "team-external");
        }
    }

    function updateScoreUI() {
        if (scoreInternalEl) scoreInternalEl.textContent = score.internal;
        if (scoreExternalEl) scoreExternalEl.textContent = score.external;

        const t = String(turns).padStart(2, "0");
        if (turnsDisplayEl) turnsDisplayEl.textContent = t;
        if (turnIndicatorEl) turnIndicatorEl.textContent = t;
    }

    function updateTeamCard() {
        if (homeBallIconEl) homeBallIconEl.classList.toggle("hidden", ball.team !== "internal");
        if (awayBallIconEl) awayBallIconEl.classList.toggle("hidden", ball.team !== "external");

        const prefix = (ball.team === "internal") ? "home" : "away";
        updateSideCard(prefix, ball.team, ball.number);
        updateCardsPower();
    }

    // ==========================
    //   Bon choix défense + bonus
    // ==========================
    function isGoodDefenseChoice(attackAction, defenseAction) {
        const a = String(attackAction || "").toLowerCase();
        const d = String(defenseAction || "").toLowerCase();

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

    function applyDuelBonuses({ attackAction, defenseAction, attackScore, defenseScore, context = {} }) {
        const isKeeperDuel = !!context.isKeeperDuel;

        const isGood = isKeeperDuel
            ? (defenseAction === "hands" || defenseAction === "gk-special")
            : isGoodDefenseChoice(attackAction, defenseAction);

        if (isGood) defenseScore += (DUEL_RULES.GOOD_COUNTER_BONUS ?? 0);
        else        attackScore  += (DUEL_RULES.GENERIC_ATTACK_BONUS ?? 0);

        return { attackScore, defenseScore };
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

    // ==========================
    //   UI Action bar
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
        </button>`;
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
        </button>`;
    }

    function buildAttackActionsHTML() {
        const cfg = TEXTS.cards.attack;
        return `<div id="attack-strip">
          ${buildSkillCard("shot", cfg.shot)}
          ${buildSkillCard("pass", cfg.pass)}
          ${buildSkillCard("dribble", cfg.dribble)}
          ${buildSkillCard("special", cfg.special)}
        </div>`;
    }

    function buildDefenseFieldHTML() {
        const cfg = TEXTS.cards.defenseField;
        return `<div id="defense-strip">
          ${buildDefCard("block", cfg.block)}
          ${buildDefCard("intercept", cfg.intercept)}
          ${buildDefCard("tackle", cfg.tackle)}
          ${buildDefCard("field-special", cfg["field-special"])}
        </div>`;
    }

    function buildDefenseGKHTML() {
        const cfg = TEXTS.cards.defenseGK;
        return `<div id="defense-strip">
          ${buildDefCard("hands", cfg.hands)}
          ${buildDefCard("punch", cfg.punch)}
          ${buildDefCard("gk-special", cfg["gk-special"])}
        </div>`;
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
                const cfg = STATS.defenseField[def] || STATS.defenseGK[def];
                if (!cfg) return;
                const costEl = btn.querySelector(".def-cost span");
                if (costEl) costEl.textContent = cfg.cost;
            });
        }
    }

    function updateCardsPower() {
        if (!actionBarEl) return;

        // ==========================
        //   ATTAQUE — joueur porteur
        // ==========================
        const info = getPlayerInfo(ball.team, ball.number);
        const s = info?.stats ?? null;

        actionBarEl.querySelectorAll(".skill-card").forEach(btn => {
            const action = btn.dataset.action;
            let value = 0;

            if (s) {
                if (action === "pass")    value = Number(s.pass ?? 0);
                if (action === "dribble") value = Number(s.dribble ?? 0);
                if (action === "shot")    value = Number(s.shot ?? 0);
                if (action === "special") value = Number(s.attack ?? 0);
            }

            const powerEl = btn.querySelector(".skill-power");
            if (powerEl) powerEl.textContent = String(value);
        });

        // ==========================
        //   DÉFENSE — détecter le mode
        // ==========================
        const modeClass = Array.from(actionBarEl.classList)
            .find(c => c.startsWith("mode-defense-"));

        if (!modeClass) return;

        const defenseTeam = modeClass.includes("mode-defense-external")
            ? "external"
            : "internal";

        const isGKBar = !!actionBarEl.querySelector(
            '.def-card[data-defense="hands"]'
        );

        // ==========================
        //   DÉFENSE GK (gardien)
        // ==========================
        if (isGKBar) {
            const gkInfo = getPlayerInfo(defenseTeam, 1);
            const gk = gkInfo?.stats ?? {};

            actionBarEl.querySelectorAll(".def-card").forEach(btn => {
                const def = btn.dataset.defense;
                let value = 0;

                if (def === "hands")      value = Number(gk.hand_save ?? 0);
                if (def === "punch")      value = Number(gk.punch_save ?? 0);
                if (def === "gk-special") value = Number(gk.defense ?? 0);

                const powerEl = btn.querySelector(".def-power");
                if (powerEl) powerEl.textContent = String(value);
            });

            return;
        }

        // ==========================
        //   DÉFENSE DE CHAMP (FIGÉE)
        // ==========================
        // ✅ ON N'EN RECHOISIT JAMAIS ICI
        const ctx = pendingDefenseContext;

        const defenderSlot = ctx?.defenderSlot ?? null;

        // fallback ultra-sécurisé (ne devrait plus arriver)
        const realSlot = defenderSlot ?? 6;

        const dInfo = getPlayerInfo(defenseTeam, realSlot);
        const d = dInfo?.stats ?? {};

        actionBarEl.querySelectorAll(".def-card").forEach(btn => {
            const def = btn.dataset.defense;
            let value = 0;

            if (def === "block")          value = Number(d.block ?? 0);
            if (def === "intercept")      value = Number(d.intercept ?? 0);
            if (def === "tackle")         value = Number(d.tackle ?? 0);
            if (def === "field-special")  value = Number(d.defense ?? 0);

            const powerEl = btn.querySelector(".def-power");
            if (powerEl) powerEl.textContent = String(value);
        });
    }

    function bindActionButtons() {
        if (!actionBarEl) return;

        actionBarEl.querySelectorAll(".skill-card").forEach((btn) => {
            btn.addEventListener("click", () => handleAttackClick(btn.dataset.action));
        });

        actionBarEl.querySelectorAll(".def-card").forEach((btn) => {
            btn.addEventListener("click", () => handleDefenseClick(btn.dataset.defense));
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

            // ✅ kickoff : pass only
            if (isKickoff && html.includes("attack-strip")) {
                actionBarEl.querySelectorAll(".skill-card").forEach((btn) => {
                    if (btn.dataset.action !== "pass") btn.style.display = "none";
                });
            }

            // ✅ keeper restart : pass only (un seul tour)
            if (keeperRestartMustPass && html.includes("attack-strip")) {
                actionBarEl.querySelectorAll(".skill-card").forEach((btn) => {
                    if (btn.dataset.action !== "pass") btn.style.display = "none";
                });
            }

            actionBarEl.classList.remove("fade-out");
            actionBarEl.classList.add("fade-in");

            bindActionButtons();
        }, 200);
    }

    // ==========================
    //   Anim / tour
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

    function refreshUI() {
        updateScoreUI();
        updateTeamCard();
    }
    function advanceTurn(newTeam) {
        if (isGameOver) return;
        decayHeat();
        currentTeam = newTeam;
        turns++;

        if (turns >= GAME_RULES.MAX_TURNS) {
            isGameOver = true;

            setMessage(TEXTS.ui.matchEndMain, `${TEXTS.ui.matchEndPrefix}${score.internal} - ${score.external}`);
            pushLogEntry(TEXTS.logs.matchEndTitle, [`Score final ${score.internal} - ${score.external}`]);

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

                    if (!internalTeamId || !externalTeamId) return;

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
        pendingShotContext = null;
    }

    // ==========================
    //   Positions de base
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
            const y = base.y;

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
        if (keeperRestartMustPass) return "pass";

        if (ball.frontOfKeeper) {
            const r = Math.random();
            const specialProb = AI_RULES.ATTACK.FRONT_GK_SPECIAL_PROB ?? 0.15;
            return (r < specialProb) ? "special" : "shot";
        }

        const z = ball.zoneIndex;
        const aiCarrierId = getPlayerId(currentTeam, ball.number);
        const stRatio = getStaminaRatio(aiCarrierId);

        if (stRatio < 0.20) return "pass";

        if (z <= 1) return (Math.random() < (AI_RULES.ATTACK.EARLY_PASS_PROB ?? 0.7)) ? "pass" : "dribble";

        if (z === 2) {
            const r = Math.random();
            if (stRatio >= 0.30) {
                if (r < 0.70) return "dribble";
                if (r < 0.95) return "pass";
                return "shot";
            }
            return (r < 0.80) ? "pass" : "dribble";
        }

        if (z === 3) {
            const r = Math.random();
            if (stRatio >= 0.35) {
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

            const threat = Math.max(getStat(attackerTeam, shooterSlot, "shot"), getStat(attackerTeam, shooterSlot, "attack"));

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
    //   Kickoff (passe auto)
    // ==========================
    function resolveKickoffPass(attackTeam) {
        if (!isKickoff) return;
        isKickoff = false;

        const dmNumbers = [5, 6];
        const number = dmNumbers[Math.floor(Math.random() * dmNumbers.length)];

        const attackerId = getPlayerId(attackTeam, ball.number);
        applyStaminaCost(attackerId, "attack", "pass");

        setMessage("Remise en jeu réussie !", `${TEAMS[attackTeam].label} joue court vers le n°${number}.`);
        pushLogEntry("Coup d'envoi – passe courte", [`Vers n°${number}`, "Auto (pas de duel)"]);

        animateAndThen(() => {
            restoreBasePositions();
            moveBallToPlayer(attackTeam, number);
            advanceTurn(attackTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   Helpers : duel field (refacto)
    // ==========================
    function pickFieldDefender(defenseTeam, originZone, originLane) {
        const defZone = getFacingZoneIndex(originZone);
        const defLane = originLane;

        const defenderId =
            pickWeightedPlayerInCell(defenseTeam, defZone, defLane, { topK: 4 }) ||
            getRandomFieldPlayer(defenseTeam);

        const defenderSlot = defenderId ? parseInt(defenderId.slice(1), 10) : 6;
        return { defenderId, defenderSlot, defZone, defLane };
    }

    function runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick = null }) {
        const attackerId = getPlayerId(attackTeam, ball.number);

        // ✅ Défenseur figé si fourni, sinon on pick
        let defenderId = defenderPick?.defenderId ?? null;
        let defenderSlot = defenderPick?.defenderSlot ?? null;

        if (!defenderId || !defenderSlot) {
            const picked = pickFieldDefender(defenseTeam, ball.zoneIndex, ball.laneIndex);
            defenderId = picked.defenderId;
            defenderSlot = picked.defenderSlot;
        }

        // 1) Base + stamina
        const attackBaseRaw = attackBaseFor(attackType, attackTeam, ball.number);
        const attackStamF   = staminaFactor(attackerId);

        const defBaseRaw = defenseBaseFor(defenseAction, defenseTeam, defenderSlot, false);
        const defStamF   = defenderId ? staminaFactor(defenderId) : 1.0;

        let attackScore  = attackBaseRaw * attackStamF;
        let defenseScore = defBaseRaw   * defStamF;

        // 2) Clearance bonus (snapshot AVANT reset)
        const clearanceBonus = (pendingClearanceBonus > 0) ? pendingClearanceBonus : 0;
        if (pendingClearanceBonus > 0) {
            attackScore += pendingClearanceBonus;
            pendingClearanceBonus = 0;
        }

        // 3) d20
        const aRoll = rollD20WithCrit();
        const dRoll = rollD20WithCrit();
        const critWinner = resolveCritOutcome(aRoll, dRoll);

        attackScore  += aRoll.bonus;
        defenseScore += dRoll.bonus;

        // 4) Bonus rule (APPLIED FOR REAL)
        const isGood = isGoodDefenseChoice(attackType, defenseAction);
        const goodBonus    = (DUEL_RULES.GOOD_COUNTER_BONUS ?? 0);
        const genericBonus = (DUEL_RULES.GENERIC_ATTACK_BONUS ?? 0);

        if (isGood) defenseScore += goodBonus;
        else        attackScore  += genericBonus;

        // 5) Tooltip breakdown
        const aTag = aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll));
        const dTag = dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll));

        const bonusRuleLabel = isGood
            ? `Good counter (+${goodBonus} defense)`
            : `Generic attack (+${genericBonus} attack)`;

        const breakdown = {
            rolls: { aTag, dTag, aBonus: aRoll.bonus, dBonus: dRoll.bonus },
            attack: {
                base: attackBaseRaw,
                staminaFactor: attackStamF,
                additions: [
                    ...(clearanceBonus ? [{ label: "Clearance bonus", value: `+ ${clearanceBonus.toFixed(2)}` }] : []),
                    { label: "d20 bonus", value: `+ ${aRoll.bonus.toFixed(2)}` },
                    ...(!isGood ? [{ label: "Generic bonus", value: `+ ${genericBonus.toFixed(2)}` }] : []),
                ],
                total: attackScore,
            },
            defense: {
                base: defBaseRaw,
                staminaFactor: defStamF,
                additions: [
                    { label: "d20 bonus", value: `+ ${dRoll.bonus.toFixed(2)}` },
                    ...(isGood ? [{ label: "Good counter bonus", value: `+ ${goodBonus.toFixed(2)}` }] : []),
                ],
                total: defenseScore,
            },
            result: {
                bonusRuleLabel,
                critWinner,
                diff: attackScore - defenseScore,
                winner: critWinner
                    ? critWinner
                    : ((attackScore - defenseScore) > 0 ? "attack" : (attackScore - defenseScore) < 0 ? "defense" : "tie"),
            },
        };

        showDuelDice(attackScore, defenseScore, aRoll, dRoll, breakdown);

        // 6) Stamina + cooldowns
        applyStaminaCost(attackerId, "attack", attackType);
        if (defenderId) applyStaminaCost(defenderId, "defenseField", defenseAction);

        if (attackType === "special") markSpecialUsed(attackerId);
        if (defenseAction === "field-special" && defenderId) markSpecialUsed(defenderId);

        // 7) Résolution
        let duelResult = null;
        if (critWinner) duelResult = critWinner;
        else {
            const diff = attackScore - defenseScore;
            if (diff === 0) {
                givePossessionOnTie(defenseTeam, defenderId);
                return {
                    isTie: true,
                    duelResult: "tie",
                    defenderId,
                    defenderSlot,
                    diceTag: `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`
                };
            }
            duelResult = diff > 0 ? "attack" : "defense";
        }

        return {
            isTie: false,
            duelResult,
            defenderId,
            defenderSlot,
            diceTag: `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`
        };
    }

    // ==========================
    //   SHOT animations
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
        setBallToKeeperVisual(defenseTeam);
        animateAndThen(() => {
            setTimeout(() => {
                if (afterAnimation) afterAnimation();
            }, GK_HOLD_MS);
        });
    }

    // ==========================
    //   Anim ballon vers une position (helper)
    // ==========================
    function animateBallToXY(xPercent, yPercent, afterAnimation) {
        if (!ballEl) { if (afterAnimation) afterAnimation(); return; }

        ballEl.style.left = xPercent + "%";
        ballEl.style.top  = yPercent + "%";

        animateAndThen(() => {
            if (afterAnimation) afterAnimation();
        });
    }


    // ==========================
    //   Relance GK (AUTO + PASS ONLY)
    // ==========================
    function performKeeperClearance(defenseTeam, defenseAction, afterClearance = null) {
        // =====================================================
        // 0) Récupère le GK + calcule origin zone/lane depuis le DOM
        // =====================================================
        const keeperEl = rootEl.querySelector(
            defenseTeam === "internal"
                ? '.player.internal.goalkeeper[data-player="I1"]'
                : '.player.external.goalkeeper[data-player="E1"]'
        );

        if (!keeperEl) {
            // Fallback minimal : donne la balle à un joueur de champ et termine
            const receiverNumber = pickReceiverInCell(defenseTeam, ball.zoneIndex, ball.laneIndex, 6, null);
            ball.frontOfKeeper = false;
            resetLastDribbler();
            moveBallToPlayer(defenseTeam, receiverNumber);
            keeperRestartMustPass = true;
            if (afterClearance) afterClearance();
            return;
        }

        const kx = parseFloat(keeperEl.style.left);
        const ky = parseFloat(keeperEl.style.top);

        // ✅ convertit la position GK en repère "interne" (comme moveBallToPlayer)
        const xInternal = (defenseTeam === "internal") ? kx : (100 - kx);

        let originZone = 0;
        for (let i = 0; i < ZONE_BOUNDS_INTERNAL.length - 1; i++) {
            const left = ZONE_BOUNDS_INTERNAL[i];
            const right = ZONE_BOUNDS_INTERNAL[i + 1];
            if (xInternal >= left && xInternal <= right) { originZone = i; break; }
        }

        let originLane = 0, bestLaneDist = Infinity;
        laneY.forEach((vy, i) => {
            const d = Math.abs(vy - ky);
            if (d < bestLaneDist) { bestLaneDist = d; originLane = i; }
        });

        // =====================================================
        // 1) Choix du bonus + cible zone/lane (en partant du GK)
        // =====================================================
        pendingClearanceBonus =
            defenseAction === "hands" ? 7 :
                defenseAction === "punch" ? 4 : 5;

        // ✅ relance "vers l'avant" par rapport à l'équipe qui relance
        const forwardZone = (lines) => {
            if (defenseTeam === "internal") return Math.min(3, originZone + lines);
            return Math.max(0, originZone - lines);
        };

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
        if (originZone <= 2) {
            if (originLane > 0) laneOptions.push(originLane - 1);
            if (originLane < laneY.length - 1) laneOptions.push(originLane + 1);
        }
        const targetLane = laneOptions[Math.floor(Math.random() * laneOptions.length)];

        const receiverNumber = pickReceiverInCell(defenseTeam, targetZone, targetLane, 6, null);
        const receiverEl = getCarrierElement(defenseTeam, receiverNumber);

        // =====================================================
        // 2) Logs/UI
        // =====================================================
        setMessage(TEXTS.ui.keeperRestartMain, `${TEXTS.ui.keeperRestartSub} (#${receiverNumber})`);
        pushLogEntry("Relance du gardien (auto)", [
            `Action: ${defenseAction}`,
            `Bonus relance: +${pendingClearanceBonus}`,
            `Vers zone ${targetZone + 1}, ligne ${targetLane + 1}`,
            `Receveur: #${receiverNumber}`,
        ]);

        // =====================================================
        // 3) Visuel : ballon au GK -> pause -> ballon vers receveur
        // =====================================================
        if (ballEl) {
            ballEl.style.left = kx + "%";
            ballEl.style.top  = ky + "%";
            ballEl.textContent = "1";
        }

        // si pas de receveur DOM, fallback direct
        if (!receiverEl || !ballEl) {
            ball.frontOfKeeper = false;
            resetLastDribbler();
            moveBallToPlayer(defenseTeam, receiverNumber);
            keeperRestartMustPass = true;
            if (afterClearance) afterClearance();
            return;
        }

        const rx = parseFloat(receiverEl.style.left);
        const ry = parseFloat(receiverEl.style.top);

        // ✅ le gardien garde la balle un peu (réalisme)
        setTimeout(() => {
            animateBallToXY(rx, ry, () => {
                // ✅ possession seulement APRÈS l'anim
                ball.frontOfKeeper = false;
                resetLastDribbler();
                moveBallToPlayer(defenseTeam, receiverNumber);

                keeperRestartMustPass = true;
                if (afterClearance) afterClearance();
            });
        }, GK_HOLD_MS);
    }

    // ==========================
    //   RESOLVE: PASS
    // ==========================
    function resolvePass(attackTeam, defenseTeam, defenseAction, defenderPick = null) {
        const wasKickoff = isKickoff;
        isKickoff = false;

        const duel = runFieldDuel({ attackTeam, defenseTeam, attackType: "pass", defenseAction, defenderPick });


        if (duel.isTie) {
            pushLogEntry("Duel équilibré (pass)", [`Défense: ${defenseAction}`], duel.diceTag);
            phase = "attack"; pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        const duelResult = duel.duelResult;
        const resultText = buildActionResultText({ attackAction: "pass", defenseAction, duelResult });

        // ✅ si c'était la passe de relance GK, on consomme le flag ici
        if (keeperRestartMustPass) keeperRestartMustPass = false;

        // KICKOFF handling
        if (wasKickoff) {
            if (duelResult === "attack") {
                const dmNumbers = [5, 6];
                const number = dmNumbers[Math.floor(Math.random() * dmNumbers.length)];

                setMessage("Remise en jeu réussie !", `${TEAMS[attackTeam].label} joue court vers le n°${number}.`);
                pushLogEntry("Remise en jeu réussie", [`Défense: ${defenseAction}`, resultText], duel.diceTag);

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
                pushLogEntry("Remise en jeu ratée", [`Défense: ${defenseAction}`, resultText], duel.diceTag);

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

        // ✅ cible passe (lane élargie au milieu)
        let targetZone = ball.zoneIndex;
        let targetLane = ball.laneIndex;

        if (ball.zoneIndex < 3) {
            targetZone = ball.zoneIndex + 1;
            const laneOptions = [ball.laneIndex];

            // ✅ variante : au milieu, élargit “cellule”
            if (ball.zoneIndex <= 2) {
                if (ball.laneIndex > 0) laneOptions.push(ball.laneIndex - 1);
                if (ball.laneIndex < laneY.length - 1) laneOptions.push(ball.laneIndex + 1);
            }
            targetLane = laneOptions[Math.floor(Math.random() * laneOptions.length)];
        } else {
            targetLane = [0, 1, 2][Math.floor(Math.random() * 3)];
        }

        if (duelResult === "attack") {
            resetLastDribbler();

            const receiverNumber = pickReceiverInCell(attackTeam, targetZone, targetLane, ball.number, ball.number);
            moveBallToPlayer(attackTeam, receiverNumber);

            setMessage(`${resultText} !`, `${TEAMS[attackTeam].label} trouve le n°${receiverNumber} (zone ${targetZone + 1}).`);
            pushLogEntry(TEXTS.logs.passSuccessTitle, [`Vers n°${receiverNumber}`, `Défense: ${defenseAction}`], duel.diceTag);

            animateAndThen(() => {
                advanceTurn(attackTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
        } else {
            resetLastDribbler();

            const number = duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6;
            moveBallToPlayer(defenseTeam, number);

            setMessage(`${resultText} !`, `${TEAMS[defenseTeam].label} récupère avec le n°${number}.`);
            pushLogEntry(TEXTS.logs.passFailTitle, [`Défense: ${defenseAction}`], duel.diceTag);

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
        }
    }

    // ==========================
    //   RESOLVE: DRIBBLE
    // ==========================
    function resolveDribble(attackTeam, defenseTeam, defenseAction, defenderPick = null) {
        // ✅ interdit face GK
        if (ball.frontOfKeeper) {
            setMessage(TEXTS.ui.dribbleForbiddenMain, TEXTS.ui.dribbleForbiddenSub);
            pushLogEntry(TEXTS.logs.dribbleRefusedTitle, [TEXTS.logs.dribbleRefusedDetail]);
            phase = "attack";
            pendingAttack = null;
            return;
        }

        const oldZone = ball.zoneIndex;
        const lane = ball.laneIndex;

        const duel = runFieldDuel({ attackTeam, defenseTeam, attackType: "dribble", defenseAction, defenderPick });

        if (duel.isTie) {
            pushLogEntry("Duel équilibré (dribble)", [`Défense: ${defenseAction}`], duel.diceTag);
            phase = "attack"; pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        const duelResult = duel.duelResult;
        const resultText = buildActionResultText({ attackAction: "dribble", defenseAction, duelResult });

        const carrierId = getPlayerId(attackTeam, ball.number);
        const carrierEl = rootEl.querySelector(`[data-player="${carrierId}"]`);

        if (duelResult === "attack") {
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

                setMessage(`${resultText} !`, `${TEAMS[attackTeam].label} avance en zone ${newZone + 1}.`);
                pushLogEntry(`${resultText} !`, [`Vers zone ${newZone + 1}`, `Défense: ${defenseAction}`], duel.diceTag);

                animateAndThen(() => {
                    advanceTurn(attackTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
            } else {
                // front GK
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

                setMessage(`${resultText} !`, "Face au gardien ! Prochaine action : tir ou tir spécial.");
                pushLogEntry(`${resultText} !`, [`Défense: ${defenseAction}`], duel.diceTag);

                animateAndThen(() => {
                    advanceTurn(attackTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
            }
        } else {
            resetLastDribbler();

            const number = duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6;
            moveBallToPlayer(defenseTeam, number);

            setMessage(`${resultText} !`, `${TEAMS[defenseTeam].label} récupère avec le n°${number}.`);
            pushLogEntry(`${resultText} !`, [`Défense: ${defenseAction}`], duel.diceTag);

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
        }
    }

    // ==========================
    //   RESOLVE: SHOT (keeper duel inside)
    // ==========================
    function resolveShot(attackTeam, defenseTeam, defenseAction, isSpecial = false, defenderPick = null) {
        const originZone = ball.zoneIndex;
        const originLane = ball.laneIndex;

        const attackerId = getPlayerId(attackTeam, ball.number);
        const attackType = isSpecial ? "special" : "shot";

        // ✅ cas 1 : face GK => duel direct GK
        if (ball.frontOfKeeper) {
            resolveShotKeeperDuel({
                stage: "keeper",
                attackTeam,
                defenseTeam,
                originZone,
                originLane,
                isSpecial,
                gkAttackBase: attackBaseFor(attackType, attackTeam, ball.number),
                logParts: [`Tir depuis zone ${originZone + 1}`],
            }, defenseAction);
            return;
        }

        // ✅ cas 2 : duel vs défense de champ (si passe la défense => GK duel)
        const duel = runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick });

        if (duel.isTie) {
            pushLogEntry("Duel équilibré (shot)", [`Défense: ${defenseAction}`], duel.diceTag);
            phase = "attack"; pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        const duelResultField = duel.duelResult;
        const resultTextField = buildActionResultText({ attackAction: attackType, defenseAction, duelResult: duelResultField });

        if (duelResultField === "defense") {
            const number = duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6;
            moveBallToPlayer(defenseTeam, number);

            setMessage("Tir contré !", `${TEAMS[defenseTeam].label} récupère avec le n°${number}.`);
            pushLogEntry(resultTextField, [`Défense: ${defenseAction}`], duel.diceTag);

            phase = "attack";
            pendingAttack = null;

            animateAndThen(() => {
                advanceTurn(defenseTeam);
                showAttackBarForCurrentTeam();
                refreshUI();
            });
            return;
        }

        // ✅ tir passe la défense => GK duel
        pushLogEntry(resultTextField, [`Tir depuis zone ${originZone + 1}`], duel.diceTag);
        setMessage("Tir cadré !", `${TEAMS[defenseTeam].label} : le gardien va intervenir.`);

        const linesBehind = getFacingZoneIndex(originZone);
        const gkAttackBase =
            attackBaseFor(attackType, attackTeam, ball.number) -
            (linesBehind * DUEL_RULES.SHOT_DISTANCE_PENALTY_PER_LINE);

        // mise en scène ballon vers la zone 4 (sans changer porteur)
        const targetZone = 3;
        const center = getCellCenter(attackTeam, targetZone, originLane);
        ball.zoneIndex = targetZone;
        ball.laneIndex = originLane;

        if (ballEl) {
            ballEl.style.left = center.x + "%";
            ballEl.style.top  = center.y + "%";
        }

        pendingShotContext = {
            stage: "keeper",
            attackTeam,
            defenseTeam,
            originZone,
            originLane,
            isSpecial,
            gkAttackBase,
            logParts: [`Tir depuis zone ${originZone + 1}`],
        };

        animateShotToKeeper(defenseTeam, () => {
            setActionBar(buildDefenseGKHTML(), `mode-defense-${defenseTeam}`);

            setMessage(
                "Tir cadré !",
                `${TEAMS[defenseTeam].label} : Arrêt main / Dégagement poing / Special.`,
            );

            phase = "defense";
            pendingAttack = attackType;

            if (isAITeam(defenseTeam)) scheduleAIDefense(attackType, defenseTeam);
        });
    }

    // ==========================
    //   DUEL GK
    // ==========================
    function resolveShotKeeperDuel(ctx, defenseAction) {
        const { attackTeam, defenseTeam, originZone, isSpecial, gkAttackBase, logParts } = ctx;

        const attackerId = getPlayerId(attackTeam, ball.number);
        const keeperId   = getKeeperId(defenseTeam);

        let attackScore  = gkAttackBase * staminaFactor(attackerId);
        const gkFactor   = keeperId ? staminaFactor(keeperId) : 1.0;
        let defenseScore = defenseBaseFor(defenseAction, defenseTeam, 1, true) * gkFactor;

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

        const aTag = aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll));
        const dTag = dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll));

        const attackStamF = staminaFactor(attackerId);
        const defStamF    = keeperId ? staminaFactor(keeperId) : 1.0;

        const isGoodGK = (defenseAction === "hands" || defenseAction === "gk-special");
        const goodBonus = (DUEL_RULES.GOOD_COUNTER_BONUS ?? 0);
        const genericBonus = (DUEL_RULES.GENERIC_ATTACK_BONUS ?? 0);

        const breakdown = {
            rolls: {
                aTag, dTag,
                aBonus: aRoll.bonus,
                dBonus: dRoll.bonus,
            },
            attack: {
                base: gkAttackBase,
                staminaFactor: attackStamF,
                additions: [
                    { label: "d20 bonus", value: `+ ${aRoll.bonus.toFixed(2)}` },
                    ...(!isGoodGK ? [{ label: "Generic bonus", value: `+ ${genericBonus.toFixed(2)}` }] : []),
                ],
                total: attackScore,
            },
            defense: {
                base: defenseBaseFor(defenseAction, defenseTeam, 1, true),
                staminaFactor: defStamF,
                additions: [
                    { label: "d20 bonus", value: `+ ${dRoll.bonus.toFixed(2)}` },
                    ...(isGoodGK ? [{ label: "Good counter bonus", value: `+ ${goodBonus.toFixed(2)}` }] : []),
                ],
                total: defenseScore,
            },
            result: {
                bonusRuleLabel: isGoodGK ? `Good GK choice (+${goodBonus} defense)` : `Generic attack (+${genericBonus} attack)`,
                critWinner,
                diff: attackScore - defenseScore,
                winner: (critWinner ? critWinner : ((attackScore - defenseScore) > 0 ? "attack" : (attackScore - defenseScore) < 0 ? "defense" : "tie")),
            },
        };

        showDuelDice(attackScore, defenseScore, aRoll, dRoll, breakdown);

        applyStaminaCost(attackerId, "attack", isSpecial ? "special" : "shot");
        if (keeperId) applyStaminaCost(keeperId, "defenseGK", defenseAction);

        if (isSpecial) markSpecialUsed(attackerId);
        if (defenseAction === "gk-special" && keeperId) markSpecialUsed(keeperId);

        let duelResult = null;
        if (critWinner) {
            duelResult = critWinner;
        } else {
            const diff = attackScore - defenseScore;
            if (diff === 0) {
                ball.frontOfKeeper = false;
                resetLastDribbler();

                pushLogEntry("Duel tir vs gardien — égalité", [
                    `Tir depuis zone ${originZone + 1}`,
                    "Égalité => défense récupère",
                ], `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`);

                givePossessionOnTie(defenseTeam, null);

                phase = "attack";
                pendingAttack = null;

                performKeeperClearance(defenseTeam, "hands", () => {
                    advanceTurn(defenseTeam);
                    showAttackBarForCurrentTeam();
                    refreshUI();
                });
                return;
            }
            duelResult = diff > 0 ? "attack" : "defense";
        }

        const diceTag = `🎲 ${attackScore.toFixed(1)}-${defenseScore.toFixed(1)}`;
        ball.frontOfKeeper = false;
        resetLastDribbler();

        if (duelResult === "attack") {
            score[attackTeam]++;

            setMessage(
                isSpecial ? `BUT SPÉCIAL pour ${TEAMS[attackTeam].label} !` : `BUT pour ${TEAMS[attackTeam].label} !`,
                `Score : ${score.internal} - ${score.external}.`
            );

            pushLogEntry(
                isSpecial ? TEXTS.logs.longShotGoalTitle : TEXTS.logs.longShotGoalTitle,
                [`Tir depuis zone ${originZone + 1}`, ...logParts],
                diceTag
            );

            const newTeam   = defenseTeam;
            const newNumber = 8;

            animateGoalThenReset(attackTeam, () => {
                isKickoff = true;
                keeperRestartMustPass = false;
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

        // ✅ ARRÊT : relance auto (plus de bouton)
        pushLogEntry(TEXTS.logs.longShotSavedTitle, [`Tir depuis zone ${originZone + 1}`, ...logParts], diceTag);

        // le “defenseAction” était hands/punch/gk-special
        phase = "attack";
        pendingAttack = null;

        performKeeperClearance(defenseTeam, defenseAction, () => {
            advanceTurn(defenseTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   DEFENDER PREVIEW (met à jour la card du défenseur)
    // ==========================
    function setDefenderPreviewFor(action, defenseTeam) {
        const defenderPrefix = (defenseTeam === "internal") ? "home" : "away";
        const isKeeperStage = !!pendingShotContext && pendingShotContext.stage === "keeper";
        const isCloseRange = ball.frontOfKeeper && (action === "shot" || action === "special");

        if (isCloseRange || isKeeperStage) {
            updateSideCard(defenderPrefix, defenseTeam, 1);
            return;
        }

        const { defenderId, defenderSlot } = pickFieldDefender(defenseTeam, ball.zoneIndex, ball.laneIndex);
        updateSideCard(defenderPrefix, defenseTeam, defenderSlot || 6);
    }

    // ==========================
    //   BAR show
    // ==========================
    function showAttackBarForCurrentTeam() {
        if (isGameOver) return;

        setActionBar(buildAttackActionsHTML(), `mode-attack-${currentTeam}`);

        const defTeam = otherTeam(currentTeam);
        const defaultAction = isKickoff ? "pass" : (ball.frontOfKeeper ? "shot" : "pass");
        setDefenderPreviewFor(defaultAction, defTeam);

        if (isAITeam(currentTeam)) scheduleAIAttack();
        updateCardsPower();
    }

    // ==========================
    //   HANDLERS
    // ==========================
    function handleAttackClick(action) {
        if (isGameOver || isAnimating) return;
        if (turns >= GAME_RULES.MAX_TURNS || phase !== "attack") return;
        if (!["shot","pass","dribble","special"].includes(action)) return;

        // ✅ kickoff = pass only
        if (isKickoff) {
            if (action !== "pass") return;
            resolveKickoffPass(currentTeam);
            return;
        }

        // ✅ relance GK = pass only (un tour)
        if (keeperRestartMustPass && action !== "pass") {
            setMessage(TEXTS.ui.keeperRestartMain, TEXTS.ui.keeperRestartSub);
            return;
        }

        // ✅ face GK : shot/special only
        if (ball.frontOfKeeper && action !== "shot" && action !== "special") return;

        if (action === "special") {
            const attackerId = getPlayerId(currentTeam, ball.number);
            if (!canUseSpecial(attackerId)) {
                setMessage(TEXTS.ui.specialCooldownMain, TEXTS.ui.specialCooldownSub);
                phase = "attack";
                pendingAttack = null;
                return;
            }
        }

        pendingAttack = action;
        phase = "defense";

        const defTeam = otherTeam(currentTeam);

        const isKeeperChoiceUI = (action === "shot" || action === "special") && ball.frontOfKeeper;

        if (!isKeeperChoiceUI) {
            const picked = pickFieldDefender(defTeam, ball.zoneIndex, ball.laneIndex);
            pendingDefenseContext = { defenseTeam: defTeam, ...picked };
        } else {
            pendingDefenseContext = { defenseTeam: defTeam, defenderId: getKeeperId(defTeam), defenderSlot: 1 };
        }

        const defenderPrefix = (defTeam === "internal") ? "home" : "away";
        updateSideCard(defenderPrefix, defTeam, pendingDefenseContext.defenderSlot || 6);


        let html;
        if (action === "shot" || action === "special") {
            const isCloseRange = ball.frontOfKeeper;
            if (isCloseRange) {
                html = buildDefenseGKHTML();
                setMessage(
                    `${TEAMS[currentTeam].label} prépare un ${action === "special" ? "TIR SPÉCIAL" : "TIR"} !`,
                    `${TEAMS[defTeam].label} (gardien) : Arrêt main / Poing / Special.`,
                );
            } else {
                html = buildDefenseFieldHTML();
                setMessage(
                    `${TEAMS[currentTeam].label} tente un ${action === "special" ? "TIR SPÉCIAL" : "TIR"} !`,
                    `${TEAMS[defTeam].label} : Block / Intercept / Tackle / Special.`,
                );
            }
        } else {
            html = buildDefenseFieldHTML();
            setMessage(
                `${TEAMS[currentTeam].label} prépare un ${action.toUpperCase()} !`,
                `${TEAMS[defTeam].label} : Block / Intercept / Tackle / Special.`,
            );
        }

        setActionBar(html, `mode-defense-${defTeam}`);
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

        const attackTeam = currentTeam;
        const defenseTeam = otherTeam(currentTeam);
        const attack = pendingAttack;

        if (defense === "field-special") {
            const defenderId = pendingDefenseContext?.defenderId ?? null;
            if (defenderId && !canUseSpecial(defenderId)) defense = "block";
        }

        if (defense === "gk-special") {
            const keeperId = getKeeperId(defenseTeam);
            if (keeperId && !canUseSpecial(keeperId)) defense = "hands";
        }

        // ✅ keeper duel stage
        if ((attack === "shot" || attack === "special") && pendingShotContext && pendingShotContext.stage === "keeper") {
            resolveShotKeeperDuel(pendingShotContext, defense);
            pendingShotContext = null;
            return;
        }

        phase = "attack";
        pendingAttack = null;

        const defenderPick = pendingDefenseContext;
        pendingDefenseContext = null;

        if (attack === "pass") resolvePass(attackTeam, defenseTeam, defense, defenderPick);
        else if (attack === "dribble") resolveDribble(attackTeam, defenseTeam, defense, defenderPick);
        else if (attack === "shot") resolveShot(attackTeam, defenseTeam, defense, false, defenderPick);
        else if (attack === "special") resolveShot(attackTeam, defenseTeam, defense, true, defenderPick);

    }

    function showPlayerCard(playerId) {
        if (!playerId) return;
        const team = playerId.startsWith("I") ? "internal" : "external";
        const number = parseInt(playerId.slice(1), 10);
        const prefix = (team === "internal") ? "home" : "away";
        updateSideCard(prefix, team, number);
    }

    function bindPlayerClickHandlers() {
        $$(".player").forEach((el) => {
            el.addEventListener("click", () => showPlayerCard(el.dataset.player));
        });
    }

    // ==========================
    //   INIT
    // ==========================
    function init() {
        initBasePositions();
        applyRosterToDOM();
        initStamina();

        bindPlayerClickHandlers();
        bindDuelTooltipEvents();

        turns = 0;
        currentTeam = "internal";
        score = { internal: 0, external: 0 };
        phase = "attack";
        pendingAttack = null;
        isAnimating = false;
        lastDribblerId = null;
        isKickoff = true;
        keeperRestartMustPass = false;
        isGameOver = false;
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
