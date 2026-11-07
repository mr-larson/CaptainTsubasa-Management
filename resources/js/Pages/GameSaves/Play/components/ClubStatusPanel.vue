<script setup>
import { computed } from 'vue';
import { positionLabel } from '@/Enums/positionBadges.js';
import { usePlayerUtils } from '../usePlayerUtils.js';
import { LOW_STAMINA } from '../usePreMatchChecklist.js';

// Statut du club (onglet Gestion, page par défaut) : récap de tous les états de
// l'équipe. Un clic sur un joueur ouvre sa fiche dans « Mon équipe ».
const props = defineProps({
    roster:            { type: Array,    default: () => [] },
    clubStanding:      { type: Object,   default: null },
    standingsCount:    { type: Number,   default: 0 },
    teamBudget:        { type: Number,   default: 0 },
    teamRecord:        { type: Object,   default: () => ({ wins: 0, draws: 0, losses: 0 }) },
    week:              { type: Number,   default: 1 },
    playerInjury:      { type: Function, default: () => null },
    playerSuspension:  { type: Function, default: () => null },
    playerYellowCards: { type: Function, default: () => 0 },
    suspensionOn3Yellows: { type: Boolean, default: true },
    cardsInHand:       { type: Array, default: null },
    activeEffects:     { type: Array, default: () => [] },
    playerPromises:    { type: Object, default: null },
    trainingEntries:   { type: Array, default: () => [] },
    positionTrainings: { type: Array, default: () => [] },
});

const emit = defineEmits(['open-player']);
const { moraleState, statLabel } = usePlayerUtils();

const LOW_MORALE = 40;
const CONTRACT_HORIZON = 4;

const byName = (a, b) => (a.lastname ?? '').localeCompare(b.lastname ?? '');

const injured = computed(() => props.roster
    .map(p => ({ p, inj: props.playerInjury(p.id) }))
    .filter(x => x.inj)
    .sort((a, b) => (a.inj.week_return ?? 0) - (b.inj.week_return ?? 0)));

const suspended = computed(() => props.roster
    .map(p => ({ p, sus: props.playerSuspension(p.id) }))
    .filter(x => x.sus)
    .sort((a, b) => (a.sus.week_return ?? 0) - (b.sus.week_return ?? 0)));

const booked = computed(() => props.roster
    .map(p => ({ p, n: Number(props.playerYellowCards(p.id)) || 0 }))
    .filter(x => x.n > 0)
    .sort((a, b) => b.n - a.n || byName(a.p, b.p)));

const contracts = computed(() => props.roster
    .filter(p => p.contract_end_week != null && Number(p.contract_end_week) <= props.week + CONTRACT_HORIZON)
    .sort((a, b) => Number(a.contract_end_week) - Number(b.contract_end_week)));

const tired = computed(() => props.roster
    .filter(p => Number(p.stamina ?? p.stats?.stamina ?? 100) < LOW_STAMINA)
    .sort((a, b) => Number(a.stamina ?? 0) - Number(b.stamina ?? 0)));

const unhappy = computed(() => props.roster
    .filter(p => Number(p.morale ?? 60) <= LOW_MORALE)
    .sort((a, b) => Number(a.morale ?? 60) - Number(b.morale ?? 60)));

const available = computed(() => props.roster.filter(p => !props.playerInjury(p.id) && !props.playerSuspension(p.id)).length);

const average = (key, fallback) => props.roster.length
    ? Math.round(props.roster.reduce((s, p) => s + Number(p[key] ?? p.stats?.[key] ?? fallback), 0) / props.roster.length)
    : 0;
const averageMorale = computed(() => average('morale', 60));
const averageStamina = computed(() => average('stamina', 0));

const promises = computed(() => props.roster.flatMap(player =>
    (props.playerPromises?.[player.id] ?? [])
        .filter(promise => promise.status === 'pending')
        .map(promise => ({ player, promise }))
).sort((a, b) => a.promise.due_week - b.promise.due_week));

const trainings = computed(() => {
    const roster = new Map(props.roster.map(player => [Number(player.id), player]));
    return [
        ...props.positionTrainings.map(entry => ({
            player: roster.get(Number(entry.game_player_id)),
            type: 'position',
            detail: `Formation · ${positionLabel(entry.target_position)}`,
            badge: `Retour S${entry.week_return}`,
        })),
        ...props.trainingEntries.filter(entry => entry.stat !== 'position').map(entry => ({
            player: roster.get(Number(entry.player_id)),
            type: 'stat',
            detail: `Séance effectuée · ${statLabel(entry.stat)}`,
            badge: `+${entry.gain ?? 0}`,
        })),
    ].filter(entry => entry.player);
});

