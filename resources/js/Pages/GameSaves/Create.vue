<template>
    <Head title="Nouvelle partie" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Nouvelle partie</H2>
        </template>

        <div class="p-4">
            <!-- Titre -->
            <div class="flex justify-center mb-6">
                <h1 class="text-3xl font-bold text-slate-600">
                    Création d'une partie
                </h1>
            </div>

            <div class="flex flex-row">
                <!-- Visuel gauche -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/hyuga2.webp')"
                ></div>

                <!-- Carte formulaire -->
                <div class="basis-2/3 p-4 min-h-[500px] border border-slate-300 rounded-lg mx-6 bg-white">
                    <form @submit.prevent="submit">
                        <!-- Ligne 1 : nom de la partie -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="label" value="Nom de la partie" />
                                <InputText
                                    id="label"
                                    type="text"
                                    class="mt-1 w-full"
                                    v-model="form.label"
                                />
                                <p v-if="form.errors.label" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.label }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 2 : période -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="period" value="Période" />
                                <InputSelect
                                    id="period"
                                    v-model="form.period"
                                    class="mt-1"
                                >
                                    <option value="college">Collège</option>
                                    <option value="highschool">Lycée</option>
                                    <option value="pro">Professionnel</option>
                                </InputSelect>
                                <p v-if="form.errors.period" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.period }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Boutons -->
                        <div class="flex justify-around p-6">
                            <button
                                type="submit"
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Démarrer
                            </button>

                            <Link
                                :href="route('mainMenu')"
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
import FormRaw from '@/Components/FormRaw.vue';
import FormCol from '@/Components/FormCol.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import InputSelect from '@/Components/InputSelect.vue';

const form = useForm({
    label: '',
    period: 'college',
    // plus tard : team_id, difficulté, etc.
});

function submit() {
    form.post(route('game-saves.store'), {
        preserveScroll: true,
    });
}
</script>
