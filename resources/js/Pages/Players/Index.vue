<template>
    <Head title="Joueurs" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Joueurs de la base</H2>
        </template>

        <div class="p-4 sm:p-6 max-w-8xl mx-auto flex flex-col gap-4">
            <EditorSubNav
                :back="{ href: route('dataBaseMenu'), label: 'Base de données' }"
                :links="[
                    { href: route('players.index'), label: 'Joueurs', active: true },
                    { href: route('teams.index'), label: 'Équipes' },
                    { href: route('contracts.index'), label: 'Contrats' },
                ]"
            />

            <EntityEditor
                title="Joueurs"
                :items="players"
                :selected-id="editor.selectedId.value"
                :creating="editor.creating.value"
                :search-text="p => `${p.firstname ?? ''} ${p.lastname ?? ''}`"
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
                          class="text-2xs font-bold px-2 py-0.5 rounded-full shrink-0">
                        {{ positionShort(item.position) }}
                    </span>
                </template>

                <template #badges>
                    <span v-if="form.position" :class="positionBadgeClass(form.position)"
                          class="text-2xs font-bold px-2 py-0.5 rounded-full">
                        {{ positionLabels[form.position] ?? form.position }}
                    </span>
                    <span v-if="overall" class="text-2xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        OVR {{ overall }}
                    </span>
                </template>

                <!-- FICHE JOUEUR -->
                <div v-show="activePanel === 'player'" class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <PlayerStatsPreview v-if="form.id" :stats="form.stats" />

                        <PlayerFormFields
                            :form="form"
                            layout="grid"
                            stats-key="stats"
                            :positions="positions"
                            :position-labels="positionLabels"
                            show-age
                            :photo-url="photoPath ? `/storage/${photoPath}` : null"
                        />

                        <ButtonGroup>
                            <ButtonPrimary :disabled="form.processing">
                                {{ editor.creating.value ? 'Créer le joueur' : 'Mettre à jour' }}
                            </ButtonPrimary>
                            <ButtonDanger v-if="!editor.creating.value" class="w-36" type="button"
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

                <!-- CONTRAT (contrats de la base : équipe de départ du joueur) -->
                <div v-show="activePanel === 'contract'" class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-700">Contrat</h2>
                        <button v-if="!contract" type="button" @click="startNewContract"
                                class="flex items-center justify-center h-9 px-3 rounded-full bg-teal-500 text-white text-sm font-medium hover:bg-teal-600 shadow-md">
                            + Ajouter
                        </button>
                    </div>

                    <form v-if="contract" @submit.prevent="saveContract" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Équipe" />
                                <select v-model="contract.team_id" required :class="fieldClass">
                                    <option disabled value="">Choisir...</option>
                                    <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Salaire (€ / match)" />
                                <input type="number" min="0" v-model="contract.salary" :class="fieldClass" />
                            </div>
                            <div>
                                <InputLabel value="Durée (matchs)" />
                                <input type="number" min="1" v-model="contract.matches_total" :class="fieldClass" />
                            </div>
                            <div>
                                <InputLabel value="Matchs joués" />
                                <input type="number" min="0" v-model="contract.matches_played" :class="fieldClass" />
                            </div>
                        </div>

                        <ButtonGroup>
                            <ButtonPrimary type="submit" :disabled="contractForm.processing">
                                {{ contract.id ? 'Mettre à jour' : 'Créer' }}
                            </ButtonPrimary>
                            <ButtonDanger v-if="contract.id" type="button" class="w-36"
                                          :disabled="contractForm.processing" @click="deleteContract">
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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { confirmDialog } from '@/Composables/useDialog.js';
import { ref, computed } from 'vue';

import H2 from '@/Components/H2.vue';
import EntityEditor from '@/Components/EntityEditor.vue';
import EditorSubNav from '@/Components/EditorSubNav.vue';
import PlayerStatsPreview from '@/Components/PlayerStatsPreview.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import PlayerFormFields from '@/Components/PlayerFormFields.vue';
import SpecialMovesEditor from '@/Components/SpecialMovesEditor.vue';
import { PLAYER_STATS, defaultPlayerStats } from '@/Enums/playerStats.js';
import { positionBadgeClass, positionShort } from '@/Enums/positionBadges.js';
import { useEntityEditor, clone } from '@/Composables/useEntityEditor.js';

const props = defineProps({
    players: { type: Array, required: true },
    teams: { type: Array, required: true },
    positions: { type: Array, required: true },
    positionLabels: { type: Object, required: true },
    // Joueur à sélectionner à l'ouverture (ex. juste après sa création).
    selectedId: { type: Number, default: null },
    // Ouvre directement une fiche vide (ancienne URL /players/create).
    startCreate: { type: Boolean, default: false },
});

