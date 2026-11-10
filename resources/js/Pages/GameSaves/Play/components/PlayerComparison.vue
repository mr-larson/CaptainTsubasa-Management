<script setup>
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import { positionLabel } from '@/Enums/positionBadges.js';
import { overallOf, STAT_COLORS, usePlayerUtils } from '../usePlayerUtils.js';
import { playerStat, positionRating } from '../recruitment.js';
import { TEAM_STATS } from '../teamStats.js';

const props = defineProps({
    player: { type: Object, default: null },
    modelValue: { type: Array, default: null },
    cost: { type: Function, default: null },
    costLabel: { type: String, default: 'Coût' },
    availability: { type: Function, default: null },
    openOnSecond: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
const local = ref([]);
const players = computed(() => props.modelValue ?? local.value);
const open = ref(false);
const { keyStatsFor } = usePlayerUtils();
const selected = computed(() => players.value.some(p => p.id === props.player?.id));

function change(next) {
    local.value = next;
    emit('update:modelValue', next);
    if (next.length < 2) open.value = false;
}

function toggle(player) {
    change(players.value.some(p => p.id === player.id)
        ? players.value.filter(p => p.id !== player.id)
        : [...players.value.slice(-1), player]);
}

function close() {
    open.value = false;
    if (props.openOnSecond) change([]);
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
    <div v-if="!openOnSecond" class="flex flex-wrap items-center gap-2">
        <ButtonSecondary v-if="player" size="sm" @click="toggle(player)">
            {{ selected ? 'Retirer du comparatif' : '＋ Ajouter au comparatif' }}
        </ButtonSecondary>
        <ButtonSecondary size="sm" :disabled="players.length < 2" @click="open = true">
            ⚖️ Comparer ({{ players.length }}/2)
        </ButtonSecondary>
        <button v-for="p in players" :key="p.id" type="button"
                class="rounded-full bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-700 hover:bg-teal-100"
                :aria-label="`Retirer ${p.firstname} ${p.lastname} du comparatif`" @click="toggle(p)">
            {{ p.lastname }} ×
        </button>
        <span v-if="players.length === 1" class="text-xs text-slate-500">Choisis un second joueur.</span>
        <span v-else-if="!player && !players.length" class="text-xs text-slate-500">Sélectionne deux joueurs avec ⚖️.</span>
        <button v-if="players.length" type="button" class="text-xs text-slate-500 hover:text-teal-700" @click="change([])">Vider</button>
    </div>

    <Modal :show="players.length === 2 && (openOnSecond || open)" max-width="2xl" @close="close">
        <div class="space-y-3 p-4 sm:p-5">
            <header class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-bold text-slate-700">Comparaison de joueurs</h3>
                <ButtonSecondary size="sm" @click="close">Fermer</ButtonSecondary>
            </header>
            <table v-if="players.length === 2" class="w-full table-fixed text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-2 py-2 text-left text-slate-500">Statistique</th>
                        <th v-for="p in players" :key="p.id" scope="col" class="break-words px-2 py-2 text-slate-700">
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
                        <td v-for="(p, i) in players" :key="p.id" class="px-2 py-1.5 text-center tabular-nums"
                            :class="better(row, p, players[1 - i]) ? 'bg-teal-50 font-bold text-teal-700' : 'text-slate-600'">
                            <span :class="{ 'font-bold': keyStatsFor(p.position).includes(row.key) }">{{ value(row, p) }}</span>
                            <GameBanknote v-if="row.key === 'cost'" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Modal>
</template>
