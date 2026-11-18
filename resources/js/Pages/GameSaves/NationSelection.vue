<script setup>
// Choix de la sélection (et des co-managers hot-seat, adversaires imposés, taille du tournoi), puis
// convocation des joueurs de chaque manager. Domaine : Coupe du Monde.
// Rendu par GameSaveCreationController::setup ; poste vers GameSaveCreationController::startWorldCup.
import NationFlag from '@/Components/NationFlag.vue';
import SquadPicker, { isValidSquad } from '@/Components/WorldCup/SquadPicker.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    label: { type: String, default: null },
    period: { type: String, default: 'college' },
    competitionType: { type: String, default: 'world_cup' },
    nations: { type: Array, default: () => [] },
    squad: { type: Object, default: null },          // aperçu de la sélection du siège 1 (étape convocation)
    coSquads: { type: Array, default: () => [] },    // aperçus des co-managers, dans l'ordre des sièges
    gameConfig: { type: Object, default: null },
    tournamentSizes: { type: Array, default: () => [8] },
    nationsCount: { type: Number, default: 8 },
    maxHumans: { type: Number, default: 2 },
    coNations: { type: Array, default: () => [] },
    opponents: { type: Array, default: () => [] },
});

const form = useForm({
    label: props.label || '',
    period: props.period,
    competition_type: props.competitionType,
    nation: props.squad?.name ?? null,
    nations_count: props.nationsCount,
    co_nations: [...props.coNations],
    opponents: [...props.opponents],
    player_ids: [...(props.squad?.selectedIds ?? [])],
    co_player_ids: Object.fromEntries(props.coSquads.filter(Boolean).map(s => [s.name, [...s.selectedIds]])),
    game_config: props.gameConfig,
});

// ── Étape 1 : nations ─────────────────────────────────────────
// Format de chaque taille de tournoi, affiché à côté du bouton.
const SIZE_HINTS = { 8: '2 poules, demies', 16: '4 poules, quarts', 24: '6 poules, 8es (+ 4 meilleurs 3es)', 32: '8 poules, 8es' };

const playableCount = computed(() => props.nations.filter(n => n.playable).length);
const sizeAvailable = (size) => playableCount.value >= size;
const maxHumans = computed(() => Math.max(1, Math.min(4, Math.floor(form.nations_count / 4))));

/** Rôle assigné au prochain clic sur une nation : 'main' (ma sélection), 'co' (co-manager), 'opponent' (adversaire imposé). */
const mode = ref(props.coNations.length ? 'co' : 'main');
const humans = computed(() => 1 + form.co_nations.length);
const seatOf = (name) => {
    if (form.nation === name) return 1;
    const index = form.co_nations.indexOf(name);
    return index === -1 ? null : index + 2;
};

function pick(nation) {
    if (!nation.playable) return;
    const name = nation.name;
    const without = (list) => list.filter(n => n !== name);

    if (mode.value === 'main' || !form.nation) {
        form.nation = name;
        form.co_nations = without(form.co_nations);
        form.opponents = without(form.opponents);
        return;
    }
    if (name === form.nation) return;

    if (mode.value === 'co') {
        form.opponents = without(form.opponents);
        if (form.co_nations.includes(name)) form.co_nations = without(form.co_nations);
        else if (humans.value < maxHumans.value) form.co_nations.push(name);
    } else {
        form.co_nations = without(form.co_nations);
        if (form.opponents.includes(name)) form.opponents = without(form.opponents);
        else if (humans.value + form.opponents.length < form.nations_count) form.opponents.push(name);
    }
}

// Réduire la taille du tournoi retire les managers et adversaires en trop.
watch(() => form.nations_count, (size) => {
    form.co_nations = form.co_nations.slice(0, maxHumans.value - 1);
    form.opponents = form.opponents.slice(0, Math.max(0, size - humans.value));
});

const setupQuery = computed(() => ({
    nation: form.nation, nations_count: form.nations_count, co_nations: form.co_nations, opponents: form.opponents,
}));

