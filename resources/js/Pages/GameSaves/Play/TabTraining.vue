<script setup>
import { positionLabel } from '@/Enums/positionBadges.js';
import { fieldClass } from '@/Components/fieldStyles';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { ref, computed, watch } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';
import RosterList from '@/Pages/GameSaves/Play/components/RosterList.vue';
import PlayerStatusAlert from '@/Pages/GameSaves/Play/components/PlayerStatusAlert.vue';
import PlayerIdentityCard from '@/Pages/GameSaves/Play/components/PlayerIdentityCard.vue';
import RadarChart from '@/Pages/GameSaves/Play/components/RadarChart.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import PerfChips from '@/Pages/GameSaves/Play/components/PerfChips.vue';
import { LOW_STAMINA } from './usePreMatchChecklist.js';

const props = defineProps({
    season:                      { type: Number,   required: true },
    week:                        { type: Number,   required: true },
    roster:                      { type: Array,    required: true },
    teamBudget:                  { type: Number,   default: 0 },
    maxPerWeek:                  { type: Number,   default: 3 },
    minStamina:                  { type: Number,   default: 10 },
    trainingCost:                { type: Number,   default: 0 },
    trainableSpecialMoves:        { type: Array,    default: () => [] },
    specialTrainingCost:          { type: Number,   default: 300 },
    specialTrainingError:         { type: String,   default: '' },
    positionTrainingCost:        { type: Number,   default: 200 },
    staminaCost:                 { type: Number,   default: 0 },
    playerPositionTraining:      { type: Function, default: () => null },
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

const emit = defineEmits(['add-slot', 'remove-slot', 'submit-training', 'prev-ai-week', 'next-ai-week', 'train-position', 'train-special']);

const { playerPhotoUrl, statColor } = usePlayerUtils();

// Endurance du joueur au prochain match une fois la séance payée.
const staminaAfter = (slot) => Math.max(0, Number(playerForEntry({ player_id: slot.player_id })?.stamina ?? 0) - props.staminaCost);

// Coût total des séances sélectionnées et budget restant après paiement.
const selectedCount = computed(() => props.selectedTrainings.filter(s => s.player_id).length);
const totalTrainingCost = computed(() => selectedCount.value * props.trainingCost);
const budgetAfter = computed(() => props.teamBudget - totalTrainingCost.value);
const canAffordSelected = computed(() => totalTrainingCost.value <= props.teamBudget);
// Peut-on encore payer une séance supplémentaire ?
const canAffordOneMore = computed(() => (selectedCount.value + 1) * props.trainingCost <= props.teamBudget);

const trainingButtonState = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return { disabled: true, label: '', reason: '' };

    if (props.playerPositionTraining(p.id)) return { disabled: true, label: 'En formation' };

    if (props.remainingTrainingsThisWeek <= 0) {
        return { disabled: true, label: 'Plus de séances cette semaine', reason: 'limit_week' };
    }
    if (props.selectedTrainings.length >= props.remainingTrainingsThisWeek) {
        return { disabled: true, label: `Limite de ${props.remainingTrainingsThisWeek} séance(s) atteinte`, reason: 'limit_slots' };
    }
    if (props.selectedTrainings.some(s => Number(s.player_id) === Number(p.id))) {
        return { disabled: true, label: '✓ Déjà dans la séance', reason: 'already_added' };
    }
    if (props.hasPlayerBeenTrainedThisWeek(p.id)) {
        return { disabled: true, label: 'Déjà entraîné cette semaine', reason: 'trained' };
    }
    if ((p.stamina ?? 0) < props.minStamina) {
        return { disabled: true, label: `Trop fatigué (< ${props.minStamina} stamina)`, reason: 'tired' };
    }
    if (props.trainingCost > 0 && !canAffordOneMore.value) {
        return { disabled: true, label: 'Budget insuffisant', reason: 'budget' };
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

// ── Formation à un nouveau poste (miroir de PositionTrainingService::durationFor) ──
const ALL_POSITIONS = ['Goalkeeper', 'Defender', 'Midfielder', 'Forward'];
const LINE = { Defender: 1, Midfielder: 2, Forward: 3 };
const knownPositions = (p) => [p.position, ...(p.secondary_positions ?? [])].filter(Boolean);
const positionTrainingWeeks = (p, target) => {
    if (target === 'Goalkeeper') return 3;
    const lines = knownPositions(p).map(pos => LINE[pos]).filter(Boolean);
    if (!lines.length) return target === 'Defender' ? 2 : 3;
    return Math.min(...lines.map(l => Math.abs(l - LINE[target])));
};
const positionTarget = ref(null);
const positionOptions = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return [];
    const known = knownPositions(p);
    return ALL_POSITIONS.filter(pos => !known.includes(pos))
        .map(pos => ({ value: pos, label: positionLabel(pos), weeks: positionTrainingWeeks(p, pos) }))
        .sort((a, b) => a.weeks - b.weeks);   // la formation la plus courte d'abord
});
watch(selectedPlayer, () => { positionTarget.value = positionOptions.value[0]?.value ?? null; }, { immediate: true });
const currentPositionTraining = computed(() => selectedPlayer.value ? props.playerPositionTraining(selectedPlayer.value.id) : null);
const formationState = (target, cost) => {
    const p = selectedPlayer.value;
    if (!p || !target) return { disabled: true, reason: '' };
    if (currentPositionTraining.value) return { disabled: true, reason: 'Déjà en formation.' };
    if (props.selectedTrainings.some(s => Number(s.player_id) === Number(p.id))) return { disabled: true, reason: 'Retire ce joueur de la séance prévue pour lancer une formation.' };
    if (props.remainingTrainingsThisWeek - selectedCount.value <= 0) return { disabled: true, reason: 'Plus de séance disponible cette semaine.' };
    if (props.hasPlayerBeenTrainedThisWeek(p.id)) return { disabled: true, reason: 'Déjà entraîné cette semaine.' };
    if (props.isPlayerInjured(p.id) || props.isPlayerSuspended(p.id)) return { disabled: true, reason: 'Indisponible : il pourra se former à son retour.' };
    if (cost > props.teamBudget - totalTrainingCost.value) return { disabled: true, reason: 'Budget insuffisant.' };
    return { disabled: false, reason: '' };
};
const positionTrainingState = computed(() => formationState(positionTarget.value, props.positionTrainingCost));

const moveAction = (move) => ({ hands: 'hand_save', punch: 'punch_save' })[move.base_action] ?? move.base_action;
const moveActionLabels = { shot: 'Tir', pass: 'Passe', cross: 'Centre', dribble: 'Dribble', block: 'Contre', intercept: 'Interception', tackle: 'Tacle', hand_save: 'Arrêt main', punch_save: 'Arrêt poings' };
const specialTarget = ref(null);
const specialOptions = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return [];
    const known = new Set((p.special_moves ?? []).map(moveAction));
    return props.trainableSpecialMoves.filter(move => {
        const action = moveAction(move);
        const keeperMove = ['hand_save', 'punch_save'].includes(action);
        return !known.has(action) && keeperMove === (p.position === 'Goalkeeper');
    });
});
watch(specialOptions, (options) => { specialTarget.value = options[0]?.key ?? null; }, { immediate: true });
const specialTrainingState = computed(() => formationState(specialTarget.value, props.specialTrainingCost));
const selectedSpecial = computed(() => specialOptions.value.find(move => move.key === specialTarget.value));
const selectedPositionWeeks = computed(() => positionOptions.value.find(o => o.value === positionTarget.value)?.weeks ?? 0);

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
    defense: 'Défense', speed: 'Vitesse', block: 'Contre',
    intercept: 'Interc.', tackle: 'Tacle', stamina: 'Stamina',
    hand_save: 'Main', punch_save: 'Poings',
}[key] ?? key);

