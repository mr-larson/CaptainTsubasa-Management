// resources/js/Pages/Match/engine/ui.js
import { TEXTS, STATS, ACTION_BAR_FADE_MS } from './constants.js';
import { getStaminaRatio, getStaminaTier } from './stamina.js';
import { getPlayerId } from './field.js';

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

export function updateScoreUI(state) {
    if (_ui.scoreInternalEl) _ui.scoreInternalEl.textContent = state.score.internal;
    if (_ui.scoreExternalEl) _ui.scoreExternalEl.textContent = state.score.external;
    const t = String(state.turns).padStart(2, "0");
    if (_ui.turnsDisplayEl)   _ui.turnsDisplayEl.textContent  = t;
    if (_ui.turnIndicatorEl)  _ui.turnIndicatorEl.textContent = t;
}

const LOG_TYPES = {
    kickoff:           { icon: '🚀', color: 'slate'  },
    'pass-success':    { icon: '✅', color: 'blue'   },
    'pass-failed':     { icon: '❌', color: 'red'    },
    'pass-recovered':  { icon: '✋', color: 'orange' },
    'dribble-success': { icon: '🌀', color: 'blue'   },
    'dribble-failed':  { icon: '❌', color: 'red'    },
    'dribble-face-gk': { icon: '🌀', color: 'blue'   },
    'shot-goal':       { icon: '⚽', color: 'gold'   },
    'shot-saved':      { icon: '🧤', color: 'amber'  },
    'shot-blocked':    { icon: '🧱', color: 'slate'  },
    'shot-recovered':  { icon: '🔄', color: 'slate'  },
    'special-goal':    { icon: '🔥', color: 'gold'   },
    'special-saved':   { icon: '🔥', color: 'amber'  },
    'gk-restart':      { icon: '🥅', color: 'slate'  },
    matchend:          { icon: '🏁', color: 'slate'  },
    injury:            { icon: '🤕', color: 'red'    },
    'card-yellow':     { icon: '🟨', color: 'yellow' },
    'card-red':        { icon: '🟥', color: 'red'    },
    foul:              { icon: '⚠️', color: 'slate'  },
    substitution:      { icon: '🔄', color: 'blue'   },
    unknown:           { icon: '▸',  color: 'slate'  },
};

class LogEntry {
    constructor({ turn = 0, actionType = 'unknown', team = null, result = 'neutral', mainText = '–', details = [], diceTag = null } = {}) {
        this.turn       = turn;
        this.actionType = actionType;
        this.team       = team;
        this.result     = result;
        this.mainText   = mainText;
        this.details    = details;
        this.diceTag    = diceTag;
    }

    get _cfg() {
        return LOG_TYPES[this.actionType] ?? LOG_TYPES.unknown;
    }

    toHTML() {
        const pad    = n => String(n).padStart(2, '0');
        const badge  = `<span class="log-turn-badge">T${pad(this.turn)}</span>`;
        const dot    = this.team
            ? `<span class="log-team-dot log-team-${this.team}"></span>`
            : '';
        const dice   = this.diceTag
            ? `<span class="log-dice">${this.diceTag}</span>`
            : '';
        const detail = this.details.length
            ? `<div class="log-line-2">${this.details.join(' · ')}</div>`
            : '';

        return `<li class="log-entry log-result-${this.result} log-${this._cfg.color}">
            <div class="log-left">${badge}${dot}</div>
            <div class="log-center">
                <span class="log-icon">${this._cfg.icon}</span>
                <div class="log-text">
                    <div class="log-line-1"><span class="log-main">${this.mainText}</span>${dice}</div>
                    ${detail}
                </div>
            </div>
        </li>`;
    }
}

const logHistory  = [];
const MAX_HISTORY = 30;

function _pushLog(entry) {
    logHistory.push(entry);
    if (logHistory.length > MAX_HISTORY) logHistory.shift();
    if (_ui?.historyListEl) {
        _ui.historyListEl.innerHTML = [...logHistory].reverse().map(e => e.toHTML()).join('');
    }
}

