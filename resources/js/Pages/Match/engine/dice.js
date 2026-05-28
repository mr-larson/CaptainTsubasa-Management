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
            number: aInfo?.number ?? null, name: fullName(aInfo),
            actionKey: attackAction, actionLabel: getAttackLabel(attackAction),
        },
        defender: defenseSlot ? {
            team: defenseTeam, slot: defenseSlot,
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
                                        }) {
    const aTag      = aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll));
    const dTag      = dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll));
    const goodBonus = DUEL_RULES.GOOD_COUNTER_BONUS   ?? 0;
    const genBonus  = DUEL_RULES.GENERIC_ATTACK_BONUS ?? 0;
    const diff      = attackScore - defenseScore;

    // Libellé pour le 2d20 advantage
    const aDisplayTag = aRoll.isAdvantage
        ? `2d20(${aRoll.roll1},${aRoll.roll2})=${aRoll.roll}${aRoll.critSuccess ? '!' : ''}`
        : aTag;

    return {
        meta,
        captainReroll,
        rolls:   { aTag: aDisplayTag, dTag, aBonus: aRoll.bonus, dBonus: dRoll.bonus },
        attack: {
            base: attackBaseRaw, staminaFactor: attackStamF,
            additions: [
                ...(clearanceBonus ? [{ label: "Clearance bonus", value: `+ ${Number(clearanceBonus).toFixed(2)}` }] : []),
                ...(captainReroll  ? [{ label: "👑 Captain Reroll", value: "Advantage!" }] : []),
                { label: isGood ? "X Mauvais contre" : "✓ Bonus attaque", value: isGood ? `—` : `+ ${genBonus}` },
            ],
            total: attackScore,
        },
        defense: {
            base: defenseBaseRaw, staminaFactor: defenseStamF,
            additions: [
                { label: isGood ? "✓ Bon contre" : "X Mauvais choix", value: isGood ? `+ ${goodBonus}` : "—" },
            ],
            total: defenseScore,
        },
        result: {
            bonusRuleLabel: isGood ? "Bon contre (+2 défense)" : "Mauvais contre (+2 attaque)",
            critWinner: null,
            diff,
            winner: diff > 0 ? "attack" : diff < 0 ? "defense" : "tie",
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

function positionTooltip() {
    const tip  = _duelTooltipEl;
    const dice = _duelDiceEl;
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

    const resultLine = b.result?.critWinner
        ? `Crit: <b>${String(b.result.critWinner).toUpperCase()}</b>`
        : `Diff: <b>${Number(b.result?.diff ?? 0).toFixed(2)}</b> → <b>${String(b.result?.winner ?? "—").toUpperCase()}</b>`;

    let contextHTML = "";
    if (b.meta?.attacker) {
        const a = b.meta.attacker;
        const d = b.meta.defender;
        const fmt = (x) => [x?.number != null ? `#${x.number}` : null, x?.name, x?.actionLabel ? `— ${x.actionLabel}` : null].filter(Boolean).join(" ");
        contextHTML = section("👤 Contexte", [row("Attaquant", fmt(a)), row("Défenseur", d ? fmt(d) : "—")].join(""));
    }

    return `<div class="dt-wrap">
        ${contextHTML}
        ${section("🎲 Jets", [
        row("Attaque d20",  `${b.rolls.aTag} (bonus +${Number(b.rolls.aBonus ?? 0).toFixed(1)})`),
        row("Défense d20",  `${b.rolls.dTag} (bonus +${Number(b.rolls.dBonus ?? 0).toFixed(1)})`),
    ].join(""))}
        ${section("⚔️ Attaque", [
        row("Base",           Number(b.attack.base  ?? 0).toFixed(2)),
        row("Stamina factor", `× ${Number(b.attack.staminaFactor ?? 1).toFixed(2)}`),
        ...(b.attack.additions  || []).map(x => row(x.label, x.value)),
        row("Total attaque",  `<b>${Number(b.attack.total  ?? 0).toFixed(2)}</b>`),
    ].join(""))}
        ${section("🛡️ Défense", [
        row("Base",           Number(b.defense.base ?? 0).toFixed(2)),
        row("Stamina factor", `× ${Number(b.defense.staminaFactor ?? 1).toFixed(2)}`),
        ...(b.defense.additions || []).map(x => row(x.label, x.value)),
        row("Total défense",  `<b>${Number(b.defense.total ?? 0).toFixed(2)}</b>`),
    ].join(""))}
        ${section("✅ Résultat", [
        row("Règle bonus", b.result?.bonusRuleLabel || "—"),
        row("Issue",       resultLine),
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

    const aTag = breakdown?.rolls?.aTag ?? String(aRoll.roll);
    const dTag = breakdown?.rolls?.dTag ?? String(dRoll.roll);

    // Chip compact — juste les scores
    duelDiceEl.textContent = `${aTag} vs ${dTag}`;
    duelDiceEl.classList.add("visible", "pop");
    setTimeout(() => duelDiceEl.classList.remove("pop"), 500);

    // Tooltip détaillé mis à jour pour le hover
    const tip = ensureTooltip();
    if (tip) tip.innerHTML = formatBreakdownHTML(breakdown);
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
