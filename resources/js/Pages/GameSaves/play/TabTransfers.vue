<script setup>
import { computed, ref } from 'vue';

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
    roster:               { type: Array,   default: () => [] },
});

const emit = defineEmits([
    'open-modal', 'close-modal', 'confirm-transfer',
    'update:transferMatches', 'update:transferSalary', 'update:transferReason',
]);

// ==========================
//   HELPERS
// ==========================
const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path)         return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
};

const overallOf = (p) => {
    if (!p) return 0;
    const keys = ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle','hand_save','punch_save'];
    const vals = keys.map(k => Number(p[k] ?? p.stats?.[k] ?? 0)).filter(v => isFinite(v));
    return vals.length ? Math.round(vals.reduce((a, b) => a + b, 0) / vals.length) : 0;
};

const positionGroup = (pos) => {
    const p = (pos ?? '').toUpperCase();
    if (p.includes('GK') || p.includes('GOAL')) return 'GK';
    if (p.includes('DEF') || p.includes('BACK')) return 'DEF';
    if (p.includes('MDF') || p.includes('MID') || p.includes('MOF')) return 'MID';
    if (p.includes('ATT') || p.includes('FOR') || p.includes('FORWARD')) return 'ATT';
    return 'OTHER';
};

const keyStatsFor = (pos) => {
    const g = positionGroup(pos);
    if (g === 'GK')  return ['hand_save','punch_save','defense','block'];
    if (g === 'DEF') return ['defense','tackle','block','intercept'];
    if (g === 'MID') return ['pass','intercept','attack','dribble'];
    if (g === 'ATT') return ['shot','dribble','attack','speed'];
    return ['attack','defense','pass','shot'];
};

const statLabel = (k) => ({
    shot:'Tir', pass:'Passe', dribble:'Dribble', attack:'Att',
    defense:'Def', speed:'Vit', block:'Block', intercept:'Interc',
    tackle:'Tacle', stamina:'End', hand_save:'Main', punch_save:'Poings',
}[k] ?? k);

const statColor = (k) => ({
    shot:'bg-red-400', pass:'bg-teal-400', dribble:'bg-yellow-400', attack:'bg-orange-400',
    defense:'bg-blue-400', speed:'bg-sky-400', block:'bg-indigo-400', intercept:'bg-purple-400',
    tackle:'bg-pink-400', stamina:'bg-emerald-400', hand_save:'bg-violet-400', punch_save:'bg-fuchsia-400',
}[k] ?? 'bg-slate-400');

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

const filteredPlayers = computed(() => {
    if (posFilter.value === 'ALL') return props.availableFreePlayers;
    return props.availableFreePlayers.filter(p => positionGroup(p.position) === posFilter.value);
});