function _detectType(key, details) {
    const k = (key  || '').toLowerCase();
    const d = (details || []).join(' ').toLowerCase();

    if (k.includes('kickoff'))                                 return ['kickoff',          'neutral'];
    if (k.includes('matchend'))                                return ['matchend',          'neutral'];
    if (k.includes('substitution'))                            return ['substitution',      'neutral'];
    if (k === 'passsuccesstitle')                              return ['pass-success',      'success'];
    if (k === 'passrecoveredtitle')                            return ['pass-recovered',    'success'];
    if (k === 'passfailtitle' || d.includes('intercept'))     return ['pass-failed',       'failed' ];
    if (k === 'dribblesuccesstitle')                           return ['dribble-success',   'success'];
    if (k === 'frontofkeepертitle' || k === 'shotgkequaltitle') return ['dribble-face-gk', 'success'];
    if (k.includes('dribblerecovered'))                        return ['pass-recovered',    'success'];
    if (k.includes('dribble') && k.includes('refus'))          return ['dribble-failed',   'failed' ];
    if (k.includes('dribblefail'))                             return ['dribble-failed',    'failed' ];
    if (k.includes('goalspecial') || (k.includes('goal') && d.includes('special')))
        return ['special-goal',      'success'];
    if (k.includes('goal'))                                    return ['shot-goal',         'success'];
    if (k.includes('saved') && d.includes('special'))         return ['special-saved',     'neutral'];
    if (k.includes('saved'))                                   return ['shot-saved',        'neutral'];
    if (k.includes('blocked'))                                 return ['shot-blocked',      'neutral'];
    if (k.includes('recovered') && k.includes('shot'))        return ['shot-recovered',    'neutral'];
    if (k.includes('keeperrestart'))                           return ['gk-restart',        'neutral'];
    if (k.includes('injury') || d.includes('blessure'))       return ['injury',            'failed' ];
    if (d.includes('🟥') || d.includes('rouge'))              return ['card-red',          'failed' ];
    if (d.includes('🟨') || d.includes('jaune'))              return ['card-yellow',       'failed' ];
    if (k.includes('foul') || k.includes('faute'))             return ['foul',             'neutral'];

    return ['unknown', 'neutral'];
}

