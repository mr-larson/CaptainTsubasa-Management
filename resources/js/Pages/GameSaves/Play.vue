<script setup>
/**
 * Dashboard de partie Captain Tsubasa
 * - Vue Inertia pour gérer :
 *   - Dashboard général
 *   - Mon équipe (GameTeam + GamePlayers)
 *   - Autres équipes
 *   - Transferts (joueurs libres)
 *   - Calendrier & classement
 */

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import H2 from '@/Components/H2.vue';

// ==========================
//   PROPS INERTIA
// ==========================

const props = defineProps({
    gameSave:   { type: Object, required: true },
    teams:      { type: Array,  required: true }, // GameTeam[] avec contracts.*
    freePlayers:{ type: Array,  required: true }, // GamePlayer[] sans contrat
    matches:    { type: Array,  required: true }, // GameMatch[] avec home_team / away_team
    controlledTeam: { type: Object, required: false, default: null },
});

// ==========================
//   SAISON / SEMAINE
// ==========================

const season = ref(props.gameSave.season || 1);
const week   = ref(props.gameSave.week   || 1);

const currentState = ref(props.gameSave.state || { match: null });
const saving = ref(false);

watch(
    () => props.gameSave.season,
    (v) => { season.value = v ?? 1; },
    { immediate: true }
);

watch(
    () => props.gameSave.week,
    (v) => { week.value = v ?? 1; },
    { immediate: true }
);

// ==========================
//   RACCOURCIS PRINCIPAUX
// ==========================

const team = computed(() => props.controlledTeam || null);

const teamById = computed(() => {
    const map = {};
    (props.teams ?? []).forEach((t) => { map[t.id] = t; });
    return map;
});

const isByeMatch = (match) => {
    if (!match) return false;
    if (match.is_bye === true) return true;
    return !match.home_team_id || !match.away_team_id;
};

const opponentTeamIdFor = (match) => {
    if (!team.value || !match || isByeMatch(match)) return null;
    const isHome = match.home_team_id === team.value.id;
    return isHome ? match.away_team_id : match.home_team_id;
};

const opponentNameFor = (match) => {
    if (!team.value) return '???';
    if (!match || isByeMatch(match)) return 'Repos';
    const opponentId = opponentTeamIdFor(match);
    if (!opponentId) return 'Repos';
    return teamById.value[opponentId]?.name ?? '???';
};

// ✅ Photo URL (GamePlayer)
const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path) return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
};

/**
 * ✅ AJOUT : Logo URL (GameTeam.logo_path stocké en public/images/teams/xxx.webp)
 * logo_path = "images/teams/xxx.webp" => URL = "/images/teams/xxx.webp"
 */
const teamLogoUrl = (team) => {
    // Cas 1 : team directement (Team)
    if (team?.logo_path) {
        return `/storage/${team.logo_path}`;
    }

    // Cas 2 : si Play.vue reçoit GameTeam avec relation team → (Team)
    if (team?.team?.logo_path) {
        return `/storage/${team.team.logo_path}`;
    }

    return null;
};

// ✅ AJOUT : équipe adverse du prochain match (robuste via IDs)
const nextOpponentTeamId = computed(() => {
    if (!team.value || !nextMatch.value) return null;
    return opponentTeamIdFor(nextMatch.value);
});

const nextOpponentTeam = computed(() => {
    const id = nextOpponentTeamId.value;
    if (!id) return null;
    return teamById.value[id] ?? null;
});

const myLogoUrl = computed(() => teamLogoUrl(team.value));
const opponentLogoUrl = computed(() => teamLogoUrl(nextOpponentTeam.value));

/**
 * Effectif = joueurs liés via les game_contracts
 * IMPORTANT : selon ton backend, l'objet peut être :
 * - c.game_player
 * - c.gamePlayer
 * - c.player
 */
const roster = computed(() => {
    if (!team.value || !Array.isArray(team.value.contracts)) return [];
    return team.value.contracts
        .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
        .filter(Boolean);
});

// Roster avec statut titulaire/remplaçant (via GameContract)
const rosterWithStatus = computed(() => {
    if (!team.value || !Array.isArray(team.value.contracts)) return [];
    return team.value.contracts
        .map(c => {
            const p = c.game_player ?? c.gamePlayer ?? c.player ?? null;
            if (!p) return null;

            return {
                ...p,
                contract_id: c.id,
                is_starter: c.is_starter ?? true,
            };
        })
        .filter(Boolean);
});

// Titulaires uniquement
const starters = computed(() =>
    rosterWithStatus.value.filter(p => p.is_starter)
);

// Lineup initiale depuis le state (si déjà enregistrée)
const initialLineupSlots = computed(() => {
    const state = props.gameSave.state ?? {};
    const lineup = state.lineup ?? {};
    if (!team.value) return {};
    return (lineup[team.value.id]?.slots ?? {}) || {};
});

const lineupForm = ref([]);

const getSlotForPlayer = (playerId) => {
    if (!playerId || !Array.isArray(lineupForm.value)) return null;
    const row = lineupForm.value.find(r => r.player_id === playerId);
    return row ? row.slot : null;
};
const initLineupForm = () => {
    const slots = [];

    // 1..11 : on remplit d’abord à partir du state
    for (let slot = 1; slot <= 11; slot++) {
        const mappedPlayerId = initialLineupSlots.value[slot] ?? null;
        slots.push({
            slot,
            player_id: mappedPlayerId,
        });
    }

    // Si aucune compo n'existe, on préremplit avec les titulaires dans l'ordre
    if (!Object.keys(initialLineupSlots.value).length) {
        starters.value.forEach((p, index) => {
            if (index < 11) {
                slots[index].player_id = p.id;
            }
        });
    }

    lineupForm.value = slots;
};

// Init au chargement
initLineupForm();

// Si l'équipe contrôlée ou le state changent (nouvelle save / reload), on réinitialise
watch(
    () => [team.value?.id, props.gameSave.state],
    () => initLineupForm()
);

const changeSelectedPlayerSlot = (newSlotValue) => {
    const player = selectedMyPlayer.value;
    if (!player) return;

    const slotNumber = Number(newSlotValue);
    if (!slotNumber || slotNumber < 1 || slotNumber > 11) return;

    const playerId = player.id;

    // Slot actuel du joueur (s'il en a un)
    const currentSlot = getSlotForPlayer(playerId);

    // Si le slot ne change pas véritablement, on ne fait rien
    if (currentSlot === slotNumber) {
        return;
    }

    // Ligne actuelle (si le joueur a déjà un slot)
    const currentRow = lineupForm.value.find(r => r.player_id === playerId) || null;

    // Ligne ciblée
    const targetRow = lineupForm.value.find(r => r.slot === slotNumber) || null;
    if (!targetRow) return;

    const previousPlayerAtTarget = targetRow.player_id ?? null;

    if (currentRow) {
        // Swap : l'ancien occupant du nouveau slot prend l'ancien slot
        const oldSlot = currentRow.slot;
        currentRow.player_id = previousPlayerAtTarget;
        targetRow.player_id = playerId;
    } else {
        // Le joueur n'avait pas encore de slot -> il prend le nouveau,
        // l'ancien occupant devient "sans slot" (null)
        targetRow.player_id = playerId;
    }

    // ✅ Sauvegarde automatique après changement
    saveLineup();
};

// Donne les infos de rôle + coordonnée sur le mini terrain pour un slot donné (1..11)
function slotRoleInfo(slot) {
    const s = Number(slot);
    switch (s) {
        case 1:
            return { label: 'Gardien', x: 10, y: 50 };
        case 2:
            return { label: 'Défenseur', x: 30, y: 20 };
        case 3:
            return { label: 'Défenseur', x: 30, y: 50 };
        case 4:
            return { label: 'Défenseur', x: 30, y: 80 };
        case 5:
            return { label: 'Milieu défensif', x: 50, y: 35 };
        case 6:
            return { label: 'Milieu défensif', x: 50, y: 65 };
        case 7:
            return { label: 'Milieu offensif', x: 70, y: 20 };
        case 8:
            return { label: 'Milieu offensif', x: 70, y: 50 };
        case 9:
            return { label: 'Milieu offensif', x: 70, y: 80 };
        case 10:
            return { label: 'Attaquant', x: 88, y: 35 };
        case 11:
            return { label: 'Attaquant', x: 88, y: 65 };
        default:
            return { label: '—', x: 50, y: 50 };
    }
}

// Style du marqueur sur le mini terrain (left/top en %)
const miniPitchMarkerStyle = computed(() => {
    const p = selectedMyPlayer.value;
    if (!p) return {};

    const slot = getSlotForPlayer(p.id);
    if (!slot) return {};

    const info = slotRoleInfo(slot);
    return {
        left: `${info.x}%`,
        top: `${info.y}%`,
    };
});
const saveLineup = () => {
    if (!team.value) return;

    router.post(
        route('game-saves.lineup.update', { gameSave: props.gameSave.id }),
        {
            team_id: team.value.id,
            slots: lineupForm.value,
        },
        {
            preserveScroll: true,
        }
    );
};

// Dernier match joué de mon équipe
const lastPlayedMatch = computed(() => {
    return myMatches.value
        .filter(m => m.status === 'played')
        .sort((a,b) => b.week - a.week)[0] || null;
});

// Stats du dernier match (format match_stats)
const lastMatchStats = computed(() => {
    return lastPlayedMatch.value?.match_stats ?? null;
});

const teamStats = computed(() => {
    const t = selectedStatsTeam.value;
    if (!t) {
        return {
            shots: 0,
            passes: 0,
            dribbles: 0,
            intercepts: 0,
            tackles: 0,
            blocks: 0,
            duelsWon: 0,
            duelsLost: 0,
        };
    }

    const allStats = playerSeasonStats.value ?? {};
    const playerIds = (t.contracts ?? [])
        .map(c => c.game_player?.id ?? c.gamePlayer?.id ?? c.player?.id)
        .filter(Boolean);

    const totals = {
        shots: 0,
        passes: 0,
        dribbles: 0,
        intercepts: 0,
        tackles: 0,
        blocks: 0,
        duelsWon: 0,
        duelsLost: 0,
    };

    playerIds.forEach(pid => {
        const s = allStats[pid];
        if (!s) return;

        totals.shots      += s.offense?.shot?.attempts ?? 0;
        totals.passes     += s.offense?.pass?.attempts ?? 0;
        totals.dribbles   += s.offense?.dribble?.attempts ?? 0;
        totals.intercepts += s.defense?.intercept?.attempts ?? 0;
        totals.tackles    += s.defense?.tackle?.attempts ?? 0;
        totals.blocks     += s.defense?.block?.attempts ?? 0;
        totals.duelsWon   += s.duelsWon ?? 0;
        totals.duelsLost  += s.duelsLost ?? 0;
    });

    return totals;
});

// Raccourcis
const myTeamStats = computed(() => {
    if (!lastMatchStats.value || !team.value) return null;
    const isHome = lastPlayedMatch.value.home_team_id === team.value.id;
    return isHome ? lastMatchStats.value.teams.home : lastMatchStats.value.teams.away;
});