// ── Étape 2 : convocation de chaque manager ───────────────────
const squads = computed(() => [props.squad, ...props.coSquads].filter(Boolean));
const active = ref(0);
const idsOf = (squad) => squad.name === props.squad?.name ? form.player_ids : (form.co_player_ids[squad.name] ?? []);
function setIds(squad, ids) {
    if (squad.name === props.squad?.name) form.player_ids = ids;
    else form.co_player_ids[squad.name] = ids;
}
const validSquads = computed(() => squads.value.map(s => isValidSquad(s, idsOf(s))));
const canLaunch = computed(() => squads.value.length > 0 && validSquads.value.every(Boolean));
const errors = computed(() => [
    form.errors.player_ids, form.errors.co_player_ids, form.errors.co_nations, form.errors.opponents,
    form.errors.nations_count, form.errors.nation, form.errors.game_save,
    ...Object.entries(form.errors).filter(([k]) => k.startsWith('player_ids.') || k.startsWith('co_player_ids.')).map(([, v]) => v),
].filter(Boolean));

watch(() => props.squad?.name, () => {
    form.nation = props.squad?.name ?? null;
    form.player_ids = [...(props.squad?.selectedIds ?? [])];
    form.clearErrors();
});

function launch() {
    if (!canLaunch.value) return;
    form.post(route('game-saves.start-world-cup'), { preserveScroll: true });
}
</script>

