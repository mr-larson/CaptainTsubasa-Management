<template>
    <Head :title="`Joueurs — Partie #${gameSave.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Joueurs de la partie</H2>
        </template>

        <div class="p-4 sm:p-6 max-w-8xl mx-auto flex flex-col gap-4">
            <EditorSubNav
                :back="{ href: route('game-saves.Play', { gameSave: gameSave.id }), label: 'Retour à la partie' }"
                :links="[
                    { href: route('game-saves.players.index', { gameSave: gameSave.id }), label: 'Joueurs', active: true },
                    { href: route('game-saves.teams.index', { gameSave: gameSave.id }), label: 'Équipes' },
                ]"
            />

            <EntityEditor
                title="Joueurs"
                :items="players"
                :selected-id="editor.selectedId.value"
                :creating="editor.creating.value"
                :search-text="p => `${p.firstname} ${p.lastname}`"
                search-placeholder="Rechercher un joueur"
                empty-label="Aucun joueur trouvé."
                add-title="Créer un joueur"
                :tabs="panelTabs"
                v-model:active-tab="activePanel"
                @select="editor.select"
                @create="editor.startCreating"
            >
                <template #item="{ item, selected }">
                    <span class="flex-1 truncate text-sm font-medium">{{ item.firstname }} {{ item.lastname }}</span>
                    <span v-if="item.position"
                          :class="selected ? 'bg-white/20 text-white' : positionBadgeClass(item.position)"
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0">
                        {{ positionShort(item.position) }}
                    </span>
                </template>

                <template #badges>
                    <span v-if="form.position" :class="positionBadgeClass(form.position)"
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full">
                        {{ form.position }}
                    </span>
                    <span v-if="overall" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        OVR {{ overall }}
                    </span>
                </template>

                <!-- FICHE JOUEUR -->
                <div v-show="activePanel === 'player'" class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <PlayerStatsPreview v-if="form.id" :stats="form" />

                        <PlayerFormFields
                            :form="form"
                            layout="grid"
                            :photo-url="photoPath ? `/storage/${photoPath}` : null"
                        />

                        <ButtonGroup>
                            <ButtonPrimary :disabled="form.processing">
                                {{ editor.creating.value ? 'Créer le joueur' : 'Mettre à jour' }}
                            </ButtonPrimary>
                            <ButtonDanger v-if="!editor.creating.value" type="button" class="w-36"
                                          :disabled="form.processing || !form.id" @click="deletePlayer">
                                Supprimer
                            </ButtonDanger>
                        </ButtonGroup>
                    </form>
                </div>

                <!-- COUPS SPÉCIAUX (enregistrés avec la fiche du joueur) -->
                <div v-show="activePanel === 'moves'" class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <SpecialMovesEditor v-model="form.special_moves" :errors="form.errors" />
                        <ButtonGroup>
                            <ButtonPrimary :disabled="form.processing">
                                {{ editor.creating.value ? 'Créer le joueur' : 'Mettre à jour' }}
                            </ButtonPrimary>
                        </ButtonGroup>
                    </form>
                </div>

                <!-- CONTRAT -->
                <div v-show="activePanel === 'contract'" class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">Contrat</h2>
                        <button v-if="!contract" type="button" @click="startNewContract"
                                class="flex items-center justify-center h-9 px-3 rounded-full bg-teal-500 text-white text-sm font-medium hover:bg-teal-600 shadow-md">
                            + Ajouter
                        </button>
                    </div>

                    <form v-if="contract" @submit.prevent="saveContract" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Équipe" />
                                <select v-model="contract.game_team_id" required :class="fieldClass">
                                    <option disabled value="">Choisir...</option>
                                    <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Salaire" />
                                <input type="number" v-model="contract.salary" :class="fieldClass" />
                            </div>
                            <div>
                                <InputLabel value="Début (semaine)" />
                                <input type="number" v-model="contract.start_week" :class="fieldClass" />
                            </div>
                            <div>
                                <InputLabel value="Fin (semaine)" />
                                <input type="number" v-model="contract.end_week" :class="fieldClass" />
                            </div>
                            <div>
                                <InputLabel value="Titulaire ?" />
                                <select v-model="contract.is_starter" :class="fieldClass">
                                    <option :value="true">Oui</option>
                                    <option :value="false">Non</option>
                                </select>
                            </div>
                        </div>

                        <ButtonGroup>
                            <ButtonPrimary type="submit" :disabled="contractProcessing">
                                {{ contract.id ? 'Mettre à jour' : 'Créer' }}
                            </ButtonPrimary>
                            <ButtonDanger v-if="contract.id" type="button" class="w-36"
                                          :disabled="contractProcessing" @click="deleteContract">
                                Supprimer
                            </ButtonDanger>
                        </ButtonGroup>
                    </form>

                    <p v-else class="text-sm text-slate-500 italic">
                        Aucun contrat défini pour ce joueur. Cliquez sur « + Ajouter » pour en créer un.
                    </p>
                </div>
            </EntityEditor>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { confirmDialog } from '@/Composables/useDialog.js'
