<script setup>
import { computed, onMounted, onBeforeUnmount } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import RosterList from '@/Pages/GameSaves/Play/components/RosterList.vue';
import PlayerStatusAlert from '@/Pages/GameSaves/Play/components/PlayerStatusAlert.vue';
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
    rosterWithStatus:     { type: Array,   required: true },
    isPlayerInjured:      { type: Function, default: () => () => false },
    isPlayerSuspended:    { type: Function, default: () => () => false },
    isPlayerBenched:      { type: Function, default: () => () => false },
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
    clubStanding:         { type: Object,  default: null },
    standings:            { type: Array,   default: () => [] },
    injuriesCount:        { type: Number,  default: 0 },
    suspensionsCount:     { type: Number,  default: 0 },
    cardsCount:           { type: Number,  default: 0 },
    averageAttack:        { type: Number,  default: 0 },
    averageDefense:       { type: Number,  default: 0 },
    averageStamina:       { type: Number,  default: 0 },
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

const { moraleState, moraleSourceLabel, moraleMatchEffect, HEROIC_MORALE_THRESHOLD } = usePlayerUtils();

// ── Moral ────────────────────────────────────────────────────
const selectedPlayerMorale = computed(() => moraleState(props.selectedMyPlayer?.morale));

const selectedPlayerMoraleLogs = computed(() =>
    props.moraleLogs?.[props.selectedMyPlayer?.id] ?? []
);

const selectedPlayerMoraleEffect = computed(() => moraleMatchEffect(props.selectedMyPlayer?.morale));

const selectedPlayerIsHeroic = computed(() =>
    Number(props.selectedMyPlayer?.morale ?? 60) > HEROIC_MORALE_THRESHOLD
);

const averageMorale = computed(() => {
    if (!props.rosterWithStatus.length) return 0;
    const sum = props.rosterWithStatus.reduce((a, p) => a + Number(p.morale ?? 60), 0);
    return Math.round(sum / props.rosterWithStatus.length);
});

// ── Relation coach + promesses ───────────────────────────────
const selectedPlayerAffinity = computed(() => Number(props.selectedMyPlayer?.coach_affinity ?? 0));

const affinityDisplay = computed(() => {
    const a = selectedPlayerAffinity.value;
    if (a <= -50) return { label: 'Rupture',    text: 'text-rose-600',    bar: 'bg-rose-500' };
    if (a <= -30) return { label: 'Tendue',     text: 'text-orange-500',  bar: 'bg-orange-400' };
    if (a <   30) return { label: 'Neutre',     text: 'text-slate-500',   bar: 'bg-slate-400' };
    if (a <   50) return { label: 'Bonne',      text: 'text-teal-600',    bar: 'bg-teal-400' };
    return         { label: 'Excellente', text: 'text-emerald-600', bar: 'bg-emerald-500' };
});

const selectedPlayerPromises = computed(() =>
    props.playerPromises?.[props.selectedMyPlayer?.id] ?? []
);

const activePromise = computed(() =>
    selectedPlayerPromises.value.find(p => p.status === 'pending') ?? null
);

const lastResolvedPromise = computed(() =>
    // 'expired' = promesse rendue caduque par l'intersaison : rien à afficher.
    selectedPlayerPromises.value.find(p => p.status === 'kept' || p.status === 'broken') ?? null
);

const PROMISE_TYPES = [
    { type: 'playing_time', icon: '🤝', label: 'Temps de jeu',  hint: 'Il jouera au moins 15 tours (~30 min) à son prochain match', kept: 5,  broken: 10 },
    { type: 'starter',      icon: '⭐', label: 'Titularisation', hint: 'Il sera titulaire sur 4 matchs dans les 5 prochaines semaines', kept: 10, broken: 15 },
    { type: 'renewal',      icon: '📜', label: 'Prolongation',   hint: 'Tu lui signeras un nouveau contrat sous 4 semaines', kept: 15, broken: 20 },
];

const promiseTypeLabel = (type) => PROMISE_TYPES.find(t => t.type === type)?.label ?? type;

// ── Déclarations publiques ───────────────────────────────────
const DECLARATION_COOLDOWN_WEEKS = 3;

const lastDeclaration = computed(() =>
    (props.playerDeclarations?.[props.selectedMyPlayer?.id] ?? [])[0] ?? null
);

// Même règle que DeclarationService : le délai ne compte que dans la saison en cours.
const declarationOnCooldown = computed(() =>
    lastDeclaration.value
    && Number(lastDeclaration.value.season) === Number(props.currentSeason)
    && lastDeclaration.value.week > (props.currentWeek - DECLARATION_COOLDOWN_WEEKS)
);

