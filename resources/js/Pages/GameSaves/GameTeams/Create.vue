<template>
    <Head :title="`Ajouter une équipe (partie #${gameSave.id})`" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Ajouter une équipe à la partie</H2>
        </template>

        <div class="p-4">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-6">Création</h1>
            </div>

            <div class="flex flex-col md:flex-row">
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/Taro_Misaki.webp')"
                ></div>

                <div class="basis-full md:basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-lg mx-0 md:mx-6 bg-white">
                    <form @submit.prevent="submit">
                        <TeamFormFields :form="form" />

                        <div class="flex justify-around p-6">
                            <button
                                type="submit"
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Création
                            </button>

                            <Link
                                :href="route('game-saves.teams.index', { gameSave: gameSave.id })"
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

import H2 from '@/Components/H2.vue';
import TeamFormFields from '@/Components/TeamFormFields.vue';

const props = defineProps({
    gameSave: { type: Object, required: true },
});

const form = useForm({
    name: '',
    description: '',
    budget: '',
    wins: 0,
    losses: 0,
    draws: 0,
    tactical_style: 'balanced',
    management_philosophy: 'collective',
    logo: null,
});

function submit() {
    form.post(route('game-saves.teams.store', { gameSave: props.gameSave.id }), {
        forceFormData: true,
    });
}
</script>
