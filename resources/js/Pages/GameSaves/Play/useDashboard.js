// resources/js/Pages/GameSaves/Play/useDashboard.js
import { computed } from 'vue';
import { averageOverall } from './usePlayerUtils.js';

export function useDashboard({ teams, gameSave, team, roster, activeInjuries, activeSuspensions, activeYellowCards }) {

    // ==========================
    //   CLASSEMENT
    // ==========================
    const standings = computed(() => {
        const list = (teams.value ?? []).map((t) => {
            const wins   = t.wins   ?? 0;
            const draws  = t.draws  ?? 0;
            const losses = t.losses ?? 0;
            // Buts maintenus sur game_teams (source de vérité côté serveur).
            const gf = t.goals_for     ?? 0;
            const ga = t.goals_against ?? 0;
            return {
                ...t, wins, draws, losses,
                played: wins + draws + losses,
                points: wins * 3 + draws,
                goals_for: gf,
                goals_against: ga,
                goal_diff: gf - ga,
                average_overall: averageOverall((t.contracts ?? [])
                    .map(contract => contract.game_player ?? contract.gamePlayer ?? contract.player)
                    .filter(Boolean)),
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

    const injuriesByPlayer = computed(() => new Map([...injuries.value].reverse().map(i => [Number(i.game_player_id), i])));
    const suspensionsByPlayer = computed(() => new Map([...suspensions.value].reverse().map(s => [Number(s.game_player_id), s])));

    const playerInjury = (playerId) => injuriesByPlayer.value.get(Number(playerId)) ?? null;
    const playerSuspension = (playerId) => suspensionsByPlayer.value.get(Number(playerId)) ?? null;
    const isPlayerInjured = (playerId) => playerInjury(playerId) !== null;
    const isPlayerSuspended = (playerId) => playerSuspension(playerId) !== null;
    const playerYellowCards = (playerId) => yellowCards.value[playerId] ?? 0;
    const isPlayerBooked = (playerId) => playerYellowCards(playerId) > 0;

    // Un joueur peut avoir plusieurs sanctions. Chaque compteur porte sur
    // les joueurs de l’effectif, comme la liste du statut du club.
    const countPlayers = (ids, predicate) => [...new Set(ids.map(Number))].filter(predicate).length;
    const rosterIds = computed(() => roster.value.map(player => player.id));
    const injuriesCount = computed(() => countPlayers(rosterIds.value, isPlayerInjured));
    const suspensionsCount = computed(() => countPlayers(rosterIds.value, isPlayerSuspended));
    const cardsCount = computed(() => countPlayers(rosterIds.value, isPlayerBooked));

    const playerIdsForTeam = (teamId) =>
        (teams.value ?? []).find(t => Number(t.id) === Number(teamId))?.contracts?.map(c => c.game_player_id) ?? [];
    const injuriesCountForTeam = (teamId) => countPlayers(playerIdsForTeam(teamId), isPlayerInjured);
    const suspensionsCountForTeam = (teamId) => countPlayers(playerIdsForTeam(teamId), isPlayerSuspended);
    const cardsCountForTeam = (teamId) => countPlayers(playerIdsForTeam(teamId), isPlayerBooked);

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
    const averageLuck    = computed(() => averageStat('luck'));
    const averageSpeed   = computed(() => averageStat('speed'));

    return {
        standings, clubStanding,
        teamRecord, teamBudget,
        injuries, suspensions, yellowCards,
        injuriesCount, suspensionsCount, cardsCount,
        injuriesCountForTeam, suspensionsCountForTeam, cardsCountForTeam,
        isPlayerInjured, isPlayerSuspended,
        playerYellowCards, playerInjury, playerSuspension,
        averageAttack, averageDefense, averageStamina, averageLuck, averageSpeed,
    };
}
