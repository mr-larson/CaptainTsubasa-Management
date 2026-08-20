// resources/js/Pages/Match/engine/dice.js
import { DIE_SIDES, DUEL_RULES } from './constants.js';

// -----------------------------------------------------------
//   Jets de dés
// -----------------------------------------------------------
export function rollDie() {
    return 1 + Math.floor(Math.random() * DIE_SIDES);
}

export function rollD20WithCrit() {
    const roll = rollDie();
    return {
        roll,
        bonus:       roll / 2,
        critSuccess: roll === 20,
        critFail:    roll === 1,
        isAdvantage: false,
    };
}

/**
 * Lancer 2d20 et prendre le meilleur (Advantage — D&D style).
 * Utilisé lors de la relance capitaine.
 */
export function rollD20Advantage() {
    const roll1 = rollDie();
    const roll2 = rollDie();
    const roll  = Math.max(roll1, roll2);
    return {
        roll,
        roll1,
        roll2,
        bonus:       roll / 2,
        critSuccess: roll === 20,
        critFail:    roll === 1,
        isAdvantage: true,
    };
}

export function resolveCritOutcome(attackRoll, defenseRoll) {
    if (attackRoll.critSuccess && !defenseRoll.critSuccess) return "attack";
    if (defenseRoll.critSuccess && !attackRoll.critSuccess) return "defense";
    if (attackRoll.critFail    && !defenseRoll.critFail)    return "defense";
    if (defenseRoll.critFail   && !attackRoll.critFail)     return "attack";
    return null;
}

// -----------------------------------------------------------
//   Duel meta (contexte joueurs pour tooltip)
// -----------------------------------------------------------
export function buildDuelMeta({ attackTeam, attackSlot, attackAction, defenseTeam, defenseSlot, defenseAction }, roster, TEXTS) {
    const aInfo = roster.getPlayerInfo(attackTeam, attackSlot);
    const dInfo = defenseSlot ? roster.getPlayerInfo(defenseTeam, defenseSlot) : null;

    const fullName = (info) => {
        if (!info) return "";
        return (String(info.firstname || "").trim() + " " + String(info.lastname || "").trim()).trim();
    };

    const getAttackLabel  = (key) => TEXTS.cards?.attack?.[key]?.title       || key;
    const getDefenseLabel = (key) => TEXTS.cards?.defenseField?.[key]?.title  || TEXTS.cards?.defenseGK?.[key]?.title || key;

    return {
        attacker: {
            team: attackTeam, slot: attackSlot,
            id: aInfo?.id ?? null,
            number: aInfo?.number ?? null, name: fullName(aInfo),
            actionKey: attackAction, actionLabel: getAttackLabel(attackAction),
        },
        defender: defenseSlot ? {
            team: defenseTeam, slot: defenseSlot,
            id: dInfo?.id ?? null,
            number: dInfo?.number ?? null, name: fullName(dInfo),
            actionKey: defenseAction, actionLabel: getDefenseLabel(defenseAction),
        } : null,
    };
}

