<!-- resources/js/Pages/Teams/Create.vue -->
<template>
    <Head title="Ajouter une équipe" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Ajouter une équipe</H2>
        </template>

        <div class="p-4">

            <div class="flex flex-col md:flex-row">
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat bg-[url('/images/Mamoru\_Izawa\_(Shutetsu\_ES-SR-Tq)\_Full.webp')]"
                ></div>

                <div class="basis-full md:basis-2/3 min-h-[500px] rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 md:mx-6">
                    <form @submit.prevent="submit">
                        <TeamFormFields :form="form" />

                        <div class="flex justify-around p-6">
                            <button
                                type="submit"
                                class="mb-2 inline-flex min-h-[44px] w-40 items-center justify-center rounded-full bg-teal-500 px-5 text-sm font-bold text-slate-900 shadow-sm transition-colors hover:bg-teal-400 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Créer
                            </button>

                            <Link
                                :href="route('dataBaseMenu')"
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
import TeamFormFields from '@/Components/TeamFormFields.vue';

const form = useForm({
    name: '',
    description: '',
    budget: '',
    wins: 0,
    losses: 0,
    draws: 0,
    tactical_style: 'balanced',
    management_philosophy: 'collective',
    logo: null, // ✅ AJOUT
});

function submit() {
    form.post(route('teams.store'), {
        forceFormData: true, // ✅ indispensable pour l'upload
        onSuccess: () => {
            // rien
        },
    });
}
</script>
