<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import H2 from '@/Components/H2.vue';
import GameRules from '@/Components/GameRules.vue';

const form = useForm({
    label: '',
    period: 'college',
    game_mode: 'prebuilt',
    competition_type: 'college_league',
    game_config: null,
});

const showConfig = ref(false);
const showRules = ref(false);

const originLabels = {
    captain_tsubasa:     'Captain Tsubasa',
    ecole_des_champions: 'École des Champions',
    hungry_heart:        'Hungry Heart',
    blue_lock:           'Blue Lock',
    ao_ashi:             'Ao Ashi',
    original:            'Joueurs générés',
};

const careerLevels = [
    { key: 'none',     icon: '∞',  label: 'Bac à sable',  desc: 'Aucun objectif. Les saisons s\'enchaînent à l\'infini.' },
    { key: 'survival', icon: '🛡️', label: 'Survie',       desc: 'Petit club. La direction tolère un objectif modeste mais reste impatiente. 1 titre pour gagner.' },
    { key: 'standard', icon: '⚖️', label: 'Standard',     desc: 'Objectif calé sur la force de l\'effectif. 2 titres pour gagner.' },
    { key: 'conquest', icon: '👑', label: 'Conquête',     desc: 'Gros club. Le board exige le haut du tableau. 3 titres pour gagner.' },
];

const config = reactive({
    career_difficulty: 'standard',
    bonus_cards_enabled: true,
    malus_cards_enabled: true,
    match_stamina_cost: 5,
    rest_stamina_recovery: 10,
    match_max_turns: 45,
    injury_on_foul: true,
    suspension_on_3_yellows: true,
    training_max_per_week: 3,
    training_gain_min: 1,
    training_gain_max: 5,
    training_stamina_cost: 2,
    training_min_stamina: 10,
    training_cost: 200,
    ai_transfers_enabled: true,
    ai_training_enabled: true,
    visible_origins: {
        captain_tsubasa: true,
        ecole_des_champions: true,
        hungry_heart: true,
        blue_lock: true,
        ao_ashi: true,
        original: true,
    },
    internationals_visible: true,
});

