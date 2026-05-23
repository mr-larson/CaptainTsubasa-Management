<script setup>
import { computed } from 'vue';
import { FORMATIONS, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';

const props = defineProps({
    // Équipes
    otherTeams:           { type: Array,    required: true },
    selectedOtherTeam:    { type: Object,   default: null },
    // Roster + joueur sélectionné
    otherRosterWithStatus:{ type: Array,    required: true },
    selectedOtherPlayer:  { type: Object,   default: null },
    // Lineup
    otherLineupForm:      { type: Array,    required: true },
    otherSelectedSlot:    { type: Number,   default: null },
    otherPlayerPosition:  { type: Function, required: true },
    otherPlayerForSlot:   { type: Function, required: true },
    getOtherSlotForPlayer:{ type: Function, required: true },
    // Formation
    otherFormation:       { type: String,   required: true },
    otherFormationData:   { type: Object,   default: null },
    // Classement / club
    standings:            { type: Array,    required: true },
    injuriesCountForTeam:   { type: Function, required: true },
    suspensionsCountForTeam:{ type: Function, required: true },
    cardsCountForTeam:      { type: Function, required: true },
    averageTeamStat:        { type: Function, required: true },
    playerSeasonStats:      { type: Object,   default: () => ({}) },
});

const emit = defineEmits([
    'select-team',
    'select-player',
    'toggle-starter',
    'change-slot',
    'save-formation',
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

const teamLogoUrl = (t) => {
    const path = t?.logo_path ?? t?.team?.logo_path;
    if (!path) return null;
    if (path.startsWith('http')) return path;
    if (path.startsWith('/'))    return path;
    if (path.startsWith('teams/')) return '/images/' + path;
    return '/' + path;
};

const overallOf = (player) => {
    if (!player) return 0;
    const s = player.stats ?? player;
    const keys = ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle','hand_save','punch_save'];
    const values = keys.map(k => Number(s[k] ?? 0)).filter(v => Number.isFinite(v));
    if (!values.length) return 0;
    return Math.round(values.reduce((a, b) => a + b, 0) / values.length);
};

// ==========================
//   RADAR CHART
// ==========================
const RADAR_STATS = [
    { key: 'shot',    label: 'Tir'     },
    { key: 'pass',    label: 'Passe'   },
    { key: 'dribble', label: 'Dribble' },
    { key: 'defense', label: 'Défense' },
    { key: 'speed',   label: 'Vitesse' },
    { key: 'stamina', label: 'Stamina' },
];

const radarPoints = computed(() => {
    const p = props.selectedOtherPlayer;
    if (!p) return [];
    const cx = 90, cy = 90, r = 68;
    return RADAR_STATS.map((s, i) => {
        const val   = Math.min(Number(p[s.key] ?? p.stats?.[s.key] ?? 0) / 100, 1);
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return {
            x:  cx + r * val * Math.cos(angle),
            y:  cy + r * val * Math.sin(angle),
            lx: cx + (r + 16) * Math.cos(angle),
            ly: cy + (r + 16) * Math.sin(angle),
            label: s.label,
        };
    });
});

const radarPolygon = computed(() => radarPoints.value.map(p => `${p.x},${p.y}`).join(' '));

const radarGrids = computed(() => {
    const cx = 90, cy = 90, r = 68;
    return [0.25, 0.5, 0.75, 1.0].map(scale =>
        RADAR_STATS.map((_, i) => {
            const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
            return `${cx + r * scale * Math.cos(angle)},${cy + r * scale * Math.sin(angle)}`;
        }).join(' ')
    );
});

const radarAxes = computed(() => {
    const cx = 90, cy = 90, r = 68;
    return RADAR_STATS.map((_, i) => {
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return { x2: cx + r * Math.cos(angle), y2: cy + r * Math.sin(angle) };
    });
});

// ── Perf chips ───────────────────────────────────────────────
const perfChips = computed(() => {
    const p = props.selectedOtherPlayer;
    if (!p) return [];
    const perf = props.playerSeasonStats?.[p.id] ?? props.playerSeasonStats?.[String(p.id)];
    if (!perf) return [];
    return [
        { icon: '⚽', label: 'Tirs',     val: perf.offense?.shot?.attempts      ?? 0, sub: perf.offense?.shot?.success      ?? 0, color: 'bg-blue-100 text-blue-700' },
        { icon: '🎯', label: 'Passes',   val: perf.offense?.pass?.attempts      ?? 0, sub: perf.offense?.pass?.success      ?? 0, color: 'bg-sky-100 text-sky-700' },
        { icon: '🔥', label: 'Dribbles', val: perf.offense?.dribble?.attempts   ?? 0, sub: perf.offense?.dribble?.success   ?? 0, color: 'bg-orange-100 text-orange-700' },
        { icon: '🛡️', label: 'Interc.',  val: perf.defense?.intercept?.attempts ?? 0, sub: perf.defense?.intercept?.success ?? 0, color: 'bg-emerald-100 text-emerald-700' },
        { icon: '⚡', label: 'Tacles',   val: perf.defense?.tackle?.attempts    ?? 0, sub: perf.defense?.tackle?.success    ?? 0, color: 'bg-yellow-100 text-yellow-700' },
        { icon: '🧱', label: 'Blocks',   val: perf.defense?.block?.attempts     ?? 0, sub: perf.defense?.block?.success     ?? 0, color: 'bg-slate-100 text-slate-600' },
        { icon: '⚔️', label: 'Gagnés',   val: perf.duelsWon  ?? 0, sub: null, color: 'bg-teal-100 text-teal-700' },
        { icon: '💔', label: 'Perdus',   val: perf.duelsLost ?? 0, sub: null, color: 'bg-rose-100 text-rose-700' },
    ];
});
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[72vh] pr-1">

        <!-- LIGNE 1 : Sélecteur équipe (chips horizontales) -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Équipes de la ligue</h3>
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="t in otherTeams" :key="t.id"
                    type="button"
                    @click="emit('select-team', t)"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                    :class="selectedOtherTeam?.id === t.id
                        ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                        : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300 hover:text-teal-600'"
                >
                    <div class="w-4 h-4 rounded-full overflow-hidden shrink-0 bg-slate-100">
                        <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" class="w-full h-full object-contain" alt=""/>
                    </div>
                    {{ t.name }}
                </button>
            </div>
        </div>

        <!-- LIGNE 2 : Formation + Terrain -->
        <div class="grid grid-cols-12 gap-4" v-if="selectedOtherTeam">

            <!-- Select formation -->
            <div class="col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col gap-3">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Formation</h3>
                <select
                    :value="otherFormation"
                    @change="emit('save-formation', $event.target.value)"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold bg-white text-slate-800 focus:ring-2 focus:ring-teal-300 focus:outline-none cursor-pointer"
                >
                    <option v-for="f in FORMATION_LIST" :key="f.key" :value="f.key">{{ f.label }}</option>
                </select>
                <p class="text-xs text-slate-500 leading-relaxed min-h-[2.5rem]">
                    {{ FORMATIONS[otherFormation]?.description ?? '—' }}
                </p>
                <div class="border-t border-slate-200 pt-3">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Répartition</h4>
                    <div class="flex gap-1.5 flex-wrap">
                        <template v-if="otherFormationData">
                            <span v-for="(label, zone) in ['GK','DEF','MDF','MOF','ATT']" :key="zone"
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                  :class="[zone==0?'bg-yellow-100 text-yellow-700':zone==1?'bg-blue-100 text-blue-700':zone==2?'bg-green-100 text-green-700':zone==3?'bg-orange-100 text-orange-700':'bg-red-100 text-red-700']">
                                {{ label }} ×{{ Object.values(otherFormationData.slots).filter(s => s.zone === zone).length }}
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Grand terrain unifié -->
            <div class="col-span-8 border border-slate-200 rounded-xl overflow-hidden shadow-sm" style="height:240px;">
                <div class="relative w-full h-full">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-800 via-green-700 to-green-800"></div>
                    <div v-for="i in 8" :key="i" class="absolute top-0 h-full bg-green-900/15" :style="{ left:((i-1)*12.5)+'%', width:'12.5%' }"></div>
                    <div class="absolute inset-2 border border-white/40 rounded pointer-events-none"></div>
                    <div class="absolute top-2 bottom-2 left-1/2 w-px bg-white/40"></div>
                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-20 h-20 rounded-full border border-white/30"></div>
                    <div class="absolute left-2 top-1/2 -translate-y-1/2 border border-white/30" style="width:9%;height:55%"></div>
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 border border-white/30" style="width:9%;height:55%"></div>
                    <div class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/15 border border-white/50" style="width:2%;height:22%"></div>
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/15 border border-white/50" style="width:2%;height:22%"></div>
                    <template v-if="otherFormationData">
                        <div
                            v-for="(slotDef, slot) in otherFormationData.slots" :key="slot"
                            class="absolute -translate-x-1/2 -translate-y-1/2 cursor-pointer"
                            :style="{ left: otherPlayerPosition(slot).x+'%', top: otherPlayerPosition(slot).y+'%' }"
                            @click="otherPlayerForSlot(slot) && emit('select-player', otherPlayerForSlot(slot))"
                        >
                            <div class="flex flex-col items-center transition-transform duration-150"
                                 :class="otherSelectedSlot === Number(slot) ? 'scale-125' : 'hover:scale-110'">
                                <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center overflow-hidden shadow-md"
                                     :class="[
                                        otherSelectedSlot === Number(slot) ? 'border-yellow-300 ring-2 ring-yellow-200' : 'border-white/70',
                                        slotDef.zone===0?'bg-yellow-500':slotDef.zone===1?'bg-blue-500':slotDef.zone===2?'bg-green-500':slotDef.zone===3?'bg-orange-500':'bg-red-500'
                                    ]">
                                    <img v-if="otherPlayerForSlot(slot) && playerPhotoUrl(otherPlayerForSlot(slot))"
                                         :src="playerPhotoUrl(otherPlayerForSlot(slot))" class="w-full h-full object-cover" alt=""/>
                                    <span v-else class="text-[9px] font-bold text-white">{{ slot }}</span>
                                </div>
                                <div class="mt-0.5 px-1 rounded text-[7px] font-semibold leading-tight text-center max-w-[48px] truncate"
                                     :class="otherSelectedSlot === Number(slot) ? 'bg-yellow-300 text-slate-900' : 'bg-black/50 text-white'">
                                    {{ otherPlayerForSlot(slot)?.lastname ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- LIGNE 3 : Infos club -->
        <div class="grid grid-cols-12 gap-4" v-if="selectedOtherTeam">

            <!-- Logo + nom + description -->
            <div class="col-span-5 border border-slate-200 rounded-xl bg-slate-50 p-4 flex items-center gap-4">
                <div class="w-20 h-20 rounded-xl overflow-hidden bg-white border border-slate-200 shrink-0 flex items-center justify-center">
                    <img v-if="teamLogoUrl(selectedOtherTeam)" :src="teamLogoUrl(selectedOtherTeam)" class="w-full h-full object-contain" alt=""/>
                    <span v-else class="text-3xl">🏟️</span>
                </div>
                <div class="min-w-0 flex flex-col gap-1">
                    <h3 class="text-base font-bold text-slate-800 truncate">{{ selectedOtherTeam.name }}</h3>
                    <p v-if="standings?.length" class="text-xs text-slate-500">
                        {{ (standings.findIndex(r => r.id === selectedOtherTeam.id) + 1) || '—' }}<sup>e</sup>
                        / {{ standings.length }}
                        &nbsp;•&nbsp; {{ selectedOtherTeam.budget ?? 0 }} €
                    </p>
                    <p v-if="selectedOtherTeam.description" class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                        {{ selectedOtherTeam.description }}
                    </p>
                </div>
            </div>

            <!-- Bilan -->
            <div class="col-span-3 border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col justify-center gap-1.5">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Bilan</h4>
                <div class="flex gap-2">
                    <div class="flex-1 text-center bg-emerald-50 rounded-lg py-2">
                        <div class="text-lg font-black text-emerald-600">{{ selectedOtherTeam.wins ?? 0 }}</div>
                        <div class="text-[10px] text-emerald-500 font-semibold">Victoires</div>
                    </div>
                    <div class="flex-1 text-center bg-slate-100 rounded-lg py-2">
                        <div class="text-lg font-black text-slate-500">{{ selectedOtherTeam.draws ?? 0 }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Nuls</div>
                    </div>
                    <div class="flex-1 text-center bg-rose-50 rounded-lg py-2">
                        <div class="text-lg font-black text-rose-500">{{ selectedOtherTeam.losses ?? 0 }}</div>
                        <div class="text-[10px] text-rose-400 font-semibold">Défaites</div>
                    </div>
                </div>
            </div>

            <!-- Stats + Effectif -->
            <div class="col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Club</h4>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Attaque moy.</span>
                        <span class="font-bold text-slate-800">{{ averageTeamStat(otherRosterWithStatus, 'attack') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Blessés</span>
                        <span class="font-bold" :class="injuriesCountForTeam(selectedOtherTeam.id) > 0 ? 'text-rose-500' : 'text-slate-800'">
                            {{ injuriesCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Défense moy.</span>
                        <span class="font-bold text-slate-800">{{ averageTeamStat(otherRosterWithStatus, 'defense') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Suspensions</span>
                        <span class="font-bold" :class="suspensionsCountForTeam(selectedOtherTeam.id) > 0 ? 'text-amber-500' : 'text-slate-800'">
                            {{ suspensionsCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Stamina moy.</span>
                        <span class="font-bold text-slate-800">{{ averageTeamStat(otherRosterWithStatus, 'stamina') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Cartons</span>
                        <span class="font-bold" :class="cardsCountForTeam(selectedOtherTeam.id) > 0 ? 'text-yellow-500' : 'text-slate-800'">
                            {{ cardsCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIGNE 4 : Liste joueurs + Profil -->
        <div class="grid grid-cols-12 gap-4" v-if="selectedOtherTeam">

            <!-- Liste joueurs -->
            <div class="col-span-3 border border-slate-200 rounded-xl bg-slate-50 p-3 max-h-[500px] overflow-y-auto">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Effectif</h3>
                <div v-if="otherRosterWithStatus.length" class="space-y-1">
                    <button
                        v-for="p in otherRosterWithStatus" :key="p.id"
                        type="button"
                        @click="emit('select-player', p)"
                        class="w-full text-left rounded-lg px-2 py-1.5 transition-all"
                        :class="selectedOtherPlayer?.id === p.id
                            ? 'bg-teal-500 text-white shadow-sm'
                            : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-100'"
                    >
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt=""/>
                                <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold truncate">{{ p.lastname }}</div>
                                <div class="text-[10px] opacity-60 truncate">{{ p.position }}</div>
                            </div>
                            <div class="w-2 h-2 rounded-full shrink-0" :class="p.is_starter ? 'bg-emerald-400' : 'bg-slate-300'"></div>
                        </div>
                    </button>
                </div>
                <p v-else class="text-xs text-slate-400">Aucun joueur.</p>
            </div>

            <!-- Profil joueur -->
            <div class="col-span-9 flex flex-col gap-3">
                <template v-if="selectedOtherPlayer">

                    <!-- Identité + actions -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <div class="flex items-start gap-4">
                            <div class="relative shrink-0">
                                <div class="w-20 h-20 rounded-xl border-2 border-slate-200 bg-white overflow-hidden">
                                    <img v-if="playerPhotoUrl(selectedOtherPlayer)" :src="playerPhotoUrl(selectedOtherPlayer)" class="w-full h-full object-cover" alt=""/>
                                    <div v-else class="w-full h-full flex items-center justify-center text-3xl text-slate-200">👤</div>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-[11px] font-black shadow"
                                     :class="overallOf(selectedOtherPlayer)>=80?'bg-emerald-500 text-white':overallOf(selectedOtherPlayer)>=65?'bg-teal-500 text-white':overallOf(selectedOtherPlayer)>=50?'bg-amber-400 text-slate-900':'bg-slate-400 text-white'">
                                    {{ overallOf(selectedOtherPlayer) }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-bold text-slate-800">{{ selectedOtherPlayer.firstname }} {{ selectedOtherPlayer.lastname }}</h3>
                                <p class="text-xs text-slate-400 mt-0.5">{{ selectedOtherPlayer.position }} • {{ selectedOtherPlayer.cost ?? 0 }} €</p>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button v-if="selectedOtherPlayer.contract_id" type="button"
                                            @click="emit('toggle-starter', selectedOtherPlayer.contract_id)"
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                                            :class="selectedOtherPlayer.is_starter
                                            ? 'bg-emerald-500 text-white border-emerald-600 hover:bg-emerald-600'
                                            : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50'">
                                        {{ selectedOtherPlayer.is_starter ? '✓ Titulaire' : '+ Titulariser' }}
                                    </button>
                                </div>

                                <div v-if="selectedOtherPlayer.is_starter" class="mt-3">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Position sur le terrain</p>
                                    <div class="flex gap-1 flex-wrap">
                                        <button v-for="n in 11" :key="n" type="button"
                                                @click="emit('change-slot', n)"
                                                class="w-7 h-7 rounded-lg text-[11px] font-bold border transition-all"
                                                :class="getOtherSlotForPlayer(selectedOtherPlayer.id) === n
                                                ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                                                : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-600'">
                                            {{ n }}
                                        </button>
                                    </div>
                                </div>
                                <p v-if="selectedOtherPlayer.description" class="mt-2 text-xs text-slate-400 italic">{{ selectedOtherPlayer.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Radar + Barres -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-col items-center">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 self-start">Profil technique</h4>
                            <svg viewBox="0 0 180 180" class="w-40 h-40">
                                <polygon v-for="(pts,i) in radarGrids" :key="i" :points="pts" fill="none" stroke="#e2e8f0" stroke-width="0.8"/>
                                <line v-for="(ax,i) in radarAxes" :key="'a'+i" x1="90" y1="90" :x2="ax.x2" :y2="ax.y2" stroke="#e2e8f0" stroke-width="0.8"/>
                                <polygon :points="radarPolygon" fill="rgba(239,68,68,0.15)" stroke="#ef4444" stroke-width="1.5" stroke-linejoin="round"/>
                                <circle v-for="(pt,i) in radarPoints" :key="'p'+i" :cx="pt.x" :cy="pt.y" r="2.5" fill="#ef4444" stroke="white" stroke-width="1"/>
                                <text v-for="(pt,i) in radarPoints" :key="'t'+i" :x="pt.lx" :y="pt.ly" text-anchor="middle" dominant-baseline="middle" font-size="8" fill="#94a3b8" font-weight="600">{{ pt.label }}</text>
                            </svg>
                        </div>

                        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Statistiques</h4>
                            <div class="space-y-1.5">
                                <div v-for="stat in [
                                    {label:'Vitesse', key:'speed',    color:'bg-sky-400'},
                                    {label:'Stamina', key:'stamina',  color:'bg-emerald-400'},
                                    {label:'Attaque', key:'attack',   color:'bg-orange-400'},
                                    {label:'Défense', key:'defense',  color:'bg-blue-400'},
                                    {label:'Tir',     key:'shot',     color:'bg-red-400'},
                                    {label:'Passe',   key:'pass',     color:'bg-teal-400'},
                                    {label:'Dribble', key:'dribble',  color:'bg-yellow-400'},
                                    {label:'Block',   key:'block',    color:'bg-indigo-400'},
                                    {label:'Interc.', key:'intercept',color:'bg-purple-400'},
                                    {label:'Tacle',   key:'tackle',   color:'bg-pink-400'},
                                ]" :key="stat.key" class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-slate-500 shrink-0 text-[11px]">{{ stat.label }}</span>
                                    <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" :class="stat.color"
                                             :style="{width: Math.min((selectedOtherPlayer[stat.key] ?? selectedOtherPlayer.stats?.[stat.key] ?? 0), 100)+'%'}">
                                        </div>
                                    </div>
                                    <span class="w-6 text-right font-bold text-slate-700 text-[11px]">
                                        {{ selectedOtherPlayer[stat.key] ?? selectedOtherPlayer.stats?.[stat.key] ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perf chips -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Performance saison</h4>
                        <div v-if="perfChips.length" class="flex flex-wrap gap-2">
                            <div v-for="chip in perfChips" :key="chip.label"
                                 class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
                                 :class="chip.color">
                                <span>{{ chip.icon }}</span>
                                <span>{{ chip.label }}</span>
                                <span class="font-black">{{ chip.val }}</span>
                                <span v-if="chip.sub !== null" class="opacity-50 text-[10px]">/ {{ chip.sub }} ✓</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Aucune donnée de performance — uniquement disponible pour les matchs joués manuellement.</p>
                    </div>

                </template>

                <div v-else class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
                    Sélectionne un joueur dans la liste ou sur le terrain
                </div>
            </div>
        </div>

        <!-- Aucune équipe sélectionnée -->
        <div v-if="!selectedOtherTeam" class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
            Sélectionne une équipe ci-dessus
        </div>
    </div>
</template>
