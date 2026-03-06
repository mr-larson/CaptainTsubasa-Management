<template>
    <Head :title="`Joueurs — Partie #${gameSave.id}`" />

    <AuthenticatedLayout>
        <template #header></template>

        <!-- SIDEBAR -->
        <aside
            id="separator-sidebar"
            class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
            aria-label="Sidebar"
        >
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">

                <!-- HEADER -->
                <div class="p-3 mb-3 border-b text-center text-gray-200">
                    <H2>Joueurs</H2>
                </div>

                <!-- SEARCH -->
                <div class="mb-2">
                    <form>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg
                                    class="w-4 h-4 text-gray-500"
                                    aria-hidden="true"
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
                                v-model="searchQuery"
                                class="block w-full p-1 pl-10 text-sm text-gray-900 border border-gray-300 bg-gray-100 rounded focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Recherche"
                            >
                        </div>
                    </form>
                </div>

                <!-- LISTE JOUEURS -->
                <ul class="space-y-1 font-medium">
                    <li
                        v-for="player in filteredPlayers"
                        :key="player.id"
                        @click="selectPlayer(player)"
                    >
                        <a
                            href="#"
                            :class="{ 'bg-slate-500': form.selectedPlayerId === player.id }"
                            class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500"
                        >
                            <span class="ml-3 truncate">
                                {{ player.firstname }} {{ player.lastname }}
                            </span>
                        </a>
                    </li>
                </ul>

                <!-- ACTIONS -->
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-1 flex">
                        <Link
                            :href="route('game-saves.players.create', { gameSave: gameSave.id })"
                            class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center"
                        >
                            Créer un joueur
                        </Link>
                    </li>

                    <li class="pt-2 flex">
                        <Link
                            :href="route('game-saves.play', { gameSave: gameSave.id })"
                            class="bg-slate-500 hover:bg-slate-600 border border-slate-300 shadow-gray-100 text-white p-1 w-full rounded text-center"
                        >
                            Retour à la partie
                        </Link>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-6 sm:ml-64 bg-slate-200 min-h-screen">
            <H1>Édition des joueurs de la partie</H1>

            <div class="bg-white rounded-2xl shadow p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-slate-800">Joueur</h2>

                    <!-- + Création joueur -->
                    <Link
                        :href="route('game-saves.players.create', { gameSave: gameSave.id })"
                        class="flex items-center justify-center h-9 px-3 rounded-full bg-teal-500 text-white text-sm font-medium hover:bg-teal-600 shadow-md"
                        title="Créer un joueur"
                    >
                        + Ajouter
                    </Link>

                </div>
                <FormContainer>
                    <form @submit.prevent="submit">

                        <!-- Ligne 1 : prénom / nom -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Prénom" />
                                <InputText v-model="form.firstname" class="mt-1 w-full" />
                                <p v-if="form.errors.firstname" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.firstname }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Nom" />
                                <InputText v-model="form.lastname" class="mt-1 w-full" />
                                <p v-if="form.errors.lastname" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.lastname }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 2 : position / coût -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Position" />
                                <InputSelect
                                    id="position"
                                    v-model="form.position"
                                    class="mt-1"
                                    required
                                >
                                    <option disabled value="">Choisir...</option>
                                    <option v-for="p in positions" :key="p" :value="p">
                                        {{ p }}
                                    </option>
                                </InputSelect>
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Coût" />
                                <InputText type="number" v-model="form.cost" class="mt-1 w-full" />
                                <p v-if="form.errors.cost" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.cost }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 3 : photo -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Photo du joueur" />

                                <div class="flex items-center gap-3 mt-1">
                                    <!-- input file caché -->
                                    <input
                                        ref="photoInput"
                                        type="file"
                                        class="hidden"
                                        accept="image/*"
                                        @change="onPhotoChange"
                                    />

                                    <!-- faux input -->
                                    <div class="flex items-center w-full md:w-56">
                                        <button
                                            type="button"
                                            class="shrink-0 px-3 py-1.5 rounded-l-full border border-gray-300 bg-stone-50 text-sm text-gray-900 hover:bg-white"
                                            @click="openPhotoPicker"
                                        >
                                            Choisir
                                        </button>

                                        <div
                                            class="flex-1 px-3 py-1.5 rounded-r-full border border-l-0 border-gray-300 bg-stone-50 text-sm text-slate-600 truncate"
                                        >
                                            {{ selectedPhotoName || 'Aucun fichier choisi' }}
                                        </div>
                                    </div>

                                    <!-- preview -->
                                    <div class="h-16 w-16 rounded border bg-white overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="photoPreviewUrl || form.photo_path"
                                            :src="photoPreviewUrl ? photoPreviewUrl : `/storage/${form.photo_path}`"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else class="text-xs text-slate-400">Aucune</span>
                                    </div>
                                </div>

                                <p v-if="form.errors.photo" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.photo }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Stats  -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Vitesse" />
                                <InputText type="number" v-model="form.speed" class="mt-1 w-full" />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Attaque" />
                                <InputText type="number" v-model="form.attack" class="mt-1 w-full" />
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Défense" />
                                <InputText type="number" v-model="form.defense" class="mt-1 w-full" />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Endurance" />
                                <InputText type="number" v-model="form.stamina" class="mt-1 w-full" />
                            </FormCol>
                        </FormRaw>
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Tir" />
                                <InputText
                                    type="number"
                                    v-model="form.shot"
                                    class="mt-1 w-full"
                                />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Passe" />
                                <InputText
                                    type="number"
                                    v-model="form.pass"
                                    class="mt-1 w-full"
                                />
                            </FormCol>
                        </FormRaw>
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Dribble" />
                                <InputText
                                    type="number"
                                    v-model="form.dribble"
                                    class="mt-1 w-full"
                                />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Block" />
                                <InputText
                                    type="number"
                                    v-model="form.block"
                                    class="mt-1 w-full"
                                />
                            </FormCol>
                        </FormRaw>
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Interception" />
                                <InputText
                                    type="number"
                                    v-model="form.intercept"
                                    class="mt-1 w-full"
                                />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Tacle" />
                                <InputText
                                    type="number"
                                    v-model="form.tackle"
                                    class="mt-1 w-full"
                                />
                            </FormCol>
                        </FormRaw>
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Arrêt main" />
                                <InputText
                                    type="number"
                                    v-model="form.hand_save"
                                    class="mt-1 w-full"
                                />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Arrêt poings" />
                                <InputText
                                    type="number"
                                    v-model="form.punch_save"
                                    class="mt-1 w-full"
                                />
                            </FormCol>
                        </FormRaw>

                        <!-- Description -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Description" />
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                ></textarea>

                                <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.description }}
                                </p>
                            </FormCol>
                        </FormRaw>



                        <!-- ACTIONS -->
                        <ButtonGroup>
                            <ButtonPrimary :disabled="form.processing || !form.id">
                                Mettre à jour
                            </ButtonPrimary>

                            <ButtonDanger
                                :disabled="form.processing || !form.id"
                                type="button"
                                class="w-36"
                                @click="deletePlayer"
                            >
                                Supprimer
                            </ButtonDanger>
                        </ButtonGroup>
                    </form>
                </FormContainer>
            </div>
            <!-- CARTES Techniques spéciales + Contrat côte à côte -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- CARTE TECHNIQUES SPÉCIALES -->
                <FormContainer>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">
                            Techniques spéciales
                        </h2>

                        <!-- Bouton + en haut à droite -->
                        <button
                            type="button"
                            class="flex items-center justify-center h-9 px-3 rounded-full bg-teal-500 text-white text-sm font-medium hover:bg-teal-600 shadow-md"
                            @click="addSpecialMove"
                        >
                            + Ajouter
                        </button>
                    </div>

                    <!-- Liste des techniques -->
                    <div class="flex flex-col gap-4 max-h-[520px] overflow-y-auto pr-1">
                        <div
                            v-for="(move, index) in form.special_moves"
                            :key="index"
                            class="border border-slate-200 bg-white rounded-xl shadow-sm px-4 py-3"
                        >
                            <!-- Header de la technique -->
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-slate-800">
                                    Technique #{{ index + 1 }}
                                    <span v-if="move.label" class="text-slate-500 font-normal">
                        — {{ move.label }}
                    </span>
                                </h3>

                                <!-- Bouton supprimer compact -->
                                <button
                                    type="button"
                                    class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-600 hover:bg-red-100 border border-red-200"
                                    @click="removeSpecialMove(index)"
                                >
                                    Supprimer
                                </button>
                            </div>

                            <!-- Contenu du formulaire de la technique -->
                            <div class="space-y-3">
                                <!-- Label + Clé -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Label" />
                                        <InputText
                                            v-model="move.label"
                                            class="mt-1 w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel value="Clé (identifiant)" />
                                        <InputText
                                            v-model="move.key"
                                            class="mt-1 w-full"
                                        />
                                    </div>
                                </div>

                                <!-- Mode + Action de base -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Mode" />
                                        <InputSelect
                                            id="mode"
                                            v-model="move.mode"
                                            class="mt-1"
                                            required
                                        >
                                            <option value="attack">Attaque</option>
                                            <option value="defense">Défense</option>
                                        </InputSelect>
                                    </div>

                                    <div>
                                        <InputLabel value="Action de base" />
                                        <InputSelect
                                            id="mode"
                                            v-model="move.base_action"
                                            class="mt-1"
                                            required
                                        >
                                            <option value="shot">Tir</option>
                                            <option value="pass">Passe</option>
                                            <option value="dribble">Dribble</option>
                                            <option value="block">Block</option>
                                            <option value="intercept">Interception</option>
                                            <option value="tackle">Tacle</option>
                                            <option value="hand_save">Arrêt main</option>
                                            <option value="punch_save">Arrêt poing</option>
                                        </InputSelect>
                                    </div>
                                </div>

                                <!-- Cooldown + Description -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Cooldown (tours)" />
                                        <InputText
                                            type="number"
                                            v-model="move.cooldown"
                                            min="0"
                                            class="mt-1 w-full"
                                        />
                                    </div>

                                    <div class="md:col-span-1">
                                        <InputLabel value="Description" />
                                        <textarea
                                            v-model="move.description"
                                            rows="3"
                                            class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message si aucune technique -->
                        <p
                            v-if="form.special_moves.length === 0"
                            class="text-sm text-slate-500 italic"
                        >
                            Aucune technique spéciale définie pour ce joueur.
                        </p>
                    </div>
                </FormContainer>

                <!-- CARTE CONTRAT -->
                <FormContainer>
                    <!-- Header de la carte -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">
                            Contrat
                        </h2>

                        <!-- Bouton + pour créer un contrat si aucun -->
                        <button
                            v-if="!form.contract"
                            type="button"
                            class="flex items-center justify-center h-9 px-3 rounded-full bg-teal-500 text-white text-sm font-medium hover:bg-teal-600 shadow-md"
                            @click="startNewContract"
                        >
                            + Ajouter
                        </button>
                    </div>
                    <div
                        v-if="contractCreatedMessage"
                        class="mb-4 px-3 py-2 rounded-lg bg-teal-50 border border-teal-200 text-teal-700 text-sm font-medium"
                    >
                        {{ contractCreatedMessage }}
                    </div>

                    <!-- Si un contrat existe : formulaire d’édition -->
                    <form v-if="form.contract" @submit.prevent="updateContract" class="space-y-4">
                        <!-- Ligne 1 : Équipe + Salaire -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Équipe" />
                                <InputSelect
                                    v-model="form.contract.game_team_id"
                                    class="mt-1 w-full"
                                    required
                                >
                                    <option disabled value="">Choisir...</option>
                                    <option
                                        v-for="team in props.teams"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }}
                                    </option>
                                </InputSelect>
                            </div>

                            <div>
                                <InputLabel value="Salaire" class="block text-start"/>
                                <InputText
                                    type="number"
                                    v-model="form.contract.salary"
                                    class="mt-1 w-full"
                                />
                            </div>
                        </div>

                        <!-- Ligne 2 : Début / Fin -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Début (semaine)" />
                                <InputText
                                    type="number"
                                    v-model="form.contract.start_week"
                                    class="mt-1 w-full"
                                />
                            </div>

                            <div>
                                <InputLabel value="Fin (semaine)" />
                                <InputText
                                    type="number"
                                    v-model="form.contract.end_week"
                                    class="mt-1 w-full"
                                />
                            </div>
                        </div>

                        <!-- Ligne 3 : Titulaire -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Titulaire ?" />
                                <InputSelect
                                    v-model="form.contract.is_starter"
                                    class="mt-1 w-full"
                                >
                                    <option :value="true">Oui</option>
                                    <option :value="false">Non</option>
                                </InputSelect>
                            </div>

                            <!-- Colonne libre (pour future info ou juste alignement) -->
                            <div class="hidden md:block"></div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4">
                            <ButtonGroup>
                                <ButtonPrimary
                                    type="submit"
                                    :disabled="contractProcessing || !form.contract"
                                >
                                    {{ form.contract.id ? 'Mettre à jour' : 'Créer' }}
                                </ButtonPrimary>

                                <ButtonDanger
                                    v-if="form.contract.id"
                                    type="button"
                                    class="w-36"
                                    :disabled="contractProcessing || !form.contract"
                                    @click="deleteContract"
                                >
                                    Supprimer
                                </ButtonDanger>
                            </ButtonGroup>
                        </div>
                    </form>

                    <!-- Si aucun contrat encore défini -->
                    <div v-else class="text-sm text-slate-500 italic">
                        Aucun contrat défini pour ce joueur. Cliquez sur “+ Créer” pour ajouter un contrat.
                    </div>
                </FormContainer>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, computed, defineProps, onMounted, onBeforeUnmount } from 'vue'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import H1 from '@/Components/H1.vue'
