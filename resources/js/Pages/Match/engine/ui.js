// resources/js/Pages/Match/engine/ui.js
import { TEXTS, STATS, ACTION_BAR_FADE_MS } from './constants.js';
import { getStaminaRatio, getStaminaTier } from './stamina.js';
import { getPlayerId } from './field.js';
import { isDiceAnimating, runAfterDiceAnimation, showBreakdownTooltip, hideBreakdownTooltip, isBreakdownTooltipVisible } from './dice.js';

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

    bindHistoryClickEvents();
}

/** Clic sur une entrée de l'historique → affiche le détail du duel (breakdown), s'il existe. */
function bindHistoryClickEvents() {
    if (!_ui?.historyListEl || _ui.historyListEl.dataset.clickBound) return;
    _ui.historyListEl.dataset.clickBound = '1';

    _ui.historyListEl.addEventListener('click', (event) => {
        const li = event.target.closest('.log-entry');
        if (!li) return;

        const id        = li.dataset.logId;
        const breakdown = id ? _logBreakdowns.get(id) : null;
        if (!breakdown) return;

        // Reclic sur la même entrée déjà ouverte → referme (comportement toggle).
        if (isBreakdownTooltipVisible() && _openLogId === id) {
            hideBreakdownTooltip();
            _openLogId = null;
            return;
        }
        showBreakdownTooltip(breakdown, li);
        _openLogId = id;
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('.log-entry') || event.target.closest('#duel-dice-tooltip')) return;
        hideBreakdownTooltip();
        _openLogId = null;
    });
}

// -----------------------------------------------------------
//   Event Notifications
// -----------------------------------------------------------
let _toastEl    = null;
let _overlayEl  = null;
let _toastTimer = null;

function _ensureToastEl() {
    if (_toastEl) return _toastEl;
    const fieldWrapper = _rootEl?.querySelector('#field-wrapper');
    if (!fieldWrapper) return null;
    if (getComputedStyle(fieldWrapper).position === 'static') {
        fieldWrapper.style.position = 'relative';
    }
    _toastEl = document.createElement('div');
    _toastEl.className = 'event-toast';
    fieldWrapper.appendChild(_toastEl);
    return _toastEl;
}

function _ensureOverlayEl() {
    if (_overlayEl) return _overlayEl;
    const fieldEl = _rootEl?.querySelector('#field');
    if (!fieldEl) return null;
    _overlayEl = document.createElement('div');
    _overlayEl.className = 'event-overlay';
    fieldEl.appendChild(_overlayEl);
    return _overlayEl;
}

export function showEventNotification(type, text, subText = null) {
    // Ne pas spoiler le résultat pendant l'animation des dés : différer le toast
    if (isDiceAnimating()) {
        runAfterDiceAnimation(() => showEventNotification(type, text, subText));
        return;
    }
    if (type === 'goal' || type === 'foul' || type === 'yellow') {
        const toast = _ensureToastEl();
        if (!toast) return;
        if (_toastTimer) { clearTimeout(_toastTimer); _toastTimer = null; }
        toast.className = `event-toast event-toast--${type}`;
        toast.innerHTML = text;
        toast.getBoundingClientRect(); // force reflow
        toast.classList.add('visible');
        _toastTimer = setTimeout(() => {
            toast.classList.add('hiding');
            toast.classList.remove('visible');
            setTimeout(() => toast.classList.remove('hiding'), 350);
        }, 2500);

    } else if (type === 'red' || type === 'injury' || type === 'freekick') {
        const overlay = _ensureOverlayEl();
        if (!overlay) return;
        const icon = type === 'red' ? '🟥' : type === 'injury' ? '🤕' : '⚽';
        overlay.className = `event-overlay event-overlay--${type}`;
        overlay.innerHTML = `
            <div class="event-overlay__icon">${icon}</div>
            <div class="event-overlay__text">${text}</div>
            ${subText ? `<div class="event-overlay__sub">${subText}</div>` : ''}
        `;
        overlay.getBoundingClientRect();
        overlay.classList.add('visible');
        setTimeout(() => overlay.classList.remove('visible'), 2200);
    }
}

