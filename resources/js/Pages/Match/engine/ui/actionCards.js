// resources/js/Pages/Match/engine/ui/actionCards.js
// Cartes d'action (attaque) et de défense de la barre d'actions : HTML généré.
import { TEXTS } from '../constants.js';

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
