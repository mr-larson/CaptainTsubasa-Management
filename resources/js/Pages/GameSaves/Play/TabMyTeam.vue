<script setup>
import PlayerChemistryCard from '@/Pages/GameSaves/Play/components/PlayerChemistryCard.vue';
import PlayerMoraleCard from '@/Pages/GameSaves/Play/components/PlayerMoraleCard.vue';
import { fieldClass } from '@/Components/fieldStyles';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import RosterList from '@/Pages/GameSaves/Play/components/RosterList.vue';
import PlayerStatusAlert from '@/Pages/GameSaves/Play/components/PlayerStatusAlert.vue';
import PlayerComparison from './components/PlayerComparison.vue';
import PlayerIdentityCard from '@/Pages/GameSaves/Play/components/PlayerIdentityCard.vue';
import RadarChart from '@/Pages/GameSaves/Play/components/RadarChart.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import PerfChips from '@/Pages/GameSaves/Play/components/PerfChips.vue';
import FormationCard from '@/Pages/GameSaves/Play/components/FormationCard.vue';
import PitchView from '@/Pages/GameSaves/Play/components/PitchView.vue';
import BenchCard from '@/Pages/GameSaves/Play/components/BenchCard.vue';
import ClubIdentityCard from '@/Pages/GameSaves/Play/components/ClubIdentityCard.vue';
import TeamRecordCard from '@/Pages/GameSaves/Play/components/TeamRecordCard.vue';
import { masteryFor } from './lineupMastery.js';
import { useLineupIssues, ISSUE_CHIP } from './useLineupIssues.js';

const props = defineProps({
    playerChemistry: { type: Object, default: () => ({}) },
    rosterWithStatus:     { type: Array,   required: true },
    isPlayerInjured:      { type: Function, default: () => () => false },
    isPlayerSuspended:    { type: Function, default: () => () => false },
    isPlayerBenched:      { type: Function, default: () => () => false },
    playerPositionTraining: { type: Function, default: () => null },
    playerYellowCards:    { type: Function, default: () => () => 0 },
    playerInjury:         { type: Function, default: () => () => null },
    playerSuspension:     { type: Function, default: () => () => null },
    playerBench:          { type: Function, default: () => () => null },
    selectedMyPlayer:     { type: Object,  default: null },
    currentFormation:     { type: String,  required: true },
    formationData:        { type: Object,  default: null },
    miniPitchMarkerStyle: { type: Object,  required: true },
    selectedMyPlayerPerf: { type: Object,  default: null },
    lineupForm:           { type: Array,   required: true },
    isPickedUp: { type: Function, default: () => false },
    // Fonctions terrain (depuis useTeam)
    playerPosition:       { type: Function, required: true },
    playerForSlot:        { type: Function, required: true },
    selectedSlot:         { type: Number,   default: null },
    // Infos club
    team:                 { type: Object,  default: null },
    teamRecord:           { type: Object,  default: () => ({ wins: 0, draws: 0, losses: 0 }) },
    teamBudget:           { type: Number,  default: 0 },
    gameSaveId:           { type: Number,  required: true },
    contractSeasonEndWeek:{ type: Number,  default: null },
    clubStanding:         { type: Object,  default: null },
    standings:            { type: Array,   default: () => [] },
    injuriesCount:        { type: Number,  default: 0 },
    suspensionsCount:     { type: Number,  default: 0 },
    cardsCount:           { type: Number,  default: 0 },
    averageAttack:        { type: Number,  default: 0 },
    averageDefense:       { type: Number,  default: 0 },
    averageStamina:       { type: Number,  default: 0 },
    averageSpeed:         { type: Number,  default: 0 },
    moraleLogs:           { type: Object,  default: () => ({}) },
    playerPromises:       { type: Object,  default: () => ({}) },
    playerDeclarations:   { type: Object,  default: () => ({}) },
    currentWeek:          { type: Number,  default: 1 },
    currentSeason:        { type: Number,  default: 1 },
    // Dernier changement de compo annulable : { id, label } | null
    lastChange:           { type: Object,  default: null },
    // (formationKey) => { asIs, optimized } (cf. useTeam.formationImpact)
    formationImpact:      { type: Function, default: null },
});