// Bannière plein terrain « ⚽ Coup franc ! » — appelée par les résolveurs.
export function showFreeKickBanner(teamLabel, takerNumber) {
    showEventNotification('freekick', 'Coup franc !', `${teamLabel} — n°${takerNumber}`);
}

function _triggerEventNotification(actionType, main, details) {
    const firstDetail = (details || [])[0] ?? '';

    // Nettoyer les emojis et caractères parasites pour extraire le nom
    const extractName = (str) => str
        .replace(/🟥|🟨|🤕|⚽|⚠️|Carton rouge|Carton jaune|Double jaune|Expulsé|Blessure|épuisement|!/g, '')
        .split('—')[0]
        .trim();

    if (actionType === 'shot-goal' || actionType === 'special-goal') {
        // firstDetail contient "Lastname n°X (TeamName)"
        const scorer = extractName(firstDetail);
        const icon   = actionType === 'special-goal' ? '🔥' : '⚽';
        showEventNotification('goal', `${icon} But ! ${scorer}`);

    } else if (actionType === 'card-red') {
        const name = extractName(firstDetail);
        showEventNotification('red', main, name || null);
        _flashCard('red');

    } else if (actionType === 'injury') {
        const name = extractName(firstDetail);
        showEventNotification('injury', main, name || null);

    } else if (actionType === 'card-yellow') {
        const name = extractName(firstDetail);
        showEventNotification('yellow', `🟨 ${name}`);
        _flashCard('yellow');

    } else if (actionType === 'foul') {
        const name = extractName(firstDetail);
        showEventNotification('foul', `⚠️ Faute — ${name}`);
        _flashCard('yellow');
    }
}

function _flashCard(type) {
    if (!_state) return;
    // La faute vient du défenseur → flash sur la card adverse à l'équipe courante
    const defTeam = _state.currentTeam === 'internal' ? 'external' : 'internal';
    const prefix  = defTeam === 'internal' ? 'home' : 'away';
    const cardEl  = _rootEl?.querySelector(`#${prefix}-card`);
    if (!cardEl) return;
    cardEl.classList.remove('flash-yellow', 'flash-red');
    cardEl.getBoundingClientRect();
    cardEl.classList.add(type === 'red' ? 'flash-red' : 'flash-yellow');
    setTimeout(() => cardEl.classList.remove('flash-yellow', 'flash-red'), 1400);
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
    // Score différé pendant l'animation des dés (spoiler de but sinon) ;
    // state est partagé, le callback relira donc le score final.
    const applyScore = () => {
        if (_ui.scoreInternalEl) _ui.scoreInternalEl.textContent = state.score.internal;
        if (_ui.scoreExternalEl) _ui.scoreExternalEl.textContent = state.score.external;
    };
    if (isDiceAnimating()) runAfterDiceAnimation(applyScore);
    else applyScore();

    const minutes = state.turns * 2;
    const t = String(minutes).padStart(2, "0");
    if (_ui.turnsDisplayEl)   _ui.turnsDisplayEl.textContent = t + "'";
    if (_ui.turnIndicatorEl)  _ui.turnIndicatorEl.textContent = t + "'";

    // Mise à jour horloge SVG
    const arc  = document.getElementById('clock-arc');
    const hand = document.getElementById('clock-hand');
    if (arc) {
        const progress = (minutes / 90) * 69.1;
        arc.setAttribute('stroke-dasharray', `${progress} 69.1`);
    }
    if (hand) {
        const angle = (minutes / 90) * 360 - 90;
        const rad   = angle * Math.PI / 180;
        const x2    = 18 + 9 * Math.cos(rad);
        const y2    = 18 + 9 * Math.sin(rad);
        hand.setAttribute('x2', x2.toFixed(2));
        hand.setAttribute('y2', y2.toFixed(2));
    }
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
    'free-kick':       { icon: '⚽', color: 'blue'   },
    substitution:      { icon: '🔄', color: 'blue'   },
    unknown:           { icon: '▸',  color: 'slate'  },
};

let _logIdSeq = 0;

