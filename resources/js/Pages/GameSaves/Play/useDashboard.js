// resources/js/Pages/GameSaves/Play/useDashboard.js
import { computed } from 'vue';

export function useDashboard({ teams, gameSave, team, roster, matches, activeInjuries, activeSuspensions, activeYellowCards }) {

    // ==========================
    //   CLASSEMENT
    // ==========================
    const standings = computed(() => {
        // Buts marqués / encaissés agrégés depuis les matchs joués.
        const goalsFor = {};
        const goalsAgainst = {};
        for (const m of (matches?.value ?? [])) {
            if (m.status !== 'played') continue;
            const hs = m.home_score ?? 0;
            const as = m.away_score ?? 0;
            goalsFor[m.home_team_id]     = (goalsFor[m.home_team_id]     ?? 0) + hs;
            goalsAgainst[m.home_team_id] = (goalsAgainst[m.home_team_id] ?? 0) + as;
            goalsFor[m.away_team_id]     = (goalsFor[m.away_team_id]     ?? 0) + as;
            goalsAgainst[m.away_team_id] = (goalsAgainst[m.away_team_id] ?? 0) + hs;
        }

        const list = (teams.value ?? []).map((t) => {
            const wins   = t.wins   ?? 0;
            const draws  = t.draws  ?? 0;
            const losses = t.losses ?? 0;
            const gf = goalsFor[t.id]     ?? 0;
            const ga = goalsAgainst[t.id] ?? 0;
            return {
                ...t, wins, draws, losses,
                played: wins + draws + losses,
                points: wins * 3 + draws,
                goals_for: gf,
                goals_against: ga,
                goal_diff: gf - ga,
            };
        });
        // Départage : points → différence de buts → buts marqués → victoires → nom.
        list.sort((a, b) => {
            if (b.points    !== a.points)    return b.points    - a.points;
            if (b.goal_diff !== a.goal_diff) return b.goal_diff - a.goal_diff;
            if (b.goals_for !== a.goals_for) return b.goals_for - a.goals_for;
            if (b.wins      !== a.wins)      return b.wins      - a.wins;
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
