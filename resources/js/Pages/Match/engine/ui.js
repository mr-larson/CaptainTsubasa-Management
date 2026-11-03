// resources/js/Pages/Match/engine/ui.js
import { TEXTS, STATS, ACTION_BAR_FADE_MS } from './constants.js';
import { getStaminaRatio, getStaminaTier } from './stamina.js';
import { getPlayerId } from './field.js';
import {
    isDiceAnimating, runAfterDiceAnimation, showBreakdownTooltip, hideBreakdownTooltip, isBreakdownTooltipVisible,
    enqueueRevealStep, revealDwellMs, resetEventQueue,
} from './dice.js';

let _rootEl = null;
let _roster = null;
let _ui     = null;
let _state  = null;
let _TEAMS  = null;

// Listeners posés sur `document` : retirés d'un coup au démontage du match
// (navigation Inertia sans rechargement) pour ne pas s'accumuler.
let _docAbort = null;

export function initUIModule(rootEl, roster, ui, state, TEAMS) {
    disposeUIModule();
    _docAbort = new AbortController();

    _rootEl = rootEl;
    _roster = roster;
    _ui     = ui;
    _state  = state;
    _TEAMS  = TEAMS;

    bindHistoryClickEvents();
}

/** Nettoyage de fin de match : listeners document, toast et overlay du terrain précédent. */
export function disposeUIModule() {
    _docAbort?.abort();
    _docAbort = null;
    if (_toastTimer) { clearTimeout(_toastTimer); _toastTimer = null; }
    _toastEl   = null;
    _overlayEl = null;
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
        // Panneaux de remplacement ouverts : refermés au clic hors du panneau.
        if (!event.target.closest('[id$="-sub-panel"]')) {
            _rootEl?.querySelectorAll('[id$="-sub-panel"]').forEach(p => p.classList.add('hidden'));
        }
        if (event.target.closest('.log-entry') || event.target.closest('#duel-dice-tooltip')) return;
        hideBreakdownTooltip();
        _openLogId = null;
    }, { signal: _docAbort?.signal });
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

/**
 * Écrit réellement le toast/bannière — sans garde anti-spoiler, puisque les
 * appelants internes (pushLogEntry, showFreeKickBanner) sont déjà séquencés
 * par la file de révélation (enqueueRevealStep). Renvoie l'élément affiché
 * (ou null), pour permettre un clic dessus qui saute la pause d'affichage.
 */
function _renderEventNotification(type, text, subText = null) {
    if (type === 'goal' || type === 'foul' || type === 'yellow') {
        const toast = _ensureToastEl();
        if (!toast) return null;
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
        return toast;

    } else if (type === 'red' || type === 'injury' || type === 'freekick') {
        const overlay = _ensureOverlayEl();
        if (!overlay) return null;
        const icon = type === 'red' ? '🟥' : type === 'injury' ? '🤕' : '⚽';
        overlay.className = `event-overlay event-overlay--${type}`;
        overlay.innerHTML = `
            <div class="event-overlay__icon text-[52px] leading-none drop-shadow-lg">${icon}</div>
            <div class="text-center text-base font-black tracking-wide text-white [text-shadow:0_2px_8px_rgba(0,0,0,0.5)]">${text}</div>
            ${subText ? `<div class="text-center text-[11px] font-semibold text-white/75">${subText}</div>` : ''}
        `;
        overlay.getBoundingClientRect();
        overlay.classList.add('visible');
        setTimeout(() => overlay.classList.remove('visible'), 2200);
        return overlay;
    }
    return null;
}

/**
 * API publique défensive — conservée pour un éventuel appelant hors file.
 * Les chemins internes (pushLogEntry, showFreeKickBanner) appellent
 * directement _renderEventNotification, déjà séquencés par la file.
 */
export function showEventNotification(type, text, subText = null) {
    if (isDiceAnimating()) {
        runAfterDiceAnimation(() => showEventNotification(type, text, subText));
        return;
    }
    _renderEventNotification(type, text, subText);
}

