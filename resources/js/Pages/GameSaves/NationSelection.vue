<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    label: { type: String, default: null },
    period: { type: String, default: 'college' },
    competitionType: { type: String, default: 'world_cup' },
    nations: { type: Array, default: () => [] },
    squad: { type: Object, default: null },
    gameConfig: { type: Object, default: null },
});

const form = useForm({
    label: props.label || '',
    period: props.period,
    competition_type: props.competitionType,
    nation: props.squad?.name ?? null,
    player_ids: [...(props.squad?.selectedIds ?? [])],
    game_config: props.gameConfig,
});

const playableCount = computed(() => props.nations.filter(n => n.playable).length);

function pick(nation) {
    if (!nation.playable) return;
    form.nation = nation.name;
}

const selected = computed(() => new Set(form.player_ids));
const hasKeeper = computed(() => props.squad?.players.some(player =>
    player.position === 'Goalkeeper' && selected.value.has(player.id)));
const canLaunch = computed(() => props.squad && hasKeeper.value &&
    selected.value.size >= props.squad.minPlayers && selected.value.size <= props.squad.maxPlayers);
const squadError = computed(() => form.errors.player_ids ??
    Object.entries(form.errors).find(([key]) => key.startsWith('player_ids.'))?.[1]);
const positions = {
    Goalkeeper: 'Gardien',
    Defender: 'Défenseur',
    Midfielder: 'Milieu',
    Forward: 'Attaquant',
};

watch(() => props.squad?.name, () => {
    form.nation = props.squad?.name ?? null;
    form.player_ids = [...(props.squad?.selectedIds ?? [])];
    form.clearErrors();
});

function togglePlayer(id) {
    if (selected.value.has(id)) {
        form.player_ids = form.player_ids.filter(playerId => playerId !== id);
    } else if (selected.value.size < props.squad.maxPlayers) {
        form.player_ids.push(id);
    }
}

function launch() {
    if (!canLaunch.value || form.processing) return;
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

                <!-- Header -->
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

                <!-- Grille des nations -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <button v-for="nation in nations" :key="nation.name"
                            type="button"
                            @click="pick(nation)"
                            :disabled="!nation.playable"
                            class="relative flex flex-col items-center gap-1 p-4 rounded-xl border-2 transition-all"
                            :class="[
                                !nation.playable
                                    ? 'border-slate-300 bg-slate-50 opacity-60 cursor-not-allowed'
                                    : (form.nation === nation.name
                                        ? 'border-indigo-500 bg-indigo-50 shadow-sm'
                                        : 'border-slate-300 bg-white hover:border-indigo-300'),
                            ]">
                        <span class="text-3xl leading-none">{{ nation.flag }}</span>
                        <span class="text-sm font-bold text-slate-700 text-center">{{ nation.name }}</span>
                        <span class="text-[11px]"
                              :class="nation.playable ? 'text-slate-400' : 'text-rose-400'">
                            {{ nation.total }} joueur{{ nation.total > 1 ? 's' : '' }}
                            <template v-if="!nation.playable"> · incomplet</template>
                        </span>
                        <div v-if="form.nation === nation.name"
                             class="absolute top-2 right-2 w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center">
                            <span class="text-white text-2xs font-bold">✓</span>
                        </div>
                    </button>
                </div>

                <p v-if="form.errors.nation" class="mt-4 text-xs text-rose-500">{{ form.errors.nation }}</p>
                <p v-if="form.errors.game_save" class="mt-4 text-xs text-rose-500">{{ form.errors.game_save }}</p>

                <div class="mt-6 pt-4 border-t border-slate-200 flex justify-end">
                    <Link v-if="form.nation"
                          :href="route('game-saves.setup', { nation: form.nation })"
                          class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Convoquer les joueurs de {{ form.nation }}
                    </Link>
                    <ButtonPrimary v-else type="button" tone="indigo" disabled>Sélectionne une nation</ButtonPrimary>
                </div>
            </div>

            <form v-else class="border border-slate-200 rounded-2xl bg-white p-4 sm:p-6 shadow-card"
                  @submit.prevent="launch">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-700">{{ squad.flag }} {{ squad.name }}</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ squad.description }}</p>
                        <p class="mt-2 text-sm font-semibold text-indigo-700">
                            Formation recommandée pour la présélection initiale · {{ squad.formation }}
                        </p>
                    </div>
                    <Link :href="route('game-saves.setup')"
                          class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        ← Choix du pays
                    </Link>
                </div>

                <fieldset class="mt-6">
                    <legend class="text-sm font-semibold text-slate-700">Joueurs à convoquer</legend>
                    <p class="mb-3 text-sm text-slate-600">
                        Sélectionne de {{ squad.minPlayers }} à {{ squad.maxPlayers }} joueurs, dont au moins un gardien.
                    </p>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        <label v-for="player in squad.players" :key="player.id"
                               class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2"
                               :class="{ 'border-indigo-300 bg-indigo-50': selected.has(player.id) }">
                            <input type="checkbox" class="rounded border-slate-300 text-indigo-600"
                                   :checked="selected.has(player.id)"
                                   :disabled="form.processing || (!selected.has(player.id) && selected.size >= squad.maxPlayers)"
                                   @change="togglePlayer(player.id)">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-700">{{ player.name }}</span>
                                <span class="block text-xs text-slate-600">{{ positions[player.position] ?? player.position }}</span>
                            </span>
                            <span class="text-sm tabular-nums text-slate-600" title="Note générale">{{ player.overall }}</span>
                        </label>
                    </div>
                </fieldset>

                <div class="sticky bottom-0 mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 bg-white py-4">
                    <div aria-live="polite">
                        <p class="text-sm font-semibold text-slate-700">{{ selected.size }} / {{ squad.maxPlayers }} joueurs convoqués</p>
                        <p v-if="!hasKeeper" class="text-sm text-rose-700">Sélectionne au moins un gardien.</p>
                        <p v-for="error in [squadError, form.errors.nation, form.errors.game_save].filter(Boolean)"
                           :key="error" class="text-sm text-rose-700">{{ error }}</p>
                    </div>
                    <ButtonPrimary type="submit" size="lg" tone="indigo" :disabled="!canLaunch || form.processing">
                        {{ form.processing ? 'Création…' : 'Lancer la Coupe du Monde' }}
                    </ButtonPrimary>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