import H2 from '@/Components/H2.vue'
import FormContainer from '@/Components/FormContainer.vue'
import FormRaw from '@/Components/FormRaw.vue'
import FormCol from '@/Components/FormCol.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputText from '@/Components/InputText.vue'
import ButtonGroup from '@/Components/ButtonGroup.vue'
import ButtonPrimary from '@/Components/ButtonPrimary.vue'
import ButtonDanger from '@/Components/ButtonDanger.vue'
import InputSelect from "@/Components/InputSelect.vue";

const props = defineProps({
    gameSave: Object,
    players: Array,
    teams:   Array,
})

const positions = [
    'Goalkeeper',
    'Defender',
    'Midfielder',
    'Forward'
]

const searchQuery = ref('')
const photoInput = ref(null)
const photoPreviewUrl = ref(null)
const selectedPhotoName = ref('')
const contractProcessing = ref(false)
const contractCreatedMessage = ref('')

const form = useForm({
    id: null,
    firstname: '',
    lastname: '',
    position: '',
    cost: 0,
    description: '',
    photo_path: null,
    photo: null,
    remove_photo: false,
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
    special_moves: [],
    contract: null,
})


const filteredPlayers = computed(() => {
    if (!searchQuery.value) return props.players
    const q = searchQuery.value.toLowerCase()

    return props.players.filter(
        p => `${p.firstname} ${p.lastname}`.toLowerCase().includes(q)
    )
})

