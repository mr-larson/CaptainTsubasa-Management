<script setup>
import GameBanknote from '@/Components/GameBanknote.vue';
import UniverseSelector from '@/Components/UniverseSelector.vue';
import ConfigSection from '@/Components/ConfigSection.vue';
import ConfigRange from '@/Components/ConfigRange.vue';
import ConfigToggle from '@/Components/ConfigToggle.vue';
import { TRAINING_COST_FIELDS as trainingCostFields } from '@/Components/configFields.js';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { ref, reactive, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import GameRules from '@/Components/GameRules.vue';
import ClubStatusPanel from './components/ClubStatusPanel.vue';
import { fieldClass } from '@/Components/fieldStyles';

const props = defineProps({
    universeOptions: { type: Object, default: () => ({ counts: {}, countries: [] }) },
    gameSave: { type: Object, required: true },
    managementStats: { type: Object, default: () => ({}) },
    gameConfig: { type: Object, default: () => ({}) },
    career: { type: Object, default: null },
    initialSection: { type: String, default: null },
    // Statut du club : données de l'équipe pilotée (cf. ClubStatusPanel)
    clubStatus: { type: Object, default: null },
});

const emit = defineEmits(['open-player', 'select-club']);

// Sélecteur d'équipe du statut du club ; le reste est transmis au panneau.
const statusPanelProps = computed(() => {
    if (!props.clubStatus) return {};
    const { teams: _teams, teamId: _teamId, isMine: _isMine, ...panel } = props.clubStatus;
    return panel;
});

// ── Préférences d'affichage (compte utilisateur, toutes parties) ──
const page = usePage();
const pixelArt = computed(() => !!page.props.auth?.user?.pixel_art);
const savingPixelArt = ref(false);
const togglePixelArt = (event) => {
    savingPixelArt.value = true;
    router.patch(route('profile.preferences'), { pixel_art: event.target.checked }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => { savingPixelArt.value = false; },
    });
};

// Réactifs : restent à jour après une action tant que l'onglet est monté.
const stats = computed(() => props.managementStats ?? {});

const sections = computed(() => [
    ...(props.clubStatus ? [{ key: 'status', label: 'Statut du club' }] : []),
    ...(props.career ? [{ key: 'rules', label: 'Règles & Objectif' }] : []),
    { key: 'profil',   label: 'Profil' },
    { key: 'config',   label: 'Configuration' },
    { key: 'dataBase', label: 'Base de données' },
]);
const activeSection = ref(props.initialSection ?? (props.clubStatus ? 'status' : 'dataBase'));

watch(() => props.initialSection, (v) => { if (v) activeSection.value = v; });



const config = reactive({ ...props.gameConfig });

watch(() => props.gameConfig, (v) => Object.assign(config, v), { deep: true });
// Gain max jamais inférieur au gain min (le serveur le refuse aussi).
watch(() => config.training_gain_min, (min) => {
    if (config.training_gain_max < min) config.training_gain_max = Math.max(1, min);
});

const saving = ref(false);
const saved = ref(false);

function saveConfig() {
    saving.value = true;
    saved.value = false;
    router.put(
        route('game-saves.config.update', { gameSave: props.gameSave.id }),
        { ...config },
        {
            preserveScroll: true,
            onSuccess: () => { saved.value = true; setTimeout(() => saved.value = false, 2000); },
            onFinish: () => saving.value = false,
        },
    );
}

function resetDefaults() {
    const defaults = {
        bonus_cards_enabled: true,
        malus_cards_enabled: true,
        player_requests_enabled: true,
        match_stamina_cost: 5,
        rest_stamina_recovery: 10,
        match_max_turns: 30,
        match_max_substitutions: 5,
        match_criticals_enabled: true,
        injury_on_foul: true,
        suspension_on_3_yellows: true,
        training_max_per_week: 3,
        training_gain_min: 1,
        training_gain_max: 5,
        training_stamina_cost: 2,
        training_min_stamina: 10,
        training_cost: 150, position_training_cost: 200, special_training_cost: 300,
        ai_transfers_enabled: true,
        ai_training_enabled: true,
        visible_origins: {
            captain_tsubasa: true, captain_tsubasa_international: true, world_legends: false,
            ecole_des_champions: false,
            hungry_heart: false,
            blue_lock: false,
            ao_ashi: false,
            original: true,
        },
        internationals_visible: false,
        legends_countries: null,
        other_teams_editable: false,
    };
    Object.assign(config, defaults);
}
</script>

