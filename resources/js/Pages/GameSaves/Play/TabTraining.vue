<script setup>
import { ref, computed } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import RosterList from '@/Pages/GameSaves/Play/RosterList.vue';

const props = defineProps({
    season:                      { type: Number,   required: true },
    week:                        { type: Number,   required: true },
    roster:                      { type: Array,    required: true },
    trainingState:               { type: Object,   default: null },
    remainingTrainingsThisWeek:  { type: Number,   required: true },
    hasPlayerBeenTrainedThisWeek:{ type: Function, required: true },
    availableTrainingStats:      { type: Array,    required: true },
    selectedTrainings:           { type: Array,    required: true },
    canSubmitTraining:           { type: Boolean,  required: true },
    aiTrainingEntries:           { type: Array,    default: () => [] },
    aiCurrentDisplayWeek:        { type: Number,   default: 1 },
    aiWeekMax:                   { type: Number,   default: 1 },
    // Nouvelles props pour le profil joueur
    playerSeasonStats:           { type: Object,   default: () => ({}) },
    isPlayerInjured:             { type: Function, default: () => () => false },
    isPlayerSuspended:           { type: Function, default: () => () => false },
    playerYellowCards:           { type: Function, default: () => () => 0 },
    playerInjury:                { type: Function, default: () => () => null },
    playerSuspension:            { type: Function, default: () => () => null },
});

const emit = defineEmits(['add-slot', 'remove-slot', 'submit-training', 'prev-ai-week', 'next-ai-week']);

const { overallOf, playerPhotoUrl, statColor, sanctionTypeLabel } = usePlayerUtils();

const trainingButtonState = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return { disabled: true, label: '', reason: '' };

    if (props.remainingTrainingsThisWeek <= 0) {
        return { disabled: true, label: 'Plus de séances cette semaine', reason: 'limit_week' };
    }
    if (props.selectedTrainings.length >= 3) {
        return { disabled: true, label: 'Limite de 3 joueurs atteinte', reason: 'limit_slots' };
    }
    if (props.selectedTrainings.some(s => Number(s.player_id) === Number(p.id))) {
        return { disabled: true, label: '✓ Déjà dans la séance', reason: 'already_added' };
    }
    if (props.hasPlayerBeenTrainedThisWeek(p.id)) {
        return { disabled: true, label: 'Déjà entraîné cette semaine', reason: 'trained' };
    }
    if ((p.stamina ?? 0) < 10) {
        return { disabled: true, label: 'Trop fatigué (< 10 stamina)', reason: 'tired' };
    }
    return { disabled: false, label: '🏋 Ajouter à l\'entraînement', reason: null };
});

// ── Sélection joueur (locale) ──────────────────────────────
const selectedPlayerId = ref(null);
const selectedPlayer   = computed(() => {
    if (!props.roster.length || !selectedPlayerId.value) return null;
    return props.roster.find(p => p.id === selectedPlayerId.value) ?? null;
});
const selectPlayer = (p) => { selectedPlayerId.value = p.id; };

// ── Filtre IA ──────────────────────────────────────────────
const aiTeamFilter = ref(null);

const aiTeams = computed(() => {
    const teams = new Map();
    props.aiTrainingEntries.forEach(e => {
        if (e.team_id && e.team_name) teams.set(e.team_id, e.team_name);
    });
    return [...teams.entries()].map(([id, name]) => ({ id, name }));
});

const filteredAiEntries = computed(() =>
    aiTeamFilter.value
        ? props.aiTrainingEntries.filter(e => e.team_id === aiTeamFilter.value)
        : props.aiTrainingEntries
);

// ── Stat label local (libellés longs vs courts du composable) ──
const statLabel = (key) => ({
    shot: 'Tir', pass: 'Passe', dribble: 'Dribble', attack: 'Attaque',
    defense: 'Défense', speed: 'Vitesse', block: 'Block',
    intercept: 'Interc.', tackle: 'Tacle', stamina: 'Stamina',
    hand_save: 'Main', punch_save: 'Poings',
}[key] ?? key);

// ── Joueur correspondant à une entrée IA / manuel ──────────
const playerForEntry       = (entry) => props.roster.find(p => Number(p.id) === Number(entry.player_id)) ?? null;
const playerForManualEntry = playerForEntry;

