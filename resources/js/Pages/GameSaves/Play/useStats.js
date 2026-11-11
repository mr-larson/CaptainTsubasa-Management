// resources/js/Pages/GameSaves/Play/useStats.js
import { ref, computed } from 'vue';

export function useStats({ gameSave, teams, team, roster, playerSeasonStats: playerSeasonStatsProp }) {

    // ==========================
    //   STATS SAISON (cumulées)
    // ==========================
    // Agrégé côté PHP depuis game_matches.match_stats — passé comme prop Inertia dédiée
    const playerSeasonStats = computed(() => playerSeasonStatsProp?.value ?? {});

    // ==========================
    //   STATS JOUEUR SÉLECTIONNÉ
    // ==========================
    const selectedMyPlayerPerf = (selectedMyPlayer) => computed(() => {
        const p = selectedMyPlayer.value;
        if (!p) return null;

        // Stats cumulées (agrégées côté PHP depuis game_matches.match_stats).
        return playerSeasonStats.value[p.id] ?? playerSeasonStats.value[String(p.id)] ?? null;
    });

    // ==========================
    //   STATS PAR ÉQUIPE
    // ==========================
    const selectedStatsTeamId = ref(null);

    const selectedStatsTeam = computed(() => {
        if (!teams.value?.length) return null;
        if (!selectedStatsTeamId.value) return team.value || teams.value[0];
        const id = Number(selectedStatsTeamId.value);
        return teams.value.find(t => Number(t.id) === id) ?? (team.value || teams.value[0]);
    });

    const selectedTeamPlayerStats = computed(() => {
        const t = selectedStatsTeam.value;
        if (!t || !Array.isArray(t.contracts)) return [];
        const allStats = playerSeasonStats.value ?? {};
        return t.contracts
            .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
            .filter(Boolean)
            .map(p => ({ ...p, stats: allStats[p.id] ?? allStats[String(p.id)] ?? null }));
    });

    const teamStats = computed(() => {
        const t = selectedStatsTeam.value;
        const totals = { shots: 0, passes: 0, dribbles: 0, intercepts: 0, tackles: 0, blocks: 0, duelsWon: 0, duelsLost: 0 };
        if (!t) return totals;

        const allStats = playerSeasonStats.value ?? {};
        const playerIds = (t.contracts ?? [])
            .map(c => c.game_player?.id ?? c.gamePlayer?.id ?? c.player?.id)
            .filter(Boolean);

        playerIds.forEach(pid => {
            const s = allStats[pid] ?? allStats[String(pid)];
            if (!s) return;
            totals.shots      += s.offense?.shot?.attempts      ?? 0;
            totals.passes     += s.offense?.pass?.attempts      ?? 0;
            totals.dribbles   += s.offense?.dribble?.attempts   ?? 0;
            totals.intercepts += s.defense?.intercept?.attempts ?? 0;
            totals.tackles    += s.defense?.tackle?.attempts    ?? 0;
            totals.blocks     += s.defense?.block?.attempts     ?? 0;
            totals.duelsWon   += s.duelsWon  ?? 0;
            totals.duelsLost  += s.duelsLost ?? 0;
        });
        return totals;
    });

    return {
        playerSeasonStats,
        selectedMyPlayerPerf,
        selectedStatsTeamId, selectedStatsTeam,
        selectedTeamPlayerStats, teamStats,
    };
}
