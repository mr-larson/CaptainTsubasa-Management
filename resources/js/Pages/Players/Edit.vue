<!-- resources/js/Pages/Players/Edit.vue -->
<template>
    <Head title="Players"/>

    <AuthenticatedLayout>
        <template #header></template>

        <!-- SIDEBAR -->
        <aside
            id="separator-sidebar"
            class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
            aria-label="Sidebar"
        >
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 mb-3 border-b text-center text-gray-200">
                    <H2>Joueurs</H2>
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

                <!-- Liste joueurs -->
                <ul class="space-y-1 font-medium">
                    <li
                        v-for="player in filteredPlayers"
                        :key="player.id"
                        @click="selectPlayer(player)"
                    >
                        <a
                            href="#"
                            :class="{'bg-slate-500': form.selectedPlayerId === player.id}"
                            class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500"
                        >
                            <span class="ml-3">
                                {{ player.firstname }} {{ player.lastname }}
                            </span>
                        </a>
                    </li>
                </ul>

                <!-- Bas de sidebar -->
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-1 flex">
                        <Link
                            :href="route('players.create')"
                            class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center"
                        >
                            Créer un joueur
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
                    <!-- Ligne 1 : prénom / nom -->
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="firstname" value="Prénom" />
                            <InputText
                                id="firstname"
                                type="text"
                                class="mt-1 w-full"
                                v-model="form.firstname"
                                autocomplete="given-name"
                            />
                            <p v-if="form.errors.firstname" class="text-sm text-red-600 mt-1">
                                {{ form.errors.firstname }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel for="lastname" value="Nom" />
                            <InputText
                                id="lastname"
                                type="text"
                                class="mt-1 w-full"
                                v-model="form.lastname"
                                autocomplete="family-name"
                            />
                            <p v-if="form.errors.lastname" class="text-sm text-red-600 mt-1">
                                {{ form.errors.lastname }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 2 : âge / position -->
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="age" value="Âge" />
                            <InputText
                                id="age"
                                type="number"
                                class="mt-1 w-full"
                                v-model="form.age"
                            />
                            <p v-if="form.errors.age" class="text-sm text-red-600 mt-1">
                                {{ form.errors.age }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel for="position" value="Position" />
                            <InputSelect
                                id="position"
                                v-model="form.position"
                                class="mt-1"
                                required
                            >
                                <option
                                    v-for="pos in positions"
                                    :key="pos"
                                    :value="pos"
                                >
                                    {{ positionLabels[pos] ?? pos }}
                                </option>
                            </InputSelect>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 3 : coût & photo-->
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="cost" value="Coût" />
                            <InputText
                                id="cost"
                                type="number"
                                class="mt-1 w-full"
                                v-model="form.cost"
                            />
                            <p v-if="form.errors.cost" class="text-sm text-red-600 mt-1">
                                {{ form.errors.cost }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel value="Photo" />

                            <div class="mt-1 flex flex-col gap-2">
                                <div class="flex items-center gap-3">
                                    <!-- input file caché -->
                                    <input
                                        ref="photoInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="onPhotoChange"
                                    />

                                    <!-- faux input -->
                                    <div class="flex items-center w-full md:w-56">
                                        <button
                                            type="button"
                                            class="shrink-0 px-3 py-1.5 rounded-l-full border border-gray-300 bg-stone-50 text-sm text-gray-900 hover:bg-white focus:outline-none focus:border-slate-700"
                                            @click="openPhotoPicker"
                                        >
                                            Choisir
                                        </button>

                                        <div
                                            class="flex-1 px-3 py-1.5 rounded-r-full border border-l-0 border-gray-300 bg-stone-50 text-sm text-slate-600 truncate"
                                            :title="selectedPhotoName || 'Aucun fichier choisi'"
                                        >
                                            {{ selectedPhotoName || 'Aucun fichier choisi' }}
                                        </div>
                                    </div>

                                    <!-- preview carré -->
                                    <div class="h-16 w-16 rounded border bg-white overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="photoPreviewUrl || form.photo_path"
                                            :src="photoPreviewUrl ? photoPreviewUrl : `/storage/${form.photo_path}`"
                                            class="h-full w-full object-cover"
                                            alt="Photo joueur"
                                        />
                                        <span v-else class="text-xs text-slate-400">Aucune</span>
                                    </div>
                                </div>
                            </div>

                            <p v-if="form.errors.photo" class="text-sm text-red-600 mt-1">
                                {{ form.errors.photo }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 4 : stats attaque / défense -->
                    <FormRaw>
                        <FormCol>
                            <label
                                for="stats.attack"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Attaque
                            </label>
                            <input
                                type="number"
                                id="stats.attack"
                                v-model="form.stats.attack"
                                placeholder="Attaque"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.attack']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.attack'] }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <label
                                for="stats.defense"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Défense
                            </label>
                            <input
                                type="number"
                                id="stats.defense"
                                v-model="form.stats.defense"
                                placeholder="Défense"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.defense']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.defense'] }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 5 : stats speed / stamina -->
                    <FormRaw>
                        <FormCol>
                            <label
                                for="stats.speed"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Vitesse
                            </label>
                            <input
                                type="number"
                                id="stats.speed"
                                v-model="form.stats.speed"
                                placeholder="Vitesse"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.speed']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.speed'] }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <label
                                for="stats.stamina"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Stamina
                            </label>
                            <input
                                type="number"
                                id="stats.stamina"
                                v-model="form.stats.stamina"
                                placeholder="Stamina"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.stamina']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.stamina'] }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 6 : stats shot / pass -->
                    <FormRaw>
                        <FormCol>
                            <label
                                for="stats.shot"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Tir
                            </label>
                            <input
                                type="number"
                                id="stats.shot"
                                v-model="form.stats.shot"
                                placeholder="Tir"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.shot']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.shot'] }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <label
                                for="stats.pass"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Passe
                            </label>
                            <input
                                type="number"
                                id="stats.pass"
                                v-model="form.stats.pass"
                                placeholder="Passe"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.pass']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.pass'] }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 7 : stats dribble / block -->
                    <FormRaw>
                        <FormCol>
                            <label
                                for="stats.dribble"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Dribble
                            </label>
                            <input
                                type="number"
                                id="stats.dribble"
                                v-model="form.stats.dribble"
                                placeholder="Dribble"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.dribble']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.dribble'] }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <label
                                for="stats.block"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Block
                            </label>
                            <input
                                type="number"
                                id="stats.block"
                                v-model="form.stats.block"
                                placeholder="Block"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.block']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.block'] }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 8 : stats intercept / tackle -->
                    <FormRaw>
                        <FormCol>
                            <label
                                for="stats.intercept"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Interception
                            </label>
                            <input
                                type="number"
                                id="stats.intercept"
                                v-model="form.stats.intercept"
                                placeholder="Interception"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.intercept']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.intercept'] }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <label
                                for="stats.tackle"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Tacle
                            </label>
                            <input
                                type="number"
                                id="stats.tackle"
                                v-model="form.stats.tackle"
                                placeholder="Tacle"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.tackle']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.tackle'] }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 9 : stats hand_save / punch_save -->
                    <FormRaw>
                        <FormCol>
                            <label
                                for="stats.hand_save"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Arrêt main
                            </label>
                            <input
                                type="number"
                                id="stats.hand_save"
                                v-model="form.stats.hand_save"
                                placeholder="Arrêt main"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.hand_save']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.hand_save'] }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <label
                                for="stats.punch_save"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Dégagement poing
                            </label>
                            <input
                                type="number"
                                id="stats.punch_save"
                                v-model="form.stats.punch_save"
                                placeholder="Dégagement poing"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.punch_save']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.punch_save'] }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Dernière ligne : description (2 colonnes) -->
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
                        <ButtonPrimary :disabled="form.processing || !form.id">
                            Mettre à jour
                        </ButtonPrimary>

                        <ButtonDanger
                            class="w-36"
                            type="button"
                            :disabled="form.processing || !form.id"
                            @click="deletePlayer"
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
import { ref, onBeforeUnmount, defineProps, computed, onMounted } from 'vue';