class LogEntry {
    constructor({ turn = 0, actionType = 'unknown', team = null, result = 'neutral', mainText = '–', details = [], diceTag = null, breakdown = null } = {}) {
        this.id         = String(++_logIdSeq);
        this.turn       = turn;
        this.actionType = actionType;
        this.team       = team;
        this.result     = result;
        this.mainText   = mainText;
        this.details    = details;
        this.diceTag    = diceTag;
        this.breakdown  = breakdown;
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
        const clickable = this.breakdown ? ' log-entry--clickable' : '';
        const infoIcon   = this.breakdown ? '<span class="log-info-icon" title="Voir le détail">ℹ</span>' : '';

        return `<li class="log-entry log-result-${this.result} log-${this._cfg.color}${clickable}" data-log-id="${this.id}">
            <div class="log-left">${badge}${dot}</div>
            <div class="log-center">
                <span class="log-icon">${this._cfg.icon}</span>
                <div class="log-text">
                    <div class="log-line-1"><span class="log-main">${this.mainText}</span>${dice}</div>
                    ${detail}
                </div>
            </div>
            ${infoIcon}
        </li>`;
    }
}

const logHistory      = [];
const _logBreakdowns  = new Map();
let _openLogId        = null;

export function resetLogHistory() {
    logHistory.length = 0;
    _logBreakdowns.clear();
    _openLogId = null;
    if (_ui?.historyListEl) _ui.historyListEl.innerHTML = '';
}

function _pushLog(entry) {
    logHistory.push(entry);
    if (entry.breakdown) _logBreakdowns.set(entry.id, entry.breakdown);
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
    if (k.includes('freekick') || d.includes('coup franc'))    return ['free-kick',         'neutral'];
    if (k.includes('foul') || k.includes('faute'))             return ['foul',             'neutral'];

    return ['unknown', 'neutral'];
}

