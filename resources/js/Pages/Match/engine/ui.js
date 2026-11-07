// resources/js/Pages/Match/engine/ui.js
import { TEXTS, STATS, ACTION_BAR_FADE_MS, GAME_RULES } from './constants.js';
import { getStaminaRatio, getStaminaTier } from './stamina.js';
import { getPlayerId } from './field.js';
import { specialActionStat } from './RosterService.js';
import {
    isDiceAnimating, runAfterDiceAnimation, showBreakdownTooltip, hideBreakdownTooltip, isBreakdownTooltipVisible,
    enqueueRevealStep, revealDwellMs, resetEventQueue,
} from './dice.js';
import { LogEntry } from './ui/logEntry.js';

// Libellés FR des postes (miroir de PlayerPosition::labels()).
const POSITION_FR = { Goalkeeper: 'Gardien', Defender: 'Défenseur', Midfielder: 'Milieu', Forward: 'Attaquant' };

// Cartes d'action : construites dans ./ui/actionCards.js, réexportées ici (API inchangée).
export { buildAttackActionsHTML, buildDefenseFieldHTML, buildDefenseGKHTML } from './ui/actionCards.js';

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

function _hideToastNow() {
    if (!_toastEl) return;
    if (_toastTimer) { clearTimeout(_toastTimer); _toastTimer = null; }
    _toastEl.classList.remove('visible', 'hiding');
}

/**
 * Écrit réellement le toast/bannière — sans garde anti-spoiler, puisque les
 * appels depuis pushLogEntry sont déjà séquencés
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
        // Un toast précédent (« But ! X ») ne doit pas rester affiché par-dessus
        // un événement plus récent (ex. coup franc adverse).
        _hideToastNow();
        const icon = type === 'red' ? '🟥' : type === 'injury' ? '🤕' : '⚽';
        overlay.className = `event-overlay event-overlay--${type}`;
        overlay.innerHTML = `
            <div class="event-overlay__icon text-[52px] leading-none drop-shadow-lg">${icon}</div>
            <div class="text-center text-base font-black tracking-wide text-white [text-shadow:0_2px_8px_rgba(0,0,0,0.5)]">${text}</div>
            ${subText ? `<div class="text-center text-[11px] font-semibold text-white/75">${subText}</div>` : ''}
        `;
        overlay.getBoundingClientRect();
        overlay.classList.add('visible');
        return overlay;
    }
    return null;
}

export function showEventNotification(type, text, subText = null) {
    enqueueRevealStep(done => {
        const el = _renderEventNotification(type, text, subText);
        _awaitDwell(done, revealDwellMs(type), el);
    });
}

/**
 * Attend `dwellMs` avant d'appeler `done()` — sauf clic sur `skipEl` (s'il est
 * fourni), qui saute la pause immédiatement. Utilisé par les étapes de la
 * file pour rythmer leur révélation.
 */
