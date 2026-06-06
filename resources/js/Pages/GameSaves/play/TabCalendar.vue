<script setup>
import { computed, ref, watch } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';

const props = defineProps({
    calendarTeams:                 { type: Array,    required: true },
    calendarTeam:                  { type: Object,   default: null },
    calendarRows:                  { type: Array,    required: true },
    calendarTeamRoster:            { type: Array,    required: true },
    calendarOpponentRoster:        { type: Array, default: () => [] },
    teamById:                      { type: Object,   required: true },
    selectedCalendarMatch:         { type: Object,   default: null },
    selectedCalendarMatchStats:    { type: Object,   default: null },
    selectedCalendarMyTeamStats:   { type: Object,   default: null },
    selectedCalendarOpponentStats: { type: Object,   default: null },
    selectedCalendarPlayersStats:  { type: Object,   required: true },
    isByeMatch:                    { type: Function, required: true },
    opponentNameForTeam:           { type: Function, required: true },
    selectedCalendarProgression: { type: Array, default: () => [] },
});

const emit = defineEmits(['select-team', 'open-match-stats']);

// ==========================
//   HELPERS
// ==========================
const { playerPhotoUrl, teamLogoUrl } = usePlayerUtils();

const homeTeamName = computed(() =>
    props.selectedCalendarMatch ? props.teamById[props.selectedCalendarMatch.home_team_id]?.name : ''
);
const awayTeamName = computed(() =>
    props.selectedCalendarMatch ? props.teamById[props.selectedCalendarMatch.away_team_id]?.name : ''
);

const homeProgressors = computed(() =>
    props.selectedCalendarProgression.filter(p => p.team_side === 'home').sort((a, b) => b.total - a.total)
);
const awayProgressors = computed(() =>
    props.selectedCalendarProgression.filter(p => p.team_side === 'away').sort((a, b) => b.total - a.total)
);

const STAT_LABELS = {
    shot:    { icon: '🎯', label: 'Tir',     color: 'text-rose-700    bg-rose-100' },
    pass:    { icon: '🎽', label: 'Passe',   color: 'text-emerald-700 bg-emerald-100' },
    dribble: { icon: '🔥', label: 'Dribble', color: 'text-amber-700   bg-amber-100' },
    defense: { icon: '🛡️', label: 'Défense', color: 'text-blue-700    bg-blue-100' },
};

// Résultat d'un match pour une équipe donnée
const matchResult = (match, teamId) => {
    if (!match || match.status !== 'played') return null;
    const isHome  = match.home_team_id === teamId;
    const scored  = isHome ? (match.home_score ?? 0) : (match.away_score ?? 0);
    const against = isHome ? (match.away_score ?? 0) : (match.home_score ?? 0);
    if (scored > against)  return { label: 'V', bg: 'bg-emerald-500', text: 'text-white' };
    if (scored === against) return { label: 'N', bg: 'bg-slate-300',   text: 'text-slate-700' };
    return { label: 'D', bg: 'bg-rose-500', text: 'text-white' };
};

const scoreFor = (match, teamId) => {
    if (!match || match.status !== 'played') return null;
    const isHome = match.home_team_id === teamId;
    const s = isHome ? match.home_score : match.away_score;
    const a = isHome ? match.away_score : match.home_score;
    return { scored: s ?? 0, against: a ?? 0 };
};

const opponentTeam = (match, teamId) => {
    if (!match || !teamId) return null;
    const oppId = match.home_team_id === teamId ? match.away_team_id : match.home_team_id;
    return props.teamById[oppId] ?? null;
};

const selectedStatsTeam = ref('home');

const statsTeamId = computed(() => {
    if (!props.selectedCalendarMatch) return null;
    return selectedStatsTeam.value === 'home'
        ? props.selectedCalendarMatch.home_team_id
        : props.selectedCalendarMatch.away_team_id;
});

const statsTeamName = computed(() => {
    if (!props.selectedCalendarMatch) return '—';
    return props.teamById[statsTeamId.value]?.name ?? '—';
});