export function pushLogEntry(logKeyOrText, details = [], diceTag = null, state, meta = null) {
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

    // Après
    const entry = new LogEntry({ turn: turns, actionType, team, result, mainText: main, details: d, diceTag, breakdown: meta ?? null });
    _pushLog(entry);

    // Historique COMPLET du déroulé du match, exploitable pour le résumé
    // post-match (action par action) côté Tab Calendar.
    if (state?.matchLog) {
        const duelMeta = meta?.meta ?? null; // breakdown.meta = { attacker, defender } (id, nom, numéro, action)
        state.matchLog.push({
            turn:       entry.turn,
            actionType: entry.actionType,
            team:       entry.team,
            result:     entry.result,
            text:       entry.mainText,
            details:    entry.details,
            diceTag:    entry.diceTag,
            // Données structurées pour le replay visuel (position du ballon + acteurs).
            // Peut être absent pour les anciens matchs / actions sans duel (kickoff, etc.).
            zone:            state?.ball?.zoneIndex ?? null,
            lane:            state?.ball?.laneIndex ?? null,
            front_of_keeper: state?.ball?.frontOfKeeper ?? false,
            action:          duelMeta?.attacker?.actionKey ?? null,
            def_action:      duelMeta?.defender?.actionKey ?? null,
            attacker:        duelMeta?.attacker
                ? { id: duelMeta.attacker.id, name: duelMeta.attacker.name, number: duelMeta.attacker.number }
                : null,
            defender:        duelMeta?.defender
                ? { id: duelMeta.defender.id, name: duelMeta.defender.name, number: duelMeta.defender.number }
                : null,
            meta:       meta ?? null,
        });
    }

    _triggerEventNotification(actionType, main, d);
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


    // ── STATUS BAR — tous les badges regroupés ──────────────────
    const playerDbId = _roster.getPlayerInfo(team, slotNumber)?.id ?? null;
    const matchYellows = (_state?.foulEvents ?? [])
        .filter(e => e.type === 'card' && e.card_type === 'yellow' && e.player_id === playerDbId)
        .length;
    const totalYellows = (info?.yellowCards ?? 0) + matchYellows;

    // Nettoyer les anciens badges du portrait
    const portraitBadges = _rootEl.querySelector(`#${prefix}-portrait`);
    portraitBadges?.querySelectorAll('.card-badge').forEach(b => b.remove());

    // Nettoyer les anciens badges dynamiques de la status-bar
    const statusBar = _rootEl.querySelector(`#${prefix}-status-bar`);
    statusBar?.querySelectorAll('.status-badge').forEach(b => b.remove());

    if (statusBar) {
        // 🤕 Indisponible (inséré en premier = apparaît à gauche)
        if (info?.isAvailable === false) {
            const b = document.createElement('div');
            b.className = 'status-badge';
            b.style.cssText = 'background:#ef4444;color:#fff;font-size:9px;font-weight:900;padding:1px 4px;border-radius:4px;white-space:nowrap;';
            b.textContent = totalYellows >= 2 ? '🚫' : '🤕';
            b.title = totalYellows >= 2 ? 'Expulsé' : 'Blessé / Indisponible';
            statusBar.insertBefore(b, statusBar.firstChild);
        }

        // 🟨 Cartons jaunes
        if (totalYellows > 0) {
            const b = document.createElement('div');
            b.className = 'status-badge';
            b.style.cssText = 'background:#eab308;color:#1c1917;font-size:9px;font-weight:900;padding:1px 4px;border-radius:4px;white-space:nowrap;';
            b.textContent = `${totalYellows}🟨`;
            b.title = `${totalYellows} carton(s) jaune`;
            statusBar.insertBefore(b, statusBar.firstChild);
        }

        // 👑 Capitaine
        if (info?.isCaptain) {
            const rerolls = _state?.captainReroll?.[team]?.rerollsRemaining ?? 0;
            const b = document.createElement('div');
            b.className = 'status-badge';
            b.setAttribute('data-captain-reroll', team);
            b.style.cssText = 'background:#f59e0b;color:#fff;font-size:9px;font-weight:900;padding:1px 5px;border-radius:4px;white-space:nowrap;cursor:default;';
            b.textContent = `👑 ${rerolls}`;
            b.title = `Capitaine — ${rerolls} relance(s) restante(s)`;
            statusBar.insertBefore(b, statusBar.firstChild);
        }
    }

    // Bouton remplacement
    _rootEl.querySelector(`#${prefix}-sub-btn`)?.remove();
    _rootEl.querySelector(`#${prefix}-sub-panel`)?.remove();

    const isControlledTeam = (
        _state?._matchConfig?.controlledSide === team ||
        _state?._matchConfig?.controlMode === 'both'
    );
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

        const subs = [];
        const subsPool = _roster.getSubs(team);
        for (const { slot: s, info: subInfo } of subsPool) {
            if (s === slotNumber) continue;
            if (subInfo.isAvailable === false) continue;
            subs.push({ slot: s, info: subInfo });
        }
        if (!subs.length) return;

        const subBtn = document.createElement('button');
        subBtn.id = `${prefix}-sub-btn`;
        subBtn.title = `Remplacer (${(_state.MAX_SUBSTITUTIONS ?? 3) - (_state.substitutionCount ?? 0)} restant(s))`;
        subBtn.textContent = '🔄';
        subBtn.style.cssText = 'background:none;border:none;cursor:pointer;font-size:14px;padding:0;line-height:1;opacity:0.85;transition:opacity 0.15s;';
        subBtn.onmouseenter = () => subBtn.style.opacity = '1';
        subBtn.onmouseleave = () => subBtn.style.opacity = '0.85';

        const panel = document.createElement('div');
        panel.id = `${prefix}-sub-panel`;
        panel.style.cssText = 'display:none;position:absolute;top:32px;right:4px;z-index:80;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.15);min-width:180px;overflow:hidden;';

        const header = document.createElement('div');
        header.style.cssText = 'padding:6px 10px;background:#0ea5e9;color:#fff;font-size:10px;font-weight:700;';
        header.textContent = `🔄 Choisir remplaçant`;
        panel.appendChild(header);

        // Liste défilable : si beaucoup de remplaçants, on peut scroller
        // au lieu de voir la liste déborder hors de l'écran.
        const listEl = document.createElement('div');
        listEl.style.cssText = 'max-height:min(220px,40vh);overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;';
        panel.appendChild(listEl);

        subs.forEach(({ slot, info: subInfo }) => {
            const subPid = getPlayerId(team, slot);
            const stMax  = _state.staminaMax[subPid] ?? 100;
            const stCur  = _state.stamina[subPid]    ?? stMax;
            const pct    = stMax > 0 ? Math.round(stCur / stMax * 100) : 100;
            const color  = pct >= 75 ? '#22c55e' : pct >= 50 ? '#f59e0b' : '#ef4444';

            const item = document.createElement('button');
            item.style.cssText = 'width:100%;padding:6px 10px;text-align:left;font-size:11px;border:none;border-bottom:1px solid #f1f5f9;background:transparent;cursor:pointer;display:flex;justify-content:space-between;align-items:center;';
            item.innerHTML = `
            <div>
                <div style="font-weight:700;">${subInfo.lastname}</div>
                <div style="font-size:9px;color:#94a3b8;">${subInfo.position ?? '—'}</div>
            </div>
            <span style="color:${color};font-size:10px;font-weight:700;">${pct}%⚡</span>`;
            item.onmouseenter = () => item.style.background = '#f0f9ff';
            item.onmouseleave = () => item.style.background = 'transparent';
            item.onclick = () => {
                if (_state._performSubstitution) {
                    const ok = _state._performSubstitution(team, slotNumber, slot);
                    if (ok) { subBtn.remove(); panel.remove(); }
                }
            };
            listEl.appendChild(item);
        });

        let open = false;
        subBtn.onclick = (e) => {
            e.stopPropagation();
            open = !open;
            panel.style.display = open ? 'block' : 'none';
        };

        document.addEventListener('click', () => {
            open = false;
            panel.style.display = 'none';
        }, { once: true });

        // 🔄 dans la status-bar, panel ancré sur la card
        const subBtnContainer = _rootEl.querySelector(`#${prefix}-status-bar`);
        if (subBtnContainer) subBtnContainer.appendChild(subBtn);
        else cardEl.appendChild(subBtn);
        cardEl.appendChild(panel);
    }

    // ⚽ Ballon — visible seulement sur le porteur actuel
    const ballIconEl = _rootEl.querySelector(`#${prefix}-ball-icon`);
    if (ballIconEl) {
        const b = _state?.ball;
        const isCarrier = b && b.team === team && b.number === slotNumber;
        ballIconEl.classList.toggle('hidden', !isCarrier);
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

    _ui.actionBarEl.querySelectorAll(".skill-card-main").forEach((btn) => {
        const a  = btn.dataset.action;
        const el = btn.querySelector(".skill-power");
        if (!el) return;
        if (a === "one_two") {
            // Puissance affichée = moyenne des passes du duo
            const duo         = _roster.getDuoPartnerOnField(ball.team, ball.number);
            const partnerPass = duo ? _roster.getStat(ball.team, duo.slot, "pass") : 0;
            el.textContent = String(Math.round((Number(carrierStats.pass ?? 0) + partnerPass) / 2));
            return;
        }
        const map = { pass: "pass", dribble: "dribble", shot: "shot", special: "attack", cross: "pass", long_pass: "pass" };
        el.textContent = String(Number(carrierStats[map[a]] ?? 0));
    });

    const specialMap = { special: "attack", "special-pass": "pass", "special-dribble": "dribble" };
    _ui.actionBarEl.querySelectorAll(".skill-card-special").forEach((btn) => {
        const el = btn.querySelector(".skill-special-power");
        if (el) el.textContent = String(Number(carrierStats[specialMap[btn.dataset.action]] ?? 0));
    });

    const mode = [..._ui.actionBarEl.classList].find(c => c.startsWith("mode-defense-"));
    if (!mode) return;

    const defenseTeam = mode.includes("external") ? "external" : "internal";
    const isGK        = !!_ui.actionBarEl.querySelector('.def-card-main[data-defense="hands"]');

    if (isGK) {
        const gkStats = _roster.getPlayerInfo(defenseTeam, 1)?.stats ?? {};
        const mapGK   = { hands: "hand_save", punch: "punch_save" };
        _ui.actionBarEl.querySelectorAll(".def-card-main").forEach((btn) => {
            const el = btn.querySelector(".def-power");
            if (el) el.textContent = String(Number(gkStats[mapGK[btn.dataset.defense]] ?? 0));
        });
        _ui.actionBarEl.querySelectorAll(".def-card-special").forEach((btn) => {
            const el = btn.querySelector(".def-special-power");
            if (el) el.textContent = String(Number(gkStats.defense ?? 0));
        });
        return;
    }

    const ctx  = _state.pendingDefenseContext;
    const slot = ctx?.defenderSlot;
    if (!slot) return;

    const dStats = _roster.getPlayerInfo(defenseTeam, slot)?.stats ?? {};
    const mapF   = { block: "block", intercept: "intercept", tackle: "tackle" };
    _ui.actionBarEl.querySelectorAll(".def-card-main").forEach((btn) => {
        const el = btn.querySelector(".def-power");
        if (el) el.textContent = String(Number(dStats[mapF[btn.dataset.defense]] ?? 0));
    });
    _ui.actionBarEl.querySelectorAll(".def-card-special").forEach((btn) => {
        const el = btn.querySelector(".def-special-power");
        if (el) el.textContent = String(Number(dStats.defense ?? 0));
    });
}

