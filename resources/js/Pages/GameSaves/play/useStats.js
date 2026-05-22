// resources/js/Pages/GameSaves/play/useStats.js
import { ref, computed } from 'vue';

export function useStats({ gameSave, teams, team, roster }) {

    // ==========================
    //   STATS SAISON (cumulées)
    // ==========================
    const playerSeasonStats = computed(() => gameSave.value.state?.player_stats ?? {});

    // ==========================
    //   STATS JOUEUR SÉLECTIONNÉ
    // ==========================
    const selectedMyPlayerPerf = (selectedMyPlayer) => computed(() => {
        const p = selectedMyPlayer.value;
        if (!p) return null;

        // Stats cumulées disponibles → on les utilise directement
        const s = playerSeasonStats.value[p.id];
        if (s) return s;

        // Fallback : calcul depuis playerActions
        const playerActions = gameSave.value.state?.player_actions ?? [];
        const events = playerActions.filter(ev =>
            ev.attack?.game_player_id === p.id ||
            ev.defense?.game_player_id === p.id
        );
        if (!events.length) return null;

        const stats = {
            offense: {
                pass:    { attempts: 0, success: 0 },
                shot:    { attempts: 0, success: 0 },
                dribble: { attempts: 0, success: 0 },
                special: { attempts: 0, success: 0 },
            },
            defense: {
                intercept: { attempts: 0, success: 0 },
                tackle:    { attempts: 0, success: 0 },
                block:     { attempts: 0, success: 0 },
                hands:     { attempts: 0, success: 0 },
                punch:     { attempts: 0, success: 0 },
                gkSpecial: { attempts: 0, success: 0 },
            },
            duelsWon: 0, duelsLost: 0,
        };

        for (const ev of events) {
            const result     = ev.result;
            const isAttacker = ev.attack?.game_player_id  === p.id;
            const isDefender = ev.defense?.game_player_id === p.id;

            if (isAttacker) {
                const type = ev.attack.action;
                if (stats.offense[type]) {
                    stats.offense[type].attempts++;
                    if (result === 'attack') stats.offense[type].success++;
                }
                if (result === 'attack')  stats.duelsWon++;
                if (result === 'defense') stats.duelsLost++;
            }

            if (isDefender) {
                const map = { intercept: 'intercept', tackle: 'tackle', block: 'block', hands: 'hands', punch: 'punch', 'gk-special': 'gkSpecial' };
                const key = map[ev.defense.action];
                if (key && stats.defense[key]) {
                    stats.defense[key].attempts++;
                    if (result === 'defense') stats.defense[key].success++;
                }
                if (result === 'defense') stats.duelsWon++;
                if (result === 'attack')  stats.duelsLost++;
            }
        }
        return stats;
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
            .map(p => ({ ...p, stats: allStats[p.id] ?? null }));
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
            const s = allStats[pid];
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

    // ==========================
    //   HELPERS AUTRES ÉQUIPES
    // ==========================
    const statValueFor = (p, key) => Number(p?.[key] ?? p?.stats?.[key] ?? 0) || 0;

    const averageTeamStat = (players, key) => {
        if (!Array.isArray(players) || !players.length) return 0;
        return Math.round(players.reduce((sum, p) => sum + statValueFor(p, key), 0) / players.length);
    };

    return {
        playerSeasonStats,
        selectedMyPlayerPerf,
        selectedStatsTeamId, selectedStatsTeam,
        selectedTeamPlayerStats, teamStats,
        statValueFor, averageTeamStat,
    };
}