// Barres comparatives pour les stats équipe
const statBars = computed(() => {
    if (!props.selectedCalendarMyTeamStats || !props.selectedCalendarOpponentStats) return [];
    const my  = props.selectedCalendarMyTeamStats;
    const opp = props.selectedCalendarOpponentStats;
    return [
        { label: 'Tirs',    my: my.shots    ?? 0, opp: opp.shots    ?? 0, color: 'bg-red-400' },
        { label: 'Passes',  my: my.passes   ?? 0, opp: opp.passes   ?? 0, color: 'bg-teal-400' },
        { label: 'Dribbles',my: my.dribbles ?? 0, opp: opp.dribbles ?? 0, color: 'bg-yellow-400' },
        { label: 'Duels +', my: my.duelsWon ?? 0, opp: opp.duelsWon ?? 0, color: 'bg-emerald-400' },
        { label: 'Duels -', my: my.duelsLost?? 0, opp: opp.duelsLost?? 0, color: 'bg-rose-400' },
    ];
});

const statBarWidth = (val, other) => {
    const total = val + other;
    if (!total) return 50;
    return Math.round((val / total) * 100);
};

// Stats joueur dans le match sélectionné
const playerMatchStat = (playerId, path) => {
    const s = props.selectedCalendarPlayersStats?.[playerId]
        ?? props.selectedCalendarPlayersStats?.[String(playerId)];
    if (!s) return 0;
    return path.split('.').reduce((o, k) => o?.[k] ?? 0, s);
};
const isGK = (p) => p?.position?.toLowerCase().includes('goalkeeper');
// ==========================
//   REPLAY
// ==========================
const showReplay = ref(false);

watch(() => props.selectedCalendarMatch?.id, () => { showReplay.value = false; });

