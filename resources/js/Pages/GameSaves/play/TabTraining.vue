<script setup>
import { computed } from 'vue';

const props = defineProps({
    season:                      { type: Number,   required: true },
    week:                        { type: Number,   required: true },
    roster:                      { type: Array,    required: true },
    trainingState:               { type: Object,   default: null },
    remainingTrainingsThisWeek:  { type: Number,   required: true },
    hasPlayerBeenTrainedThisWeek:{ type: Function, required: true },
    availableTrainingStats:      { type: Array,    required: true },
    selectedTrainings:           { type: Array,    required: true },
    canSubmitTraining:           { type: Boolean,  required: true },
    aiTrainingEntries:           { type: Array,    default: () => [] },
});

const emit = defineEmits(['add-slot', 'remove-slot', 'submit-training']);

const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path)         return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
};

const statLabel = (key) => ({
    shot: 'Tir', pass: 'Passe', dribble: 'Dribble', attack: 'Attaque',
    defense: 'Défense', speed: 'Vitesse', block: 'Block',
    intercept: 'Interc.', tackle: 'Tacle', stamina: 'Stamina',
    hand_save: 'Main', punch_save: 'Poings',
}[key] ?? key);

const statColor = (key) => ({
    shot: 'bg-red-400', pass: 'bg-teal-400', dribble: 'bg-yellow-400',
    attack: 'bg-orange-400', defense: 'bg-blue-400', speed: 'bg-sky-400',
    block: 'bg-indigo-400', intercept: 'bg-purple-400', tackle: 'bg-pink-400',
    stamina: 'bg-emerald-400', hand_save: 'bg-violet-400', punch_save: 'bg-fuchsia-400',
}[key] ?? 'bg-slate-400');

// Joueur correspondant à un ai_entry
const playerForEntry = (entry) =>
    props.roster.find(p => Number(p.id) === Number(entry.player_id)) ?? null;

// Historique entraînements manuels cette semaine
const manualEntries = computed(() => {
    const s = props.trainingState;
    if (!s || Number(s.season) !== Number(props.season) || Number(s.week) !== Number(props.week)) return [];
    return Array.isArray(s.entries) ? s.entries : [];
});

