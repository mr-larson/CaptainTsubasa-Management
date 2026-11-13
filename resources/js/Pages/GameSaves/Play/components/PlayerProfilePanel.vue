<script setup>
import PlayerChemistryCard from './PlayerChemistryCard.vue';
import PlayerIdentityCard from './PlayerIdentityCard.vue';
import PlayerMoraleCard from './PlayerMoraleCard.vue';
import PlayerStatusAlert from './PlayerStatusAlert.vue';
import PerfChips from './PerfChips.vue';
import RadarChart from './RadarChart.vue';
import StatBars from './StatBars.vue';

/**
 * Fiche complète du joueur sélectionné (Mon équipe, Autres équipes) :
 * alerte de statut, identité (+ actions), moral, radar, stats, perfs, alchimie.
 * Slots `actions` et `footer` transmis à la carte d'identité.
 */
defineProps({
    player:            { type: Object,  default: null },
    // Fonctions de statut passées à PlayerStatusAlert (isPlayerInjured, playerInjury…)
    status:            { type: Object,  default: () => ({}) },
    morale:            { type: Object,  default: () => ({ logs: [], promises: [], declarations: [] }) },
    moraleLoading:     { type: Boolean, default: false },
    moraleEditable:    { type: Boolean, default: true },
    chemistry:         { type: Array,   default: () => [] },
    chemistryEditable: { type: Boolean, default: true },
    gameSaveId:        { type: Number,  required: true },
    currentWeek:       { type: Number,  default: 1 },
    currentSeason:     { type: Number,  default: 1 },
    perf:              { type: Object,  default: null },
    accent:            { type: String,  default: 'teal' },
    emptyText:         { type: String,  default: 'Sélectionne un joueur dans la liste ou sur le terrain' },
});

const emit = defineEmits(['make-promise', 'make-declaration']);
</script>

<template>
    <div class="min-w-0 flex flex-col gap-3">
        <template v-if="player">
            <PlayerStatusAlert :player="player" v-bind="status" />

            <PlayerIdentityCard :player="player">
                <template v-if="$slots.actions" #actions><slot name="actions" /></template>
                <template #footer>
                    <slot name="footer" />
                    <p v-if="player.description" class="mt-2 text-xs text-slate-500 italic">{{ player.description }}</p>
                </template>
            </PlayerIdentityCard>

            <PlayerMoraleCard :player="player"
                              :class="moraleLoading ? 'opacity-60' : ''"
                              :logs="morale.logs ?? []" :promises="morale.promises ?? []" :declarations="morale.declarations ?? []"
                              :currentWeek="currentWeek" :currentSeason="currentSeason"
                              :editable="moraleEditable"
                              @make-promise="(p, t) => emit('make-promise', p, t)"
                              @make-declaration="(p, t) => emit('make-declaration', p, t)" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <RadarChart :player="player" :accent="accent" />
                <StatBars :player="player" />
            </div>

            <PerfChips :player="player" :perf="perf" />

            <PlayerChemistryCard :key="player.id"
                                 :gameSaveId="gameSaveId"
                                 :playerId="player.id"
                                 :relations="chemistry"
                                 :editable="chemistryEditable" />
        </template>

        <div v-else class="flex items-center justify-center rounded-xl border border-dashed border-slate-200 p-10 text-slate-400 text-sm">
            {{ emptyText }}
        </div>
    </div>
</template>
