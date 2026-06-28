<script setup>
import { computed, ref } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import RadarChart from '@/Pages/GameSaves/Play/components/RadarChart.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import SecondaryPositions from '@/Pages/GameSaves/Play/components/SecondaryPositions.vue';

const props = defineProps({
    availableFreePlayers: { type: Array,   required: true },
    team:                 { type: Object,  default: null },
    teamBudget:           { type: Number,  required: true },
    showTransferModal:    { type: Boolean, required: true },
    transferTarget:       { type: Object,  default: null },
    transferMatches:      { type: Number,  required: true },
    transferSalary:       { type: Number,  required: true },
    transferReason:       { type: String,  required: true },
    transferTotalCost:    { type: Number,  required: true },
    transferHistory:      { type: Array, default: () => [] },
    roster:               { type: Array,   default: () => [] },
});

const emit = defineEmits([
    'open-modal', 'close-modal', 'confirm-transfer',
    'update:transferMatches', 'update:transferSalary', 'update:transferReason',
]);

// ==========================
//   HELPERS
// ==========================
const { overallOf, playerPhotoUrl, positionGroup, keyStatsFor, statLabel } = usePlayerUtils();


// ==========================
//   RECHERCHE NOM (comme le draft)
// ==========================
const searchQuery = ref('');

// ==========================
//   FILTRE ORIGINE (œuvre de provenance)
// ==========================
const originFilter = ref('ALL');
const originFilters = [
    { key: 'ALL',                 label: 'Toutes' },
    { key: 'captain_tsubasa',     label: 'Captain Tsubasa' },
    { key: 'blue_lock',           label: 'Blue Lock' },
    { key: 'hungry_heart',        label: 'Hungry Heart' },
    { key: 'ecole_des_champions', label: "L'École des Champions" },
    { key: 'ao_ashi',             label: 'Ao Ashi' },
    { key: 'original',            label: 'Originaux' },
];

const originCount = (key) =>
    key === 'ALL'
        ? props.availableFreePlayers.length
        : props.availableFreePlayers.filter(p => p.origin === key).length;

// Liste réduite à l'origine choisie (sert aux compteurs de poste + à la liste).
const originScoped = computed(() =>
    originFilter.value === 'ALL'
        ? props.availableFreePlayers
        : props.availableFreePlayers.filter(p => p.origin === originFilter.value)
);

// ==========================
//   FILTRE POSTE
// ==========================
const posFilter = ref('ALL');
const filters = [
    { key: 'ALL', label: 'Tous' },
    { key: 'GK',  label: 'Gardiens' },
    { key: 'DEF', label: 'Défenseurs' },
    { key: 'MID', label: 'Milieux' },
    { key: 'ATT', label: 'Attaquants' },
];

const posCount = (key) =>
    key === 'ALL'
        ? originScoped.value.length
        : originScoped.value.filter(p => positionGroup(p.position) === key).length;

const filteredPlayers = computed(() => {
    let list = originScoped.value;

    if (posFilter.value !== 'ALL') {
        list = list.filter(p => positionGroup(p.position) === posFilter.value);
    }

    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(p =>
            (p.lastname?.toLowerCase().includes(q)) ||
            (p.firstname?.toLowerCase().includes(q))
        );
    }

    return list;
});

// ==========================
//   JOUEUR SÉLECTIONNÉ
// ==========================
const selectedPlayer = ref(null);
const selectPlayer = (p) => {
    selectedPlayer.value = p;
    // Le salaire fait autorité côté serveur (coût ajusté à la polyvalence) :
    // on l'affiche sans le rendre éditable, le serveur l'imposera de toute façon.
    emit('update:transferSalary', p.adjusted_cost ?? p.cost ?? 0);
    emit('update:transferMatches', 10);
};

// Meilleur joueur de mon effectif au même poste (pour comparaison)
const bestTeamPlayerSamePos = computed(() => {
    if (!selectedPlayer.value || !props.roster.length) return null;
    const group = positionGroup(selectedPlayer.value.position);
    const samePos = props.roster.filter(p => positionGroup(p.position) === group);
    if (!samePos.length) return null;
    return samePos.reduce((best, p) => overallOf(p) > overallOf(best) ? p : best, samePos[0]);
});