const playerForManualEntry = (entry) =>
    props.roster.find(p => Number(p.id) === Number(entry.player_id)) ?? null;
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- HEADER -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Entraînement</h3>
                    <p class="text-sm text-slate-600 mt-1">
                        Saison {{ season }} — Semaine {{ week }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <!-- Entraînements manuels restants -->
                    <div class="text-center px-4 py-2 rounded-xl border"
                         :class="remainingTrainingsThisWeek > 0 ? 'bg-teal-50 border-teal-200' : 'bg-slate-100 border-slate-200'">
                        <div class="text-2xl font-black"
                             :class="remainingTrainingsThisWeek > 0 ? 'text-teal-600' : 'text-slate-400'">
                            {{ remainingTrainingsThisWeek }}
                        </div>
                        <div class="text-[10px] font-semibold"
                             :class="remainingTrainingsThisWeek > 0 ? 'text-teal-500' : 'text-slate-400'">
                            séance(s) restante(s)
                        </div>
                    </div>
                    <!-- Entraînements IA cette semaine -->
                    <div class="text-center px-4 py-2 rounded-xl border bg-amber-50 border-amber-200">
                        <div class="text-2xl font-black text-amber-500">{{ aiTrainingEntries.length }}</div>
                        <div class="text-[10px] font-semibold text-amber-400">entr. auto</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">

            <!-- ============================================ -->
            <!-- COLONNE GAUCHE : Effectif                    -->
            <!-- ============================================ -->
            <div class="col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-3 max-h-[600px] overflow-y-auto">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Effectif</h3>
                <div v-if="roster.length" class="space-y-1">
                    <div v-for="p in roster" :key="p.id"
                         class="flex items-center gap-2 px-2 py-1.5 rounded-lg border transition-all"
                         :class="hasPlayerBeenTrainedThisWeek(p.id)
                            ? 'bg-teal-50 border-teal-200'
                            : 'bg-white border-slate-100'">

                        <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                            <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt=""/>
                            <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold truncate text-slate-700">{{ p.lastname }}</div>
                            <div class="text-[10px] text-slate-400 truncate">{{ p.position }}</div>
                        </div>

                        <!-- Stamina bar -->
                        <div class="w-14 flex flex-col items-end gap-0.5">
                            <div class="text-[10px] font-bold text-slate-600">{{ p.stamina ?? '—' }}</div>
                            <div class="w-full h-1 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     :class="(p.stamina ?? 0) >= 60 ? 'bg-emerald-400' : (p.stamina ?? 0) >= 30 ? 'bg-amber-400' : 'bg-rose-400'"
                                     :style="{ width: Math.min(p.stamina ?? 0, 100) + '%' }">
                                </div>
                            </div>
                        </div>

                        <!-- Badge entraîné -->
                        <div v-if="hasPlayerBeenTrainedThisWeek(p.id)"
                             class="text-[9px] bg-teal-500 text-white px-1.5 py-0.5 rounded-full font-bold shrink-0">
                            ✓
                        </div>
                    </div>
                </div>
                <p v-else class="text-xs text-slate-400">Aucun joueur.</p>
            </div>

            <!-- ============================================ -->
            <!-- COLONNE DROITE                               -->
            <!-- ============================================ -->
            <div class="col-span-8 flex flex-col gap-4">

                <!-- Formulaire entraînement manuel -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                        🏋️ Entraînement manuel
                    </h4>
                    <p class="text-xs text-slate-400 mb-3">
                        Choisis jusqu'à 3 joueurs différents et la stat à améliorer (+1 à +5, coûte 5 stamina).
                    </p>

                    <div class="space-y-2 mb-3">
                        <div v-for="(slot, index) in selectedTrainings" :key="index"
                             class="flex items-center gap-2">
                            <select v-model.number="slot.player_id"
                                    class="flex-1 border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-teal-300 focus:outline-none">
                                <option :value="null">Choisir un joueur</option>
                                <option v-for="p in roster" :key="p.id" :value="p.id"
                                        :disabled="hasPlayerBeenTrainedThisWeek(p.id) || (p.stamina ?? 0) < 10">
                                    {{ p.lastname }} {{ p.firstname }}
                                    — Stamina {{ p.stamina ?? '—' }}
                                    {{ hasPlayerBeenTrainedThisWeek(p.id) ? '(déjà entraîné)' : '' }}
                                    {{ (p.stamina ?? 0) < 10 ? '(trop fatigué)' : '' }}
                                </option>
                            </select>
                            <select v-model="slot.stat"
                                    class="w-36 border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-teal-300 focus:outline-none">
                                <option v-for="s in availableTrainingStats" :key="s.key" :value="s.key">
                                    {{ s.label }}
                                </option>
                            </select>
                            <button type="button"
                                    class="w-7 h-7 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-200 flex items-center justify-center text-xs font-bold shrink-0"
                                    @click="emit('remove-slot', index)">✕</button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="button"
                                class="text-xs px-3 py-1.5 rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100 disabled:opacity-40 transition-all"
                                @click="emit('add-slot')"
                                :disabled="selectedTrainings.length >= 3 || remainingTrainingsThisWeek <= 0">
                            + Ajouter un joueur
                        </button>
                        <button type="button"
                                class="px-5 py-1.5 text-sm rounded-full font-semibold transition-all disabled:opacity-40"
                                :class="canSubmitTraining
                                ? 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm'
                                : 'bg-slate-200 text-slate-400'"
                                @click="emit('submit-training')"
                                :disabled="!canSubmitTraining">
                            Lancer l'entraînement
                        </button>
                    </div>

                    <p v-if="remainingTrainingsThisWeek <= 0"
                       class="mt-3 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Tu as utilisé tous tes entraînements manuels pour cette semaine.
                    </p>
                </div>

                <!-- Entraînements IA cette semaine -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center justify-between">
                        <span>🤖 Entraînement automatique</span>
                        <div class="flex items-center gap-2">
                            <button @click="emit('prev-ai-week')" :disabled="aiCurrentDisplayWeek <= 1"
                                    class="w-6 h-6 rounded-full bg-slate-200 hover:bg-slate-300 disabled:opacity-30 text-xs font-bold">←</button>
                            <span class="text-xs text-slate-500">Semaine {{ aiCurrentDisplayWeek }}</span>
                            <button @click="emit('next-ai-week')" :disabled="aiCurrentDisplayWeek >= aiWeekMax"
                                    class="w-6 h-6 rounded-full bg-slate-200 hover:bg-slate-300 disabled:opacity-30 text-xs font-bold">→</button>
                        </div>
                    </h4>

                    <div v-if="aiTrainingEntries.length" class="space-y-2">
                        <div v-for="entry in aiTrainingEntries" :key="entry.player_id + entry.stat"
                             class="flex items-center gap-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-100">

                            <!-- Photo -->
                            <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <img v-if="playerForEntry(entry) && playerPhotoUrl(playerForEntry(entry))"
                                     :src="playerPhotoUrl(playerForEntry(entry))" class="w-full h-full object-cover" alt=""/>
                                <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                            </div>

                            <!-- Nom -->
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-700 truncate">{{ entry.player_name }}</div>
                                <div class="text-[10px] text-slate-400">Stamina -{{ entry.stamina_cost }}</div>
                            </div>

                            <!-- Stat entraînée -->
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full" :class="statColor(entry.stat)"></div>
                                <span class="text-xs text-slate-600 font-medium">{{ statLabel(entry.stat) }}</span>
                            </div>

                            <!-- Gain -->
                            <div class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-200 text-amber-700">
                                +{{ entry.gain }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-xs text-slate-400 italic py-2">
                        L'entraînement automatique s'effectue après chaque match joué ou simulé.
                    </div>
                </div>

                <!-- Historique entraînements manuels -->
                <div v-if="manualEntries.length" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                        📋 Historique manuel — semaine {{ week }}
                    </h4>
                    <div class="space-y-2">
                        <div v-for="entry in manualEntries" :key="entry.player_id + entry.stat"
                             class="flex items-center gap-3 px-3 py-2 rounded-lg bg-teal-50 border border-teal-100">

                            <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <img v-if="playerForManualEntry(entry) && playerPhotoUrl(playerForManualEntry(entry))"
                                     :src="playerPhotoUrl(playerForManualEntry(entry))" class="w-full h-full object-cover" alt=""/>
                                <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-700 truncate">
                                    {{ playerForManualEntry(entry)?.lastname ?? `Joueur #${entry.player_id}` }}
                                </div>
                                <div class="text-[10px] text-slate-400">Stamina -{{ entry.stamina_cost }}</div>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full" :class="statColor(entry.stat)"></div>
                                <span class="text-xs text-slate-600 font-medium">{{ statLabel(entry.stat) }}</span>
                            </div>

                            <div class="px-2 py-0.5 rounded-full text-xs font-black bg-teal-200 text-teal-700">
                                +{{ entry.gain }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
