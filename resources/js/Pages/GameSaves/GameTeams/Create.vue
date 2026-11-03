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
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat bg-[url('/images/Taro_Misaki.webp')]"
                ></div>

                <div class="basis-full md:basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-lg mx-0 md:mx-6 bg-white">
                    <form @submit.prevent="submit">
                        <TeamFormFields :form="form" />

                        <div class="flex justify-around p-6">
                            <button
                                type="submit"
                                class="mb-2 inline-flex min-h-[44px] w-40 items-center justify-center rounded-full bg-teal-500 px-5 text-sm font-bold text-slate-900 shadow-sm transition-colors hover:bg-teal-400 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Création
                            </button>

                            <Link
                                :href="route('game-saves.teams.index', { gameSave: gameSave.id })"
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