const lastDeclarationSummary = computed(() => {
    const d = lastDeclaration.value;
    if (!d) return null;
    if (d.type === 'praise') return { text: '📣 Félicité', positive: true };
    if (d.outcome === 'proud_reaction') return { text: '📢 Critiqué — réaction d\'orgueil', positive: true };
    return { text: '📢 Critiqué — mal vécu', positive: false };
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
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">État du onze</span>
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
                              :budget="teamBudget" />

            <TeamRecordCard class="md:col-span-3"
                            :wins="teamRecord.wins"
                            :draws="teamRecord.draws"
                            :losses="teamRecord.losses" />

            <!-- Stats + Effectif -->
            <div class="md:col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Club</h4>
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
                    <div class="flex justify-between"><span>Moral moy.</span>
                        <span class="font-bold" :class="moraleState(averageMorale).text">{{ moraleState(averageMorale).emoji }} {{ averageMorale }}</span>
                    </div>
                    <div class="flex justify-between"><span>Cartons</span>
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
                                    <button type="button"
                                            @click="emit('toggle-starter', selectedMyPlayer.contract_id)"
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all bg-white text-slate-500 border-slate-300 hover:bg-slate-50"
                                            title="Le joueur passe sur le banc et son poste reste vide">
                                        ↓ Sortir du onze
                                    </button>
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
                                    class="w-14 border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-teal-300 focus:outline-none"
                                />
                            </div>
                            <!-- Résiliation -->
                            <button v-if="selectedMyPlayer.contract_id" type="button"
                                    @click="emit('release-player', selectedMyPlayer)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all bg-white text-rose-500 border-rose-300 hover:bg-rose-50"
                                    title="Résilier le contrat : salaire déjà versé non remboursé, relation avec le coach rompue (-100)">
                                ✖ Résilier
                            </button>
                        </template>
                        <template #footer>
                            <p class="mt-3 text-[10px] text-slate-400 italic">
                                💡 Sur le terrain : clique un joueur pour voir sa fiche, re-clique-le (ou glisse-le) pour le déplacer,
                                puis clique sa cible. Les postes vides « + » se remplissent d'un clic. Échap pour annuler.
                            </p>
                            <p v-if="selectedMyPlayer.description" class="mt-2 text-xs text-slate-400 italic">{{ selectedMyPlayer.description }}</p>
                        </template>
                    </PlayerIdentityCard>

                    <!-- Moral -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Moral</h4>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                  :class="selectedPlayerMorale.chip">
                                {{ selectedPlayerMorale.emoji }} {{ selectedPlayerMorale.label }}
                                <span class="opacity-60">{{ selectedMyPlayer.morale ?? 60 }}/100</span>
                            </span>
                        </div>

                        <div class="h-2 bg-slate-200 rounded-full overflow-hidden mb-2">
                            <div class="h-full rounded-full transition-all"
                                 :class="selectedPlayerMorale.bar"
                                 :style="{ width: Math.min(selectedMyPlayer.morale ?? 60, 100) + '%' }"></div>
                        </div>

                        <!-- Effets en match -->
                        <div class="flex flex-wrap items-center gap-1.5 mb-3">
                            <span v-if="selectedPlayerMoraleEffect"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                  :class="selectedPlayerMoraleEffect.positive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                  title="Appliqué à toutes les actions en match (duels, tirs, arrêts)">
                                ⚔️ Effet en match : {{ selectedPlayerMoraleEffect.pct }}
                            </span>
                            <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500">
                                ⚔️ Aucun effet en match
                            </span>
                            <span v-if="selectedPlayerIsHeroic"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700"
                                  title="5 % de chance de relancer gratuitement un duel perdu (une fois par match)">
                                🔥 Dépassement de soi
                            </span>
                        </div>

                        <!-- Relation coach -->
                        <div class="border-t border-slate-200 pt-3 mb-3">
                            <div class="flex items-center justify-between mb-1.5">
                                <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Relation coach</h5>
                                <span class="text-[11px] font-bold" :class="affinityDisplay.text">
                                    {{ affinityDisplay.label }}
                                    <span class="opacity-60">{{ selectedPlayerAffinity > 0 ? '+' : '' }}{{ selectedPlayerAffinity }}</span>
                                </span>
                            </div>
                            <div class="relative h-1.5 bg-slate-200 rounded-full overflow-hidden mb-2">
                                <div class="absolute top-0 bottom-0 w-px bg-slate-400/60 left-1/2"></div>
                                <div class="h-full rounded-full transition-all"
                                     :class="affinityDisplay.bar"
                                     :style="{ width: ((selectedPlayerAffinity + 100) / 2) + '%' }"></div>
                            </div>

                            <!-- Promesses -->
                            <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                <span v-if="activePromise"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700"
                                      :title="activePromise.type === 'renewal'
                                          ? `Signe-lui un nouveau contrat avant la semaine ${activePromise.due_week}`
                                          : activePromise.type === 'playing_time'
                                              ? `Il doit jouer au moins ${activePromise.target_turns} tours à son prochain match`
                                              : `Il doit être titulaire sur ${activePromise.target_matches} matchs entre la semaine ${activePromise.start_week} et la semaine ${activePromise.due_week}`">
                                    🤝 Promesse en cours — {{ promiseTypeLabel(activePromise.type) }}
                                    <template v-if="activePromise.type === 'playing_time'">: ≥{{ activePromise.target_turns }} tours au prochain match</template>
                                    <template v-else-if="activePromise.type === 'starter'">: {{ activePromise.target_matches }} matchs titulaire avant S{{ activePromise.due_week }}</template>
                                    <template v-else>avant S{{ activePromise.due_week }}</template>
                                </span>
                                <template v-else>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Promettre :</span>
                                    <button v-for="pt in PROMISE_TYPES" :key="pt.type" type="button"
                                            @click="emit('make-promise', selectedMyPlayer, pt.type)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold border border-sky-300 bg-white text-sky-600 hover:bg-sky-50 transition-all"
                                            :title="`${pt.hint}. Tenue : +${pt.kept} relation, +${pt.kept} moral. Rompue : −${pt.broken} relation, −${pt.broken} moral.`">
                                        {{ pt.icon }} {{ pt.label }}
                                    </button>
                                </template>
                                <span v-if="lastResolvedPromise"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                      :class="lastResolvedPromise.status === 'kept' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                    {{ lastResolvedPromise.status === 'kept' ? '✓ Promesse tenue' : '✗ Promesse rompue' }}
                                    <template v-if="lastResolvedPromise.type === 'playing_time'">
                                        ({{ lastResolvedPromise.played_turns ?? 0 }}/{{ lastResolvedPromise.target_turns }} tours)
                                    </template>
                                    <template v-else-if="lastResolvedPromise.type === 'starter'">
                                        ({{ lastResolvedPromise.played_matches ?? 0 }}/{{ lastResolvedPromise.target_matches }})
                                    </template>
                                </span>
                            </div>

                            <!-- Déclarations publiques -->
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Presse :</span>
                                <template v-if="!declarationOnCooldown">
                                    <button type="button"
                                            @click="emit('make-declaration', selectedMyPlayer, 'praise')"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold border border-emerald-300 bg-white text-emerald-600 hover:bg-emerald-50 transition-all"
                                            title="Féliciter publiquement. Mérité (en forme) : +8 relation, +4 moral. Immérité : +4 relation seulement.">
                                        📣 Féliciter
                                    </button>
                                    <button type="button"
                                            @click="emit('make-declaration', selectedMyPlayer, 'criticize')"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold border border-rose-300 bg-white text-rose-600 hover:bg-rose-50 transition-all"
                                            title="Critiquer publiquement. En méforme : peut provoquer une réaction d'orgueil (+5 moral)… ou être mal vécu. En forme : toujours injuste (−15 relation, −8 moral).">
                                        📢 Critiquer
                                    </button>
                                </template>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-400"
                                      :title="`Prochaine déclaration possible en semaine ${lastDeclaration.week + 3}`">
                                    ⏳ Déjà exprimé récemment
                                </span>
                                <span v-if="lastDeclarationSummary"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                      :class="lastDeclarationSummary.positive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                    {{ lastDeclarationSummary.text }} (S{{ lastDeclaration.week }})
                                </span>
                            </div>
                        </div>

                        <div v-if="selectedPlayerMoraleLogs.length" class="space-y-1">
                            <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Derniers changements</h5>
                            <div v-for="(log, i) in selectedPlayerMoraleLogs" :key="i"
                                 class="flex items-center gap-2 text-xs">
                                <span class="w-8 text-right font-black shrink-0"
                                      :class="log.value > 0 ? 'text-emerald-600' : 'text-rose-500'">
                                    {{ log.value > 0 ? '+' : '' }}{{ log.value }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 text-[10px] font-semibold shrink-0">
                                    {{ moraleSourceLabel(log.source) }}
                                </span>
                                <span class="text-slate-600 truncate">{{ log.label }}</span>
                                <span class="ml-auto text-[10px] text-slate-400 shrink-0">S{{ log.week }}</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Aucun changement de moral pour l'instant.</p>
                    </div>

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
