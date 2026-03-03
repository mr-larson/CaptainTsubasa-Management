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
        <div class="p-4 sm:ml-64">
            <H1>Édition des joueurs de la partie</H1>

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
                        <FormCol>
                            <InputLabel value="Techniques spéciales" />

                            <div class="flex flex-col gap-4 bg-slate-100 p-3 rounded">

                                <!-- Affichage dynamique des moves -->
                                <div
                                    v-for="(move, index) in form.special_moves"
                                    :key="index"
                                    class="p-3 rounded border bg-white shadow-sm"
                                >
                                    <h3 class="font-semibold text-sm mb-2">
                                        Technique #{{ index + 1 }}
                                    </h3>

                                    <!-- Label -->
                                    <div class="mb-2">
                                        <InputLabel value="Label" />
                                        <InputText
                                            v-model="move.label"
                                            class="mt-1 w-full"
                                        />
                                    </div>

                                    <!-- Key -->
                                    <div class="mb-2">
                                        <InputLabel value="Clé (identifiant)" />
                                        <InputText
                                            v-model="move.key"
                                            class="mt-1 w-full"
                                        />
                                    </div>

                                    <!-- Mode -->
                                    <div class="mb-2">
                                        <InputLabel value="Mode" />
                                        <select
                                            v-model="move.mode"
                                            class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                        >
                                            <option value="attack">Attaque</option>
                                            <option value="defense">Défense</option>
                                        </select>
                                    </div>

                                    <!-- Base action -->
                                    <div class="mb-2">
                                        <InputLabel value="Action de base" />
                                        <select
                                            v-model="move.base_action"
                                            class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                        >
                                            <option value="shot">Tir</option>
                                            <option value="pass">Passe</option>
                                            <option value="dribble">Dribble</option>
                                            <option value="block">Block</option>
                                            <option value="intercept">Interception</option>
                                            <option value="tackle">Tacle</option>
                                            <option value="hand_save">Arrêt main</option>
                                            <option value="punch_save">Arrêt poing</option>
                                        </select>
                                    </div>

                                    <!-- Cooldown -->
                                    <div class="mb-2">
                                        <InputLabel value="Cooldown (tours)" />
                                        <InputText
                                            type="number"
                                            v-model="move.cooldown"
                                            class="mt-1 w-full"
                                        />
                                    </div>

                                    <!-- Description -->
                                    <div class="mb-2">
                                        <InputLabel value="Description" />
                                        <textarea
                                            v-model="move.description"
                                            rows="3"
                                            class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                        ></textarea>
                                    </div>

                                    <!-- Bouton suppression -->
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 rounded-full bg-red-100 text-sm text-red-700 hover:bg-red-200"
                                        @click="removeSpecialMove(index)"
                                    >
                                        Supprimer cette technique
                                    </button>
                                </div>

                                <!-- Bouton ajout -->
                                <button
                                    type="button"
                                    class="px-3 py-1.5 rounded bg-teal-500 text-white text-sm hover:bg-teal-600"
                                    @click="addSpecialMove"
                                >
                                    Ajouter une technique spéciale
                                </button>
                            </div>
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

const form = useForm({
    id: null,
    firstname: '',
    lastname: '',
    position: '',
    cost: 0,
    description: '',
    photo_path: null,
    photo: null,
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
