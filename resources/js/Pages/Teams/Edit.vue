<template>
    <Head title="Teams"/>

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
        </aside>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-4 sm:ml-64">
            <H1>Editions</H1>

            <FormContainer>
                <form @submit.prevent="submit">
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="name" value="Name" />
                            <InputText
                                id="name"
                                type="text"
                                class="mt-1 w-full"
                                v-model="form.name"
                                autocomplete="name"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                                {{ form.errors.name }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel for="budget" value="Budget" />
                            <InputText
                                type="number"
                                id="budget"
                                v-model="form.budget"
                                placeholder="Budget de l'équipe"
                                class="mt-1 w-full"
                                autocomplete="budget"
                            />
                            <p v-if="form.errors.budget" class="text-sm text-red-600 mt-1">
                                {{ form.errors.budget }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel for="wins" value="Victoire(s)" />
                            <InputText
                                type="number"
                                id="wins"
                                v-model="form.wins"
                                placeholder="Victoire(s) de l'équipe"
                                class="mt-1 w-full"
                            />
                            <p v-if="form.errors.wins" class="text-sm text-red-600 mt-1">
                                {{ form.errors.wins }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel for="losses" value="Défaite(s)" />
                            <InputText
                                type="number"
                                id="losses"
                                v-model="form.losses"
                                placeholder="Défaite(s) de l'équipe"
                                class="mt-1 w-full"
                            />
                            <p v-if="form.errors.losses" class="text-sm text-red-600 mt-1">
                                {{ form.errors.losses }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel for="draws" value="Matchs nuls" />
                            <InputText
                                type="number"
                                id="draws"
                                v-model="form.draws"
                                placeholder="Matchs nuls de l'équipe"
                                class="mt-1 w-full"
                            />
                            <p v-if="form.errors.draws" class="text-sm text-red-600 mt-1">
                                {{ form.errors.draws }}
                            </p>
                        </FormCol>
                        <FormCol>
                            <InputLabel value="Logo" />

                            <div class="mt-1 flex flex-col gap-2">
                                <div class="flex items-center gap-3">
                                    <!-- input file caché -->
                                    <input
                                        ref="logoInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="onLogoChange"
                                    />

                                    <!-- faux input -->
                                    <div class="flex items-center w-full md:w-56">
                                        <button
                                            type="button"
                                            class="shrink-0 px-3 py-1.5 rounded-l-full border border-gray-300 bg-stone-50 text-sm text-gray-900 hover:bg-white focus:outline-none focus:border-slate-700"
                                            @click="openLogoPicker"
                                        >
                                            Choisir
                                        </button>

                                        <div
                                            class="flex-1 px-3 py-1.5 rounded-r-full border border-l-0 border-gray-300 bg-stone-50 text-sm text-slate-600 truncate"
                                            :title="selectedLogoName || 'Aucun fichier choisi'"
                                        >
                                            {{ selectedLogoName || 'Aucun fichier choisi' }}
                                        </div>
                                    </div>

                                    <!-- preview carré -->
                                    <div class="h-16 w-16 rounded border bg-white overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="logoPreviewUrl || form.logo_path"
                                            :src="logoPreviewUrl ? logoPreviewUrl : `/${form.logo_path}`"
                                            class="h-full w-full object-cover"
                                            alt="Logo équipe"
                                        />
                                        <span v-else class="text-xs text-slate-400">Aucun</span>
                                    </div>

                                    <!-- supprimer logo existant -->
                                    <button
                                        v-if="form.logo_path"
                                        type="button"
                                        class="px-3 py-1.5 rounded-full border border-red-300 bg-red-50 text-sm text-red-700 hover:bg-red-100"
                                        @click="removeLogo"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </div>

                            <p v-if="form.errors.logo" class="text-sm text-red-600 mt-1">
                                {{ form.errors.logo }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <FormRaw>
                        <FormCol>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Description du joueur (optionnel)"
                                class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm text-gray-900 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">
                                {{ form.errors.description }}
                            </p>
                        </FormCol>
                    </FormRaw>

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
import { ref, defineProps, computed, onMounted } from 'vue';

// Components
import H2 from '@/Components/H2.vue';
import H1 from '@/Components/H1.vue';
import FormContainer from '@/Components/FormContainer.vue';
import FormRaw from '@/Components/FormRaw.vue';
import FormCol from '@/Components/FormCol.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';

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
});

const searchQuery = ref('');

const filteredTeams = computed(() => {
    if (!searchQuery.value) return props.teams;
    return props.teams.filter(team =>
        team.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// AJOUT LOGO UI
const logoInput = ref(null);
const selectedLogoName = ref('');
const logoPreviewUrl = ref('');

function openLogoPicker() {
    logoInput.value?.click();
}

function onLogoChange(e) {
    const file = e.target.files?.[0] ?? null;

    if (!file) {
        form.logo = null;
        selectedLogoName.value = '';
        logoPreviewUrl.value = '';
        return;
    }

    form.logo = file;
    form.remove_logo = false; // si on choisit un fichier, on annule le "remove"
    selectedLogoName.value = file.name;

    // preview
    logoPreviewUrl.value = URL.createObjectURL(file);
}

function removeLogo() {
    // suppression côté backend
    form.remove_logo = true;

    // reset fichier / preview
    form.logo = null;
    selectedLogoName.value = '';
    logoPreviewUrl.value = '';

    // reset input file
    if (logoInput.value) logoInput.value.value = '';
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

    // AJOUT LOGO
    form.logo_path      = team.logo_path ?? null;
    form.logo           = null;
    form.remove_logo    = false;

    // reset preview UI
    selectedLogoName.value = '';
    logoPreviewUrl.value   = '';

    form.clearErrors();
}

function submit() {
    if (!form.id) return;

    form.post(route('teams.update', form.id), {
        preserveScroll: true,
        forceFormData: true,
    });
}

function deleteTeam() {
    if (!form.id) return;

    if (!confirm('Voulez-vous vraiment supprimer cette équipe ?')) {
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
