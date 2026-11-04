// resources/js/Pages/GameSaves/Play/lineupMastery.js
//
// Maîtrise d'un poste du terrain (miroir de RosterService.positionMastery côté
// moteur) et réorganisation d'un onze sur une formation.

// Zone de slot (0=GK, 1=DEF, 2=MDF, 3=MOF, 4=ATT) → rôle joueur
export const ROLE_BY_ZONE = { 0: 'GK', 1: 'DF', 2: 'MF', 3: 'MF', 4: 'FW' };

export const roleFromPosition = (pos) => {
    const p = String(pos || '').toLowerCase();
    if (p.includes('goal')) return 'GK';
    if (p.includes('def'))  return 'DF';
    if (p.includes('mid'))  return 'MF';
    if (p.includes('for') || p.includes('att')) return 'FW';
    return null;
};

/** 'primary' | 'secondary' | 'off' | null */
export const masteryFor = (player, slotDef) => {
    if (!player || !slotDef) return null;
    const slotRole = ROLE_BY_ZONE[slotDef.zone] ?? null;
    const natural  = roleFromPosition(player.position);
    if (!slotRole || !natural) return null;
    if (slotRole === natural) return 'primary';
    const secondary = (player.secondary_positions ?? []).map(roleFromPosition);
    return secondary.includes(slotRole) ? 'secondary' : 'off';
};

const COST = { primary: 0, secondary: 1, off: 4 };
const costOf = (player, slotDef) => (player ? (COST[masteryFor(player, slotDef)] ?? 0) : 0);

/**
 * Compte les joueurs hors poste / en poste secondaire.
 * @param {Object} slots   { slot: playerId|null }
 * @param {Object} formation  FORMATIONS[key]
 * @param {Function} byId  (id) => player
 */
export const countMismatches = (slots, formation, byId) => {
    let off = 0, secondary = 0;
    Object.entries(formation?.slots ?? {}).forEach(([slot, def]) => {
        const m = masteryFor(byId(slots[slot]), def);
        if (m === 'off') off++;
        else if (m === 'secondary') secondary++;
    });
    return { off, secondary };
};

/**
 * Ré-affecte les mêmes titulaires aux postes qui leur conviennent le mieux,
 * en bougeant le moins de joueurs possible : échanges 2 à 2 tant qu'ils
 * réduisent le coût total (suffisant pour 11 slots et 4 rôles).
 * Retourne un nouvel objet { slot: playerId|null } (l'entrée n'est pas modifiée).
 */
export const optimizeSlots = (slots, formation, byId) => {
    const result = { ...slots };
    const keys   = Object.keys(formation?.slots ?? {});
    const def    = (k) => formation.slots[k];

    let improved = true;
    let guard    = 0;
    while (improved && guard++ < 50) {
        improved = false;
        for (let i = 0; i < keys.length; i++) {
            for (let j = i + 1; j < keys.length; j++) {
                const a = keys[i], b = keys[j];
                const pa = byId(result[a]), pb = byId(result[b]);
                if (!pa && !pb) continue;
                const before = costOf(pa, def(a)) + costOf(pb, def(b));
                const after  = costOf(pb, def(a)) + costOf(pa, def(b));
                if (after < before) {
                    [result[a], result[b]] = [result[b], result[a]];
                    improved = true;
                }
            }
        }
    }
    return result;
};