const replayEvents = computed(() => {
    const stats = props.selectedCalendarMatchStats;
    if (!stats) return [];
    const players = stats.players ?? {};
    const events  = [];

    for (const [pid, pStats] of Object.entries(players)) {
        const allRosters = [...props.calendarTeamRoster, ...props.calendarOpponentRoster];
        const player = allRosters.find(p => String(p.id) === String(pid));
        if (!player) continue;

        const isHome = props.selectedCalendarMatch.home_team_id ===
            props.calendarTeams.find(t => t.contracts?.some(c =>
                (c.game_player ?? c.gamePlayer ?? c.player)?.id == pid
            ))?.id;
        const teamSide = isHome ? 'home' : 'away';

        const goals = pStats.offense?.goals ?? 0;
        for (let i = 0; i < goals; i++) {
            events.push({ type: 'goal', teamSide, priority: 0, icon: '⚽', color: 'bg-emerald-500', text: `⚽ But de ${player.lastname} !`, detail: null });
        }

        const hands = pStats.defense?.hands?.attempts ?? 0;
        const punch = pStats.defense?.punch?.attempts ?? 0;
        const saves = hands + punch;
        if (saves > 0 && isGK(player)) {
            const savesSuccess = (pStats.defense?.hands?.success ?? 0) + (pStats.defense?.punch?.success ?? 0);
            events.push({ type: 'save', teamSide, priority: 1, icon: '🧤', color: 'bg-violet-500', text: `${player.lastname} : ${savesSuccess} arrêt(s) sur ${saves} tir(s)`, detail: `Mains ${hands} · Poings ${punch}` });
        }

        const shotAttempts = pStats.offense?.shot?.attempts ?? 0;
        const shotSuccess  = pStats.offense?.shot?.success  ?? 0;
        if (shotAttempts > 0 && !isGK(player)) {
            events.push({ type: 'shot', teamSide, priority: 2, icon: '🔥', color: 'bg-orange-400', text: `${player.lastname} : ${shotAttempts} tir(s)`, detail: shotSuccess > 0 ? `${shotSuccess} cadré(s)` : 'Aucun cadré' });
        }

        const passAttempts = pStats.offense?.pass?.attempts ?? 0;
        const passSuccess  = pStats.offense?.pass?.success  ?? 0;
        if (passSuccess >= 1) {
            events.push({ type: 'pass', teamSide, priority: 3, icon: '🎯', color: 'bg-sky-400', text: `${player.lastname} : ${passSuccess}/${passAttempts} passes`, detail: null });
        }

        const dribAttempts = pStats.offense?.dribble?.attempts ?? 0;
        const dribSuccess  = pStats.offense?.dribble?.success  ?? 0;
        if (dribSuccess >= 1) {
            events.push({ type: 'dribble', teamSide, priority: 3, icon: '🌀', color: 'bg-yellow-400', text: `${player.lastname} : ${dribSuccess}/${dribAttempts} dribbles`, detail: null });
        }

        const interceptAttempts = pStats.defense?.intercept?.attempts ?? 0;
        const intercepts        = pStats.defense?.intercept?.success  ?? 0;
        if (interceptAttempts >= 1) {
            events.push({ type: 'intercept', teamSide, priority: 4, icon: '🛡️', color: 'bg-emerald-400', text: `${player.lastname} : ${intercepts}/${interceptAttempts} interception(s)`, detail: null });
        }

        const tackleAttempts = pStats.defense?.tackle?.attempts ?? 0;
        const tackles        = pStats.defense?.tackle?.success  ?? 0;
        if (tackleAttempts >= 1) {
            events.push({ type: 'tackle', teamSide, priority: 4, icon: '⚡', color: 'bg-amber-400', text: `${player.lastname} : ${tackles}/${tackleAttempts} tacle(s)`, detail: null });
        }

        const blockAttempts = pStats.defense?.block?.attempts ?? 0;
        const blocks        = pStats.defense?.block?.success  ?? 0;
        if (blockAttempts >= 1) {
            events.push({ type: 'block', teamSide, priority: 4, icon: '🧱', color: 'bg-slate-500', text: `${player.lastname} : ${blocks}/${blockAttempts} bloc(s)`, detail: null });
        }

        const duelsWon  = pStats.duelsWon  ?? 0;
        const duelsLost = pStats.duelsLost ?? 0;
        if (duelsWon > 0) {
            events.push({ type: 'duels', teamSide, priority: 5, icon: '⚔️', color: 'bg-teal-500', text: `${player.lastname} : ${duelsWon} duel(s) gagné(s)`, detail: `${duelsLost} perdu(s)` });
        }
    }

    return events.sort((a, b) => a.priority - b.priority);
});

