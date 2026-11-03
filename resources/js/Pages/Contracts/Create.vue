<template>
    <Head title="Ajouter un contrat" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Ajouter un contrat</H2>
        </template>

        <div class="p-4">

            <div class="flex flex-col md:flex-row">
                <!-- Image décorative -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat bg-[url('/images/Mamoru\_Izawa\_(Shutetsu\_ES-SR-Tq)\_Full.webp')]"
                ></div>

                <!-- Formulaire -->
                <div class="basis-full md:basis-2/3 min-h-[500px] rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 md:mx-6">
                    <form @submit.prevent="submit">
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="team_id" value="Équipe" />
                                <InputSelect
                                    id="team_id"
                                    v-model="form.team_id"
                                    class="mt-1"
                                >
                                    <option disabled value="">
                                        -- Sélectionne une équipe --
                                    </option>
                                    <option
                                        v-for="team in teams"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }}
                                    </option>
                                </InputSelect>
                                <p v-if="form.errors.team_id" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.team_id }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="player_id" value="Joueur" />
                                <InputSelect
                                    id="player_id"
                                    v-model="form.player_id"
                                    class="mt-1"
                                >
                                    <option disabled value="">
                                        -- Sélectionne un joueur --
                                    </option>
                                    <option
                                        v-for="player in players"
                                        :key="player.id"
                                        :value="player.id"
                                    >
                                        {{ player.firstname }} {{ player.lastname }}
                                    </option>
                                </InputSelect>
                                <p v-if="form.errors.player_id" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.player_id }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel for="salary" value="Coût par match (€)" />
                                <InputText
                                    id="salary"
                                    type="number"
                                    class="mt-1 w-full"
                                    v-model="form.salary"
                                    min="0"
                                />
                                <p v-if="form.errors.salary" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.salary }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="matches_total" value="Nombre de matchs" />
                                <InputText
                                    id="matches_total"
                                    type="number"
                                    class="mt-1 w-full"
                                    v-model="form.matches_total"
                                    min="1"
                                />
                                <p v-if="form.errors.matches_total" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.matches_total }}
                                </p>
                            </FormCol>
                        </FormRaw>

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
import FormRaw from '@/Components/FormRaw.vue';
import FormCol from '@/Components/FormCol.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import InputSelect from '@/Components/InputSelect.vue';

const props = defineProps({
    teams: {
        type: Array,
        required: true,
    },
    players: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    team_id: '',
    player_id: '',
    salary: '',
    matches_total: 1,
});

const { teams, players } = props;

function submit() {
    form.post(route('contracts.store'), {
        onSuccess: () => {
            // redirection gérée côté controller (vers contracts.index ou contracts.edit)
        },
    });
}
</script>