const manualEntries = computed(() => {
    const s = props.trainingState;
    if (!s || Number(s.season) !== Number(props.season) || Number(s.week) !== Number(props.week)) return [];
    return Array.isArray(s.entries) ? s.entries : [];
});

// ── Stats autorisées par poste (5 groupes — sémantique entraînement) ──
const STATS_BY_POSITION = {
    GK:      ['hand_save', 'punch_save', 'defense', 'block', 'stamina', 'speed'],
    DEF:     ['defense', 'tackle', 'block', 'intercept', 'stamina', 'speed'],
    MDF:     ['pass', 'intercept', 'tackle', 'defense', 'attack', 'stamina'],
    MOF:     ['pass', 'dribble', 'attack', 'intercept', 'shot', 'stamina'],
    ATT:     ['shot', 'dribble', 'attack', 'pass', 'speed', 'stamina'],
    DEFAULT: ['speed', 'attack', 'defense', 'pass', 'dribble', 'shot', 'tackle', 'intercept'],
};

const positionGroup = (position) => {
    const p = (position ?? '').toUpperCase();
    if (p.includes('GK') || p.includes('GOAL'))    return 'GK';
    if (p.includes('DEF') || p.includes('BACK'))   return 'DEF';
    if (p.includes('MDF') || p.includes('DEFENSIVE MID')) return 'MDF';
    if (p.includes('MOF') || p.includes('MID'))    return 'MOF';
    if (p.includes('ATT') || p.includes('FOR') || p.includes('FORWARD')) return 'ATT';
    return 'DEFAULT';
};

const statsForSlot = (slot) => {
    if (!slot?.player_id) return props.availableTrainingStats;
    const player = props.roster.find(p => Number(p.id) === Number(slot.player_id));
    if (!player) return props.availableTrainingStats;

    // Toutes les stats sont entraînables, mais celles pertinentes au poste
    // remontent en haut de la liste
    const allowed = STATS_BY_POSITION[positionGroup(player.position)] ?? STATS_BY_POSITION.DEFAULT;
    const allowedSet = new Set(allowed);

    return [
        ...props.availableTrainingStats.filter(s => allowedSet.has(s.key)),
        ...props.availableTrainingStats.filter(s => !allowedSet.has(s.key)),
    ];
};

// ── Radar du joueur sélectionné ────────────────────────────
const RADAR_STATS = [
    { key: 'shot',    label: 'Tir'     },
    { key: 'pass',    label: 'Passe'   },
    { key: 'dribble', label: 'Dribble' },
    { key: 'defense', label: 'Défense' },
    { key: 'speed',   label: 'Vitesse' },
    { key: 'stamina', label: 'Stamina' },
];

const radarPoints = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return [];
    const cx = 90, cy = 90, r = 68;
    return RADAR_STATS.map((s, i) => {
        const val = Math.min(Number(p[s.key] ?? p.stats?.[s.key] ?? 0) / 100, 1);
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return {
            x: cx + r * val * Math.cos(angle),
            y: cy + r * val * Math.sin(angle),
            lx: cx + (r + 16) * Math.cos(angle),
            ly: cy + (r + 16) * Math.sin(angle),
            label: s.label,
        };
    });
});
const radarPolygon = computed(() => radarPoints.value.map(p => `${p.x},${p.y}`).join(' '));
const radarGrids   = computed(() => {
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

// ── Perf chips ─────────────────────────────────────────────
const selectedPlayerPerf = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return null;
    return props.playerSeasonStats?.[p.id] ?? props.playerSeasonStats?.[String(p.id)] ?? null;
});

