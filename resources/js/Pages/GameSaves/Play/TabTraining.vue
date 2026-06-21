<script setup>
import { ref, computed } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import RosterList from '@/Pages/GameSaves/Play/components/RosterList.vue';
import PlayerStatusAlert from '@/Pages/GameSaves/Play/components/PlayerStatusAlert.vue';
import PlayerIdentityCard from '@/Pages/GameSaves/Play/components/PlayerIdentityCard.vue';
import RadarChart from '@/Pages/GameSaves/Play/components/RadarChart.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import PerfChips from '@/Pages/GameSaves/Play/components/PerfChips.vue';

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
    aiCurrentDisplayWeek:        { type: Number,   default: 1 },
    aiWeekMax:                   { type: Number,   default: 1 },
    // Nouvelles props pour le profil joueur
    playerSeasonStats:           { type: Object,   default: () => ({}) },
    isPlayerInjured:             { type: Function, default: () => () => false },
    isPlayerSuspended:           { type: Function, default: () => () => false },
    playerYellowCards:           { type: Function, default: () => () => 0 },
    playerInjury:                { type: Function, default: () => () => null },
    playerSuspension:            { type: Function, default: () => () => null },
});

const emit = defineEmits(['add-slot', 'remove-slot', 'submit-training', 'prev-ai-week', 'next-ai-week']);

const { playerPhotoUrl, statColor } = usePlayerUtils();

const trainingButtonState = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return { disabled: true, label: '', reason: '' };

    if (props.remainingTrainingsThisWeek <= 0) {
        return { disabled: true, label: 'Plus de séances cette semaine', reason: 'limit_week' };
    }
    if (props.selectedTrainings.length >= 3) {
        return { disabled: true, label: 'Limite de 3 joueurs atteinte', reason: 'limit_slots' };
    }
    if (props.selectedTrainings.some(s => Number(s.player_id) === Number(p.id))) {
        return { disabled: true, label: '✓ Déjà dans la séance', reason: 'already_added' };
    }
    if (props.hasPlayerBeenTrainedThisWeek(p.id)) {
        return { disabled: true, label: 'Déjà entraîné cette semaine', reason: 'trained' };
    }
    if ((p.stamina ?? 0) < 10) {
        return { disabled: true, label: 'Trop fatigué (< 10 stamina)', reason: 'tired' };
    }
    return { disabled: false, label: '🏋 Ajouter à l\'entraînement', reason: null };
});

// ── Sélection joueur (locale) ──────────────────────────────
const selectedPlayerId = ref(null);
const selectedPlayer   = computed(() => {
    if (!props.roster.length || !selectedPlayerId.value) return null;
    return props.roster.find(p => p.id === selectedPlayerId.value) ?? null;
});
const selectPlayer = (p) => { selectedPlayerId.value = p.id; };

// ── Filtre IA ──────────────────────────────────────────────
const aiTeamFilter = ref(null);

const aiTeams = computed(() => {
    const teams = new Map();
    props.aiTrainingEntries.forEach(e => {
        if (e.team_id && e.team_name) teams.set(e.team_id, e.team_name);
    });
    return [...teams.entries()].map(([id, name]) => ({ id, name }));
});

const filteredAiEntries = computed(() =>
    aiTeamFilter.value
        ? props.aiTrainingEntries.filter(e => e.team_id === aiTeamFilter.value)
        : props.aiTrainingEntries
);

// ── Stat label local (libellés longs vs courts du composable) ──
const statLabel = (key) => ({
    shot: 'Tir', pass: 'Passe', dribble: 'Dribble', attack: 'Attaque',
    defense: 'Défense', speed: 'Vitesse', block: 'Block',
    intercept: 'Interc.', tackle: 'Tacle', stamina: 'Stamina',
    hand_save: 'Main', punch_save: 'Poings',
}[key] ?? key);

// ── Joueur correspondant à une entrée IA / manuel ──────────
const playerForEntry       = (entry) => props.roster.find(p => Number(p.id) === Number(entry.player_id)) ?? null;
const playerForManualEntry = playerForEntry;

const manualEntries = computed(() => {
    const s = props.trainingState;
    if (!s || Number(s.season) !== Number(props.season) || Number(s.week) !== Number(props.week)) return [];
    return Array.isArray(s.entries) ? s.entries : [];
});