// PREVIEW RESET
const revokePreview = () => {
    if (photoPreviewUrl.value) {
        URL.revokeObjectURL(photoPreviewUrl.value)
        photoPreviewUrl.value = null
    }
}

onBeforeUnmount(() => revokePreview())

// SELECT PLAYER
function selectPlayer(player) {
    revokePreview()
    form.contract = player.contracts?.[0] ?? null
    form.selectedPlayerId = player.id
    form.id = player.id
    form.firstname = player.firstname
    form.lastname = player.lastname
    form.position = player.position
    form.cost = player.cost
    form.description = player.description ?? ''
    form.special_moves = player.special_moves ?? []
    form.speed = player.speed ?? 0
    form.attack = player.attack ?? 0
    form.defense = player.defense ?? 0
    form.stamina = player.stamina ?? 0
    form.shot = player.shot ?? 0
    form.pass = player.pass ?? 0
    form.dribble = player.dribble ?? 0
    form.block = player.block ?? 0
    form.intercept = player.intercept ?? 0
    form.tackle = player.tackle ?? 0
    form.hand_save = player.hand_save ?? 0
    form.punch_save = player.punch_save ?? 0

    form.photo_path = player.photo_path ?? null
    form.photo = null
    form.remove_photo = false
    selectedPhotoName.value = ''
}

