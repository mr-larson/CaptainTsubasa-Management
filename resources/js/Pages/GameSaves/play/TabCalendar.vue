<script setup>
import { computed } from 'vue';

const props = defineProps({
    calendarTeams:                 { type: Array,    required: true },
    calendarTeam:                  { type: Object,   default: null },
    calendarRows:                  { type: Array,    required: true },
    calendarTeamRoster:            { type: Array,    required: true },
    teamById:                      { type: Object,   required: true },
    selectedCalendarMatch:         { type: Object,   default: null },
    selectedCalendarMatchStats:    { type: Object,   default: null },
    selectedCalendarMyTeamStats:   { type: Object,   default: null },
    selectedCalendarOpponentStats: { type: Object,   default: null },
    selectedCalendarPlayersStats:  { type: Object,   required: true },
    isByeMatch:                    { type: Function, required: true },
    opponentNameForTeam:           { type: Function, required: true },
});

const emit = defineEmits(['select-team', 'open-match-stats']);

// ==========================
//   HELPERS
// ==========================
const teamLogoUrl = (t) => {
    const path = t?.logo_path ?? t?.team?.logo_path;
    if (!path) return null;
    if (path.startsWith('http')) return path;
    if (path.startsWith('/'))    return path;
    if (path.startsWith('teams/')) return '/images/' + path;
    return '/' + path;
};

const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path)         return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
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
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[72vh] pr-1">

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
                 :class="selectedCalendarMatch && selectedCalendarMatchStats ? 'col-span-5' : 'col-span-12'">

                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg overflow-hidden bg-white border border-slate-200 shrink-0">
                        <img v-if="teamLogoUrl(calendarTeam)" :src="teamLogoUrl(calendarTeam)" class="w-full h-full object-contain" alt=""/>
                    </div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ calendarTeam.name }}</h3>
                </div>

                <div class="space-y-1 max-h-[480px] overflow-y-auto pr-1">
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
                 class="col-span-7 flex flex-col gap-3">

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
                                {{ teamById[selectedCalendarMatch.home_team_id]?.name }}
                            </div>
                        </div>

                        <!-- Score -->
                        <div class="text-center shrink-0">
                            <div class="text-3xl font-black text-slate-800">
                                {{ selectedCalendarMatch.home_score }}
                                <span class="text-slate-300 mx-1">-</span>
                                {{ selectedCalendarMatch.away_score }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-semibold mt-1">Semaine {{ selectedCalendarMatch.week }}</div>
                        </div>

                        <!-- Équipe extérieure -->
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-white border border-slate-200">
                                <img v-if="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                                     :src="teamLogoUrl(teamById[selectedCalendarMatch.away_team_id])"
                                     class="w-full h-full object-contain" alt=""/>
                            </div>
                            <div class="text-xs font-semibold text-slate-700 text-center truncate max-w-[80px]">
                                {{ teamById[selectedCalendarMatch.away_team_id]?.name }}
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
                    <div class="flex justify-between mt-2 text-[10px] text-slate-400">
                        <span class="font-semibold text-teal-600">{{ calendarTeam?.name }}</span>
                        <span>{{ opponentNameForTeam(selectedCalendarMatch, calendarTeam?.id) }}</span>
                    </div>
                </div>

                <!-- Stats joueurs -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">
                        Joueurs — {{ calendarTeam?.name }}
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs min-w-max text-left">
                            <thead class="border-b border-slate-200">
                            <tr>
                                <th class="py-1.5 pr-3 text-slate-500 font-semibold">Joueur</th>
                                <th v-for="h in ['Tirs','Passes','Drib.','Interc.','Tacles','Blocks','D+','D-']"
                                    :key="h" class="py-1.5 px-2 text-right text-slate-500 font-semibold">{{ h }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="p in calendarTeamRoster" :key="p.id"
                                class="border-b border-slate-100 last:border-0 hover:bg-white transition-colors">
                                <td class="py-1.5 pr-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                            <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt=""/>
                                            <div v-else class="w-full h-full flex items-center justify-center text-[8px] text-slate-400">?</div>
                                        </div>
                                        <span class="font-medium text-slate-700 truncate max-w-[80px]">{{ p.lastname }}</span>
                                    </div>
                                </td>
                                <td class="py-1.5 px-2 text-right font-semibold text-slate-600">{{ playerMatchStat(p.id, 'offense.shot.attempts') }}</td>
                                <td class="py-1.5 px-2 text-right font-semibold text-slate-600">{{ playerMatchStat(p.id, 'offense.pass.attempts') }}</td>
                                <td class="py-1.5 px-2 text-right font-semibold text-slate-600">{{ playerMatchStat(p.id, 'offense.dribble.attempts') }}</td>
                                <td class="py-1.5 px-2 text-right font-semibold text-slate-600">{{ playerMatchStat(p.id, 'defense.intercept.attempts') }}</td>
                                <td class="py-1.5 px-2 text-right font-semibold text-slate-600">{{ playerMatchStat(p.id, 'defense.tackle.attempts') }}</td>
                                <td class="py-1.5 px-2 text-right font-semibold text-slate-600">{{ playerMatchStat(p.id, 'defense.block.attempts') }}</td>
                                <td class="py-1.5 px-2 text-right font-bold text-emerald-600">{{ playerMatchStat(p.id, 'duelsWon') }}</td>
                                <td class="py-1.5 px-2 text-right font-bold text-rose-500">{{ playerMatchStat(p.id, 'duelsLost') }}</td>
                            </tr>
                            </tbody>
                        </table>
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

        <!-- Aucune équipe -->
        <div v-else class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
            Sélectionne une équipe ci-dessus
        </div>
    </div>
</template>