import { ref, computed } from 'vue'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import H2 from '@/Components/H2.vue'
import EntityEditor from '@/Components/EntityEditor.vue'
import EditorSubNav from '@/Components/EditorSubNav.vue'
import PlayerStatsPreview from '@/Components/PlayerStatsPreview.vue'
import InputLabel from '@/Components/InputLabel.vue'
import ButtonGroup from '@/Components/ButtonGroup.vue'
import ButtonPrimary from '@/Components/ButtonPrimary.vue'
import ButtonDanger from '@/Components/ButtonDanger.vue'
import PlayerFormFields from '@/Components/PlayerFormFields.vue'
import SpecialMovesEditor from '@/Components/SpecialMovesEditor.vue'
import { PLAYER_STATS } from '@/Enums/playerStats.js'
import { positionBadgeClass, positionShort } from '@/Enums/positionBadges.js'
import { useEntityEditor, clone } from '@/Composables/useEntityEditor.js'

const props = defineProps({
    gameSave: Object,
    players: Array,
    teams:   Array,
    // Joueur à sélectionner à l'ouverture (ex. juste après sa création).
    selectedId: { type: Number, default: null },
    // Ouvre directement une fiche vide (ancienne URL /players/create).
    startCreate: { type: Boolean, default: false },
})

// Valeurs d'une fiche vide.
const BLANK = {
    id: null, firstname: '', lastname: '', position: '', secondary_positions: [],
    cost: 0, description: '', photo: null, remove_photo: false,
    speed: 0, attack: 0, defense: 0, stamina: 0, shot: 0, pass: 0, dribble: 0,
    block: 0, intercept: 0, tackle: 0, heading: 0, hand_save: 0, punch_save: 0,
    special_moves: [],
}

const form = useForm(clone(BLANK))
// Photo enregistrée : affichée seulement, jamais renvoyée au serveur.
const photoPath = ref(null)
// Contrat édité : copie locale, enregistrée par ses propres routes.
const contract = ref(null)
const contractProcessing = ref(false)
const fieldClass = 'mt-1 block w-full text-sm text-slate-900 bg-white border border-slate-300 rounded-full px-3 py-1.5 focus:outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-200'

const activePanel = ref('player')

const editor = useEntityEditor({
    form,
    items: () => props.players,
    blank: BLANK,
    entityLabel: 'Le nouveau joueur',
    initial: () => ({ selectedId: props.selectedId, startCreate: props.startCreate }),
    apply(player) {
        Object.assign(form, {
            id: player.id,
            firstname: player.firstname,
            lastname: player.lastname,
            position: player.position,
            secondary_positions: clone(player.secondary_positions ?? []),
            cost: player.cost,
            description: player.description ?? '',
            special_moves: clone(player.special_moves ?? []),
            photo: null, // efface aussi l'aperçu du fichier choisi
            remove_photo: false,
            ...Object.fromEntries(PLAYER_STATS.map(({ key }) => [key, player[key] ?? 0])),
        })
        photoPath.value = player.photo_path ?? null
        contract.value = clone(player.contracts?.[0] ?? null)
    },
    onCreate() {
        photoPath.value = null
        contract.value = null
        if (activePanel.value === 'contract') activePanel.value = 'player'
    },
})