<template>
    <Head :title="squad ? 'Convoquer les joueurs' : 'Choisir ta sélection'" />

    <AuthenticatedLayout>
        <template #header>
            <H2>🌍 Coupe du Monde — {{ squad ? 'Convoque tes joueurs' : 'Choisis ta sélection' }}</H2>
        </template>

        <div class="p-2 sm:p-4">
            <div v-if="!squad" class="border border-slate-200 rounded-2xl bg-white p-4 sm:p-6 shadow-card">

                <div class="mb-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">Tournoi mondial</div>
                        <h1 class="text-xl font-bold text-slate-700">{{ label || 'Nouvelle Coupe du Monde' }}</h1>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ playableCount }} sélection(s) prête(s) à concourir. Les nations grisées n'ont pas encore
                            assez de joueurs pour aligner un onze avec un gardien.
                        </p>
                    </div>
                    <Link :href="route('game-saves.create')"
                          class="shrink-0 px-4 py-2 bg-white hover:bg-slate-50 text-slate-500 text-sm font-semibold rounded-xl border border-slate-200 transition-all">
                        ← Retour
                    </Link>
                </div>

                <!-- Taille du tournoi -->
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wide mr-1">Format</span>
                    <button v-for="size in tournamentSizes" :key="size" type="button"
                            :disabled="!sizeAvailable(size)" @click="form.nations_count = size"
                            class="px-3 py-1.5 rounded-lg border text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="form.nations_count === size
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                : 'border-slate-300 bg-white text-slate-600 hover:border-indigo-300'">
                        {{ size }} sélections
                        <span class="text-2xs font-normal text-slate-400">· {{ SIZE_HINTS[size] }}</span>
                    </button>
                </div>

                <!-- Rôle du prochain clic -->
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wide mr-1">Clic sur une nation</span>
                    <button v-for="m in [
                                { key: 'main', label: 'Ma sélection' },
                                { key: 'co', label: `Co-manager hot-seat (${humans}/${maxHumans})`, hidden: maxHumans < 2 },
                                { key: 'opponent', label: `Adversaire imposé (${form.opponents.length})` },
                            ].filter(x => !x.hidden)" :key="m.key" type="button" @click="mode = m.key"
                            class="px-3 py-1.5 rounded-lg border text-sm font-semibold transition-all"
                            :class="mode === m.key
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                : 'border-slate-300 bg-white text-slate-600 hover:border-indigo-300'">
                        {{ m.label }}
                    </button>
                    <span class="text-xs text-slate-400">Les autres places sont tirées au sort.</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <button v-for="nation in nations" :key="nation.name" type="button"
                            @click="pick(nation)" :disabled="!nation.playable"
                            class="relative flex flex-col items-center gap-1 p-4 rounded-xl border-2 transition-all"
                            :class="[
                                !nation.playable
                                    ? 'border-slate-300 bg-slate-50 opacity-60 cursor-not-allowed'
                                    : (seatOf(nation.name)
                                        ? 'border-indigo-500 bg-indigo-50 shadow-sm'
                                        : (form.opponents.includes(nation.name)
                                            ? 'border-amber-400 bg-amber-50'
                                            : 'border-slate-300 bg-white hover:border-indigo-300')),
                            ]">
                        <span class="text-3xl leading-none"><NationFlag :flag="nation.flag" :url="nation.flag_url" :name="nation.name" /></span>
                        <span class="text-sm font-bold text-slate-700 text-center">{{ nation.name }}</span>
                        <span class="text-[11px]" :class="nation.playable ? 'text-slate-400' : 'text-rose-400'">
                            {{ nation.total }} joueur{{ nation.total > 1 ? 's' : '' }}
                            <template v-if="!nation.playable"> · incomplet</template>
                        </span>
                        <div v-if="seatOf(nation.name)"
                             class="absolute top-2 right-2 px-1.5 h-5 rounded-full bg-indigo-500 flex items-center justify-center">
                            <span class="text-white text-2xs font-bold">{{ humans > 1 ? 'Siège ' + seatOf(nation.name) : '✓' }}</span>
                        </div>
                        <div v-else-if="form.opponents.includes(nation.name)"
                             class="absolute top-2 right-2 px-1.5 h-5 rounded-full bg-amber-500 flex items-center justify-center">
                            <span class="text-white text-2xs font-bold">Adversaire</span>
                        </div>
                    </button>
                </div>

                <p v-for="error in errors" :key="error" class="mt-4 text-xs text-rose-500">{{ error }}</p>

                <div class="mt-6 pt-4 border-t border-slate-200 flex justify-end">
                    <Link v-if="form.nation" :href="route('game-saves.setup', setupQuery)"
                          class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Convoquer les joueurs{{ humans > 1 ? ' (' + humans + ' managers)' : ' de ' + form.nation }}
                    </Link>
                    <ButtonPrimary v-else type="button" tone="indigo" disabled>Sélectionne une nation</ButtonPrimary>
                </div>
            </div>

            <form v-else class="border border-slate-200 rounded-2xl bg-white p-4 sm:p-6 shadow-card" @submit.prevent="launch">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <!-- Un onglet par manager humain -->
                    <div class="flex flex-wrap gap-2">
                        <button v-for="(s, i) in squads" :key="s.name" type="button" @click="active = i"
                                class="px-3 py-1.5 rounded-lg border text-sm font-semibold"
                                :class="active === i ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-300 bg-white text-slate-600'">
                            <span v-if="squads.length > 1">Siège {{ i + 1 }} · </span>{{ s.name }}
                            <span :class="validSquads[i] ? 'text-emerald-600' : 'text-rose-500'">{{ validSquads[i] ? '✓' : '!' }}</span>
                        </button>
                    </div>
                    <Link :href="route('game-saves.setup')"
                          class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        ← Choix du pays
                    </Link>
                </div>

                <SquadPicker v-if="squads[active]" :key="squads[active].name" class="mt-4" :squad="squads[active]"
                             :model-value="idsOf(squads[active])" :disabled="form.processing"
                             @update:model-value="setIds(squads[active], $event)" />

                <div class="sticky bottom-0 mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 bg-white py-4">
                    <div aria-live="polite">
                        <p class="text-sm font-semibold text-slate-700">
                            {{ idsOf(squads[active]).length }} / {{ squads[active].maxPlayers }} joueurs convoqués
                        </p>
                        <p v-if="!canLaunch" class="text-sm text-rose-700">
                            Chaque manager doit convoquer {{ squads[0].minPlayers }} à {{ squads[0].maxPlayers }} joueurs, dont un gardien.
                        </p>
                        <p v-for="error in errors" :key="error" class="text-sm text-rose-700">{{ error }}</p>
                    </div>
                    <ButtonPrimary type="submit" size="lg" tone="indigo" :disabled="!canLaunch || form.processing">
                        {{ form.processing ? 'Création…' : 'Lancer la Coupe du Monde' }}
                    </ButtonPrimary>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