// Carte de base (Shot/Pass/Dribble/...) avec, si `special` est fourni, un slot
// compact à droite pour l'action spéciale correspondante (fusion des deux
// cartes en une pour limiter le nombre de cartes affichées).

// Icône par type de base — identifie le genre de spécial en un coup d'œil.
const SPECIAL_ICONS = {
    shot: "🔥", pass: "🎯", dribble: "💨",
    block: "🛡️", tackle: "🛡️", intercept: "🛡️",
    hand_save: "🧤", punch_save: "🧤",
};
const specialIcon = (baseAction) => SPECIAL_ICONS[baseAction] ?? "🔥";

// Sérialise un special move dans un attribut data (guillemets simples : le
// JSON est en double-quotes, seule l'apostrophe éventuelle doit être échappée).
const specialDataAttr = (move) => `data-special='${JSON.stringify(move).replace(/'/g, "&#39;")}'`;
const specialCooldownBadge = (move, cls) => move?.cooldown != null ? `<span class="${cls}">⏳${move.cooldown}</span>` : '';

function buildSkillCard(actionKey, cfg, special = null) {
    const specialHTML = special ? `
        <button class="skill-card-special" data-action="${special.action}" ${specialDataAttr(special.move)}>
            ${specialCooldownBadge(special.move, 'skill-special-cooldown')}
            <div class="skill-special-icon">${specialIcon(special.move.base_action)}</div>
            <div class="skill-special-title">${special.move.short_label || special.move.label || 'Spécial'}</div>
            <div class="skill-special-foot">
                <div class="skill-special-power"></div>
                <div class="skill-special-cost">⚡<span></span></div>
            </div>
        </button>` : '';
    return `<div class="skill-card${special ? ' has-special' : ''}">
        <button class="skill-card-main" data-action="${actionKey}">
            <div class="skill-icon">${cfg.icon}</div>
            <div class="skill-title">${cfg.title}</div>
            <div class="skill-sub">${cfg.sub}</div>
            <div class="skill-bottom">
                <div class="skill-power"></div>
                <div class="skill-cost">Énergie <span></span></div>
            </div>
        </button>${specialHTML}
    </div>`;
}

