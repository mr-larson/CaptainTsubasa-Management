<template>
    <Head :title="`Joueurs — Partie #${gameSave.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Joueurs de la partie</H2>
        </template>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-4 sm:p-6 max-w-8xl mx-auto flex flex-col gap-4">

            <!-- SOUS-NAVIGATION GESTION -->
            <div class="flex flex-wrap items-center gap-2">
                <Link :href="route('game-saves.Play', { gameSave: gameSave.id })"
                      class="text-xs font-semibold px-3 py-1.5 rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-all">
                    ← Retour à la partie
                </Link>
                <span class="w-px h-5 bg-slate-200"></span>
                <Link :href="route('game-saves.players.index', { gameSave: gameSave.id })"
                      class="text-xs font-bold px-3 py-1.5 rounded-full bg-teal-500 text-white shadow-sm">
                    Joueurs
                </Link>
                <Link :href="route('game-saves.teams.index', { gameSave: gameSave.id })"
                      class="text-xs font-bold px-3 py-1.5 rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-all">
                    Équipes
                </Link>
            </div>

            <div class="flex flex-col lg:flex-row gap-2">
                <!-- LISTE JOUEURS -->
                <div class="lg:w-72 shrink-0 border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-col gap-3 lg:max-h-[calc(100vh-13rem)]">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">Joueurs <span class="text-slate-400 font-normal">({{ players.length }})</span></h3>
                        <Link
                            :href="route('game-saves.players.create', { gameSave: gameSave.id })"
                            class="flex items-center justify-center h-7 px-2.5 rounded-full bg-teal-500 text-white text-xs font-bold hover:bg-teal-600 shadow-sm"
                            title="Créer un joueur"
                        >
                            + Ajouter
                        </Link>
                    </div>

                    <!-- SEARCH -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" aria-hidden="true" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <input
                            type="search"
                            v-model="searchQuery"
                            class="block w-full py-1.5 pl-9 pr-3 text-sm text-slate-700 bg-white border border-slate-200 rounded-full focus:ring-2 focus:ring-teal-300 focus:border-teal-300 focus:outline-none"
                            placeholder="Rechercher un joueur"
                        >
                    </div>

                    <!-- LISTE -->
                    <ul class="space-y-1 overflow-y-auto pr-1">
                        <li v-for="player in filteredPlayers" :key="player.id">
                            <a
                                href="#"
                                @click.prevent="selectPlayer(player)"
                                :class="form.selectedPlayerId === player.id
                                    ? 'bg-teal-500 text-white border-teal-500 shadow-sm'
                                    : 'bg-white text-slate-700 border-slate-200 hover:border-teal-300 hover:bg-teal-50'"
                                class="flex items-center justify-between gap-2 px-3 py-2 border rounded-lg transition-all"
                            >
                                <span class="truncate text-sm font-medium">{{ player.firstname }} {{ player.lastname }}</span>
                                <span v-if="player.position"
                                      :class="form.selectedPlayerId === player.id ? 'bg-white/20 text-white' : positionBadgeClass(player.position)"
                                      class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0">
                                    {{ positionShort(player.position) }}
                                </span>
                            </a>
                        </li>
                        <li v-if="filteredPlayers.length === 0" class="text-xs text-slate-400 italic px-2 py-3 text-center">
                            Aucun joueur trouvé.
                        </li>
                    </ul>
                </div>

                <!-- PANNEAU DROIT -->
                <div class="flex-1 flex flex-col gap-4 min-w-0">

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 md:p-8">
                <!-- Onglets de la fiche + infos du joueur -->
                <div class="flex items-end justify-between flex-wrap gap-3 mb-6 border-b border-slate-200">
                    <nav class="flex gap-1 -mb-px" role="tablist" aria-label="Édition du joueur">
                        <button v-for="t in panelTabs" :key="t.key" type="button" role="tab"
                                :aria-selected="activePanel === t.key"
                                @click="activePanel = t.key"
                                class="px-3 py-2 text-sm font-semibold border-b-2 transition-colors"
                                :class="activePanel === t.key
                                    ? 'border-teal-500 text-teal-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'">
                            {{ t.label }}
                            <span v-if="t.badge" class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                                  :class="activePanel === t.key ? 'bg-teal-50 text-teal-600' : 'bg-slate-100 text-slate-500'">
                                {{ t.badge }}
                            </span>
                        </button>
                    </nav>

                    <div class="flex items-center gap-2 pb-2">
                        <span v-if="form.position" :class="positionBadgeClass(form.position)"
                              class="text-[10px] font-bold px-2 py-0.5 rounded-full">
                            {{ form.position }}
                        </span>
                        <span v-if="overall" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                            OVR {{ overall }}
                        </span>
                    </div>
                </div>

                <!-- FICHE JOUEUR -->
                <FormContainer v-show="activePanel === 'player'" :custom-class="panelClass">
                    <form @submit.prevent="submit">

                        <!-- Aperçu visuel des stats -->
                        <div v-if="form.id" class="mb-4 border border-slate-200 rounded-xl bg-white p-3">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Aperçu des statistiques</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5">
                                <div v-for="stat in statBars" :key="stat.key" class="flex items-center gap-2">
                                    <span class="w-16 text-[11px] text-slate-500 shrink-0">{{ stat.short ?? stat.label }}</span>
                                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all" :class="stat.color"
                                             :style="{ width: Math.min(stat.value, 100) + '%' }">
                                        </div>
                                    </div>
                                    <span class="w-7 text-right text-[11px] font-bold text-slate-700">{{ stat.value }}</span>
                                </div>
                            </div>
                        </div>

                        <PlayerFormFields
                            :form="form"
                            layout="grid"
                            :photo-url="photoPath ? `/storage/${photoPath}` : null"
                        />

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

                <!-- COUPS SPÉCIAUX (enregistrés avec la fiche du joueur) -->
                <FormContainer v-show="activePanel === 'moves'" :custom-class="panelClass">
                    <form @submit.prevent="submit">
                        <SpecialMovesEditor v-model="form.special_moves" :errors="form.errors" />
                        <ButtonGroup>
                            <ButtonPrimary :disabled="form.processing || !form.id">
                                Mettre à jour
                            </ButtonPrimary>
                        </ButtonGroup>
                    </form>
                </FormContainer>

                <!-- CONTRAT -->
                <FormContainer v-show="activePanel === 'contract'" :custom-class="panelClass">
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
                    <!-- Si un contrat existe : formulaire d’édition -->
                    <form v-if="form.contract" @submit.prevent="updateContract" class="space-y-4">
                        <!-- Ligne 1 : Équipe + Salaire -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Équipe" />
                                <select
                                    v-model="form.contract.game_team_id"
                                    required
                                    class="mt-1 block w-full appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full px-3 py-1.5 leading-tight focus:outline-none focus:bg-white focus:border-slate-700"
                                >
                                    <option disabled value="">Choisir...</option>
                                    <option
                                        v-for="team in props.teams"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <InputLabel value="Salaire" class="block text-start"/>
                                <input
                                    type="number"
                                    v-model="form.contract.salary"
                                    class="mt-1 block w-full appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full px-3 py-1.5 leading-tight focus:outline-none focus:bg-white focus:border-slate-700"
                                />
                            </div>
                        </div>

                        <!-- Ligne 2 : Début / Fin -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Début (semaine)" />
                                <input
                                    type="number"
                                    v-model="form.contract.start_week"
                                    class="mt-1 block w-full appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full px-3 py-1.5 leading-tight focus:outline-none focus:bg-white focus:border-slate-700"
                                />
                            </div>

                            <div>
                                <InputLabel value="Fin (semaine)" />
                                <input
                                    type="number"
                                    v-model="form.contract.end_week"
                                    class="mt-1 block w-full appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full px-3 py-1.5 leading-tight focus:outline-none focus:bg-white focus:border-slate-700"
                                />
                            </div>
                        </div>

                        <!-- Ligne 3 : Titulaire -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Titulaire ?" />
                                <select
                                    v-model="form.contract.is_starter"
                                    class="mt-1 block w-full appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full px-3 py-1.5 leading-tight focus:outline-none focus:bg-white focus:border-slate-700"
                                >
                                    <option :value="true">Oui</option>
                                    <option :value="false">Non</option>
                                </select>
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
                        Aucun contrat défini pour ce joueur. Cliquez sur « + Ajouter » pour en créer un.
                    </div>
                </FormContainer>
            </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { confirmDialog } from '@/Composables/useDialog.js'
