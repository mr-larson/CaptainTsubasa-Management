<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';

const props = defineProps({
    packages: { type: Array, required: true },
});

const toggleShared = (pkg) => {
    router.put(route('period-packages.update', pkg.id), { is_shared: !pkg.is_shared }, { preserveScroll: true });
};

const destroyPackage = (pkg) => {
    if (!confirm(`Supprimer la période « ${pkg.name} » ?`)) return;
    router.delete(route('period-packages.destroy', pkg.id), { preserveScroll: true });
};

const formatDate = (value) => {
    if (!value) return '—';
    try { return new Date(value).toLocaleDateString('fr-FR'); } catch { return value; }
};
</script>

<template>
    <Head title="Mes périodes" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Mes périodes</H2>
        </template>

        <div class="p-2 sm:p-4">
            <div class="flex flex-col md:flex-row">
                <div class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                     style="background-image: url('/images/Jun_Misugi.webp')"></div>

                <div class="basis-full md:basis-2/3 p-4 sm:p-6 border border-slate-200 rounded-2xl mx-0 md:mx-6 bg-white min-h-[500px] flex flex-col shadow-sm">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                        <div>
                            <h1 class="text-xl font-bold text-slate-800">Bibliothèque de périodes</h1>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Importe un fichier de période (effectifs, équipes, contrats) pour le choisir à la création d'une partie.
                            </p>
                        </div>
                        <Link :href="route('period-packages.create')"
                              class="flex items-center justify-center gap-2 px-4 py-2 bg-teal-500 hover:bg-teal-400 text-white text-sm font-semibold rounded-xl transition-all">
                            📦 Importer une période
                        </Link>
                    </div>

                    <div v-if="packages.length === 0"
                         class="flex-1 flex flex-col items-center justify-center gap-4 text-slate-400">
                        <div class="text-5xl">📦</div>
                        <p class="text-sm">Aucune période importée pour le moment.</p>
                        <Link :href="route('period-packages.create')"
                              class="px-6 py-2 bg-teal-500 hover:bg-teal-400 text-white text-sm font-semibold rounded-xl transition-all">
                            Importer une période
                        </Link>
                    </div>

                    <div v-else class="flex-1 flex flex-col gap-3 overflow-y-auto">
                        <div v-for="pkg in packages" :key="pkg.id"
                             class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 hover:border-teal-300 hover:bg-teal-50 transition-all">

                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                    {{ pkg.name }}
                                    <span v-if="pkg.is_shared" class="px-2 py-0.5 rounded-full bg-teal-100 text-teal-700 text-[10px] font-bold uppercase tracking-wide">Partagée</span>
                                    <span v-else class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-bold uppercase tracking-wide">Privée</span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ pkg.description || 'Sans description' }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ pkg.team_count }} équipe(s) · {{ pkg.player_count }} joueur(s)
                                    · <span class="text-slate-300">{{ formatDate(pkg.created_at) }}</span>
                                </div>
                            </div>

                            <div v-if="pkg.user_id === $page.props.auth.user.id" class="flex items-center gap-2 shrink-0">
                                <button type="button" @click="toggleShared(pkg)"
                                        class="px-3 py-1.5 bg-white hover:bg-teal-50 text-teal-600 text-xs font-semibold rounded-lg border border-teal-200 hover:border-teal-300 transition-all">
                                    {{ pkg.is_shared ? 'Rendre privée' : 'Partager' }}
                                </button>
                                <button type="button" @click="destroyPackage(pkg)"
                                        class="px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-500 hover:text-rose-600 text-xs font-semibold rounded-lg border border-rose-200 hover:border-rose-300 transition-all">
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end">
                        <Link :href="route('game-saves.index')"
                              class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-500 text-sm font-semibold rounded-xl border border-slate-200 transition-all">
                            ← Retour
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