// PHOTO UPLOAD
function openPhotoPicker() {
    photoInput.value?.click()
}

function onPhotoChange(e) {
    const file = e.target.files?.[0] ?? null

    revokePreview()

    form.photo = file
    form.remove_photo = false
    selectedPhotoName.value = file ? file.name : ''

    if (file) photoPreviewUrl.value = URL.createObjectURL(file)
    e.target.value = ''
}

function addSpecialMove() {
    form.special_moves.push({
        key: '',
        label: '',
        short_label: '',
        mode: 'attack',
        base_action: 'shot',
        cooldown: 0,
        description: ''
    })
}

function removeSpecialMove(index) {
    form.special_moves.splice(index, 1)
}

function startNewContract() {
    if (!form.id) return // pas de joueur sélectionné => sécurité

    form.contract = {
        id: null,
        game_team_id: '',
        salary: 0,
        start_week: null,
        end_week: null,
        is_starter: false,
    }
}
function updateContract() {
    if (!form.contract) return

    contractProcessing.value = true

    const payload = {
        game_team_id: form.contract.game_team_id,
        salary: form.contract.salary,
        start_week: form.contract.start_week,
        end_week: form.contract.end_week,
        is_starter: form.contract.is_starter,
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            contractProcessing.value = false

            // 🎉 Message de succès
            contractCreatedMessage.value = form.contract.id
                ? 'Contrat mis à jour.'
                : 'Contrat créé avec succès.'

            // 🧹 Nettoie après 3 secondes
            setTimeout(() => {
                contractCreatedMessage.value = ''
            }, 3000)
        }
    }

    if (form.contract.id) {
        // UPDATE
        router.put(
            route('game-saves.contracts.update', {
                gameSave: props.gameSave.id,
                contract: form.contract.id,
            }),
            payload,
            options
        )
    } else {
        // CREATE
        router.post(
            route('game-saves.contracts.store', {
                gameSave: props.gameSave.id,
                player: form.id,
            }),
            payload,
            options
        )
    }
}

function deleteContract() {
    if (!form.contract || !form.contract.id) return
    if (!confirm('Supprimer ce contrat ?')) return

    router.delete(
        route('game-saves.contracts.destroy', {
            gameSave: props.gameSave.id,
            contract: form.contract.id
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                form.contract = null   // <= Reset immédiat
            }
        }
    )
}
``

function deletePlayer() {
    if (!form.id) return
    if (!confirm('Supprimer ce joueur ?')) return

    form.delete(
        route('game-saves.players.destroy', {
            gameSave: props.gameSave.id,
            player: form.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                if (props.players.length > 0) {
                    selectPlayer(props.players[0])
                } else {
                    form.reset()
                }
            }
        }
    )
}

function submit() {
    if (!form.id) return

    form.post(
        route('game-saves.players.update', {
            gameSave: props.gameSave.id,
            player: form.id
        }),
        {
            preserveScroll: true,
            forceFormData: true,
        }
    )
}

onMounted(() => {
    if (props.players.length > 0) {
        selectPlayer(props.players[0])
    }
})
</script>