import { ref, computed, onMounted } from 'vue'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import H2 from '@/Components/H2.vue'
import FormContainer from '@/Components/FormContainer.vue'
import InputLabel from '@/Components/InputLabel.vue'
import ButtonGroup from '@/Components/ButtonGroup.vue'
import ButtonPrimary from '@/Components/ButtonPrimary.vue'
import ButtonDanger from '@/Components/ButtonDanger.vue'
import PlayerFormFields from '@/Components/PlayerFormFields.vue'
import SpecialMovesEditor from '@/Components/SpecialMovesEditor.vue'
import { PLAYER_STATS } from '@/Enums/playerStats.js'

const props = defineProps({
    gameSave: Object,
    players: Array,
    teams:   Array,
})

const searchQuery = ref('')
// Photo enregistrée : affichée seulement, jamais renvoyée au serveur.
const photoPath = ref(null)
const contractProcessing = ref(false)

// Onglets du panneau d'édition : fiche, coups spéciaux, contrat.
const activePanel = ref('player')
// Fond gris clair, comme la liste des joueurs à gauche.
const panelClass = 'p-4 sm:p-6 border border-slate-200 rounded-xl md:mx-6 bg-slate-50'
const panelTabs = computed(() => [
    { key: 'player',   label: 'Fiche joueur' },
    { key: 'moves',    label: 'Coups spéciaux', badge: form.special_moves?.length || null },
    { key: 'contract', label: 'Contrat',        badge: form.contract?.id ? '✓' : null },
])