function promiseText(promise) {
    switch (promise.type) {
        case 'starter': return `Titularisations · ${promise.played_matches ?? 0}/${promise.target_matches ?? 4}`;
        case 'playing_time': return `Temps de jeu · ${promise.target_turns ?? 15} tours`;
        case 'renewal': return 'Prolongation de contrat';
        case 'rest': return 'Repos · aucun match ni entraînement';
        default: return 'Promesse en cours';
    }
}

const KPI = 'border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-0.5';
const KPI_LABEL = 'text-2xs font-bold text-slate-400 uppercase tracking-wider';
const SECTION = 'border border-slate-200 rounded-xl bg-white p-3 flex flex-col gap-2';
const ROW = 'w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg border border-slate-100 text-left text-xs hover:border-teal-300 hover:bg-teal-50 transition-colors';
const PILL = 'shrink-0 text-2xs font-bold px-2 py-0.5 rounded-full';
const EMPTY = 'text-xs text-emerald-600';
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Indicateurs -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div :class="KPI">
                <span class="text-2xl font-black text-slate-700">
                    {{ clubStanding?.position ?? '—' }}<sup v-if="clubStanding" class="text-sm">e</sup>
                </span>
                <span :class="KPI_LABEL">Classement / {{ standingsCount }}</span>
            </div>
            <div :class="KPI">
                <span class="text-2xl font-black text-slate-700">
                    <span class="text-emerald-600">{{ teamRecord.wins }}</span>-<span class="text-slate-500">{{ teamRecord.draws }}</span>-<span class="text-rose-500">{{ teamRecord.losses }}</span>
                </span>
                <span :class="KPI_LABEL">Bilan V-N-D</span>
            </div>
            <div :class="KPI">
                <span class="text-2xl font-black text-teal-600">{{ teamBudget.toLocaleString('fr-FR') }} GL</span>
                <span :class="KPI_LABEL">Budget</span>
            </div>
            <div :class="KPI">
                <span class="text-2xl font-black" :class="available < roster.length ? 'text-amber-600' : 'text-slate-700'">
                    {{ available }}/{{ roster.length }}
                </span>
                <span :class="KPI_LABEL">Disponibles</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Infirmerie -->
            <section :class="SECTION">
                <h4 class="text-sm font-semibold text-slate-700">🤕 Infirmerie <span class="text-slate-400 font-normal">({{ injured.length }})</span></h4>
                <button v-for="{ p, inj } in injured" :key="'i' + p.id" type="button" :class="ROW" @click="emit('open-player', p)">
                    <span class="truncate"><span class="font-semibold text-slate-700">{{ p.lastname }}</span>
                        <span class="text-slate-400"> — {{ inj.description ?? 'Blessure' }}</span></span>
                    <span :class="[PILL, 'bg-rose-100 text-rose-700']">Retour S{{ inj.week_return ?? '?' }}</span>
                </button>
                <p v-if="!injured.length" :class="EMPTY">✓ Aucun blessé</p>
            </section>

            <!-- Discipline -->
            <section :class="SECTION">
                <h4 class="text-sm font-semibold text-slate-700">🟥 Discipline <span class="text-slate-400 font-normal">({{ suspended.length + booked.length }})</span></h4>
                <button v-for="{ p, sus } in suspended" :key="'s' + p.id" type="button" :class="ROW" @click="emit('open-player', p)">
                    <span class="truncate"><span class="font-semibold text-slate-700">{{ p.lastname }}</span>
                        <span class="text-slate-400"> — suspendu</span></span>
                    <span :class="[PILL, 'bg-rose-100 text-rose-700']">Retour S{{ sus.week_return ?? '?' }}</span>
                </button>
                <button v-for="{ p, n } in booked" :key="'y' + p.id" type="button" :class="ROW" @click="emit('open-player', p)">
                    <span class="truncate"><span class="font-semibold text-slate-700">{{ p.lastname }}</span>
                        <span v-if="suspensionOn3Yellows && n === 2" class="text-amber-600"> — à 1 jaune de la suspension</span></span>
                    <span :class="[PILL, 'bg-amber-100 text-amber-700']">{{ '🟨'.repeat(Math.min(n, 3)) }} {{ n }}</span>
                </button>
                <p v-if="!suspended.length && !booked.length" :class="EMPTY">✓ Aucun carton ni suspension</p>
            </section>

            <!-- Contrats -->
            <section :class="SECTION">
                <h4 class="text-sm font-semibold text-slate-700">📄 Contrats à échéance <span class="text-slate-400 font-normal">(≤ S{{ week + CONTRACT_HORIZON }})</span></h4>
                <button v-for="p in contracts" :key="'c' + p.id" type="button" :class="ROW" @click="emit('open-player', p)">
                    <span class="truncate font-semibold text-slate-700">{{ p.firstname }} {{ p.lastname }}</span>
                    <span :class="[PILL, Number(p.contract_end_week) <= week + 1 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700']">
                        Fin S{{ p.contract_end_week }}
                    </span>
                </button>
                <p v-if="!contracts.length" :class="EMPTY">✓ Aucun contrat ne se termine dans les {{ CONTRACT_HORIZON }} semaines</p>
            </section>

            <!-- Forme et moral -->
            <section :class="SECTION">
                <h4 class="text-sm font-semibold text-slate-700">
                    💪 Forme et moral
                    <span class="text-slate-400 font-normal">· stamina moy. {{ averageStamina }} · moral moy.
                        <span :class="moraleState(averageMorale).text">{{ moraleState(averageMorale).emoji }} {{ averageMorale }}</span></span>
                </h4>
                <button v-for="p in tired" :key="'t' + p.id" type="button" :class="ROW" @click="emit('open-player', p)">
                    <span class="truncate"><span class="font-semibold text-slate-700">{{ p.lastname }}</span>
                        <span class="text-slate-400"> — épuisé</span></span>
                    <span :class="[PILL, 'bg-orange-100 text-orange-700']">🔋 {{ p.stamina ?? p.stats?.stamina ?? 0 }}</span>
                </button>
                <button v-for="p in unhappy" :key="'m' + p.id" type="button" :class="ROW" @click="emit('open-player', p)">
                    <span class="truncate"><span class="font-semibold text-slate-700">{{ p.lastname }}</span>
                        <span class="text-slate-400"> — {{ moraleState(p.morale).label.toLowerCase() }}</span></span>
                    <span :class="[PILL, moraleState(p.morale).chip]">{{ moraleState(p.morale).emoji }} {{ p.morale ?? 60 }}</span>
                </button>
                <p v-if="!tired.length && !unhappy.length" :class="EMPTY">✓ Personne d'épuisé ni de mécontent</p>
            </section>
            <section v-if="cardsInHand !== null" :class="SECTION">
                <h4 class="text-sm font-semibold text-slate-700">🎒 Cartes en main <span class="text-slate-400 font-normal">({{ cardsInHand.length }})</span></h4>
                <div v-for="card in cardsInHand" :key="card.id"
                     class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-2.5 py-1.5 text-xs">
                    <span class="font-semibold text-slate-700" :title="card.description">{{ card.icon }} {{ card.name }}</span>
                    <span v-if="card.kind === 'malus'" :class="[PILL, 'bg-rose-100 text-rose-700']">Malus</span>
                </div>
                <p v-if="!cardsInHand.length" :class="EMPTY">Aucune carte en main</p>
            </section>

            <section v-if="cardsInHand !== null" :class="SECTION">
                <h4 class="text-sm font-semibold text-slate-700">⚡ Bonus et malus actifs <span class="text-slate-400 font-normal">({{ activeEffects.length }})</span></h4>
                <div v-for="(effect, index) in activeEffects" :key="index"
                     class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-2.5 py-1.5 text-xs">
                    <span>
                        <span class="font-semibold text-slate-700">{{ effect.icon }} {{ effect.name }}</span>
                        <span class="text-slate-500"> · {{ effect.detail }}</span>
                    </span>
                    <span :class="[PILL, effect.type === 'Malus' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700']">{{ effect.type }}</span>
                </div>
                <p v-if="!activeEffects.length" :class="EMPTY">Aucun effet actif</p>
            </section>

            <section v-if="playerPromises !== null" :class="SECTION">
                <h4 class="text-sm font-semibold text-slate-700">🤝 Promesses en cours <span class="text-slate-400 font-normal">({{ promises.length }})</span></h4>
                <button v-for="{ player, promise } in promises" :key="promise.id" type="button" :class="ROW" @click="emit('open-player', player)">
                    <span>
                        <span class="font-semibold text-slate-700">{{ player.firstname }} {{ player.lastname }}</span>
                        <span class="text-slate-500"> · {{ promiseText(promise) }}</span>
                    </span>
                    <span :class="[PILL, 'bg-sky-100 text-sky-700']">Échéance S{{ promise.due_week }}</span>
                </button>
                <p v-if="!promises.length" :class="EMPTY">Aucune promesse en cours</p>
            </section>

            <section v-if="playerPromises !== null" :class="SECTION">
                <h4 class="text-sm font-semibold text-slate-700">🎓 Entraînements en cours <span class="text-slate-400 font-normal">({{ trainings.length }})</span></h4>
                <button v-for="entry in trainings" :key="`${entry.type}-${entry.player.id}`" type="button"
                        :class="ROW" @click="emit('open-player', entry.player)">
                    <span>
                        <span class="font-semibold text-slate-700">{{ entry.player.firstname }} {{ entry.player.lastname }}</span>
                        <span class="text-slate-500"> · {{ entry.detail }}</span>
                    </span>
                    <span :class="[PILL, entry.type === 'position' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700']">{{ entry.badge }}</span>
                </button>
                <p v-if="!trainings.length" :class="EMPTY">Aucune formation en cours ni séance cette semaine</p>
            </section>
        </div>
    </div>
</template>