// ── Joueur correspondant à une entrée IA / manuel ──────────
const playerForEntry       = (entry) => props.roster.find(p => Number(p.id) === Number(entry.player_id)) ?? null;
const playerForManualEntry = playerForEntry;

const manualEntries = computed(() => {
    const s = props.trainingState;
    if (!s || Number(s.season) !== Number(props.season) || Number(s.week) !== Number(props.week)) return [];
    // Seules les séances de mon équipe (en hot-seat, les autres managers ont les leurs).
    return Array.isArray(s.entries) ? s.entries.filter(e => playerForEntry(e)) : [];
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
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- HEADER -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Entraînement</h3>
                    <p class="text-sm text-slate-600 mt-1">
                        Saison {{ season }} — Semaine {{ week }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <!-- Budget & coût par séance -->
                    <div v-if="trainingCost > 0" class="text-center px-4 py-2 rounded-xl border bg-white border-slate-200">
                        <div class="text-2xl font-black text-slate-700">{{ teamBudget }} <GameBanknote /></div>
                        <div class="text-2xs font-semibold text-slate-400">budget · {{ trainingCost }} <GameBanknote />/séance</div>
                    </div>
                    <!-- Entraînements manuels restants -->
                    <div class="text-center px-4 py-2 rounded-xl border"
                         :class="remainingTrainingsThisWeek > 0 ? 'bg-teal-50 border-teal-200' : 'bg-slate-100 border-slate-200'">
                        <div class="text-2xl font-black"
                             :class="remainingTrainingsThisWeek > 0 ? 'text-teal-600' : 'text-slate-400'">
                            {{ remainingTrainingsThisWeek }}
                        </div>
                        <div class="text-2xs font-semibold"
                             :class="remainingTrainingsThisWeek > 0 ? 'text-teal-500' : 'text-slate-400'">
                            séance(s) restante(s)
                        </div>
                    </div>
                    <!-- Entraînements IA cette semaine -->
                    <div class="text-center px-4 py-2 rounded-xl border bg-amber-50 border-amber-200">
                        <div class="text-2xl font-black text-amber-500">{{ aiTrainingEntries.length }}</div>
                        <div class="text-2xs font-semibold text-amber-400">entr. auto</div>
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
                             class="text-3xs px-1.5 py-0.5 rounded-full font-bold shrink-0"
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
                                        :subtitle="`${positionLabel(selectedPlayer.position)} • Stamina ${selectedPlayer.stamina ?? '—'}`">
                        <template #actions>
                            <ButtonPrimary type="button" size="sm" @click="emit('add-slot', selectedPlayer.id)" :disabled="trainingButtonState.disabled">
                                {{ trainingButtonState.label }}
                            </ButtonPrimary>
                        </template>
                        <template #footer>
                            <p v-if="selectedPlayer.description" class="mt-2 text-xs text-slate-400 italic line-clamp-2">
                                {{ selectedPlayer.description }}
                            </p>
                        </template>
                    </PlayerIdentityCard>

                    <!-- Formation à un nouveau poste -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">🎓 Former à un nouveau poste</h4>
                        <p class="text-xs text-slate-500 mb-3">
                            Postes maîtrisés : <span class="font-semibold text-slate-700">{{ knownPositions(selectedPlayer).map(positionLabel).join(', ') }}</span>.
                            Le poste appris devient secondaire (bonus réduit, plus de malus « hors poste »). Le joueur est absent pendant la formation.
                        </p>

                        <div v-if="currentPositionTraining"
                             class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs text-violet-700">
                            🎓 <span v-if="currentPositionTraining.special_label">Technique spéciale · <strong>{{ currentPositionTraining.special_label }}</strong></span>
                            <span v-else>En formation au poste de <strong>{{ positionLabel(currentPositionTraining.target_position) }}</strong></span>
                            — de retour en semaine {{ currentPositionTraining.week_return }}.
                        </div>
                        <p v-else-if="!positionOptions.length" class="text-xs text-slate-400 italic">Il maîtrise déjà tous les postes.</p>
                        <template v-else>
                            <div class="flex flex-wrap items-center gap-2">
                                <select v-model="positionTarget" class="flex-1 min-w-[160px] px-2 py-1.5" :class="fieldClass({ shape: 'rect', size: 'sm' })">
                                    <option v-for="o in positionOptions" :key="o.value" :value="o.value">
                                        {{ o.label }} — absent {{ o.weeks }} match{{ o.weeks > 1 ? 's' : '' }}
                                    </option>
                                </select>
                                <ButtonPrimary type="button" size="sm" :disabled="positionTrainingState.disabled"
                                               @click="emit('train-position', { playerId: selectedPlayer.id, position: positionTarget })">
                                    Lancer la formation<template v-if="positionTrainingCost > 0"> — {{ positionTrainingCost }} <GameBanknote /></template>
                                </ButtonPrimary>
                            </div>
                            <p class="mt-2 text-2xs" :class="positionTrainingState.reason ? 'text-rose-500' : 'text-slate-400'">
                                {{ positionTrainingState.reason
                                    || `Compte comme une séance de la semaine. ${selectedPlayer.lastname} manquera ${selectedPositionWeeks} match${selectedPositionWeeks > 1 ? 's' : ''}.` }}
                            </p>
                        </template>
                    </div>

                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">✨ Technique spéciale</h4>
                        <p class="text-xs text-slate-500 mb-3">
                            Une technique spéciale par action. Le joueur apprend la technique en une semaine et reste indisponible pendant la formation.
                        </p>
                        <p v-if="selectedPlayer.special_moves?.length" class="mb-3 text-xs text-slate-500">
                            Techniques maîtrisées : <span class="font-semibold text-slate-700">{{ selectedPlayer.special_moves.map(move => move.label).join(', ') }}</span>
                        </p>
                        <div v-if="currentPositionTraining" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs text-violet-700">
                            {{ currentPositionTraining.special_label ?? 'Déjà en formation à un poste' }} · retour en semaine {{ currentPositionTraining.week_return }}.
                        </div>
                        <p v-else-if="!specialOptions.length" class="text-xs text-slate-400 italic">Aucune technique compatible disponible.</p>
                        <template v-else>
                            <div class="flex flex-wrap items-center gap-2">
                                <select v-model="specialTarget" aria-label="Technique spéciale à apprendre"
                                        class="flex-1 min-w-[160px] px-2 py-1.5" :class="fieldClass({ shape: 'rect', size: 'sm' })">
                                    <option v-for="move in specialOptions" :key="move.key" :value="move.key">
                                        {{ moveActionLabels[moveAction(move)] }} · {{ move.label }}
                                    </option>
                                </select>
                                <ButtonPrimary type="button" size="sm" :disabled="specialTrainingState.disabled"
                                               @click="emit('train-special', { playerId: selectedPlayer.id, moveKey: specialTarget })">
                                    Lancer la formation — {{ specialTrainingCost }} <GameBanknote />
                                </ButtonPrimary>
                            </div>
                            <p v-if="selectedSpecial?.description" class="mt-2 text-xs text-slate-500">{{ selectedSpecial.description }}</p>
                            <p class="mt-2 text-2xs" :class="specialTrainingState.reason ? 'text-rose-500' : 'text-slate-400'">
                                {{ specialTrainingState.reason || 'Compte comme une séance de la semaine.' }}
                            </p>
                        </template>
                        <p v-if="specialTrainingError" role="alert" class="mt-2 text-xs text-rose-600">{{ specialTrainingError }}</p>
                    </div>

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
                        Choisis jusqu'à {{ maxPerWeek }} joueurs différents et la stat à améliorer (+1 à +5, coûte de la stamina<span v-if="trainingCost > 0"> et {{ trainingCost }} <GameBanknote /> par séance</span>).
                    </p>

                    <div class="space-y-2 mb-3">
                        <div v-if="selectedTrainings.length" class="space-y-2 mb-3">
                            <div v-for="(slot, index) in selectedTrainings" :key="index"
                                 class="flex flex-col gap-2 p-2.5 rounded-lg bg-white border border-slate-200">

                                <!-- Carte joueur compacte -->
                                <div v-if="playerForEntry({ player_id: slot.player_id })" class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                        <PlayerPhoto :src="playerPhotoUrl(playerForEntry({ player_id: slot.player_id }))"
                                             class="w-full h-full object-cover" alt="" />
                                        <div v-if="!playerPhotoUrl(playerForEntry({ player_id: slot.player_id }))" class="w-full h-full flex items-center justify-center text-3xs text-slate-400">?</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-semibold truncate text-slate-700">
                                            {{ playerForEntry({ player_id: slot.player_id })?.lastname ?? '—' }}
                                        </div>
                                        <div class="text-2xs text-slate-400 truncate">
                                            {{ positionLabel(playerForEntry({ player_id: slot.player_id })?.position) }}
                                        </div>
                                        <!-- Endurance attendue au prochain match (la séance se paie avant le match) -->
                                        <div class="text-2xs truncate"
                                             :class="staminaAfter(slot) < LOW_STAMINA && playerForEntry({ player_id: slot.player_id })?.is_starter ? 'text-rose-500 font-semibold' : 'text-slate-500'">
                                            Endurance au match : {{ playerForEntry({ player_id: slot.player_id })?.stamina ?? 0 }} → {{ staminaAfter(slot) }}
                                            <template v-if="staminaAfter(slot) < LOW_STAMINA && playerForEntry({ player_id: slot.player_id })?.is_starter"> · titulaire fatigué</template>
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
                                        class="w-full px-2 py-1.5" :class="fieldClass({ shape: 'rect', size: 'sm' })">
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

                        <!-- Récap coût -->
                        <div v-if="trainingCost > 0 && selectedCount > 0"
                             class="flex items-center justify-between text-xs mb-2 px-1">
                            <span class="text-slate-500">Coût total ({{ selectedCount }} séance{{ selectedCount > 1 ? 's' : '' }})</span>
                            <span class="font-bold" :class="canAffordSelected ? 'text-slate-700' : 'text-rose-500'">
                                {{ totalTrainingCost }} <GameBanknote />
                                <span class="font-normal text-slate-400">→ reste {{ budgetAfter }} <GameBanknote /></span>
                            </span>
                        </div>

                        <!-- Bouton Lancer -->
                        <ButtonPrimary type="button" class="w-full" @click="emit('submit-training')" :disabled="!canSubmitTraining || !canAffordSelected">
                            <span class="text-center">Lancer l'entraînement<template v-if="trainingCost > 0 && selectedCount > 0"><span class="whitespace-nowrap"> — {{ totalTrainingCost }} <GameBanknote /></span></template></span>
                        </ButtonPrimary>
                    </div>

                    <p v-if="trainingCost > 0 && selectedCount > 0 && !canAffordSelected"
                       class="mt-3 text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2">
                        Budget insuffisant : il te faut {{ totalTrainingCost }} <GameBanknote /> pour ces séances.
                    </p>
                    <p v-else-if="remainingTrainingsThisWeek <= 0"
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
                                class="px-2.5 py-1 rounded-full text-2xs font-semibold border transition-all"
                                :class="aiTeamFilter === null ? 'bg-amber-400 text-white border-amber-500' : 'bg-white text-slate-500 border-slate-200 hover:border-amber-300'">
                            Toutes
                        </button>
                        <button v-for="t in aiTeams" :key="t.id" type="button"
                                @click="aiTeamFilter = aiTeamFilter === t.id ? null : t.id"
                                class="px-2.5 py-1 rounded-full text-2xs font-semibold border transition-all"
                                :class="aiTeamFilter === t.id ? 'bg-amber-400 text-white border-amber-500' : 'bg-white text-slate-500 border-slate-200 hover:border-amber-300'">
                            {{ t.name }}
                        </button>
                    </div>

                    <div v-if="filteredAiEntries.length" class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                        <div v-for="entry in filteredAiEntries" :key="entry.player_id + entry.stat + entry.team_id"
                             class="flex items-center gap-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-100">

                            <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <div class="w-full h-full flex items-center justify-center text-3xs text-slate-400">
                                    {{ entry.player_name?.charAt(0) ?? '?' }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-700 truncate">{{ entry.player_name }}</div>
                                <div class="text-2xs text-amber-600 font-semibold truncate">{{ entry.team_name }}</div>
                            </div>

                            <div v-if="entry.formation_label" class="text-right text-xs text-slate-600">
                                <div class="font-semibold">{{ entry.stat === 'position' ? 'Nouveau poste' : 'Technique spéciale' }} · {{ entry.formation_label }}</div>
                                <div class="text-2xs text-slate-500">{{ entry.cost }} GL · retour S{{ entry.week_return }}</div>
                            </div>
                            <div v-else class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full" :class="statColor(entry.stat)"></div>
                                <span class="text-xs text-slate-600 font-medium">{{ statLabel(entry.stat) }}</span>
                            </div>

                            <div v-if="!entry.formation_label" class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-200 text-amber-700">
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
                                <PlayerPhoto :src="playerForManualEntry(entry) ? playerPhotoUrl(playerForManualEntry(entry)) : null"
                                     class="w-full h-full object-cover" alt="" />
                                <div v-if="!(playerForManualEntry(entry) && playerPhotoUrl(playerForManualEntry(entry)))" class="w-full h-full flex items-center justify-center text-3xs text-slate-400">?</div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-700 truncate">
                                    {{ playerForManualEntry(entry)?.lastname ?? `Joueur #${entry.player_id}` }}
                                </div>
                                <div class="text-2xs text-slate-400">{{ entry.special_label ? `Formation · ${entry.cost} GL` : `Stamina -${entry.stamina_cost}` }}</div>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full" :class="statColor(entry.stat)"></div>
                                <span class="text-xs text-slate-600 font-medium">{{ entry.special_label ?? statLabel(entry.stat) }}</span>
                            </div>

                            <div v-if="!entry.special_label" class="px-2 py-0.5 rounded-full text-xs font-black bg-teal-200 text-teal-700">
                                +{{ entry.gain }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