// -----------------------------------------------------------
//   Breakdown duel champ
// -----------------------------------------------------------
export function buildFieldDuelBreakdown({
                                            attackBaseRaw, defenseBaseRaw,
                                            attackStamF, defenseStamF,
                                            aRoll, dRoll, isGood,
                                            attackScore, defenseScore,
                                            clearanceBonus = 0, meta = null,
                                            captainReroll = false,
                                            heroic = false,
                                            homeBonus = 0,
                                            homeSide = null,       // 'attack' | 'defense' | null
                                            critWinner = null,
                                        }) {
    const goodBonus = DUEL_RULES.GOOD_COUNTER_BONUS   ?? 0;
    const genBonus  = DUEL_RULES.GENERIC_ATTACK_BONUS ?? 0;
    const diff      = attackScore - defenseScore;

    // Tag dé — affiche les deux dés si advantage (2d20)
    const aTag = aRoll.isAdvantage
        ? `2d20(${aRoll.roll1},${aRoll.roll2})→${aRoll.roll}${aRoll.critSuccess ? '!' : ''}`
        : (aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll)));
    const dTag = dRoll.isAdvantage
        ? `2d20(${dRoll.roll1},${dRoll.roll2})→${dRoll.roll}${dRoll.critSuccess ? '!' : ''}`
        : (dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll)));

    return {
        meta,
        captainReroll,
        rolls: { aTag, dTag, aBonus: aRoll.bonus, dBonus: dRoll.bonus },
        attack: {
            base: attackBaseRaw,
            staminaFactor: attackStamF,
            additions: [
                { label: "🎲 Bonus dé", value: `+ ${aRoll.bonus.toFixed(1)}` },
                ...(!isGood ? [{ label: "⚔️ Bonus attaque", value: `+ ${genBonus.toFixed(1)}` }] : []),
                ...(clearanceBonus ? [{ label: "💨 Dégagement", value: `+ ${Number(clearanceBonus).toFixed(1)}` }] : []),
                ...(captainReroll  ? [{ label: "👑 Reroll capitaine", value: "2d20 avantage" }] : []),
                ...(heroic         ? [{ label: "🔥 Dépassement de soi", value: "2d20 avantage" }] : []),
                ...(homeSide === 'attack' && homeBonus ? [{ label: "🏠 Avantage domicile", value: `+ ${Number(homeBonus).toFixed(1)}` }] : []),
            ],
            total: attackScore,
        },
        defense: {
            base: defenseBaseRaw,
            staminaFactor: defenseStamF,
            additions: [
                { label: "🎲 Bonus dé", value: `+ ${dRoll.bonus.toFixed(1)}` },
                ...(isGood
                        ? [{ label: "✅ Bon contre", value: `+ ${goodBonus.toFixed(1)}` }]
                        : [{ label: "❌ Mauvais contre", value: "—" }]
                ),
                ...(homeSide === 'defense' && homeBonus ? [{ label: "🏠 Avantage domicile", value: `+ ${Number(homeBonus).toFixed(1)}` }] : []),
            ],
            total: defenseScore,
        },
        result: {
            bonusRuleLabel: isGood
                ? `Bon contre (+${goodBonus} défense)`
                : `Mauvais contre (+${genBonus} attaque)`,
            critWinner,
            diff,
            winner: critWinner ?? (diff > 0 ? "attack" : diff < 0 ? "defense" : "tie"),
        },
    };
}

// -----------------------------------------------------------
//   Tooltip DOM
// -----------------------------------------------------------
let _duelTooltipEl  = null;
let _duelDiceEl     = null;
let _lastBreakdown  = null;

export function initDiceUI(duelDiceEl) {
    _duelDiceEl = duelDiceEl;
    initDiceAnimToggle();
}

// -----------------------------------------------------------
//   Animation de lancer de dés (overlay sur le terrain)
// -----------------------------------------------------------
const DICE_ANIM_STORAGE_KEY = 'ctm-dice-anim';
const DICE_ANIM_MODES       = ['normal', 'fast', 'off'];
const DICE_ANIM_LABELS      = { normal: '🎲 normale', fast: '🎲 rapide', off: '🎲 off' };

let _animState    = null;   // animation en cours { abort() }
let _afterAnimCbs = [];     // notifications différées pendant l'animation
let _chipToken    = 0;      // seul le dernier duel a le droit d'écrire le chip

export function getDiceAnimMode() {
    try {
        const stored = localStorage.getItem(DICE_ANIM_STORAGE_KEY);
        if (DICE_ANIM_MODES.includes(stored)) return stored;
    } catch (e) { /* localStorage indisponible */ }
    if (typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return 'off';
    return 'normal';
}

export function isDiceAnimating() {
    return _animState !== null;
}

/** Exécute cb tout de suite, ou à la fin de l'animation de dés en cours. */
export function runAfterDiceAnimation(cb) {
    if (_animState) _afterAnimCbs.push(cb);
    else cb();
}

function flushAfterAnimCbs() {
    const cbs = _afterAnimCbs;
    _afterAnimCbs = [];
    cbs.forEach(cb => { try { cb(); } catch (e) { console.error(e); } });
}

function initDiceAnimToggle() {
    const toggle = document.getElementById('dice-anim-toggle');
    if (!toggle) return;
    const refresh = () => { toggle.textContent = DICE_ANIM_LABELS[getDiceAnimMode()]; };
    toggle.addEventListener('click', () => {
        const next = DICE_ANIM_MODES[(DICE_ANIM_MODES.indexOf(getDiceAnimMode()) + 1) % DICE_ANIM_MODES.length];
        try { localStorage.setItem(DICE_ANIM_STORAGE_KEY, next); } catch (e) { /* ignore */ }
        refresh();
    });
    refresh();
}

function escapeHTML(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));
}

