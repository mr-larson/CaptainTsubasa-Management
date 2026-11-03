<!-- resources/js/Pages/Players/Create.vue -->
<template>
    <Head title="Ajouter un joueur" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Ajouter un joueur</H2>
        </template>

        <div class="p-4">

            <div class="flex flex-col md:flex-row">
                <!-- Visuel gauche -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat bg-[url('/images/Mamoru\_Izawa\_(Shutetsu\_ES-SR-Tq)\_Full.webp')]"
                ></div>

                <!-- Carte formulaire -->
                <div class="basis-full md:basis-2/3 min-h-[500px] rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 md:mx-6">
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
                                class="mb-2 inline-flex min-h-[44px] w-40 items-center justify-center rounded-full bg-teal-500 px-5 text-sm font-bold text-slate-900 shadow-sm transition-colors hover:bg-teal-400 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Créer
                            </button>

                            <Link
                                :href="route('players.index')"
                                class="mb-2 inline-flex min-h-[44px] w-40 items-center justify-center rounded-full border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50"
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
