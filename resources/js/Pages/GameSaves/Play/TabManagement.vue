<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    gameSave: { type: Object, required: true },
    managementStats: { type: Object, default: () => ({}) },
    gameConfig: { type: Object, default: () => ({}) },
    career: { type: Object, default: null },
    initialSection: { type: String, default: null },
});

const stats = props.managementStats ?? {};
const expiringContracts = stats.expiringContracts ?? [];

const sections = computed(() => [
    { key: 'dataBase', label: 'Base de données' },
    ...(props.career ? [{ key: 'rules', label: 'Règles & Objectif' }] : []),
    { key: 'profil',   label: 'Profil' },
    { key: 'config',   label: 'Configuration' },
]);
const activeSection = ref(props.initialSection ?? 'dataBase');

watch(() => props.initialSection, (v) => { if (v) activeSection.value = v; });

// ── Détail des objectifs de carrière (mode Ligue) ──────────────
// Barème statique (miroir de CareerObjectiveService) pour la doc des règles.
const DIFFICULTY_PRESETS = [
    { key: 'survival', label: 'Survie',    confidence: 45, titles: 1, rank: 'Objectif indulgent (rang toléré +2)' },
    { key: 'standard', label: 'Standard',  confidence: 50, titles: 2, rank: 'Objectif = classement attendu' },
    { key: 'conquest', label: 'Conquête',  confidence: 55, titles: 3, rank: 'Objectif exigeant (rang −2)' },
];

const lastVerdict = computed(() => props.career?.last_verdict ?? null);
const careerHistory = computed(() => props.career?.history ?? []);