function ensureRollOverlay() {
    let overlay = document.getElementById('duel-roll-overlay');
    if (overlay) return overlay;
    const wrapper = document.getElementById('field-wrapper');
    if (!wrapper) return null;
    overlay = document.createElement('div');
    overlay.id = 'duel-roll-overlay';
    wrapper.appendChild(overlay);
    return overlay;
}

/**
 * Joue l'animation des deux dés vers un résultat déjà résolu.
 * Skippable au clic (ou Échap). Résout la promesse à la fermeture.
 */
function playDuelAnimation(aRoll, dRoll, breakdown, mode, finalWinner, isCrit) {
    return new Promise((resolve) => {
        if (_animState) _animState.abort();

        const overlay = ensureRollOverlay();
        if (!overlay) { resolve(); return; }
        const logCard = document.getElementById('log-card');
        const meta    = breakdown?.meta ?? null;

        const teamClass = (p, fallback) =>
            p?.team === 'internal' ? 'droll-die--internal'
                : p?.team === 'external' ? 'droll-die--external'
                    : fallback;

        const playerLine = (p, fallback) => p?.name
            ? `${p.number != null ? '#' + escapeHTML(p.number) + ' ' : ''}${escapeHTML(p.name)}`
            : fallback;

        const advBadge = (roll) => roll.isAdvantage
            ? `<div class="droll-adv">${breakdown?.captainReroll ? '👑 Reroll capitaine' : '🔥 Dépassement de soi'} — 2d20</div>`
            : '';

        const sideHTML = (side, roll, p, dieCls) => `
            <div class="droll-side">
                <div class="droll-label droll-label--${side}">${side === 'attack' ? '⚔️ Attaque' : '🛡️ Défense'}</div>
                <div class="droll-name">${playerLine(p, side === 'attack' ? 'Attaquant' : 'Défenseur')}</div>
                <div class="droll-action">${p?.actionLabel ? escapeHTML(p.actionLabel) : '&nbsp;'}</div>
                <div class="droll-dice-row">
                    <div class="droll-die ${dieCls}" data-die="${side}">?</div>
                    ${roll.isAdvantage ? `<div class="droll-die droll-die--alt ${dieCls}" data-die="${side}-alt">?</div>` : ''}
                </div>
                ${advBadge(roll)}
                <div class="droll-bonus" data-bonus="${side}">&nbsp;</div>
            </div>`;

        overlay.innerHTML = `
            <div class="droll-card" role="status">
                <div class="droll-row">
                    ${sideHTML('attack', aRoll, meta?.attacker, teamClass(meta?.attacker, 'droll-die--attack'))}
                    <div class="droll-vs">VS</div>
                    ${sideHTML('defense', dRoll, meta?.defender, teamClass(meta?.defender, 'droll-die--defense'))}
                </div>
                <div class="droll-verdict">&nbsp;</div>
                <div class="droll-hint">cliquer pour passer</div>
            </div>`;

        overlay.classList.add('visible');
        logCard?.classList.add('dice-rolling');

        const dieA      = overlay.querySelector('[data-die="attack"]');
        const dieAAlt   = overlay.querySelector('[data-die="attack-alt"]');
        const dieD      = overlay.querySelector('[data-die="defense"]');
        const dieDAlt   = overlay.querySelector('[data-die="defense-alt"]');
        const verdictEl = overlay.querySelector('.droll-verdict');

        const timers = [];
        const later  = (fn, ms) => timers.push(setTimeout(fn, ms));
        const flip   = setInterval(() => {
            [dieA, dieAAlt, dieD, dieDAlt].forEach(el => {
                if (el && !el.classList.contains('droll-die--settled')) {
                    el.textContent = 1 + Math.floor(Math.random() * DIE_SIDES);
                }
            });
        }, 70);

        const settleSide = (side, mainEl, altEl, roll) => {
            if (!mainEl || mainEl.classList.contains('droll-die--settled')) return;
            mainEl.textContent = roll.roll;
            mainEl.classList.add('droll-die--settled');
            if (roll.critSuccess) mainEl.classList.add('droll-die--crit');
            if (roll.critFail)    mainEl.classList.add('droll-die--fumble');
            if (altEl && roll.isAdvantage) {
                altEl.textContent = Math.min(roll.roll1 ?? roll.roll, roll.roll2 ?? roll.roll);
                altEl.classList.add('droll-die--settled', 'droll-die--discarded');
            }
            const bonusEl = overlay.querySelector(`[data-bonus="${side}"]`);
            if (bonusEl) bonusEl.textContent = `bonus +${roll.bonus.toFixed(1)}`;
        };

        const showVerdict = () => {
            if (!verdictEl || verdictEl.dataset.done) return;
            verdictEl.dataset.done = '1';
            const label = finalWinner === 'attack' ? "✓ L'attaque l'emporte"
                : finalWinner === 'defense' ? '✗ La défense tient bon'
                    : '= Égalité';
            verdictEl.textContent = isCrit ? `⚡ Critique — ${label}` : label;
            verdictEl.classList.add(
                finalWinner === 'attack' ? 'droll-verdict--attack'
                    : finalWinner === 'defense' ? 'droll-verdict--defense'
                        : 'droll-verdict--tie'
            );
        };

        const settleAll = () => {
            settleSide('attack',  dieA, dieAAlt, aRoll);
            settleSide('defense', dieD, dieDAlt, dRoll);
            showVerdict();
        };

        let done = false;
        const finish = () => {
            if (done) return;
            done = true;
            clearInterval(flip);
            timers.forEach(clearTimeout);
            overlay.classList.remove('visible');
            logCard?.classList.remove('dice-rolling');
            overlay.onclick = null;
            document.removeEventListener('keydown', onKey);
            _animState = null;
            resolve();
            flushAfterAnimCbs();
        };

        const skip = () => {
            if (done) return;
            if (!verdictEl?.dataset.done) {
                settleAll();
                later(finish, 400);
            } else {
                finish();
            }
        };
        const onKey = (e) => { if (e.key === 'Escape') skip(); };

        overlay.onclick = skip;
        document.addEventListener('keydown', onKey);

        const T = mode === 'fast'
            ? { settleA: 300, settleD: 480, verdict: 560,  close: 1150 }
            : { settleA: 800, settleD: 1050, verdict: 1200, close: 2100 };

        later(() => settleSide('attack',  dieA, dieAAlt, aRoll), T.settleA);
        later(() => settleSide('defense', dieD, dieDAlt, dRoll), T.settleD);
        later(showVerdict, T.verdict);
        later(finish, T.close);

        _animState = { abort: () => { settleAll(); finish(); } };
    });
}