// ==========================
//   COMPARATIF DE 2 JOUEURS (comme dans le draft)
// ==========================
const compareList = ref([]);

const isComparing = (player) => compareList.value.some(p => p.id === player.id);

const toggleCompare = (player) => {
    const idx = compareList.value.findIndex(p => p.id === player.id);
    if (idx !== -1) {
        compareList.value.splice(idx, 1);
        return;
    }
    if (compareList.value.length >= 2) compareList.value.shift();
    compareList.value.push(player);
};

const clearCompare = () => { compareList.value = []; };

const compareStatRows = [
    { key: 'overall', label: 'OVR' },
    { key: 'speed', label: 'Vitesse' },
    { key: 'stamina', label: 'Endurance' },
    { key: 'shot', label: 'Tir' },
    { key: 'pass', label: 'Passe' },
    { key: 'dribble', label: 'Dribble' },
    { key: 'attack', label: 'Attaque' },
    { key: 'defense', label: 'Défense' },
    { key: 'tackle', label: 'Tacle' },
    { key: 'intercept', label: 'Interception' },
    { key: 'block', label: 'Blocage' },
    { key: 'hand_save', label: 'Main' },
    { key: 'punch_save', label: 'Poing' },
    { key: 'cost', label: 'Coût (€/match)', lowerBetter: true },
];

const compareStatValue = (player, key) => {
    if (key === 'overall') return overallOf(player);
    const src = player?.stats ?? player;
    return Number(src?.[key] ?? player?.[key] ?? 0);
};

// Vrai si `player` est meilleur que `other` sur cette stat (coût : plus bas = mieux)
const compareWins = (player, other, row) => {
    const v = compareStatValue(player, row.key);
    const o = compareStatValue(other, row.key);
    if (v === o) return false;
    return row.lowerBetter ? v < o : v > o;
};

// ==========================
//   BUDGET
// ==========================
const budgetAfter = computed(() => props.teamBudget - props.transferTotalCost);
const canAfford   = computed(() => budgetAfter.value >= 0);

const historyTeamFilter = ref(null);

const historyTeams = computed(() => {
    const seen = new Set();
    return props.transferHistory
        .filter(e => {
            if (seen.has(e.team.id)) return false;
            seen.add(e.team.id);
            return true;
        })
        .map(e => e.team);
});