// ── Stats autorisées par poste (5 groupes — sémantique entraînement) ──
const STATS_BY_POSITION = {
    GK:      ['hand_save', 'punch_save', 'defense', 'block', 'stamina', 'speed'],
    DEF:     ['defense', 'tackle', 'block', 'intercept', 'stamina', 'speed'],
    MDF:     ['pass', 'intercept', 'tackle', 'defense', 'attack', 'stamina'],
    MOF:     ['pass', 'dribble', 'attack', 'intercept', 'shot', 'stamina'],
    ATT:     ['shot', 'dribble', 'attack', 'pass', 'speed', 'stamina'],
    DEFAULT: ['speed', 'attack', 'defense', 'pass', 'dribble', 'shot', 'tackle', 'intercept'],
};

const positionGroup = (position) => {
    const p = (position ?? '').toUpperCase();
    if (p.includes('GK') || p.includes('GOAL'))    return 'GK';
    if (p.includes('DEF') || p.includes('BACK'))   return 'DEF';
    if (p.includes('MDF') || p.includes('DEFENSIVE MID')) return 'MDF';
    if (p.includes('MOF') || p.includes('MID'))    return 'MOF';
    if (p.includes('ATT') || p.includes('FOR') || p.includes('FORWARD')) return 'ATT';
    return 'DEFAULT';
};

const statsForSlot = (slot) => {
    if (!slot?.player_id) return props.availableTrainingStats;
    const player = props.roster.find(p => Number(p.id) === Number(slot.player_id));
    if (!player) return props.availableTrainingStats;

    // Toutes les stats sont entraînables, mais celles pertinentes au poste
    // remontent en haut de la liste
    const allowed = STATS_BY_POSITION[positionGroup(player.position)] ?? STATS_BY_POSITION.DEFAULT;
    const allowedSet = new Set(allowed);

    return [
        ...props.availableTrainingStats.filter(s => allowedSet.has(s.key)),
        ...props.availableTrainingStats.filter(s => !allowedSet.has(s.key)),
    ];
};

