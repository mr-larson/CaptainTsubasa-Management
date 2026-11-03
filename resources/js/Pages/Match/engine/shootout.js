// -----------------------------------------------------------
//   Tirs au but (phase à élimination directe, égalité en fin de match)
// -----------------------------------------------------------
// 5 tirs par équipe en alternance, puis mort subite. Les tireurs sont les
// joueurs de champ encore sur le terrain, du meilleur tireur au moins bon ;
// le gardien en slot 1 défend. Chance de marquer ≈ 75 %, ajustée par l'écart
// tir / arrêt main.

const BASE_CHANCE  = 0.75;
const MIN_CHANCE   = 0.5;
const MAX_CHANCE   = 0.92;
const REGULAR_KICKS = 5;
const MAX_KICKS     = 60; // garde-fou de la mort subite

function shootersFor(team, roster, rootEl) {
    const prefix = team === 'internal' ? 'I' : 'E';
    const shooters = [];
    for (let slot = 2; slot <= 11; slot++) {
        const el = rootEl.querySelector(`[data-player="${prefix}${slot}"]`);
        if (el?.classList.contains('unavailable')) continue;
        const info = roster.getPlayerInfo(team, slot);
        if (info) shooters.push(info);
    }
    return shooters.sort((a, b) => (b.stats?.shot ?? 0) - (a.stats?.shot ?? 0));
}

function scoreChance(shooter, keeper) {
    const diff = (shooter?.stats?.shot ?? 50) - (keeper?.stats?.hand_save ?? 50);
    return Math.min(MAX_CHANCE, Math.max(MIN_CHANCE, BASE_CHANCE + diff / 200));
}

/**
 * Joue la séance (résultat immédiat, l'affichage passe par le journal).
 * @returns {{ winner: 'internal'|'external', score: {internal:number, external:number}, kicks: Array }}
 */
export function runShootout(roster, rootEl, firstTeam = 'internal') {
    const order   = firstTeam === 'internal' ? ['internal', 'external'] : ['external', 'internal'];
    const shooters = { internal: shootersFor('internal', roster, rootEl), external: shootersFor('external', roster, rootEl) };
    const keepers  = { internal: roster.getPlayerInfo('internal', 1), external: roster.getPlayerInfo('external', 1) };
    const score    = { internal: 0, external: 0 };
    const taken    = { internal: 0, external: 0 };
    const kicks    = [];

    // Séance régulière : arrêt dès que l'écart ne peut plus être comblé.
    // Ensuite (mort subite) : décidé quand les deux ont tiré autant et que le score diffère.
    const remaining = (t) => Math.max(0, REGULAR_KICKS - taken[t]);
    const decided = () => {
        if (taken.internal < REGULAR_KICKS || taken.external < REGULAR_KICKS) {
            return score.internal + remaining('internal') < score.external
                || score.external + remaining('external') < score.internal;
        }
        return taken.internal === taken.external && score.internal !== score.external;
    };

    while (!decided() && kicks.length < MAX_KICKS) {
        for (const team of order) {
            const pool    = shooters[team];
            const shooter = pool.length ? pool[taken[team] % pool.length] : null;
            const keeper  = keepers[team === 'internal' ? 'external' : 'internal'];
            const scored  = Math.random() < scoreChance(shooter, keeper);

            taken[team]++;
            if (scored) score[team]++;
            kicks.push({ team, player_id: shooter?.id ?? null, name: shooter?.lastname ?? '?', scored });

            if (decided()) break;
        }
    }

    // Garde-fou (jamais atteint en pratique) : pile ou face.
    if (score.internal === score.external) score[Math.random() < 0.5 ? 'internal' : 'external']++;

    return { winner: score.internal > score.external ? 'internal' : 'external', score, kicks };
}
