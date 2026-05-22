<script setup>
const props = defineProps({
    standings: { type: Array,  required: true },
    team:      { type: Object, default: null },
});
</script>

<template>
    <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4">
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
                <tr v-for="(row, index) in standings" :key="row.id"
                    :class="['border-b last:border-b-0', team?.id === row.id ? 'bg-teal-50 font-semibold' : '']">
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
</template>