const emit = defineEmits([
    'select-player', 'toggle-starter', 'toggle-captain', 'save-formation', 'update-number',
    'player-click', 'drag-start', 'drag-over', 'drop-on', 'make-promise', 'make-declaration',
    'release-player', 'pick-up', 'cancel-pickup', 'fill-slot', 'drop-on-slot', 'drag-end',
    'undo', 'reorganize',
]);

const substitutes = computed(() => props.rosterWithStatus.filter(p => !p.is_starter));
const startersCount = computed(() => props.rosterWithStatus.filter(p => p.is_starter).length);
const onzeComplet   = computed(() => startersCount.value >= 11);

const selectedIsPickedUp = computed(() => !!props.selectedMyPlayer && props.isPickedUp(props.selectedMyPlayer));

// Échap annule la prise en cours (le clic à côté est géré sur la racine).
const onKey = (e) => { if (e.key === 'Escape') emit('cancel-pickup'); };
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    emit('cancel-pickup'); // changement d'onglet : rien ne reste soulevé
});

// Numéro refusé par le serveur (déjà pris) : l'input revient à l'ancien numéro.
function onNumberChange(event) {
    const input    = event.target;
    const previous = props.selectedMyPlayer?.number ?? '';
    emit('update-number', props.selectedMyPlayer.id, input.value, () => { input.value = previous; });
}

const { moraleState } = usePlayerUtils();

const renewalPlayer = ref(null);
const renewalForm = useForm({ weeks: 1, end_week: null });
const renewalLimit = (player) => player?.contract_end_week >= props.currentWeek && props.contractSeasonEndWeek
    ? Math.max(0, props.contractSeasonEndWeek - player.contract_end_week)
    : 0;
const renewalMaxWeeks = computed(() => renewalLimit(renewalPlayer.value));
const renewalCoveredWeeks = computed(() => Math.max(0, renewalForm.end_week - props.currentWeek));
const renewalEndWeek = computed(() =>
    Number.isInteger(renewalForm.weeks) && renewalForm.weeks >= 1 && renewalForm.weeks <= renewalMaxWeeks.value
        ? renewalForm.end_week + renewalForm.weeks
        : null
);
const renewalCost = computed(() => (renewalPlayer.value?.renewal_cost ?? 0) * renewalForm.weeks);
const canSubmitRenewal = computed(() =>
    renewalEndWeek.value !== null && renewalCost.value <= props.teamBudget && !renewalForm.processing
);

function openRenewal(player) {
    renewalForm.reset();
    renewalForm.clearErrors();
    renewalForm.end_week = player.contract_end_week;
    renewalPlayer.value = player;
}

function closeRenewal() {
    if (!renewalForm.processing) renewalPlayer.value = null;
}

function submitRenewal() {
    if (!canSubmitRenewal.value) return;
    renewalForm.patch(route('game-saves.contracts.renew', {
        gameSave: props.gameSaveId,
        contract: renewalPlayer.value.contract_id,
    }), { preserveScroll: true, onSuccess: () => { renewalPlayer.value = null; } });
}

// ── Moral moyen (carte Club) ─────────────────────────────────
const averageMorale = computed(() => {
    if (!props.rosterWithStatus.length) return 0;
    const sum = props.rosterWithStatus.reduce((a, p) => a + Number(p.morale ?? 60), 0);
    return Math.round(sum / props.rosterWithStatus.length);
});

// ── Maîtrise du poste occupé (miroir de RosterService.positionMastery) ──
// Titulaire soulevé : adéquation de chaque remplaçant à son poste (halos du banc).
const pickedPlayer = computed(() => props.rosterWithStatus.find(p => props.isPickedUp(p)) ?? null);
const benchFit = computed(() => {
    const picked = pickedPlayer.value;
    if (!picked?.is_starter) return null;
    const slot = props.lineupForm.find(r => r.player_id === picked.id)?.slot;
    const def  = slot ? props.formationData?.slots?.[slot] : null;
    return def ? (sub) => masteryFor(sub, def) : null;
});

// Réorganisation possible sur la formation actuelle ?
const currentImpact = computed(() => props.formationImpact ? props.formationImpact(props.currentFormation) : null);
const canReorganize = computed(() => {
    const i = currentImpact.value;
    if (!i) return false;
    return i.optimized.off < i.asIs.off
        || (i.optimized.off === i.asIs.off && i.optimized.secondary < i.asIs.secondary);
});

const slotMastery = (slot, slotDef) => masteryFor(props.playerForSlot(slot), slotDef);

