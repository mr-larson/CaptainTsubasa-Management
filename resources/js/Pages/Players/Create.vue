<!-- resources/js/Pages/Players/Create.vue -->
<template>
    <Head title="Ajouter un joueur" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Ajouter un joueur</H2>
        </template>

        <div class="p-4">
            <!-- Titre -->
            <div class="flex justify-center mb-6">
                <h1 class="text-3xl font-bold text-slate-600">
                    Création d'un joueur
                </h1>
            </div>

            <div class="flex flex-col md:flex-row">
                <!-- Visuel gauche -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat bg-[url('/images/Mamoru\_Izawa\_(Shutetsu\_ES-SR-Tq)\_Full.webp')]"
                ></div>

                <!-- Carte formulaire -->
                <div class="basis-full md:basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-lg mx-0 md:mx-6 bg-white">
                    <form @submit.prevent="submit">
                        <PlayerFormFields
                            :form="form"
                            stats-key="stats"
                            :positions="positions"
                            :position-labels="positionLabels"
                            position-placeholder="-- Sélectionne une position --"
                            show-age
                            cost-label="Coût par match"
                        />

                        <!-- Boutons -->
                        <div class="flex justify-around p-6">
                            <button
                                type="submit"
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Création
                            </button>

                            <Link
                                :href="route('players.index')"
                                class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-2 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2"
                            >
                                Retour
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// Components
import H2 from '@/Components/H2.vue';
import PlayerFormFields from '@/Components/PlayerFormFields.vue';
import { defaultPlayerStats } from '@/Enums/playerStats.js';

const props = defineProps({
    positions: { type: Array, required: true },
    positionLabels: { type: Object, required: true },
});

const form = useForm({
    firstname: '',
    lastname: '',
    age: '',
    position: '',
    secondary_positions: [],
    cost: '',
    stats: defaultPlayerStats({ heading: 15 }), // défaut du modèle Player
    description: '',
    photo: null,
});

function submit() {
    form.post(route('players.store'), {
        preserveScroll: true,
        forceFormData: true, // ✅ important
        onSuccess: () => form.reset(), // photo remise à null : l'aperçu s'efface
    });
}
</script>
