<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, default: null },
    period: { type: String, default: 'college' },
    competitionType: { type: String, default: 'world_cup' },
    nations: { type: Array, default: () => [] },
});

const form = useForm({
    label: props.label || '',
    period: props.period,
    competition_type: props.competitionType,
    nation: null,
});

const playableCount = computed(() => props.nations.filter(n => n.playable).length);

function pick(nation) {
    if (!nation.playable) return;
    form.nation = nation.name;
}

function launch() {
    if (!form.nation || form.processing) return;
    form.post(route('game-saves.start-world-cup'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Choisir ta sélection" />

    <AuthenticatedLayout>
        <template #header>
            <H2>🌍 Coupe du Monde — Choisis ta sélection</H2>
        </template>

        <div class="p-4">
            <div class="border border-slate-200 rounded-2xl bg-white shadow-sm p-6">

                <!-- Header -->
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">Tournoi mondial</div>
                        <h1 class="text-xl font-bold text-slate-800">{{ label || 'Nouvelle Coupe du Monde' }}</h1>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ playableCount }} sélection(s) prête(s) à concourir. Les nations grisées n'ont pas encore
                            assez de joueurs pour aligner un onze.
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
                                    ? 'border-slate-100 bg-slate-50 opacity-60 cursor-not-allowed'
                                    : (form.nation === nation.name
                                        ? 'border-indigo-500 bg-indigo-50 shadow-sm'
                                        : 'border-slate-200 bg-white hover:border-indigo-300'),
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
                            <span class="text-white text-[10px] font-bold">✓</span>
                        </div>
                    </button>
                </div>

                <p v-if="form.errors.nation" class="mt-4 text-xs text-rose-500">{{ form.errors.nation }}</p>

                <!-- Pied : lancement -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-4">
                    <p class="text-xs text-slate-400">
                        Ton effectif national est constitué automatiquement à partir des meilleurs joueurs de la nation.
                    </p>
                    <button type="button"
                            @click="launch"
                            :disabled="!form.nation || form.processing"
                            class="shrink-0 flex items-center gap-2 px-6 py-2.5 bg-indigo-500 hover:bg-indigo-400 text-white text-sm font-bold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]">
                        <span v-if="form.processing">Création...</span>
                        <span v-else-if="form.nation">🏆 Lancer avec {{ form.nation }}</span>
                        <span v-else>Sélectionne une nation</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
