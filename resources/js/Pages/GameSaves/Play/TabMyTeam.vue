<script setup>
import { computed } from 'vue';
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

const props = defineProps({
    rosterWithStatus:     { type: Array,   required: true },
    isPlayerInjured:      { type: Function, default: () => () => false },
    isPlayerSuspended:    { type: Function, default: () => () => false },
    playerYellowCards:    { type: Function, default: () => () => 0 },
    playerInjury:         { type: Function, default: () => () => null },
    playerSuspension:     { type: Function, default: () => () => null },
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
});

const emit = defineEmits([
    'select-player', 'toggle-starter', 'toggle-captain', 'save-formation', 'update-number',
    'player-click', 'drag-start', 'drag-over', 'drop-on', 'make-promise', 'make-declaration',
]);

const substitutes = computed(() => props.rosterWithStatus.filter(p => !p.is_starter));

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
    selectedPlayerPromises.value.find(p => p.status !== 'pending') ?? null
);

const PROMISE_TYPES = [
    { type: 'playing_time', icon: '🤝', label: 'Temps de jeu',  hint: 'Il jouera 3 matchs dans les 5 prochaines semaines' },
    { type: 'starter',      icon: '⭐', label: 'Titularisation', hint: 'Il jouera 4 matchs dans les 5 prochaines semaines' },
    { type: 'renewal',      icon: '📜', label: 'Prolongation',   hint: 'Tu lui signeras un nouveau contrat sous 4 semaines' },
];

const promiseTypeLabel = (type) => PROMISE_TYPES.find(t => t.type === type)?.label ?? type;

// ── Déclarations publiques ───────────────────────────────────
const DECLARATION_COOLDOWN_WEEKS = 3;

const lastDeclaration = computed(() =>
    (props.playerDeclarations?.[props.selectedMyPlayer?.id] ?? [])[0] ?? null
);