// Carte autonome pour un spécial dont l'action de base n'est pas proposée ce
// tour-ci (ex : spécial tir hors de la zone de tir) — le spécial doit rester
// jouable même sans carte hôte.
function buildStandaloneSpecialCard(actionKey, move, defaultCfg) {
    return `<div class="skill-card">
        <button class="skill-card-main" data-action="${actionKey}" ${specialDataAttr(move ?? {})}>
            ${specialCooldownBadge(move, 'skill-special-cooldown skill-special-cooldown--main')}
            <div class="skill-icon">${specialIcon(move?.base_action)}</div>
            <div class="skill-title">${move?.label || defaultCfg.title}</div>
            <div class="skill-sub">${move?.description || defaultCfg.sub}</div>
            <div class="skill-bottom">
                <div class="skill-power"></div>
                <div class="skill-cost">Énergie <span></span></div>
            </div>
        </button>
    </div>`;
}

function buildDefCard(defKey, cfg, special = null) {
    const specialHTML = special ? `
        <button class="def-card-special" data-defense="${special.action}" ${specialDataAttr(special.move)}>
            ${specialCooldownBadge(special.move, 'def-special-cooldown')}
            <div class="def-special-icon">${specialIcon(special.move.base_action)}</div>
            <div class="def-special-title">${special.move.short_label || special.move.label || 'Spécial'}</div>
            <div class="def-special-foot">
                <div class="def-special-power"></div>
                <div class="def-special-cost">⚡<span></span></div>
            </div>
        </button>` : '';
    return `<div class="def-card${special ? ' has-special' : ''}">
        <button class="def-card-main" data-defense="${defKey}">
            <div class="def-icon">${cfg.icon}</div>
            <div class="def-title">${cfg.title}</div>
            <div class="def-sub">${cfg.sub}</div>
            <div class="def-bottom">
                <div class="def-power"></div>
                <div class="def-cost">Énergie <span></span></div>
            </div>
        </button>${specialHTML}
    </div>`;
}

