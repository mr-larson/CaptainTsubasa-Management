<script setup>
const props = defineProps({
    calendarTeams:                 { type: Array,   required: true },
    calendarTeam:                  { type: Object,  default: null },
    calendarRows:                  { type: Array,   required: true },
    calendarTeamRoster:            { type: Array,   required: true },
    teamById:                      { type: Object,  required: true },
    selectedCalendarMatch:         { type: Object,  default: null },
    selectedCalendarMatchStats:    { type: Object,  default: null },
    selectedCalendarMyTeamStats:   { type: Object,  default: null },
    selectedCalendarOpponentStats: { type: Object,  default: null },
    selectedCalendarPlayersStats:  { type: Object,  required: true },
    isByeMatch:                    { type: Function, required: true },
    opponentNameForTeam:           { type: Function, required: true },
});

const emit = defineEmits(['select-team', 'open-match-stats']);
</script>

<template>
    <div class="flex-1 flex gap-4">

        <!-- Liste équipes -->
        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
            <h3 class="text-md font-semibold text-slate-700 mb-2">Equipes</h3>
            <div v-if="calendarTeams.length" class="max-h-96 overflow-y-auto space-y-1">
                <button v-for="t in calendarTeams" :key="t.id" type="button"
                        @click="emit('select-team', t)"
                        :class="['w-full text-left text-sm px-2 py-1 rounded',
                        calendarTeam?.id === t.id ? 'bg-teal-100 text-slate-900' : 'bg-white hover:bg-slate-100 text-slate-700']">
                    {{ t.name }}
                </button>
            </div>
            <p v-else class="text-sm text-slate-500">Aucune équipe trouvée.</p>
        </div>

        <div class="flex-1">
            <!-- Tableau calendrier -->
            <div v-if="calendarTeam" class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                <h3 class="text-lg font-semibold text-slate-700 mb-2">Calendrier : {{ calendarTeam.name }}</h3>
                <p class="text-xs text-slate-500 mb-3">Un match aller et un match retour contre chaque équipe de la ligue.</p>
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
                        <tr v-for="match in calendarRows" :key="match.id"
                            class="border-b last:border-b-0 cursor-pointer hover:bg-teal-50"
                            @click="!isByeMatch(match) && match.status === 'played' && match.match_stats && emit('open-match-stats', match)">
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

            <!-- Stats match sélectionné -->
            <div v-if="selectedCalendarMatch && selectedCalendarMatchStats" class="border border-slate-200 rounded-lg bg-slate-50 p-4 mt-4">
                <h4 class="text-md font-semibold text-slate-700 mb-2">Stats du match — Semaine {{ selectedCalendarMatch.week }}</h4>
                <p class="text-sm text-slate-600 mb-3">
                    {{ teamById[selectedCalendarMatch.home_team_id]?.name }}
                    {{ selectedCalendarMatch.home_score }} - {{ selectedCalendarMatch.away_score }}
                    {{ teamById[selectedCalendarMatch.away_team_id]?.name }}
                </p>
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
                <h5 class="text-sm font-semibold text-slate-700 mb-2">Stats par joueur ({{ calendarTeam?.name }})</h5>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs md:text-sm min-w-max text-left">
                        <thead class="text-[10px] uppercase text-slate-500 border-b">
                        <tr>
                            <th class="py-1 pr-2">Joueur</th>
                            <th v-for="h in ['Tirs','Passes','Dribbles','Interc.','Tacles','Blocks','Duels +','Duels -']" :key="h" class="py-1 pr-2 text-right">{{ h }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="p in calendarTeamRoster" :key="p.id" class="border-b last:border-b-0">
                            <td class="py-1 pr-2">{{ p.firstname }} {{ p.lastname }}</td>
                            <td class="py-1 pr-2 text-right">{{ selectedCalendarPlayersStats[p.id]?.offense?.shot?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ selectedCalendarPlayersStats[p.id]?.offense?.pass?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ selectedCalendarPlayersStats[p.id]?.offense?.dribble?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ selectedCalendarPlayersStats[p.id]?.defense?.intercept?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ selectedCalendarPlayersStats[p.id]?.defense?.tackle?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ selectedCalendarPlayersStats[p.id]?.defense?.block?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ selectedCalendarPlayersStats[p.id]?.duelsWon ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ selectedCalendarPlayersStats[p.id]?.duelsLost ?? 0 }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <p v-else class="text-sm text-slate-500 mt-2">Clique sur un match joué pour afficher ses statistiques.</p>
        </div>
    </div>
</template>