// Components
import H2 from '@/Components/H2.vue';
import H1 from '@/Components/H1.vue';
import FormContainer from '@/Components/FormContainer.vue';
import FormCol from '@/Components/FormCol.vue';
import FormRaw from '@/Components/FormRaw.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import InputSelect from "@/Components/InputSelect.vue";

const props = defineProps({
    players: { type: Array, required: true },
    positions: { type: Array, required: true },
    positionLabels: { type: Object, required: true },
});

const form = useForm({
    selectedPlayerId: null,
    id: null,
    lastname: '',
    firstname: '',
    age: '',
    position: '',
    cost: '',
    description: '',
    photo_path: null, // string depuis DB
    photo: null,      // File
    stats: {
        speed: 0,
        attack: 0,
        defense: 0,
        stamina: 0,
        shot: 0,
        pass: 0,
        dribble: 0,
        block: 0,
        intercept: 0,
        tackle: 0,
        hand_save: 0,
        punch_save: 0,
    },
});

const searchQuery = ref('');

const filteredPlayers = computed(() => {
    if (!searchQuery.value) return props.players;

    const q = searchQuery.value.toLowerCase();
    return props.players.filter((player) => {
        return (
            (player.firstname ?? '').toLowerCase().includes(q) ||
            (player.lastname ?? '').toLowerCase().includes(q)
        );
    });
});