export function buildAttackActionsHTML(ball, roster) {
    const cfg      = TEXTS.cards.attack;
    const specials = roster.getSpecialMoves(ball.team, ball.number).filter(m => m?.mode === "attack");
    // Un slot spécial par carte de base concernée (shot/pass/dribble) — premier
    // spécial trouvé pour chaque base_action en cas de doublon improbable.
    const specialByBase = {};
    specials.forEach(m => { const base = m.base_action || "shot"; if (!specialByBase[base]) specialByBase[base] = m; });
    const zone     = Math.min(4, (ball.zoneIndex ?? 0) + 1); // 1=DEF,2=MDF,3=MOF,4=ATT

    // Une-deux : uniquement si le partenaire de duo du porteur est sur le terrain
    const duo = roster.getDuoPartnerOnField(ball.team, ball.number);
    const oneTwoHTML = duo ? buildSkillCard("one_two", {
        ...cfg.one_two,
        sub: duo.label
            ? `${duo.label} — ${duo.partnerInfo?.lastname ?? ""}`.trim()
            : `Avec ${duo.partnerInfo?.lastname ?? "son duo"}`,
    }) : "";

    let forwardCount = 0;
    for (const p of roster.rosters[ball.team].values()) {
        if (p?.isStarter && (p.position || "").toLowerCase().includes("forward")) forwardCount++;
    }

    let extraHTML = "";
    if (zone === 1) {
        extraHTML = buildSkillCard("long_pass", cfg.long_pass);
    } else if (zone === 2) {
        extraHTML = buildSkillCard("cross", cfg.cross);
    } else if (zone === 3) {
        extraHTML = buildSkillCard("cross", cfg.cross);
    } else {
        if (forwardCount >= 2) {
            extraHTML = buildSkillCard("cross", cfg.cross);
        }
    }

    const showShot = zone >= 3;
    // Le spécial tir perd sa carte hôte hors zone de tir (Shot non proposé) :
    // on garde une carte autonome pour qu'il reste toujours jouable.
    const shotOrphanHTML = (!showShot && specialByBase.shot)
        ? buildStandaloneSpecialCard("special", specialByBase.shot, cfg.special)
        : "";

    return `<div id="attack-strip">
        ${showShot ? buildSkillCard("shot", cfg.shot, specialByBase.shot ? { action: "special", move: specialByBase.shot } : null) : ""}
        ${buildSkillCard("pass",    cfg.pass,    specialByBase.pass    ? { action: "special-pass",    move: specialByBase.pass    } : null)}
        ${buildSkillCard("dribble", cfg.dribble, specialByBase.dribble ? { action: "special-dribble", move: specialByBase.dribble } : null)}
        ${oneTwoHTML}
        ${extraHTML}
        ${shotOrphanHTML}
    </div>`;
}

