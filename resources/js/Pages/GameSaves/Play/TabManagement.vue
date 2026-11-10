<script setup>
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { ref, reactive, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import GameRules from '@/Components/GameRules.vue';
import ClubStatusPanel from './components/ClubStatusPanel.vue';
import { fieldClass } from '@/Components/fieldStyles';

const props = defineProps({
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
    const { teams, teamId, isMine, ...panel } = props.clubStatus;
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
    { key: 'dataBase', label: 'Base de données' },
    ...(props.career ? [{ key: 'rules', label: 'Règles & Objectif' }] : []),
    { key: 'profil',   label: 'Profil' },
    { key: 'config',   label: 'Configuration' },
]);
const activeSection = ref(props.initialSection ?? (props.clubStatus ? 'status' : 'dataBase'));

watch(() => props.initialSection, (v) => { if (v) activeSection.value = v; });

const originLabels = {
    captain_tsubasa:     'Captain Tsubasa',
    ecole_des_champions: 'École des Champions',
    hungry_heart:        'Hungry Heart',
    blue_lock:           'Blue Lock',
    ao_ashi:             'Ao Ashi',
    original:            'Joueurs générés',
};

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
        match_stamina_cost: 5,
        rest_stamina_recovery: 10,
        match_max_turns: 30,
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
            captain_tsubasa: true,
            ecole_des_champions: false,
            hungry_heart: false,
            blue_lock: false,
            ao_ashi: false,
            original: true,
        },
        internationals_visible: false,
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
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-700">{{ stats.playersCount ?? '—' }}</span>
                        <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Joueurs</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-700">{{ stats.teamsCount ?? '—' }}</span>
                        <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Équipes</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-700">{{ stats.activeContractsCount ?? '—' }}</span>
                        <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Contrats actifs</span>
                    </div>
                    <button type="button"
                            class="border rounded-xl p-3 flex flex-col items-center gap-1 transition-all"
                            :class="(stats.freePlayersCount ?? 0) > 0
                                ? 'border-amber-200 bg-amber-50 hover:bg-amber-100'
                                : 'border-slate-200 bg-white'"
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

                <!-- Cartes Bonus -->
                <div class="border border-slate-200 rounded-lg bg-white p-4 flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="text-base">🃏</span> Cartes
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="config.bonus_cards_enabled"
                                   class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                            Bonus cards
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer"
                               :class="config.bonus_cards_enabled ? 'text-slate-700' : 'text-slate-400'">
                            <input type="checkbox" v-model="config.malus_cards_enabled"
                                   :disabled="!config.bonus_cards_enabled"
                                   class="rounded border-slate-300 text-teal-500 focus:ring-teal-400 disabled:opacity-40" />
                            Malus cards
                        </label>
                    </div>
                </div>

                <!-- Fatigue / Stamina -->
                <div class="border border-slate-200 rounded-lg bg-white p-4 flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="text-base">⚡</span> Fatigue & Stamina
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Coût stamina par match (joueurs titulaires)</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.match_stamina_cost"
                                       min="0" max="20" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ config.match_stamina_cost }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Récupération remplaçants (par semaine)</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.rest_stamina_recovery"
                                       min="0" max="30" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ config.rest_stamina_recovery }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Match -->
                <div class="border border-slate-200 rounded-lg bg-white p-4 flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="text-base">⚽</span> Match
                    </h4>
                    <div class="flex flex-col gap-1 max-w-xs">
                        <label class="text-xs text-slate-600">Nombre de tours par match</label>
                        <div class="flex items-center gap-2">
                            <input type="range" v-model.number="config.match_max_turns"
                                   min="10" max="80" step="5"
                                   class="flex-1 h-1.5 accent-teal-500" />
                            <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ config.match_max_turns }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="config.injury_on_foul"
                                   class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                            Risque de blessure sur faute
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="config.suspension_on_3_yellows"
                                   class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                            Suspension après 3 cartons jaunes
                        </label>
                    </div>
                </div>

                <!-- Entraînement -->
                <div class="border border-slate-200 rounded-lg bg-white p-4 flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="text-base">🏋️</span> Entraînement
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Sessions / semaine</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.training_max_per_week"
                                       min="1" max="10" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-700 w-4 text-right">{{ config.training_max_per_week }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Gain min</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.training_gain_min"
                                       min="0" max="10" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-700 w-4 text-right">{{ config.training_gain_min }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Gain max</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.training_gain_max"
                                       :min="Math.max(1, config.training_gain_min)" max="20" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-700 w-4 text-right">{{ config.training_gain_max }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Coût stamina</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.training_stamina_cost"
                                       min="0" max="15" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-700 w-4 text-right">{{ config.training_stamina_cost }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Stamina minimum requise pour s'entraîner</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.training_min_stamina"
                                       min="0" max="50" step="5"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ config.training_min_stamina }}</span>
                            </div>
                        </div>
                        <div v-for="(label, key) in { training_cost: 'Coût par séance simple', position_training_cost: 'Coût par formation de poste', special_training_cost: 'Coût par technique spéciale' }"
                             :key="key" class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">{{ label }} (<GameBanknote />)</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config[key]"
                                       min="0" max="2000" step="50"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-700 w-10 text-right">{{ config[key] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visibilité joueurs -->
                <div class="border border-slate-200 rounded-lg bg-white p-4 flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="text-base">👁</span> Visibilité des joueurs
                    </h4>
                    <p class="text-xs text-slate-500">Active ou désactive les groupes de joueurs par univers d'origine.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <label v-for="(enabled, key) in config.visible_origins" :key="key"
                               class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="config.visible_origins[key]"
                                   class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                            {{ originLabels[key] ?? key }}
                        </label>
                    </div>
                    <div class="border-t border-slate-100 pt-3 mt-1">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="config.internationals_visible"
                                   class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                            Joueurs internationaux visibles
                        </label>
                    </div>
                </div>

                <!-- Accès aux autres clubs -->
                <div class="border border-slate-200 rounded-lg bg-white p-4 flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="text-base">🔓</span> Accès aux autres clubs
                    </h4>
                    <label class="flex items-start gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" v-model="config.other_teams_editable"
                               class="mt-0.5 rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                        <span>
                            Modifier la composition des autres équipes
                            <span class="block text-xs text-slate-400">
                                Dans « Autres équipes » : formation, titulaires, remplacements, capitaine et numéros des clubs gérés par l'IA,
                                comme pour ton équipe. Les autres managers humains (hot-seat) restent protégés.
                            </span>
                        </span>
                    </label>
                </div>

                <!-- IA -->
                <div class="border border-slate-200 rounded-lg bg-white p-4 flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="text-base">🤖</span> Intelligence Artificielle
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="config.ai_transfers_enabled"
                                   class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                            Transferts IA
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="config.ai_training_enabled"
                                   class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                            Entraînement IA
                        </label>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
