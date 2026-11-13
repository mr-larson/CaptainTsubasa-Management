<script setup>
import { fieldClass, TEXTAREA_CLASS } from '@/Components/fieldStyles';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import H2 from '@/Components/H2.vue';
import GameRules from '@/Components/GameRules.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    aiDraftBudget: { type: Number, required: true },
    aiStartBudget: { type: Number, required: true },
    humanStartBudgets: { type: Object, required: true },
    draftBudgets: { type: Object, required: true },
    periodPackages: { type: Array, default: () => [] },
});

const form = useForm({
    label: '',
    period: 'college',
    game_mode: 'prebuilt',
    competition_type: 'college_league',
    game_config: null,
    period_package_id: null,
});

// Périodes importées proposées dans le sélecteur : aucune en mode Draft (le
// draft pioche dans le pool global de joueurs libres, incompatible avec un
// roster de package pré-assigné).
const availablePackages = computed(() => form.game_mode === 'draft' ? [] : props.periodPackages);

// Exporter la période actuellement choisie (modèle Collège par défaut, ou le
// package sélectionné) — lien direct, pas de visite Inertia (téléchargement).
const exportHref = computed(() =>
    form.period_package_id
        ? route('period-packages.export', form.period_package_id)
        : route('period-packages.export-template')
);

// Import inline : reste sur cet écran (redirect_to=create), la période
// importée devient immédiatement sélectionnable dans le menu ci-dessus.
const showImportModal = ref(false);
const importForm = useForm({
    name: '',
    description: '',
    is_shared: false,
    zip: null,
    redirect_to: 'create',
});

function onImportZipChange(e) {
    importForm.zip = e.target.files?.[0] ?? null;
}