function ensureTooltip() {
    if (_duelTooltipEl) return _duelTooltipEl;
    const el = document.createElement("div");
    el.id        = "duel-dice-tooltip";
    el.className = "dice-tooltip hidden";
    el.setAttribute("role", "tooltip");
    document.body.appendChild(el);
    _duelTooltipEl = el;
    return _duelTooltipEl;
}

function positionTooltip(anchorEl = _duelDiceEl) {
    const tip  = _duelTooltipEl;
    const dice = anchorEl;
    if (!tip || !dice) return;

    const margin   = 12;
    const gap      = 10;
    const diceRect = dice.getBoundingClientRect();

    tip.style.cssText = "position:fixed;z-index:9999;transform:none;right:auto;bottom:auto;";

    const wasHidden = tip.classList.contains("hidden");
    if (wasHidden) { tip.style.visibility = "hidden"; tip.classList.remove("hidden"); }

    const tipRect = tip.getBoundingClientRect();
    let left = diceRect.left + diceRect.width / 2 - tipRect.width / 2;
    let top  = diceRect.bottom + gap;
    left = Math.max(margin, Math.min(left, window.innerWidth  - tipRect.width  - margin));

    if (top + tipRect.height + margin > window.innerHeight) {
        top = diceRect.top - tipRect.height - gap;
        tip.setAttribute("data-placement", "top");
    } else {
        tip.setAttribute("data-placement", "bottom");
    }
    top = Math.max(margin, Math.min(top, window.innerHeight - tipRect.height - margin));

    tip.style.left = `${Math.round(left)}px`;
    tip.style.top  = `${Math.round(top)}px`;

    if (wasHidden) { tip.classList.add("hidden"); tip.style.visibility = ""; }
}