// ==========================
//   JOUEUR SÉLECTIONNÉ
// ==========================
const selectedPlayer = ref(null);
const selectPlayer = (p) => {
    selectedPlayer.value = p;
    // Pré-remplir le formulaire d'offre
    emit('update:transferSalary', p.cost ?? 0);
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
//   RADAR
// ==========================
const RADAR_STATS = [
    { key: 'shot',    label: 'Tir'     },
    { key: 'pass',    label: 'Passe'   },
    { key: 'dribble', label: 'Dribble' },
    { key: 'defense', label: 'Défense' },
    { key: 'speed',   label: 'Vitesse' },
    { key: 'stamina', label: 'Stamina' },
];

const radarPointsFor = (player) => {
    if (!player) return [];
    const cx = 80, cy = 80, r = 60;
    return RADAR_STATS.map((s, i) => {
        const val = Math.min(Number(player[s.key] ?? player.stats?.[s.key] ?? 0) / 100, 1);
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return {
            x: cx + r * val * Math.cos(angle),
            y: cy + r * val * Math.sin(angle),
            lx: cx + (r + 14) * Math.cos(angle),
            ly: cy + (r + 14) * Math.sin(angle),
            label: s.label,
        };
    });
};

const radarGrids = computed(() => {
    const cx = 80, cy = 80, r = 60;
    return [0.25, 0.5, 0.75, 1.0].map(scale =>
        RADAR_STATS.map((_, i) => {
            const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
            return `${cx + r * scale * Math.cos(angle)},${cy + r * scale * Math.sin(angle)}`;
        }).join(' ')
    );
});

const radarAxes = computed(() => {
    const cx = 80, cy = 80, r = 60;
    return RADAR_STATS.map((_, i) => {
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return { x2: cx + r * Math.cos(angle), y2: cy + r * Math.sin(angle) };
    });
});

const radarPolygon = (player) =>
    radarPointsFor(player).map(p => `${p.x},${p.y}`).join(' ');

// ==========================
//   BUDGET
// ==========================
const budgetAfter = computed(() => props.teamBudget - props.transferTotalCost);
const canAfford   = computed(() => budgetAfter.value >= 0);
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-[75vh] max-h-[75vh] pr-1">

        <!-- Header budget -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Marché des transferts</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ availableFreePlayers.length }} joueur(s) sans contrat disponible(s)</p>
                </div>
                <div class="flex gap-4">
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
                <span class="ml-1 opacity-60">
                    ({{ f.key === 'ALL' ? availableFreePlayers.length : availableFreePlayers.filter(p => positionGroup(p.position) === f.key).length }})
                </span>
            </button>
        </div>

        <!-- Contenu principal -->
        <div class="grid grid-cols-12 gap-4">

            <!-- ============================================ -->
            <!-- LISTE JOUEURS LIBRES                         -->
            <!-- ============================================ -->
            <div class="col-span-5 border border-slate-200 rounded-xl bg-slate-50 p-3 max-h-[520px] overflow-y-auto">
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
                    </div>
                </div>

                <div v-else class="flex items-center justify-center py-8 text-slate-400 text-xs italic">
                    Aucun agent libre {{ posFilter !== 'ALL' ? 'à ce poste' : '' }}
                </div>
            </div>

            <!-- ============================================ -->
            <!-- PROFIL + OFFRE                               -->
            <!-- ============================================ -->
            <div v-if="selectedPlayer" class="col-span-7 flex flex-col gap-3">

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
                            <p v-if="selectedPlayer.description" class="text-xs text-slate-500 mt-2 italic">{{ selectedPlayer.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Radar + stats comparées -->
                <div class="grid grid-cols-2 gap-3">

                    <!-- Radar -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-col items-center">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 self-start">Profil technique</h4>
                        <svg viewBox="0 0 160 160" class="w-36 h-36">
                            <!-- Grille adversaire (comparaison) -->
                            <polygon v-if="bestTeamPlayerSamePos"
                                     :points="radarPolygon(bestTeamPlayerSamePos)"
                                     fill="rgba(148,163,184,0.15)" stroke="#94a3b8" stroke-width="1" stroke-dasharray="3,2"/>
                            <!-- Grille joueur -->
                            <polygon v-for="(pts,i) in radarGrids" :key="i" :points="pts" fill="none" stroke="#e2e8f0" stroke-width="0.8"/>
                            <line v-for="(ax,i) in radarAxes" :key="'a'+i" x1="80" y1="80" :x2="ax.x2" :y2="ax.y2" stroke="#e2e8f0" stroke-width="0.8"/>
                            <polygon :points="radarPolygon(selectedPlayer)" fill="rgba(20,184,166,0.2)" stroke="#14b8a6" stroke-width="1.5" stroke-linejoin="round"/>
                            <circle v-for="(pt,i) in radarPointsFor(selectedPlayer)" :key="'p'+i" :cx="pt.x" :cy="pt.y" r="2" fill="#14b8a6" stroke="white" stroke-width="1"/>
                            <text v-for="(pt,i) in radarPointsFor(selectedPlayer)" :key="'t'+i" :x="pt.lx" :y="pt.ly" text-anchor="middle" dominant-baseline="middle" font-size="7" fill="#94a3b8" font-weight="600">{{ pt.label }}</text>
                        </svg>
                        <p v-if="bestTeamPlayerSamePos" class="text-[9px] text-slate-400 mt-1">
                            <span class="inline-block w-3 h-0.5 bg-slate-300 mr-1 align-middle"></span>
                            {{ bestTeamPlayerSamePos.lastname }} (ton équipe)
                        </p>
                    </div>

                    <!-- Barres stats -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Statistiques</h4>
                        <div class="space-y-1.5">
                            <div v-for="stat in [
                                {label:'Vitesse',  key:'speed'},
                                {label:'Stamina',  key:'stamina'},
                                {label:'Attaque',  key:'attack'},
                                {label:'Défense',  key:'defense'},
                                {label:'Tir',      key:'shot'},
                                {label:'Passe',    key:'pass'},
                                {label:'Dribble',  key:'dribble'},
                                {label:'Block',    key:'block'},
                                {label:'Interc.',  key:'intercept'},
                                {label:'Tacle',    key:'tackle'},
                            ]" :key="stat.key" class="flex items-center gap-2 text-xs">
                                <span class="w-14 text-slate-500 shrink-0 text-[11px]">{{ stat.label }}</span>
                                <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden relative">
                                    <!-- Barre comparaison équipe -->
                                    <div v-if="bestTeamPlayerSamePos"
                                         class="absolute h-full rounded-full opacity-30 bg-slate-500"
                                         :style="{width: Math.min(bestTeamPlayerSamePos[stat.key] ?? 0, 100)+'%'}">
                                    </div>
                                    <!-- Barre joueur -->
                                    <div class="h-full rounded-full" :class="statColor(stat.key)"
                                         :style="{width: Math.min(selectedPlayer[stat.key] ?? selectedPlayer.stats?.[stat.key] ?? 0, 100)+'%'}">
                                    </div>
                                </div>
                                <span class="w-6 text-right font-bold text-slate-700 text-[11px]">
                                    {{ selectedPlayer[stat.key] ?? selectedPlayer.stats?.[stat.key] ?? '—' }}
                                </span>
                                <span v-if="bestTeamPlayerSamePos" class="w-5 text-right text-[10px] text-slate-400">
                                    {{ bestTeamPlayerSamePos[stat.key] ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <p v-if="bestTeamPlayerSamePos" class="text-[9px] text-slate-400 mt-2">
                            Comparé à {{ bestTeamPlayerSamePos.lastname }}
                        </p>
                    </div>
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
                            <input type="number" min="0"
                                   :value="transferSalary"
                                   @input="emit('update:transferSalary', +$event.target.value)"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-teal-300 focus:outline-none"/>
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
            <div v-else class="col-span-7 flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
                <div class="text-center">
                    <div class="text-3xl mb-2">👤</div>
                    <p>Sélectionne un joueur pour voir son profil et faire une offre</p>
                </div>
            </div>
        </div>
    </div>
</template>
