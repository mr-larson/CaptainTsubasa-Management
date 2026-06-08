// resources/js/Pages/GameSaves/Play/useCalendar.js
import { ref, computed } from 'vue';

export function useCalendar({ matches, teams, team, week }) {

    // ==========================
    //   MATCHS DE MON ÉQUIPE
    // ==========================
    const isByeMatch = (match) => {
        if (!match) return false;
        if (match.is_bye === true) return true;
        return !match.home_team_id || !match.away_team_id;
    };

    const teamById = computed(() => {
        const map = {};
        (teams.value ?? []).forEach(t => { map[t.id] = t; });
        return map;
    });

    const opponentTeamIdFor = (match) => {
        if (!team.value || !match || isByeMatch(match)) return null;
        return match.home_team_id === team.value.id ? match.away_team_id : match.home_team_id;
    };

    const opponentNameFor = (match) => {
        if (!team.value)                         return '???';
        if (!match || isByeMatch(match))         return 'Repos';
        const oppId = opponentTeamIdFor(match);
        if (!oppId)                              return 'Repos';
        return teamById.value[oppId]?.name ?? '???';
    };

    const opponentNameForTeam = (match, teamId) => {
        if (!match || !teamId)         return '???';
        if (match.is_bye === true)     return 'Repos';
        const isHome = match.home_team_id === teamId;
        const oppId  = isHome ? match.away_team_id : match.home_team_id;
        return teamById.value[oppId]?.name ?? '???';
    };

    const myMatches = computed(() => {
        if (!team.value) return [];
        return (matches.value ?? [])
            .filter(m => m.home_team_id === team.value.id || m.away_team_id === team.value.id)
            .sort((a, b) => (a.week ?? 0) - (b.week ?? 0));
    });

    const seasonWeeksCount = computed(() => {
        const n = teams.value?.length ?? 0;
        if (n < 2) return 0;
        return n % 2 === 1 ? n * 2 : (n - 1) * 2;
    });

    const myMatchThisWeek = computed(() => {
        if (!team.value) return null;
        return myMatches.value.find(m =>
            m.week === (week.value ?? 1) &&
            !isByeMatch(m) &&
            (m.status === 'scheduled' || m.status === 'played')
        ) ?? null;
    });

    const isByeWeek = computed(() => !team.value || !myMatchThisWeek.value);

    const nextMatch = computed(() => {
        if (!team.value || !myMatches.value.length) return null;
        return myMatches.value
            .filter(m => m.status === 'scheduled' && m.week >= (week.value ?? 1))
            .sort((a, b) => a.week - b.week)[0] ?? null;
    });

    const nextMatchInfo = computed(() => {
        if (!nextMatch.value || !team.value) return null;
        const isHome   = nextMatch.value.home_team_id === team.value.id;
        const opponent = isHome ? nextMatch.value.away_team : nextMatch.value.home_team;
        return {
            isHome,
            opponentName: opponent?.name ?? opponentNameFor(nextMatch.value),
            week:         nextMatch.value.week,
        };
    });

    // ==========================
    //   CALENDRIER PAR ÉQUIPE
    // ==========================
    const selectedCalendarTeamId = ref(null);
    const calendarTeams          = computed(() => teams.value ?? []);

    const calendarTeam = computed(() => {
        if (!calendarTeams.value.length) return null;
        if (!selectedCalendarTeamId.value) return team.value || calendarTeams.value[0];
        const id = Number(selectedCalendarTeamId.value);
        return calendarTeams.value.find(t => Number(t.id) === id) ?? (team.value || calendarTeams.value[0]);
    });

    const selectCalendarTeam = (t) => { selectedCalendarTeamId.value = t.id; };

    const calendarTeamMatches = computed(() => {
        if (!calendarTeam.value) return [];
        return (matches.value ?? [])
            .filter(m => m.home_team_id === calendarTeam.value.id || m.away_team_id === calendarTeam.value.id)
            .sort((a, b) => (a.week ?? 0) - (b.week ?? 0));
    });

    const calendarRows = computed(() => {
        if (!calendarTeam.value) return [];
        const byWeek = new Map();
        calendarTeamMatches.value.forEach(m => byWeek.set(Number(m.week), m));
        const rows = [];
        for (let w = 1; w <= (seasonWeeksCount.value || 0); w++) {
            rows.push(byWeek.get(w) ?? {
                id: `bye-${calendarTeam.value.id}-${w}`,
                week: w, is_bye: true, status: 'bye',
                home_team_id: null, away_team_id: null,
            });
        }
        return rows;
    });

    const calendarTeamRoster = computed(() => {
        if (!calendarTeam.value || !Array.isArray(calendarTeam.value.contracts)) return [];
        return calendarTeam.value.contracts
            .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
            .filter(Boolean);
    });
    const calendarOpponentRoster = computed(() => {
        if (!selectedCalendarMatch.value) return [];
        const isHome = selectedCalendarMatch.value.home_team_id === calendarTeam.value?.id;
        const oppId  = isHome
            ? selectedCalendarMatch.value.away_team_id
            : selectedCalendarMatch.value.home_team_id;
        const oppTeam = teams.value?.find(t => Number(t.id) === Number(oppId)) ?? null;
        if (!oppTeam || !Array.isArray(oppTeam.contracts)) return [];
        return oppTeam.contracts
            .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
            .filter(Boolean);
    });

    // ==========================
    //   STATS MATCH SÉLECTIONNÉ
    // ==========================
    const selectedCalendarMatchId = ref(null);

    const selectedCalendarMatch = computed(() =>
        selectedCalendarMatchId.value
            ? calendarTeamMatches.value.find(m => m.id === selectedCalendarMatchId.value) ?? null
            : null
    );

    const selectedCalendarMatchStats      = computed(() => selectedCalendarMatch.value?.match_stats ?? null);
    const selectedCalendarPlayersStats    = computed(() => selectedCalendarMatchStats.value?.players ?? {});
    const selectedCalendarProgression = computed(() =>
        selectedCalendarMatchStats.value?.progression ?? []
    );

    // Déroulé du match (action par action) — disponible pour les matchs joués
    // ET les matchs simulés par l'IA (voir buildMatchStats / MatchSimulator::buildEventEntry)
    const selectedCalendarEvents = computed(() =>
        selectedCalendarMatchStats.value?.events ?? []
    );

    const selectedCalendarMyTeamStats = computed(() => {
        if (!selectedCalendarMatchStats.value || !calendarTeam.value || !selectedCalendarMatch.value) return null;
        const isHome = selectedCalendarMatch.value.home_team_id === calendarTeam.value.id;
        return isHome ? selectedCalendarMatchStats.value.teams.home : selectedCalendarMatchStats.value.teams.away;
    });

    const selectedCalendarOpponentStats = computed(() => {
        if (!selectedCalendarMatchStats.value || !calendarTeam.value || !selectedCalendarMatch.value) return null;
        const isHome = selectedCalendarMatch.value.home_team_id === calendarTeam.value.id;
        return isHome ? selectedCalendarMatchStats.value.teams.away : selectedCalendarMatchStats.value.teams.home;
    });

    const openMatchStats = (match) => {
        if (!match || match.status !== 'played' || !match.match_stats) return;
        selectedCalendarMatchId.value = match.id;
    };

    return {
        isByeMatch, teamById,
        opponentTeamIdFor, opponentNameFor, opponentNameForTeam,
        myMatches, seasonWeeksCount,
        myMatchThisWeek, isByeWeek,
        nextMatch, nextMatchInfo,
        selectedCalendarTeamId, calendarTeams, calendarTeam, selectCalendarTeam,
        calendarTeamMatches, calendarRows, calendarTeamRoster, calendarOpponentRoster,
        selectedCalendarMatchId, selectedCalendarMatch,
        selectedCalendarMyTeamStats, selectedCalendarOpponentStats,
        selectedCalendarMatchStats, selectedCalendarPlayersStats,
        selectedCalendarProgression,
        selectedCalendarEvents,
        openMatchStats,
    };
}