function formatBreakdownHTML(b) {
    if (!b) return "";

    const row     = (label, value) => `<div class="dt-row"><div class="dt-label">${label}</div><div class="dt-value">${value}</div></div>`;
    const section = (title, inner) => `<div class="dt-section"><div class="dt-title">${title}</div>${inner}</div>`;

    // Résultat avec couleur
    const winner      = b.result?.critWinner ?? b.result?.winner ?? "tie";
    const winnerLabel = winner === "attack"  ? "✓ Attaque gagne"
        : winner === "defense" ? "✗ Défense gagne"
            : "= Égalité";
    const winnerColor = winner === "attack"  ? "#22c55e"
        : winner === "defense" ? "#ef4444"
            : "#94a3b8";

    const resultLine = b.result?.critWinner
        ? `<span style="color:${winnerColor};font-weight:700">CRITIQUE — ${winnerLabel}</span>`
        : `<span style="color:${winnerColor};font-weight:700">${winnerLabel}</span>`
        + ` <span style="color:#94a3b8">(diff: ${Number(b.result?.diff ?? 0).toFixed(1)})</span>`;

    // Badge captain reroll
    const rerollBadge = b.captainReroll
        ? `<div style="background:#f59e0b;color:#fff;font-size:9px;font-weight:700;padding:2px 8px;border-radius:4px;margin-bottom:6px;text-align:center;letter-spacing:0.05em;">👑 CAPTAIN REROLL — 2d20 Avantage</div>`
        : "";

    // Contexte joueurs
    let contextHTML = "";
    if (b.meta?.attacker) {
        const a   = b.meta.attacker;
        const d   = b.meta.defender;
        const fmt = (x) => [
            x?.number != null ? `<span style="color:#94a3b8">#${x.number}</span>` : null,
            x?.name ? `<b>${x.name}</b>` : null,
            x?.actionLabel ? `<span style="color:#64748b"> — ${x.actionLabel}</span>` : null,
        ].filter(Boolean).join(" ");
        contextHTML = section("👤 Joueurs", [
            row("Attaquant", fmt(a)),
            row("Défenseur", d ? fmt(d) : `<span style="color:#94a3b8">—</span>`),
        ].join(""));
    }

    // Calcul intermédiaire affiché
    const aBase    = Number(b.attack?.base  ?? 0);
    const aStam    = Number(b.attack?.staminaFactor ?? 1);
    const dBase    = Number(b.defense?.base ?? 0);
    const dStam    = Number(b.defense?.staminaFactor ?? 1);

    return `<div class="dt-wrap">
        ${rerollBadge}
        ${contextHTML}
        ${section("🎲 Jets de dés", [
        row("Attaque", `<b style="font-size:12px">${b.rolls?.aTag ?? "?"}</b> → +${Number(b.rolls?.aBonus ?? 0).toFixed(1)}`),
        row("Défense", `<b style="font-size:12px">${b.rolls?.dTag ?? "?"}</b> → +${Number(b.rolls?.dBonus ?? 0).toFixed(1)}`),
    ].join(""))}
        ${section("⚔️ Attaque", [
        row("Base", `${aBase.toFixed(1)}`),
        row("× Stamina", `${aStam.toFixed(2)} = <b>${(aBase * aStam).toFixed(1)}</b>`),
        ...(b.attack?.additions || []).map(x => row(x.label, `<span style="color:#0ea5e9">${x.value}</span>`)),
        row("Total", `<b style="font-size:12px;color:#ffffff">${Number(b.attack?.total ?? 0).toFixed(1)}</b>`),
    ].join(""))}
        ${section("🛡️ Défense", [
        row("Base", `${dBase.toFixed(1)}`),
        row("× Stamina", `${dStam.toFixed(2)} = <b>${(dBase * dStam).toFixed(1)}</b>`),
        ...(b.defense?.additions || []).map(x => row(x.label, `<span style="color:#8b5cf6">${x.value}</span>`)),
        row("Total", `<b style="font-size:12px;color:#ffffff">${Number(b.defense?.total ?? 0).toFixed(1)}</b>`),
    ].join(""))}
        ${section("✅ Résultat", [
        row("Règle RPS", b.result?.bonusRuleLabel || "—"),
        row("Issue", resultLine),
    ].join(""))}
    </div>`;
}