const form = useForm({
    id: null,
    firstname: '',
    lastname: '',
    position: '',
    secondary_positions: [],
    cost: 0,
    description: '',
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
    heading: 0,
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

// BADGES & STATS VISUELS
const POSITION_BADGES = {
    Goalkeeper: 'bg-amber-50 text-amber-700',
    Defender:   'bg-blue-50 text-blue-700',
    Midfielder: 'bg-emerald-50 text-emerald-700',
    Forward:    'bg-rose-50 text-rose-700',
}
const POSITION_SHORT = {
    Goalkeeper: 'GB',
    Defender:   'DEF',
    Midfielder: 'MIL',
    Forward:    'ATT',
}
function positionBadgeClass(position) {
    return POSITION_BADGES[position] ?? 'bg-slate-100 text-slate-600'
}
function positionShort(position) {
    return POSITION_SHORT[position] ?? position
}

const statBars = computed(() => PLAYER_STATS
    .map(def => ({ ...def, value: Number(form[def.key] ?? 0) }))
    .filter(stat => stat.value > 0)
)

const overall = computed(() => {
    const values = PLAYER_STATS
        .map(def => Number(form[def.key] ?? 0))
        .filter(v => Number.isFinite(v) && v > 0)
    if (!values.length) return 0
    return Math.round(values.reduce((a, b) => a + b, 0) / values.length)
})

// Copie profonde : le formulaire ne doit pas modifier les props (sinon une
// saisie non enregistrée réapparaît comme sauvegardée en revenant sur le joueur).
const clone = (value) => (value == null ? value : JSON.parse(JSON.stringify(value)))

// SELECT PLAYER
function selectPlayer(player) {
    form.contract = clone(player.contracts?.[0] ?? null)
    form.selectedPlayerId = player.id
    form.id = player.id
    form.firstname = player.firstname
    form.lastname = player.lastname
    form.position = player.position
    form.secondary_positions = clone(player.secondary_positions ?? [])
    form.cost = player.cost
    form.description = player.description ?? ''
    form.special_moves = clone(player.special_moves ?? [])
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
    form.heading = player.heading ?? 0
    form.hand_save = player.hand_save ?? 0
    form.punch_save = player.punch_save ?? 0

    photoPath.value = player.photo_path ?? null
    form.photo = null // efface aussi l'aperçu du fichier choisi
    form.remove_photo = false
    form.clearErrors()
}

function startNewContract() {
    if (!form.id) return // pas de joueur sélectionné => sécurité

    form.contract = {
        id: null,
        game_team_id: '',
        salary: 0,
        start_week: props.gameSave.week ?? 1, // requis côté serveur
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

    // Succès et erreurs (422, contrat déjà actif…) s'affichent dans le toast
    // global ; on débloque les boutons dans tous les cas.
    const options = {
        preserveScroll: true,
        // Recharger le contrat enregistré (id créé, valeurs serveur) dans la copie locale.
        onSuccess: () => {
            const fresh = props.players.find(p => p.id === form.id)
            if (fresh) form.contract = clone(fresh.contracts?.[0] ?? null)
        },
        onFinish: () => { contractProcessing.value = false },
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

async function deleteContract() {
    if (!form.contract || !form.contract.id) return
    if (!(await confirmDialog({ title: 'Supprimer le contrat', message: 'Supprimer ce contrat ?', confirmLabel: 'Supprimer', danger: true }))) return

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

async function deletePlayer() {
    if (!form.id) return
    if (!(await confirmDialog({ title: 'Supprimer le joueur', message: 'Supprimer ce joueur ?', confirmLabel: 'Supprimer', danger: true }))) return

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

    form.transform(data => ({
        ...data,
        _method: 'PUT',
    })).post(
        route('game-saves.players.update', {
            gameSave: props.gameSave.id,
            player: form.id
        }),
        {
            preserveScroll: true,
            forceFormData: true,
            // Recharger le joueur enregistré (photo à jour, fichier remis à zéro).
            onSuccess: () => {
                const fresh = props.players.find(p => p.id === form.id)
                if (fresh) selectPlayer(fresh)
            },
        }
    )
}

onMounted(() => {
    if (props.players.length > 0) {
        selectPlayer(props.players[0])
    }
})
</script>