// ── Poste vide : remplaçants proposés, les plus adaptés d'abord ──
const MASTERY_RANK = { primary: 0, secondary: 1, off: 2 };

const { unavailability, lineupIssues } = useLineupIssues({
    roster:            () => props.rosterWithStatus,
    formationData:     () => props.formationData,
    playerForSlot:     () => props.playerForSlot,
    isPlayerInjured:   () => props.isPlayerInjured,
    isPlayerSuspended: () => props.isPlayerSuspended,
    isPlayerBenched:   () => props.isPlayerBenched,
    positionTraining:  () => props.playerPositionTraining,
});

const slotCandidates = (slot, slotDef) => substitutes.value
    .map(p => {
        const u = unavailability(p);
        return { player: p, mastery: masteryFor(p, slotDef), unavailable: u?.label ?? null, unavailableIcon: u?.icon ?? null };
    })
    .sort((a, b) =>
        (a.unavailable ? 1 : 0) - (b.unavailable ? 1 : 0)
        || (MASTERY_RANK[a.mastery] ?? 3) - (MASTERY_RANK[b.mastery] ?? 3)
        || Number(b.player.stamina ?? b.player.stats?.stamina ?? 0) - Number(a.player.stamina ?? a.player.stats?.stamina ?? 0)
    );
