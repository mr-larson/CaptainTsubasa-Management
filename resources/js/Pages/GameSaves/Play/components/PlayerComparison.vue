<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import { positionLabel } from '@/Enums/positionBadges.js';
import { overallOf, STAT_COLORS, usePlayerUtils } from '../usePlayerUtils.js';
import { playerStat, positionRating } from '../recruitment.js';
import { TEAM_STATS } from '../teamStats.js';

const props = defineProps({
    modelValue: { type: Array, required: true },
    cost: { type: Function, default: null },
    costLabel: { type: String, default: 'Coût' },
    availability: { type: Function, default: null },
});
const emit = defineEmits(['update:modelValue']);
const { keyStatsFor } = usePlayerUtils();

function close() {
    emit('update:modelValue', []);
}

const rows = computed(() => [
    ...(props.cost ? [{ key: 'cost', label: props.costLabel, value: props.cost, lower: true }] : []),
    { key: 'overall', label: 'Note globale', value: overallOf },
    { key: 'profile', label: 'Note au poste', value: positionRating },
    { key: 'stamina', label: 'Endurance' },
    { key: 'morale', label: 'Moral' },
    ...TEAM_STATS.filter(stat => stat.key !== 'stamina'),
]);
const value = (row, player) => row.value ? row.value(player) : Math.round(playerStat(player, row.key));
const better = (row, a, b) => row.lower ? value(row, a) < value(row, b) : value(row, a) > value(row, b);
</script>

<template>
    <Modal :show="modelValue.length === 2" max-width="2xl" title="Comparaison de joueurs" @close="close">
            <table v-if="modelValue.length === 2" class="w-full table-fixed text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-2 py-2 text-left text-slate-500">Statistique</th>
                        <th v-for="p in modelValue" :key="p.id" scope="col" class="break-words px-2 py-2 text-slate-700">
                            {{ p.firstname }} {{ p.lastname }}
                            <span class="block font-normal text-slate-500">{{ positionLabel(p.position) }}{{ p.comparisonTeamName ? ` · ${p.comparisonTeamName}` : '' }}</span>
                            <span v-if="availability" class="block font-normal text-slate-500">{{ availability(p) }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.key" class="border-b border-slate-100">
                        <th scope="row" class="px-2 py-1.5 text-left font-medium text-slate-500">
                            <span v-if="row.color ?? STAT_COLORS[row.key]" class="mr-1 inline-block h-2 w-2 rounded-full" :class="row.color ?? STAT_COLORS[row.key]"></span>
                            {{ row.label }}
                        </th>
                        <td v-for="(p, i) in modelValue" :key="p.id" class="px-2 py-1.5 text-center tabular-nums"
                            :class="better(row, p, modelValue[1 - i]) ? 'bg-teal-50 font-bold text-teal-700' : 'text-slate-600'">
                            <span :class="{ 'font-bold': keyStatsFor(p.position).includes(row.key) }">{{ value(row, p) }}</span>
                            <GameBanknote v-if="row.key === 'cost'" />
                        </td>
                    </tr>
                </tbody>
            </table>
    </Modal>
</template>
