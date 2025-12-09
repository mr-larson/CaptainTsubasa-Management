<template>
    <Head title="Add Contract" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Add Contract</H2>
        </template>

        <div class="p-4">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-6">
                    Création d'un contrat
                </h1>
            </div>

            <div class="flex flex-row">
                <!-- Image décorative -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/Mamoru_Izawa_(Shutetsu_ES-SR-Tq)_Full.webp')"
                ></div>

                <!-- Formulaire -->
                <div class="basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-lg mx-6 bg-white">
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
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Création
                            </button>

                            <Link
                                :href="route('dataBaseMenu')"
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