const panelTabs = computed(() => [
    { key: 'player',   label: 'Fiche joueur' },
    { key: 'moves',    label: 'Coups spéciaux', badge: form.special_moves?.length || null },
    // Un contrat se rattache à un joueur existant : disponible après la création.
    { key: 'contract', label: 'Contrat', badge: contract.value?.id ? '✓' : null,
      disabled: editor.creating.value, disabledHint: "Crée d'abord le joueur" },
])

const overall = computed(() => {
    const values = PLAYER_STATS.map(def => Number(form[def.key] ?? 0)).filter(v => Number.isFinite(v) && v > 0)
    return values.length ? Math.round(values.reduce((a, b) => a + b, 0) / values.length) : 0
})

// ---------- Joueur ----------
function submit() {
    if (editor.creating.value) {
        // transform() est mémorisé par le formulaire : on repart des seules données.
        form.transform(({ id: _id, ...data }) => data).post(
            route('game-saves.players.store', { gameSave: props.gameSave.id }),
            {
                preserveScroll: true,
                forceFormData: true,
                // Le serveur renvoie l'id du joueur créé : on l'ouvre en édition.
                onSuccess: () => editor.reload(props.selectedId),
            },
        )
        return
    }
    if (!form.id) return

    form.transform(data => ({ ...data, _method: 'PUT' })).post(
        route('game-saves.players.update', { gameSave: props.gameSave.id, player: form.id }),
        {
            preserveScroll: true,
            forceFormData: true,
            // Recharger le joueur enregistré (photo à jour, fichier remis à zéro).
            onSuccess: () => editor.reload(form.id),
        },
    )
}

async function deletePlayer() {
    if (!form.id) return
    if (!(await confirmDialog({ title: 'Supprimer le joueur', message: 'Supprimer ce joueur ?', confirmLabel: 'Supprimer', danger: true }))) return

    form.delete(route('game-saves.players.destroy', { gameSave: props.gameSave.id, player: form.id }), {
        preserveScroll: true,
        onSuccess: () => editor.afterDelete(),
    })
}

// ---------- Contrat ----------
function startNewContract() {
    if (!form.id) return
    contract.value = {
        id: null,
        game_team_id: '',
        salary: 0,
        start_week: props.gameSave.week ?? 1, // requis côté serveur
        end_week: null,
        is_starter: false,
    }
}

function saveContract() {
    if (!contract.value) return
    contractProcessing.value = true

    const { game_team_id, salary, start_week, end_week, is_starter } = contract.value
    const payload = { game_team_id, salary, start_week, end_week, is_starter }
    // Succès et erreurs s'affichent dans le toast global.
    const options = {
        preserveScroll: true,
        // Recharger seulement le contrat (une saisie en cours sur la fiche est conservée).
        onSuccess: () => {
            const fresh = props.players.find(p => p.id === form.id)
            contract.value = clone(fresh?.contracts?.[0] ?? null)
        },
        onFinish: () => { contractProcessing.value = false },
    }

    if (contract.value.id) {
        router.put(route('game-saves.contracts.update', { gameSave: props.gameSave.id, contract: contract.value.id }), payload, options)
    } else {
        router.post(route('game-saves.contracts.store', { gameSave: props.gameSave.id, player: form.id }), payload, options)
    }
}

async function deleteContract() {
    if (!contract.value?.id) return
    if (!(await confirmDialog({ title: 'Supprimer le contrat', message: 'Supprimer ce contrat ?', confirmLabel: 'Supprimer', danger: true }))) return

    router.delete(route('game-saves.contracts.destroy', { gameSave: props.gameSave.id, contract: contract.value.id }), {
        preserveScroll: true,
        onSuccess: () => { contract.value = null },
    })
}
</script>
