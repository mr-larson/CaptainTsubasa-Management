// resources/js/Pages/Match/engine/ui.js
import { TEXTS, STATS, ACTION_BAR_FADE_MS } from './constants.js';
import { getStaminaRatio, getStaminaTier } from './stamina.js';
import { getPlayerId } from './field.js';

// -----------------------------------------------------------
//   Dépendances injectées
// -----------------------------------------------------------
let _rootEl = null;
let _roster = null;
let _ui     = null;
let _state  = null;
let _TEAMS  = null;

export function initUIModule(rootEl, roster, ui, state, TEAMS) {
    _rootEl = rootEl;
    _roster = roster;
    _ui     = ui;
    _state  = state;
    _TEAMS  = TEAMS;
}

// -----------------------------------------------------------
//   Messages
// -----------------------------------------------------------
export function setMessage(main, sub) {
    if (_ui.msgMainEl && main !== undefined) _ui.msgMainEl.textContent = main;
    if (_ui.msgSubEl  && sub  !== undefined) _ui.msgSubEl.textContent  = sub;
}

export function setAIOverlay(visible, text) {
    if (!_ui.aiOverlayEl) return;
    if (visible) {
        _ui.aiOverlayEl.classList.add("visible");
        _ui.aiOverlayEl.textContent = text || TEXTS.ui.aiThinkingDefault;
    } else {
        _ui.aiOverlayEl.classList.remove("visible");
    }
}

// -----------------------------------------------------------
//   Score / tours
// -----------------------------------------------------------
export function updateScoreUI(state) {
    if (_ui.scoreInternalEl) _ui.scoreInternalEl.textContent = state.score.internal;
    if (_ui.scoreExternalEl) _ui.scoreExternalEl.textContent = state.score.external;
    const t = String(state.turns).padStart(2, "0");
    if (_ui.turnsDisplayEl)   _ui.turnsDisplayEl.textContent  = t;
    if (_ui.turnIndicatorEl)  _ui.turnIndicatorEl.textContent = t;
}

// -----------------------------------------------------------
//   Historique actions
// -----------------------------------------------------------
const actionHistory = [];
const MAX_HISTORY   = 15;

export function pushLogEntry(logKeyOrText, details = [], diceTag = null, state) {
    const main = TEXTS.logs[logKeyOrText] ?? logKeyOrText;
    const d    = (details || []).map(x => typeof x === "string" ? (TEXTS.logs[x] ?? x) : x).filter(Boolean);

    if (_ui.currentActionTitleEl)  _ui.currentActionTitleEl.textContent  = main || "–";
    if (_ui.currentActionDetailEl) _ui.currentActionDetailEl.textContent = d.length ? d.join(" | ") : "";

    const turns    = state?.turns ?? 0;
    const turnLabel= `T${String(turns + 1).padStart(2, "0")}`;
    const line     = diceTag ? `${turnLabel} — ${main} (${diceTag})` : `${turnLabel} — ${main}`;

    actionHistory.push(line);
    if (actionHistory.length > MAX_HISTORY) actionHistory.shift();
    if (_ui.historyListEl) _ui.historyListEl.innerHTML = actionHistory.map(l => `<li>${l}</li>`).join("");
}

// -----------------------------------------------------------
//   Photo card
// -----------------------------------------------------------
function ensureCardPhotoLayer(cardEl) {
    if (!cardEl) return null;
    if (getComputedStyle(cardEl).position === "static") cardEl.style.position = "relative";
    cardEl.style.overflow = "hidden";

    let img = cardEl.querySelector("img.player-card-photo");
    if (!img) {
        img = document.createElement("img");
        img.className   = "player-card-photo hidden";
        img.alt         = "";
        img.loading     = "lazy";
        img.decoding    = "async";
        Object.assign(img.style, { position: "absolute", inset: "0", width: "100%", height: "100%", objectFit: "cover", pointerEvents: "none", zIndex: "50" });
        img.addEventListener("error", () => { img.classList.add("hidden"); img.removeAttribute("src"); });
        cardEl.appendChild(img);
    }
    return img;
}