const filteredHistory = computed(() =>
    historyTeamFilter.value !== null
        ? props.transferHistory.filter(e => Number(e.team.id) === Number(historyTeamFilter.value))
        : props.transferHistory
);
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- Header : Marché (1/4) + recherche & filtres (3/4) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-stretch">

            <!-- Marché des transferts (1/4) -->
            <div class="md:col-span-3 border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col gap-3">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Marché des transferts</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ availableFreePlayers.length }} joueur(s) sans contrat disponible(s)</p>
                </div>
                <div class="flex flex-wrap gap-2 mt-auto">
                    <div class="text-center px-4 py-2 rounded-xl border bg-teal-50 border-teal-200">
                        <div class="text-xl font-black text-teal-600">{{ teamBudget }} €</div>
                        <div class="text-[10px] text-teal-400 font-semibold">Budget actuel</div>
                    </div>
                    <div v-if="transferTarget" class="text-center px-4 py-2 rounded-xl border"
                         :class="canAfford ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200'">
                        <div class="text-xl font-black" :class="canAfford ? 'text-emerald-600' : 'text-rose-500'">
                            {{ budgetAfter }} €
                        </div>
                        <div class="text-[10px] font-semibold" :class="canAfford ? 'text-emerald-400' : 'text-rose-400'">
                            Après transfert
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recherche + filtres (3/4) -->
            <div class="md:col-span-9 flex flex-col gap-3">
                <!-- Barre de recherche (comme le draft) -->
                <input type="search" v-model="searchQuery"
                       class="w-full px-4 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-teal-300 focus:outline-none"
                       placeholder="Rechercher un joueur..."/>

                <!-- Filtres origine (œuvre de provenance) -->
                <div class="flex gap-2 flex-wrap">
                    <button v-for="o in originFilters" :key="o.key" type="button"
                            @click="originFilter = o.key"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                            :class="originFilter === o.key
                            ? 'bg-indigo-500 text-white border-indigo-600 shadow-sm'
                            : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600'">
                        {{ o.label }}
                        <span class="ml-1 opacity-60">({{ originCount(o.key) }})</span>
                    </button>
                </div>

                <!-- Filtres poste -->
                <div class="flex gap-2 flex-wrap">
                    <button v-for="f in filters" :key="f.key" type="button"
                            @click="posFilter = f.key"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                            :class="posFilter === f.key
                            ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                            : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300 hover:text-teal-600'">
                        {{ f.label }}
                        <span class="ml-1 opacity-60">({{ posCount(f.key) }})</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

            <!-- ============================================ -->
            <!-- LISTE JOUEURS LIBRES                         -->
            <!-- ============================================ -->
            <div class="lg:col-span-5 border border-slate-200 rounded-xl bg-slate-50 p-3 max-h-[690px] overflow-y-auto">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Agents libres</h3>

                <div v-if="filteredPlayers.length" class="space-y-1">
                    <div v-for="p in filteredPlayers" :key="p.id"
                         @click="selectPlayer(p)"
                         class="flex items-center gap-3 px-3 py-2 rounded-lg border cursor-pointer transition-all"
                         :class="selectedPlayer?.id === p.id
                            ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                            : 'bg-white hover:bg-slate-50 border-slate-100 hover:border-teal-200'">

                        <!-- Photo -->
                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 shrink-0 border-2"
                             :class="selectedPlayer?.id === p.id ? 'border-white/50' : 'border-slate-100'">
                            <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt=""/>
                            <div v-else class="w-full h-full flex items-center justify-center text-[10px] text-slate-400">?</div>
                        </div>

                        <!-- Infos -->
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold truncate"
                                 :class="selectedPlayer?.id === p.id ? 'text-white' : 'text-slate-800'">
                                {{ p.lastname }} {{ p.firstname }}
                            </div>
                            <div class="text-[10px] truncate"
                                 :class="selectedPlayer?.id === p.id ? 'text-white/70' : 'text-slate-400'">
                                {{ p.position }}
                            </div>
                        </div>

                        <!-- Stats clés -->
                        <div class="flex gap-1 shrink-0">
                            <div v-for="k in keyStatsFor(p.position).slice(0,2)" :key="k"
                                 class="text-center w-8">
                                <div class="text-[11px] font-black"
                                     :class="selectedPlayer?.id === p.id ? 'text-white' : 'text-slate-700'">
                                    {{ p[k] ?? p.stats?.[k] ?? '—' }}
                                </div>
                                <div class="text-[8px]"
                                     :class="selectedPlayer?.id === p.id ? 'text-white/60' : 'text-slate-400'">
                                    {{ statLabel(k) }}
                                </div>
                            </div>
                        </div>

                        <!-- Overall -->
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-black shrink-0"
                             :class="selectedPlayer?.id === p.id
                                ? 'bg-white/20 text-white'
                                : overallOf(p) >= 70 ? 'bg-emerald-100 text-emerald-700'
                                : overallOf(p) >= 50 ? 'bg-amber-100 text-amber-700'
                                : 'bg-slate-100 text-slate-500'">
                            {{ overallOf(p) }}
                        </div>

                        <!-- Comparer -->
                        <button type="button"
                                @click.stop="toggleCompare(p)"
                                class="w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0 border transition-all"
                                :class="isComparing(p)
                                    ? 'bg-amber-400 border-amber-500 text-white shadow-sm'
                                    : selectedPlayer?.id === p.id
                                        ? 'border-white/40 text-white/80 hover:bg-white/10'
                                        : 'border-slate-200 text-slate-400 hover:border-amber-300 hover:text-amber-500'"
                                :title="isComparing(p) ? 'Retirer de la comparaison' : 'Ajouter à la comparaison'">
                            ⚖️
                        </button>
                    </div>
                </div>

                <div v-else class="flex items-center justify-center py-8 text-slate-400 text-xs italic">
                    {{ (searchQuery || posFilter !== 'ALL' || originFilter !== 'ALL')
                        ? 'Aucun agent libre ne correspond aux filtres'
                        : 'Aucun agent libre disponible' }}
                </div>
            </div>

            <!-- ============================================ -->
            <!-- PROFIL + OFFRE                               -->
            <!-- ============================================ -->
            <div v-if="selectedPlayer" class="lg:col-span-7 flex flex-col gap-3">

                <!-- Identité -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <div class="flex items-start gap-4">
                        <div class="relative shrink-0">
                            <div class="w-20 h-20 rounded-xl border-2 border-slate-200 bg-white overflow-hidden">
                                <img v-if="playerPhotoUrl(selectedPlayer)" :src="playerPhotoUrl(selectedPlayer)" class="w-full h-full object-cover" alt=""/>
                                <div v-else class="w-full h-full flex items-center justify-center text-3xl text-slate-200">👤</div>
                            </div>
                            <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-[11px] font-black shadow"
                                 :class="overallOf(selectedPlayer)>=70?'bg-emerald-500 text-white':overallOf(selectedPlayer)>=50?'bg-amber-400 text-slate-900':'bg-slate-400 text-white'">
                                {{ overallOf(selectedPlayer) }}
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-slate-800">{{ selectedPlayer.firstname }} {{ selectedPlayer.lastname }}</h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ selectedPlayer.position }} • Coût de base : {{ selectedPlayer.cost ?? 0 }} €/match</p>
                            <SecondaryPositions :player="selectedPlayer" class="mt-1.5" />
                            <p v-if="selectedPlayer.description" class="text-xs text-slate-500 mt-2 italic">{{ selectedPlayer.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Radar + stats comparées -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <RadarChart :player="selectedPlayer"
                                accent="teal"
                                :comparePlayer="bestTeamPlayerSamePos"
                                :compareLabel="bestTeamPlayerSamePos ? `${bestTeamPlayerSamePos.lastname} (ton équipe)` : null" />

                    <StatBars :player="selectedPlayer"
                              :comparePlayer="bestTeamPlayerSamePos"
                              :compareLabel="bestTeamPlayerSamePos?.lastname" />
                </div>

                <!-- Formulaire offre -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">💼 Proposer un contrat</h4>

                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Durée (matchs)</label>
                            <input type="number" min="1" max="60"
                                   :value="transferMatches"
                                   @input="emit('update:transferMatches', +$event.target.value)"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-teal-300 focus:outline-none"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Salaire / match (€)</label>
                            <div class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-slate-100 text-slate-600 font-semibold flex items-center justify-between">
                                <span>{{ transferSalary }} €</span>
                                <span class="text-[10px] text-slate-400 font-normal">fixé</span>
                            </div>
                        </div>
                    </div>

                    <!-- Résumé coût -->
                    <div class="flex items-center gap-3 p-3 rounded-lg mb-3"
                         :class="canAfford ? 'bg-emerald-50 border border-emerald-200' : 'bg-rose-50 border border-rose-200'">
                        <div class="flex-1">
                            <div class="text-xs text-slate-500">Coût total</div>
                            <div class="text-lg font-black" :class="canAfford ? 'text-emerald-600' : 'text-rose-500'">
                                {{ transferTotalCost }} €
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-500">Budget restant</div>
                            <div class="text-sm font-bold" :class="canAfford ? 'text-emerald-600' : 'text-rose-500'">
                                {{ budgetAfter }} €
                            </div>
                        </div>
                        <div v-if="!canAfford" class="text-rose-500 text-sm">⚠️ Budget insuffisant</div>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <button type="button"
                                class="px-4 py-1.5 text-sm rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100 transition-all"
                                @click="selectedPlayer = null">
                            Annuler
                        </button>
                        <button type="button"
                                class="px-5 py-1.5 text-sm rounded-full font-semibold transition-all disabled:opacity-40"
                                :class="canAfford && team && transferMatches > 0
                                ? 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm'
                                : 'bg-slate-200 text-slate-400'"
                                :disabled="!canAfford || !team || transferMatches <= 0"
                                @click="emit('open-modal', selectedPlayer); emit('confirm-transfer')">
                            Confirmer l'offre
                        </button>
                    </div>
                </div>
            </div>

            <!-- Placeholder si aucun joueur sélectionné -->
            <div v-else class="lg:col-span-7 flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
                <div class="text-center">
                    <div class="text-3xl mb-2">👤</div>
                    <p>Sélectionne un joueur pour voir son profil et faire une offre</p>
                </div>
            </div>
        </div>
        <!-- ============================================ -->
        <!-- HISTORIQUE TRANSFERTS                         -->
        <!-- ============================================ -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4 mt-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">📋 Historique des transferts</h4>
                <!-- Filtre équipe -->
                <select :value="historyTeamFilter ?? 'null'"
                        @change="historyTeamFilter = $event.target.value === 'null' ? null : $event.target.value"
                        class="border border-slate-300 rounded-lg px-3 py-1 text-xs bg-white focus:ring-2 focus:ring-teal-300 focus:outline-none">
                    <option value="null">Toutes les équipes  </option>
                    <option v-for="t in historyTeams" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </div>

            <div v-if="filteredHistory.length" class="space-y-1.5 max-h-[300px] overflow-y-auto pr-1">
                <div v-for="entry in filteredHistory" :key="entry.player.id + '-' + entry.start_week"
                     class="flex items-center gap-3 px-3 py-2 rounded-lg bg-white border border-slate-100">
                    <!-- Photo -->
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                        <img v-if="playerPhotoUrl(entry.player)" :src="playerPhotoUrl(entry.player)" class="w-full h-full object-cover" alt=""/>
                        <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                    </div>
                    <!-- Nom -->
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-slate-700 truncate">
                            {{ entry.player.firstname }} {{ entry.player.lastname }}
                        </div>
                        <div class="text-[10px] text-slate-400">{{ entry.player.position }}</div>
                    </div>
                    <!-- Équipe -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-xs text-slate-600 font-semibold">→ {{ entry.team.name }}</span>
                    </div>
                    <!-- Semaine -->
                    <div class="text-[10px] text-slate-400 shrink-0">S{{ entry.start_week }}</div>
                    <!-- Salaire -->
                    <div class="text-xs font-bold text-teal-600 shrink-0">{{ entry.salary }} €</div>
                </div>
            </div>

            <div v-else class="text-xs text-slate-400 italic py-3 text-center">
                Aucun transfert enregistré pour le moment.
            </div>
        </div>

        <!-- ============================================ -->
        <!-- PANNEAU DE COMPARAISON (2 joueurs)           -->
        <!-- ============================================ -->
        <Teleport to="body">
            <div v-if="compareList.length > 0"
                 class="fixed bottom-4 inset-x-0 z-50 flex justify-center px-4 pointer-events-none">
                <div class="bg-white border border-slate-200 shadow-2xl rounded-2xl p-4 w-full max-w-2xl pointer-events-auto">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-slate-700">⚖️ Comparaison de joueurs</h3>
                        <button type="button" @click="clearCompare"
                                class="text-xs text-slate-400 hover:text-slate-600 font-semibold">
                            ✕ Fermer
                        </button>
                    </div>

                    <div v-if="compareList.length < 2" class="text-xs text-slate-400 text-center py-4">
                        Sélectionne un second joueur (⚖️) pour comparer.
                    </div>

                    <table v-else class="w-full text-xs">
                        <thead>
                        <tr class="text-slate-500">
                            <th class="text-left py-1 px-2 font-semibold">Stat</th>
                            <th v-for="p in compareList" :key="p.id" class="py-1 px-2 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                        <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt=""/>
                                        <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                                    </div>
                                    <div class="text-left leading-tight">
                                        <div class="text-slate-800">{{ p.lastname }}</div>
                                        <div class="text-[9px] text-slate-400 font-normal">{{ p.position }}</div>
                                    </div>
                                    <button type="button" @click="toggleCompare(p)"
                                            class="text-slate-300 hover:text-rose-500" title="Retirer">✕</button>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="row in compareStatRows" :key="row.key" class="border-t border-slate-50">
                            <td class="py-1 px-2 text-slate-500">{{ row.label }}</td>
                            <td v-for="(p, i) in compareList" :key="p.id"
                                class="py-1 px-2 text-center font-semibold"
                                :class="compareWins(p, compareList[1 - i], row) ? 'text-emerald-600' : 'text-slate-600'">
                                {{ compareStatValue(p, row.key) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </Teleport>
    </div>
</template>
