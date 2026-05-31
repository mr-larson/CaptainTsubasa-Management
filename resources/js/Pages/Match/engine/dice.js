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
        row("Total", `<b style="font-size:12px;color:#1e293b">${Number(b.attack?.total ?? 0).toFixed(1)}</b>`),
    ].join(""))}
        ${section("🛡️ Défense", [
        row("Base", `${dBase.toFixed(1)}`),
        row("× Stamina", `${dStam.toFixed(2)} = <b>${(dBase * dStam).toFixed(1)}</b>`),
        ...(b.defense?.additions || []).map(x => row(x.label, `<span style="color:#8b5cf6">${x.value}</span>`)),
        row("Total", `<b style="font-size:12px;color:#1e293b">${Number(b.defense?.total ?? 0).toFixed(1)}</b>`),
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

    const aTag = breakdown?.rolls?.aTag ?? String(aRoll.roll);
    const dTag = breakdown?.rolls?.dTag ?? String(dRoll.roll);

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
