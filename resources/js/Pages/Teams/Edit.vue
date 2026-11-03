<template>
    <Head title="Équipes"/>

    <AuthenticatedLayout>
        <template #header>
            <H2>Équipes</H2>
        </template>

        <!-- SIDEBAR -->
        <MobileSidebar>
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 pb-5 mb-3 border-b text-center text-gray-200">
                    <H2>Equipes</H2>
                </div>

                <!-- Recherche -->
                <div class="mb-2">
                    <form>
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only">
                            Search
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg
                                    class="w-4 h-4 text-gray-500 dark:text-gray-400"
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
                                type="search"
                                id="default-search"
                                v-model="searchQuery"
                                class="block w-full p-1 pl-10 text-sm text-gray-900 border border-gray-300 bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded"
                                placeholder="Recherche"
                            >
                        </div>
                    </form>
                </div>

                <!-- Liste -->
                <ul class="space-y-1 font-medium">
                    <li
                        v-for="team in filteredTeams"
                        :key="team.id"
                        @click="selectTeam(team)"
                    >
                        <a
                            href="#"
                            :class="{'bg-slate-500': form.selectedTeamId === team.id}"
                            class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500"
                        >
                            <span class="ml-3">{{ team.name }}</span>
                        </a>
                    </li>
                </ul>

                <!-- Actions bas -->
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-1 flex">
                        <Link
                            :href="route('teams.create')"
                            class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center"
                        >
                            Créer une équipe
                        </Link>
                    </li>
                    <li class="pt-2 flex">
                        <Link
                            :href="route('dataBaseMenu')"
                            class="bg-slate-500 hover:bg-slate-600 border border-slate-300 text-white p-1 w-full rounded text-center"
                        >
                            Retour
                        </Link>
                    </li>
                </ul>
            </div>
        </MobileSidebar>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-4 sm:ml-64">

            <FormContainer>
                <form @submit.prevent="submit">
                    <TeamFormFields
                        :form="form"
                        :logo-url="logoPath ? `/storage/${logoPath}` : null"
                        @remove-logo="removeLogo"
                    />

                    <ButtonGroup>
                        <ButtonPrimary :disabled="form.processing">
                            Mettre à jour
                        </ButtonPrimary>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-if="form.recentlySuccessful"
                                class="text-sm text-slate-600"
                            >
                                Saved.
                            </p>
                        </Transition>

                        <ButtonDanger
                            class="w-36"
                            type="button"
                            :disabled="form.processing || !form.id"
                            @click="deleteTeam"
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
import { confirmDialog } from '@/Composables/useDialog.js';
import { ref, computed, onMounted } from 'vue';

// Components
import H2 from '@/Components/H2.vue';
import MobileSidebar from '@/Components/MobileSidebar.vue';
import FormContainer from '@/Components/FormContainer.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import TeamFormFields from '@/Components/TeamFormFields.vue';

const props = defineProps({
    teams: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    selectedTeamId: null,
    id: null,
    name: '',
    description: '',
    budget: '',
    wins: 0,
    draws: 0,
    losses: 0,
    logo: null,
    remove_logo: false,
    tactical_style: 'balanced',
    management_philosophy: 'collective',
});

const searchQuery = ref('');

const filteredTeams = computed(() => {
    if (!searchQuery.value) return props.teams;
    return props.teams.filter(team =>
        team.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// Logo enregistré : affiché seulement, jamais renvoyé au serveur (il gère seul
// logo_path ; le renvoyer écrasait un nouveau logo par l'ancien chemin).
const logoPath = ref(null);

function removeLogo() {
    // suppression côté backend
    form.remove_logo = true;
    form.logo = null;
}

function selectTeam(team) {
    form.selectedTeamId = team.id;
    form.id             = team.id;
    form.name           = team.name;
    form.description    = team.description ?? '';
    form.budget         = team.budget ?? 0;
    form.wins           = team.wins ?? 0;
    form.draws          = team.draws ?? 0;
    form.losses         = team.losses ?? 0;

    form.tactical_style        = team.tactical_style ?? 'balanced';
    form.management_philosophy = team.management_philosophy ?? 'collective';

    logoPath.value      = team.logo_path ?? null;
    form.logo           = null;
    form.remove_logo    = false;

    form.clearErrors();
}

function submit() {
    if (!form.id) return;

    form.post(route('teams.update', form.id), {
        preserveScroll: true,
        forceFormData: true,
        // Recharger l'équipe enregistrée : logo à jour, fichier et « supprimer
        // le logo » remis à zéro (sinon re-upload au clic suivant).
        onSuccess: () => {
            const fresh = props.teams.find(t => t.id === form.id);
            if (fresh) selectTeam(fresh);
        },
    });
}

async function deleteTeam() {
    if (!form.id) return;

    if (!(await confirmDialog({ title: "Supprimer l'équipe", message: 'Voulez-vous vraiment supprimer cette équipe ?', confirmLabel: 'Supprimer', danger: true }))) {
        return;
    }

    form.delete(route('teams.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            // On peut sélectionner la première équipe restante
            if (props.teams.length > 0) {
                selectTeam(props.teams[0]);
            } else {
                form.reset();
            }
        },
    });
}

onMounted(() => {
    if (props.teams.length > 0) {
        selectTeam(props.teams[0]);
    }
});
</script>