const declarationOnCooldown = computed(() =>
    lastDeclaration.value
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
const ROLE_BY_ZONE = { 0: 'GK', 1: 'DF', 2: 'MF', 3: 'MF', 4: 'FW' };

const roleFromPosition = (pos) => {
    const p = String(pos || '').toLowerCase();
    if (p.includes('goal')) return 'GK';
    if (p.includes('def'))  return 'DF';
    if (p.includes('mid'))  return 'MF';
    if (p.includes('for') || p.includes('att')) return 'FW';
    return null;
};

// 'primary' | 'secondary' | 'off' | null
const slotMastery = (slot, slotDef) => {
    const player = props.playerForSlot(slot);
    if (!player) return null;
    const slotRole = ROLE_BY_ZONE[slotDef.zone] ?? null;
    const natural  = roleFromPosition(player.position);
    if (!slotRole || !natural) return null;
    if (slotRole === natural) return 'primary';
    const secondary = (player.secondary_positions ?? []).map(roleFromPosition);
    return secondary.includes(slotRole) ? 'secondary' : 'off';
};
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- LIGNE 1 : Formation + Terrain + Banc -->
        <div class="grid grid-cols-12 gap-4">
            <FormationCard class="col-span-4"
                           :formation="currentFormation"
                           :formationData="formationData"
                           @change="key => emit('save-formation', key)" />

            <PitchView class="col-span-6"
                       :formationData="formationData"
                       :playerPosition="playerPosition"
                       :playerForSlot="playerForSlot"
                       :selectedSlot="selectedSlot"
                       :isPickedUp="isPickedUp"
                       :players="rosterWithStatus"
                       :slotMastery="slotMastery"
                       @player-click="p => emit('player-click', p)"
                       @drag-start="(p, e) => emit('drag-start', p, e)"
                       @drag-over="e => emit('drag-over', e)"
                       @drop-on="(p, e) => emit('drop-on', p, e)" />

            <BenchCard class="col-span-2"
                       :substitutes="substitutes"
                       :selectedId="selectedMyPlayer?.id"
                       :isPickedUp="isPickedUp"
                       @player-click="p => emit('player-click', p)"
                       @drag-start="(p, e) => emit('drag-start', p, e)"
                       @drag-over="e => emit('drag-over', e)"
                       @drop-on="(p, e) => emit('drop-on', p, e)" />
        </div>

        <!-- LIGNE 2 : Infos club -->
        <div class="grid grid-cols-12 gap-4">
            <ClubIdentityCard class="col-span-5"
                              :team="team"
                              :rank="clubStanding?.position ?? null"
                              :total="standings.length"
                              :budget="teamBudget" />

            <TeamRecordCard class="col-span-3"
                            :wins="teamRecord.wins"
                            :draws="teamRecord.draws"
                            :losses="teamRecord.losses" />

            <!-- Stats + Effectif -->
            <div class="col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Club</h4>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                    <div class="flex justify-between"><span>Attaque moy.</span><span class="font-bold text-slate-800">{{ averageAttack }}</span></div>
                    <div class="flex justify-between"><span>Blessés</span>
                        <span class="font-bold" :class="injuriesCount > 0 ? 'text-rose-500' : 'text-slate-800'">{{ injuriesCount }}</span>
                    </div>
                    <div class="flex justify-between"><span>Défense moy.</span><span class="font-bold text-slate-800">{{ averageDefense }}</span></div>
                    <div class="flex justify-between"><span>Suspensions</span>
                        <span class="font-bold" :class="suspensionsCount > 0 ? 'text-amber-500' : 'text-slate-800'">{{ suspensionsCount }}</span>
                    </div>
                    <div class="flex justify-between"><span>Stamina moy.</span><span class="font-bold text-slate-800">{{ averageStamina }}</span></div>
                    <div class="flex justify-between"><span>Moral moy.</span>
                        <span class="font-bold" :class="moraleState(averageMorale).text">{{ moraleState(averageMorale).emoji }} {{ averageMorale }}</span>
                    </div>
                    <div class="flex justify-between"><span>Cartons</span>
                        <span class="font-bold" :class="cardsCount > 0 ? 'text-yellow-500' : 'text-slate-800'">{{ cardsCount }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIGNE 3 : Liste joueurs + Profil -->
        <div class="grid grid-cols-12 gap-4">

            <!-- Liste joueurs -->
            <RosterList class="col-span-3 max-h-[630px]"
                        :players="rosterWithStatus"
                        :selectedId="selectedMyPlayer?.id"
                        :isPlayerInjured="isPlayerInjured"
                        :isPlayerSuspended="isPlayerSuspended"
                        :playerYellowCards="playerYellowCards"
                        :playerInjury="playerInjury"
                        :playerSuspension="playerSuspension"
                        @select="p => emit('select-player', p)" />

            <!-- Profil joueur -->
            <div class="col-span-9 flex flex-col gap-3">
                <template v-if="selectedMyPlayer">

                    <PlayerStatusAlert :player="selectedMyPlayer"
                                       :isPlayerInjured="isPlayerInjured"
                                       :isPlayerSuspended="isPlayerSuspended"
                                       :playerInjury="playerInjury"
                                       :playerSuspension="playerSuspension" />

                    <!-- Identité + actions -->
                    <PlayerIdentityCard :player="selectedMyPlayer">
                        <template #actions>
                            <!-- Toggle titulaire -->
                            <button v-if="selectedMyPlayer.contract_id" type="button"
                                    @click="emit('toggle-starter', selectedMyPlayer.contract_id)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                                    :class="selectedMyPlayer.is_starter
                                        ? 'bg-emerald-500 text-white border-emerald-600 hover:bg-emerald-600'
                                        : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50'">
                                {{ selectedMyPlayer.is_starter ? '✓ Titulaire' : '+ Titulariser' }}
                            </button>
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
                                    @change="emit('update-number', selectedMyPlayer.id, $event.target.value)"
                                    class="w-14 border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-teal-300 focus:outline-none"
                                />
                            </div>
                        </template>
                        <template #footer>
                            <p v-if="selectedMyPlayer.is_starter" class="mt-3 text-[10px] text-slate-400 italic">
                                💡 Glisse ce joueur sur un titulaire pour échanger les positions, ou sur un remplaçant pour le sortir.
                            </p>
                            <p v-else class="mt-3 text-[10px] text-emerald-600 italic">
                                💡 Glisse ce remplaçant sur un titulaire pour le faire entrer en jeu.
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
                                          : `Il doit jouer ${activePromise.target_matches} matchs entre la semaine ${activePromise.start_week} et la semaine ${activePromise.due_week}`">
                                    🤝 Promesse en cours — {{ promiseTypeLabel(activePromise.type) }}
                                    <template v-if="activePromise.type !== 'renewal'">: {{ activePromise.target_matches }} matchs</template>
                                    avant S{{ activePromise.due_week }}
                                </span>
                                <template v-else>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Promettre :</span>
                                    <button v-for="pt in PROMISE_TYPES" :key="pt.type" type="button"
                                            @click="emit('make-promise', selectedMyPlayer, pt.type)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold border border-sky-300 bg-white text-sky-600 hover:bg-sky-50 transition-all"
                                            :title="`${pt.hint}. Tenue : +15 relation, +5 moral. Rompue : −25 relation, −10 moral.`">
                                        {{ pt.icon }} {{ pt.label }}
                                    </button>
                                </template>
                                <span v-if="lastResolvedPromise"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                      :class="lastResolvedPromise.status === 'kept' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                    {{ lastResolvedPromise.status === 'kept' ? '✓ Promesse tenue' : '✗ Promesse rompue' }}
                                    <template v-if="lastResolvedPromise.type !== 'renewal'">
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
                    <div class="grid grid-cols-2 gap-3">
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
    </div>
</template>
