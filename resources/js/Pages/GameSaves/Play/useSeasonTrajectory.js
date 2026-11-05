// resources/js/Pages/GameSaves/Play/useSeasonTrajectory.js
// Trajectoire de la saison (Dashboard) : position semaine par semaine, écart avec
// l'objectif du mandat, projection budgétaire. Partagé entre les indicateurs du
// haut et les cartes Trajectoire / Finances.
import { computed, toValue } from 'vue';

export function useSeasonTrajectory({ team, teams, matches, season, week, career, budget, incomeModel }) {
    const myId = computed(() => toValue(team)?.id ?? null);
    const standings = computed(() => toValue(teams) ?? []);

    const seasonMatches = computed(() => (toValue(matches) ?? [])
        .filter(m => m.season == null || Number(m.season) === Number(toValue(season))));

    const isMine = (m) => m.home_team_id === myId.value || m.away_team_id === myId.value;

    // ── Position semaine par semaine ─────────────────────────
    // Rejoue le classement après chaque semaine avec le même départage que
    // useDashboard : points → diff. de buts → buts marqués → victoires → nom.
    const positionHistory = computed(() => {
        if (myId.value == null || !standings.value.length) return [];
        const played = seasonMatches.value.filter(m => m.status === 'played');
        const weeks  = [...new Set(played.map(m => Number(m.week)))].sort((a, b) => a - b);
        const rows   = Object.fromEntries(standings.value.map(t => [t.id, { id: t.id, name: t.name ?? '', pts: 0, gf: 0, ga: 0, w: 0 }]));

        return weeks.map(w => {
            played.filter(m => Number(m.week) === w).forEach(m => {
                const h = rows[m.home_team_id], a = rows[m.away_team_id];
                if (!h || !a) return;
                const hs = Number(m.home_score) || 0, as = Number(m.away_score) || 0;
                h.gf += hs; h.ga += as; a.gf += as; a.ga += hs;
                if (hs > as)      { h.pts += 3; h.w++; }
                else if (hs < as) { a.pts += 3; a.w++; }
                else              { h.pts++; a.pts++; }
            });
            const order = Object.values(rows).sort((x, y) =>
                (y.pts - x.pts) || ((y.gf - y.ga) - (x.gf - x.ga)) || (y.gf - x.gf) || (y.w - x.w) || x.name.localeCompare(y.name));
            return { week: w, position: order.findIndex(r => r.id === myId.value) + 1 };
        });
    });

    // Places gagnées (+) ou perdues (−) sur la dernière semaine jouée.
    const positionDelta = computed(() => {
        const h = positionHistory.value;
        return h.length >= 2 ? h.at(-2).position - h.at(-1).position : null;
    });

    const teamCount = computed(() => Math.max(standings.value.length, 2));

    // ── Objectif du mandat ───────────────────────────────────
    const targetRank = computed(() => toValue(career)?.mandate?.target_rank ?? null);

    const remainingMatches = computed(() =>
        seasonMatches.value.filter(m => isMine(m) && m.status !== 'played'
            && m.home_team_id != null && m.away_team_id != null).length);

    const objective = computed(() => {
        const target = targetRank.value;
        if (!target || myId.value == null) return null;
        const list  = standings.value;
        const pts   = (t) => (t?.wins ?? 0) * 3 + (t?.draws ?? 0);
        const myPos = list.findIndex(t => t.id === myId.value) + 1;
        const me    = list[myPos - 1];
        if (!me) return null;

        if (myPos <= target) {
            const chaser = list[target]; // (target+1)e
            const lead   = chaser ? pts(me) - pts(chaser) : null;
            return {
                ok: true,
                short: lead != null ? `+${lead} pt(s)` : 'Atteint',
                text: chaser ? `+${lead} pt(s) sur la ${target + 1}e place (${chaser.name})` : 'Objectif atteint',
            };
        }
        const holder = list[target - 1];
        const gap    = pts(holder) - pts(me);
        return {
            ok: false,
            out: gap > remainingMatches.value * 3,
            short: `−${gap} pt(s)`,
            text: `À ${gap} pt(s) de la ${target}e place (${holder.name})`,
        };
    });

    // ── Finances ─────────────────────────────────────────────
    const totalWeeks = computed(() => Math.max(Number(toValue(week)), ...seasonMatches.value.map(m => Number(m.week) || 0)));
    // Le revenu de la semaine en cours n'est versé qu'à sa clôture : elle compte.
    const weeksLeft  = computed(() => Math.max(0, totalWeeks.value - Number(toValue(week)) + 1));

    // Bonus moyen par match au rythme actuel (victoires/nuls déjà obtenus).
    const avgBonus = computed(() => {
        const m = toValue(incomeModel), t = toValue(team);
        const w = t?.wins ?? 0, d = t?.draws ?? 0, l = t?.losses ?? 0;
        if (!m || !(w + d + l)) return 0;
        return (w * m.win_bonus + d * m.draw_bonus) / (w + d + l);
    });

    const projection = computed(() => {
        const m = toValue(incomeModel);
        if (!m) return null;
        const base = Number(toValue(budget)) + weeksLeft.value * m.base;
        return { min: base, likely: Math.round(base + remainingMatches.value * avgBonus.value) };
    });

    return {
        positionHistory, positionDelta, teamCount, targetRank, remainingMatches, objective,
        totalWeeks, weeksLeft, projection,
    };
}