// -----------------------------------------------------------
//   Affichage duel
// -----------------------------------------------------------
export function showDuelDice(attackScore, defenseScore, aRoll, dRoll, breakdown) {
    _lastBreakdown = breakdown ?? null;

    const duelDiceEl = document.getElementById("duel-dice-display");
    if (!duelDiceEl) return;

    const winner = attackScore > defenseScore ? "attack"
        : attackScore < defenseScore ? "defense" : "tie";
    const finalWinner = breakdown?.result?.critWinner ?? breakdown?.result?.winner ?? winner;

    const aTag = breakdown?.rolls?.aTag ?? String(aRoll.roll);
    const dTag = breakdown?.rolls?.dTag ?? String(dRoll.roll);

    const token = ++_chipToken;
    const revealChip = () => {
        if (token !== _chipToken) return;

        // Chip compact — juste les scores
        const winnerLabel = winner === 'attack' ? '✓ Attaque' : winner === 'defense' ? '✗ Défense' : '= Égalité';
        const winnerColor = winner === 'attack' ? '#22c55e' : winner === 'defense' ? '#ef4444' : '#94a3b8';

        duelDiceEl.textContent = `${aTag} vs ${dTag} — ${winnerLabel}`;
        duelDiceEl.style.color = winnerColor;
        duelDiceEl.style.fontWeight = '700';
        duelDiceEl.classList.add("visible", "pop");
        setTimeout(() => duelDiceEl.classList.remove("pop"), 500);

        // Tooltip détaillé mis à jour pour le hover
        const tip = ensureTooltip();
        if (tip) tip.innerHTML = formatBreakdownHTML(breakdown);
    };

    const mode = getDiceAnimMode();
    if (mode === 'off') { revealChip(); return; }

    // Chip masqué pendant l'animation, révélé à la fin
    duelDiceEl.classList.remove('visible', 'pop');
    playDuelAnimation(aRoll, dRoll, breakdown, mode, finalWinner, !!breakdown?.result?.critWinner)
        .then(revealChip);
}

/** Affiche le détail d'un duel (breakdown) ancré sur n'importe quel élément — utilisé par le clic sur l'historique. */
export function showBreakdownTooltip(breakdown, anchorEl) {
    if (!breakdown || !anchorEl) return;
    const tip = ensureTooltip();
    tip.innerHTML = formatBreakdownHTML(breakdown);
    positionTooltip(anchorEl);
    tip.classList.remove("hidden");
}

function formatSpecialHTML(move) {
    if (!move) return "";
    const row = (label, value) => `<div class="dt-row"><div class="dt-label">${label}</div><div class="dt-value">${value}</div></div>`;
    return `<div class="dt-wrap">
        <div class="dt-section">
            <div class="dt-title">🔥 ${move.label ?? move.short_label ?? "Spécial"}</div>
            ${move.description ? `<div class="dt-desc">${move.description}</div>` : ""}
            ${row("Recharge", move.cooldown != null ? `${move.cooldown} tour${move.cooldown > 1 ? "s" : ""}` : "—")}
        </div>
    </div>`;
}

/** Affiche le détail (nom complet, description, recharge) d'un spécial ancré sur son slot de carte. */
export function showSpecialTooltip(move, anchorEl) {
    if (!move || !anchorEl) return;
    const tip = ensureTooltip();
    tip.innerHTML = formatSpecialHTML(move);
    positionTooltip(anchorEl);
    tip.classList.remove("hidden");
}

export function hideBreakdownTooltip() {
    _duelTooltipEl?.classList.add("hidden");
}

export function isBreakdownTooltipVisible() {
    return !!_duelTooltipEl && !_duelTooltipEl.classList.contains("hidden");
}

export function bindDuelTooltipEvents() {
    if (!_duelDiceEl) return;
    ensureTooltip();

    const show = () => { if (_lastBreakdown) { positionTooltip(); _duelTooltipEl?.classList.remove("hidden"); } };
    const hide = () => _duelTooltipEl?.classList.add("hidden");

    _duelDiceEl.addEventListener("mouseenter", show);
    _duelDiceEl.addEventListener("mouseleave", hide);
    _duelDiceEl.setAttribute("tabindex", "0");
    _duelDiceEl.addEventListener("focus", show);
    _duelDiceEl.addEventListener("blur",  hide);

    const reposition = () => { if (!_duelTooltipEl?.classList.contains("hidden")) positionTooltip(); };
    window.addEventListener("scroll", reposition, { passive: true });
    window.addEventListener("resize", reposition);
}