function submitImport() {
    importForm.post(route('period-packages.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
}

const showConfig = ref(false);
const showRules = ref(false);
const showConfigHint = ref(true);

const originLabels = {
    captain_tsubasa:     'Captain Tsubasa',
    ecole_des_champions: 'École des Champions',
    hungry_heart:        'Hungry Heart',
    blue_lock:           'Blue Lock',
    ao_ashi:             'Ao Ashi',
    original:            'Joueurs générés',
};

const careerLevels = [
    { key: 'none',     icon: '∞',  label: 'Bac à sable',  desc: 'Aucun objectif. Les saisons s\'enchaînent à l\'infini. Budget 2500 billets, revenus pleins.' },
    { key: 'survival', icon: '🛡️', label: 'Survie',       desc: 'Petit club. La direction tolère un objectif modeste mais reste impatiente. 1 titre pour gagner. Budget 1500 billets, 500 billets/sem.' },
    { key: 'standard', icon: '⚖️', label: 'Standard',     desc: 'Objectif calé sur la force de l\'effectif. 2 titres pour gagner. Budget 1000 billets, 450 billets/sem.' },
    { key: 'conquest', icon: '👑', label: 'Conquête',     desc: 'Gros club. Le board exige le haut du tableau ET tient les cordons de la bourse. 3 titres pour gagner. Budget 500 billets, 350 billets/sem.' },
];

const config = reactive({
    career_difficulty: 'standard',
    season_challenges_enabled: true,
    bonus_cards_enabled: true,
    malus_cards_enabled: true,
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
    initial_morale_random: true, initial_stamina_random: true, initial_speed_random: true, luck_random: true,
    human_start_budget: props.humanStartBudgets.standard, ai_start_budget: props.aiStartBudget,
    draft_budget: props.draftBudgets.standard, ai_draft_budget: props.aiDraftBudget,
    visible_origins: {
        captain_tsubasa: true,
        ecole_des_champions: false,
        hungry_heart: false,
        blue_lock: false,
        ao_ashi: false,
        original: true,
    },
    internationals_visible: false,
});

const budgetFields = [
    { key: 'human_start_budget', label: 'Budget de départ par équipe humaine', min: 0 },
    { key: 'ai_start_budget', label: 'Budget de départ par équipe IA', min: 0 },
    { key: 'draft_budget', label: 'Budget de draft par équipe humaine', min: 3000 },
    { key: 'ai_draft_budget', label: 'Budget de draft par équipe IA', min: 3000 },
];

function selectDifficulty(difficulty) {
    if (config.draft_budget === props.draftBudgets[config.career_difficulty]) {
        config.draft_budget = props.draftBudgets[difficulty];
    }
    if (config.human_start_budget === props.humanStartBudgets[config.career_difficulty]) {
        config.human_start_budget = props.humanStartBudgets[difficulty];
    }
    config.career_difficulty = difficulty;
}

function resetDefaults() {
    Object.assign(config, {
        career_difficulty: 'standard',
        season_challenges_enabled: true,
        bonus_cards_enabled: true, malus_cards_enabled: true,
        match_stamina_cost: 5, rest_stamina_recovery: 10, match_max_turns: 30, match_max_substitutions: 5,
        match_criticals_enabled: true,
        injury_on_foul: true, suspension_on_3_yellows: true,
        training_max_per_week: 3, training_gain_min: 1, training_gain_max: 5,
        training_stamina_cost: 2, training_min_stamina: 10, training_cost: 150, position_training_cost: 200, special_training_cost: 300,
        ai_transfers_enabled: true, ai_training_enabled: true,
        initial_morale_random: true, initial_stamina_random: true, initial_speed_random: true, luck_random: true,
        human_start_budget: props.humanStartBudgets.standard, ai_start_budget: props.aiStartBudget,
        draft_budget: props.draftBudgets.standard, ai_draft_budget: props.aiDraftBudget,
        visible_origins: { captain_tsubasa: true, ecole_des_champions: false, hungry_heart: false, blue_lock: false, ao_ashi: false, original: true },
        internationals_visible: false,
    });
}

function submit() {
    form.game_config = { ...config, visible_origins: { ...config.visible_origins } };
    form.post(route('game-saves.store'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Nouvelle partie" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Nouvelle partie</H2>
        </template>

        <div class="p-2 sm:p-4">
            <div class="flex flex-col md:flex-row">
                <!-- Visuel gauche -->
                <div class="hidden md:block basis-1/4 p-4 bg-contain bg-center bg-no-repeat bg-[url('/images/Hyuga2.webp')]"></div>

                <!-- Carte formulaire / config -->
                <div class="basis-full md:basis-3/4 p-4 sm:p-6 border border-slate-200 rounded-2xl mx-0 md:mx-6 bg-white h-[calc(100vh-6rem)] md:h-[800px] flex flex-col shadow-sm overflow-hidden">

                    <!-- Header -->
                    <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div class="text-xs font-bold text-teal-500 uppercase tracking-widest mb-1">Nouvelle sauvegarde</div>
                            <h1 class="text-lg sm:text-xl font-bold text-slate-700">
                                {{ showConfig ? 'Configuration avancée' : showRules ? 'Règles du jeu' : 'Création d\'une partie' }}
                            </h1>
                            <p class="text-xs text-slate-400 mt-1">
                                {{ showConfig ? 'Paramètres appliqués à cette sauvegarde.'
                                    : showRules ? 'Comment fonctionne le jeu, les matchs, la draft et les objectifs.'
                                    : 'Configure ta nouvelle saison et choisis ton niveau de jeu.' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="showRules = !showRules; showConfig = false"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border transition-all"
                                    :class="showRules
                                        ? 'border-teal-300 bg-teal-50 text-teal-700 hover:bg-teal-100'
                                        : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'">
                                <span>{{ showRules ? '← Retour' : '📖 Règles' }}</span>
                            </button>
                            <button type="button" @click="showConfig = !showConfig; showRules = false; showConfigHint = false"
                                    @animationend.self="showConfigHint = false"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border transition-all"
                                    :class="[showConfig
                                        ? 'border-teal-300 bg-teal-50 text-teal-700 hover:bg-teal-100'
                                        : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50',
                                        { 'configuration-wizz': showConfigHint && !showConfig && !showRules }]">
                                <span>{{ showConfig ? '← Retour' : '⚙ Configuration' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- ====== PANNEAU RÈGLES ====== -->
                    <div v-show="showRules" class="flex flex-col flex-1 gap-4 overflow-y-auto min-h-0 pr-1">
                        <GameRules />
                    </div>

                    <!-- ====== FORMULAIRE CREATION ====== -->
                    <form v-show="!showConfig && !showRules" @submit.prevent="submit" class="flex flex-col flex-1 gap-6 overflow-y-auto">

                        <div class="flex flex-col gap-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <!-- Nom de la partie -->
                            <div class="flex flex-col gap-1.5">
                                <label for="label" class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Nom de la partie
                                </label>
                                <input
                                    id="label"
                                    type="text"
                                    v-model="form.label"
                                    placeholder="ex: Saison Nankatsu 1"
                                    class="w-full px-4 py-2.5" :class="fieldClass()"
                                />
                                <p v-if="form.errors.label" class="text-xs text-rose-500">{{ form.errors.label }}</p>
                            </div>

                            <!-- Format de compétition -->
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Format de compétition
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button type="button"
                                            @click="form.competition_type = 'college_league'"
                                            class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                            :class="form.competition_type === 'college_league'
                                                ? 'border-teal-500 bg-teal-50 shadow-sm'
                                                : 'border-slate-300 bg-white hover:border-slate-300'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">🏫</span>
                                            <span class="text-sm font-bold"
                                                  :class="form.competition_type === 'college_league' ? 'text-teal-700' : 'text-slate-700'">
                                                Ligue
                                            </span>
                                        </div>
                                        <p class="text-[11px] leading-relaxed"
                                           :class="form.competition_type === 'college_league' ? 'text-teal-600' : 'text-slate-500'">
                                            Championnat des collèges en matchs aller-retour. Le mode classique de Captain Tsubasa.
                                        </p>
                                        <div v-if="form.competition_type === 'college_league'"
                                             class="absolute top-2 right-2 w-5 h-5 rounded-full bg-teal-500 flex items-center justify-center">
                                            <span class="text-white text-2xs font-bold">✓</span>
                                        </div>
                                    </button>

                                    <button type="button"
                                            @click="form.competition_type = 'world_cup'"
                                            class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                            :class="form.competition_type === 'world_cup'
                                                ? 'border-indigo-500 bg-indigo-50 shadow-sm'
                                                : 'border-slate-300 bg-white hover:border-slate-300'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">🌍</span>
                                            <span class="text-sm font-bold"
                                                  :class="form.competition_type === 'world_cup' ? 'text-indigo-700' : 'text-slate-700'">
                                                Coupe du Monde
                                            </span>
                                        </div>
                                        <p class="text-[11px] leading-relaxed"
                                           :class="form.competition_type === 'world_cup' ? 'text-indigo-600' : 'text-slate-500'">
                                            Tournoi des sélections nationales : poules puis élimination directe. Mène ta nation au titre.
                                        </p>
                                        <div v-if="form.competition_type === 'world_cup'"
                                             class="absolute top-2 right-2 w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center">
                                            <span class="text-white text-2xs font-bold">✓</span>
                                        </div>
                                    </button>
                                </div>
                                <p v-if="form.errors.competition_type" class="text-xs text-rose-500">{{ form.errors.competition_type }}</p>
                            </div>

                            <!-- Période : effectif standard, ou une période importée qui le remplace entièrement -->
                            <div v-if="form.competition_type === 'college_league'" class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <label for="period_package" class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Période
                                    </label>
                                    <div class="flex items-center gap-2 text-[11px] font-semibold">
                                        <a :href="exportHref" download
                                           class="text-teal-600 hover:text-teal-700 hover:underline">
                                            📤 Exporter{{ form.period_package_id ? ' cette période' : ' un modèle' }}
                                        </a>
                                        <span class="text-slate-300">·</span>
                                        <button type="button" @click="showImportModal = true"
                                                class="text-teal-600 hover:text-teal-700 hover:underline">
                                            📥 Importer une période
                                        </button>
                                    </div>
                                </div>
                                <select
                                    id="period_package"
                                    v-model="form.period_package_id"
                                    class="w-full px-4 py-2.5" :class="fieldClass()"
                                >
                                    <option :value="null">Collège (effectif standard)</option>
                                    <option v-for="pkg in availablePackages" :key="pkg.id" :value="pkg.id">
                                        📦 {{ pkg.name }} ({{ pkg.teamCount }} équipes, {{ pkg.playerCount }} joueurs)
                                    </option>
                                </select>
                                <p v-if="form.period_package_id" class="text-xs text-slate-400">
                                    Remplace entièrement l'effectif standard pour cette partie.
                                </p>
                                <p v-else-if="form.game_mode === 'draft' && periodPackages.length" class="text-xs text-slate-400">
                                    Les périodes importées ne sont pas disponibles en mode Draft.
                                </p>
                                <p v-if="form.errors.period_package_id" class="text-xs text-rose-500">{{ form.errors.period_package_id }}</p>
                            </div>

                            <!-- Mode de jeu (Ligue collège uniquement) -->
                            <div v-if="form.competition_type === 'college_league'" class="flex flex-col gap-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Mode de démarrage
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button type="button"
                                            @click="form.game_mode = 'prebuilt'"
                                            class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                            :class="form.game_mode === 'prebuilt'
                                                ? 'border-teal-500 bg-teal-50 shadow-sm'
                                                : 'border-slate-300 bg-white hover:border-slate-300'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">🏟️</span>
                                            <span class="text-sm font-bold"
                                                  :class="form.game_mode === 'prebuilt' ? 'text-teal-700' : 'text-slate-700'">
                                                Effectifs pré-faits
                                            </span>
                                        </div>
                                        <p class="text-[11px] leading-relaxed"
                                           :class="form.game_mode === 'prebuilt' ? 'text-teal-600' : 'text-slate-500'">
                                            Chaque équipe démarre avec son effectif Captain Tsubasa classique. Idéal pour jouer directement.
                                        </p>
                                        <div v-if="form.game_mode === 'prebuilt'"
                                             class="absolute top-2 right-2 w-5 h-5 rounded-full bg-teal-500 flex items-center justify-center">
                                            <span class="text-white text-2xs font-bold">✓</span>
                                        </div>
                                    </button>

                                    <button type="button"
                                            @click="form.game_mode = 'draft'; form.period_package_id = null"
                                            class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                            :class="form.game_mode === 'draft'
                                                ? 'border-amber-500 bg-amber-50 shadow-sm'
                                                : 'border-slate-300 bg-white hover:border-slate-300'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">🎯</span>
                                            <span class="text-sm font-bold"
                                                  :class="form.game_mode === 'draft' ? 'text-amber-700' : 'text-slate-700'">
                                                Draft initial
                                            </span>
                                        </div>
                                        <p class="text-[11px] leading-relaxed"
                                           :class="form.game_mode === 'draft' ? 'text-amber-600' : 'text-slate-500'">
                                            Toutes les équipes partent à zéro. Chaque manager pioche ses joueurs tour par tour. Mode gestionnaire pur.
                                        </p>
                                        <div v-if="form.game_mode === 'draft'"
                                             class="absolute top-2 right-2 w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center">
                                            <span class="text-white text-2xs font-bold">✓</span>
                                        </div>
                                    </button>
                                </div>
                                <p v-if="form.errors.game_mode" class="text-xs text-rose-500">{{ form.errors.game_mode }}</p>
                            </div>
                        </div>

                        <!-- Spacer -->
                        <div class="flex-1"></div>

                        <!-- Boutons -->
                        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-slate-200">
                            <Link :href="route('mainMenu')"
                                  class="inline-flex min-h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50">
                                ← Retour
                            </Link>
                            <ButtonPrimary size="lg" :tone="form.competition_type === 'world_cup' ? 'indigo' : 'teal'"
                                           :disabled="form.processing">
                                <span v-if="form.processing">Création...</span>
                                <span v-else-if="form.competition_type === 'world_cup'">🌍 Choisir ma sélection</span>
                                <span v-else-if="form.game_mode === 'draft'">🎯 Lancer le draft</span>
                                <span v-else>⚽ Démarrer la partie</span>
                            </ButtonPrimary>
                        </div>
                    </form>

                    <!-- ====== PANNEAU CONFIGURATION ====== -->
                    <div v-show="showConfig" class="flex flex-col flex-1 gap-4 overflow-y-auto min-h-0">

                        <!-- Mandat de la direction -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>🎯</span> Mandat de la direction
                            </h4>
                            <p class="text-2xs text-slate-400 -mt-1">
                                Définit l'objectif de classement, la patience du board et la condition de victoire de carrière.
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <button v-for="lvl in careerLevels" :key="lvl.key" type="button"
                                        @click="selectDifficulty(lvl.key)"
                                        class="relative flex flex-col gap-1 p-2.5 rounded-lg border-2 transition-all text-left"
                                        :class="config.career_difficulty === lvl.key
                                            ? 'border-teal-500 bg-teal-50'
                                            : 'border-slate-300 bg-white hover:border-slate-300'">
                                    <span class="text-xs font-bold flex items-center gap-1.5"
                                          :class="config.career_difficulty === lvl.key ? 'text-teal-700' : 'text-slate-700'">
                                        <span>{{ lvl.icon }}</span> {{ lvl.label }}
                                    </span>
                                    <span class="text-2xs leading-snug"
                                          :class="config.career_difficulty === lvl.key ? 'text-teal-600' : 'text-slate-500'">
                                        {{ lvl.desc }}
                                    </span>
                                </button>
                            </div>
                            <label class="flex items-start gap-2 text-xs text-slate-700 cursor-pointer mt-1">
                                <input type="checkbox" v-model="config.season_challenges_enabled"
                                       class="mt-0.5 rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                <span>
                                    🎲 Défis de saison
                                    <span class="block text-2xs text-slate-400">5 objectifs tirés au sort par équipe et par saison (primes + classement des défis, sans effet sur le championnat).</span>
                                </span>
                            </label>
                        </div>

                        <!-- Cartes -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>🃏</span> Cartes
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input type="checkbox" v-model="config.bonus_cards_enabled"
                                           class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                    Cartes bonus
                                </label>
                                <label class="flex items-center gap-2 text-xs cursor-pointer"
                                       :class="config.bonus_cards_enabled ? 'text-slate-700' : 'text-slate-400'">
                                    <input type="checkbox" v-model="config.malus_cards_enabled"
                                           :disabled="!config.bonus_cards_enabled"
                                           class="rounded border-slate-300 text-teal-500 focus:ring-teal-400 disabled:opacity-40" />
                                    Cartes malus
                                </label>
                            </div>
                        </div>

                        <!-- Données aléatoires -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>🎲</span> Données aléatoires
                            </h4>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" v-model="config.initial_morale_random"
                                               class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                        Moral initial aléatoire (45–75 par joueur)
                                    </label>
                                    <p class="text-2xs text-slate-400">
                                        Désactivé, le moral de départ est fixé à 60.
                                    </p>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" v-model="config.initial_stamina_random"
                                               class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                        Endurance initiale aléatoire (60–100)
                                    </label>
                                    <p class="text-2xs text-slate-400">
                                        Les amateurs générés tirent entre 55 et 75. Désactivée, l’endurance de départ est fixée à 100.
                                    </p>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" v-model="config.initial_speed_random"
                                               class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                        Vitesse variable (−10 à +10)
                                    </label>
                                    <p class="text-2xs text-slate-400">
                                        Le tirage varie la note de base dans la limite de 0 à 100. La vitesse obtenue est conservée d’une saison à l’autre.
                                    </p>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" v-model="config.luck_random"
                                               class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                        Chance aléatoire (20–80 par joueur)
                                    </label>
                                    <p class="text-2xs text-slate-400">
                                        Tirée à la création de la partie et au début de chaque saison. Désactivée, la chance est fixée à 50.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Fatigue -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>⚡</span> Fatigue & Endurance
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-2xs text-slate-500">Coût en endurance / match</label>
                                    <div class="flex items-center gap-2">
                                        <input type="range" v-model.number="config.match_stamina_cost" min="0" max="20" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-700 w-5 text-right">{{ config.match_stamina_cost }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-2xs text-slate-500">Récupération remplaçants</label>
                                    <div class="flex items-center gap-2">
                                        <input type="range" v-model.number="config.rest_stamina_recovery" min="0" max="30" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-700 w-5 text-right">{{ config.rest_stamina_recovery }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Match -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>⚽</span> Match
                            </h4>
                            <div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-2">
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-col gap-1 max-w-xs">
                                        <label class="text-2xs text-slate-500">Tours par match</label>
                                        <div class="flex items-center gap-2">
                                            <input type="range" v-model.number="config.match_max_turns" min="10" max="80" step="5" class="flex-1 h-1 accent-teal-500" />
                                            <span class="text-xs font-bold text-slate-700 w-5 text-right">{{ config.match_max_turns }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1 max-w-xs">
                                        <label for="setup-match-substitutions" class="text-2xs text-slate-500">Remplacements par équipe</label>
                                        <div class="flex items-center gap-2">
                                            <input id="setup-match-substitutions" type="range" v-model.number="config.match_max_substitutions" min="0" max="11" step="1" class="flex-1 h-1 accent-teal-500" />
                                            <span class="text-xs font-bold text-slate-700 w-5 text-right">{{ config.match_max_substitutions }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" v-model="config.match_criticals_enabled"
                                               class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                        Activer les critiques
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" v-model="config.injury_on_foul"
                                               class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                        Blessures sur faute
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" v-model="config.suspension_on_3_yellows"
                                               class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                        Suspension 3 jaunes
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Économie -->
                        <div v-if="form.competition_type !== 'world_cup'" class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>💰</span> Économie
                            </h4>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div v-for="field in budgetFields" :key="field.key" class="flex flex-col gap-1 min-w-0">
                                    <label :for="field.key" class="text-2xs text-slate-500">{{ field.label }}</label>
                                    <div class="flex items-center gap-2">
                                        <input :id="field.key" v-model.number="config[field.key]" type="range" :min="field.min" max="20000" step="250" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-700 w-14 text-right">{{ config[field.key] }} <GameBanknote /></span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-2xs text-slate-400">Les budgets de draft sont réservés au recrutement. Les soldes expirent à la fin de chaque draft.</p>
                        </div>

                        <!-- Entraînement -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>🏋️</span> Entraînement
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-2xs text-slate-500">Sessions / sem.</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="config.training_max_per_week" min="1" max="10" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-2xs font-bold text-slate-700 w-3 text-right">{{ config.training_max_per_week }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-2xs text-slate-500">Gain min</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="config.training_gain_min" min="0" max="10" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-2xs font-bold text-slate-700 w-3 text-right">{{ config.training_gain_min }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-2xs text-slate-500">Gain max</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="config.training_gain_max" min="1" max="20" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-2xs font-bold text-slate-700 w-3 text-right">{{ config.training_gain_max }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-2xs text-slate-500">Coût en endurance</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="config.training_stamina_cost" min="0" max="15" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-2xs font-bold text-slate-700 w-3 text-right">{{ config.training_stamina_cost }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-2xs text-slate-500">Endurance min. requise</label>
                                    <div class="flex items-center gap-2">
                                        <input type="range" v-model.number="config.training_min_stamina" min="0" max="50" step="5" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-700 w-5 text-right">{{ config.training_min_stamina }}</span>
                                    </div>
                                </div>
                                <div v-for="(label, key) in { training_cost: 'Coût par séance simple', position_training_cost: 'Coût par formation de poste', special_training_cost: 'Coût par technique spéciale' }"
                                     :key="key" class="flex flex-col gap-1">
                                    <label class="text-2xs text-slate-500">{{ label }} (<GameBanknote />)</label>
                                    <div class="flex items-center gap-2">
                                        <input type="range" v-model.number="config[key]" min="0" max="2000" step="50" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-700 w-10 text-right">{{ config[key] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visibilité -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>👁</span> Visibilité des joueurs
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <label v-for="(enabled, key) in config.visible_origins" :key="key"
                                       class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input type="checkbox" v-model="config.visible_origins[key]"
                                           class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                    {{ originLabels[key] ?? key }}
                                </label>
                            </div>
                            <div class="border-t border-slate-200 pt-2 mt-1">
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input type="checkbox" v-model="config.internationals_visible"
                                           class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                    Joueurs internationaux
                                </label>
                            </div>
                        </div>

                        <!-- IA -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>🤖</span> Intelligence Artificielle
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input type="checkbox" v-model="config.ai_transfers_enabled"
                                           class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                    Transferts IA
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input type="checkbox" v-model="config.ai_training_enabled"
                                           class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                    Entraînement IA
                                </label>
                            </div>
                        </div>

                        <!-- Spacer + bouton reset -->
                        <div class="flex-1"></div>
                        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
                            <button type="button" @click="resetDefaults"
                                    class="px-3 py-1.5 text-xs rounded-full border border-slate-300 bg-white hover:bg-slate-50 text-slate-600 font-medium">
                                Réinitialiser
                            </button>
                            <button type="button" @click="showConfig = false"
                                    class="px-4 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold">
                                ✓ Valider la config
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Import inline d'une période : reste sur cet écran (redirect_to=create) -->
        <Modal :show="showImportModal" max-width="lg" @close="showImportModal = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-700 mb-1">Importer une période</h3>
                <p class="text-xs text-slate-400 mb-4">
                    Dépose un fichier .zip au format attendu (voir « Exporter un modèle » pour un exemple à éditer).
                    Elle sera ajoutée à ta bibliothèque et immédiatement sélectionnable ci-dessus.
                </p>
                <form @submit.prevent="submitImport" class="flex flex-col gap-3">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Nom</label>
                        <input v-model="importForm.name" type="text"
                               class="mt-1 w-full px-3 py-2" :class="fieldClass()" />
                        <p v-if="importForm.errors.name" class="text-xs text-rose-500 mt-1">{{ importForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Description</label>
                        <textarea v-model="importForm.description" rows="2"
                                  class="mt-1 w-full px-3 py-2" :class="TEXTAREA_CLASS"></textarea>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Fichier (.zip)</label>
                        <input type="file" accept=".zip" class="mt-1 w-full text-sm text-slate-600" @change="onImportZipChange" />
                        <p v-if="importForm.errors.zip" class="text-xs text-rose-500 mt-1">{{ importForm.errors.zip }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-600">
                        <input v-model="importForm.is_shared" type="checkbox" class="rounded border-slate-300" />
                        Partager cette période (visible par les autres utilisateurs)
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showImportModal = false"
                                class="px-4 py-2 text-xs font-semibold rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50">
                            Annuler
                        </button>
                        <button type="submit" :disabled="importForm.processing"
                                class="px-4 py-2 text-xs font-semibold rounded-lg bg-teal-500 hover:bg-teal-600 text-white disabled:opacity-50">
                            <span v-if="importForm.processing">Import en cours...</span>
                            <span v-else>Importer</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
@media (prefers-reduced-motion: no-preference) {
    .configuration-wizz {
        animation: configuration-wizz 10s ease-in-out;
    }
}

@keyframes configuration-wizz {
    0%, 6%, 12%, 46%, 52%, 86%, 92%, 100% { transform: none; }
    7%, 9%, 47%, 49%, 87%, 89% { transform: translateX(-3px) rotate(-4deg); }
    8%, 10%, 48%, 50%, 88%, 90% { transform: translateX(3px) rotate(4deg); }
}
</style>