// ── Perf saison du joueur sélectionné (consommé par PerfChips) ──
const selectedPlayerPerf = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return null;
    return props.playerSeasonStats?.[p.id] ?? props.playerSeasonStats?.[String(p.id)] ?? null;
});
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1 [&>*]:shrink-0">

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

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

            <!-- ============================================ -->
            <!-- COLONNE GAUCHE : Effectif         -->
            <!-- ============================================ -->
            <!-- Hauteur calée sur les colonnes de droite (scroll interne) -->
            <div class="lg:col-span-3 relative min-h-[320px]">
                <RosterList class="absolute inset-0"
                            :players="roster"
                            :selectedId="selectedPlayer?.id"
                            :isPlayerInjured="isPlayerInjured"
                            :isPlayerSuspended="isPlayerSuspended"
                            :playerYellowCards="playerYellowCards"
                            :playerInjury="playerInjury"
                            :playerSuspension="playerSuspension"
                            :rowHighlight="p => hasPlayerBeenTrainedThisWeek(p.id)"
                            @select="selectPlayer">
                    <template #badge="{ player, selected }">
                        <div v-if="hasPlayerBeenTrainedThisWeek(player.id)"
                             class="text-[9px] px-1.5 py-0.5 rounded-full font-bold shrink-0"
                             :class="selected ? 'bg-white text-teal-600' : 'bg-teal-500 text-white'"
                             title="Déjà entraîné cette semaine">
                            ✓
                        </div>
                    </template>
                </RosterList>
            </div>

            <!-- ============================================ -->
            <!-- COLONNE  CENTRAL & DROITE                               -->
            <!-- ============================================ -->
            <div class="lg:col-span-6 flex flex-col gap-4">
                <!-- ====================================== -->
                <!-- PROFIL JOUEUR SÉLECTIONNÉ              -->
                <!-- ====================================== -->
                <template v-if="selectedPlayer">

                    <PlayerStatusAlert :player="selectedPlayer"
                                       :isPlayerInjured="isPlayerInjured"
                                       :isPlayerSuspended="isPlayerSuspended"
                                       :playerInjury="playerInjury"
                                       :playerSuspension="playerSuspension" />

                    <!-- Identité -->
                    <PlayerIdentityCard :player="selectedPlayer"
                                        :subtitle="`${selectedPlayer.position} • Stamina ${selectedPlayer.stamina ?? '—'}`">
                        <template #actions>
                            <button type="button"
                                    @click="emit('add-slot', selectedPlayer.id)"
                                    :disabled="trainingButtonState.disabled"
                                    class="px-4 py-2 rounded-full text-xs font-bold transition-all"
                                    :class="trainingButtonState.disabled
                                        ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
                                        : 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm'">
                                {{ trainingButtonState.label }}
                            </button>
                        </template>
                        <template #footer>
                            <p v-if="selectedPlayer.description" class="mt-2 text-xs text-slate-400 italic line-clamp-2">
                                {{ selectedPlayer.description }}
                            </p>
                        </template>
                    </PlayerIdentityCard>

                    <!-- Radar + Barres stats -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <RadarChart :player="selectedPlayer" accent="teal" />
                        <StatBars :player="selectedPlayer" />
                    </div>

                    <!-- Perf chips -->
                    <PerfChips :player="selectedPlayer" :perf="selectedPlayerPerf" />
                </template>
                <!-- État vide -->
                <div v-else
                     class="flex-1 flex items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/40 min-h-[400px] p-8">
                    <div class="text-center max-w-xs">
                        <div class="text-5xl mb-3 opacity-30">👤</div>
                        <h4 class="text-sm font-bold text-slate-500 mb-2">Aucun joueur sélectionné</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Clique sur un joueur dans la liste à gauche pour consulter son profil
                            et décider s'il a besoin d'un entraînement.
                        </p>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-3 flex flex-col gap-4">
                <!-- Formulaire entraînement manuel -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                        🏋️ Entraînement manuel
                    </h4>
                    <p class="text-xs text-slate-400 mb-3">
                        Choisis jusqu'à 3 joueurs différents et la stat à améliorer (+1 à +5, coûte 5 stamina).
                    </p>

                    <div class="space-y-2 mb-3">
                        <div v-if="selectedTrainings.length" class="space-y-2 mb-3">
                            <div v-for="(slot, index) in selectedTrainings" :key="index"
                                 class="flex flex-col gap-2 p-2.5 rounded-lg bg-white border border-slate-200">

                                <!-- Carte joueur compacte -->
                                <div v-if="playerForEntry({ player_id: slot.player_id })" class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                        <img v-if="playerPhotoUrl(playerForEntry({ player_id: slot.player_id }))"
                                             :src="playerPhotoUrl(playerForEntry({ player_id: slot.player_id }))"
                                             class="w-full h-full object-cover" alt=""/>
                                        <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-semibold truncate text-slate-700">
                                            {{ playerForEntry({ player_id: slot.player_id })?.lastname ?? '—' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 truncate">
                                            {{ playerForEntry({ player_id: slot.player_id })?.position ?? '' }}
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="emit('remove-slot', index)"
                                            class="w-6 h-6 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-200 flex items-center justify-center text-xs font-bold shrink-0">
                                        ✕
                                    </button>
                                </div>

                                <!-- Select stat -->
                                <select v-model="slot.stat"
                                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-xs bg-white focus:ring-2 focus:ring-teal-300 focus:outline-none">
                                    <option v-for="s in statsForSlot(slot)" :key="s.key" :value="s.key">
                                        {{ s.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- État vide -->
                        <div v-else class="mb-3 p-4 rounded-lg bg-slate-100 border border-dashed border-slate-300 text-center">
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                Sélectionne un joueur dans l'effectif puis appuie sur
                                <span class="font-bold text-teal-600">🏋 Ajouter à l'entraînement</span>
                                depuis sa carte.
                            </p>
                        </div>

                        <!-- Bouton Lancer -->
                        <button type="button"
                                class="w-full px-4 py-2 text-sm rounded-full font-semibold transition-all disabled:opacity-40"
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
            </div>
        </div>
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 flex flex-col gap-4">
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

                    <!-- Filtre équipe -->
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <button type="button"
                                @click="aiTeamFilter = null"
                                class="px-2.5 py-1 rounded-full text-[10px] font-semibold border transition-all"
                                :class="aiTeamFilter === null ? 'bg-amber-400 text-white border-amber-500' : 'bg-white text-slate-500 border-slate-200 hover:border-amber-300'">
                            Toutes
                        </button>
                        <button v-for="t in aiTeams" :key="t.id" type="button"
                                @click="aiTeamFilter = aiTeamFilter === t.id ? null : t.id"
                                class="px-2.5 py-1 rounded-full text-[10px] font-semibold border transition-all"
                                :class="aiTeamFilter === t.id ? 'bg-amber-400 text-white border-amber-500' : 'bg-white text-slate-500 border-slate-200 hover:border-amber-300'">
                            {{ t.name }}
                        </button>
                    </div>

                    <div v-if="filteredAiEntries.length" class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                        <div v-for="entry in filteredAiEntries" :key="entry.player_id + entry.stat + entry.team_id"
                             class="flex items-center gap-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-100">

                            <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <div class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">
                                    {{ entry.player_name?.charAt(0) ?? '?' }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-700 truncate">{{ entry.player_name }}</div>
                                <div class="text-[10px] text-amber-600 font-semibold truncate">{{ entry.team_name }}</div>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full" :class="statColor(entry.stat)"></div>
                                <span class="text-xs text-slate-600 font-medium">{{ statLabel(entry.stat) }}</span>
                            </div>

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