<template>
    <div class="flex-1 flex flex-col md:flex-row gap-4 overflow-y-auto md:overflow-hidden min-h-0">
        <!-- Sidebar -->
        <div class="md:w-1/5 shrink-0 border border-slate-200 rounded-lg bg-slate-50 p-3">
            <h3 class="text-md font-semibold text-slate-700 mb-2">Gestion</h3>
            <nav class="flex md:flex-col gap-1 md:gap-0 md:space-y-1 overflow-x-auto">
                <button v-for="sec in sections" :key="sec.key" type="button"
                        @click="activeSection = sec.key"
                        :class="['shrink-0 md:w-full text-left text-sm px-2 py-1 rounded whitespace-nowrap',
                        activeSection === sec.key ? 'bg-teal-100 text-slate-700' : 'bg-white hover:bg-slate-100 text-slate-700']">
                    {{ sec.label }}
                </button>
            </nav>
        </div>

        <!-- Panneau droit -->
        <div class="flex-1 min-w-0 border border-slate-200 rounded-lg bg-slate-50 p-3 sm:p-4 flex flex-col gap-4 md:min-h-0"
             :class="activeSection === 'status' && clubStatus ? 'md:overflow-hidden' : 'md:overflow-y-auto'">

            <!-- Statut du club (page par défaut) -->
            <div v-if="activeSection === 'status' && clubStatus" class="flex-1 flex flex-col gap-3 md:min-h-0">
                <div class="flex shrink-0 flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-700">Statut du club</h3>
                    <select :value="clubStatus.teamId" @change="emit('select-club', Number($event.target.value))"
                            aria-label="Club affiché"
                            class="w-full sm:w-64 pl-3 pr-9 py-1.5 cursor-pointer" :class="fieldClass({ shape: 'rect' })">
                        <option v-for="t in clubStatus.teams" :key="t.id" :value="t.id">
                            {{ t.name }}{{ t.mine ? ' (mon club)' : '' }}
                        </option>
                    </select>
                </div>
                <div class="md:min-h-0 md:overflow-y-auto md:pr-1">
                    <ClubStatusPanel v-bind="statusPanelProps" @open-player="p => emit('open-player', p)" />
                </div>
            </div>

            <!-- Base de données -->
            <div v-else-if="activeSection === 'dataBase'" class="flex-1 flex flex-col gap-3">
                <h3 class="text-lg font-semibold text-slate-700 mb-1">Base de données</h3>
                <p class="text-sm text-slate-600 mb-2">Crée, édite et assigne des joueurs, contrats et équipes.</p>

                <!-- Compteurs -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1 shadow-card">
                        <span class="text-2xl font-black text-slate-700">{{ stats.playersCount ?? '—' }}</span>
                        <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Joueurs</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1 shadow-card">
                        <span class="text-2xl font-black text-slate-700">{{ stats.teamsCount ?? '—' }}</span>
                        <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Équipes</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1 shadow-card">
                        <span class="text-2xl font-black text-slate-700">{{ stats.activeContractsCount ?? '—' }}</span>
                        <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Contrats actifs</span>
                    </div>
                    <button type="button"
                            class="border rounded-xl p-3 flex flex-col items-center gap-1 transition-all"
                            :class="(stats.freePlayersCount ?? 0) > 0
                                ? 'border-amber-200 bg-amber-50 hover:bg-amber-100'
                                : 'border-slate-300 bg-white'"
                            @click="router.get(route('game-saves.players.index', { gameSave: gameSave.id }))">
                        <span class="text-2xl font-black" :class="(stats.freePlayersCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-700'">
                            {{ stats.freePlayersCount ?? '—' }}
                        </span>
                        <span class="text-2xs font-bold uppercase tracking-wider"
                              :class="(stats.freePlayersCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-400'">
                            Joueurs libres
                        </span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                        <h4 class="text-sm font-semibold text-slate-700">Joueurs</h4>
                        <p class="text-xs text-slate-500">Crée, édite et supprime les joueurs de la base de données.</p>
                        <ButtonPrimary type="button" size="sm" class="mt-2" @click="router.get(route('game-saves.players.index', { gameSave: gameSave.id }))">
                            Gérer les joueurs
                        </ButtonPrimary>
                    </div>
                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                        <h4 class="text-sm font-semibold text-slate-700">Équipes</h4>
                        <p class="text-xs text-slate-500">Accéder à la gestion complète des équipes.</p>
                        <ButtonPrimary type="button" size="sm" class="mt-2" @click="router.get(route('game-saves.teams.index', { gameSave: gameSave.id }))">
                            Gérer les équipes
                        </ButtonPrimary>
                    </div>
                </div>
            </div>

            <!-- Profil -->
            <div v-else-if="activeSection === 'profil'" class="flex-1 flex flex-col gap-3">
                <h3 class="text-lg font-semibold text-slate-700 mb-1">Mon profil</h3>
                <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                    <h4 class="text-sm font-semibold text-slate-700">Édition de mon profil</h4>
                    <p class="text-xs text-slate-500">Accéder aux informations de ton compte (nom, email, mot de passe…).</p>
                    <ButtonPrimary type="button" size="sm" class="mt-2" @click="router.get(route('profile.edit'))">
                        Modifier mon profil
                    </ButtonPrimary>
                </div>
                <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                    <h4 class="text-sm font-semibold text-slate-700">Affichage</h4>
                    <label class="flex items-start gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" :checked="pixelArt" :disabled="savingPixelArt" @change="togglePixelArt"
                               class="mt-0.5 rounded border-slate-300 text-teal-500 focus:ring-teal-400 disabled:opacity-40" />
                        <span>
                            <span class="font-semibold">Mode Pixel</span>
                            <span class="block text-xs text-slate-500">
                                Photos des joueurs en pixel art. S'applique à toutes tes parties ; les joueurs sans photo gardent la silhouette.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Règles & Objectif -->
            <div v-else-if="activeSection === 'rules'" class="flex-1 flex flex-col gap-3 overflow-y-auto pr-1">
                <div>
                    <h3 class="text-lg font-semibold text-slate-700 mb-1">Règles & Objectif</h3>
                    <p class="text-sm text-slate-500">Comment fonctionne le jeu, les matchs, la draft et tes objectifs de carrière.</p>
                </div>
                <GameRules :career="career" />
            </div>

            <!-- Configuration -->
            <div v-else-if="activeSection === 'config'" class="flex-1 flex flex-col gap-3 overflow-y-auto">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-700">Configuration du jeu</h3>
                        <p class="text-sm text-slate-500">Paramètres appliqués à cette sauvegarde.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <ButtonSecondary size="sm" @click="resetDefaults">
                            Réinitialiser
                        </ButtonSecondary>
                        <ButtonPrimary type="button" size="sm" :tone="saved ? 'emerald' : 'teal'" @click="saveConfig" :disabled="saving">
                            {{ saving ? 'Enregistrement…' : saved ? 'Enregistré !' : 'Enregistrer' }}
                        </ButtonPrimary>
                    </div>
                </div>

                <ConfigSection icon="🃏" title="Cartes">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <ConfigToggle v-model="config.bonus_cards_enabled">Cartes bonus</ConfigToggle>
                        <ConfigToggle v-model="config.malus_cards_enabled" :disabled="!config.bonus_cards_enabled">Cartes malus</ConfigToggle>
                        <ConfigToggle v-model="config.player_requests_enabled">Demandes des joueurs</ConfigToggle>
                    </div>
                </ConfigSection>

                <ConfigSection icon="⚡" title="Fatigue & Endurance">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <ConfigRange v-model="config.match_stamina_cost" label="Coût en endurance par match (joueurs titulaires)" :max="20" />
                        <ConfigRange v-model="config.rest_stamina_recovery" label="Récupération remplaçants (par semaine)" :max="30" />
                    </div>
                </ConfigSection>

                <ConfigSection icon="⚽" title="Match">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="flex flex-col gap-3">
                            <ConfigRange v-model="config.match_max_turns" label="Nombre de tours par match" :min="10" :max="80" :step="5" class="max-w-xs" />
                            <ConfigRange v-model="config.match_max_substitutions" id="management-match-substitutions" label="Remplacements par équipe" :max="11" class="max-w-xs" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <ConfigToggle v-model="config.match_criticals_enabled">Activer les critiques</ConfigToggle>
                            <ConfigToggle v-model="config.injury_on_foul">Risque de blessure sur faute</ConfigToggle>
                            <ConfigToggle v-model="config.suspension_on_3_yellows">Suspension après 3 cartons jaunes</ConfigToggle>
                        </div>
                    </div>
                </ConfigSection>

                <ConfigSection icon="🏋️" title="Entraînement">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <ConfigRange v-model="config.training_max_per_week" label="Sessions / semaine" :min="1" :max="10" value-width="w-4" />
                        <ConfigRange v-model="config.training_gain_min" label="Gain min" :max="10" value-width="w-4" />
                        <ConfigRange v-model="config.training_gain_max" label="Gain max" :min="Math.max(1, config.training_gain_min)" :max="20" value-width="w-4" />
                        <ConfigRange v-model="config.training_stamina_cost" label="Coût en endurance" :max="15" value-width="w-4" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <ConfigRange v-model="config.training_min_stamina" label="Endurance minimum requise pour s'entraîner" :max="50" :step="5" />
                        <ConfigRange v-for="(label, key) in trainingCostFields" :key="key" v-model="config[key]"
                                     :max="2000" :step="50" value-width="w-10">
                            {{ label }} (<GameBanknote />)
                        </ConfigRange>
                    </div>
                </ConfigSection>

                <UniverseSelector :config="config" :options="universeOptions" in-game />

                <ConfigSection icon="🔓" title="Accès aux autres clubs">
                    <ConfigToggle v-model="config.other_teams_editable">
                        Modifier la composition des autres équipes
                        <template #hint>
                            Dans « Autres équipes » : formation, titulaires, remplacements, capitaine et numéros des clubs gérés par l'IA,
                            comme pour ton équipe. Les autres managers humains (hot-seat) restent protégés.
                        </template>
                    </ConfigToggle>
                </ConfigSection>

                <ConfigSection icon="🤖" title="Intelligence Artificielle">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <ConfigToggle v-model="config.ai_transfers_enabled">Transferts IA</ConfigToggle>
                        <ConfigToggle v-model="config.ai_training_enabled">Entraînement IA</ConfigToggle>
                    </div>
                </ConfigSection>

            </div>
        </div>
    </div>
</template>