const opponentTeamStats = computed(() => {
    if (!lastMatchStats.value || !team.value) return null;
    const isHome = lastPlayedMatch.value.home_team_id === team.value.id;
    return isHome ? lastMatchStats.value.teams.away : lastMatchStats.value.teams.home;
});

// Stats des joueurs du match
const matchPlayersStats = computed(() => {
    return lastMatchStats.value?.players ?? {};
});

// ==========================
//   MON ÉQUIPE - SÉLECTION JOUEUR
// ==========================

const selectedMyPlayerId = ref(null);


const selectedMyPlayer = computed(() => {
    if (!rosterWithStatus.value.length) return null;
    if (!selectedMyPlayerId.value) return rosterWithStatus.value[0];
    return rosterWithStatus.value.find(p => p.id === selectedMyPlayerId.value) ?? rosterWithStatus.value[0];
});

const selectMyPlayer = (player) => { selectedMyPlayerId.value = player.id; };

const overallOf = (player) => {
    if (!player) return 0;

    // Priorité à player.stats si présent, sinon on utilise directement le joueur.
    const s = player.stats ?? player;

    const keys = [
        'speed', 'stamina', 'attack', 'defense',
        'shot', 'pass', 'dribble',
        'block', 'intercept', 'tackle',
        'hand_save', 'punch_save',
    ];

    const values = keys
        .map(k => Number(s[k] ?? 0))
        .filter(v => Number.isFinite(v));

    if (!values.length) return 0;

    return Math.round(values.reduce((a, b) => a + b, 0) / values.length);
};

const toggleStarter = (contractId) => {
    if (!contractId) return;

    router.patch(
        route('game-contracts.toggle-starter', { contract: contractId }),
        {},
        {
            preserveScroll: true,
        }
    );
};

// ==========================
//   BILAN / BUDGET
// ==========================

const teamRecord = computed(() => {
    if (!team.value) return { wins: 0, draws: 0, losses: 0 };
    return {
        wins:   team.value.wins   ?? 0,
        draws:  team.value.draws  ?? 0,
        losses: team.value.losses ?? 0,
    };
});

const teamBudget = computed(() => team.value?.budget ?? 0);

// ==========================
//   ONGLET & NAVIGATION
// ==========================

const tabs = [
    { key: 'dashboard',   label: 'Dashboard' },
    { key: 'my-team',     label: 'Mon équipe' },
    { key: 'other-teams', label: 'Autres équipes' },
    { key: 'calendar',    label: 'Calendrier' },
    { key: 'standings',   label: 'Classement' },
    { key: 'match-stats',    label: 'Stats' },
    { key: 'training',    label: 'Entraînement' },
    { key: 'transfers',   label: 'Transferts' },
    { key: 'cards',       label: 'Cartes bonus' },
    { key: 'management',  label: 'Gestion' },
];

const activeTab = ref('dashboard');

// ==========================
//   CALENDRIER & MATCHS
// ==========================

const myMatches = computed(() => {
    if (!team.value) return [];
    return (props.matches ?? [])
        .filter((m) => m.home_team_id === team.value.id || m.away_team_id === team.value.id)
        .sort((a, b) => (a.week ?? 0) - (b.week ?? 0));
});

const seasonWeeksCount = computed(() => {
    const n = props.teams?.length ?? 0;
    if (n < 2) return 0;
    return (n % 2 === 1) ? (n * 2) : ((n - 1) * 2);
});

// ==========================
//   CALENDRIER - ÉQUIPE SÉLECTIONNÉE
// ==========================

const selectedCalendarTeamId = ref(null);
const calendarTeams = computed(() => props.teams ?? []);

const calendarTeam = computed(() => {
    if (!calendarTeams.value.length) return null;

    if (!selectedCalendarTeamId.value) {
        return team.value || calendarTeams.value[0];
    }

    const id = Number(selectedCalendarTeamId.value);
    return calendarTeams.value.find(t => Number(t.id) === id) ?? (team.value || calendarTeams.value[0]);
});

// ✅ Match sélectionné dans le calendrier pour afficher ses stats
const selectedCalendarMatchId = ref(null);

const selectedCalendarMatch = computed(() => {
    if (!selectedCalendarMatchId.value) return null;
    return calendarTeamMatches.value.find(m => m.id === selectedCalendarMatchId.value) ?? null;
});

const selectedCalendarMatchStats = computed(() => {
    return selectedCalendarMatch.value?.match_stats ?? null;
});

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

const selectedCalendarPlayersStats = computed(() => {
    return selectedCalendarMatchStats.value?.players ?? {};
});

// Roster de l'équipe utilisée dans l'onglet calendrier (comme roster mais pour calendarTeam)
const calendarTeamRoster = computed(() => {
    if (!calendarTeam.value || !Array.isArray(calendarTeam.value.contracts)) return [];
    return calendarTeam.value.contracts
        .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
        .filter(Boolean);
});

// Handler pour ouvrir les stats d'un match
const openMatchStats = (match) => {
    if (!match || match.status !== 'played' || !match.match_stats) return;
    selectedCalendarMatchId.value = match.id;
};

const selectCalendarTeam = (t) => { selectedCalendarTeamId.value = t.id; };

const calendarTeamMatches = computed(() => {
    if (!calendarTeam.value) return [];
    return (props.matches ?? [])
        .filter(m => m.home_team_id === calendarTeam.value.id || m.away_team_id === calendarTeam.value.id)
        .sort((a, b) => (a.week ?? 0) - (b.week ?? 0));
});

const calendarRows = computed(() => {
    if (!calendarTeam.value) return [];

    const byWeek = new Map();
    calendarTeamMatches.value.forEach(m => byWeek.set(Number(m.week), m));

    const totalWeeks = seasonWeeksCount.value || 0;
    const rows = [];

    for (let w = 1; w <= totalWeeks; w++) {
        const match = byWeek.get(w);
        if (match) rows.push(match);
        else {
            rows.push({
                id: `bye-${calendarTeam.value.id}-${w}`,
                week: w,
                is_bye: true,
                status: 'bye',
                home_team_id: null,
                away_team_id: null,
            });
        }
    }
    return rows;
});

const myMatchThisWeek = computed(() => {
    if (!team.value) return null;
    const w = week.value ?? 1;

    return myMatches.value.find(m =>
        m.week === w &&
        !isByeMatch(m) &&
        (m.status === 'scheduled' || m.status === 'played')
    ) ?? null;
});

const isByeWeek = computed(() => {
    if (!team.value) return false;
    return !myMatchThisWeek.value;
});

// ==========================
//   ACTIONS / PERFORMANCES
// ==========================

// Toutes les actions stockées dans gameSave.state.player_actions (array)
const playerActions = computed(() => props.gameSave.state?.player_actions ?? []);

// Actions impliquant un joueur donné (attaque ou défense)
const actionsForPlayer = (playerId) => {
    if (!playerId) return [];
    return playerActions.value.filter(ev =>
        ev.attack?.game_player_id === playerId ||
        ev.defense?.game_player_id === playerId
    );
};

// Actions du joueur actuellement sélectionné (Mon équipe)
const selectedMyPlayerActions = computed(() => {
    if (!selectedMyPlayer.value) return [];
    return actionsForPlayer(selectedMyPlayer.value.id);
});


const playerSeasonStats = computed(() => props.gameSave.state?.player_stats ?? {});

const selectedMyPlayerPerf = computed(() => {

    const p = selectedMyPlayer.value;
    if (!p) return null;

    // Si les stats cumulées existent déjà : on les utilise
    const s = playerSeasonStats.value[p.id];
    if (s) return s;

    // Sinon, fallback sur l'ancien calcul depuis playerActions (optionnel)
    const events = selectedMyPlayerActions.value;
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
        duelsWon: 0,
        duelsLost: 0,
    };

    for (const ev of events) {
        const isAttacker = ev.attack?.game_player_id === p.id;
        const isDefender = ev.defense?.game_player_id === p.id;

        const result = ev.result; // "attack" | "defense" | "tie"

        // ============
        // 🟦 OFFENSIF
        // ============
        if (isAttacker) {
            const type = ev.attack.action;
            if (stats.offense[type]) {
                stats.offense[type].attempts++;
                if (result === "attack") stats.offense[type].success++;
            }

            if (result === "attack") stats.duelsWon++;
            else if (result === "defense") stats.duelsLost++;
        }

        // ============
        // 🟥 DÉFENSIF
        // ============
        if (isDefender) {
            const def = ev.defense.action; // ex: "intercept", "tackle", "block", "hands", "punch", "gk-special"

            const map = {
                "intercept": "intercept",
                "tackle": "tackle",
                "block": "block",
                "hands": "hands",
                "punch": "punch",
                "gk-special": "gkSpecial",
            };

            const key = map[def];
            if (key && stats.defense[key]) {
                stats.defense[key].attempts++;
                if (result === "defense") stats.defense[key].success++;
            }

            if (result === "defense") stats.duelsWon++;
            else if (result === "attack") stats.duelsLost++;
        }
    }

    return stats;
});

const simulateWeek = () => {
    router.post(
        route('game-saves.simulate-week', { gameSave: props.gameSave.id }),
        {},
        {
            preserveScroll: true,
            preserveState: false,
        }
    );
};


const nextMatch = computed(() => {
    if (!team.value || !myMatches.value.length) return null;

    const currentWeek = week.value ?? 1;

    const candidates = myMatches.value
        .filter(m => m.status === 'scheduled' && m.week >= currentWeek)
        .sort((a, b) => a.week - b.week);

    return candidates[0] ?? null;
});

const nextMatchInfo = computed(() => {
    if (!nextMatch.value || !team.value) return null;

    const isHome = nextMatch.value.home_team_id === team.value.id;
    const opponent = isHome ? nextMatch.value.away_team : nextMatch.value.home_team;

    return {
        isHome,
        opponentName: opponent?.name ?? opponentNameFor(nextMatch.value),
        week: nextMatch.value.week,
    };
});

const opponentNameForTeam = (match, teamId) => {
    if (!match || !teamId) return '???';
    if (match.is_bye === true) return 'Repos';

    const isHome = match.home_team_id === teamId;
    const oppId  = isHome ? match.away_team_id : match.home_team_id;

    return teamById.value[oppId]?.name ?? '???';
};

// ==========================
//   AUTRES EQUIPES
// ==========================

const otherTeams = computed(() => {
    const currentId = team.value?.id ?? null;
    return (props.teams ?? []).filter(t => t.id !== currentId);
});

const selectedOtherTeamId = ref(null);

const selectedOtherTeam = computed(() => {
    if (!otherTeams.value.length) return null;
    if (!selectedOtherTeamId.value) return otherTeams.value[0];
    return otherTeams.value.find(t => t.id === selectedOtherTeamId.value) ?? otherTeams.value[0];
});

const selectedOtherTeamRoster = computed(() => {
    if (!selectedOtherTeam.value || !Array.isArray(selectedOtherTeam.value.contracts)) return [];
    return selectedOtherTeam.value.contracts
        .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
        .filter(Boolean);
});

const selectOtherTeam = (t) => { selectedOtherTeamId.value = t.id; };

