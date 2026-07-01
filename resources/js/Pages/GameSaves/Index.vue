<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';

const props = defineProps({
    gameSaves: { type: Array, required: true },
});

const play = (save) => router.get(route('game-saves.Play', save.id));

const destroySave = (save) => {
    if (!confirm('Voulez-vous vraiment supprimer cette sauvegarde ?')) return;
    router.delete(route('game-saves.destroy', save.id), { preserveScroll: true });
};

const periodLabel = (p) => ({ college: 'Collège', highschool: 'Lycée', pro: 'Professionnel' }[p] ?? p);

const formatDate = (value) => {
    if (!value) return '—';
    try { return new Date(value).toLocaleString('fr-FR'); } catch { return value; }
};
</script>

<template>
    <Head title="Mes parties" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Mes parties sauvegardées</H2>
        </template>

        <div class="p-2 sm:p-4">
            <div class="flex flex-col md:flex-row">
                <!-- Visuel gauche -->
                <div class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                     style="background-image: url('/images/Jun_Misugi.webp')"></div>

                <!-- Carte -->
                <div class="basis-full md:basis-2/3 p-4 sm:p-6 border border-slate-200 rounded-2xl mx-0 md:mx-6 bg-white min-h-[500px] flex flex-col shadow-sm">

                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                        <div>
                            <h1 class="text-xl font-bold text-slate-800">Charger une partie</h1>
                            <p class="text-xs text-slate-400 mt-0.5">Sélectionne une sauvegarde ou crée une nouvelle partie</p>
                        </div>
                        <Link :href="route('game-saves.create')"
                              class="flex items-center justify-center gap-2 px-4 py-2 bg-teal-500 hover:bg-teal-400 text-white text-sm font-semibold rounded-xl transition-all">
                            ⚽ Nouvelle partie
                        </Link>
                    </div>

                    <!-- Aucune sauvegarde -->
                    <div v-if="gameSaves.length === 0"
                         class="flex-1 flex flex-col items-center justify-center gap-4 text-slate-400">
                        <div class="text-5xl">💾</div>
                        <p class="text-sm">Tu n'as pas encore de partie sauvegardée.</p>
                        <Link :href="route('game-saves.create')"
                              class="px-6 py-2 bg-teal-500 hover:bg-teal-400 text-white text-sm font-semibold rounded-xl transition-all">
                            Créer une partie
                        </Link>
                    </div>

                    <!-- Liste -->
                    <div v-else class="flex-1 flex flex-col gap-3 overflow-y-auto">
                        <div v-for="save in gameSaves" :key="save.id"
                             class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 hover:border-teal-300 hover:bg-teal-50 transition-all">

                            <!-- Infos -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-slate-800">
                                    {{ save.label ?? `Sauvegarde #${save.id}` }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ periodLabel(save.period) }}
                                    · Saison {{ save.season }} · Semaine {{ save.week }}
                                    · <span class="text-slate-300">{{ formatDate(save.updated_at) }}</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" @click="play(save)"
                                        class="px-4 py-1.5 bg-teal-500 hover:bg-teal-400 text-white text-xs font-semibold rounded-lg transition-all">
                                    ▶ Jouer
                                </button>
                                <button type="button" @click="destroySave(save)"
                                        class="px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-500 hover:text-rose-600 text-xs font-semibold rounded-lg border border-rose-200 hover:border-rose-300 transition-all">
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end">
                        <Link :href="route('mainMenu')"
                              class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-500 text-sm font-semibold rounded-xl border border-slate-200 transition-all">
                            ← Retour
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