function setCardPhoto(cardEl, photoUrl) {
    const img = ensureCardPhotoLayer(cardEl);
    if (!img) return;
    const raw = String(photoUrl || "").trim();
    if (!raw) { img.classList.add("hidden"); img.removeAttribute("src"); return; }
    let url = raw;
    if (url.startsWith("storage/")) url = `/${url}`;
    if (!/^https?:\/\//.test(url) && !url.startsWith("/")) url = `/${url}`;
    img.src = url;
    img.classList.remove("hidden");
}

// -----------------------------------------------------------
//   Side card
// -----------------------------------------------------------
export function updateSideCard(prefix, team, slotNumber) {
    const info = _roster.getPlayerInfo(team, slotNumber);
    const setText = (id, value) => {
        const el = _rootEl.querySelector(id);
        if (el) el.textContent = value ?? "—";
    };

    const fullName = info ? `${info.firstname} ${info.lastname}`.trim() : "";
    setText(`#${prefix}-name`,   fullName || `#${slotNumber}`);
    setText(`#${prefix}-role`,   info?.position || "—");
    setText(`#${prefix}-number`, info ? String(info.number) : String(slotNumber));
    setText(`#${prefix}-team`,   _TEAMS[team].label);

    const stat = (k) => Number(info?.stats?.[k] ?? 0) || 0;
    ["shot","pass","dribble","attack","block","intercept","tackle","defense","hand_save","punch_save"]
        .forEach(k => setText(`#${prefix}-stat-${k}`, String(stat(k))));

    const isGK = (info?.position || "").toLowerCase().includes("goalkeeper");
    ["block","intercept","tackle","dribble"].forEach(k => {
        const el = _rootEl.querySelector(`#${prefix}-stat-${k}`)?.parentElement;
        if (el) el.classList.toggle("hidden", isGK);
    });
    ["hand_save","punch_save"].forEach(k => {
        const el = _rootEl.querySelector(`#${prefix}-stat-${k}`)?.parentElement;
        if (el) el.classList.toggle("hidden", !isGK);
    });

    const playerId = getPlayerId(team, slotNumber);
    const ratio    = getStaminaRatio(playerId);
    const fillEl   = _rootEl.querySelector(`#${prefix}-energy-fill`);
    if (fillEl) {
        fillEl.style.width = `${Math.max(0, ratio * 100)}%`;
        fillEl.className   = `energy-fill e-${getStaminaTier(playerId)}`;
    }

    const portraitEl = _rootEl.querySelector(`#${prefix}-portrait`);
    if (portraitEl) setCardPhoto(portraitEl, info?.photo);
}

export function syncRecovererCard(defenseTeam, slot) {
    updateSideCard(defenseTeam === "internal" ? "home" : "away", defenseTeam, slot);
}

export function updateTeamCard(ball) {
    _ui.homeBallIconEl?.classList.toggle("hidden", ball.team !== "internal");
    _ui.awayBallIconEl?.classList.toggle("hidden", ball.team !== "external");
    updateSideCard(ball.team === "internal" ? "home" : "away", ball.team, ball.number);
    updateCardsPower(ball);
}

// -----------------------------------------------------------
//   Puissances cartes
// -----------------------------------------------------------
export function updateCardsPower(ball) {
    if (!_ui.actionBarEl) return;

    const carrier      = _roster.getPlayerInfo(ball.team, ball.number);
    const carrierStats = carrier?.stats ?? {};

    _ui.actionBarEl.querySelectorAll(".skill-card").forEach((btn) => {
        const a   = btn.dataset.action;
        const map = { pass: "pass", dribble: "dribble", shot: "shot", special: "attack" };
        const el  = btn.querySelector(".skill-power");
        if (el) el.textContent = String(Number(carrierStats[map[a]] ?? 0));
    });

    // Label special attaque
    const specialBtn = _ui.actionBarEl.querySelector('.skill-card[data-action="special"]');
    if (specialBtn) {
        const titleEl = specialBtn.querySelector('.skill-title');
        const subEl   = specialBtn.querySelector('.skill-sub');
        if (titleEl && subEl) {
            const specials = _roster.getSpecialMoves(ball.team, ball.number).filter(m => m?.mode === "attack");
            if (!specials.length) {
                titleEl.textContent = TEXTS.cards.attack.special.title;
                subEl.textContent   = TEXTS.cards.attack.special.sub;
            } else if (specials.length === 1) {
                titleEl.textContent = specials[0].label       || TEXTS.cards.attack.special.title;
                subEl.textContent   = specials[0].description || TEXTS.cards.attack.special.sub;
            } else {
                titleEl.textContent = "Spéciaux";
                subEl.textContent   = specials.map(m => m.short_label || m.label).filter(Boolean).join(" / ") || TEXTS.cards.attack.special.sub;
            }
        }
    }

    const mode = [..._ui.actionBarEl.classList].find(c => c.startsWith("mode-defense-"));
    if (!mode) return;

    const defenseTeam = mode.includes("external") ? "external" : "internal";
    const isGK        = !!_ui.actionBarEl.querySelector('.def-card[data-defense="hands"]');

    if (isGK) {
        const gkStats = _roster.getPlayerInfo(defenseTeam, 1)?.stats ?? {};
        const mapGK   = { hands: "hand_save", punch: "punch_save", "gk-special": "defense" };
        _ui.actionBarEl.querySelectorAll(".def-card").forEach((btn) => {
            const el = btn.querySelector(".def-power");
            if (el) el.textContent = String(Number(gkStats[mapGK[btn.dataset.defense]] ?? 0));
        });
        _updateSpecialDefLabel(_ui.actionBarEl.querySelector('.def-card[data-defense="gk-special"]'), defenseTeam, 1, "defense", TEXTS.cards.defenseGK["gk-special"]);
        return;
    }

    const ctx  = _state.pendingDefenseContext;
    const slot = ctx?.defenderSlot;
    if (!slot) return;

    const dStats = _roster.getPlayerInfo(defenseTeam, slot)?.stats ?? {};
    const mapF   = { block: "block", intercept: "intercept", tackle: "tackle", "field-special": "defense" };
    _ui.actionBarEl.querySelectorAll(".def-card").forEach((btn) => {
        const el = btn.querySelector(".def-power");
        if (el) el.textContent = String(Number(dStats[mapF[btn.dataset.defense]] ?? 0));
    });
    _updateSpecialDefLabel(_ui.actionBarEl.querySelector('.def-card[data-defense="field-special"]'), defenseTeam, slot, "defense", TEXTS.cards.defenseField["field-special"]);
}

function _updateSpecialDefLabel(btn, team, slot, mode, defaultCfg) {
    if (!btn) return;
    const titleEl = btn.querySelector('.def-title');
    const subEl   = btn.querySelector('.def-sub');
    if (!titleEl || !subEl) return;
    const specials = _roster.getSpecialMoves(team, slot).filter(m => m?.mode === mode);
    if (!specials.length) {
        titleEl.textContent = defaultCfg.title;
        subEl.textContent   = defaultCfg.sub;
    } else if (specials.length === 1) {
        titleEl.textContent = specials[0].label       || defaultCfg.title;
        subEl.textContent   = specials[0].description || defaultCfg.sub;
    } else {
        titleEl.textContent = "Spéciaux";
        subEl.textContent   = specials.map(m => m.short_label || m.label).filter(Boolean).join(" / ") || defaultCfg.sub;
    }
}

// -----------------------------------------------------------
//   Construction HTML barre d'actions
// -----------------------------------------------------------
function buildSkillCard(actionKey, cfg) {
    return `<button class="skill-card" data-action="${actionKey}">
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
    return `<button class="def-card" data-defense="${defKey}">
        <div class="def-icon">${cfg.icon}</div>
        <div class="def-title">${cfg.title}</div>
        <div class="def-sub">${cfg.sub}</div>
        <div class="def-bottom">
            <div class="def-power"></div>
            <div class="def-cost">Énergie <span></span></div>
        </div>
    </button>`;
}

export function buildAttackActionsHTML(ball, roster) {
    const cfg      = TEXTS.cards.attack;
    const specials = roster.getSpecialMoves(ball.team, ball.number).filter(m => m?.mode === "attack");
    return `<div id="attack-strip">
        ${buildSkillCard("shot",    cfg.shot)}
        ${buildSkillCard("pass",    cfg.pass)}
        ${buildSkillCard("dribble", cfg.dribble)}
        ${specials.length ? buildSkillCard("special", cfg.special) : ""}
    </div>`;
}

export function buildDefenseFieldHTML(defenderTeam, defenderSlot, roster) {
    const cfg      = TEXTS.cards.defenseField;
    const specials = roster.getSpecialMoves(defenderTeam, defenderSlot).filter(m => m?.mode === "defense");
    return `<div id="defense-strip">
        ${buildDefCard("block",     cfg.block)}
        ${buildDefCard("intercept", cfg.intercept)}
        ${buildDefCard("tackle",    cfg.tackle)}
        ${specials.length ? buildDefCard("field-special", cfg["field-special"]) : ""}
    </div>`;
}

export function buildDefenseGKHTML(defenderTeam, roster) {
    const cfg      = TEXTS.cards.defenseGK;
    const specials = roster.getSpecialMoves(defenderTeam, 1).filter(m => m?.mode === "defense");
    return `<div id="defense-strip">
        ${buildDefCard("hands", cfg.hands)}
        ${buildDefCard("punch", cfg.punch)}
        ${specials.length ? buildDefCard("gk-special", cfg["gk-special"]) : ""}
    </div>`;
}

export function initUIFromStats() {
    const attackStrip = _rootEl.querySelector("#attack-strip");
    if (attackStrip) {
        attackStrip.querySelectorAll(".skill-card").forEach(btn => {
            const cfg   = STATS.attack[btn.dataset.action];
            const costEl= btn.querySelector(".skill-cost span");
            if (cfg && costEl) costEl.textContent = cfg.cost;
        });
    }
    const defenseStrip = _rootEl.querySelector("#defense-strip");
    if (defenseStrip) {
        defenseStrip.querySelectorAll(".def-card").forEach(btn => {
            const cfg   = STATS.defenseField[btn.dataset.defense] || STATS.defenseGK[btn.dataset.defense];
            const costEl= btn.querySelector(".def-cost span");
            if (cfg && costEl) costEl.textContent = cfg.cost;
        });
    }
}

export function setActionBar(html, modeClass, ball, roster, bindFn, isKickoff) {
    if (!_ui.actionBarEl) return;

    _ui.actionBarEl.classList.add("fade-out");

    setTimeout(() => {
        _ui.actionBarEl.innerHTML = html;
        _ui.actionBarEl.className = _ui.actionBarEl.className.replace(/\bmode-[^\s]+/g, "");
        if (modeClass) _ui.actionBarEl.classList.add(modeClass);

        initUIFromStats();
        updateCardsPower(ball);

        if (isKickoff && html.includes("attack-strip")) {
            _ui.actionBarEl.querySelectorAll(".skill-card").forEach(btn => {
                if (btn.dataset.action !== "pass") {
                    btn.style.display = "none";
                } else {
                    const t = btn.querySelector('.skill-title');
                    const s = btn.querySelector('.skill-sub');
                    if (t) t.textContent = "Coup d'envoi";
                    if (s) s.textContent = "Passe obligatoire";
                }
            });
        }

        _ui.actionBarEl.classList.remove("fade-out");
        _ui.actionBarEl.classList.add("fade-in");

        if (bindFn) bindFn();
    }, ACTION_BAR_FADE_MS);
}

// -----------------------------------------------------------
//   Roster → DOM (numéros)
// -----------------------------------------------------------
export function applyRosterToDOM(roster, rootEl) {
    for (const team of ["internal", "external"]) {
        for (let slot = 1; slot <= 11; slot++) {
            const id = (team === "internal" ? "I" : "E") + String(slot);
            const el = rootEl.querySelector(`.player[data-player="${id}"]`);
            if (!el) continue;
            const info = roster.getPlayerInfo(team, slot);
            if (!info) continue;
            el.textContent      = String(info.number);
            el.dataset.slot     = String(slot);
            el.dataset.jersey   = String(info.number);
            el.dataset.firstname= info.firstname;
            el.dataset.lastname = info.lastname;
            el.dataset.position = info.position;
        }
    }
}
