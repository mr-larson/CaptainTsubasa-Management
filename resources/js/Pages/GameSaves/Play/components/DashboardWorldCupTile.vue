<script setup>
// Tuile « Poule » du Dashboard en Coupe du Monde : position dans la poule, étape du tournoi et parcours
// (qualifié, éliminé, finaliste…). Domaine : Coupe du Monde. Utilisée par Pages/GameSaves/Play/TabDashboard.vue.
import SectionTitle from '@/Components/SectionTitle.vue';
import { computed } from 'vue';
import { outcomeOf, stageLabel, standingOf } from '../worldCup.js';

const props = defineProps({
    tournament: { type: Object, default: null },
    team: { type: Object, default: null },
});
const emit = defineEmits(['open']);

const KPI = 'border border-slate-200 rounded-xl bg-slate-50 p-2 text-left transition-colors hover:bg-slate-100 hover:border-slate-200';

const standing = computed(() => props.team ? standingOf(props.tournament, props.team.id) : null);
const outcome = computed(() => props.team ? outcomeOf(props.tournament, props.team.id) : null);
</script>

<template>
    <button type="button" :class="KPI" class="flex flex-col items-center gap-1" title="Voir le tournoi" @click="emit('open')">
        <SectionTitle as="span">
            {{ standing ? 'Poule ' + standing.group : 'Tournoi' }}
        </SectionTitle>
        <span class="text-3xl font-black text-indigo-600">
            {{ standing ? standing.position : '—' }}<sup v-if="standing" class="text-base">e</sup>
        </span>
        <span class="text-2xs text-slate-400">{{ standing ? '/ ' + standing.total : stageLabel(tournament?.stage) }}</span>
        <span v-if="outcome" class="text-2xs font-bold px-2 py-0.5 rounded-full text-center"
              :class="outcome.tone === 'amber' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-600'">
            {{ outcome.label }}
        </span>
        <span v-else-if="standing" class="text-2xs font-bold px-2 py-0.5 rounded-full"
              :class="standing.qualified ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-200 text-slate-500'">
            {{ standing.qualified ? (tournament?.stage === 'group' ? 'En zone de qualification' : 'Qualifié') : standing.points + ' pts' }}
        </span>
    </button>
</template>