/**
 * Attend `dwellMs` avant d'appeler `done()` — sauf clic sur `skipEl` (s'il est
 * fourni), qui saute la pause immédiatement. Utilisé par les étapes de la
 * file (pushLogEntry, showFreeKickBanner) pour rythmer leur révélation.
 */
function _awaitDwell(done, dwellMs, skipEl = null) {
    if (dwellMs <= 0) { done(); return; }
    let resolved = false;
    const finish = () => {
        if (resolved) return;
        resolved = true;
        done();
    };
    const timer = setTimeout(finish, dwellMs);
    if (skipEl) {
        skipEl.addEventListener('click', () => { clearTimeout(timer); finish(); }, { once: true });
    }
}

// Bannière plein terrain « ⚽ Coup franc ! » — appelée par les résolveurs.
export function showFreeKickBanner(teamLabel, takerNumber) {
    enqueueRevealStep(done => {
        const el = _renderEventNotification('freekick', 'Coup franc !', `${teamLabel} — n°${takerNumber}`);
        _awaitDwell(done, revealDwellMs('freekick'), el);
    });
}

/** Renvoie l'élément toast/overlay affiché (ou null si le type n'en a pas). */
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
        return _renderEventNotification('goal', `${icon} But ! ${scorer}`);

    } else if (actionType === 'card-red') {
        const name = extractName(firstDetail);
        _flashCard('red');
        return _renderEventNotification('red', main, name || null);

    } else if (actionType === 'injury') {
        const name = extractName(firstDetail);
        return _renderEventNotification('injury', main, name || null);

    } else if (actionType === 'card-yellow') {
        const name = extractName(firstDetail);
        _flashCard('yellow');
        return _renderEventNotification('yellow', `🟨 ${name}`);

    } else if (actionType === 'foul') {
        const name = extractName(firstDetail);
        _flashCard('yellow');
        return _renderEventNotification('foul', `⚠️ Faute — ${name}`);
    }
    return null;
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

    // Durée de match réglable (10-80 tours) : la minute affichée reste sur 90'.
    const minutes = Math.round(state.turns / (state.maxTurns || 45) * 90);
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
        const pad     = n => String(n).padStart(2, '0');
        const isGold  = this._cfg.color === 'gold';
        const badgeBg = isGold ? 'from-amber-600 to-amber-700' : 'from-slate-500 to-slate-600';
        const badge   = `<span class="log-turn-badge inline-flex h-[18px] w-7 shrink-0 items-center justify-center rounded-[5px] bg-gradient-to-br ${badgeBg} text-[9px] font-extrabold tracking-[0.3px] text-white shadow">T${pad(this.turn)}</span>`;
        const dotBg   = this.team === 'internal' ? 'bg-blue-500 shadow-[0_0_4px_rgba(59,130,246,0.6)]' : 'bg-orange-500 shadow-[0_0_4px_rgba(249,115,22,0.6)]';
        const dot     = this.team
            ? `<span class="h-[5px] w-[5px] shrink-0 rounded-full ${dotBg}"></span>`
            : '';
        const dice    = this.diceTag
            ? `<span class="whitespace-nowrap rounded bg-slate-400/20 px-1 font-mono text-[9px] text-slate-600">${this.diceTag}</span>`
            : '';
        const detail  = this.details.length
            ? `<div class="truncate text-[10px] text-slate-500">${this.details.join(' · ')}</div>`
            : '';
        const clickable = this.breakdown ? ' log-entry--clickable group' : '';
        const infoIcon  = this.breakdown
            ? '<span class="flex h-3.5 w-3.5 shrink-0 items-center justify-center self-center rounded-full bg-slate-400/15 text-[9px] not-italic text-slate-400 transition-colors group-hover:bg-blue-500/20 group-hover:text-blue-600" title="Voir le détail">ℹ</span>'
            : '';

        return `<li class="log-entry log-result-${this.result} log-${this._cfg.color}${clickable}" data-log-id="${this.id}">
            <div class="flex shrink-0 flex-col items-center gap-[3px]">${badge}${dot}</div>
            <div class="flex min-w-0 flex-1 items-start gap-1.5">
                <span class="log-icon mt-px shrink-0 text-[13px] leading-none">${this._cfg.icon}</span>
                <div class="flex min-w-0 flex-1 flex-col gap-px">
                    <div class="flex flex-wrap items-baseline gap-1 font-semibold text-slate-900"><span>${this.mainText}</span>${dice}</div>
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
    resetEventQueue();
}

function _pushLog(entry) {
    logHistory.push(entry);
    if (entry.breakdown) _logBreakdowns.set(entry.id, entry.breakdown);
    if (_ui?.historyListEl) {
        // Ajout en tête plutôt que reconstruire tout l'historique à chaque entrée.
        _ui.historyListEl.insertAdjacentHTML('afterbegin', entry.toHTML());
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

    const turns = state?.turns ?? 0;
    const team  = state?.currentTeam ?? null;

    const [actionType, result] = _detectType(logKeyOrText, d);

    const entry = new LogEntry({ turn: turns, actionType, team, result, mainText: main, details: d, diceTag, breakdown: meta ?? null });

    // Historique COMPLET du déroulé du match, exploitable pour le résumé
    // post-match (action par action) côté Tab Calendar. Bookkeeping immédiat,
    // indépendant du rythme d'affichage ci-dessous.
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

    // Révélation à l'écran (titre courant, historique, toast) : mise en file
    // pour rythmer la cascade faute → carton → coup franc → tir → arrêt au
    // lieu de l'afficher d'un bloc.
    enqueueRevealStep(done => {
        if (_ui?.currentActionTitleEl)  _ui.currentActionTitleEl.textContent  = main || '–';
        if (_ui?.currentActionDetailEl) _ui.currentActionDetailEl.textContent = '';
        _pushLog(entry);
        const el = _triggerEventNotification(actionType, main, d);
        _awaitDwell(done, revealDwellMs(actionType), el);
    });
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
            b.className = 'status-badge whitespace-nowrap rounded px-1 text-[9px] font-black bg-red-500 py-px text-white';
            b.textContent = totalYellows >= 2 ? '🚫' : '🤕';
            b.title = totalYellows >= 2 ? 'Expulsé' : 'Blessé / Indisponible';
            statusBar.insertBefore(b, statusBar.firstChild);
        }

        // 🟨 Cartons jaunes
        if (totalYellows > 0) {
            const b = document.createElement('div');
            b.className = 'status-badge whitespace-nowrap rounded px-1 text-[9px] font-black bg-yellow-500 py-px text-stone-900';
            b.textContent = `${totalYellows}🟨`;
            b.title = `${totalYellows} carton(s) jaune`;
            statusBar.insertBefore(b, statusBar.firstChild);
        }

        // 👑 Capitaine
        if (info?.isCaptain) {
            const rerolls = _state?.captainReroll?.[team]?.rerollsRemaining ?? 0;
            const b = document.createElement('div');
            b.className = 'status-badge whitespace-nowrap rounded px-1 text-[9px] font-black cursor-default bg-amber-500 px-[5px] py-px text-white';
            b.setAttribute('data-captain-reroll', team);
            b.textContent = `👑 ${rerolls}`;
            b.title = `Capitaine — ${rerolls} relance(s) restante(s)`;
            statusBar.insertBefore(b, statusBar.firstChild);
        }
    }

    // Bouton remplacement
    _rootEl.querySelector(`#${prefix}-sub-btn`)?.remove();
    _rootEl.querySelector(`#${prefix}-sub-panel`)?.remove();

    // Mode courant (bouton « Mode 1 / 2 joueurs ») plutôt que la config figée
    // au chargement : en mode 2 joueurs, les deux camps peuvent remplacer.
    const isControlledTeam = typeof _state?._isAITeam === 'function'
        ? !_state._isAITeam(team)
        : (_state?._matchConfig?.controlledSide === team || _state?._matchConfig?.controlMode === 'both');
    const canSub = (
        isControlledTeam &&
        !_state?.isGameOver &&
        (_state?.substitutionCount?.[team] ?? 0) < (_state?.MAX_SUBSTITUTIONS ?? 3) &&
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
        subBtn.title = `Remplacer (${(_state.MAX_SUBSTITUTIONS ?? 3) - (_state.substitutionCount?.[team] ?? 0)} restant(s))`;
        subBtn.textContent = '🔄';
        subBtn.className = 'cursor-pointer border-0 bg-transparent p-0 text-sm leading-none opacity-[0.85] transition-opacity hover:opacity-100';
        subBtn.setAttribute('aria-label', 'Remplacer ce joueur');

        const panel = document.createElement('div');
        panel.id = `${prefix}-sub-panel`;
        panel.className = 'sub-panel hidden absolute right-1 top-8 z-[80] min-w-[180px] overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-lg';

        const header = document.createElement('div');
        header.className = 'bg-sky-500 px-2.5 py-1.5 text-[10px] font-bold text-white';
        header.textContent = `🔄 Choisir remplaçant`;
        panel.appendChild(header);

        // Liste défilable : si beaucoup de remplaçants, on peut scroller
        // au lieu de voir la liste déborder hors de l'écran.
        const listEl = document.createElement('div');
        listEl.className = 'max-h-[min(220px,40vh)] overflow-y-auto overscroll-contain';
        panel.appendChild(listEl);

        subs.forEach(({ slot, info: subInfo }) => {
            const subPid = getPlayerId(team, slot);
            const stMax  = _state.staminaMax[subPid] ?? 100;
            const stCur  = _state.stamina[subPid]    ?? stMax;
            const pct    = stMax > 0 ? Math.round(stCur / stMax * 100) : 100;
            const color  = pct >= 75 ? 'text-green-500' : pct >= 50 ? 'text-amber-500' : 'text-red-500';

            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'flex w-full cursor-pointer items-center justify-between border-0 border-b border-slate-100 bg-transparent px-2.5 py-1.5 text-left text-[11px] hover:bg-sky-50';
            item.innerHTML = `
            <div>
                <div class="font-bold">${subInfo.lastname}</div>
                <div class="text-[9px] text-slate-400">${subInfo.position ?? '—'}</div>
            </div>
            <span class="${color} text-[10px] font-bold">${pct}%⚡</span>`;
            item.onclick = () => {
                if (_state._performSubstitution) {
                    const ok = _state._performSubstitution(team, slotNumber, slot);
                    if (ok) { subBtn.remove(); panel.remove(); }
                }
            };
            listEl.appendChild(item);
        });

        // Fermeture au clic ailleurs : un seul listener délégué (bindHistoryClickEvents),
        // au lieu d'un listener `document` ajouté à chaque rafraîchissement de carte.
        subBtn.onclick = (e) => {
            e.stopPropagation();
            panel.classList.toggle('hidden');
        };

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
// Typographie des cartes d'action et de défense (classes Tailwind). Les noms
// sémantiques (skill-power, def-cost…) restent : le JS s'en sert comme repères.
const CARD = {
    icon:       'text-lg leading-none',
    title:      'text-[13px] font-extrabold tracking-[0.2px] text-slate-900',
    sub:        'text-[11px] text-slate-900/70',
    bottom:     'mt-1.5 flex flex-col items-start gap-0.5',
    power:      'text-lg font-black tabular-nums text-slate-900',
    cost:       'whitespace-nowrap text-[11px] font-bold text-slate-900/75 [&>span]:font-black [&>span]:text-slate-900/90',
    spIcon:     'text-[15px] leading-none',
    spTitle:    'line-clamp-2 text-[10px] font-extrabold leading-tight text-amber-800',
    spFoot:     'flex flex-col gap-px',
    spPower:    'text-[13px] font-black tabular-nums text-amber-800',
    spCost:     'whitespace-nowrap text-[9px] font-bold text-amber-800/85 [&>span]:font-black',
    cooldown:   'pointer-events-none absolute right-1 top-1 rounded-full bg-white/75 px-1 text-[8px] font-extrabold leading-tight text-amber-800/85',
    cooldownMain: 'pointer-events-none absolute right-2 top-2 rounded-full bg-white/75 px-1 text-[9px] font-extrabold leading-tight text-amber-800/85',
};

const specialDataAttr = (move) => `data-special='${JSON.stringify(move).replace(/'/g, "&#39;")}'`;
const specialCooldownBadge = (move, cls) => move?.cooldown != null ? `<span class="${cls}">⏳${move.cooldown}</span>` : '';

function buildSkillCard(actionKey, cfg, special = null) {
    return buildActionCard('skill', 'data-action', actionKey, cfg, special);
}

// Carte autonome pour un spécial dont l'action de base n'est pas proposée ce
// tour-ci (ex : spécial tir hors de la zone de tir) — le spécial doit rester
// jouable même sans carte hôte.
function buildStandaloneSpecialCard(actionKey, move, defaultCfg) {
    return `<div class="skill-card">
        <button class="skill-card-main" data-action="${actionKey}" ${specialDataAttr(move ?? {})}>
            ${specialCooldownBadge(move, CARD.cooldownMain)}
            <div class="skill-icon ${CARD.icon}">${specialIcon(move?.base_action)}</div>
            <div class="skill-title ${CARD.title}">${move?.label || defaultCfg.title}</div>
            <div class="skill-sub ${CARD.sub}">${move?.description || defaultCfg.sub}</div>
            <div class="skill-bottom ${CARD.bottom}">
                <div class="skill-power ${CARD.power}"></div>
                <div class="skill-cost ${CARD.cost}">Énergie <span></span></div>
            </div>
        </button>
    </div>`;
}

function buildDefCard(defKey, cfg, special = null) {
    return buildActionCard('def', 'data-defense', defKey, cfg, special);
}

// Carte commune attaque (`skill`) / défense (`def`) : action principale, plus
// un slot spécial accolé à droite quand le joueur a une technique de ce type.
function buildActionCard(kind, dataAttr, key, cfg, special) {
    const specialHTML = special ? `
        <button class="${kind}-card-special" ${dataAttr}="${special.action}" ${specialDataAttr(special.move)}>
            ${specialCooldownBadge(special.move, CARD.cooldown)}
            <div class="${kind}-special-icon ${CARD.spIcon}">${specialIcon(special.move.base_action)}</div>
            <div class="${kind}-special-title ${CARD.spTitle}">${special.move.short_label || special.move.label || 'Spécial'}</div>
            <div class="${kind}-special-foot ${CARD.spFoot}">
                <div class="${kind}-special-power ${CARD.spPower}"></div>
                <div class="${kind}-special-cost ${CARD.spCost}">⚡<span></span></div>
            </div>
        </button>` : '';
    return `<div class="${kind}-card${special ? ' has-special' : ''}">
        <button class="${kind}-card-main" ${dataAttr}="${key}">
            <div class="${kind}-icon ${CARD.icon}">${cfg.icon}</div>
            <div class="${kind}-title ${CARD.title}">${cfg.title}</div>
            <div class="${kind}-sub ${CARD.sub}">${cfg.sub}</div>
            <div class="${kind}-bottom ${CARD.bottom}">
                <div class="${kind}-power ${CARD.power}"></div>
                <div class="${kind}-cost ${CARD.cost}">Énergie <span></span></div>
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
