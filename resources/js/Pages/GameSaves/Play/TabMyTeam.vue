<script setup>
import { computed } from 'vue';
import { FORMATIONS, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';
import { usePlayerUtils } from './usePlayerUtils.js';
import TeamStyleBadges from "@/Pages/GameSaves/Play/TeamStyleBadges.vue";
import RosterList from "@/Pages/GameSaves/Play/RosterList.vue";

const props = defineProps({
    rosterWithStatus:     { type: Array,   required: true },
    isPlayerInjured:      { type: Function, default: () => () => false },
    isPlayerSuspended:    { type: Function, default: () => () => false },
    playerYellowCards:    { type: Function, default: () => () => 0 },
    playerInjury:         { type: Function, default: () => () => null },
    playerSuspension:     { type: Function, default: () => () => null },
    selectedMyPlayer:     { type: Object,  default: null },
    currentFormation:     { type: String,  required: true },
    formationData:        { type: Object,  default: null },
    miniPitchMarkerStyle: { type: Object,  required: true },
    selectedMyPlayerPerf: { type: Object,  default: null },
    lineupForm:           { type: Array,   required: true },
    isPickedUp: { type: Function, default: () => false },
    // Fonctions terrain (depuis useTeam)
    playerPosition:       { type: Function, required: true },
    playerForSlot:        { type: Function, required: true },
    selectedSlot:         { type: Number,   default: null },
    // Infos club
    team:                 { type: Object,  default: null },
    teamRecord:           { type: Object,  default: () => ({ wins: 0, draws: 0, losses: 0 }) },
    teamBudget:           { type: Number,  default: 0 },
    clubStanding:         { type: Object,  default: null },
    standings:            { type: Array,   default: () => [] },
    injuriesCount:        { type: Number,  default: 0 },
    suspensionsCount:     { type: Number,  default: 0 },
    cardsCount:           { type: Number,  default: 0 },
    averageAttack:        { type: Number,  default: 0 },
    averageDefense:       { type: Number,  default: 0 },
    averageStamina:       { type: Number,  default: 0 },
    moraleLogs:           { type: Object,  default: () => ({}) },
    playerPromises:       { type: Object,  default: () => ({}) },
    playerDeclarations:   { type: Object,  default: () => ({}) },
    currentWeek:          { type: Number,  default: 1 },
});

const emit = defineEmits([
    'select-player', 'toggle-starter', 'toggle-captain', 'save-formation', 'update-number',
    'player-click', 'drag-start', 'drag-over', 'drop-on', 'make-promise', 'make-declaration',
]);

const substitutes = computed(() => props.rosterWithStatus.filter(p => !p.is_starter));

const { overallOf, playerPhotoUrl, teamLogoUrl, sanctionTypeLabel, moraleState, moraleSourceLabel, moraleMatchEffect, HEROIC_MORALE_THRESHOLD } = usePlayerUtils();

// ── Moral ────────────────────────────────────────────────────
const selectedPlayerMorale = computed(() => moraleState(props.selectedMyPlayer?.morale));

const selectedPlayerMoraleLogs = computed(() =>
    props.moraleLogs?.[props.selectedMyPlayer?.id] ?? []
);

const selectedPlayerMoraleEffect = computed(() => moraleMatchEffect(props.selectedMyPlayer?.morale));

const selectedPlayerIsHeroic = computed(() =>
    Number(props.selectedMyPlayer?.morale ?? 60) > HEROIC_MORALE_THRESHOLD
);

// ── Relation coach + promesses ───────────────────────────────
const selectedPlayerAffinity = computed(() => Number(props.selectedMyPlayer?.coach_affinity ?? 0));

const affinityDisplay = computed(() => {
    const a = selectedPlayerAffinity.value;
    if (a <= -50) return { label: 'Rupture',    text: 'text-rose-600',    bar: 'bg-rose-500' };
    if (a <= -30) return { label: 'Tendue',     text: 'text-orange-500',  bar: 'bg-orange-400' };
    if (a <   30) return { label: 'Neutre',     text: 'text-slate-500',   bar: 'bg-slate-400' };
    if (a <   50) return { label: 'Bonne',      text: 'text-teal-600',    bar: 'bg-teal-400' };
    return         { label: 'Excellente', text: 'text-emerald-600', bar: 'bg-emerald-500' };
});

const selectedPlayerPromises = computed(() =>
    props.playerPromises?.[props.selectedMyPlayer?.id] ?? []
);

const activePromise = computed(() =>
    selectedPlayerPromises.value.find(p => p.status === 'pending') ?? null
);

const lastResolvedPromise = computed(() =>
    selectedPlayerPromises.value.find(p => p.status !== 'pending') ?? null
);

const PROMISE_TYPES = [
    { type: 'playing_time', icon: '🤝', label: 'Temps de jeu',  hint: 'Il jouera 3 matchs dans les 5 prochaines semaines' },
    { type: 'starter',      icon: '⭐', label: 'Titularisation', hint: 'Il jouera 4 matchs dans les 5 prochaines semaines' },
    { type: 'renewal',      icon: '📜', label: 'Prolongation',   hint: 'Tu lui signeras un nouveau contrat sous 4 semaines' },
];

const promiseTypeLabel = (type) => PROMISE_TYPES.find(t => t.type === type)?.label ?? type;

// ── Déclarations publiques ───────────────────────────────────
const DECLARATION_COOLDOWN_WEEKS = 3;

const lastDeclaration = computed(() =>
    (props.playerDeclarations?.[props.selectedMyPlayer?.id] ?? [])[0] ?? null
);

const declarationOnCooldown = computed(() =>
    lastDeclaration.value
    && lastDeclaration.value.week > (props.currentWeek - DECLARATION_COOLDOWN_WEEKS)
);

const lastDeclarationSummary = computed(() => {
    const d = lastDeclaration.value;
    if (!d) return null;
    if (d.type === 'praise') return { text: '📣 Félicité', positive: true };
    if (d.outcome === 'proud_reaction') return { text: '📢 Critiqué — réaction d\'orgueil', positive: true };
    return { text: '📢 Critiqué — mal vécu', positive: false };
});

const averageMorale = computed(() => {
    if (!props.rosterWithStatus.length) return 0;
    const sum = props.rosterWithStatus.reduce((a, p) => a + Number(p.morale ?? 60), 0);
    return Math.round(sum / props.rosterWithStatus.length);
});

const getSlotForPlayer = (playerId) => {
    if (!playerId || !Array.isArray(props.lineupForm)) return null;
    const row = props.lineupForm.find(r => r.player_id === playerId);
    return row ? row.slot : null;
};

// ── Maîtrise du poste occupé (miroir de RosterService.positionMastery) ──
const ROLE_BY_ZONE = { 0: 'GK', 1: 'DF', 2: 'MF', 3: 'MF', 4: 'FW' };

const roleFromPosition = (pos) => {
    const p = String(pos || '').toLowerCase();
    if (p.includes('goal')) return 'GK';
    if (p.includes('def'))  return 'DF';
    if (p.includes('mid'))  return 'MF';
    if (p.includes('for') || p.includes('att')) return 'FW';
    return null;
};

// 'primary' | 'secondary' | 'off' | null
const slotMastery = (slot, slotDef) => {
    const player = props.playerForSlot(slot);
    if (!player) return null;
    const slotRole = ROLE_BY_ZONE[slotDef.zone] ?? null;
    const natural  = roleFromPosition(player.position);
    if (!slotRole || !natural) return null;
    if (slotRole === natural) return 'primary';
    const secondary = (player.secondary_positions ?? []).map(roleFromPosition);
    return secondary.includes(slotRole) ? 'secondary' : 'off';
};

// ── Terrain unifié : playerPosition / playerForSlot / selectedSlot viennent de useTeam via props ──

// ── Radar chart ─────────────────────────────────────────────
const RADAR_STATS = [
    { key: 'shot',    label: 'Tir'     },
    { key: 'pass',    label: 'Passe'   },
    { key: 'dribble', label: 'Dribble' },
    { key: 'defense', label: 'Défense' },
    { key: 'speed',   label: 'Vitesse' },
    { key: 'stamina', label: 'Stamina' },
];

const radarPoints = computed(() => {
    const p = props.selectedMyPlayer;
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
    const perf = props.selectedMyPlayerPerf;
    if (!perf) return [];
    const isGK = props.selectedMyPlayer?.position?.toLowerCase().includes('goalkeeper');

    if (isGK) return [
        { icon: '🧤', label: 'Arrêts',  val: perf.defense.hands.attempts,     sub: perf.defense.hands.success,     color: 'bg-violet-100 text-violet-700' },
        { icon: '👊', label: 'Poings',     val: perf.defense.gkSpecial?.attempts ?? 0, sub: perf.defense.gkSpecial?.success ?? 0, color: 'bg-fuchsia-100 text-fuchsia-700' },
        { icon: '🧱', label: 'Blocks',     val: perf.defense.block.attempts,     sub: perf.defense.block.success,     color: 'bg-slate-100 text-slate-600' },
        { icon: '🎯', label: 'Passes',     val: perf.offense.pass.attempts,      sub: perf.offense.pass.success,      color: 'bg-sky-100 text-sky-700' },
        { icon: '⚔️', label: 'Gagnés',     val: perf.duelsWon,                   sub: null,                           color: 'bg-teal-100 text-teal-700' },
        { icon: '💔', label: 'Perdus',     val: perf.duelsLost,                  sub: null,                           color: 'bg-rose-100 text-rose-700' },
    ];

    return [
        { icon: '⚽', label: 'Tirs',     val: perf.offense.shot.attempts,      sub: perf.offense.shot.success,      color: 'bg-blue-100 text-blue-700' },
        { icon: '🎯', label: 'Passes',   val: perf.offense.pass.attempts,      sub: perf.offense.pass.success,      color: 'bg-sky-100 text-sky-700' },
        { icon: '🔥', label: 'Dribbles', val: perf.offense.dribble.attempts,   sub: perf.offense.dribble.success,   color: 'bg-orange-100 text-orange-700' },
        { icon: '🛡️', label: 'Interc.',  val: perf.defense.intercept.attempts, sub: perf.defense.intercept.success, color: 'bg-emerald-100 text-emerald-700' },
        { icon: '⚡', label: 'Tacles',   val: perf.defense.tackle.attempts,    sub: perf.defense.tackle.success,    color: 'bg-yellow-100 text-yellow-700' },
        { icon: '🧱', label: 'Blocks',   val: perf.defense.block.attempts,     sub: perf.defense.block.success,     color: 'bg-slate-100 text-slate-600' },
        { icon: '🧤', label: 'Arrêts',   val: perf.defense.hands.attempts,     sub: perf.defense.hands.success,     color: 'bg-violet-100 text-violet-700' },
        { icon: '⚔️', label: 'Gagnés',   val: perf.duelsWon,                   sub: null,                           color: 'bg-teal-100 text-teal-700' },
        { icon: '💔', label: 'Perdus',   val: perf.duelsLost,                  sub: null,                           color: 'bg-rose-100 text-rose-700' },
    ];
});
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- LIGNE 1 : Formation + Terrain + Banc -->
        <div class="grid grid-cols-12 gap-4">

            <!-- Select formation (inchangé, col-span-4) -->
            <div class="col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col gap-3">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Formation</h3>

                <select
                    :value="currentFormation"
                    @change="emit('save-formation', $event.target.value)"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold bg-white text-slate-800 focus:ring-2 focus:ring-teal-300 focus:outline-none cursor-pointer"
                >
                    <option v-for="f in FORMATION_LIST" :key="f.key" :value="f.key">{{ f.label }}</option>
                </select>

                <p class="text-xs text-slate-500 leading-relaxed min-h-[2.5rem]">
                    {{ FORMATIONS[currentFormation]?.description ?? '—' }}
                </p>

                <div class="border-t border-slate-200 pt-3">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Répartition</h4>
                    <div class="flex gap-1.5 flex-wrap">
                        <template v-if="formationData">
                    <span v-for="(label, zone) in ['GK','DEF','MDF','MOF','ATT']" :key="zone"
                          class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                          :class="[zone==0?'bg-yellow-100 text-yellow-700':zone==1?'bg-blue-100 text-blue-700':zone==2?'bg-green-100 text-green-700':zone==3?'bg-orange-100 text-orange-700':'bg-red-100 text-red-700']">
                        {{ label }} ×{{ Object.values(formationData.slots).filter(s => s.zone === zone).length }}
                    </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Terrain -->
            <div class="col-span-6 border border-slate-200 rounded-xl overflow-hidden shadow-sm" style="height:240px;">
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

                    <template v-if="formationData">
                        <div
                            v-for="(slotDef, slot) in formationData.slots"
                            :key="slot"
                            class="absolute -translate-x-1/2 -translate-y-1/2"
                            :class="playerForSlot(slot) ? 'cursor-grab active:cursor-grabbing' : 'cursor-default'"
                            :style="{ left: playerPosition(slot).x+'%', top: playerPosition(slot).y+'%' }"
                            :draggable="!!playerForSlot(slot)"
                            @click.stop="playerForSlot(slot) && emit('player-click', playerForSlot(slot))"
                            @dragstart.stop="playerForSlot(slot) && emit('drag-start', playerForSlot(slot), $event)"
                            @dragover="emit('drag-over', $event)"
                            @drop.stop="playerForSlot(slot) && emit('drop-on', playerForSlot(slot), $event)"
                        >
                            <div class="relative flex flex-col items-center transition-transform duration-150"
                                 :class="[
                            isPickedUp(playerForSlot(slot)) ? 'scale-125'
                                : selectedSlot === Number(slot) ? 'scale-110'
                                : 'hover:scale-110'
                         ]">
                                <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center overflow-hidden shadow-md transition-all"
                                     :class="[
                                isPickedUp(playerForSlot(slot))
                                    ? 'border-amber-300 ring-4 ring-amber-300/60 animate-pulse'
                                    : selectedSlot === Number(slot)
                                        ? 'border-yellow-300 ring-2 ring-yellow-200'
                                        : 'border-white/70',
                                slotDef.zone===0?'bg-yellow-500':slotDef.zone===1?'bg-blue-500':slotDef.zone===2?'bg-green-500':slotDef.zone===3?'bg-orange-500':'bg-red-500'
                            ]">
                                    <img v-if="playerForSlot(slot) && playerPhotoUrl(playerForSlot(slot))"
                                         :src="playerPhotoUrl(playerForSlot(slot))" class="w-full h-full object-cover pointer-events-none" alt=""/>
                                    <span v-else class="text-[9px] font-bold text-white">{{ slot }}</span>
                                </div>
                                <div v-if="slotMastery(slot, slotDef) === 'off'"
                                     class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-red-500 border border-white text-[8px] leading-[12px] text-white text-center font-bold pointer-events-none"
                                     title="Hors poste : malus appliqué en match">!</div>
                                <div v-else-if="slotMastery(slot, slotDef) === 'secondary'"
                                     class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-amber-400 border border-white text-[8px] leading-[12px] text-slate-900 text-center font-bold pointer-events-none"
                                     title="Poste secondaire : bonus réduit">2</div>
                                <div class="mt-0.5 px-1 rounded text-[7px] font-semibold leading-tight text-center max-w-[48px] truncate pointer-events-none"
                                     :class="isPickedUp(playerForSlot(slot))
                                ? 'bg-amber-300 text-slate-900'
                                : selectedSlot === Number(slot) ? 'bg-yellow-300 text-slate-900' : 'bg-black/50 text-white'">
                                    {{ playerForSlot(slot)?.lastname ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div v-if="rosterWithStatus.some(p => isPickedUp(p))"
                             class="absolute top-1 left-1/2 -translate-x-1/2 bg-amber-400/95 text-slate-900 text-[10px] font-bold px-3 py-1 rounded-full shadow-md pointer-events-none">
                            ⚡ Tap un autre joueur pour échanger / titulariser
                        </div>
                    </template>
                </div>
            </div>

            <!-- Banc -->
            <div class="col-span-2 border border-slate-200 rounded-xl bg-slate-100 overflow-hidden shadow-sm flex flex-col" style="height:240px;">
                <div class="px-3 py-1.5 bg-slate-200/60 border-b border-slate-200 flex items-center justify-between shrink-0">
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Banc</h4>
                    <span class="text-[10px] font-bold text-slate-400">{{ substitutes.length }}</span>
                </div>

                <div class="flex-1 overflow-y-auto p-2">
                    <div v-if="substitutes.length" class="grid grid-cols-3 gap-2">
                        <div
                            v-for="p in substitutes" :key="p.id"
                            class="cursor-grab active:cursor-grabbing flex justify-center"
                            :draggable="true"
                            @click.stop="emit('player-click', p)"
                            @dragstart.stop="emit('drag-start', p, $event)"
                            @dragover="emit('drag-over', $event)"
                            @drop.stop="emit('drop-on', p, $event)"
                        >
                            <div class="flex flex-col items-center transition-transform duration-150"
                                 :class="isPickedUp(p) ? 'scale-110' : 'hover:scale-105'">
                                <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center overflow-hidden shadow-md transition-all bg-slate-400"
                                     :class="[
                                isPickedUp(p)
                                    ? 'border-amber-300 ring-4 ring-amber-300/60 animate-pulse'
                                    : selectedMyPlayer?.id === p.id
                                        ? 'border-teal-400 ring-2 ring-teal-300'
                                        : 'border-white/70'
                             ]">
                                    <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover pointer-events-none" alt=""/>
                                    <span v-else class="text-[9px] font-bold text-white">?</span>
                                </div>
                                <div class="mt-0.5 px-1 rounded text-[7px] font-semibold leading-tight text-center max-w-[48px] truncate pointer-events-none"
                                     :class="isPickedUp(p)
                                ? 'bg-amber-300 text-slate-900'
                                : selectedMyPlayer?.id === p.id
                                    ? 'bg-teal-300 text-slate-900'
                                    : 'bg-slate-300 text-slate-700'">
                                    {{ p.lastname }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-[10px] text-slate-400 text-center py-4 italic">
                        Aucun remplaçant
                    </p>
                </div>
            </div>
        </div>

        <!-- LIGNE 2 : Infos club -->
        <div class="grid grid-cols-12 gap-4">

            <!-- Logo + nom + description -->
            <div class="col-span-5 border border-slate-200 rounded-xl bg-slate-50 p-4 flex items-center gap-4">
                <!-- Logo -->
                <div class="w-20 h-20 rounded-xl overflow-hidden bg-white border border-slate-200 shrink-0 flex items-center justify-center">
                    <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt="Logo équipe"/>
                    <span v-else class="text-3xl">🏟️</span>
                </div>
                <!-- Texte -->
                <div class="min-w-0 flex flex-col gap-1">
                    <h3 class="text-base font-bold text-slate-800 truncate">{{ team?.name ?? '—' }}</h3>
                    <p v-if="clubStanding" class="text-xs text-slate-500">
                        {{ clubStanding.position }}<sup>e</sup> / {{ standings.length }} &nbsp;•&nbsp; {{ teamBudget }} €
                    </p>
                    <p v-if="team?.description" class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                        {{ team.description }}
                    </p>
                    <TeamStyleBadges :team="team" size="sm" />
                </div>
            </div>

            <!-- Bilan -->
            <div class="col-span-3 border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col justify-center gap-1.5">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Bilan</h4>
                <div class="flex gap-2">
                    <div class="flex-1 text-center bg-emerald-50 rounded-lg py-2">
                        <div class="text-lg font-black text-emerald-600">{{ teamRecord.wins }}</div>
                        <div class="text-[10px] text-emerald-500 font-semibold">Victoires</div>
                    </div>
                    <div class="flex-1 text-center bg-slate-100 rounded-lg py-2">
                        <div class="text-lg font-black text-slate-500">{{ teamRecord.draws }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Nuls</div>
                    </div>
                    <div class="flex-1 text-center bg-rose-50 rounded-lg py-2">
                        <div class="text-lg font-black text-rose-500">{{ teamRecord.losses }}</div>
                        <div class="text-[10px] text-rose-400 font-semibold">Défaites</div>
                    </div>
                </div>
            </div>

            <!-- Stats + Effectif -->
            <div class="col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Club</h4>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                    <div class="flex justify-between"><span>Attaque moy.</span><span class="font-bold text-slate-800">{{ averageAttack }}</span></div>
                    <div class="flex justify-between"><span>Blessés</span>
                        <span class="font-bold" :class="injuriesCount > 0 ? 'text-rose-500' : 'text-slate-800'">{{ injuriesCount }}</span>
                    </div>
                    <div class="flex justify-between"><span>Défense moy.</span><span class="font-bold text-slate-800">{{ averageDefense }}</span></div>
                    <div class="flex justify-between"><span>Suspensions</span>
                        <span class="font-bold" :class="suspensionsCount > 0 ? 'text-amber-500' : 'text-slate-800'">{{ suspensionsCount }}</span>
                    </div>
                    <div class="flex justify-between"><span>Stamina moy.</span><span class="font-bold text-slate-800">{{ averageStamina }}</span></div>
                    <div class="flex justify-between"><span>Moral moy.</span>
                        <span class="font-bold" :class="moraleState(averageMorale).text">{{ moraleState(averageMorale).emoji }} {{ averageMorale }}</span>
                    </div>
                    <div class="flex justify-between"><span>Cartons</span>
                        <span class="font-bold" :class="cardsCount > 0 ? 'text-yellow-500' : 'text-slate-800'">{{ cardsCount }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIGNE 3 : Liste joueurs + Profil -->
        <div class="grid grid-cols-12 gap-4">

            <!-- Liste joueurs -->
            <RosterList class="col-span-3 max-h-[630px]"
                        :players="rosterWithStatus"
                        :selectedId="selectedMyPlayer?.id"
                        :isPlayerInjured="isPlayerInjured"
                        :isPlayerSuspended="isPlayerSuspended"
                        :playerYellowCards="playerYellowCards"
                        :playerInjury="playerInjury"
                        :playerSuspension="playerSuspension"
                        @select="p => emit('select-player', p)" />

            <!-- Profil joueur -->
            <div class="col-span-9 flex flex-col gap-3">
                <template v-if="selectedMyPlayer">

                    <!-- Alerte blessure/suspension -->
                    <div v-if="isPlayerInjured(selectedMyPlayer.id)" class="border border-rose-200 rounded-xl bg-rose-50 px-4 py-2.5 flex items-center gap-2">
                        <span class="text-lg">🤕</span>
                        <div>
                            <div class="text-xs font-bold text-rose-700">Joueur blessé</div>
                            <div class="text-[10px] text-rose-500">
                                {{ playerInjury(selectedMyPlayer.id)?.description ?? 'Blessure' }}
                                — Retour semaine {{ playerInjury(selectedMyPlayer.id)?.week_return }}
                            </div>
                        </div>
                    </div>
                    <div v-else-if="isPlayerSuspended(selectedMyPlayer.id)"
                         class="border border-amber-200 rounded-xl bg-amber-50 px-4 py-2.5 flex items-center gap-2">
                        <span class="text-lg">🚫</span>
                        <div>
                            <div class="text-xs font-bold text-amber-700">Joueur suspendu</div>
                            <div class="text-[10px] text-amber-500">
                                {{ sanctionTypeLabel(playerSuspension(selectedMyPlayer.id)?.type) }}
                                <template v-if="playerSuspension(selectedMyPlayer.id)?.weeks_suspended">
                                    — {{ playerSuspension(selectedMyPlayer.id).weeks_suspended }} semaine(s)
                                </template>
                                — Retour semaine {{ playerSuspension(selectedMyPlayer.id)?.week_return ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <!-- Identité + actions -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <div class="flex items-start gap-4">
                            <!-- Photo -->
                            <div class="relative shrink-0">
                                <div class="w-20 h-20 rounded-xl border-2 border-slate-200 bg-white overflow-hidden">
                                    <img v-if="playerPhotoUrl(selectedMyPlayer)" :src="playerPhotoUrl(selectedMyPlayer)" class="w-full h-full object-cover" alt=""/>
                                    <div v-else class="w-full h-full flex items-center justify-center text-3xl text-slate-200">👤</div>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-[11px] font-black shadow"
                                     :class="overallOf(selectedMyPlayer)>=80?'bg-emerald-500 text-white':overallOf(selectedMyPlayer)>=65?'bg-teal-500 text-white':overallOf(selectedMyPlayer)>=50?'bg-amber-400 text-slate-900':'bg-slate-400 text-white'">
                                    {{ overallOf(selectedMyPlayer) }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-bold text-slate-800">{{ selectedMyPlayer.firstname }} {{ selectedMyPlayer.lastname }}</h3>
                                <div v-if="selectedMyPlayer.is_captain" class="flex items-center gap-1.5 mt-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wide">
                                            👑 Capitaine
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                        {{ selectedMyPlayer.captain_rerolls_remaining ?? 3 }} relances/match
                                    </span>
                                </div>

                                <p class="text-xs text-slate-400 mt-0.5">{{ selectedMyPlayer.position }} • {{ selectedMyPlayer.cost ?? 0 }} €</p>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <!-- Toggle titulaire -->
                                    <button v-if="selectedMyPlayer.contract_id" type="button"
                                            @click="emit('toggle-starter', selectedMyPlayer.contract_id)"
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                                            :class="selectedMyPlayer.is_starter
                                            ? 'bg-emerald-500 text-white border-emerald-600 hover:bg-emerald-600'
                                            : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50'">
                                        {{ selectedMyPlayer.is_starter ? '✓ Titulaire' : '+ Titulariser' }}
                                    </button>
                                    <button
                                        v-if="selectedMyPlayer.contract_id"
                                        type="button"
                                        @click="emit('toggle-captain', selectedMyPlayer.contract_id)"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                                        :class="selectedMyPlayer.is_captain
        ? 'bg-amber-400 text-white border-amber-500 hover:bg-amber-500'
        : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50'"
                                        :title="selectedMyPlayer.is_captain ? 'Retirer le brassard' : 'Nommer capitaine'"
                                    >
                                        {{ selectedMyPlayer.is_captain ? '👑 Capitaine' : '👑 Nommer capitaine' }}
                                    </button>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs text-slate-500 font-semibold">N°</span>
                                        <input
                                            type="number"
                                            min="1"
                                            max="99"
                                            :value="selectedMyPlayer.number"
                                            @change="emit('update-number', selectedMyPlayer.id, $event.target.value)"
                                            class="w-14 border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-teal-300 focus:outline-none"
                                        />
                                    </div>
                                </div>

                                <!-- Sélecteur de slot (grille de boutons numérotés) -->
                                <p v-if="selectedMyPlayer.is_starter" class="mt-3 text-[10px] text-slate-400 italic">
                                    💡 Glisse ce joueur sur un titulaire pour échanger les positions, ou sur un remplaçant pour le sortir.
                                </p>
                                <p v-else class="mt-3 text-[10px] text-emerald-600 italic">
                                    💡 Glisse ce remplaçant sur un titulaire pour le faire entrer en jeu.
                                </p>

                                <p v-if="selectedMyPlayer.description" class="mt-2 text-xs text-slate-400 italic">{{ selectedMyPlayer.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Moral -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Moral</h4>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                  :class="selectedPlayerMorale.chip">
                                {{ selectedPlayerMorale.emoji }} {{ selectedPlayerMorale.label }}
                                <span class="opacity-60">{{ selectedMyPlayer.morale ?? 60 }}/100</span>
                            </span>
                        </div>

                        <div class="h-2 bg-slate-200 rounded-full overflow-hidden mb-2">
                            <div class="h-full rounded-full transition-all"
                                 :class="selectedPlayerMorale.bar"
                                 :style="{ width: Math.min(selectedMyPlayer.morale ?? 60, 100) + '%' }"></div>
                        </div>

                        <!-- Effets en match -->
                        <div class="flex flex-wrap items-center gap-1.5 mb-3">
                            <span v-if="selectedPlayerMoraleEffect"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                  :class="selectedPlayerMoraleEffect.positive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                  title="Appliqué à toutes les actions en match (duels, tirs, arrêts)">
                                ⚔️ Effet en match : {{ selectedPlayerMoraleEffect.pct }}
                            </span>
                            <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500">
                                ⚔️ Aucun effet en match
                            </span>
                            <span v-if="selectedPlayerIsHeroic"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700"
                                  title="5 % de chance de relancer gratuitement un duel perdu (une fois par match)">
                                🔥 Dépassement de soi
                            </span>
                        </div>

                        <!-- Relation coach -->
                        <div class="border-t border-slate-200 pt-3 mb-3">
                            <div class="flex items-center justify-between mb-1.5">
                                <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Relation coach</h5>
                                <span class="text-[11px] font-bold" :class="affinityDisplay.text">
                                    {{ affinityDisplay.label }}
                                    <span class="opacity-60">{{ selectedPlayerAffinity > 0 ? '+' : '' }}{{ selectedPlayerAffinity }}</span>
                                </span>
                            </div>
                            <div class="relative h-1.5 bg-slate-200 rounded-full overflow-hidden mb-2">
                                <div class="absolute top-0 bottom-0 w-px bg-slate-400/60 left-1/2"></div>
                                <div class="h-full rounded-full transition-all"
                                     :class="affinityDisplay.bar"
                                     :style="{ width: ((selectedPlayerAffinity + 100) / 2) + '%' }"></div>
                            </div>

                            <!-- Promesses -->
                            <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                <span v-if="activePromise"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700"
                                      :title="activePromise.type === 'renewal'
                                          ? `Signe-lui un nouveau contrat avant la semaine ${activePromise.due_week}`
                                          : `Il doit jouer ${activePromise.target_matches} matchs entre la semaine ${activePromise.start_week} et la semaine ${activePromise.due_week}`">
                                    🤝 Promesse en cours — {{ promiseTypeLabel(activePromise.type) }}
                                    <template v-if="activePromise.type !== 'renewal'">: {{ activePromise.target_matches }} matchs</template>
                                    avant S{{ activePromise.due_week }}
                                </span>
                                <template v-else>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Promettre :</span>
                                    <button v-for="pt in PROMISE_TYPES" :key="pt.type" type="button"
                                            @click="emit('make-promise', selectedMyPlayer, pt.type)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold border border-sky-300 bg-white text-sky-600 hover:bg-sky-50 transition-all"
                                            :title="`${pt.hint}. Tenue : +15 relation, +5 moral. Rompue : −25 relation, −10 moral.`">
                                        {{ pt.icon }} {{ pt.label }}
                                    </button>
                                </template>
                                <span v-if="lastResolvedPromise"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                      :class="lastResolvedPromise.status === 'kept' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                    {{ lastResolvedPromise.status === 'kept' ? '✓ Promesse tenue' : '✗ Promesse rompue' }}
                                    <template v-if="lastResolvedPromise.type !== 'renewal'">
                                        ({{ lastResolvedPromise.played_matches ?? 0 }}/{{ lastResolvedPromise.target_matches }})
                                    </template>
                                </span>
                            </div>

                            <!-- Déclarations publiques -->
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Presse :</span>
                                <template v-if="!declarationOnCooldown">
                                    <button type="button"
                                            @click="emit('make-declaration', selectedMyPlayer, 'praise')"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold border border-emerald-300 bg-white text-emerald-600 hover:bg-emerald-50 transition-all"
                                            title="Féliciter publiquement. Mérité (en forme) : +8 relation, +4 moral. Immérité : +4 relation seulement.">
                                        📣 Féliciter
                                    </button>
                                    <button type="button"
                                            @click="emit('make-declaration', selectedMyPlayer, 'criticize')"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold border border-rose-300 bg-white text-rose-600 hover:bg-rose-50 transition-all"
                                            title="Critiquer publiquement. En méforme : peut provoquer une réaction d'orgueil (+5 moral)… ou être mal vécu. En forme : toujours injuste (−15 relation, −8 moral).">
                                        📢 Critiquer
                                    </button>
                                </template>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-400"
                                      :title="`Prochaine déclaration possible en semaine ${lastDeclaration.week + 3}`">
                                    ⏳ Déjà exprimé récemment
                                </span>
                                <span v-if="lastDeclarationSummary"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                      :class="lastDeclarationSummary.positive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                    {{ lastDeclarationSummary.text }} (S{{ lastDeclaration.week }})
                                </span>
                            </div>
                        </div>

                        <div v-if="selectedPlayerMoraleLogs.length" class="space-y-1">
                            <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Derniers changements</h5>
                            <div v-for="(log, i) in selectedPlayerMoraleLogs" :key="i"
                                 class="flex items-center gap-2 text-xs">
                                <span class="w-8 text-right font-black shrink-0"
                                      :class="log.value > 0 ? 'text-emerald-600' : 'text-rose-500'">
                                    {{ log.value > 0 ? '+' : '' }}{{ log.value }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 text-[10px] font-semibold shrink-0">
                                    {{ moraleSourceLabel(log.source) }}
                                </span>
                                <span class="text-slate-600 truncate">{{ log.label }}</span>
                                <span class="ml-auto text-[10px] text-slate-400 shrink-0">S{{ log.week }}</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Aucun changement de moral pour l'instant.</p>
                    </div>

                    <!-- Radar + Barres -->
                    <div class="grid grid-cols-2 gap-3">

                        <!-- Radar -->
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

                        <!-- Barres horizontales -->
                        <!-- Barres horizontales -->
                        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Statistiques</h4>
                            <div class="space-y-1.5">
                                <div v-for="stat in (selectedMyPlayer?.position?.toLowerCase().includes('goalkeeper') ? [
                                        {label:'Vitesse',  key:'speed',      color:'bg-sky-400'},
                                        {label:'Stamina',  key:'stamina',     color:'bg-emerald-400'},
                                        {label:'Défense',  key:'defense',     color:'bg-blue-400'},
                                        {label:'Arrêt ✋', key:'hand_save',   color:'bg-violet-400'},
                                        {label:'Arrêt 👊', key:'punch_save',  color:'bg-fuchsia-400'},
                                        {label:'Attaque',  key:'attack',      color:'bg-orange-400'},
                                        {label:'Block',    key:'block',       color:'bg-indigo-400'},
                                    ] : [
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
                                        {label:'Tête',    key:'heading',  color:'bg-cyan-400'},
                                    ])" :key="stat.key" class="flex items-center gap-2 text-xs">
                                    <span class="w-16 text-slate-500 shrink-0 text-[11px]">{{ stat.label }}</span>
                                    <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" :class="stat.color"
                                             :style="{width: Math.min((selectedMyPlayer[stat.key] ?? selectedMyPlayer.stats?.[stat.key] ?? 0), 100)+'%'}">
                                        </div>
                                    </div>
                                    <span class="w-6 text-right font-bold text-slate-700 text-[11px]">
                                        {{ selectedMyPlayer[stat.key] ?? selectedMyPlayer.stats?.[stat.key] ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perf chips -->
                    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Performance saison</h4>
                        <div v-if="selectedMyPlayerPerf" class="flex flex-wrap gap-2">
                            <div v-for="chip in perfChips" :key="chip.label"
                                 class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
                                 :class="chip.color">
                                <span>{{ chip.icon }}</span>
                                <span>{{ chip.label }}</span>
                                <span class="font-black">{{ chip.val }}</span>
                                <span v-if="chip.sub !== null" class="opacity-50 text-[10px]">/ {{ chip.sub }} ✓</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Aucune donnée de performance pour ce joueur.</p>
                    </div>

                </template>

                <div v-else class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
                    Sélectionne un joueur dans la liste ou sur le terrain
                </div>
            </div>
        </div>
    </div>
</template>