// ==========================
// PREVIEW (instantané)
// ==========================

const photoPreviewUrl = ref(null);

const revokePreview = () => {
    if (photoPreviewUrl.value) {
        URL.revokeObjectURL(photoPreviewUrl.value);
        photoPreviewUrl.value = null;
    }
};

const photoInput = ref(null);
const selectedPhotoName = ref('');

const openPhotoPicker = () => {
    photoInput.value?.click();
};

const onPhotoChange = (e) => {
    const file = e.target.files?.[0] ?? null;

    revokePreview();
    form.photo = file;

    selectedPhotoName.value = file ? file.name : '';

    if (file) {
        photoPreviewUrl.value = URL.createObjectURL(file);
    }
    e.target.value = '';
};


onBeforeUnmount(() => {
    revokePreview();
});

// ==========================
// SELECT / CRUD
// ==========================

function selectPlayer(player) {
    // reset preview + file quand on change de joueur
    revokePreview();
    form.photo = null;

    form.selectedPlayerId = player.id;
    form.id               = player.id;
    form.firstname        = player.firstname ?? '';
    form.lastname         = player.lastname ?? '';
    form.age              = player.age ?? '';
    form.position         = player.position ?? '';
    form.cost             = player.cost ?? '';
    form.description      = player.description ?? '';
    form.photo_path       = player.photo_path ?? null;
    selectedPhotoName.value = '';


    const stats = player.stats || {};
    form.stats = {
        speed:       Number(stats.speed ?? 0),
        attack:      Number(stats.attack ?? 0),
        defense:     Number(stats.defense ?? 0),
        stamina:     Number(stats.stamina ?? 0),

        shot:        Number(stats.shot ?? 0),
        pass:        Number(stats.pass ?? 0),
        dribble:     Number(stats.dribble ?? 0),
        block:       Number(stats.block ?? 0),
        intercept:   Number(stats.intercept ?? 0),
        tackle:      Number(stats.tackle ?? 0),
        hand_save:   Number(stats.hand_save ?? 0),
        punch_save:  Number(stats.punch_save ?? 0),
    };

    form.clearErrors();
}

function submit() {
    if (!form.id) return;

    form.post(route('players.update', form.id), {
        preserveScroll: true,
        forceFormData: true, // ✅ nécessaire pour envoyer form.photo (File)
    });
}

function deletePlayer() {
    if (!form.id) return;

    if (!confirm('Voulez-vous vraiment supprimer ce joueur ?')) return;

    form.delete(route('players.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (props.players.length > 0) {
                selectPlayer(props.players[0]);
            } else {
                form.reset();
            }
        },
    });
}

onMounted(() => {
    if (props.players.length > 0) {
        selectPlayer(props.players[0]);
    }
});
</script>