// ==========================
//   HELPERS AUTRES ÉQUIPES
// ==========================

const statValueFor = (p, key) => {
    if (!p) return 0;
    const stats = p.stats ?? {};
    const value = p[key] ?? stats[key] ?? 0;
    return Number(value || 0);
};

const averageTeamStat = (players, key) => {
    if (!Array.isArray(players) || players.length === 0) return 0;
    const total = players.reduce((sum, p) => sum + statValueFor(p, key), 0);
    return Math.round(total / players.length);
};

// si dans state tu stockes team_id => on filtre, sinon fallback sur le total global
const countByTeamIdOrAll = (items, teamId) => {
    if (!Array.isArray(items)) return 0;
    const hasTeamId = items.some(x => x && Object.prototype.hasOwnProperty.call(x, 'team_id'));
    if (!hasTeamId) return items.length;
    return items.filter(x => Number(x.team_id) === Number(teamId)).length;
};

const injuriesCountForTeam = (teamId) => countByTeamIdOrAll(injuries.value, teamId);
const suspensionsCountForTeam = (teamId) => countByTeamIdOrAll(suspensions.value, teamId);
const cardsCountForTeam = (teamId) => countByTeamIdOrAll(cards.value, teamId);

// ==========================
//   CLASSEMENT
// ==========================

const standings = computed(() => {
    const list = (props.teams ?? []).map((t) => {
        const wins   = t.wins   ?? 0;
        const draws  = t.draws  ?? 0;
        const losses = t.losses ?? 0;
        const played = wins + draws + losses;
        const points = wins * 3 + draws;

        return { ...t, wins, draws, losses, played, points };
    });

    list.sort((a, b) => {
        if (b.points !== a.points) return b.points - a.points;
        if (b.wins !== a.wins)     return b.wins - a.wins;
        return a.name.localeCompare(b.name);
    });

    return list;
});

const clubStanding = computed(() => {
    if (!team.value) return null;

    const index = standings.value.findIndex(row => row.id === team.value.id);
    if (index === -1) return null;

    return { ...standings.value[index], position: index + 1 };
});

// ==========================
//   BLESSURES / CARTONS
// ==========================

const injuries    = computed(() => props.gameSave.state?.injuries    ?? []);
const suspensions = computed(() => props.gameSave.state?.suspensions ?? []);
const cards       = computed(() => props.gameSave.state?.cards       ?? []);

const injuriesCount    = computed(() => injuries.value.length);
const suspensionsCount = computed(() => suspensions.value.length);
const cardsCount       = computed(() => cards.value.length);

// ==========================
//   MOYENNES DES STATS
// ==========================
const selectedStatsTeamId = ref(null);

const selectedStatsTeam = computed(() => {
    if (!props.teams?.length) return null;

    if (!selectedStatsTeamId.value) {
        // par défaut : ton équipe contrôlée
        return team.value || props.teams[0];
    }

    const id = Number(selectedStatsTeamId.value);
    return props.teams.find(t => Number(t.id) === id) ?? (team.value || props.teams[0]);
});
const statValue = (p, key) => {
    if (!p) return 0;
    const stats = p.stats ?? {};
    const value = p[key] ?? stats[key] ?? 0;
    return Number(value || 0);
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

// ==========================
//   MODAL TRANSFERT
// ==========================

const freeAgentSignings = computed(() => props.gameSave.state?.free_agent_signings ?? []);
const signedFreePlayerIds = computed(() => freeAgentSignings.value.map(s => s.player_id));

const freePlayers = computed(() => {
    if (!Array.isArray(props.freePlayers)) return [];
    return props.freePlayers.filter((p) => !signedFreePlayerIds.value.includes(p.id));
});

const showTransferModal = ref(false);
const transferTarget    = ref(null);
const transferMatches   = ref(10);
const transferSalary    = ref(0);
const transferReason    = ref('');

const transferTotalCost = computed(() => {
    return (Number(transferMatches.value) || 0) * (Number(transferSalary.value) || 0);
});

const openTransferModal = (player) => {
    transferTarget.value  = player;
    transferMatches.value = 10;
    transferSalary.value  = player.cost ?? 0;
    transferReason.value  = '';
    showTransferModal.value = true;
};

const closeTransferModal = () => {
    showTransferModal.value = false;
    transferTarget.value    = null;
};

const confirmTransfer = () => {
    if (!team.value || !transferTarget.value) return;

    router.post(
        route('game-saves.free-agents.sign', {
            gameSave: props.gameSave.id,
            player:   transferTarget.value.id,
        }),
        {
            team_id:       team.value.id,
            salary:        transferSalary.value,
            matches_total: transferMatches.value,
            reason:        transferReason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => closeTransferModal(),
        }
    );
};

// ==========================
//   ENTRAÎNEMENT
// ==========================

const trainingState = computed(() => props.gameSave.state?.training ?? null);

const remainingTrainingsThisWeek = computed(() => {
    const max = 3; // correspond à config('training.max_trainings_per_week')
    const s = trainingState.value;

    if (!s) return max;
    if (s.season !== season.value || s.week !== week.value) return max;

    const used = Array.isArray(s.entries) ? s.entries.length : 0;
    return Math.max(0, max - used);
});

const hasPlayerBeenTrainedThisWeek = (playerId) => {
    const s = trainingState.value;
    if (!s) return false;
    if (s.season !== season.value || s.week !== week.value) return false;
    if (!Array.isArray(s.entries)) return false;
    return s.entries.some(e => Number(e.player_id) === Number(playerId));
};

// Sélection locale des entraînements à envoyer (max 3)
const availableTrainingStats = [
    { key: 'shot',       label: 'Tir' },
    { key: 'pass',       label: 'Passe' },
    { key: 'dribble',    label: 'Dribble' },
    { key: 'attack',     label: 'Attaque' },
    { key: 'defense',    label: 'Défense' },
    { key: 'speed',      label: 'Vitesse' },
    { key: 'block',      label: 'Block' },
    { key: 'intercept',  label: 'Interception' },
    { key: 'tackle',     label: 'Tacle' },
    { key: 'hand_save',  label: 'Arrêt main' },
    { key: 'punch_save', label: 'Arrêt poings' },
];

const selectedTrainings = ref([
    // Exemple d’entrée: { player_id: 12, stat: 'shot' }
]);
const selectedTeamPlayerStats = computed(() => {
    const t = selectedStatsTeam.value;
    if (!t || !Array.isArray(t.contracts)) return [];

    const allStats = playerSeasonStats.value ?? {};

    return t.contracts
        .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
        .filter(Boolean)
        .map(p => ({
            ...p,
            stats: allStats[p.id] ?? null,
        }));
});

const addTrainingSlot = () => {
    if (selectedTrainings.value.length >= 3) return;
    selectedTrainings.value.push({ player_id: null, stat: 'shot' });
};

const removeTrainingSlot = (index) => {
    selectedTrainings.value.splice(index, 1);
};

const canSubmitTraining = computed(() => {
    if (remainingTrainingsThisWeek.value <= 0) return false;
    if (!selectedTrainings.value.length) return false;

    const filtered = selectedTrainings.value.filter(t => t.player_id && t.stat);
    if (!filtered.length) return false;

    // Joueurs distincts dans la sélection locale
    const ids = filtered.map(t => t.player_id);
    const uniqueIds = new Set(ids);
    if (uniqueIds.size !== ids.length) return false;

    return true;
});

const submitTraining = () => {
    const payloadTrainings = selectedTrainings.value
        .filter(t => t.player_id && t.stat)
        .slice(0, remainingTrainingsThisWeek.value); // on ne dépasse pas la limite

    if (!payloadTrainings.length) return;

    router.post(
        route('game-saves.training.store', { gameSave: props.gameSave.id }),
        {
            season: season.value,
            week: week.value,
            trainings: payloadTrainings,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Reset la sélection après succès
                selectedTrainings.value = [];
            },
        }
    );
};

// ==========================
//   GESTION (onglet management)
// ==========================
const managementSections = [
    { key: 'dataBase',  label: 'Base de données' },
    { key: 'profil',    label: 'Profil' },
    { key: 'config',   label: 'Configuration' },
];


const activeManagementSection = ref('dataBase');

const goToManagementSection = (key) => {
    activeManagementSection.value = key;
};

// ==========================
//   HELPERS GÉNÉRAUX
// ==========================

const periodLabel = (period) => {
    if (period === 'college')    return 'Collège';
    if (period === 'highschool') return 'Lycée';
    if (period === 'pro')        return 'Professionnel';
    return period;
};

const saveGame = () => {
    saving.value = true;

    router.put(
        route('game-saves.update', props.gameSave.id),
        {
            label:  props.gameSave.label,
            season: season.value,
            week:   week.value,
            state:  currentState.value,
        },
        {
            preserveState:  true,
            preserveScroll: true,
            onFinish: () => { saving.value = false; },
        }
    );
};

const playNextMatch = () => {
    router.get(route('game-saves.match', { gameSave: props.gameSave.id }));
};
</script>

