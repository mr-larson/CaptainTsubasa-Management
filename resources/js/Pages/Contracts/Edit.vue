<template>
    <Head title="Contracts" />

    <AuthenticatedLayout>
        <template #header>
        </template>

        <!-- SIDEBAR -->
        <aside
            id="separator-sidebar"
            class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
            aria-label="Sidebar"
        >
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 mb-3 border-b text-center text-gray-200">
                    <H2>Contrats</H2>
                </div>

                <div class="mb-2">
                    <form>
                        <label for="contract-search" class="mb-2 text-sm font-medium text-gray-900 sr-only">
                            Search
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg
                                    class="w-4 h-4 text-gray-500"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                                    />
                                </svg>
                            </div>
                            <input
                                id="contract-search"
                                v-model="searchQuery"
                                type="search"
                                class="block w-full p-1 pl-10 text-sm text-gray-900 border border-gray-300 bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded"
                                placeholder="Recherche"
                            >
                        </div>
                    </form>
                </div>

                <ul class="space-y-1 font-medium">
                    <li
                        v-for="contract in filteredContracts"
                        :key="contract.id"
                        @click="selectContract(contract)"
                    >
                        <a
                            href="#"
                            :class="{ 'bg-slate-500': form.selectedContractId === contract.id }"
                            class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500"
                        >
                        <span class="ml-3">
                            {{ contract.team_name ?? 'Équipe ?' }}
                            /
                            {{ contract.player_name ?? 'Joueur ?' }}
                        </span>
                        </a>
                    </li>
                </ul>


                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-1 flex">
                        <Link
                            :href="route('contracts.create')"
                            class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center"
                        >
                            Créer un contrat
                        </Link>
                    </li>
                    <li class="pt-2 flex">
                        <Link
                            :href="route('dataBaseMenu')"
                            class="bg-slate-500 hover:bg-slate-600 border border-slate-300 shadow-gray-100 text-white p-1 w-full rounded text-center"
                        >
                            Retour
                        </Link>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-4 sm:ml-64">
            <H1>Editions</H1>

            <FormContainer>
                <form @submit.prevent="submit">
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="team_id" value="Équipe" />
                            <InputSelect
                                id="team_id"
                                v-model="form.team_id"
                                class="mt-1"
                                :placeholder="'Sélectionne une équipe'"
                            >
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
                                :placeholder="'Sélectionne un joueur'"
                            >
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
                            <InputLabel for="salary" value="Coût par match" />
                            <InputText
                                id="salary"
                                type="number"
                                class="mt-1 w-full"
                                v-model="form.salary"
                            />
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
                        </FormCol>
                    </FormRaw>

                    <FormRaw>
                        <FormCol>
                            <InputLabel value="Matchs joués" />
                            <InputText
                                type="number"
                                class="mt-1 w-full bg-gray-100"
                                :value="form.matches_played"
                                disabled
                            />
                        </FormCol>

                        <FormCol>
                            <InputLabel value="Matchs restants" />
                            <InputText
                                type="number"
                                class="mt-1 w-full bg-gray-100"
                                :value="Math.max(0, form.matches_total - form.matches_played)"
                                disabled
                            />
                        </FormCol>
                    </FormRaw>

                    <ButtonGroup>
                        <ButtonPrimary :disabled="form.processing || !form.id">
                            Mettre à jour
                        </ButtonPrimary>

                        <ButtonDanger
                            type="button"
                            class="w-36"
                            :disabled="form.processing || !form.id"
                            @click="deleteContract"
                        >
                            Supprimer
                        </ButtonDanger>
                    </ButtonGroup>
                </form>
            </FormContainer>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, defineProps, computed, onMounted } from 'vue';

// Components
import H2 from '@/Components/H2.vue';
import H1 from '@/Components/H1.vue';
import FormContainer from '@/Components/FormContainer.vue';
import FormCol from '@/Components/FormCol.vue';
import FormRaw from '@/Components/FormRaw.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import InputSelect from '@/Components/InputSelect.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';

const props = defineProps({
    contracts: {
        type: Array,
        required: true,
    },
    teams: {
        type: Array,
        required: true,
    },
    players: {
        type: Array,
        required: true,
    },
});


const teams   = props.teams;
const players = props.players;

const form = useForm({
    selectedContractId: null,
    id: null,
    team_id: '',
    player_id: '',
    salary: '',
    matches_total: 1,
    matches_played: 0,
});

const searchQuery = ref('');

const filteredContracts = computed(() => {
    if (!searchQuery.value) return props.contracts;

    const q = searchQuery.value.toLowerCase();

    return props.contracts.filter((c) => {
        const team = (c.team_name || '').toLowerCase();
        const player = (c.player_name || '').toLowerCase();
        return team.includes(q) || player.includes(q);
    });
});

function selectContract(contract) {
    form.selectedContractId = contract.id;
    form.id                 = contract.id;
    form.team_id            = contract.team_id ?? '';
    form.player_id          = contract.player_id ?? '';
    form.salary             = contract.salary ?? '';
    form.matches_total      = contract.matches_total ?? 1;
    form.matches_played     = contract.matches_played ?? 0;

    form.clearErrors();
}


function submit() {
    if (!form.id) return;

    form.post(route('contracts.update', form.id), {
        preserveScroll: true,
    });
}

function deleteContract() {
    if (!form.id) return;

    if (!confirm('Voulez-vous vraiment supprimer ce contrat ?')) {
        return;
    }

    form.delete(route('contracts.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (props.contracts.length > 0) {
                selectContract(props.contracts[0]);
            } else {
                form.reset();
            }
        },
    });
}

onMounted(() => {
    if (props.contracts.length > 0) {
        selectContract(props.contracts[0]);
    }
});
</script>