const BLANK = {
    id: null,
    firstname: '',
    lastname: '',
    age: '',
    position: '',
    secondary_positions: [],
    cost: '',
    description: '',
    photo: null,
    stats: defaultPlayerStats({ heading: 15 }), // défaut du modèle Player
    special_moves: [],
};

const form = useForm(clone(BLANK));
// Photo enregistrée : affichée seulement, jamais renvoyée au serveur.
const photoPath = ref(null);
// Contrat édité : copie locale, enregistrée par les routes des contrats.
const contract = ref(null);
const contractForm = useForm({});
const fieldClass = 'mt-1 block w-full text-sm text-slate-700 bg-white border border-slate-300 rounded-full px-3 py-1.5 focus:outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-200';

const activePanel = ref('player');

const editor = useEntityEditor({
    form,
    items: () => props.players,
    blank: BLANK,
    entityLabel: 'Le nouveau joueur',
    initial: () => ({ selectedId: props.selectedId, startCreate: props.startCreate }),
    apply(player) {
        const stats = player.stats || {};
        Object.assign(form, {
            id: player.id,
            firstname: player.firstname ?? '',
            lastname: player.lastname ?? '',
            age: player.age ?? '',
            position: player.position ?? '',
            secondary_positions: clone(player.secondary_positions ?? []),
            cost: player.cost ?? '',
            description: player.description ?? '',
            photo: null, // efface aussi l'aperçu du fichier choisi
            // heading absent = 15, défaut du modèle Player
            stats: Object.fromEntries(PLAYER_STATS.map(({ key }) => [key, Number(stats[key] ?? (key === 'heading' ? 15 : 0))])),
            special_moves: clone(player.special_moves ?? []),
        });
        photoPath.value = player.photo_path ?? null;
        contract.value = clone(player.contracts?.[0] ?? null);
    },
    onCreate() {
        photoPath.value = null;
        contract.value = null;
        if (activePanel.value === 'contract') activePanel.value = 'player';
    },
});

const panelTabs = computed(() => [
    { key: 'player',   label: 'Fiche joueur' },
    { key: 'moves',    label: 'Coups spéciaux', badge: form.special_moves?.length || null },
    // Un contrat se rattache à un joueur existant : disponible après la création.
    { key: 'contract', label: 'Contrat', badge: contract.value?.id ? '✓' : null,
      disabled: editor.creating.value, disabledHint: "Crée d'abord le joueur" },
]);

const overall = computed(() => {
    const values = PLAYER_STATS.map(def => Number(form.stats?.[def.key] ?? 0)).filter(v => Number.isFinite(v) && v > 0);
    return values.length ? Math.round(values.reduce((a, b) => a + b, 0) / values.length) : 0;
});

// ---------- Joueur ----------
function submit() {
    const creating = editor.creating.value;
    if (!creating && !form.id) return;

    form.transform(({ id: _id, ...data }) => data).post(
        creating ? route('players.store') : route('players.update', form.id),
        {
            preserveScroll: true,
            forceFormData: true, // nécessaire pour envoyer form.photo (File)
            // Création : le serveur renvoie l'id du joueur créé. Mise à jour : joueur rechargé.
            onSuccess: () => editor.reload(creating ? props.selectedId : form.id),
        },
    );
}

async function deletePlayer() {
    if (!form.id) return;
    if (!(await confirmDialog({ title: 'Supprimer le joueur', message: 'Voulez-vous vraiment supprimer ce joueur ?', confirmLabel: 'Supprimer', danger: true }))) return;

    form.delete(route('players.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => editor.afterDelete(),
    });
}

// ---------- Contrat ----------
function startNewContract() {
    if (!form.id) return;
    contract.value = { id: null, team_id: '', salary: Number(form.cost) || 0, matches_total: 10, matches_played: 0 };
}

function reloadContract() {
    const fresh = props.players.find(p => p.id === form.id);
    contract.value = clone(fresh?.contracts?.[0] ?? null);
}

function saveContract() {
    if (!contract.value) return;
    const { id, team_id, salary, matches_total, matches_played } = contract.value;

    contractForm
        .transform(() => ({ player_id: form.id, team_id, salary, matches_total, matches_played }))
        .post(id ? route('contracts.update', id) : route('contracts.store'), {
            preserveScroll: true,
            // Recharger seulement le contrat (une saisie en cours sur la fiche est conservée).
            onSuccess: reloadContract,
        });
}

async function deleteContract() {
    if (!contract.value?.id) return;
    if (!(await confirmDialog({ title: 'Supprimer le contrat', message: 'Supprimer ce contrat ?', confirmLabel: 'Supprimer', danger: true }))) return;

    contractForm.delete(route('contracts.destroy', contract.value.id), {
        preserveScroll: true,
        onSuccess: reloadContract,
    });
}
</script>
