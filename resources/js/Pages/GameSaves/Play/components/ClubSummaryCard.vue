<script setup>
import { computed } from 'vue';
import Panel from '@/Components/Panel.vue';
import { averageTeamStat } from '../teamStats.js';
import { usePlayerUtils } from '../usePlayerUtils.js';

/** Carte « Club » : moyennes de l'effectif et absences. Même calcul pour toutes les équipes. */
const props = defineProps({
    players:     { type: Array,  default: () => [] },
    injuries:    { type: Number, default: 0 },
    suspensions: { type: Number, default: 0 },
    cards:       { type: Number, default: 0 },
});

const { moraleState } = usePlayerUtils();

const average = (key) => averageTeamStat(props.players, key) ?? 0;
const averageMorale = computed(() => props.players.length
    ? Math.round(props.players.reduce((sum, p) => sum + Number(p.morale ?? 60), 0) / props.players.length)
    : 0);
const morale = computed(() => moraleState(averageMorale.value));

const rows = computed(() => [
    { label: 'Attaque moy.',    value: average('attack') },
    { label: 'Blessés',         value: props.injuries,    alert: props.injuries > 0 ? 'text-rose-500' : null },
    { label: 'Défense moy.',    value: average('defense') },
    { label: 'Suspensions',     value: props.suspensions, alert: props.suspensions > 0 ? 'text-amber-500' : null },
    { label: 'Endurance moy.',  value: average('stamina') },
    { label: 'Vitesse moy.',    value: average('speed') },
    { label: 'Moral moy.',      value: `${morale.value.emoji} ${averageMorale.value}`, alert: morale.value.text },
    { label: 'Joueurs avertis', value: props.cards,       alert: props.cards > 0 ? 'text-yellow-500' : null },
]);
</script>

<template>
    <Panel title="Club">
        <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
            <div v-for="row in rows" :key="row.label" class="flex justify-between gap-2">
                <dt>{{ row.label }}</dt>
                <dd class="font-bold tabular-nums" :class="row.alert ?? 'text-slate-700'">{{ row.value }}</dd>
            </div>
        </dl>
    </Panel>
</template>
