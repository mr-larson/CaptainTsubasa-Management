<script setup>
import { ISSUE_CHIP } from './useLineupIssues.js';

// Checklist « Avant le match » (cf. usePreMatchChecklist) : actions de la semaine
// + état du onze. Un clic mène à l'onglet concerné (et au joueur s'il y en a un).
defineProps({
    actions: { type: Array, default: () => [] },
    squad:   { type: Array, default: () => [] },
    // Dans le Dashboard (téléphone/tablette) : même fond que les autres cartes.
    inDashboard: { type: Boolean, default: false },
    // { available, total } : joueurs ni blessés, ni suspendus, ni consignés
    availability: { type: Object, default: null },
});

const emit = defineEmits(['open']);

const CHIP = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] font-semibold text-left transition-all';
const LEVEL_CHIP = { ...ISSUE_CHIP, action: 'bg-teal-50 border-teal-200 text-teal-700 hover:bg-teal-100' };
</script>

<template>
    <div class="border border-slate-200 rounded-xl flex flex-col gap-3"
         :class="inDashboard ? 'bg-slate-50 p-4' : 'bg-white p-3'">
        <div v-if="actions.length">
            <div class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Avant le match</div>
            <div class="flex flex-wrap items-start gap-1.5">
                <button v-for="item in actions" :key="item.key" type="button"
                        @click="emit('open', item)"
                        :class="[CHIP, LEVEL_CHIP[item.level]]">
                    {{ item.text }}
                </button>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">État du onze</span>
                <span v-if="availability" class="text-2xs font-semibold"
                      :class="availability.available < availability.total ? 'text-amber-600' : 'text-slate-400'"
                      title="Joueurs ni blessés, ni suspendus, ni consignés">
                    {{ availability.available }}/{{ availability.total }} disponibles
                </span>
            </div>
            <div class="flex flex-wrap items-start gap-1.5">
                <button v-for="item in squad" :key="item.key" type="button"
                        @click="emit('open', item)"
                        :class="[CHIP, LEVEL_CHIP[item.level]]"
                        :title="item.player ? 'Voir la fiche du joueur' : 'Aller à l\'onglet Mon équipe'">
                    {{ item.text }}
                </button>
                <span v-if="!squad.length"
                      :class="[CHIP, 'bg-emerald-50 border-emerald-200 text-emerald-700']">
                    ✓ Onze complet, tous à leur poste, en forme
                </span>
            </div>
        </div>
    </div>
</template>