function resetDefaults() {
    Object.assign(config, {
        career_difficulty: 'standard',
        bonus_cards_enabled: true, malus_cards_enabled: true,
        match_stamina_cost: 5, rest_stamina_recovery: 10, match_max_turns: 45,
        injury_on_foul: true, suspension_on_3_yellows: true,
        training_max_per_week: 3, training_gain_min: 1, training_gain_max: 5,
        training_stamina_cost: 2, training_min_stamina: 10, training_cost: 200,
        ai_transfers_enabled: true, ai_training_enabled: true,
        visible_origins: { captain_tsubasa: true, ecole_des_champions: true, hungry_heart: true, blue_lock: true, ao_ashi: true, original: true },
        internationals_visible: true,
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

        <div class="p-4">
            <div class="flex flex-row">
                <!-- Visuel gauche -->
                <div class="hidden md:block basis-1/4 p-4 bg-contain bg-center bg-no-repeat"
                     style="background-image: url('/images/hyuga2.webp')"></div>

                <!-- Carte formulaire / config -->
                <div class="basis-3/4 p-6 border border-slate-200 rounded-2xl mx-6 bg-white h-[800px] flex flex-col shadow-sm overflow-hidden">

                    <!-- Header -->
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-teal-500 uppercase tracking-widest mb-1">Nouvelle sauvegarde</div>
                            <h1 class="text-xl font-bold text-slate-800">
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
                                <span>{{ showRules ? '← Retour au formulaire' : '📖 Règles' }}</span>
                            </button>
                            <button type="button" @click="showConfig = !showConfig; showRules = false"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border transition-all"
                                    :class="showConfig
                                        ? 'border-teal-300 bg-teal-50 text-teal-700 hover:bg-teal-100'
                                        : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'">
                                <span>{{ showConfig ? '← Retour au formulaire' : '⚙ Configuration' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- ====== PANNEAU RÈGLES ====== -->
                    <div v-show="showRules" class="flex flex-col flex-1 gap-4 overflow-y-auto min-h-0 pr-1">
                        <GameRules />
                    </div>

                    <!-- ====== FORMULAIRE CREATION ====== -->
                    <form v-show="!showConfig && !showRules" @submit.prevent="submit" class="flex flex-col flex-1 gap-6 overflow-y-auto">

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
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-300 transition-all"
                            />
                            <p v-if="form.errors.label" class="text-xs text-rose-500">{{ form.errors.label }}</p>
                        </div>

                        <!-- Format de compétition -->
                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Format de compétition
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button"
                                        @click="form.competition_type = 'college_league'"
                                        class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                        :class="form.competition_type === 'college_league'
                                            ? 'border-teal-500 bg-teal-50 shadow-sm'
                                            : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🏫</span>
                                        <span class="text-sm font-bold"
                                              :class="form.competition_type === 'college_league' ? 'text-teal-700' : 'text-slate-700'">
                                            Ligue
                                        </span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed"
                                       :class="form.competition_type === 'college_league' ? 'text-teal-600' : 'text-slate-400'">
                                        Championnat des collèges en matchs aller-retour. Le mode classique de Captain Tsubasa.
                                    </p>
                                    <div v-if="form.competition_type === 'college_league'"
                                         class="absolute top-2 right-2 w-5 h-5 rounded-full bg-teal-500 flex items-center justify-center">
                                        <span class="text-white text-[10px] font-bold">✓</span>
                                    </div>
                                </button>

                                <button type="button"
                                        @click="form.competition_type = 'world_cup'"
                                        class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                        :class="form.competition_type === 'world_cup'
                                            ? 'border-indigo-500 bg-indigo-50 shadow-sm'
                                            : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🌍</span>
                                        <span class="text-sm font-bold"
                                              :class="form.competition_type === 'world_cup' ? 'text-indigo-700' : 'text-slate-700'">
                                            Coupe du Monde
                                        </span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed"
                                       :class="form.competition_type === 'world_cup' ? 'text-indigo-600' : 'text-slate-400'">
                                        Tournoi des sélections nationales : poules puis élimination directe. Mène ta nation au titre.
                                    </p>
                                    <div v-if="form.competition_type === 'world_cup'"
                                         class="absolute top-2 right-2 w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center">
                                        <span class="text-white text-[10px] font-bold">✓</span>
                                    </div>
                                </button>
                            </div>
                            <p v-if="form.errors.competition_type" class="text-xs text-rose-500">{{ form.errors.competition_type }}</p>
                        </div>

                        <!-- Période -->
                        <div class="flex flex-col gap-1.5">
                            <label for="period" class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Période
                            </label>
                            <select
                                id="period"
                                v-model="form.period"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-300 transition-all"
                            >
                                <option value="college">Collège</option>
                                <option value="highschool">Lycée</option>
                                <option value="pro">Professionnel</option>
                            </select>
                            <p v-if="form.errors.period" class="text-xs text-rose-500">{{ form.errors.period }}</p>
                        </div>

                        <!-- Mode de jeu (Ligue collège uniquement) -->
                        <div v-if="form.competition_type === 'college_league'" class="flex flex-col gap-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Mode de démarrage
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button"
                                        @click="form.game_mode = 'prebuilt'"
                                        class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                        :class="form.game_mode === 'prebuilt'
                                            ? 'border-teal-500 bg-teal-50 shadow-sm'
                                            : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🏟️</span>
                                        <span class="text-sm font-bold"
                                              :class="form.game_mode === 'prebuilt' ? 'text-teal-700' : 'text-slate-700'">
                                            Effectifs pré-faits
                                        </span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed"
                                       :class="form.game_mode === 'prebuilt' ? 'text-teal-600' : 'text-slate-400'">
                                        Chaque équipe démarre avec son effectif Captain Tsubasa classique. Idéal pour jouer directement.
                                    </p>
                                    <div v-if="form.game_mode === 'prebuilt'"
                                         class="absolute top-2 right-2 w-5 h-5 rounded-full bg-teal-500 flex items-center justify-center">
                                        <span class="text-white text-[10px] font-bold">✓</span>
                                    </div>
                                </button>

                                <button type="button"
                                        @click="form.game_mode = 'draft'"
                                        class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                        :class="form.game_mode === 'draft'
                                            ? 'border-amber-500 bg-amber-50 shadow-sm'
                                            : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🎯</span>
                                        <span class="text-sm font-bold"
                                              :class="form.game_mode === 'draft' ? 'text-amber-700' : 'text-slate-700'">
                                            Draft initial
                                        </span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed"
                                       :class="form.game_mode === 'draft' ? 'text-amber-600' : 'text-slate-400'">
                                        Toutes les équipes partent à zéro. Chaque manager pioche ses joueurs tour par tour. Mode gestionnaire pur.
                                    </p>
                                    <div v-if="form.game_mode === 'draft'"
                                         class="absolute top-2 right-2 w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center">
                                        <span class="text-white text-[10px] font-bold">✓</span>
                                    </div>
                                </button>
                            </div>
                            <p v-if="form.errors.game_mode" class="text-xs text-rose-500">{{ form.errors.game_mode }}</p>
                        </div>

                        <!-- Spacer -->
                        <div class="flex-1"></div>

                        <!-- Boutons -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                            <Link :href="route('mainMenu')"
                                  class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-500 text-sm font-semibold rounded-xl border border-slate-200 transition-all">
                                ← Retour
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex items-center gap-2 px-6 py-2.5 text-white text-sm font-bold rounded-xl transition-all disabled:opacity-50 active:scale-[0.98]"
                                :class="form.competition_type === 'world_cup'
                                    ? 'bg-indigo-500 hover:bg-indigo-400'
                                    : (form.game_mode === 'draft'
                                        ? 'bg-amber-500 hover:bg-amber-400'
                                        : 'bg-teal-500 hover:bg-teal-400')">
                                <span v-if="form.processing">Création...</span>
                                <span v-else-if="form.competition_type === 'world_cup'">🌍 Choisir ma sélection</span>
                                <span v-else-if="form.game_mode === 'draft'">🎯 Lancer le draft</span>
                                <span v-else>⚽ Démarrer la partie</span>
                            </button>
                        </div>
                    </form>

                    <!-- ====== PANNEAU CONFIGURATION ====== -->
                    <div v-show="showConfig" class="flex flex-col flex-1 gap-4 overflow-y-auto min-h-0">

                        <!-- Mandat de la direction -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>🎯</span> Mandat de la direction
                            </h4>
                            <p class="text-[10px] text-slate-400 -mt-1">
                                Définit l'objectif de classement, la patience du board et la condition de victoire de carrière.
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="lvl in careerLevels" :key="lvl.key" type="button"
                                        @click="config.career_difficulty = lvl.key"
                                        class="relative flex flex-col gap-1 p-2.5 rounded-lg border-2 transition-all text-left"
                                        :class="config.career_difficulty === lvl.key
                                            ? 'border-teal-500 bg-teal-50'
                                            : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <span class="text-xs font-bold flex items-center gap-1.5"
                                          :class="config.career_difficulty === lvl.key ? 'text-teal-700' : 'text-slate-700'">
                                        <span>{{ lvl.icon }}</span> {{ lvl.label }}
                                    </span>
                                    <span class="text-[10px] leading-snug"
                                          :class="config.career_difficulty === lvl.key ? 'text-teal-600' : 'text-slate-400'">
                                        {{ lvl.desc }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Cartes -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>🃏</span> Cartes
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input type="checkbox" v-model="config.bonus_cards_enabled"
                                           class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                    Bonus cards
                                </label>
                                <label class="flex items-center gap-2 text-xs cursor-pointer"
                                       :class="config.bonus_cards_enabled ? 'text-slate-700' : 'text-slate-400'">
                                    <input type="checkbox" v-model="config.malus_cards_enabled"
                                           :disabled="!config.bonus_cards_enabled"
                                           class="rounded border-slate-300 text-teal-500 focus:ring-teal-400 disabled:opacity-40" />
                                    Malus cards
                                </label>
                            </div>
                        </div>

                        <!-- Fatigue -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>⚡</span> Fatigue & Stamina
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] text-slate-500">Coût stamina / match</label>
                                    <div class="flex items-center gap-2">
                                        <input type="range" v-model.number="config.match_stamina_cost" min="0" max="20" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-800 w-5 text-right">{{ config.match_stamina_cost }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] text-slate-500">Récupération remplaçants</label>
                                    <div class="flex items-center gap-2">
                                        <input type="range" v-model.number="config.rest_stamina_recovery" min="0" max="30" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-800 w-5 text-right">{{ config.rest_stamina_recovery }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Match -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>⚽</span> Match
                            </h4>
                            <div class="flex flex-col gap-1 max-w-xs">
                                <label class="text-[10px] text-slate-500">Tours par match</label>
                                <div class="flex items-center gap-2">
                                    <input type="range" v-model.number="config.match_max_turns" min="10" max="80" step="5" class="flex-1 h-1 accent-teal-500" />
                                    <span class="text-xs font-bold text-slate-800 w-5 text-right">{{ config.match_max_turns }}</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
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

                        <!-- Entraînement -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>🏋️</span> Entraînement
                            </h4>
                            <div class="grid grid-cols-4 gap-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] text-slate-500">Sessions / sem.</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="config.training_max_per_week" min="1" max="10" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-[10px] font-bold text-slate-800 w-3 text-right">{{ config.training_max_per_week }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] text-slate-500">Gain min</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="config.training_gain_min" min="0" max="10" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-[10px] font-bold text-slate-800 w-3 text-right">{{ config.training_gain_min }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] text-slate-500">Gain max</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="config.training_gain_max" min="1" max="20" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-[10px] font-bold text-slate-800 w-3 text-right">{{ config.training_gain_max }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] text-slate-500">Coût stamina</label>
                                    <div class="flex items-center gap-1">
                                        <input type="range" v-model.number="config.training_stamina_cost" min="0" max="15" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-[10px] font-bold text-slate-800 w-3 text-right">{{ config.training_stamina_cost }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] text-slate-500">Stamina min. requise</label>
                                    <div class="flex items-center gap-2">
                                        <input type="range" v-model.number="config.training_min_stamina" min="0" max="50" step="5" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-800 w-5 text-right">{{ config.training_min_stamina }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] text-slate-500">Coût / séance (€)</label>
                                    <div class="flex items-center gap-2">
                                        <input type="range" v-model.number="config.training_cost" min="0" max="2000" step="50" class="flex-1 h-1 accent-teal-500" />
                                        <span class="text-xs font-bold text-slate-800 w-10 text-right">{{ config.training_cost }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visibilité -->
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3 flex flex-col gap-2">
                            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span>👁</span> Visibilité des joueurs
                            </h4>
                            <div class="grid grid-cols-3 gap-2">
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
                            <div class="grid grid-cols-2 gap-3">
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
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
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
    </AuthenticatedLayout>
</template>