const perfChips = computed(() => {
    const perf = selectedPlayerPerf.value;
    const p = selectedPlayer.value;
    if (!perf || !p) return [];
    const isGK = p.position?.toLowerCase().includes('goalkeeper');

    if (isGK) return [
        { icon: '🧤', label: 'Arrêts',  val: perf.defense?.hands?.attempts     ?? 0, sub: perf.defense?.hands?.success     ?? 0, color: 'bg-violet-100 text-violet-700' },
        { icon: '👊', label: 'Poings',  val: perf.defense?.gkSpecial?.attempts ?? 0, sub: perf.defense?.gkSpecial?.success ?? 0, color: 'bg-fuchsia-100 text-fuchsia-700' },
        { icon: '🧱', label: 'Blocks',  val: perf.defense?.block?.attempts     ?? 0, sub: perf.defense?.block?.success     ?? 0, color: 'bg-slate-100 text-slate-600' },
        { icon: '🎯', label: 'Passes',  val: perf.offense?.pass?.attempts      ?? 0, sub: perf.offense?.pass?.success      ?? 0, color: 'bg-sky-100 text-sky-700' },
        { icon: '⚔️', label: 'Gagnés',  val: perf.duelsWon  ?? 0, sub: null, color: 'bg-teal-100 text-teal-700' },
        { icon: '💔', label: 'Perdus',  val: perf.duelsLost ?? 0, sub: null, color: 'bg-rose-100 text-rose-700' },
    ];

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

// ── Stats du profil (barres horizontales selon poste) ──────
const profileStatBars = computed(() => {
    const p = selectedPlayer.value;
    if (!p) return [];
    return p.position?.toLowerCase().includes('goalkeeper') ? [
        { label: 'Vitesse',  key: 'speed',      color: 'bg-sky-400' },
        { label: 'Stamina',  key: 'stamina',    color: 'bg-emerald-400' },
        { label: 'Défense',  key: 'defense',    color: 'bg-blue-400' },
        { label: 'Arrêt ✋', key: 'hand_save',  color: 'bg-violet-400' },
        { label: 'Arrêt 👊', key: 'punch_save', color: 'bg-fuchsia-400' },
        { label: 'Attaque',  key: 'attack',     color: 'bg-orange-400' },
        { label: 'Block',    key: 'block',      color: 'bg-indigo-400' },
    ] : [
        { label: 'Vitesse', key: 'speed',    color: 'bg-sky-400' },
        { label: 'Stamina', key: 'stamina',  color: 'bg-emerald-400' },
        { label: 'Attaque', key: 'attack',   color: 'bg-orange-400' },
        { label: 'Défense', key: 'defense',  color: 'bg-blue-400' },
        { label: 'Tir',     key: 'shot',     color: 'bg-red-400' },
        { label: 'Passe',   key: 'pass',     color: 'bg-teal-400' },
        { label: 'Dribble', key: 'dribble',  color: 'bg-yellow-400' },
        { label: 'Block',   key: 'block',    color: 'bg-indigo-400' },
        { label: 'Interc.', key: 'intercept',color: 'bg-purple-400' },
        { label: 'Tacle',   key: 'tackle',   color: 'bg-pink-400' },
    ];
});
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- HEADER -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Entraînement</h3>
                    <p class="text-sm text-slate-600 mt-1">
                        Saison {{ season }} — Semaine {{ week }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <!-- Entraînements manuels restants -->
                    <div class="text-center px-4 py-2 rounded-xl border"
                         :class="remainingTrainingsThisWeek > 0 ? 'bg-teal-50 border-teal-200' : 'bg-slate-100 border-slate-200'">
                        <div class="text-2xl font-black"
                             :class="remainingTrainingsThisWeek > 0 ? 'text-teal-600' : 'text-slate-400'">
                            {{ remainingTrainingsThisWeek }}
                        </div>
                        <div class="text-[10px] font-semibold"
                             :class="remainingTrainingsThisWeek > 0 ? 'text-teal-500' : 'text-slate-400'">
                            séance(s) restante(s)
                        </div>
                    </div>
                    <!-- Entraînements IA cette semaine -->
                    <div class="text-center px-4 py-2 rounded-xl border bg-amber-50 border-amber-200">
                        <div class="text-2xl font-black text-amber-500">{{ aiTrainingEntries.length }}</div>
                        <div class="text-[10px] font-semibold text-amber-400">entr. auto</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">

            <!-- ============================================ -->
            <!-- COLONNE GAUCHE : Effectif         -->
            <!-- ============================================ -->
            <RosterList class="col-span-3 max-h-[500px]"
                        :players="roster"
                        :selectedId="selectedPlayer?.id"
                        :isPlayerInjured="isPlayerInjured"
                        :isPlayerSuspended="isPlayerSuspended"
                        :playerYellowCards="playerYellowCards"
                        :playerInjury="playerInjury"
                        :playerSuspension="playerSuspension"
                        :rowHighlight="p => hasPlayerBeenTrainedThisWeek(p.id)"
                        @select="selectPlayer">
                <template #badge="{ player, selected }">
                    <div v-if="hasPlayerBeenTrainedThisWeek(player.id)"
                         class="text-[9px] px-1.5 py-0.5 rounded-full font-bold shrink-0"
                         :class="selected ? 'bg-white text-teal-600' : 'bg-teal-500 text-white'"
                         title="Déjà entraîné cette semaine">
                        ✓
                    </div>
                </template>
            </RosterList>

            <!-- ============================================ -->
            <!-- COLONNE  CENTRAL & DROITE                               -->
            <!-- ============================================ -->
            <div class="col-span-6 flex flex-col gap-4">
                <!-- ====================================== -->
                <!-- PROFIL JOUEUR SÉLECTIONNÉ              -->
                <!-- ====================================== -->
                <template v-if="selectedPlayer">

                    <!-- Alerte blessure/suspension -->
                    <div v-if="isPlayerInjured(selectedPlayer.id)"
                         class="border border-rose-200 rounded-xl bg-rose-50 px-4 py-2.5 flex items-center gap-2">
                        <span class="text-lg">🤕</span>
                        <div>
                            <div class="text-xs font-bold text-rose-700">Joueur blessé</div>
                            <div class="text-[10px] text-rose-500">
                                {{ playerInjury(selectedPlayer.id)?.description ?? 'Blessure' }}
                                — Retour semaine {{ playerInjury(selectedPlayer.id)?.week_return ?? '—' }}
                            </div>
                        </div>
                    </div>
                    <div v-else-if="isPlayerSuspended(selectedPlayer.id)"
                         class="border border-amber-200 rounded-xl bg-amber-50 px-4 py-2.5 flex items-center gap-2">
                        <span class="text-lg">🚫</span>
                        <div>
                            <div class="text-xs font-bold text-amber-700">Joueur suspendu</div>
                            <div class="text-[10px] text-amber-500">
                                {{ sanctionTypeLabel(playerSuspension(selectedPlayer.id)?.type) }}
                                <template v-if="playerSuspension(selectedPlayer.id)?.weeks_suspended">
                                    — {{ playerSuspension(selectedPlayer.id).weeks_suspended }} semaine(s)
                                </template>
                                — Retour semaine {{ playerSuspension(selectedPlayer.id)?.week_return ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <!-- Identité -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <div class="flex items-start gap-4">
                            <div class="relative shrink-0">
                                <div class="w-20 h-20 rounded-xl border-2 border-slate-200 bg-white overflow-hidden">
                                    <img v-if="playerPhotoUrl(selectedPlayer)" :src="playerPhotoUrl(selectedPlayer)" class="w-full h-full object-cover" alt=""/>
                                    <div v-else class="w-full h-full flex items-center justify-center text-3xl text-slate-200">👤</div>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-[11px] font-black shadow"
                                     :class="overallOf(selectedPlayer)>=80?'bg-emerald-500 text-white':overallOf(selectedPlayer)>=65?'bg-teal-500 text-white':overallOf(selectedPlayer)>=50?'bg-amber-400 text-slate-900':'bg-slate-400 text-white'">
                                    {{ overallOf(selectedPlayer) }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-bold text-slate-800">{{ selectedPlayer.firstname }} {{ selectedPlayer.lastname }}</h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ selectedPlayer.position }} • Stamina {{ selectedPlayer.stamina ?? '—' }}
                                </p>
                                <p v-if="selectedPlayer.description" class="mt-2 text-xs text-slate-400 italic line-clamp-2">
                                    {{ selectedPlayer.description }}
                                </p>
                                <div class="mt-3">
                                    <button type="button"
                                            @click="emit('add-slot', selectedPlayer.id)"
                                            :disabled="trainingButtonState.disabled"
                                            class="px-4 py-2 rounded-full text-xs font-bold transition-all"
                                            :class="trainingButtonState.disabled
                ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
                : 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm'">
                                        {{ trainingButtonState.label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Radar + Barres stats -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-col items-center">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 self-start">Profil technique</h4>
                            <svg viewBox="0 0 180 180" class="w-40 h-40">
                                <polygon v-for="(pts,i) in radarGrids" :key="i" :points="pts" fill="none" stroke="#e2e8f0" stroke-width="0.8"/>
                                <line v-for="(ax,i) in radarAxes" :key="'a'+i" x1="90" y1="90" :x2="ax.x2" :y2="ax.y2" stroke="#e2e8f0" stroke-width="0.8"/>
                                <polygon :points="radarPolygon" fill="rgba(20,184,166,0.2)" stroke="#14b8a6" stroke-width="1.5" stroke-linejoin="round"/>
                                <circle v-for="(pt,i) in radarPoints" :key="'p'+i" :cx="pt.x" :cy="pt.y" r="2.5" fill="#14b8a6" stroke="white" stroke-width="1"/>
                                <text v-for="(pt,i) in radarPoints" :key="'t'+i" :x="pt.lx" :y="pt.ly" text-anchor="middle" dominant-baseline="middle" font-size="8" fill="#94a3b8" font-weight="600">{{ pt.label }}</text>
                            </svg>
                        </div>

                        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Statistiques</h4>
                            <div class="space-y-1.5">
                                <div v-for="stat in profileStatBars" :key="stat.key" class="flex items-center gap-2 text-xs">
                                    <span class="w-16 text-slate-500 shrink-0 text-[11px]">{{ stat.label }}</span>
                                    <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" :class="stat.color"
                                             :style="{width: Math.min((selectedPlayer[stat.key] ?? selectedPlayer.stats?.[stat.key] ?? 0), 100)+'%'}">
                                        </div>
                                    </div>
                                    <span class="w-6 text-right font-bold text-slate-700 text-[11px]">
                        {{ selectedPlayer[stat.key] ?? selectedPlayer.stats?.[stat.key] ?? '—' }}
                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perf chips -->
                    <div v-if="perfChips.length" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Performance saison</h4>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="chip in perfChips" :key="chip.label"
                                 class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
                                 :class="chip.color">
                                <span>{{ chip.icon }}</span>
                                <span>{{ chip.label }}</span>
                                <span class="font-black">{{ chip.val }}</span>
                                <span v-if="chip.sub !== null" class="opacity-50 text-[10px]">/ {{ chip.sub }} ✓</span>
                            </div>
                        </div>
                    </div>
                </template>
                <!-- État vide -->
                <div v-else
                     class="flex-1 flex items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/40 min-h-[400px] p-8">
                    <div class="text-center max-w-xs">
                        <div class="text-5xl mb-3 opacity-30">👤</div>
                        <h4 class="text-sm font-bold text-slate-500 mb-2">Aucun joueur sélectionné</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Clique sur un joueur dans la liste à gauche pour consulter son profil
                            et décider s'il a besoin d'un entraînement.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-span-3 flex flex-col gap-4">
                <!-- Formulaire entraînement manuel -->
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                        🏋️ Entraînement manuel
                    </h4>
                    <p class="text-xs text-slate-400 mb-3">
                        Choisis jusqu'à 3 joueurs différents et la stat à améliorer (+1 à +5, coûte 5 stamina).
                    </p>

                    <div class="space-y-2 mb-3">
                        <div v-if="selectedTrainings.length" class="space-y-2 mb-3">
                            <div v-for="(slot, index) in selectedTrainings" :key="index"
                                 class="flex flex-col gap-2 p-2.5 rounded-lg bg-white border border-slate-200">

                                <!-- Carte joueur compacte -->
                                <div v-if="playerForEntry({ player_id: slot.player_id })" class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                        <img v-if="playerPhotoUrl(playerForEntry({ player_id: slot.player_id }))"
                                             :src="playerPhotoUrl(playerForEntry({ player_id: slot.player_id }))"
                                             class="w-full h-full object-cover" alt=""/>
                                        <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-semibold truncate text-slate-700">
                                            {{ playerForEntry({ player_id: slot.player_id })?.lastname ?? '—' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 truncate">
                                            {{ playerForEntry({ player_id: slot.player_id })?.position ?? '' }}
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="emit('remove-slot', index)"
                                            class="w-6 h-6 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-200 flex items-center justify-center text-xs font-bold shrink-0">
                                        ✕
                                    </button>
                                </div>

                                <!-- Select stat -->
                                <select v-model="slot.stat"
                                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-xs bg-white focus:ring-2 focus:ring-teal-300 focus:outline-none">
                                    <option v-for="s in statsForSlot(slot)" :key="s.key" :value="s.key">
                                        {{ s.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- État vide -->
                        <div v-else class="mb-3 p-4 rounded-lg bg-slate-100 border border-dashed border-slate-300 text-center">
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                Sélectionne un joueur dans l'effectif puis appuie sur
                                <span class="font-bold text-teal-600">🏋 Ajouter à l'entraînement</span>
                                depuis sa carte.
                            </p>
                        </div>

                        <!-- Bouton Lancer -->
                        <button type="button"
                                class="w-full px-4 py-2 text-sm rounded-full font-semibold transition-all disabled:opacity-40"
                                :class="canSubmitTraining
            ? 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm'
            : 'bg-slate-200 text-slate-400'"
                                @click="emit('submit-training')"
                                :disabled="!canSubmitTraining">
                            Lancer l'entraînement
                        </button>
                    </div>

                    <p v-if="remainingTrainingsThisWeek <= 0"
                       class="mt-3 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Tu as utilisé tous tes entraînements manuels pour cette semaine.
                    </p>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 flex flex-col gap-4">
                <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center justify-between">
                        <span>🤖 Entraînement automatique</span>
                        <div class="flex items-center gap-2">
                            <button @click="emit('prev-ai-week')" :disabled="aiCurrentDisplayWeek <= 1"
                                    class="w-6 h-6 rounded-full bg-slate-200 hover:bg-slate-300 disabled:opacity-30 text-xs font-bold">←</button>
                            <span class="text-xs text-slate-500">Semaine {{ aiCurrentDisplayWeek }}</span>
                            <button @click="emit('next-ai-week')" :disabled="aiCurrentDisplayWeek >= aiWeekMax"
                                    class="w-6 h-6 rounded-full bg-slate-200 hover:bg-slate-300 disabled:opacity-30 text-xs font-bold">→</button>
                        </div>
                    </h4>

                    <!-- Filtre équipe -->
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <button type="button"
                                @click="aiTeamFilter = null"
                                class="px-2.5 py-1 rounded-full text-[10px] font-semibold border transition-all"
                                :class="aiTeamFilter === null ? 'bg-amber-400 text-white border-amber-500' : 'bg-white text-slate-500 border-slate-200 hover:border-amber-300'">
                            Toutes
                        </button>
                        <button v-for="t in aiTeams" :key="t.id" type="button"
                                @click="aiTeamFilter = aiTeamFilter === t.id ? null : t.id"
                                class="px-2.5 py-1 rounded-full text-[10px] font-semibold border transition-all"
                                :class="aiTeamFilter === t.id ? 'bg-amber-400 text-white border-amber-500' : 'bg-white text-slate-500 border-slate-200 hover:border-amber-300'">
                            {{ t.name }}
                        </button>
                    </div>

                    <div v-if="filteredAiEntries.length" class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                        <div v-for="entry in filteredAiEntries" :key="entry.player_id + entry.stat + entry.team_id"
                             class="flex items-center gap-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-100">

                            <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <div class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">
                                    {{ entry.player_name?.charAt(0) ?? '?' }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-700 truncate">{{ entry.player_name }}</div>
                                <div class="text-[10px] text-amber-600 font-semibold truncate">{{ entry.team_name }}</div>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full" :class="statColor(entry.stat)"></div>
                                <span class="text-xs text-slate-600 font-medium">{{ statLabel(entry.stat) }}</span>
                            </div>

                            <div class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-200 text-amber-700">
                                +{{ entry.gain }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-xs text-slate-400 italic py-2">
                        L'entraînement automatique s'effectue après chaque match joué ou simulé.
                    </div>
                </div>

                <!-- Historique entraînements manuels -->
                <div v-if="manualEntries.length" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                        📋 Historique manuel — semaine {{ week }}
                    </h4>
                    <div class="space-y-2">
                        <div v-for="entry in manualEntries" :key="entry.player_id + entry.stat"
                             class="flex items-center gap-3 px-3 py-2 rounded-lg bg-teal-50 border border-teal-100">

                            <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <img v-if="playerForManualEntry(entry) && playerPhotoUrl(playerForManualEntry(entry))"
                                     :src="playerPhotoUrl(playerForManualEntry(entry))" class="w-full h-full object-cover" alt=""/>
                                <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-700 truncate">
                                    {{ playerForManualEntry(entry)?.lastname ?? `Joueur #${entry.player_id}` }}
                                </div>
                                <div class="text-[10px] text-slate-400">Stamina -{{ entry.stamina_cost }}</div>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full" :class="statColor(entry.stat)"></div>
                                <span class="text-xs text-slate-600 font-medium">{{ statLabel(entry.stat) }}</span>
                            </div>

                            <div class="px-2 py-0.5 rounded-full text-xs font-black bg-teal-200 text-teal-700">
                                +{{ entry.gain }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
