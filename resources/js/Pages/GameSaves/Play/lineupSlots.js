// Composition d'un onze : la « grille » est une liste de 11 lignes { slot, player_id } (player_id
// null = poste vide). Ces helpers sont communs à la composition de mon équipe (useTeam) et à celle
// des autres clubs (useOtherTeam). Ils modifient la grille en place, comme les formulaires qui la
// lient, et ne parlent jamais au serveur.

export const LINEUP_SLOTS = 11;

/** Coordonnées visuelles du terrain (en %) : abscisse par zone, ordonnée par couloir. */
export const PITCH_ZONE_X = { 0: 6, 1: 22, 2: 40, 3: 60, 4: 78 };
export const PITCH_LANE_Y = { 0: 10, 1: 30, 2: 50, 3: 70, 4: 90 };

/**
 * Grille initiale : chaque titulaire reprend son poste enregistré ; ceux qui n'en ont pas (ou dont le
 * poste est pris) comblent les postes restés vides, dans l'ordre.
 *
 * @param {Array<number>} starterIds  ids des titulaires
 * @param {Object<number, number>} savedSlots  poste → id du joueur, tel qu'enregistré
 */
export function buildLineupForm(starterIds, savedSlots = {}) {
    const unplaced = new Set(starterIds);
    const slots = [];
    for (let slot = 1; slot <= LINEUP_SLOTS; slot++) {
        const playerId = savedSlots[slot];
        slots.push({ slot, player_id: unplaced.delete(playerId) ? playerId : null });
    }
    for (const playerId of unplaced) {
        const available = slots.find((row) => !row.player_id);
        if (!available) break;
        available.player_id = playerId;
    }
    return slots;
}

/** Poste occupé par le joueur dans la grille, ou null. */
export function slotOfPlayer(form, playerId) {
    if (!playerId || !Array.isArray(form)) return null;
    const row = form.find((r) => r.player_id === playerId);
    return row ? row.slot : null;
}

/**
 * Envoie le joueur sur un poste : s'il est déjà titulaire il échange avec l'occupant du poste visé
 * (ou le laisse vide), sinon il prend simplement le poste. Retourne false (sans rien changer) si le
 * poste est invalide, déjà le sien ou inexistant.
 */
export function moveToSlot(form, playerId, slotValue) {
    const slotNumber = Number(slotValue);
    if (!slotNumber || slotNumber < 1 || slotNumber > LINEUP_SLOTS) return false;
    if (slotOfPlayer(form, playerId) === slotNumber) return false;

    const currentRow = form.find((r) => r.player_id === playerId) || null;
    const targetRow = form.find((r) => r.slot === slotNumber) || null;
    if (!targetRow) return false;

    const previousAtTarget = targetRow.player_id ?? null;
    if (currentRow) currentRow.player_id = previousAtTarget;
    targetRow.player_id = playerId;
    return true;
}

/** Échange les postes de deux titulaires. Retourne false si l'un des deux n'est pas dans la grille. */
export function swapSlots(form, playerAId, playerBId) {
    if (!playerAId || !playerBId || playerAId === playerBId) return false;
    const rowA = form.find((r) => r.player_id === playerAId);
    const rowB = form.find((r) => r.player_id === playerBId);
    if (!rowA || !rowB) return false;

    [rowA.player_id, rowB.player_id] = [rowB.player_id, rowA.player_id];
    return true;
}

/** Table poste → id du joueur, pour les postes occupés. */
export function slotToPlayerMap(form) {
    const map = {};
    form.forEach((row) => { if (row.player_id) map[row.slot] = row.player_id; });
    return map;
}

/** Position (x, y en %) d'un poste d'après la formation ; centre du terrain si le poste est inconnu. */
export function pitchPosition(formationData, slot) {
    const def = formationData?.slots?.[Number(slot)];
    if (!def) return { x: 50, y: 50 };
    return {
        x: PITCH_ZONE_X[def.zone] ?? 50,
        y: def.laneIndex === null ? 50 : (PITCH_LANE_Y[def.laneIndex] ?? 50),
    };
}

/**
 * Que fait-on quand un joueur « soulevé » est déposé sur un autre ?
 * - 'swap'      : deux titulaires échangent leurs postes ;
 * - 'bench-in'  : le soulevé est remplaçant, la cible titulaire → le remplaçant entre ;
 * - 'bench-out' : le soulevé est titulaire, la cible remplaçante → le titulaire sort ;
 * - null        : deux remplaçants, rien à faire.
 */
export function interactionKind(source, target) {
    if (source.is_starter && target.is_starter) return 'swap';
    if (!source.is_starter && target.is_starter) return 'bench-in';
    if (source.is_starter && !target.is_starter) return 'bench-out';
    return null;
}