export function pushLogEntry(logKeyOrText, details = [], diceTag = null, state) {
    const main = TEXTS.logs[logKeyOrText] ?? logKeyOrText;

    const TECHNICAL_PATTERNS = [/^zone \d/i, /^defense:/i, /^ok bon/i, /^x mauvais/i, /^\(special/i];
    const d = (details || [])
        .map(x => typeof x === 'string' ? (TEXTS.logs[x] ?? x) : x)
        .filter(x => x && !TECHNICAL_PATTERNS.some(p => p.test(String(x))));

    if (_ui?.currentActionTitleEl)  _ui.currentActionTitleEl.textContent  = main || '–';
    if (_ui?.currentActionDetailEl) _ui.currentActionDetailEl.textContent = '';

    const turns = state?.turns ?? 0;
    const team  = state?.currentTeam ?? null;

    const [actionType, result] = _detectType(logKeyOrText, d);

    _pushLog(new LogEntry({ turn: turns, actionType, team, result, mainText: main, details: d, diceTag }));
}

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

    // Badges cartons/statut
    const matchYellows = (_state?.foulEvents ?? [])
        .filter(e => e.type === 'card' && e.card_type === 'yellow' && e.player_id === playerId)
        .length;
    const totalYellows = (info?.yellowCards ?? 0) + matchYellows;

    const badgeEl = _rootEl.querySelector(`#${prefix}-portrait`);
    badgeEl?.querySelectorAll('.card-badge').forEach(b => b.remove());

    if (totalYellows > 0 && badgeEl) {
        const badge = document.createElement('div');
        badge.className = 'card-badge';
        badge.style.cssText = 'position:absolute;bottom:2px;right:2px;background:#eab308;color:#1c1917;font-size:9px;font-weight:900;padding:1px 4px;border-radius:4px;z-index:60;';
        badge.textContent = `${totalYellows}🟨`;
        badgeEl.appendChild(badge);
    }
    if (info?.isAvailable === false && badgeEl) {
        const badge = document.createElement('div');
        badge.className = 'card-badge';
        badge.style.cssText = 'position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;font-size:9px;font-weight:900;padding:1px 4px;border-radius:4px;z-index:60;';
        badge.textContent = totalYellows >= 2 ? '🚫' : '🤕';
        badgeEl.appendChild(badge);
    }

    // Bouton remplacement — icône 🔄 à côté du ⚽ dans le coin de la carte
    _rootEl.querySelector(`#${prefix}-sub-btn`)?.remove();
    _rootEl.querySelector(`#${prefix}-sub-panel`)?.remove();

    const isControlledTeam = (
        _state?._matchConfig?.controlledSide === team ||
        _state?._matchConfig?.controlMode === 'both'
    );
    const staminaRatio = getStaminaRatio(playerId);
    const canSub = (
        isControlledTeam &&
        !_state?.isGameOver &&
        (_state?.substitutionCount ?? 0) < (_state?.MAX_SUBSTITUTIONS ?? 3) &&
        !(_state?.substitutions ?? []).some(s => s.outSlot === slotNumber && s.team === team) &&
        !_state?.isKickoff
    );

    if (canSub) {
        const cardEl = _rootEl.querySelector(`#${prefix}-card`);
        if (!cardEl) return;

        // Construire la liste des remplaçants
        const subs = [];
        for (let s = 1; s <= 11; s++) {
            if (s === slotNumber) continue;
            const subDomId = getPlayerId(team, s);
            const subEl    = _rootEl.querySelector(`[data-player="${subDomId}"]`);
            if (!subEl || subEl.classList.contains('unavailable')) continue;
            const subInfo  = _roster.getPlayerInfo(team, s);
            if (!subInfo) continue;
            subs.push({ slot: s, info: subInfo });
        }
        if (!subs.length) return;

        // Bouton icône positionné à côté du ballon
        const subBtn = document.createElement('button');
        subBtn.id = `${prefix}-sub-btn`;
        subBtn.title = `Remplacer (${(_state.MAX_SUBSTITUTIONS ?? 3) - (_state.substitutionCount ?? 0)} restant(s))`;
        subBtn.textContent = '🔄';
        subBtn.style.cssText = 'position:absolute;top:8px;right:30px;background:none;border:none;cursor:pointer;font-size:16px;padding:0;line-height:1;z-index:70;opacity:0.85;transition:opacity 0.15s;';
        subBtn.onmouseenter = () => subBtn.style.opacity = '1';
        subBtn.onmouseleave = () => subBtn.style.opacity = '0.85';

        // Panel liste remplaçants
        const panel = document.createElement('div');
        panel.id = `${prefix}-sub-panel`;
        panel.style.cssText = 'display:none;position:absolute;top:32px;right:4px;z-index:80;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.15);min-width:180px;overflow:hidden;';

        const header = document.createElement('div');
        header.style.cssText = 'padding:6px 10px;background:#0ea5e9;color:#fff;font-size:10px;font-weight:700;';
        header.textContent = `🔄 Choisir remplaçant`;
        panel.appendChild(header);

        subs.forEach(({ slot, info: subInfo }) => {
            const subPid = getPlayerId(team, slot);
            const stMax  = _state.staminaMax[subPid] ?? 100;
            const stCur  = _state.stamina[subPid]    ?? stMax;
            const pct    = stMax > 0 ? Math.round(stCur / stMax * 100) : 100;
            const color  = pct >= 75 ? '#22c55e' : pct >= 50 ? '#f59e0b' : '#ef4444';

            const item = document.createElement('button');
            item.style.cssText = 'width:100%;padding:6px 10px;text-align:left;font-size:11px;border:none;border-bottom:1px solid #f1f5f9;background:transparent;cursor:pointer;display:flex;justify-content:space-between;align-items:center;';
            item.innerHTML = `<span style="font-weight:600;">${subInfo.number}. ${subInfo.lastname}</span><span style="color:${color};font-size:10px;font-weight:700;">${pct}%⚡</span>`;
            item.onmouseenter = () => item.style.background = '#f0f9ff';
            item.onmouseleave = () => item.style.background = 'transparent';
            item.onclick = () => {
                if (_state._performSubstitution) {
                    const ok = _state._performSubstitution(team, slotNumber, slot);
                    if (ok) { subBtn.remove(); panel.remove(); }
                }
            };
            panel.appendChild(item);
        });

        let open = false;
        subBtn.onclick = (e) => {
            e.stopPropagation();
            open = !open;
            panel.style.display = open ? 'block' : 'none';
        };

        // Fermer si clic ailleurs
        document.addEventListener('click', () => {
            open = false;
            panel.style.display = 'none';
        }, { once: true });

        cardEl.appendChild(subBtn);
        cardEl.appendChild(panel);
    }
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
            if (info.isAvailable === false) {
                el.classList.add('unavailable');
            } else {
                el.classList.remove('unavailable');
            }
        }
    }
}