const homeTeamEvents = computed(() => replayEvents.value.filter(e => e.teamSide === 'home'));
const awayTeamEvents = computed(() => replayEvents.value.filter(e => e.teamSide === 'away'));
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- Sélecteur équipes — chips horizontales -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Équipes</h3>
            <div class="flex flex-wrap gap-1.5">
                <button v-for="t in calendarTeams" :key="t.id" type="button"
                        @click="emit('select-team', t)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                        :class="calendarTeam?.id === t.id
                        ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                        : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300 hover:text-teal-600'">
                    <div class="w-4 h-4 rounded-full overflow-hidden shrink-0 bg-slate-100">
                        <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" class="w-full h-full object-contain" alt=""/>
                    </div>
                    {{ t.name }}
                </button>
            </div>
        </div>

        <!-- Contenu principal : calendrier + stats -->
        <div v-if="calendarTeam" class="grid grid-cols-12 gap-4">

            <!-- ============================================ -->
            <!-- CALENDRIER                                    -->
            <!-- ============================================ -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4"
                 :class="selectedCalendarMatch && selectedCalendarMatchStats ? 'col-span-4' : 'col-span-12'">

                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg overflow-hidden bg-white border border-slate-200 shrink-0">
                        <img v-if="teamLogoUrl(calendarTeam)" :src="teamLogoUrl(calendarTeam)" class="w-full h-full object-contain" alt=""/>
                    </div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ calendarTeam.name }}</h3>
                </div>

                <div class="space-y-1 h-[630px] overflow-y-auto pr-1">
                    <div v-for="match in calendarRows" :key="match.id"
                         class="flex items-center gap-3 px-3 py-2 rounded-lg border transition-all"
                         :class="[
                            isByeMatch(match) ? 'bg-slate-50 border-slate-100 opacity-50' :
                            match.status === 'played' && match.match_stats
                                ? 'bg-white border-slate-200 cursor-pointer hover:border-teal-300 hover:bg-teal-50'
                                : 'bg-white border-slate-100',
                            selectedCalendarMatch?.id === match.id ? 'border-teal-400 bg-teal-50 shadow-sm' : ''
                        ]"
                         @click="!isByeMatch(match) && match.status === 'played' && match.match_stats && emit('open-match-stats', match)">

                        <!-- Semaine -->
                        <div class="w-8 text-center shrink-0">
                            <div class="text-[10px] font-bold text-slate-400">S{{ match.week }}</div>
                        </div>

                        <!-- Résultat badge -->
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black shrink-0"
                             :class="isByeMatch(match) ? 'bg-slate-200 text-slate-400' :
                                    matchResult(match, calendarTeam.id)
                                        ? [matchResult(match, calendarTeam.id).bg, matchResult(match, calendarTeam.id).text]
                                        : 'bg-slate-100 text-slate-400'">
                            {{ isByeMatch(match) ? '—' : (matchResult(match, calendarTeam.id)?.label ?? '?') }}
                        </div>

                        <!-- Adversaire -->
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div v-if="!isByeMatch(match)" class="w-5 h-5 rounded-full overflow-hidden bg-slate-100 shrink-0">
                                <img v-if="teamLogoUrl(opponentTeam(match, calendarTeam.id))"
                                     :src="teamLogoUrl(opponentTeam(match, calendarTeam.id))"
                                     class="w-full h-full object-contain" alt=""/>
                            </div>
                            <span class="text-xs font-medium text-slate-700 truncate">
                                {{ isByeMatch(match) ? 'Repos' : opponentNameForTeam(match, calendarTeam.id) }}
                            </span>
                        </div>

                        <!-- Domicile/Extérieur -->
                        <div v-if="!isByeMatch(match)"
                             class="text-[10px] font-semibold px-1.5 py-0.5 rounded shrink-0"
                             :class="match.home_team_id === calendarTeam.id
                                ? 'bg-blue-100 text-blue-600'
                                : 'bg-slate-100 text-slate-500'">
                            {{ match.home_team_id === calendarTeam.id ? 'Dom.' : 'Ext.' }}
                        </div>

                        <!-- Score -->
                        <div class="text-xs font-black text-slate-700 w-12 text-right shrink-0">
                            <template v-if="!isByeMatch(match) && match.status === 'played'">
                                <span :class="(scoreFor(match, calendarTeam.id)?.scored ?? 0) > (scoreFor(match, calendarTeam.id)?.against ?? 0) ? 'text-emerald-600' : (scoreFor(match, calendarTeam.id)?.scored ?? 0) < (scoreFor(match, calendarTeam.id)?.against ?? 0) ? 'text-rose-500' : 'text-slate-500'">
                                    {{ scoreFor(match, calendarTeam.id)?.scored }}
                                </span>
                                <span class="text-slate-300 mx-0.5">-</span>
                                <span>{{ scoreFor(match, calendarTeam.id)?.against }}</span>
                            </template>
                            <span v-else-if="!isByeMatch(match)" class="text-slate-300 text-[10px]">vs</span>
                        </div>

                        <!-- Icône stats dispo -->
                        <div class="w-4 shrink-0">
                            <span v-if="match.status === 'played' && match.match_stats" class="text-teal-400 text-xs">📊</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- STATS MATCH SÉLECTIONNÉ                      -->
            <!-- ============================================ -->
            <div v-if="selectedCalendarMatch && selectedCalendarMatchStats"
                 class="col-span-8 flex flex-col gap-3 max-h-[830px] overflow-y-auto pr-1">

                <!-- Score du match -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <div class="flex items-center justify-around gap-4">

                        <!-- Équipe domicile -->
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-white border border-slate-200">
                                <img v-if="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
                                     :src="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
                                     class="w-full h-full object-contain" alt=""/>
                            </div>
                            <div class="text-xs font-semibold text-slate-700 text-center truncate max-w-[80px]">
                                {{ teamById[selectedCalendarMatch?.home_team_id]?.name }}
                            </div>
                        </div>

                        <!-- Score + bouton replay -->
                        <div class="text-center shrink-0">
                            <div class="text-3xl font-black text-slate-800">
                                {{ selectedCalendarMatch.home_score }}
                                <span class="text-slate-300 mx-1">-</span>
                                {{ selectedCalendarMatch.away_score }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-semibold mt-1">Semaine {{ selectedCalendarMatch.week }}</div>
                            <button type="button"
                                    @click="showReplay = true"
                                    class="mt-2 flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-bold bg-slate-800 text-white hover:bg-slate-700 transition-all mx-auto">
                                ▶ Résumé
                            </button>
                        </div>

                        <!-- Équipe extérieure -->
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-white border border-slate-200">
                                <img v-if="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                                     :src="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                                     class="w-full h-full object-contain" alt=""/>
                            </div>
                            <div class="text-xs font-semibold text-slate-700 text-center truncate max-w-[80px]">
                                {{ teamById[selectedCalendarMatch?.away_team_id]?.name }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barres comparatives équipes -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Comparatif équipes</h4>
                    <div class="space-y-2">
                        <div v-for="bar in statBars" :key="bar.label" class="flex items-center gap-2">
                            <!-- Valeur gauche (calendarTeam) -->
                            <div class="w-6 text-right text-xs font-black text-slate-700">{{ bar.my }}</div>

                            <!-- Barre -->
                            <div class="flex-1 h-3 bg-slate-100 rounded-full overflow-hidden flex">
                                <div class="h-full rounded-l-full transition-all"
                                     :class="bar.color"
                                     :style="{ width: statBarWidth(bar.my, bar.opp) + '%' }">
                                </div>
                                <div class="h-full rounded-r-full bg-slate-300 transition-all"
                                     :style="{ width: statBarWidth(bar.opp, bar.my) + '%' }">
                                </div>
                            </div>

                            <!-- Valeur droite (adversaire) -->
                            <div class="w-6 text-left text-xs font-black text-slate-500">{{ bar.opp }}</div>

                            <!-- Label -->
                            <div class="w-14 text-[10px] text-slate-400 text-right">{{ bar.label }}</div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-[12px] text-orange-500">
                        <span class="font-semibold text-blue-500">{{ calendarTeam?.name }}</span>
                        <span>{{ opponentNameForTeam(selectedCalendarMatch, calendarTeam?.id) }}</span>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- BLOC : Progression des joueurs               -->
                <!-- ============================================ -->
                <div v-if="selectedCalendarProgression.length" class="border border-slate-200 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 p-4">
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-3">
                        📈 Progression suite à ce match
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- HOME -->
                        <div>
                            <div class="text-xs font-bold text-slate-600 mb-2 pb-1.5 border-b border-slate-200">
                                {{ homeTeamName }}
                                <span class="text-[10px] text-slate-400 font-normal ml-1">
                    ({{ homeProgressors.length }} joueur{{ homeProgressors.length > 1 ? 's' : '' }})
                </span>
                            </div>
                            <div v-if="!homeProgressors.length" class="text-[11px] text-slate-400 italic py-2">
                                Aucune progression
                            </div>
                            <div v-else class="space-y-1.5">
                                <div v-for="p in homeProgressors" :key="p.player_id"
                                     class="flex items-center gap-2 p-2 bg-white/80 rounded-lg">
                                    <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                        <img v-if="p.photo_path" :src="`/storage/${p.photo_path}`" class="w-full h-full object-cover" alt=""/>
                                        <div v-else class="w-full h-full flex items-center justify-center text-[10px] text-slate-400">?</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-800 truncate">{{ p.lastname }}</div>
                                        <div class="flex flex-wrap gap-1 mt-0.5">
                            <span v-for="(detail, stat) in p.gains" :key="stat"
                                  :class="STAT_LABELS[stat]?.color"
                                  class="px-1.5 py-0.5 rounded text-[9px] font-bold inline-flex items-center gap-0.5">
                                <span>{{ STAT_LABELS[stat]?.icon }}</span>
                                <span>+{{ detail.gain }}</span>
                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0 px-1">
                                        <div class="text-lg font-black text-emerald-600 leading-none">+{{ p.total }}</div>
                                        <div class="text-[8px] text-slate-400 uppercase">pts</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AWAY -->
                        <div>
                            <div class="text-xs font-bold text-slate-600 mb-2 pb-1.5 border-b border-slate-200">
                                {{ awayTeamName }}
                                <span class="text-[10px] text-slate-400 font-normal ml-1">
                    ({{ awayProgressors.length }} joueur{{ awayProgressors.length > 1 ? 's' : '' }})
                </span>
                            </div>
                            <div v-if="!awayProgressors.length" class="text-[11px] text-slate-400 italic py-2">
                                Aucune progression
                            </div>
                            <div v-else class="space-y-1.5">
                                <div v-for="p in awayProgressors" :key="p.player_id"
                                     class="flex items-center gap-2 p-2 bg-white/80 rounded-lg">
                                    <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                        <img v-if="p.photo_path" :src="`/storage/${p.photo_path}`" class="w-full h-full object-cover" alt=""/>
                                        <div v-else class="w-full h-full flex items-center justify-center text-[10px] text-slate-400">?</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-800 truncate">{{ p.lastname }}</div>
                                        <div class="flex flex-wrap gap-1 mt-0.5">
                            <span v-for="(detail, stat) in p.gains" :key="stat"
                                  :class="STAT_LABELS[stat]?.color"
                                  class="px-1.5 py-0.5 rounded text-[9px] font-bold inline-flex items-center gap-0.5">
                                <span>{{ STAT_LABELS[stat]?.icon }}</span>
                                <span>+{{ detail.gain }}</span>
                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0 px-1">
                                        <div class="text-lg font-black text-emerald-600 leading-none">+{{ p.total }}</div>
                                        <div class="text-[8px] text-slate-400 uppercase">pts</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- Message si aucun match sélectionné -->
            <div v-else class="col-span-7 flex items-center justify-center rounded-xl border border-dashed border-slate-300 text-slate-400 text-sm">
                <div class="text-center p-8">
                    <div class="text-3xl mb-2">📊</div>
                    <p>Clique sur un match joué (📊) pour voir les statistiques</p>
                </div>
            </div>
        </div>
        <div v-if="calendarTeam && selectedCalendarMatch" class="grid grid-cols gap-4">
        <!-- Stats joueurs -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        Joueurs — {{ statsTeamName }}
                    </h4>
                    <div class="flex rounded-lg overflow-hidden border border-slate-200 text-[11px] font-semibold">
                        <button type="button" @click="selectedStatsTeam = 'home'"
                                class="px-3 py-1 transition-all"
                                :class="selectedStatsTeam === 'home' ? 'bg-teal-500 text-white' : 'bg-white text-slate-500 hover:bg-slate-50'">
                            {{ teamById[selectedCalendarMatch.home_team_id]?.name }}
                        </button>
                        <button type="button" @click="selectedStatsTeam = 'away'"
                                class="px-3 py-1 transition-all border-l border-slate-200"
                                :class="selectedStatsTeam === 'away' ? 'bg-teal-500 text-white' : 'bg-white text-slate-500 hover:bg-slate-50'">
                            {{ teamById[selectedCalendarMatch.away_team_id]?.name }}
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs min-w-max text-left">
                        <thead class="border-b border-slate-200">
                        <tr>
                            <th class="py-1.5 pr-3 text-slate-500 font-semibold">Joueur</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">Tirs</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">Passes</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">Drib.</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">Arrêts</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">Interc.</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">Tacles</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">Blocks</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">D+</th>
                            <th class="py-1.5 px-2 text-right text-slate-500 font-semibold">D-</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="p in (selectedStatsTeam === 'home'
            ? (selectedCalendarMatch.home_team_id === calendarTeam?.id ? calendarTeamRoster : calendarOpponentRoster)
            : (selectedCalendarMatch.away_team_id === calendarTeam?.id ? calendarTeamRoster : calendarOpponentRoster)
        )" :key="p.id"
                            class="border-b border-slate-100 last:border-0 hover:bg-white transition-colors">
                            <td class="py-1.5 pr-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                        <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt=""/>
                                        <div v-else class="w-full h-full flex items-center justify-center text-[8px] text-slate-400">?</div>
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-700 truncate max-w-[80px]">{{ p.lastname }}</div>
                                    </div>
                                </div>
                            </td>
                            <!-- Tirs — masqué pour GK -->
                            <td class="py-1.5 px-2 text-right font-semibold"
                                :class="isGK(p) ? 'text-slate-300' : 'text-slate-600'">
                                {{ isGK(p) ? '—' : playerMatchStat(p.id, 'offense.shot.attempts') }}
                            </td>
                            <!-- Passes -->
                            <td class="py-1.5 px-2 text-right font-semibold text-slate-600">
                                {{ playerMatchStat(p.id, 'offense.pass.attempts') }}
                            </td>
                            <!-- Dribbles — masqué pour GK -->
                            <td class="py-1.5 px-2 text-right font-semibold"
                                :class="isGK(p) ? 'text-slate-300' : 'text-slate-600'">
                                {{ isGK(p) ? '—' : playerMatchStat(p.id, 'offense.dribble.attempts') }}
                            </td>
                            <!-- Arrêts — uniquement pour GK, sinon — -->
                            <td class="py-1.5 px-2 text-right font-semibold"
                                :class="isGK(p) ? 'text-violet-600' : 'text-slate-300'">
                                {{ isGK(p) ? playerMatchStat(p.id, 'defense.hands.attempts') : '—' }}
                            </td>
                            <!-- Interceptions — masqué pour GK -->
                            <td class="py-1.5 px-2 text-right font-semibold"
                                :class="isGK(p) ? 'text-slate-300' : 'text-slate-600'">
                                {{ isGK(p) ? '—' : playerMatchStat(p.id, 'defense.intercept.attempts') }}
                            </td>
                            <!-- Tacles — masqué pour GK -->
                            <td class="py-1.5 px-2 text-right font-semibold"
                                :class="isGK(p) ? 'text-slate-300' : 'text-slate-600'">
                                {{ isGK(p) ? '—' : playerMatchStat(p.id, 'defense.tackle.attempts') }}
                            </td>
                            <!-- Blocks -->
                            <td class="py-1.5 px-2 text-right font-semibold text-slate-600">
                                {{ playerMatchStat(p.id, 'defense.block.attempts') }}
                            </td>
                            <!-- D+ -->
                            <td class="py-1.5 px-2 text-right font-bold text-emerald-600">
                                {{ playerMatchStat(p.id, 'duelsWon') }}
                            </td>
                            <!-- D- -->
                            <td class="py-1.5 px-2 text-right font-bold text-rose-500">
                                {{ playerMatchStat(p.id, 'duelsLost') }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Aucune équipe -->
        <div v-else class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
            Sélectionne une équipe ci-dessus
        </div>
    </div>
    <!-- ================================================ -->
    <!-- MODAL REPLAY                                      -->
    <!-- ================================================ -->
    <Teleport to="body">
        <div v-if="showReplay"
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
             @click.self="showReplay = false">

            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col overflow-hidden">

                <!-- Header -->
                <div class="p-4 bg-slate-800 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">▶</span>
                        <div>
                            <div class="font-black text-sm">
                                {{ teamById[selectedCalendarMatch.home_team_id]?.name }}
                                <span class="mx-2 text-slate-400">{{ selectedCalendarMatch.home_score }} - {{ selectedCalendarMatch.away_score }}</span>
                                {{ teamById[selectedCalendarMatch.away_team_id]?.name }}
                            </div>
                            <div class="text-[10px] text-slate-400">Semaine {{ selectedCalendarMatch.week }}</div>
                        </div>
                    </div>
                    <button type="button" @click="showReplay = false"
                            class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-sm transition-all">
                        ✕
                    </button>
                </div>

                <!-- Corps — deux colonnes home/away -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="grid grid-cols-2 gap-4">

                        <!-- HOME -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-6 h-6 rounded overflow-hidden bg-slate-100 shrink-0">
                                    <img v-if="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
                                         :src="teamLogoUrl(teamById[selectedCalendarMatch.home_team_id])"
                                         class="w-full h-full object-contain" alt=""/>
                                </div>
                                <span class="text-xs font-black text-slate-700">
                                        {{ teamById[selectedCalendarMatch.home_team_id]?.name }}
                                    </span>
                                <span class="text-lg font-black text-emerald-600 ml-auto">
                                        {{ selectedCalendarMatch.home_score }}
                                    </span>
                            </div>

                            <div v-if="homeTeamEvents.length" class="space-y-2">
                                <div v-for="(ev, i) in homeTeamEvents" :key="i"
                                     class="flex items-start gap-2 p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[11px] shrink-0"
                                         :class="ev.color">
                                        {{ ev.icon }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-slate-700">{{ ev.text }}</div>
                                        <div v-if="ev.detail" class="text-[10px] text-slate-400">{{ ev.detail }}</div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs text-slate-400 italic">Aucun événement notable.</p>
                        </div>

                        <!-- AWAY -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-6 h-6 rounded overflow-hidden bg-slate-100 shrink-0">
                                    <img v-if="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                                         :src="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                                         class="w-full h-full object-contain" alt=""/>
                                </div>
                                <span class="text-xs font-black text-slate-700">
                                        {{ teamById[selectedCalendarMatch.away_team_id]?.name }}
                                    </span>
                                <span class="text-lg font-black text-emerald-600 ml-auto">
                                        {{ selectedCalendarMatch.away_score }}
                                    </span>
                            </div>

                            <div v-if="awayTeamEvents.length" class="space-y-2">
                                <div v-for="(ev, i) in awayTeamEvents" :key="i"
                                     class="flex items-start gap-2 p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[11px] shrink-0"
                                         :class="ev.color">
                                        {{ ev.icon }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-slate-700">{{ ev.text }}</div>
                                        <div v-if="ev.detail" class="text-[10px] text-slate-400">{{ ev.detail }}</div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs text-slate-400 italic">Aucun événement notable.</p>
                        </div>
                    </div>

                    <!-- Stats équipe résumé -->
                    <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4 text-center text-xs">
                        <div class="flex justify-around text-slate-500">
                            <div><div class="font-black text-slate-800">{{ selectedCalendarMatchStats?.teams?.home?.shots ?? 0 }}</div><div>Tirs</div></div>
                            <div><div class="font-black text-slate-800">{{ selectedCalendarMatchStats?.teams?.home?.passes ?? 0 }}</div><div>Passes</div></div>
                            <div><div class="font-black text-slate-800">{{ selectedCalendarMatchStats?.teams?.home?.saves ?? 0 }}</div><div>Arrêts</div></div>
                            <div><div class="font-black text-slate-800">{{ selectedCalendarMatchStats?.teams?.home?.duelsWon ?? 0 }}</div><div>D+</div></div>
                        </div>
                        <div class="flex justify-around text-slate-500">
                            <div><div class="font-black text-slate-800">{{ selectedCalendarMatchStats?.teams?.away?.shots ?? 0 }}</div><div>Tirs</div></div>
                            <div><div class="font-black text-slate-800">{{ selectedCalendarMatchStats?.teams?.away?.passes ?? 0 }}</div><div>Passes</div></div>
                            <div><div class="font-black text-slate-800">{{ selectedCalendarMatchStats?.teams?.away?.saves ?? 0 }}</div><div>Arrêts</div></div>
                            <div><div class="font-black text-slate-800">{{ selectedCalendarMatchStats?.teams?.away?.duelsWon ?? 0 }}</div><div>D+</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
