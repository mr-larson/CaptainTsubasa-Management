<script setup>
const props = defineProps({
    teams:                   { type: Array,   required: true },
    selectedStatsTeamId:     { type: Number,  default: null },
    selectedStatsTeam:       { type: Object,  default: null },
    teamStats:               { type: Object,  required: true },
    selectedTeamPlayerStats: { type: Array,   required: true },
});

const emit = defineEmits(['select-team']);
</script>

<template>
    <div class="flex-1 flex gap-4">
        <!-- Sélection équipe -->
        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
            <h3 class="text-md font-semibold text-slate-700 mb-2">Équipes</h3>
            <div v-if="teams.length" class="max-h-96 overflow-y-auto space-y-1">
                <button v-for="t in teams" :key="t.id" type="button"
                        @click="emit('select-team', t.id)"
                        :class="['w-full text-left text-sm px-2 py-1 rounded',
                        selectedStatsTeamId === t.id ? 'bg-teal-100 text-slate-900' : 'bg-white hover:bg-slate-100 text-slate-700']">
                    {{ t.name }}
                </button>
            </div>
            <p v-else class="text-sm text-slate-500">Aucune équipe trouvée.</p>
        </div>

        <!-- Stats -->
        <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-6">
            <h3 class="text-xl font-bold text-slate-800 mb-4">📊 Stats d'équipe <span v-if="selectedStatsTeam">— {{ selectedStatsTeam.name }}</span></h3>
            <template v-if="selectedStatsTeam">
                <h4 class="text-md font-semibold text-slate-700 mb-2">Stats globales</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-6">
                    <div v-for="stat in [
                        { label: 'Tirs', key: 'shots' }, { label: 'Passes', key: 'passes' },
                        { label: 'Dribbles', key: 'dribbles' }, { label: 'Interceptions', key: 'intercepts' },
                        { label: 'Tacles', key: 'tackles' }, { label: 'Blocks', key: 'blocks' },
                        { label: 'Duels gagnés', key: 'duelsWon' }, { label: 'Duels perdus', key: 'duelsLost' },
                    ]" :key="stat.key" class="p-3 border rounded bg-white">
                        <p class="font-medium">{{ stat.label }}</p>
                        <p>{{ teamStats[stat.key] }}</p>
                    </div>
                </div>
                <h4 class="text-md font-semibold text-slate-700 mb-3">Stats des joueurs</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-max text-left">
                        <thead class="text-xs uppercase text-slate-500 border-b">
                        <tr>
                            <th class="py-1 pr-2">Joueur</th>
                            <th v-for="h in ['Tirs','Passes','Dribbles','Interc.','Tacles','Blocks','Duels +','Duels –']" :key="h" class="py-1 pr-2 text-right">{{ h }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="p in selectedTeamPlayerStats" :key="p.id" class="border-b last:border-b-0">
                            <td class="py-1 pr-2">{{ p.firstname }} {{ p.lastname }}</td>
                            <td class="py-1 pr-2 text-right">{{ p.stats?.offense?.shot?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ p.stats?.offense?.pass?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ p.stats?.offense?.dribble?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ p.stats?.defense?.intercept?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ p.stats?.defense?.tackle?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ p.stats?.defense?.block?.attempts ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ p.stats?.duelsWon ?? 0 }}</td>
                            <td class="py-1 pr-2 text-right">{{ p.stats?.duelsLost ?? 0 }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <p v-else class="text-slate-500 text-sm">Sélectionne une équipe dans la liste à gauche.</p>
        </div>
    </div>
</template>
