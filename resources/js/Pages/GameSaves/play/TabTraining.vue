<script setup>
const props = defineProps({
    season:                      { type: Number,  required: true },
    week:                        { type: Number,  required: true },
    roster:                      { type: Array,   required: true },
    trainingState:               { type: Object,  default: null },
    remainingTrainingsThisWeek:  { type: Number,  required: true },
    hasPlayerBeenTrainedThisWeek:{ type: Function, required: true },
    availableTrainingStats:      { type: Array,   required: true },
    selectedTrainings:           { type: Array,   required: true },
    canSubmitTraining:           { type: Boolean, required: true },
});

const emit = defineEmits(['add-slot', 'remove-slot', 'submit-training']);
</script>

<template>
    <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4">
        <h3 class="text-lg font-semibold text-slate-700 mb-2">Entraînement</h3>
        <p class="text-sm text-slate-600 mb-2">
            Semaine {{ week }} — Saison {{ season }}.
            Il te reste <span class="font-semibold">{{ remainingTrainingsThisWeek }}</span> entraînement(s) possible(s) cette semaine (max 3, joueurs différents).
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Effectif -->
            <div class="border border-slate-200 rounded-lg bg-white p-3 max-h-96 overflow-y-auto">
                <h4 class="text-sm font-semibold text-slate-700 mb-2">Effectif</h4>
                <div v-if="roster.length" class="space-y-1 text-sm">
                    <div v-for="p in roster" :key="p.id"
                         class="flex items-center justify-between px-2 py-1 rounded border border-slate-100 bg-slate-50"
                         :class="{ 'opacity-60': hasPlayerBeenTrainedThisWeek(p.id) }">
                        <div class="flex flex-col">
                            <span class="font-medium text-slate-800">{{ p.firstname }} {{ p.lastname }}</span>
                            <span class="text-xs text-slate-500">
                                Poste : {{ p.position }} • Stamina : {{ p.stamina ?? p.stats?.stamina ?? '-' }}
                                <span v-if="hasPlayerBeenTrainedThisWeek(p.id)" class="ml-1 text-[11px] text-amber-600">(déjà entraîné cette semaine)</span>
                            </span>
                        </div>
                        <div class="text-[11px] text-right text-slate-600 leading-tight space-y-0.5">
                            <div>Vit : {{ p.speed ?? p.stats?.speed ?? '-' }} • End : {{ p.stamina ?? p.stats?.stamina ?? '-' }} • Att : {{ p.attack ?? p.stats?.attack ?? '-' }} • Def : {{ p.defense ?? p.stats?.defense ?? '-' }}</div>
                            <div>Tir : {{ p.shot ?? p.stats?.shot ?? '-' }} • Pas : {{ p.pass ?? p.stats?.pass ?? '-' }} • Dri : {{ p.dribble ?? p.stats?.dribble ?? '-' }} • Tac : {{ p.tackle ?? p.stats?.tackle ?? '-' }} • Int : {{ p.intercept ?? p.stats?.intercept ?? '-' }} • Blk : {{ p.block ?? p.stats?.block ?? '-' }}</div>
                            <div v-if="(p.hand_save ?? p.stats?.hand_save ?? 0) > 0 || (p.punch_save ?? p.stats?.punch_save ?? 0) > 0">
                                Main : {{ p.hand_save ?? p.stats?.hand_save ?? '-' }} • Poings : {{ p.punch_save ?? p.stats?.punch_save ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">Aucun joueur sous contrat.</p>
            </div>

            <!-- Formulaire -->
            <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-3">
                <h4 class="text-sm font-semibold text-slate-700 mb-1">Planifier des entraînements</h4>
                <p class="text-xs text-slate-500 mb-2">Sélectionne 1 à 3 joueurs différents et la statistique à améliorer. Chaque entraînement améliore la stat (+1 à +5) et coûte 5 points de stamina.</p>
                <div class="space-y-2">
                    <div v-for="(slot, index) in selectedTrainings" :key="index" class="flex items-center gap-2">
                        <select v-model.number="slot.player_id" class="flex-1 border border-slate-300 rounded-md px-2 py-1 text-sm">
                            <option :value="null">Choisir un joueur</option>
                            <option v-for="p in roster" :key="p.id" :value="p.id" :disabled="hasPlayerBeenTrainedThisWeek(p.id)">
                                {{ p.firstname }} {{ p.lastname }} — Stamina {{ p.stamina ?? p.stats?.stamina ?? '-' }}
                            </option>
                        </select>
                        <select v-model="slot.stat" class="w-32 border border-slate-300 rounded-md px-2 py-1 text-sm">
                            <option v-for="s in availableTrainingStats" :key="s.key" :value="s.key">{{ s.label }}</option>
                        </select>
                        <button type="button" class="text-xs text-red-600 hover:text-red-700" @click="emit('remove-slot', index)">✕</button>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <button type="button"
                            class="text-xs px-3 py-1 rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100 disabled:opacity-50"
                            @click="emit('add-slot')" :disabled="selectedTrainings.length >= 3 || remainingTrainingsThisWeek <= 0">
                        + Ajouter un joueur
                    </button>
                    <button type="button"
                            class="px-4 py-1.5 text-sm rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold disabled:opacity-50"
                            @click="emit('submit-training')" :disabled="!canSubmitTraining">
                        Lancer l'entraînement
                    </button>
                </div>
                <p v-if="remainingTrainingsThisWeek <= 0" class="mt-2 text-xs text-amber-600">Tu as utilisé tous tes entraînements pour cette semaine.</p>
            </div>
        </div>

        <!-- Historique -->
        <div v-if="trainingState && trainingState.season === season && trainingState.week === week && trainingState.entries?.length"
             class="mt-4 border border-slate-200 rounded-lg bg-white p-3">
            <h4 class="text-sm font-semibold text-slate-700 mb-2">Historique de la semaine</h4>
            <ul class="text-xs text-slate-600 space-y-1 max-h-40 overflow-y-auto">
                <li v-for="(entry, idx) in trainingState.entries" :key="idx">
                    Joueur ID {{ entry.player_id }} — {{ entry.stat }} : +{{ entry.gain }} (stamina -{{ entry.stamina_cost }})
                </li>
            </ul>
        </div>
    </div>
</template>
