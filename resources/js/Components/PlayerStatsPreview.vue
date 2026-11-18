<script setup>
import { computed } from 'vue';
import { useAnimatedStats } from '@/Composables/useAnimatedStats.js';
import { PLAYER_STATS } from '@/Enums/playerStats.js';

// Aperçu visuel des statistiques d'un joueur (barres). `stats` = { speed: 53, … }.
const props = defineProps({
    stats: { type: Object, required: true },
    playerId: { type: [Number, String], default: null },
    editable: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, default: '' },
});

const emit = defineEmits(['update:stat']);
const statError = key => props.errors[`${props.errorPrefix}${key}`];
const updateStat = (key, event) => {
    const value = event.target.value;
    emit('update:stat', key, value === '' ? '' : Number(value));
};

const bars = computed(() =>
    PLAYER_STATS.map((def) => ({ ...def, value: Number(props.stats[def.key] ?? 0) })).filter((s) => props.editable || s.value > 0),
);
const { root, value: animatedValue, width: animatedWidth } = useAnimatedStats(
    () => Object.fromEntries(bars.value.map(stat => [stat.key, stat.value])),
    () => props.playerId,
);
</script>

<template>
    <div ref="root" v-if="bars.length" class="mb-4 border border-slate-200 rounded-xl bg-white p-3 shadow-card">
        <div class="flex flex-wrap items-baseline justify-between gap-1 mb-2">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ editable ? 'Statistiques' : 'Aperçu des statistiques' }}</h4>
            <p v-if="editable" class="text-[11px] text-slate-400">Clique sur une valeur pour la modifier.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5">
            <div v-for="stat in bars" :key="stat.key" class="min-w-0">
                <div class="flex items-center gap-2">
                    <label v-if="editable" :for="`stat-${stat.key}`" class="w-16 text-[11px] text-slate-500 shrink-0">{{ stat.short ?? stat.label }}</label>
                    <span v-else class="w-16 text-[11px] text-slate-500 shrink-0">{{ stat.short ?? stat.label }}</span>
                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" :class="stat.color"
                             :style="{ width: animatedWidth(stat.key) }"></div>
                    </div>
                    <input v-if="editable" :id="`stat-${stat.key}`" type="number" min="0" max="100" step="1"
                           :value="stats[stat.key] ?? 0" :aria-label="stat.label"
                           :aria-invalid="!!statError(stat.key)" :aria-describedby="statError(stat.key) ? `stat-error-${stat.key}` : undefined"
                           :title="`Modifier : ${stat.label}`"
                           class="stat-value w-12 shrink-0 rounded border border-transparent bg-transparent px-1 py-0.5 text-right text-[11px] font-bold text-slate-700 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-1 focus:ring-teal-500"
                           :class="{ 'border-rose-400': statError(stat.key) }"
                           @input="updateStat(stat.key, $event)" @focus="$event.target.select()"
                           @keydown.enter.prevent="$event.target.blur()" />
                    <span v-else class="w-7 text-right text-[11px] font-bold text-slate-700">{{ animatedValue(stat.key) }}</span>
                </div>
                <p v-if="editable && statError(stat.key)" :id="`stat-error-${stat.key}`" class="mt-1 text-xs text-rose-600">{{ statError(stat.key) }}</p>
            </div>
        </div>
    </div>
</template>


<style scoped>
.stat-value { appearance: textfield; -moz-appearance: textfield; }
.stat-value::-webkit-inner-spin-button,
.stat-value::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
</style>