const verdictOutcomeLabel = (o) => ({
    won:      '🏆 Carrière gagnée',
    fired:    '❌ Licencié',
    retained: '✅ Maintenu',
}[o] ?? o);

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
    <div class="flex-1 flex gap-4 overflow-y-auto min-h-0">
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

            <!-- Règles & Objectif -->
            <div v-else-if="activeSection === 'rules'" class="flex-1 flex flex-col gap-4 overflow-y-auto pr-1">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-1">Règles & Objectif</h3>
                    <p class="text-sm text-slate-500">
                        En mode Ligue, la direction te fixe un objectif de classement chaque saison et suit une
                        <strong>jauge de confiance</strong>. Atteins assez de titres pour gagner ta carrière… ou tombe à 0 et tu es licencié.
                    </p>
                </div>

                <!-- ÉTAT LIVE : mandat de la saison en cours -->
                <div v-if="career" class="border rounded-xl bg-white p-4"
                     :class="career.alert ? 'border-rose-300 ring-1 ring-rose-100' : 'border-slate-200'">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Objectif de la saison</h4>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                              :class="career.status === 'won' ? 'bg-emerald-100 text-emerald-700'
                                  : career.status === 'fired' ? 'bg-rose-100 text-rose-700'
                                  : 'bg-slate-100 text-slate-600'">
                            Difficulté · {{ career.difficulty_label }}
                        </span>
                    </div>

                    <div v-if="career.mandate" class="flex items-baseline gap-2 mb-1">
                        <span class="text-lg">🎯</span>
                        <span class="text-base font-bold text-slate-800">{{ career.mandate.label }}</span>
                        <span class="text-xs text-slate-400">
                            (classement attendu : {{ career.mandate.expected_rank }}<sup>e</sup> / {{ career.mandate.team_count }})
                        </span>
                    </div>
                    <p v-else class="text-xs text-slate-400 italic mb-1">Objectif en attente (effectif pas encore constitué).</p>

                    <!-- Jauge de confiance -->
                    <div class="mt-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Confiance de la direction</span>
                            <span class="text-sm font-black"
                                  :class="career.confidence <= 25 ? 'text-rose-500' : career.confidence <= 50 ? 'text-amber-500' : 'text-emerald-600'">
                                {{ career.confidence }} / 100
                            </span>
                        </div>
                        <div class="relative h-2.5 rounded-full bg-slate-100 overflow-hidden">
                            <!-- repère du seuil d'alerte (25) -->
                            <div class="absolute top-0 bottom-0 w-px bg-rose-300" style="left: 25%"></div>
                            <div class="h-full rounded-full transition-all"
                                 :class="career.confidence <= 25 ? 'bg-rose-500' : career.confidence <= 50 ? 'bg-amber-400' : 'bg-emerald-500'"
                                 :style="{ width: career.confidence + '%' }"></div>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">
                            ⚠️ Sous 25 le board est en alerte · à 0 c'est le licenciement.
                        </p>
                    </div>

                    <!-- Titres -->
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Titres</span>
                        <div class="flex items-center gap-1">
                            <span v-for="n in career.titles_required" :key="n"
                                  class="w-5 h-5 rounded-full flex items-center justify-center text-[11px]"
                                  :class="n <= career.titles_won ? 'bg-amber-100' : 'bg-slate-100 grayscale opacity-50'">🏆</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-600">{{ career.titles_won }} / {{ career.titles_required }} pour gagner</span>
                    </div>

                    <!-- Bannières statut -->
                    <div v-if="career.status === 'won'" class="mt-3 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700">
                        🏆 Carrière accomplie : objectif de titres atteint !
                    </div>
                    <div v-else-if="career.status === 'fired'" class="mt-3 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                        ❌ Tu as été licencié{{ career.fired_reason === 'mid_season' ? ' en cours de saison' : ' en fin de saison' }}.
                    </div>
                </div>

                <!-- Dernier verdict -->
                <div v-if="lastVerdict" class="border border-slate-200 rounded-xl bg-white p-4">
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Dernier verdict (saison {{ lastVerdict.season }})</h4>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                        <span>Classé <strong>{{ lastVerdict.rank }}<sup>e</sup></strong> (objectif top {{ lastVerdict.target_rank }})</span>
                        <span :class="lastVerdict.met ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-semibold'">
                            {{ lastVerdict.met ? '✅ Objectif atteint' : '❌ Objectif manqué' }}
                        </span>
                        <span :class="lastVerdict.delta >= 0 ? 'text-emerald-600' : 'text-rose-600'" class="font-bold">
                            Confiance {{ lastVerdict.delta >= 0 ? '+' : '' }}{{ lastVerdict.delta }}
                        </span>
                        <span class="font-semibold">{{ verdictOutcomeLabel(lastVerdict.outcome) }}</span>
                    </div>
                </div>

                <!-- Historique des saisons -->
                <div v-if="careerHistory.length" class="border border-slate-200 rounded-xl bg-white p-4">
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Historique des saisons</h4>
                    <table class="w-full text-xs">
                        <thead>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="text-left py-1">Saison</th>
                            <th class="text-center py-1">Objectif</th>
                            <th class="text-center py-1">Classé</th>
                            <th class="text-center py-1">Confiance</th>
                            <th class="text-right py-1">Résultat</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="v in careerHistory" :key="v.season" class="border-b border-slate-50">
                            <td class="py-1.5 font-semibold text-slate-700">S{{ v.season }}</td>
                            <td class="py-1.5 text-center text-slate-500">top {{ v.target_rank }}</td>
                            <td class="py-1.5 text-center font-semibold"
                                :class="v.met ? 'text-emerald-600' : 'text-rose-500'">{{ v.rank }}<sup>e</sup></td>
                            <td class="py-1.5 text-center font-bold"
                                :class="v.delta >= 0 ? 'text-emerald-600' : 'text-rose-500'">{{ v.delta >= 0 ? '+' : '' }}{{ v.delta }}</td>
                            <td class="py-1.5 text-right">
                                <span v-if="v.champion" title="Champion">🏆</span>
                                <span class="text-slate-500">{{ verdictOutcomeLabel(v.outcome) }}</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- RÈGLES STATIQUES -->
                <div class="border border-slate-200 rounded-xl bg-white p-4 flex flex-col gap-3">
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Comment évolue la confiance</h4>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-100 py-2">
                            <div class="font-black text-emerald-600">+5</div>
                            <div class="text-slate-500">Victoire</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 border border-slate-100 py-2">
                            <div class="font-black text-slate-500">+1</div>
                            <div class="text-slate-500">Match nul</div>
                        </div>
                        <div class="rounded-lg bg-rose-50 border border-rose-100 py-2">
                            <div class="font-black text-rose-500">−5</div>
                            <div class="text-slate-500">Défaite</div>
                        </div>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-1 list-disc pl-4">
                        <li><strong>Exploit</strong> (+3) : battre une équipe plus forte que la tienne.</li>
                        <li><strong>Contre-performance</strong> (−3) : perdre contre une équipe plus faible.</li>
                        <li><strong>Fin de saison</strong> : gros bonus si l'objectif est atteint (et encore plus en le dépassant), forte pénalité sinon — d'autant plus que le ratage est large.</li>
                        <li><strong>Titre de champion</strong> : compte pour ta condition de victoire de carrière.</li>
                    </ul>
                </div>

                <!-- Barème par difficulté -->
                <div class="border border-slate-200 rounded-xl bg-white p-4">
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Niveaux de difficulté</h4>
                    <table class="w-full text-xs">
                        <thead>
                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="text-left py-1">Niveau</th>
                            <th class="text-center py-1">Confiance départ</th>
                            <th class="text-center py-1">Titres à gagner</th>
                            <th class="text-left py-1 pl-3">Exigence</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="d in DIFFICULTY_PRESETS" :key="d.key" class="border-b border-slate-50"
                            :class="career && career.difficulty === d.key ? 'bg-teal-50' : ''">
                            <td class="py-1.5 font-semibold text-slate-700">
                                {{ d.label }}
                                <span v-if="career && career.difficulty === d.key" class="text-[9px] font-bold text-teal-600 ml-1">• en cours</span>
                            </td>
                            <td class="py-1.5 text-center text-slate-600">{{ d.confidence }}</td>
                            <td class="py-1.5 text-center text-slate-600">{{ d.titles }} 🏆</td>
                            <td class="py-1.5 pl-3 text-slate-500">{{ d.rank }}</td>
                        </tr>
                        </tbody>
                    </table>
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