<template>
    <Head :title="`Partie ${gameSave.label ?? '#' + gameSave.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <H2>
                Partie : {{ gameSave.label ?? `Sauvegarde #${gameSave.id}` }}
            </H2>
        </template>

        <div class="p-4">
            <!-- Titre -->
            <div class="flex justify-center mb-6">
                <h1 class="text-3xl font-bold text-slate-600">
                    Session de jeu
                </h1>
            </div>

            <div class="flex flex-row">
                <!-- Visuel gauche -->
                <div
                    class="hidden md:block basis-1/4 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/wakabayashi.webp')"
                ></div>

                <!-- Carte principale -->
                <div class="basis-3/4 p-4 border border-slate-300 rounded-lg mx-6 bg-white min-h-[500px] flex flex-col">
                    <!-- Onglets -->
                    <div class="mb-4 border-b border-slate-200">
                        <nav class="-mb-px flex space-x-2 overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                @click="activeTab = tab.key"
                                :class="[
                                    'whitespace-nowrap py-2 px-4 text-sm font-medium border-b-2 rounded-t-md',
                                    activeTab === tab.key
                                        ? 'border-teal-500 text-slate-900 bg-slate-50'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                ]"
                            >
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <!-- ============================== -->
                    <!--            DASHBOARD           -->
                    <!-- ============================== -->
                    <div v-if="activeTab === 'dashboard'" class="flex-1 flex flex-col">
                        <!-- Infos générales -->
                        <div class="mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-around gap-2">
                            <p class="text-slate-600">
                                Période :
                                <span class="font-semibold">{{ periodLabel(gameSave.period) }}</span>
                            </p>
                            <p class="text-slate-600">Saison {{ season }} — Semaine {{ week }}</p>
                            <p class="text-slate-600" v-if="team">
                                Équipe contrôlée : <span class="font-semibold">{{ team.name }}</span>
                            </p>
                        </div>

                        <!-- Cartes du dashboard -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1">
                            <!-- Prochain match -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                                <!-- header: titre à gauche, logo à droite -->
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <div v-if="nextMatch && nextMatchInfo" class="text-sm text-slate-700">
                                        <h3 class="text-lg font-semibold text-slate-700">Prochain match</h3>
                                        <ul class="space-y-1">
                                            <li><span class="font-semibold">Semaine :</span> {{ isByeWeek ? week : nextMatchInfo.week }}</li>
                                            <li><span class="font-semibold">Adversaire :</span> {{ opponentNameFor(nextMatch) }}</li>
                                            <li><span class="font-semibold">Lieu :</span> {{ nextMatchInfo.isHome ? 'Domicile' : 'Extérieur' }}</li>
                                            <li><span class="font-semibold">Contexte :</span> Saison {{ season }} — Championnat</li>
                                        </ul>
                                    </div>
                                    <div v-else class="text-sm text-slate-600">
                                        <p class="mb-2">Aucun match de championnat planifié pour le moment.</p>
                                        <p class="text-xs text-slate-500">Vérifie que le calendrier a bien été généré pour cette sauvegarde.</p>
                                    </div>
                                    <!-- ✅ Logo adversaire à droite (case rouge) -->
                                    <div class="h-24 w-24 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                                        <img
                                            v-if="nextMatch && opponentTeamIdFor(nextMatch)"
                                            :src="teamLogoUrl(teamById[opponentTeamIdFor(nextMatch)])"
                                            class="h-full w-full object-contain"
                                            alt="Logo adversaire"
                                        />
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-6 flex justify-center gap-3">
                                    <button
                                        v-if="!isByeWeek"
                                        type="button"
                                        class="w-60 bg-teal-300 hover:bg-teal-400 text-center font-semibold py-1 px-5 border-2 border-teal-500 rounded-full drop-shadow-md"
                                        @click="playNextMatch"
                                    >
                                        Jouer le prochain match
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        class="w-60 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-1 px-5 border-2 border-slate-500 rounded-full drop-shadow-md"
                                        @click="simulateWeek"
                                    >
                                        Simuler la semaine
                                    </button>
                                </div>
                            </div>

                            <!-- Résumé du club -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                                <!-- Ligne haute : texte + logo -->
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <div class="text-sm text-slate-700">
                                        <h3 class="text-lg font-semibold text-slate-700 mb-1">
                                            Résumé du club
                                        </h3>

                                        <p><span class="font-semibold">Nom :</span> {{ team.name }}</p>
                                        <p><span class="font-semibold">Budget :</span> {{ teamBudget }} €</p>
                                        <p><span class="font-semibold">Joueurs sous contrat :</span> {{ roster.length }}</p>
                                    </div>

                                    <!-- Logo équipe à droite -->
                                    <div class="h-24 w-24 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                                        <img
                                            v-if="teamLogoUrl(team)"
                                            :src="teamLogoUrl(team)"
                                            class="h-full w-full object-contain"
                                            alt="Logo équipe"
                                        />
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </div>
                                </div>

                                <!-- Description descendue (équivalent du bouton dans l’autre carte) -->
                                <div v-if="team.description" class="mt-6 text-sm text-slate-700">
                                    <span class="text-slate-600">{{ team.description }}</span>
                                </div>
                            </div>


                            <!-- Statut du club -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 lg:col-span-2">
                                <h3 class="text-lg font-semibold text-slate-700 mb-2">Statut du club</h3>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">
                                    <div>
                                        <p class="font-semibold mb-1">Classement</p>
                                        <p v-if="clubStanding">
                                            Place : {{ clubStanding.position }}<sup>e</sup> / {{ standings.length }}
                                        </p>
                                        <p v-else>Classement non disponible.</p>

                                        <p class="mt-1">
                                            Bilan : {{ teamRecord.wins }} V / {{ teamRecord.draws }} N / {{ teamRecord.losses }} D
                                        </p>
                                        <p>Matchs joués : {{ teamRecord.wins + teamRecord.draws + teamRecord.losses }}</p>
                                    </div>

                                    <div>
                                        <p class="font-semibold mb-1">Forces moyennes de l’équipe</p>
                                        <p>Attaque : {{ averageAttack }}</p>
                                        <p>Défense : {{ averageDefense }}</p>
                                        <p>Endurance : {{ averageStamina }}</p>
                                    </div>

                                    <div>
                                        <p class="font-semibold mb-1">État de l’effectif</p>
                                        <p>Blessés : {{ injuriesCount }}</p>
                                        <p>Suspensions : {{ suspensionsCount }}</p>
                                        <p>Cartons en cours : {{ cardsCount }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons bas -->
                        <div class="flex justify-around mt-6">
                            <button
                                type="button"
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-1 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="saving"
                                @click="saveGame"
                            >
                                {{ saving ? 'Sauvegarde...' : 'Sauvegarder' }}
                            </button>

                            <button
                                type="button"
                                class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-1 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2"
                                @click="$inertia.visit(route('mainMenu'))"
                            >
                                Quitter
                            </button>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--        MON ÉQUIPE              -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'my-team'" class="flex-1 grid grid-cols-12 gap-6">

                        <!-- ========================== -->
                        <!--   Colonne gauche (joueurs) -->
                        <!-- ========================== -->
                        <div class="col-span-3 border border-slate-200 rounded-lg bg-slate-50 p-3 self-start">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">Joueurs</h3>

                            <div v-if="rosterWithStatus.length" class="max-h-96 overflow-y-auto space-y-1">
                                <button
                                    v-for="p in rosterWithStatus"
                                    :key="p.id"
                                    type="button"
                                    @click="selectMyPlayer(p)"
                                    :class="[
            'w-full text-left text-sm px-2 py-1 rounded',
            selectedMyPlayer && selectedMyPlayer.id === p.id
                ? 'bg-teal-100 text-slate-900'
                : 'bg-white hover:bg-slate-100 text-slate-700'
        ]"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <div class="flex flex-col">
                <span class="truncate">
                    {{ p.firstname }} {{ p.lastname }}
                </span>
                                            <span class="text-[11px] text-slate-500">
                    {{ p.position }}
                </span>
                                        </div>

                                        <span
                                            class="ml-2 shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                            :class="p.is_starter ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'"
                                        >
                {{ p.is_starter ? 'Titulaire' : 'Remplaçant' }}
            </span>
                                    </div>
                                </button>
                            </div>

                            <p v-else class="text-sm text-slate-500">Aucun joueur sous contrat.</p>
                        </div>


                        <!-- ========================== -->
                        <!--  Colonne droite (profil + stats) -->
                        <!-- ========================== -->
                        <div class="col-span-9 flex flex-col gap-6">

                            <template v-if="selectedMyPlayer">

                                <!-- ========================== -->
                                <!-- Carte Profil complète -->
                                <!-- ========================== -->
                                <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                                    <div class="flex items-start gap-6">

                                        <!-- PHOTO + BADGE OVERALL -->
                                        <div class="relative w-24 h-24 rounded-lg border border-slate-200 bg-white overflow-hidden flex items-center justify-center">
                                            <img
                                                v-if="playerPhotoUrl(selectedMyPlayer)"
                                                :src="playerPhotoUrl(selectedMyPlayer)"
                                                class="h-full w-full object-cover"
                                                alt="Photo joueur"
                                            />
                                            <span v-else class="text-xs text-slate-400 text-center px-2">
                Aucune<br>photo
            </span>

                                            <!-- ⭐ Badge Overall - coin supérieur gauche -->
                                            <div
                                                v-if="overallOf(selectedMyPlayer) > 0"
                                                :class="[
                    'absolute -top-2 -left-2 h-9 w-9 rounded-full border-2 border-white flex items-center justify-center shadow-md',
                    overallOf(selectedMyPlayer) >= 80
                        ? 'bg-emerald-600'
                        : overallOf(selectedMyPlayer) >= 70
                            ? 'bg-emerald-500'
                            : overallOf(selectedMyPlayer) >= 60
                                ? 'bg-teal-500'
                                : 'bg-slate-500'
                ]"
                                            >
                <span class="text-xs font-extrabold text-white">
                    {{ overallOf(selectedMyPlayer) }}
                </span>
                                            </div>
                                        </div>

                                        <!-- TEXTE + ACTIONS -->
                                        <div class="flex-1 flex flex-col gap-2">

                                            <!-- NOM -->
                                            <h3 class="text-lg font-semibold text-slate-800">
                                                {{ selectedMyPlayer.firstname }} {{ selectedMyPlayer.lastname }}
                                            </h3>

                                            <!-- POSTE + COÛT -->
                                            <p class="text-sm text-slate-600">
                                                Poste :
                                                <span class="font-semibold">{{ selectedMyPlayer.position }}</span>
                                                <span class="text-slate-400 mx-2">•</span>
                                                Coût :
                                                <span class="font-semibold">{{ selectedMyPlayer.cost ?? 0 }} €</span>
                                            </p>

                                            <!-- Titulaire / Remplaçant + Slot -->
                                            <div class="mt-2 flex flex-wrap items-center justify-between gap-3">

                                                <!-- Bouton Titulaire -->
                                                <button
                                                    v-if="selectedMyPlayer.contract_id"
                                                    type="button"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm"
                                                    :class="selectedMyPlayer.is_starter
                        ? 'bg-emerald-500 text-white hover:bg-emerald-600'
                        : 'bg-slate-300 text-slate-800 hover:bg-slate-400'"
                                                    @click="toggleStarter(selectedMyPlayer.contract_id)"
                                                >
                                                    {{ selectedMyPlayer.is_starter ? 'Titulaire' : 'Remplaçant' }}
                                                </button>

                                                <!-- Sélecteur de slot -->
                                                <div class="flex items-center gap-2 ml-auto">
                                                    <label class="text-[11px] text-slate-500">Slot terrain</label>

                                                    <select
                                                        :value="getSlotForPlayer(selectedMyPlayer.id) ?? ''"
                                                        :disabled="!selectedMyPlayer.is_starter"
                                                        @change="changeSelectedPlayerSlot($event.target.value)"
                                                        class="border border-slate-300 rounded-md px-2 py-1 text-xs bg-white
                               disabled:bg-slate-100 disabled:text-slate-400
                               focus:ring focus:ring-teal-200"
                                                    >
                                                        <option value="">
                                                            {{ selectedMyPlayer.is_starter ? '(non assigné)' : 'Titulaire requis' }}
                                                        </option>

                                                        <option v-for="n in 11" :key="n" :value="n">
                                                            {{ n }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- DESCRIPTION -->
                                            <p v-if="selectedMyPlayer.description" class="mt-3 text-sm text-slate-700">
                                                <span class="text-slate-600">{{ selectedMyPlayer.description }}</span>
                                            </p>
                                            <p v-else class="mt-3 text-sm text-slate-400 italic">
                                                Aucune description.
                                            </p>
                                        </div>

                                        <!-- ========================== -->
                                        <!--  MINI TERRAIN (DROITE)    -->
                                        <!-- ========================== -->
                                        <div class="w-40 h-32 relative rounded-lg overflow-hidden border border-slate-300 shadow-sm">

                                            <!-- Fond terrain cohérent avec ton exemple -->
                                            <div class="absolute inset-0 bg-gradient-to-r from-green-700 via-green-600 to-green-700"></div>

                                            <!-- Bandes verticales -->
                                            <div
                                                v-for="i in 6"
                                                :key="i"
                                                class="absolute top-0 h-full w-[16.66%] bg-green-800/20"
                                                :style="{ left: ((i-1)*16.66)+'%' }"
                                            ></div>

                                            <!-- Lignes blanches -->
                                            <div class="absolute inset-0 border-2 border-white/70 pointer-events-none"></div>

                                            <!-- Ligne médiane -->
                                            <div class="absolute left-1/2 top-0 h-full w-px bg-white/60"></div>

                                            <!-- Cercle central -->
                                            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2
                        w-16 h-16 rounded-full border border-white/70"></div>

                                            <!-- Surface gauche -->
                                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-8 h-24 border border-white/60"></div>
                                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-4 h-12 border border-white/70"></div>

                                            <!-- Surface droite -->
                                            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-8 h-24 border border-white/60"></div>
                                            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-12 border border-white/70"></div>

                                            <!-- Marqueur du joueur -->
                                            <div
                                                v-if="selectedMyPlayer.is_starter && getSlotForPlayer(selectedMyPlayer.id)"
                                                class="absolute h-5 w-5 rounded-full border-2 border-white bg-yellow-300 shadow-md flex items-center justify-center text-[10px] font-bold"
                                                :style="miniPitchMarkerStyle"
                                            >
                                                {{ getSlotForPlayer(selectedMyPlayer.id) }}
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- ========================== -->
                                <!-- Carte Statistiques -->
                                <!-- ========================== -->
                                <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                                    <h4 class="text-md font-semibold text-slate-700 mb-3">Statistiques</h4>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Vitesse</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.speed ?? selectedMyPlayer.stats?.speed ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Stamina</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.stamina ?? selectedMyPlayer.stats?.stamina ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Attaque</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.attack ?? selectedMyPlayer.stats?.attack ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Défense</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.defense ?? selectedMyPlayer.stats?.defense ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Tir</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.shot ?? selectedMyPlayer.stats?.shot ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Passe</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.pass ?? selectedMyPlayer.stats?.pass ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Dribble</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.dribble ?? selectedMyPlayer.stats?.dribble ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Block</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.block ?? selectedMyPlayer.stats?.block ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Interception</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.intercept ?? selectedMyPlayer.stats?.intercept ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Tacle</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.tackle ?? selectedMyPlayer.stats?.tackle ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Arrêt main</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.hand_save ?? selectedMyPlayer.stats?.hand_save ?? '-' }}</span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Arrêt poings</span>
                                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer.punch_save ?? selectedMyPlayer.stats?.punch_save ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                            </template>

                            <p v-else class="text-sm text-slate-500">Sélectionne un joueur dans la liste à gauche.</p>
                        </div>


                        <!-- ========================== -->
                        <!-- Bloc Historique FULL WIDTH -->
                        <!-- ========================== -->
                        <div class="col-span-12 border border-slate-200 rounded-lg bg-slate-50 p-6 mt-2">
                            <h4 class="text-md font-semibold text-slate-800 mb-4">📊 Historique & performance</h4>

                            <template v-if="selectedMyPlayerPerf">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-slate-700">

                                    <!-- OFFENSIF -->
                                    <div>
                                        <p class="font-semibold mb-2 flex items-center gap-1">
                                            <span>⚽</span> Actions offensives
                                        </p>
                                        <ul class="space-y-1">
                                            <li><span class="font-medium">Tirs :</span>
                                                {{ selectedMyPlayerPerf.offense.shot.attempts }},
                                                {{ selectedMyPlayerPerf.offense.shot.success }} réussis
                                            </li>

                                            <li><span class="font-medium">Passes :</span>
                                                {{ selectedMyPlayerPerf.offense.pass.attempts }},
                                                {{ selectedMyPlayerPerf.offense.pass.success }} réussies
                                            </li>

                                            <li><span class="font-medium">Dribbles :</span>
                                                {{ selectedMyPlayerPerf.offense.dribble.attempts }},
                                                {{ selectedMyPlayerPerf.offense.dribble.success }} réussis
                                            </li>

                                            <li><span class="font-medium">Spéciaux :</span>
                                                {{ selectedMyPlayerPerf.offense.special.attempts }},
                                                {{ selectedMyPlayerPerf.offense.special.success }} réussis
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- DEFENSE -->
                                    <div>
                                        <p class="font-semibold mb-2 flex items-center gap-1">
                                            <span>🛡️</span> Actions défensives
                                        </p>
                                        <ul class="space-y-1">
                                            <li><span class="font-medium">Interceptions :</span>
                                                {{ selectedMyPlayerPerf.defense.intercept.attempts }},
                                                {{ selectedMyPlayerPerf.defense.intercept.success }} réussies
                                            </li>

                                            <li><span class="font-medium">Tacles :</span>
                                                {{ selectedMyPlayerPerf.defense.tackle.attempts }},
                                                {{ selectedMyPlayerPerf.defense.tackle.success }} réussis
                                            </li>

                                            <li><span class="font-medium">Blocks :</span>
                                                {{ selectedMyPlayerPerf.defense.block.attempts }},
                                                {{ selectedMyPlayerPerf.defense.block.success }} réussis
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- GK -->
                                    <div>
                                        <p class="font-semibold mb-2 flex items-center gap-1">
                                            <span>🧤</span> Actions gardien
                                        </p>
                                        <ul class="space-y-1">
                                            <li><span class="font-medium">Arrêts main :</span>
                                                {{ selectedMyPlayerPerf.defense.hands.attempts }},
                                                {{ selectedMyPlayerPerf.defense.hands.success }} réussis
                                            </li>

                                            <li><span class="font-medium">Poings :</span>
                                                {{ selectedMyPlayerPerf.defense.punch.attempts }},
                                                {{ selectedMyPlayerPerf.defense.punch.success }} réussis
                                            </li>

                                            <li><span class="font-medium">Special GK :</span>
                                                {{ selectedMyPlayerPerf.defense.gkSpecial.attempts }},
                                                {{ selectedMyPlayerPerf.defense.gkSpecial.success }} réussis
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- DUELS -->
                                    <div>
                                        <p class="font-semibold mb-2 flex items-center gap-1">
                                            <span>⚔️</span> Duels
                                        </p>
                                        <ul class="space-y-1">
                                            <li>
                                                <span class="font-medium">Duels gagnés :</span>
                                                {{ selectedMyPlayerPerf.duelsWon }}
                                            </li>
                                            <li>
                                                <span class="font-medium">Duels perdus :</span>
                                                {{ selectedMyPlayerPerf.duelsLost }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </template>

                            <p v-else class="text-sm text-slate-500">
                                Aucune action enregistrée pour ce joueur dans cette sauvegarde.
                            </p>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--        AUTRES ÉQUIPES          -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'other-teams'" class="flex-1 grid grid-cols-12 gap-6">

                        <!-- ========================== -->
                        <!--   Colonne gauche : équipes -->
                        <!-- ========================== -->
                        <div class="col-span-3 border border-slate-200 rounded-lg bg-slate-50 p-3 self-start">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">Équipes de la ligue</h3>

                            <div v-if="otherTeams.length" class="max-h-64 overflow-y-auto space-y-1">
                                <button
                                    v-for="t in otherTeams"
                                    :key="t.id"
                                    type="button"
                                    @click="selectOtherTeam(t)"
                                    :class="[
                    'w-full text-left text-sm px-2 py-1 rounded',
                    selectedOtherTeam && selectedOtherTeam.id === t.id
                        ? 'bg-teal-100 text-slate-900'
                        : 'bg-white hover:bg-slate-100 text-slate-700'
                ]"
                                >
                                    {{ t.name }}
                                </button>
                            </div>

                            <p v-else class="text-sm text-slate-500">Aucune autre équipe trouvée.</p>
                        </div>

                        <!-- ========================== -->
                        <!--   Colonne droite : détails -->
                        <!-- ========================== -->
                        <div v-if="selectedOtherTeam" class="col-span-9 flex flex-col gap-6">

                            <!-- TOP : Nom / Bilan / Description (2 colonnes) / Logo -->
                            <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                                <div class="grid grid-cols-4 gap-6 items-center">

                                    <!-- Bloc 1 : Nom + infos -->
                                    <div class="col-span-1">
                                        <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                            {{ selectedOtherTeam.name }}
                                        </h3>

                                        <div class="text-sm text-slate-700 space-y-1">
                                            <p v-if="standings?.length">
                                                <span class="font-semibold">Place :</span>
                                                {{ (standings.findIndex(row => row.id === selectedOtherTeam.id)+1) || '—' }}
                                                <sup>e</sup> / {{ standings.length }}
                                            </p>

                                            <p>
                                                <span class="font-semibold">Budget :</span>
                                                {{ selectedOtherTeam.budget ?? 0 }} €
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Bloc 2+3 : Description en 2 colonnes -->
                                    <div class="col-span-2">
                                        <p class="text-sm text-slate-700 leading-relaxed ">
                                            {{ selectedOtherTeam.description || '-' }}
                                        </p>
                                    </div>

                                    <!-- Bloc 4 : Logo -->
                                    <div class="col-span-1 flex items-center justify-center">
                                        <div class="h-24 w-24 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                                            <img
                                                v-if="teamLogoUrl(selectedOtherTeam)"
                                                :src="teamLogoUrl(selectedOtherTeam)"
                                                class="h-full w-full object-contain"
                                                alt=""
                                            >
                                            <span v-else class="text-xs text-slate-400">—</span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- STATUT DU CLUB -->
                            <div class="border border-slate-200 rounded-lg bg-slate-50 mt-1 p-4">
                                <h4 class="text-md font-semibold text-slate-700 mb-3">Statut du club</h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">

                                    <div>
                                        <p class="font-semibold mb-1">Matchs joués {{
                                                (selectedOtherTeam.wins ?? 0)
                                                + (selectedOtherTeam.draws ?? 0)
                                                + (selectedOtherTeam.losses ?? 0)
                                            }}</p>
                                        <p>Victoire : {{ selectedOtherTeam.wins ?? 0 }}</p>
                                        <p>Nul : {{ selectedOtherTeam.draws ?? 0 }}</p>
                                        <p>Défaite : {{ selectedOtherTeam.losses ?? 0 }}</p>
                                    </div>

                                    <div>
                                        <p class="font-semibold mb-1">Stats moyennes</p>
                                        <p>Attaque : {{ averageTeamStat(selectedOtherTeamRoster, 'attack') }}</p>
                                        <p>Défense : {{ averageTeamStat(selectedOtherTeamRoster, 'defense') }}</p>
                                        <p>Endurance : {{ averageTeamStat(selectedOtherTeamRoster, 'stamina') }}</p>
                                    </div>

                                    <div>
                                        <p class="font-semibold mb-1">État de l'effectif</p>
                                        <p>Blessés : {{ injuriesCountForTeam(selectedOtherTeam.id) }}</p>
                                        <p>Suspensions : {{ suspensionsCountForTeam(selectedOtherTeam.id) }}</p>
                                        <p>Cartons : {{ cardsCountForTeam(selectedOtherTeam.id) }}</p>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- ============ -->
                        <!-- EFFECTIF     -->
                        <!-- ============ -->
                        <div class="col-span-12 border border-slate-200 rounded-lg bg-slate-50 p-4 h-80 overflow-y-auto">

                            <h4 class="text-md font-semibold text-slate-700 mb-2">Effectif</h4>

                            <div v-if="selectedOtherTeamRoster.length">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm min-w-max text-left">
                                        <thead class="text-xs uppercase text-slate-500 border-b">
                                        <tr>
                                            <th class="py-1 pr-2 w-10"></th>
                                            <th class="py-1 pr-2">Joueur</th>
                                            <th class="py-1 pr-2">Poste</th>

                                            <th class="py-1 pr-2 text-right">Vit</th>
                                            <th class="py-1 pr-2 text-right">End</th>
                                            <th class="py-1 pr-2 text-right">Att</th>
                                            <th class="py-1 pr-2 text-right">Def</th>

                                            <th class="py-1 pr-2 text-right">Tir</th>
                                            <th class="py-1 pr-2 text-right">Passe</th>
                                            <th class="py-1 pr-2 text-right">Dribble</th>

                                            <th class="py-1 pr-2 text-right">Block</th>
                                            <th class="py-1 pr-2 text-right">Interc.</th>
                                            <th class="py-1 pr-2 text-right">Tacle</th>

                                            <th class="py-1 pr-2 text-right">Main</th>
                                            <th class="py-1 pr-2 text-right">Poings</th>

                                            <th class="py-1 pr-2 text-right">Coût</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        <tr
                                            v-for="player in selectedOtherTeamRoster"
                                            :key="player.id"
                                            class="border-b last:border-b-0"
                                        >
                                            <td class="py-1 pr-2">
                                                <div class="h-7 w-7 rounded border bg-white overflow-hidden flex items-center justify-center">
                                                    <img
                                                        v-if="playerPhotoUrl(player)"
                                                        :src="playerPhotoUrl(player)"
                                                        class="h-full w-full object-cover"
                                                        alt=""
                                                    >
                                                    <span v-else class="text-[10px] text-slate-400">—</span>
                                                </div>
                                            </td>

                                            <td class="py-1 pr-2">{{ player.firstname }} {{ player.lastname }}</td>
                                            <td class="py-1 pr-2">{{ player.position }}</td>

                                            <td class="py-1 pr-2 text-right">{{ player.speed ?? player.stats?.speed ?? '-' }}</td>
                                            <td class="py-1 pr-2 text-right">{{ player.stamina ?? player.stats?.stamina ?? '-' }}</td>
                                            <td class="py-1 pr-2 text-right">{{ player.attack ?? player.stats?.attack ?? '-' }}</td>
                                            <td class="py-1 pr-2 text-right">{{ player.defense ?? player.stats?.defense ?? '-' }}</td>

                                            <td class="py-1 pr-2 text-right">{{ player.shot ?? player.stats?.shot ?? '-' }}</td>
                                            <td class="py-1 pr-2 text-right">{{ player.pass ?? player.stats?.pass ?? '-' }}</td>
                                            <td class="py-1 pr-2 text-right">{{ player.dribble ?? player.stats?.dribble ?? '-' }}</td>

                                            <td class="py-1 pr-2 text-right">{{ player.block ?? player.stats?.block ?? '-' }}</td>
                                            <td class="py-1 pr-2 text-right">{{ player.intercept ?? player.stats?.intercept ?? '-' }}</td>
                                            <td class="py-1 pr-2 text-right">{{ player.tackle ?? player.stats?.tackle ?? '-' }}</td>

                                            <td class="py-1 pr-2 text-right">{{ player.hand_save ?? player.stats?.hand_save ?? '-' }}</td>
                                            <td class="py-1 pr-2 text-right">{{ player.punch_save ?? player.stats?.punch_save ?? '-' }}</td>

                                            <td class="py-1 pr-2 text-right">{{ player.cost ?? '-' }}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <p v-else class="text-sm text-slate-500">
                                Aucun joueur sous contrat pour cette équipe.
                            </p>
                        </div>

                    </div>

                    <!-- ============================== -->
                    <!--          TRANSFERTS           -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'transfers'" class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4 relative">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-slate-700">Joueurs libres</h3>
                            <p class="text-xs text-slate-500">
                                Les signatures impactent le budget de ton club dans cette sauvegarde.
                            </p>
                        </div>

                        <div v-if="freePlayers.length" class="max-h-96 overflow-y-auto">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left min-w-max">
                                    <thead class="text-xs uppercase text-slate-500 border-b">
                                    <tr>
                                        <th class="py-1 pr-2 w-10"></th>
                                        <th class="py-1 pr-2">Joueur</th>
                                        <th class="py-1 pr-2">Poste</th>

                                        <th class="py-1 pr-2 text-right">Vit</th>
                                        <th class="py-1 pr-2 text-right">End</th>
                                        <th class="py-1 pr-2 text-right">Att</th>
                                        <th class="py-1 pr-2 text-right">Def</th>

                                        <th class="py-1 pr-2 text-right">Tir</th>
                                        <th class="py-1 pr-2 text-right">Passe</th>
                                        <th class="py-1 pr-2 text-right">Dribble</th>

                                        <th class="py-1 pr-2 text-right">Block</th>
                                        <th class="py-1 pr-2 text-right">Interc.</th>
                                        <th class="py-1 pr-2 text-right">Tacle</th>

                                        <th class="py-1 pr-2 text-right">Main</th>
                                        <th class="py-1 pr-2 text-right">Poings</th>

                                        <th class="py-1 pr-2 text-right">Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <tr v-for="player in freePlayers" :key="player.id" class="border-b last:border-b-0">
                                        <td class="py-1 pr-2">
                                            <div class="h-7 w-7 rounded border bg-white overflow-hidden flex items-center justify-center">
                                                <img
                                                    v-if="playerPhotoUrl(player)"
                                                    :src="playerPhotoUrl(player)"
                                                    class="h-full w-full object-cover"
                                                    alt=""
                                                />
                                                <span v-else class="text-[10px] text-slate-400">—</span>
                                            </div>
                                        </td>

                                        <td class="py-1 pr-2">{{ player.firstname }} {{ player.lastname }}</td>
                                        <td class="py-1 pr-2">{{ player.position }}</td>

                                        <td class="py-1 pr-2 text-right">{{ player.speed ?? player.stats?.speed ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.stamina ?? player.stats?.stamina ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.attack ?? player.stats?.attack ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.defense ?? player.stats?.defense ?? '-' }}</td>

                                        <td class="py-1 pr-2 text-right">{{ player.shot ?? player.stats?.shot ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.pass ?? player.stats?.pass ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.dribble ?? player.stats?.dribble ?? '-' }}</td>

                                        <td class="py-1 pr-2 text-right">{{ player.block ?? player.stats?.block ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.intercept ?? player.stats?.intercept ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.tackle ?? player.stats?.tackle ?? '-' }}</td>

                                        <td class="py-1 pr-2 text-right">{{ player.hand_save ?? player.stats?.hand_save ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.punch_save ?? player.stats?.punch_save ?? '-' }}</td>

                                        <td class="py-1 pr-2 text-right">
                                            <button
                                                type="button"
                                                class="text-xs px-3 py-0.5 rounded-full border border-teal-500 bg-teal-100 hover:bg-teal-200 font-semibold disabled:opacity-50"
                                                :disabled="!team"
                                                @click="openTransferModal(player)"
                                            >
                                                Offre
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p v-else class="text-sm text-slate-500">Aucun joueur libre disponible.</p>

                        <!-- MODAL TRANSFERT -->
                        <div v-if="showTransferModal && transferTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-5">
                                <h3 class="text-lg font-semibold text-slate-800 mb-3">
                                    Proposer un contrat à {{ transferTarget.firstname }} {{ transferTarget.lastname }}
                                </h3>

                                <!-- mini header avec photo -->
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="h-10 w-10 rounded border bg-white overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="playerPhotoUrl(transferTarget)"
                                            :src="playerPhotoUrl(transferTarget)"
                                            class="h-full w-full object-cover"
                                            alt=""
                                        />
                                        <span v-else class="text-[10px] text-slate-400">—</span>
                                    </div>
                                    <p class="text-sm text-slate-600">
                                        Club : <span class="font-semibold">{{ team?.name }}</span> —
                                        Budget actuel : <span class="font-semibold">{{ teamBudget }} €</span>
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre de matchs</label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="60"
                                            v-model.number="transferMatches"
                                            class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Coût par match (€)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            v-model.number="transferSalary"
                                            class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        >
                                    </div>
                                </div>

                                <p class="text-sm text-slate-700 mb-3">
                                    Coût total estimé : <span class="font-semibold">{{ transferTotalCost }} €</span>
                                </p>

                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Raison du recrutement</label>
                                    <textarea
                                        rows="3"
                                        v-model="transferReason"
                                        class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        placeholder="Ex. : Renforcer l’aile gauche, remplacer un blessé..."
                                    ></textarea>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 text-sm rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100"
                                        @click="closeTransferModal"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="button"
                                        class="px-4 py-1.5 text-sm rounded-md bg-teal-500 hover:bg-teal-600 text-white font-semibold disabled:opacity-50"
                                        :disabled="!team || transferMatches <= 0 || transferSalary < 0"
                                        @click="confirmTransfer"
                                    >
                                        Confirmer l’offre
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--          CALENDRIER           -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'calendar'" class="flex-1 flex gap-4">
                        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">Equipes</h3>

                            <div v-if="calendarTeams.length" class="max-h-96 overflow-y-auto space-y-1">
                                <button
                                    v-for="t in calendarTeams"
                                    :key="t.id"
                                    type="button"
                                    @click="selectCalendarTeam(t)"
                                    :class="[
                                        'w-full text-left text-sm px-2 py-1 rounded',
                                        calendarTeam && calendarTeam.id === t.id
                                            ? 'bg-teal-100 text-slate-900'
                                            : 'bg-white hover:bg-slate-100 text-slate-700'
                                    ]"
                                >
                                    {{ t.name }}
                                </button>
                            </div>

                            <p v-else class="text-sm text-slate-500">Aucune équipe trouvée.</p>
                        </div>

                        <div class="flex-1">
                            <div v-if="calendarTeam" class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                                <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                    Calendrier : {{ calendarTeam.name }}
                                </h3>

                                <p class="text-xs text-slate-500 mb-3">
                                    Un match aller et un match retour contre chaque équipe de la ligue.
                                </p>

                                <div v-if="calendarRows.length" class="max-h-96 overflow-y-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="text-xs uppercase text-slate-500 border-b">
                                        <tr>
                                            <th class="py-1 pr-2 text-right">Semaine</th>
                                            <th class="py-1 pr-2">Adversaire</th>
                                            <th class="py-1 pr-2">Lieu</th>
                                            <th class="py-1 pr-2 text-right">Statut</th>
                                            <th class="py-1 pr-2 text-right">Score</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        <tr
                                            v-for="match in calendarRows"
                                            :key="match.id"
                                            class="border-b last:border-b-0 cursor-pointer hover:bg-teal-50"
                                            @click="!isByeMatch(match) && match.status === 'played' && match.match_stats && openMatchStats(match)"
                                        >
                                            <td class="py-1 pr-2 text-right">{{ match.week }}</td>

                                            <td class="py-1 pr-2">
                                                <span v-if="isByeMatch(match)" class="text-slate-400 italic">Repos</span>
                                                <span v-else>{{ opponentNameForTeam(match, calendarTeam.id) }}</span>
                                            </td>

                                            <td class="py-1 pr-2">
                                                <span v-if="isByeMatch(match)" class="text-slate-400">—</span>
                                                <span v-else>{{ match.home_team_id === calendarTeam.id ? 'Domicile' : 'Extérieur' }}</span>
                                            </td>

                                            <td class="py-1 pr-2 text-right">
                                                <span v-if="isByeMatch(match)" class="text-slate-400">Repos</span>
                                                <template v-else>
                                                    <span v-if="match.status === 'scheduled'">À jouer</span>
                                                    <span v-else-if="match.status === 'played'">Joué</span>
                                                    <span v-else>Annulé</span>
                                                </template>
                                            </td>

                                            <td class="py-1 pr-2 text-right">
                                                <span v-if="isByeMatch(match)" class="text-slate-400">-</span>
                                                <span v-else-if="match.status === 'played'">{{ match.home_score }} - {{ match.away_score }}</span>
                                                <span v-else class="text-slate-400">-</span>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- ✅ PANEL STATS DU MATCH SÉLECTIONNÉ -->
                            <div
                                v-if="selectedCalendarMatch && selectedCalendarMatchStats"
                                class="border border-slate-200 rounded-lg bg-slate-50 p-4 mt-4"
                            >
                                <h4 class="text-md font-semibold text-slate-700 mb-2">
                                    Stats du match — Semaine {{ selectedCalendarMatch.week }}
                                </h4>

                                <p class="text-sm text-slate-600 mb-3">
                                    {{ teamById[selectedCalendarMatch.home_team_id]?.name }}
                                    {{ selectedCalendarMatch.home_score }} - {{ selectedCalendarMatch.away_score }}
                                    {{ teamById[selectedCalendarMatch.away_team_id]?.name }}
                                </p>

                                <!-- Stats d'équipe -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-4">
                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-semibold mb-1">Tirs</p>
                                        <p>Nous : {{ selectedCalendarMyTeamStats?.shots ?? 0 }}</p>
                                        <p>Adversaire : {{ selectedCalendarOpponentStats?.shots ?? 0 }}</p>
                                    </div>
                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-semibold mb-1">Duels gagnés</p>
                                        <p>Nous : {{ selectedCalendarMyTeamStats?.duelsWon ?? 0 }}</p>
                                        <p>Adversaire : {{ selectedCalendarOpponentStats?.duelsWon ?? 0 }}</p>
                                    </div>
                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-semibold mb-1">Duels perdus</p>
                                        <p>Nous : {{ selectedCalendarMyTeamStats?.duelsLost ?? 0 }}</p>
                                        <p>Adversaire : {{ selectedCalendarOpponentStats?.duelsLost ?? 0 }}</p>
                                    </div>
                                </div>

                                <!-- Stats par joueur de l'équipe sélectionnée -->
                                <h5 class="text-sm font-semibold text-slate-700 mb-2">
                                    Stats par joueur ({{ calendarTeam.name }})
                                </h5>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs md:text-sm min-w-max text-left">
                                        <thead class="text-[10px] uppercase text-slate-500 border-b">
                                        <tr>
                                            <th class="py-1 pr-2">Joueur</th>
                                            <th class="py-1 pr-2 text-right">Tirs</th>
                                            <th class="py-1 pr-2 text-right">Passes</th>
                                            <th class="py-1 pr-2 text-right">Dribbles</th>
                                            <th class="py-1 pr-2 text-right">Interc.</th>
                                            <th class="py-1 pr-2 text-right">Tacles</th>
                                            <th class="py-1 pr-2 text-right">Blocks</th>
                                            <th class="py-1 pr-2 text-right">Duels +</th>
                                            <th class="py-1 pr-2 text-right">Duels -</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr
                                            v-for="p in calendarTeamRoster"
                                            :key="p.id"
                                            class="border-b last:border-b-0"
                                        >
                                            <td class="py-1 pr-2">{{ p.firstname }} {{ p.lastname }}</td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ selectedCalendarPlayersStats[p.id]?.offense?.shot?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ selectedCalendarPlayersStats[p.id]?.offense?.pass?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ selectedCalendarPlayersStats[p.id]?.offense?.dribble?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ selectedCalendarPlayersStats[p.id]?.defense?.intercept?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ selectedCalendarPlayersStats[p.id]?.defense?.tackle?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ selectedCalendarPlayersStats[p.id]?.defense?.block?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ selectedCalendarPlayersStats[p.id]?.duelsWon ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ selectedCalendarPlayersStats[p.id]?.duelsLost ?? 0 }}
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <p v-else class="text-sm text-slate-500 mt-2">
                                Clique sur un match joué dans le calendrier pour afficher ses statistiques détaillées.
                            </p>

                            <p v-else class="text-sm text-slate-500">Aucun match planifié pour le moment.</p>

                            <p v-else class="text-sm text-slate-500">
                                Sélectionne une équipe dans la liste à gauche.
                            </p>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--          CLASSEMENT           -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'standings'" class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">Classement de la saison</h3>

                        <p class="text-xs text-slate-500 mb-3">Ligne surlignée = ton équipe contrôlée.</p>

                        <div class="max-h-96 overflow-y-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs uppercase text-slate-500 border-b">
                                <tr>
                                    <th class="py-1 pr-2 text-right">#</th>
                                    <th class="py-1 pr-2">Équipe</th>
                                    <th class="py-1 pr-2 text-right">J</th>
                                    <th class="py-1 pr-2 text-right">V</th>
                                    <th class="py-1 pr-2 text-right">N</th>
                                    <th class="py-1 pr-2 text-right">D</th>
                                    <th class="py-1 pr-2 text-right">Pts</th>
                                </tr>
                                </thead>

                                <tbody>
                                <tr
                                    v-for="(row, index) in standings"
                                    :key="row.id"
                                    :class="[
                                        'border-b last:border-b-0',
                                        team && team.id === row.id ? 'bg-teal-50 font-semibold' : ''
                                    ]"
                                >
                                    <td class="py-1 pr-2 text-right">{{ index + 1 }}</td>
                                    <td class="py-1 pr-2">{{ row.name }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.played }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.wins }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.draws }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.losses }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.points }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- ============================== -->
                    <!--         STATS D'ÉQUIPE        -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'match-stats'" class="flex-1 flex gap-4">

                        <!-- Barre gauche : sélection d'équipe -->
                        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">Équipes</h3>

                            <div v-if="teams.length" class="max-h-96 overflow-y-auto space-y-1">
                                <button
                                    v-for="t in teams"
                                    :key="t.id"
                                    type="button"
                                    @click="selectedStatsTeamId = t.id"
                                    :class="[
                    'w-full text-left text-sm px-2 py-1 rounded',
                    selectedStatsTeamId === t.id
                        ? 'bg-teal-100 text-slate-900'
                        : 'bg-white hover:bg-slate-100 text-slate-700'
                ]"
                                >
                                    {{ t.name }}
                                </button>
                            </div>

                            <p v-else class="text-sm text-slate-500">
                                Aucune équipe trouvée.
                            </p>
                        </div>

                        <!-- Colonne droite : stats cumulées -->
                        <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-6">

                            <h3 class="text-xl font-bold text-slate-800 mb-4">
                                📊 Stats d'équipe
                                <span v-if="selectedStatsTeam">— {{ selectedStatsTeam.name }}</span>
                            </h3>

                            <template v-if="selectedStatsTeam">

                                <!-- Stats globales de l'équipe -->
                                <h4 class="text-md font-semibold text-slate-700 mb-2">Stats globales</h4>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-6">
                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-medium">Tirs</p>
                                        <p>{{ teamStats.shots }}</p>
                                    </div>

                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-medium">Passes</p>
                                        <p>{{ teamStats.passes }}</p>
                                    </div>

                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-medium">Dribbles</p>
                                        <p>{{ teamStats.dribbles }}</p>
                                    </div>

                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-medium">Interceptions</p>
                                        <p>{{ teamStats.intercepts }}</p>
                                    </div>

                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-medium">Tacles</p>
                                        <p>{{ teamStats.tackles }}</p>
                                    </div>

                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-medium">Blocks</p>
                                        <p>{{ teamStats.blocks }}</p>
                                    </div>

                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-medium">Duels gagnés</p>
                                        <p>{{ teamStats.duelsWon }}</p>
                                    </div>

                                    <div class="p-3 border rounded bg-white">
                                        <p class="font-medium">Duels perdus</p>
                                        <p>{{ teamStats.duelsLost }}</p>
                                    </div>
                                </div>

                                <!-- Stats des joueurs -->
                                <h4 class="text-md font-semibold text-slate-700 mb-3">Stats des joueurs</h4>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm min-w-max text-left">
                                        <thead class="text-xs uppercase text-slate-500 border-b">
                                        <tr>
                                            <th class="py-1 pr-2">Joueur</th>
                                            <th class="py-1 pr-2 text-right">Tirs</th>
                                            <th class="py-1 pr-2 text-right">Passes</th>
                                            <th class="py-1 pr-2 text-right">Dribbles</th>
                                            <th class="py-1 pr-2 text-right">Interc.</th>
                                            <th class="py-1 pr-2 text-right">Tacles</th>
                                            <th class="py-1 pr-2 text-right">Blocks</th>
                                            <th class="py-1 pr-2 text-right">Duels +</th>
                                            <th class="py-1 pr-2 text-right">Duels –</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr
                                            v-for="p in selectedTeamPlayerStats"
                                            :key="p.id"
                                            class="border-b last:border-b-0"
                                        >
                                            <td class="py-1 pr-2">
                                                {{ p.firstname }} {{ p.lastname }}
                                            </td>

                                            <td class="py-1 pr-2 text-right">
                                                {{ p.stats?.offense?.shot?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ p.stats?.offense?.pass?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ p.stats?.offense?.dribble?.attempts ?? 0 }}
                                            </td>

                                            <td class="py-1 pr-2 text-right">
                                                {{ p.stats?.defense?.intercept?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ p.stats?.defense?.tackle?.attempts ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ p.stats?.defense?.block?.attempts ?? 0 }}
                                            </td>

                                            <td class="py-1 pr-2 text-right">
                                                {{ p.stats?.duelsWon ?? 0 }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ p.stats?.duelsLost ?? 0 }}
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </template>

                            <p v-else class="text-slate-500 text-sm">
                                Sélectionne une équipe dans la liste à gauche pour voir ses stats cumulées.
                            </p>

                        </div>

                    </div>
                    <!-- ============================== -->
                    <!--         ENTRAÎNEMENT          -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'training'" class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">Entraînement</h3>

                        <p class="text-sm text-slate-600 mb-2">
                            Semaine {{ week }} — Saison {{ season }}.
                            Il te reste
                            <span class="font-semibold">{{ remainingTrainingsThisWeek }}</span>
                            entraînement(s) possible(s) cette semaine (max 3, joueurs différents).
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Colonne gauche : effectif + stamina -->
                            <div class="border border-slate-200 rounded-lg bg-white p-3 max-h-96 overflow-y-auto">
                                <h4 class="text-sm font-semibold text-slate-700 mb-2">Effectif</h4>

                                <div v-if="roster.length" class="space-y-1 text-sm">
                                    <div
                                        v-for="p in roster"
                                        :key="p.id"
                                        class="flex items-center justify-between px-2 py-1 rounded border border-slate-100 bg-slate-50"
                                        :class="{ 'opacity-60': hasPlayerBeenTrainedThisWeek(p.id) }"
                                    >
                                        <div class="flex flex-col">
                        <span class="font-medium text-slate-800">
                            {{ p.firstname }} {{ p.lastname }}
                        </span>
                                            <span class="text-xs text-slate-500">
                            Poste : {{ p.position }} • Stamina : {{ p.stamina ?? p.stats?.stamina ?? '-' }}
                            <span
                                v-if="hasPlayerBeenTrainedThisWeek(p.id)"
                                class="ml-1 text-[11px] text-amber-600"
                            >
                                (déjà entraîné cette semaine)
                            </span>
                        </span>
                                        </div>

                                        <div class="text-[11px] text-right text-slate-600 leading-tight space-y-0.5">

                                            <!-- Ligne 1 : stats globales -->
                                            <div>
                                                Vit : {{ p.speed ?? p.stats?.speed ?? '-' }}
                                                • End : {{ p.stamina ?? p.stats?.stamina ?? '-' }}
                                                • Att : {{ p.attack ?? p.stats?.attack ?? '-' }}
                                                • Def : {{ p.defense ?? p.stats?.defense ?? '-' }}
                                            </div>

                                            <!-- Ligne 2 : stats de terrain -->
                                            <div>
                                                Tir : {{ p.shot ?? p.stats?.shot ?? '-' }}
                                                • Pas : {{ p.pass ?? p.stats?.pass ?? '-' }}
                                                • Dri : {{ p.dribble ?? p.stats?.dribble ?? '-' }}
                                                • Tac : {{ p.tackle ?? p.stats?.tackle ?? '-' }}
                                                • Int : {{ p.intercept ?? p.stats?.intercept ?? '-' }}
                                                • Blk : {{ p.block ?? p.stats?.block ?? '-' }}
                                            </div>

                                            <!-- Ligne 3 : stats gardien (uniquement si > 0) -->
                                            <div v-if="(p.hand_save ?? p.stats?.hand_save ?? 0) > 0 || (p.punch_save ?? p.stats?.punch_save ?? 0) > 0">
                                                Main : {{ p.hand_save ?? p.stats?.hand_save ?? '-' }}
                                                • Poings : {{ p.punch_save ?? p.stats?.punch_save ?? '-' }}
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <p v-else class="text-sm text-slate-500">
                                    Aucun joueur sous contrat pour cette équipe.
                                </p>
                            </div>

                            <!-- Colonne droite : formulaire d'entraînement -->
                            <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-3">
                                <h4 class="text-sm font-semibold text-slate-700 mb-1">Planifier des entraînements</h4>

                                <p class="text-xs text-slate-500 mb-2">
                                    Sélectionne 1 à 3 joueurs différents et la statistique à améliorer.
                                    Chaque entraînement améliore la stat (+1 à +5) et coûte 5 points de stamina.
                                </p>

                                <div class="space-y-2">
                                    <div
                                        v-for="(slot, index) in selectedTrainings"
                                        :key="index"
                                        class="flex items-center gap-2"
                                    >
                                        <!-- Select joueur -->
                                        <select
                                            v-model.number="slot.player_id"
                                            class="flex-1 border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        >
                                            <option :value="null">Choisir un joueur</option>
                                            <option
                                                v-for="p in roster"
                                                :key="p.id"
                                                :value="p.id"
                                                :disabled="hasPlayerBeenTrainedThisWeek(p.id)"
                                            >
                                                {{ p.firstname }} {{ p.lastname }} — Stamina {{ p.stamina ?? p.stats?.stamina ?? '-' }}
                                            </option>
                                        </select>

                                        <!-- Select stat -->
                                        <select
                                            v-model="slot.stat"
                                            class="w-32 border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        >
                                            <option
                                                v-for="s in availableTrainingStats"
                                                :key="s.key"
                                                :value="s.key"
                                            >
                                                {{ s.label }}
                                            </option>
                                        </select>

                                        <!-- Bouton remove -->
                                        <button
                                            type="button"
                                            class="text-xs text-red-600 hover:text-red-700"
                                            @click="removeTrainingSlot(index)"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between mt-2">
                                    <button
                                        type="button"
                                        class="text-xs px-3 py-1 rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100 disabled:opacity-50"
                                        @click="addTrainingSlot"
                                        :disabled="selectedTrainings.length >= 3 || remainingTrainingsThisWeek <= 0"
                                    >
                                        + Ajouter un joueur
                                    </button>

                                    <button
                                        type="button"
                                        class="px-4 py-1.5 text-sm rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold disabled:opacity-50"
                                        :disabled="!canSubmitTraining"
                                        @click="submitTraining"
                                    >
                                        Lancer l'entraînement
                                    </button>
                                </div>

                                <p v-if="remainingTrainingsThisWeek <= 0" class="mt-2 text-xs text-amber-600">
                                    Tu as utilisé tous tes entraînements pour cette semaine.
                                </p>
                            </div>
                        </div>

                        <!-- Historique simple des entraînements de la semaine -->
                        <div
                            v-if="trainingState && trainingState.season === season && trainingState.week === week && trainingState.entries?.length"
                            class="mt-4 border border-slate-200 rounded-lg bg-white p-3"
                        >
                            <h4 class="text-sm font-semibold text-slate-700 mb-2">Historique de la semaine</h4>

                            <ul class="text-xs text-slate-600 space-y-1 max-h-40 overflow-y-auto">
                                <li
                                    v-for="(entry, idx) in trainingState.entries"
                                    :key="idx"
                                >
                                    Joueur ID {{ entry.player_id }} — {{ entry.stat }} :
                                    +{{ entry.gain }} (stamina -{{ entry.stamina_cost }})
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--         CARTES BONUS          -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'cards'" class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">Cartes bonus</h3>
                        <p class="text-sm text-slate-600">Système de cartes bonus / malus (à venir).</p>
                    </div>

                    <!-- ============================== -->
                    <!--            GESTION            -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'management'" class="flex-1 flex gap-4">

                        <!-- Sidebar gauche -->
                        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">Gestion</h3>

                            <nav class="space-y-1">
                                <button
                                    v-for="sec in managementSections"
                                    :key="sec.key"
                                    type="button"
                                    @click="goToManagementSection(sec.key)"
                                    :class="[
                    'w-full text-left text-sm px-2 py-1 rounded',
                    activeManagementSection === sec.key
                        ? 'bg-teal-100 text-slate-900'
                        : 'bg-white hover:bg-slate-100 text-slate-700'
                ]"
                                >
                                    {{ sec.label }}
                                </button>
                            </nav>
                        </div>

                        <!-- Panneau droit -->
                        <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4">

                            <!-- ====================== -->
                            <!-- 1. Gestion des joueurs -->
                            <!-- ====================== -->
                            <div v-if="activeManagementSection === 'dataBase'" class="flex-1 flex flex-col gap-3">
                                <h3 class="text-lg font-semibold text-slate-800 mb-1">
                                    Base de données
                                </h3>
                                <p class="text-sm text-slate-600 mb-2">
                                    Ici tu gères les joueurs et équipes de ta partie : création, édition, et assignation de contrats à des équipes.
                                </p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <!-- Bloc joueurs -->
                                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                                        <h4 class="text-sm font-semibold text-slate-700">Joueurs</h4>
                                        <p class="text-xs text-slate-500">
                                            Crée, édite et supprime les joueurs de la base de données.
                                        </p>
                                        <div class="mt-2 flex gap-2">
                                            <button
                                                type="button"
                                                class="px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                                @click="$inertia.visit(route('players.index'))"
                                            >
                                                Gérer les joueurs
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Bloc contrats -->
                                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                                        <h4 class="text-sm font-semibold text-slate-700">Contrats</h4>
                                        <p class="text-xs text-slate-500">
                                            Assigne des joueurs à des équipes, ajuste les durées et les coûts des contrats.
                                        </p>
                                        <div class="mt-2 flex gap-2">
                                            <button
                                                type="button"
                                                class="px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                                @click="$inertia.visit(route('contracts.index'))"
                                            >
                                                Gérer les contrats
                                            </button>
                                        </div>
                                    </div>
                                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                                        <h4 class="text-sm font-semibold text-slate-700">Équipes</h4>
                                        <p class="text-xs text-slate-500">
                                            Accéder à la gestion complète des équipes.
                                        </p>
                                        <div class="mt-2 flex gap-2">
                                            <button
                                                type="button"
                                                class="px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                                @click="$inertia.visit(route('teams.index'))"
                                            >
                                                Gérer les équipes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ====================== -->
                            <!-- 2. Gestion de profil -->
                            <!-- ====================== -->
                            <div v-else-if="activeManagementSection === 'profil'" class="flex-1 flex flex-col gap-3">
                                <h3 class="text-lg font-semibold text-slate-800 mb-1">
                                    Mon profil
                                </h3>
                                <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-slate-700">Édition de mon profil</h4>

                                    <p class="text-xs text-slate-500">
                                        Accéder aux informations de ton compte et les modifier (nom, email, mot de passe…).
                                    </p>

                                    <div class="mt-2 flex gap-2">
                                        <button
                                            type="button"
                                            class="px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                            @click="$inertia.visit(route('profile.edit'))"
                                        >
                                            Modifier mon profil
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ====================== -->
                            <!-- 3. Configuration du jeu -->
                            <!-- ====================== -->
                            <div v-else-if="activeManagementSection === 'config'" class="flex-1 flex flex-col gap-3">
                                <h3 class="text-lg font-semibold text-slate-800 mb-1">
                                    Configuration du jeu
                                </h3>
                                <p class="text-sm text-slate-600 mb-2">
                                    Paramètres globaux du jeu (balancing, règles, etc.). Pour l’instant, cette section est en chantier,
                                    mais tu pourras y exposer les valeurs de <code>config/training.php</code>, des règles IA, etc.
                                </p>

                                <div class="border border-dashed border-slate-300 rounded-lg bg-white p-4 text-sm text-slate-500">
                                    Zone de configuration à implémenter :
                                    <ul class="list-disc pl-5 mt-2 text-xs">
                                        <li>Coefficients de training (gain min/max, stamina cost…)</li>
                                        <li>Paramètres IA (probabilités, bonus/malus)</li>
                                        <li>Règles de match (nombre de tours, etc.)</li>
                                    </ul>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