const comparison = ref([]);
const comparisonAvailability = p => unavailability(p)?.label ?? "Disponible";
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0"
         @click="emit('cancel-pickup')">

        <!-- LIGNE 1 : Formation + Terrain + Banc -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <FormationCard class="lg:col-span-4"
                           :formation="currentFormation"
                           :formationData="formationData"
                           :impact="formationImpact"
                           @change="key => emit('save-formation', key)">
                <template #after-select>
                    <button v-if="canReorganize" type="button"
                            @click.stop="emit('reorganize')"
                            class="-mt-1 w-full flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border transition-all bg-amber-50 text-amber-700 border-amber-300 hover:bg-amber-100"
                            title="Replace les mêmes titulaires sur les postes qui leur conviennent le mieux (en bougeant le moins de joueurs possible)">
                        ⇄ Réorganiser le onze
                        <span class="font-semibold opacity-80">
                            <template v-if="currentImpact.optimized.off < currentImpact.asIs.off">
                                ({{ currentImpact.asIs.off }} → {{ currentImpact.optimized.off }} hors poste)
                            </template>
                            <template v-else>
                                ({{ currentImpact.asIs.secondary }} → {{ currentImpact.optimized.secondary }} en poste secondaire)
                            </template>
                        </span>
                    </button>
                </template>
            </FormationCard>

            <PitchView class="lg:col-span-6"
                       :formationData="formationData"
                       :playerPosition="playerPosition"
                       :playerForSlot="playerForSlot"
                       :selectedSlot="selectedSlot"
                       :isPickedUp="isPickedUp"
                       :players="rosterWithStatus"
                       :slotMastery="slotMastery"
                       :slotCandidates="slotCandidates"
                       :masteryFor="masteryFor"
                       :playerStatus="unavailability"
                       :playerCards="p => ({ yellow: playerYellowCards(p.id), red: isPlayerSuspended(p.id) })"
                       @player-click="p => emit('player-click', p)"
                       @drag-start="(p, e) => emit('drag-start', p, e)"
                       @drag-end="emit('drag-end')"
                       @drag-over="e => emit('drag-over', e)"
                       @drop-on="(p, e) => emit('drop-on', p, e)"
                       @fill-slot="(slot, p) => emit('fill-slot', slot, p)"
                       @drop-on-slot="(slot, e) => emit('drop-on-slot', slot, e)"
                       @cancel-pickup="emit('cancel-pickup')" />

            <BenchCard class="lg:col-span-2"
                       :substitutes="substitutes"
                       :selectedId="selectedMyPlayer?.id"
                       :isPickedUp="isPickedUp"
                       :playerStatus="unavailability"
                       :pickFit="benchFit"
                       @player-click="p => emit('player-click', p)"
                       @drag-start="(p, e) => emit('drag-start', p, e)"
                       @drag-end="emit('drag-end')"
                       @drag-over="e => emit('drag-over', e)"
                       @drop-on="(p, e) => emit('drop-on', p, e)" />
        </div>

        <!-- État du onze : ce qui mérite attention avant le match -->
        <!-- Grand écran : déjà affiché en permanence dans la colonne gauche -->
        <div class="-mt-2 flex flex-wrap items-center gap-1.5 min-[1400px]:hidden">
            <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider mr-1">État du onze</span>
            <template v-if="lineupIssues.length">
                <component :is="issue.player ? 'button' : 'span'" v-for="issue in lineupIssues" :key="issue.key"
                           :type="issue.player ? 'button' : undefined"
                           @click="issue.player && emit('select-player', issue.player)"
                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] font-semibold transition-all"
                           :class="[ISSUE_CHIP[issue.level], issue.player ? 'cursor-pointer' : 'cursor-default']"
                           :title="issue.player ? 'Voir la fiche du joueur' : undefined">
                    {{ issue.text }}
                </component>
            </template>
            <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] font-semibold bg-emerald-50 border-emerald-200 text-emerald-700">
                ✓ Onze complet, tous à leur poste, capitaine désigné
            </span>
        </div>

        <!-- LIGNE 2 : Infos club -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <ClubIdentityCard class="md:col-span-5"
                              :team="team"
                              :rank="clubStanding?.position ?? null"
                              :total="standings.length"
                              :averageOverall="clubStanding?.average_overall ?? null"
                              :budget="teamBudget" />

            <TeamRecordCard class="md:col-span-3"
                            :wins="teamRecord.wins"
                            :draws="teamRecord.draws"
                            :losses="teamRecord.losses" />

            <!-- Stats + Effectif -->
            <div class="md:col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">Club</h4>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                    <div class="flex justify-between"><span>Attaque moy.</span><span class="font-bold text-slate-700">{{ averageAttack }}</span></div>
                    <div class="flex justify-between"><span>Blessés</span>
                        <span class="font-bold" :class="injuriesCount > 0 ? 'text-rose-500' : 'text-slate-700'">{{ injuriesCount }}</span>
                    </div>
                    <div class="flex justify-between"><span>Défense moy.</span><span class="font-bold text-slate-700">{{ averageDefense }}</span></div>
                    <div class="flex justify-between"><span>Suspensions</span>
                        <span class="font-bold" :class="suspensionsCount > 0 ? 'text-amber-500' : 'text-slate-700'">{{ suspensionsCount }}</span>
                    </div>
                    <div class="flex justify-between"><span>Stamina moy.</span><span class="font-bold text-slate-700">{{ averageStamina }}</span></div>
                    <div class="flex justify-between"><span>Vitesse moy.</span><span class="font-bold text-slate-700">{{ averageSpeed }}</span></div>
                    <div class="flex justify-between"><span>Moral moy.</span>
                        <span class="font-bold" :class="moraleState(averageMorale).text">{{ moraleState(averageMorale).emoji }} {{ averageMorale }}</span>
                    </div>
                    <div class="flex justify-between"><span>Joueurs avertis</span>
                        <span class="font-bold" :class="cardsCount > 0 ? 'text-yellow-500' : 'text-slate-700'">{{ cardsCount }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIGNE 3 : Liste joueurs + Profil -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

            <!-- Liste joueurs — hauteur calée sur la colonne profil (scroll interne) -->
            <div class="lg:col-span-3 relative min-h-[320px]">
                <RosterList class="absolute inset-0"
                            :players="rosterWithStatus"
                            :selectedId="selectedMyPlayer?.id"
                            :isPlayerInjured="isPlayerInjured"
                            :isPlayerSuspended="isPlayerSuspended"
                            :isPlayerBenched="isPlayerBenched"
                            :playerYellowCards="playerYellowCards"
                            :playerInjury="playerInjury"
                            :playerSuspension="playerSuspension"
                            :playerBench="playerBench"
                            @select="p => emit('select-player', p)" />
            </div>

            <!-- Profil joueur -->
            <div class="lg:col-span-9 flex flex-col gap-3">
                <template v-if="selectedMyPlayer">

                    <PlayerStatusAlert :player="selectedMyPlayer"
                                       :isPlayerInjured="isPlayerInjured"
                                       :isPlayerSuspended="isPlayerSuspended"
                                       :isPlayerBenched="isPlayerBenched"
                                       :playerInjury="playerInjury"
                                       :playerSuspension="playerSuspension"
                                       :playerBench="playerBench" />

                    <!-- Identité + actions -->
                    <PlayerIdentityCard :player="selectedMyPlayer">
                        <template #actions>
                            <!-- Entrée / sortie du onze -->
                            <template v-if="selectedMyPlayer.contract_id">
                                <!-- Prise en cours : un seul bouton pour annuler -->
                                <button v-if="selectedIsPickedUp" type="button"
                                        @click.stop="emit('cancel-pickup')"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all bg-amber-400 text-slate-700 border-amber-500 hover:bg-amber-500"
                                        title="Annuler (Échap)">
                                    ✕ Annuler le remplacement
                                </button>
                                <template v-else-if="selectedMyPlayer.is_starter">
                                    <button type="button"
                                            @click.stop="emit('pick-up', selectedMyPlayer)"
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all bg-emerald-500 text-white border-emerald-600 hover:bg-emerald-600"
                                            title="Puis clique un coéquipier (échange de poste), un remplaçant (il sort) ou un poste vide">
                                        ⇄ Déplacer / remplacer…
                                    </button>
                                    <ButtonSecondary size="sm" @click="emit('toggle-starter', selectedMyPlayer.contract_id)" title="Le joueur passe sur le banc et son poste reste vide">
                                        ↓ Sortir du onze
                                    </ButtonSecondary>
                                </template>
                                <!-- Remplaçant, onze complet : choisir qui il remplace -->
                                <button v-else-if="onzeComplet" type="button"
                                        @click.stop="emit('pick-up', selectedMyPlayer)"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all bg-white text-emerald-600 border-emerald-400 hover:bg-emerald-50"
                                        title="Puis clique sur le terrain le titulaire qu'il remplace">
                                    ⇄ Faire entrer…
                                </button>
                                <!-- Remplaçant, poste(s) vide(s) : titularisation directe -->
                                <button v-else type="button"
                                        @click="emit('toggle-starter', selectedMyPlayer.contract_id)"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all bg-white text-emerald-600 border-emerald-400 hover:bg-emerald-50"
                                        :title="`Il prend le premier poste vide (${11 - startersCount} libre(s)) — ou clique un poste vide sur le terrain pour choisir`">
                                    + Titulariser ({{ startersCount }}/11)
                                </button>
                            </template>
                            <button
                                v-if="selectedMyPlayer.contract_id"
                                type="button"
                                @click="emit('toggle-captain', selectedMyPlayer.contract_id)"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                                :class="selectedMyPlayer.is_captain
                                    ? 'bg-amber-400 text-white border-amber-500 hover:bg-amber-500'
                                    : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50'"
                                :title="selectedMyPlayer.is_captain ? 'Retirer le brassard' : 'Nommer capitaine'"
                            >
                                {{ selectedMyPlayer.is_captain ? '👑 Capitaine' : '👑 Nommer capitaine' }}
                            </button>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs text-slate-500 font-semibold">N°</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    :value="selectedMyPlayer.number"
                                    @change="onNumberChange($event)"
                                    class="w-14 px-2 py-1" :class="fieldClass({ shape: 'rect', size: 'sm' })"
                                />
                            </div>
                            <!-- Résiliation -->
                            <ButtonDanger size="sm" v-if="selectedMyPlayer.contract_id" @click="emit('release-player', selectedMyPlayer)" title="Résilier le contrat : salaire déjà versé non remboursé, relation avec le coach rompue (-100)">
                                ✖ Résilier
                            </ButtonDanger>
                            <ButtonSecondary v-if="selectedMyPlayer.contract_id && renewalLimit(selectedMyPlayer)"
                                             type="button" size="sm" @click="openRenewal(selectedMyPlayer)">
                                Prolonger
                            </ButtonSecondary>
                        </template>
                        <template #footer>
                            <p class="mt-3 text-2xs text-slate-400 italic">
                                💡 Sur le terrain : clique un joueur pour voir sa fiche, re-clique-le (ou glisse-le) pour le déplacer,
                                puis clique sa cible. Les postes vides « + » se remplissent d'un clic. Échap pour annuler.
                            </p>
                            <p v-if="selectedMyPlayer.description" class="mt-2 text-xs text-slate-400 italic">{{ selectedMyPlayer.description }}</p>
                        </template>
                    </PlayerIdentityCard>

                    <PlayerComparison v-model="comparison" :player="selectedMyPlayer" :availability="comparisonAvailability" />

                    <!-- Moral -->
                    <PlayerMoraleCard :player="selectedMyPlayer"
                                      :logs="moraleLogs?.[selectedMyPlayer.id] ?? []"
                                      :promises="playerPromises?.[selectedMyPlayer.id] ?? []"
                                      :declarations="playerDeclarations?.[selectedMyPlayer.id] ?? []"
                                      :currentWeek="currentWeek" :currentSeason="currentSeason"
                                      @make-promise="(p, t) => emit('make-promise', p, t)"
                                      @make-declaration="(p, t) => emit('make-declaration', p, t)" />

                    <PlayerChemistryCard :key="selectedMyPlayer.id"
                                         :gameSaveId="gameSaveId"
                                         :playerId="selectedMyPlayer.id"
                                         :relations="playerChemistry[selectedMyPlayer.id] ?? []" />

                    <!-- Radar + Barres -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <RadarChart :player="selectedMyPlayer" accent="teal" />
                        <StatBars :player="selectedMyPlayer" />
                    </div>

                    <!-- Perf chips -->
                    <PerfChips :player="selectedMyPlayer" :perf="selectedMyPlayerPerf" />

                </template>

                <div v-else class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
                    Sélectionne un joueur dans la liste ou sur le terrain
                </div>
            </div>
        </div>
        <Modal :show="!!renewalPlayer" max-width="md" @close="closeRenewal">
            <form v-if="renewalPlayer" class="space-y-4 p-5" @submit.prevent="submitRenewal">
                <h3 class="text-lg font-bold text-slate-800">Prolonger {{ renewalPlayer.firstname }} {{ renewalPlayer.lastname }}</h3>
                <dl class="grid grid-cols-[1fr_auto] gap-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                    <dt>Fin du contrat actuel</dt><dd class="text-right font-semibold">Semaine {{ renewalForm.end_week }}</dd>
                    <dt>Semaines à venir déjà couvertes</dt><dd class="text-right font-semibold">{{ renewalCoveredWeeks }}</dd>
                    <dt>Semaines encore ajoutables</dt><dd class="text-right font-semibold">{{ renewalMaxWeeks }}</dd>
                </dl>
                <label class="block text-sm font-semibold text-slate-700">
                    Semaines à ajouter
                    <input v-model.number="renewalForm.weeks" type="number" required min="1"
                           :max="renewalMaxWeeks" class="mt-1 block w-full px-3 py-1.5"
                           :class="fieldClass({ shape: 'rect' })" />
                </label>
                <dl class="grid grid-cols-2 gap-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                    <dt>Fin après prolongation</dt><dd class="text-right font-semibold">{{ renewalEndWeek === null ? '—' : `Semaine ${renewalEndWeek}` }}</dd>
                    <dt>Salaire recalculé</dt><dd class="text-right font-semibold">{{ renewalPlayer.renewal_cost }} <GameBanknote /> / semaine</dd>
                    <dt>Coût total</dt><dd class="text-right font-semibold">{{ renewalCost }} <GameBanknote /></dd>
                    <dt>Budget après signature</dt><dd class="text-right font-semibold" :class="{ 'text-rose-600': renewalCost > teamBudget }">{{ teamBudget - renewalCost }} <GameBanknote /></dd>
                </dl>
                <p v-if="renewalForm.errors.weeks || renewalForm.errors.end_week" role="alert" class="text-sm text-rose-600">
                    {{ renewalForm.errors.weeks || renewalForm.errors.end_week }}
                </p>
                <div class="flex justify-end gap-2">
                    <ButtonSecondary type="button" :disabled="renewalForm.processing" @click="closeRenewal">Annuler</ButtonSecondary>
                    <ButtonPrimary :disabled="!canSubmitRenewal">Confirmer la prolongation</ButtonPrimary>
                </div>
            </form>
        </Modal>

        <!-- Annuler le dernier changement de compo -->
        <Transition enter-from-class="opacity-0 translate-y-2" leave-to-class="opacity-0 translate-y-2"
                    enter-active-class="transition duration-200" leave-active-class="transition duration-200">
            <div v-if="lastChange" :key="lastChange.id"
                 class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 pl-4 pr-1.5 py-1.5 rounded-full bg-slate-700 text-white text-xs font-semibold shadow-xl"
                 @click.stop>
                <span>✓ {{ lastChange.label }}</span>
                <button type="button" @click="emit('undo')"
                        class="px-3 py-1 rounded-full bg-white/15 hover:bg-white/30 font-bold">
                    ↶ Annuler
                </button>
            </div>
        </Transition>
    </div>
</template>
