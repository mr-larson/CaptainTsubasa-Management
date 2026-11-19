// Conserver les postes choisis quand la convocation évolue, compléter les postes libérés.
export function reconcileSelectionLineup(slots, suggested, ids) {
    const selected = new Set(ids);
    const next = {};
    const used = new Set();
    for (let slot = 1; slot <= 11; slot++) {
        const id = Number(slots?.[slot]);
        if (selected.has(id) && !used.has(id)) { next[slot] = id; used.add(id); }
    }
    const candidates = [...Object.values(suggested ?? {}).map(Number), ...ids];
    for (let slot = 1; slot <= 11; slot++) {
        if (next[slot]) continue;
        const id = candidates.find(id => selected.has(id) && !used.has(id));
        if (id != null) { next[slot] = id; used.add(id); }
    }
    return next;
}

export function isValidSelectionLineup(slots, ids) {
    if (slots == null) return true; // Pas de changement manuel : composition automatique.
    const entries = Object.entries(slots);
    const selected = new Set(ids);
    return entries.length === 11 && new Set(entries.map(([, id]) => id)).size === 11
        && entries.every(([slot, id]) => /^(?:[1-9]|10|11)$/.test(slot) && selected.has(id));
}
