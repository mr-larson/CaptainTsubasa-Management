// resources/js/Pages/GameSaves/play/useDashboard.js
import { computed } from 'vue';

export function useDashboard({ teams, gameSave, team, roster }) {

    // ==========================
    //   CLASSEMENT
    // ==========================
    const standings = computed(() => {
        const list = (teams.value ?? []).map((t) => {
            const wins   = t.wins   ?? 0;
            const draws  = t.draws  ?? 0;
            const losses = t.losses ?? 0;
            return { ...t, wins, draws, losses, played: wins + draws + losses, points: wins * 3 + draws };
        });
        list.sort((a, b) => {
            if (b.points !== a.points) return b.points - a.points;
            if (b.wins   !== a.wins)   return b.wins   - a.wins;
            return a.name.localeCompare(b.name);
        });
        return list;
    });

    const clubStanding = computed(() => {
        if (!team.value) return null;
        const index = standings.value.findIndex(r => r.id === team.value.id);
        if (index === -1) return null;
        return { ...standings.value[index], position: index + 1 };
    });

    // ==========================
    //   BILAN / BUDGET
    // ==========================
    const teamRecord = computed(() => ({
        wins:   team.value?.wins   ?? 0,
        draws:  team.value?.draws  ?? 0,
        losses: team.value?.losses ?? 0,
    }));

    const teamBudget = computed(() => team.value?.budget ?? 0);

    // ==========================
    //   BLESSURES / CARTONS
    // ==========================
    const injuries    = computed(() => gameSave.value.state?.injuries    ?? []);
    const suspensions = computed(() => gameSave.value.state?.suspensions ?? []);
    const cards       = computed(() => gameSave.value.state?.cards       ?? []);

    const injuriesCount    = computed(() => injuries.value.length);
    const suspensionsCount = computed(() => suspensions.value.length);
    const cardsCount       = computed(() => cards.value.length);

    const countByTeamIdOrAll = (items, teamId) => {
        if (!Array.isArray(items)) return 0;
        const hasTeamId = items.some(x => x && Object.prototype.hasOwnProperty.call(x, 'team_id'));
        if (!hasTeamId) return items.length;
        return items.filter(x => Number(x.team_id) === Number(teamId)).length;
    };

    const injuriesCountForTeam    = (teamId) => countByTeamIdOrAll(injuries.value,    teamId);
    const suspensionsCountForTeam = (teamId) => countByTeamIdOrAll(suspensions.value, teamId);
    const cardsCountForTeam       = (teamId) => countByTeamIdOrAll(cards.value,       teamId);

    // ==========================
    //   STATS MOYENNES
    // ==========================
    const statValue = (p, key) => {
        const stats = p?.stats ?? {};
        return Number(p?.[key] ?? stats[key] ?? 0) || 0;
    };

    const averageStat = (key) => {
        if (!roster.value.length) return 0;
        const total = roster.value.reduce((sum, p) => sum + statValue(p, key), 0);
        return Math.round(total / roster.value.length);
    };

    const averageAttack  = computed(() => averageStat('attack'));
    const averageDefense = computed(() => averageStat('defense'));
    const averageStamina = computed(() => averageStat('stamina'));
    const averageSpeed   = computed(() => averageStat('speed'));

    return {
        standings, clubStanding,
        teamRecord, teamBudget,
        injuries, suspensions, cards,
        injuriesCount, suspensionsCount, cardsCount,
        injuriesCountForTeam, suspensionsCountForTeam, cardsCountForTeam,
        averageAttack, averageDefense, averageStamina, averageSpeed,
    };
}
