// resources/js/Pages/GameSaves/Play/useDashboard.js
import { computed } from 'vue';

export function useDashboard({ teams, gameSave, team, roster, activeInjuries, activeSuspensions, activeYellowCards }) {

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
    //   BLESSURES / SUSPENSIONS / CARTONS
    //   Source : game_injuries + game_sanctions (via props Inertia)
    // ==========================
    const injuries    = computed(() => activeInjuries?.value    ?? []);
    const suspensions = computed(() => activeSuspensions?.value ?? []);
    const yellowCards = computed(() => activeYellowCards?.value ?? {});

    const injuriesCount    = computed(() => injuries.value.length);
    const suspensionsCount = computed(() => suspensions.value.length);
    const cardsCount       = computed(() => Object.keys(yellowCards.value).length);

    // Par équipe — filtre par les joueurs de l'équipe
    const injuriesCountForTeam = (teamId) => {
        const t = (teams.value ?? []).find(t => t.id === teamId);
        if (!t) return 0;
        const playerIds = new Set((t.contracts ?? []).map(c => c.game_player_id));
        return injuries.value.filter(i => playerIds.has(i.game_player_id)).length;
    };

    const suspensionsCountForTeam = (teamId) => {
        const t = (teams.value ?? []).find(t => t.id === teamId);
        if (!t) return 0;
        const playerIds = new Set((t.contracts ?? []).map(c => c.game_player_id));
        return suspensions.value.filter(s => playerIds.has(s.game_player_id)).length;
    };

    const cardsCountForTeam = (teamId) => {
        const t = (teams.value ?? []).find(t => t.id === teamId);
        if (!t) return 0;
        const playerIds = new Set((t.contracts ?? []).map(c => c.game_player_id));
        return [...playerIds].filter(id => yellowCards.value[id] > 0).length;
    };

    // Helpers pour vérifier le statut d'un joueur spécifique
    const isPlayerInjured = (playerId) =>
        injuries.value.some(i => i.game_player_id === playerId);

    const isPlayerSuspended = (playerId) =>
        suspensions.value.some(s => s.game_player_id === playerId);

    const playerYellowCards = (playerId) =>
        yellowCards.value[playerId] ?? 0;

    const playerInjury = (playerId) =>
        injuries.value.find(i => i.game_player_id === playerId) ?? null;

    const playerSuspension = (playerId) =>
        suspensions.value.find(s => s.game_player_id === playerId) ?? null;

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
        injuries, suspensions, yellowCards,
        injuriesCount, suspensionsCount, cardsCount,
        injuriesCountForTeam, suspensionsCountForTeam, cardsCountForTeam,
        isPlayerInjured, isPlayerSuspended,
        playerYellowCards, playerInjury, playerSuspension,
        averageAttack, averageDefense, averageStamina, averageSpeed,
    };
}
