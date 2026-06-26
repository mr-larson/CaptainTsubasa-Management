<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    gameSave: { type: Object, required: true },
    managementStats: { type: Object, default: () => ({}) },
    gameConfig: { type: Object, default: () => ({}) },
});

const stats = props.managementStats ?? {};
const expiringContracts = stats.expiringContracts ?? [];

const sections = [
    { key: 'dataBase', label: 'Base de données' },
    { key: 'profil',   label: 'Profil' },
    { key: 'config',   label: 'Configuration' },
];
const activeSection = ref('dataBase');

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
        match_max_turns: 45,
        injury_on_foul: true,
        suspension_on_3_yellows: true,
        training_max_per_week: 3,
        training_gain_min: 1,
        training_gain_max: 5,
        training_stamina_cost: 2,
        training_min_stamina: 10,
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
    };
    Object.assign(config, defaults);
}
</script>

<template>
    <div class="flex-1 flex gap-4 overflow-y-auto min-h-[75vh]">
        <!-- Sidebar -->
        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
            <h3 class="text-md font-semibold text-slate-700 mb-2">Gestion</h3>
            <nav class="space-y-1">
                <button v-for="sec in sections" :key="sec.key" type="button"
                        @click="activeSection = sec.key"
                        :class="['w-full text-left text-sm px-2 py-1 rounded',
                        activeSection === sec.key ? 'bg-teal-100 text-slate-900' : 'bg-white hover:bg-slate-100 text-slate-700']">
                    {{ sec.label }}
                </button>
            </nav>
        </div>

        <!-- Panneau droit -->
        <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4">

            <!-- Base de données -->
            <div v-if="activeSection === 'dataBase'" class="flex-1 flex flex-col gap-3">
                <h3 class="text-lg font-semibold text-slate-800 mb-1">Base de données</h3>
                <p class="text-sm text-slate-600 mb-2">Crée, édite et assigne des joueurs, contrats et équipes.</p>

                <!-- Compteurs -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-800">{{ stats.playersCount ?? '—' }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Joueurs</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-800">{{ stats.teamsCount ?? '—' }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Équipes</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-800">{{ stats.activeContractsCount ?? '—' }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Contrats actifs</span>
                    </div>
                    <button type="button"
                            class="border rounded-xl p-3 flex flex-col items-center gap-1 transition-all"
                            :class="(stats.freePlayersCount ?? 0) > 0
                                ? 'border-amber-200 bg-amber-50 hover:bg-amber-100'
                                : 'border-slate-200 bg-white'"
                            @click="router.get(route('game-saves.players.index', { gameSave: gameSave.id }))">
                        <span class="text-2xl font-black" :class="(stats.freePlayersCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-800'">
                            {{ stats.freePlayersCount ?? '—' }}
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-wider"
                              :class="(stats.freePlayersCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-400'">
                            Joueurs libres
                        </span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                        <h4 class="text-sm font-semibold text-slate-700">Joueurs</h4>
                        <p class="text-xs text-slate-500">Crée, édite et supprime les joueurs de la base de données.</p>
                        <button type="button" class="mt-2 px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                @click="router.get(route('game-saves.players.index', { gameSave: gameSave.id }))">
                            Gérer les joueurs
                        </button>
                    </div>
                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                        <h4 class="text-sm font-semibold text-slate-700">Équipes</h4>
                        <p class="text-xs text-slate-500">Accéder à la gestion complète des équipes.</p>
                        <button type="button" class="mt-2 px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                @click="router.get(route('game-saves.teams.index', { gameSave: gameSave.id }))">
                            Gérer les équipes
                        </button>
                    </div>
                </div>

                <!-- Raccourci : contrats arrivant à échéance -->
                <div v-if="expiringContracts.length" class="border border-rose-200 rounded-lg bg-rose-50 p-3 flex flex-col gap-2">
                    <h4 class="text-sm font-semibold text-rose-700">Contrats arrivant à échéance</h4>
                    <p class="text-xs text-rose-600">{{ expiringContracts.length }} contrat(s) se terminent dans les 4 prochaines semaines.</p>
                    <ul class="flex flex-col gap-1">
                        <li v-for="c in expiringContracts" :key="c.id"
                            class="flex items-center justify-between text-xs bg-white rounded-lg px-3 py-1.5 border border-rose-100">
                            <span class="text-slate-700 font-medium">{{ c.player }} <span class="text-slate-400 font-normal">— {{ c.team }}</span></span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Semaine {{ c.end_week }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Profil -->
            <div v-else-if="activeSection === 'profil'" class="flex-1 flex flex-col gap-3">
                <h3 class="text-lg font-semibold text-slate-800 mb-1">Mon profil</h3>
                <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                    <h4 class="text-sm font-semibold text-slate-700">Édition de mon profil</h4>
                    <p class="text-xs text-slate-500">Accéder aux informations de ton compte (nom, email, mot de passe…).</p>
                    <button type="button" class="mt-2 px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                            @click="router.get(route('profile.edit'))">
                        Modifier mon profil
                    </button>
                </div>
            </div>

            <!-- Configuration -->
            <div v-else-if="activeSection === 'config'" class="flex-1 flex flex-col gap-3 overflow-y-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Configuration du jeu</h3>
                        <p class="text-sm text-slate-500">Paramètres appliqués à cette sauvegarde.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="resetDefaults"
                                class="px-3 py-1.5 text-xs rounded-full border border-slate-300 bg-white hover:bg-slate-50 text-slate-600 font-medium">
                            Réinitialiser
                        </button>
                        <button type="button" @click="saveConfig" :disabled="saving"
                                class="px-4 py-1.5 text-xs rounded-full font-semibold text-white transition-all"
                                :class="saved ? 'bg-emerald-500' : 'bg-teal-500 hover:bg-teal-600'">
                            {{ saving ? 'Enregistrement…' : saved ? 'Enregistré !' : 'Enregistrer' }}
                        </button>
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
                                <span class="text-xs font-bold text-slate-800 w-6 text-right">{{ config.match_stamina_cost }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Récupération remplaçants (par semaine)</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.rest_stamina_recovery"
                                       min="0" max="30" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-800 w-6 text-right">{{ config.rest_stamina_recovery }}</span>
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
                            <span class="text-xs font-bold text-slate-800 w-6 text-right">{{ config.match_max_turns }}</span>
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
                                <span class="text-xs font-bold text-slate-800 w-4 text-right">{{ config.training_max_per_week }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Gain min</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.training_gain_min"
                                       min="0" max="10" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-800 w-4 text-right">{{ config.training_gain_min }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Gain max</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.training_gain_max"
                                       min="1" max="20" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-800 w-4 text-right">{{ config.training_gain_max }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-slate-600">Coût stamina</label>
                            <div class="flex items-center gap-2">
                                <input type="range" v-model.number="config.training_stamina_cost"
                                       min="0" max="15" step="1"
                                       class="flex-1 h-1.5 accent-teal-500" />
                                <span class="text-xs font-bold text-slate-800 w-4 text-right">{{ config.training_stamina_cost }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 max-w-xs">
                        <label class="text-xs text-slate-600">Stamina minimum requise pour s'entraîner</label>
                        <div class="flex items-center gap-2">
                            <input type="range" v-model.number="config.training_min_stamina"
                                   min="0" max="50" step="5"
                                   class="flex-1 h-1.5 accent-teal-500" />
                            <span class="text-xs font-bold text-slate-800 w-6 text-right">{{ config.training_min_stamina }}</span>
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
