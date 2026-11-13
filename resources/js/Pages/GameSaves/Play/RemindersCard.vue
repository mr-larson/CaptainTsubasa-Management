<script setup>

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

const ROW = 'checklist-row w-full border-0 border-b border-slate-200 rounded-none bg-transparent py-2 text-[11px] leading-relaxed font-semibold text-left transition-colors hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600';
const LEVEL_ROW = {
    action: 'checklist-row--action text-teal-800',
    error: 'checklist-row--error text-rose-800',
    warn: 'checklist-row--warn text-amber-800',
    info: 'text-slate-500',
};
</script>

<template>
    <div class="border border-slate-200 rounded-xl flex flex-col gap-3"
         :class="inDashboard ? 'bg-slate-50 p-4' : 'bg-white p-3'">
        <div v-if="actions.length">
            <div class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Avant le match</div>
            <div class="flex flex-col">
                <button v-for="item in actions" :key="item.key" type="button"
                        @click="emit('open', item)"
                        :class="[ROW, LEVEL_ROW[item.level]]">
                    <span class="checklist-label">{{ item.text }}</span>
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
            <div class="flex flex-col">
                <button v-for="item in squad" :key="item.key" type="button"
                        @click="emit('open', item)"
                        :class="[ROW, LEVEL_ROW[item.level]]"
                        :title="item.player ? 'Voir la fiche du joueur' : 'Aller à l\'onglet Mon équipe'">
                    <span class="checklist-label">{{ item.text }}</span>
                </button>
                <span v-if="!squad.length"
                      :class="[ROW, 'text-emerald-700']">
                    ✓ Onze complet, tous à leur poste, en forme
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.checklist-row {
    cursor: pointer;
}

.checklist-row--action {
    --marker-color: #99f6e4;
}

.checklist-row--warn {
    --marker-color: #fef08a;
}

.checklist-row--error {
    --marker-color: #fecdd3;
}

.checklist-label {
    background-image: linear-gradient(transparent 45%, var(--marker-color, transparent) 45%, var(--marker-color, transparent) 90%, transparent 90%);
    box-decoration-break: clone;
    -webkit-box-decoration-break: clone;
}

span.checklist-row {
    cursor: default;
}
</style>
