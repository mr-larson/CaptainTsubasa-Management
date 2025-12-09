<template>
    <Head title="Mes parties" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Mes parties sauvegardées</H2>
        </template>

        <div class="p-4">
            <!-- Titre -->
            <div class="flex justify-center mb-6">
                <h1 class="text-3xl font-bold text-slate-600">
                    Charger une partie
                </h1>
            </div>

            <div class="flex flex-row">
                <!-- Visuel gauche -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/wakabayashi.webp')"
                ></div>

                <!-- Carte liste de sauvegardes -->
                <div class="basis-2/3 p-4 border border-slate-300 rounded-lg mx-6 bg-white min-h-[500px] flex flex-col">
                    <div class="mb-4">
                        <p class="text-slate-600">
                            Sélectionne une partie à charger ou crée une nouvelle sauvegarde.
                        </p>
                    </div>

                    <!-- Aucune sauvegarde -->
                    <div
                        v-if="gameSaves.length === 0"
                        class="flex-1 flex flex-col items-center justify-center text-slate-500"
                    >
                        <p class="mb-4">
                            Tu n'as pas encore de partie sauvegardée.
                        </p>

                        <Link
                            :href="route('game-saves.create')"
                            class="w-60 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2"
                        >
                            Nouvelle partie
                        </Link>
                    </div>

                    <!-- Liste des sauvegardes -->
                    <div v-else class="flex-1 flex flex-col">
                        <div class="flex-1 overflow-y-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs uppercase text-slate-500 border-b">
                                <tr>
                                    <th class="py-2 pr-2">Nom</th>
                                    <th class="py-2 pr-2">Période</th>
                                    <th class="py-2 pr-2">Progression</th>
                                    <th class="py-2 pr-2">Dernière maj</th>
                                    <th class="py-2 pr-2 text-right">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    v-for="save in gameSaves"
                                    :key="save.id"
                                    class="border-b last:border-b-0"
                                >
                                    <td class="py-2 pr-2">
                                        {{ save.label ?? `Sauvegarde #${save.id}` }}
                                    </td>
                                    <td class="py-2 pr-2">
                                        {{ periodLabel(save.period) }}
                                    </td>
                                    <td class="py-2 pr-2">
                                        Saison {{ save.season }} – Semaine {{ save.week }}
                                    </td>
                                    <td class="py-2 pr-2 text-xs text-slate-500">
                                        {{ formatDate(save.updated_at) }}
                                    </td>
                                    <td class="py-2 pr-2 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                class="px-3 py-1 bg-cyan-300 hover:bg-cyan-400 text-cyan-800 font-semibold border-2 border-cyan-500 rounded-full text-xs"
                                                @click="play(save)"
                                            >
                                                Jouer
                                            </button>
                                            <button
                                                type="button"
                                                class="px-3 py-1 bg-rose-300 hover:bg-rose-400 text-rose-800 font-semibold border-2 border-rose-500 rounded-full text-xs"
                                                @click="destroySave(save)"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Boutons bas -->
                    <div class="flex justify-around mt-6">
                        <Link
                            :href="route('game-saves.create')"
                            class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2"
                        >
                            Nouvelle partie
                        </Link>

                        <Link
                            :href="route('mainMenu')"
                            class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-2 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2"
                        >
                            Retour
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';

const props = defineProps({
    gameSaves: {
        type: Array,
        required: true,
    },
});

const play = (save) => {
    router.get(route('game-saves.play', save.id));
};

const destroySave = (save) => {
    if (!confirm('Voulez-vous vraiment supprimer cette sauvegarde ?')) {
        return;
    }

    router.delete(route('game-saves.destroy', save.id), {
        preserveScroll: true,
    });
};

const periodLabel = (period) => {
    if (period === 'college') return 'Collège';
    if (period === 'highschool') return 'Lycée';
    if (period === 'pro') return 'Professionnel';
    return period;
};

const formatDate = (value) => {
    if (!value) return '-';
    try {
        return new Date(value).toLocaleString();
    } catch (e) {
        return value;
    }
};
</script>