const FIELD_DEFENSE_KEYS = ["block", "intercept", "tackle"];
const GK_DEFENSE_KEYS    = { hand_save: "hands", punch_save: "punch" };

export function buildDefenseFieldHTML(defenderTeam, defenderSlot, roster) {
    const cfg  = TEXTS.cards.defenseField;
    const move = roster.getSpecialMoves(defenderTeam, defenderSlot).filter(m => m?.mode === "defense")[0] ?? null;
    // Le moteur ne résout qu'un seul spécial défensif par joueur (cf. defenseBaseFor) ;
    // on l'accroche à sa carte de base si reconnue, sinon à Tackle par défaut.
    const base = move ? (FIELD_DEFENSE_KEYS.includes(move.base_action) ? move.base_action : "tackle") : null;
    return `<div id="defense-strip">
        ${buildDefCard("block",     cfg.block,     base === "block"     ? { action: "field-special", move } : null)}
        ${buildDefCard("intercept", cfg.intercept, base === "intercept" ? { action: "field-special", move } : null)}
        ${buildDefCard("tackle",    cfg.tackle,    base === "tackle"    ? { action: "field-special", move } : null)}
    </div>`;
}

export function buildDefenseGKHTML(defenderTeam, roster) {
    const cfg  = TEXTS.cards.defenseGK;
    const move = roster.getSpecialMoves(defenderTeam, 1).filter(m => m?.mode === "defense")[0] ?? null;
    const base = move ? (GK_DEFENSE_KEYS[move.base_action] ?? "hands") : null;
    return `<div id="defense-strip">
        ${buildDefCard("hands", cfg.hands, base === "hands" ? { action: "gk-special", move } : null)}
        ${buildDefCard("punch", cfg.punch, base === "punch" ? { action: "gk-special", move } : null)}
    </div>`;
}

export function initUIFromStats() {
    const attackStrip = _rootEl.querySelector("#attack-strip");
    if (attackStrip) {
        attackStrip.querySelectorAll(".skill-card-main").forEach(btn => {
            const cfg   = STATS.attack[btn.dataset.action];
            const costEl= btn.querySelector(".skill-cost span");
            if (cfg && costEl) costEl.textContent = cfg.cost;
        });
        attackStrip.querySelectorAll(".skill-card-special").forEach(btn => {
            const cfg   = STATS.attack[btn.dataset.action];
            const costEl= btn.querySelector(".skill-special-cost span");
            if (cfg && costEl) costEl.textContent = cfg.cost;
        });
    }
    const defenseStrip = _rootEl.querySelector("#defense-strip");
    if (defenseStrip) {
        defenseStrip.querySelectorAll(".def-card-main").forEach(btn => {
            const cfg   = STATS.defenseField[btn.dataset.defense] || STATS.defenseGK[btn.dataset.defense];
            const costEl= btn.querySelector(".def-cost span");
            if (cfg && costEl) costEl.textContent = cfg.cost;
        });
        defenseStrip.querySelectorAll(".def-card-special").forEach(btn => {
            const cfg   = STATS.defenseField[btn.dataset.defense] || STATS.defenseGK[btn.dataset.defense];
            const costEl= btn.querySelector(".def-special-cost span");
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
            _ui.actionBarEl.querySelectorAll("#attack-strip > .skill-card").forEach(card => {
                const btn = card.querySelector(".skill-card-main");
                if (btn?.dataset.action !== "pass") {
                    card.style.display = "none";
                } else {
                    const t = btn.querySelector('.skill-title');
                    const s = btn.querySelector('.skill-sub');
                    if (t) t.textContent = "Coup d'envoi";
                    if (s) s.textContent = "Passe obligatoire";
                    // Le spécial n'est pas jouable sur la remise en jeu obligatoire.
                    card.querySelector('.skill-card-special')?.remove();
                    card.classList.remove('has-special');
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