function _awaitDwell(done, dwellMs, skipEl = null) {
    let resolved = false;
    let timer;
    const finish = () => {
        if (resolved) return;
        resolved = true;
        clearTimeout(timer);
        skipEl?.removeEventListener('click', finish);
        if (skipEl && skipEl === _overlayEl) skipEl.classList.remove('visible');
        done();
    };
    if (dwellMs <= 0) { finish(); return; }
    timer = setTimeout(finish, dwellMs);
    skipEl?.addEventListener('click', finish);
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

    } else if (actionType === 'free-kick') {
        if (_ui?.currentActionDetailEl) _ui.currentActionDetailEl.textContent = firstDetail;
        return _renderEventNotification('freekick', 'Coup franc !', firstDetail);

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
    const minutes = Math.round(state.turns / (state.maxTurns || GAME_RULES.MAX_TURNS) * 90);
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
    setText(`#${prefix}-role`,   POSITION_FR[info?.position] ?? info?.position ?? "—");
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
    setText(`#${prefix}-energy-value`, `${Math.max(0, Math.round(ratio * 100))} %`);

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
            b.className = 'status-badge whitespace-nowrap rounded px-1 text-3xs font-black bg-rose-500 py-px text-white';
            b.textContent = totalYellows >= 2 ? '🚫' : '🤕';
            b.title = totalYellows >= 2 ? 'Expulsé' : 'Blessé / Indisponible';
            statusBar.insertBefore(b, statusBar.firstChild);
        }

        // 🟨 Cartons jaunes
        if (totalYellows > 0) {
            const b = document.createElement('div');
            b.className = 'status-badge whitespace-nowrap rounded px-1 text-3xs font-black bg-yellow-500 py-px text-stone-900';
            b.textContent = `${totalYellows}🟨`;
            b.title = `${totalYellows} carton(s) jaune`;
            statusBar.insertBefore(b, statusBar.firstChild);
        }

        // 👑 Capitaine
        if (info?.isCaptain) {
            const rerolls = _state?.captainReroll?.[team]?.rerollsRemaining ?? 0;
            const b = document.createElement('div');
            b.className = 'status-badge whitespace-nowrap rounded px-1 text-3xs font-black cursor-default bg-amber-500 px-[5px] py-px text-white';
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

        // Remplaçants du même poste d'abord (gardien pour un gardien…), puis
        // les plus frais : l'endurance du sortant est rappelée dans l'en-tête.
        const outInfo = _roster.getPlayerInfo(team, slotNumber);
        const outPos  = outInfo?.position ?? '';
        const staminaPct = (s) => {
            const pid = getPlayerId(team, s);
            const max = _state.staminaMax[pid] ?? 100;
            const cur = _state.stamina[pid] ?? max;
            return max > 0 ? Math.round(cur / max * 100) : 100;
        };
        const fitRank = (info) => info.position === outPos ? 0 : (info.secondaryPositions ?? []).includes(outPos) ? 1 : 2;
        subs.sort((a, b) => fitRank(a.info) - fitRank(b.info) || staminaPct(b.slot) - staminaPct(a.slot));

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
        header.className = 'bg-sky-500 px-2.5 py-1.5 text-2xs font-bold text-white';
        header.textContent = `🔄 Remplacer ${outInfo?.lastname ?? ''} (${staminaPct(slotNumber)} %⚡)`;
        panel.appendChild(header);

        // Liste défilable : si beaucoup de remplaçants, on peut scroller
        // au lieu de voir la liste déborder hors de l'écran.
        const listEl = document.createElement('div');
        listEl.className = 'max-h-[min(220px,40vh)] overflow-y-auto overscroll-contain';
        panel.appendChild(listEl);

        subs.forEach(({ slot, info: subInfo }) => {
            const pct    = staminaPct(slot);
            const color  = pct >= 75 ? 'text-emerald-500' : pct >= 50 ? 'text-amber-500' : 'text-rose-500';
            const rank   = fitRank(subInfo);
            const fitTag = rank === 0 ? '<span class="text-emerald-600">même poste</span>'
                : rank === 1 ? '<span class="text-amber-600">poste secondaire</span>'
                : '<span class="text-rose-500">hors poste</span>';

            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'flex w-full cursor-pointer items-center justify-between border-0 border-b border-slate-100 bg-transparent px-2.5 py-1.5 text-left text-[11px] hover:bg-sky-50';
            item.innerHTML = `
            <div>
                <div class="font-bold">${subInfo.lastname}</div>
                <div class="text-3xs text-slate-400">${POSITION_FR[subInfo.position] ?? subInfo.position ?? '—'} · ${fitTag}</div>
            </div>
            <span class="${color} text-2xs font-bold">${pct}%⚡</span>`;
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
        const map = { pass: "pass", dribble: "dribble", shot: "shot", special: "shot", cross: "pass", long_pass: "pass" };
        el.textContent = String(Number(carrierStats[map[a]] ?? 0));
    });

    const specialMap = { special: "shot", "special-pass": "pass", "special-dribble": "dribble" };
    _ui.actionBarEl.querySelectorAll(".skill-card-special").forEach((btn) => {
        const el = btn.querySelector(".skill-special-power");
        if (el) el.textContent = String(Number(carrierStats[specialMap[btn.dataset.action]] ?? 0));
    });

    // Cartes d'attaque : stat de contre du défenseur annoncé (fiche adverse), et
    // arrêt du gardien pour un tir face à lui. Centre et passe longue opposent un
    // autre joueur (duel aérien, relais) : rien d'affiché.
    const ATTACK_VS = { pass: ["intercept", "Interc."], one_two: ["intercept", "Interc."], dribble: ["tackle", "Tacle"], shot: ["block", "Contre"] };
    const attackDefTeam = ball.team === "internal" ? "external" : "internal";
    const previewSlot   = _state?.defensePreview?.picked?.defenderSlot ?? null;
    _ui.actionBarEl.querySelectorAll(".skill-card-main").forEach((btn) => {
        const el = btn.querySelector(".skill-vs");
        if (!el) return;
        const a  = btn.dataset.action;
        let vs   = ATTACK_VS[a];
        let slot = previewSlot;
        if (a === "shot" && ball.frontOfKeeper) { vs = ["hand_save", "Arrêt du gardien"]; slot = 1; }
        if (!vs || !slot || _state?.isKickoff) { el.textContent = ""; return; }   // coup d'envoi : passe sans duel
        const value = Number(_roster.getPlayerInfo(attackDefTeam, slot)?.stats?.[vs[0]] ?? 0);
        el.innerHTML = `vs ${vs[1]} <b>${value}</b>`;
        el.title     = `Face à : ${vs[1]} de l'adversaire (${value})`;
    });

    const mode = [..._ui.actionBarEl.classList].find(c => c.startsWith("mode-defense-"));
    if (!mode) return;

    const defenseTeam = mode.includes("external") ? "external" : "internal";
    const isGK        = !!_ui.actionBarEl.querySelector('.def-card-main[data-defense="hands"]');

    // Stat du porteur adverse que chaque défense affronte (ex. Interception
    // face à sa Passe), pour comparer avant de choisir.
    const VS_STAT = { block: ["shot", "Tir"], intercept: ["pass", "Passe"], tackle: ["dribble", "Dribble"], hands: ["shot", "Tir"], punch: ["shot", "Tir"] };
    _ui.actionBarEl.querySelectorAll(".def-card-main").forEach((btn) => {
        const vs = VS_STAT[btn.dataset.defense];
        const el = btn.querySelector(".def-vs");
        if (el && vs) {
            const value = Number(carrierStats[vs[0]] ?? 0);
            el.innerHTML = `vs ${vs[1]} <b>${value}</b>`;
            el.title     = `Face à : ${vs[1]} de l'attaquant (${value})`;
        }
    });

    if (isGK) {
        const gkStats = _roster.getPlayerInfo(defenseTeam, 1)?.stats ?? {};
        const mapGK   = { hands: "hand_save", punch: "punch_save" };
        _ui.actionBarEl.querySelectorAll(".def-card-main").forEach((btn) => {
            const el = btn.querySelector(".def-power");
            if (el) el.textContent = String(Number(gkStats[mapGK[btn.dataset.defense]] ?? 0));
        });
        _ui.actionBarEl.querySelectorAll(".def-card-special").forEach((btn) => {
            const el = btn.querySelector(".def-special-power");
            const move = _roster.getSpecialMove(defenseTeam, 1, "defense");
            if (el && move) el.textContent = String(specialActionStat(move, defenseTeam, 1, _roster));
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
        const move = _roster.getSpecialMove(defenseTeam, slot, "defense");
        if (el && move) el.textContent = String(specialActionStat(move, defenseTeam, slot, _roster));
    });
}

// Carte de base (Shot/Pass/Dribble/...) avec, si `special` est fourni, un slot
// compact à droite pour l'action spéciale correspondante (fusion des deux
// cartes en une pour limiter le nombre de cartes affichées).

// Icône par type de base — identifie le genre de spécial en un coup d'œil.
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

        // Face au gardien : tir, techniques spéciales, ou passe en retrait. Les autres
        // cartes (ignorées par handleAttackClick) sont retirées ; la partie « base »
        // d'une carte qui porte un spécial est désactivée.
        if (!isKickoff && ball?.frontOfKeeper && html.includes("attack-strip")) {
            _ui.actionBarEl.querySelectorAll("#attack-strip > .skill-card").forEach(card => {
                const btn = card.querySelector(".skill-card-main");
                const action = btn?.dataset.action ?? "";
                if (!btn || action === "shot" || action.startsWith("special")) return;
                if (action === "pass") {
                    const t = btn.querySelector('.skill-title');
                    const s = btn.querySelector('.skill-sub');
                    if (t) t.textContent = "Passe en retrait";
                    if (s) s.textContent = "Remettre à un partenaire";
                    return;
                }
                if (card.querySelector(".skill-card-special")) {
                    btn.disabled = true;
                    btn.classList.add("opacity-40", "cursor-not-allowed");
                    btn.title = "Face au gardien : tir ou technique spéciale uniquement";
                } else {
                    card.style.display = "none";
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

// -----------------------------------------------------------
//   Stats utiles au duel en cours (fiches latérales)
// -----------------------------------------------------------
// Stat de l'attaquant selon son geste, et stat du « bon contre » côté défenseur.
const DUEL_ATTACK_STAT  = { pass: 'pass', one_two: 'pass', cross: 'pass', long_pass: 'pass', 'special-pass': 'pass',
                            dribble: 'dribble', 'special-dribble': 'dribble', shot: 'shot', special: 'shot' };
const DUEL_COUNTER_STAT = { pass: 'intercept', dribble: 'tackle', shot: 'block' };

/** Surligne la stat de l'attaquant et celle du contre adapté ; sans argument, efface. */
export function highlightDuelStats(attackTeam = null, attackAction = null, keeper = false) {
    _rootEl?.querySelectorAll('.duel-stat').forEach(el => el.classList.remove('duel-stat'));
    if (!attackTeam || !attackAction) return;

    const attackStat = DUEL_ATTACK_STAT[attackAction];
    if (!attackStat) return;
    const attackPrefix  = attackTeam === 'internal' ? 'home' : 'away';
    const defensePrefix = attackPrefix === 'home' ? 'away' : 'home';
    const counters = keeper ? ['hand_save', 'punch_save'] : [DUEL_COUNTER_STAT[attackStat]];

    const mark = (prefix, key) => _rootEl?.querySelector(`#${prefix}-stat-${key}`)?.parentElement?.classList.add('duel-stat');
    mark(attackPrefix, attackStat);
    counters.forEach(k => mark(defensePrefix, k));
}
