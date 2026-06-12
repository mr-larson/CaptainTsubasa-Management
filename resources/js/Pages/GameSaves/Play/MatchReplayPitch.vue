<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { FORMATIONS, DEFAULT_FORMATION, LANE_Y, GK_LANE_Y } from '@/Pages/Match/engine/formations.js';

const props = defineProps({
    events:        { type: Array,  default: () => [] },
    homeName:      { type: String, default: 'Domicile' },
    awayName:      { type: String, default: 'Extérieur' },
    homeLogo:      { type: String, default: null },
    awayLogo:      { type: String, default: null },
    finalHomeScore:{ type: Number, default: 0 },
    finalAwayScore:{ type: Number, default: 0 },
    // Effectifs pour la reconstitution : { formation, players: [{id, lastname, number, position, is_starter}] }
    homeSquad:     { type: Object, default: null },
    awaySquad:     { type: Object, default: null },
});

// ==========================
//  ÉTAT DE LECTURE
// ==========================
const cursor   = ref(0);          // index de l'événement courant (0 = avant le 1er)
const playing  = ref(false);
const speed    = ref(1);          // multiplicateur de vitesse
const BASE_DELAY = 1400;          // ms entre deux actions à vitesse x1

let timer = null;

const totalSteps = computed(() => props.events.length);
const currentEvent = computed(() => props.events[cursor.value - 1] ?? null);

// Score "live" recalculé en rejouant les buts jusqu'au curseur courant
const liveScore = computed(() => {
    let home = 0, away = 0;
    for (let i = 0; i < cursor.value; i++) {
        const ev = props.events[i];
        if (isGoalEvent(ev)) {
            if (ev.team === 'internal') home++; else away++;
        }
    }
    return { home, away };
});

const isFinished = computed(() => cursor.value >= totalSteps.value);

// Les matchs simulés IA taguent les buts en 'goal', le moteur réel en
// 'shot-goal' / 'special-goal' (cf. _detectType dans engine/ui.js) — on couvre les deux.
function isGoalEvent(ev) {
    return /goal/i.test(ev?.actionType ?? '');
}

function parseZone(ev) {
    // Source primaire : champ structuré `zone` (0..4, ajouté aux deux moteurs).
    if (Number.isFinite(ev?.zone)) return Math.max(1, Math.min(5, ev.zone + 1));
    // Fallback anciens matchs : parsing du texte "Zone N" des détails.
    if (!ev?.details) return null;
    for (const d of ev.details) {
        const m = typeof d === 'string' ? d.match(/Zone\s+(\d)/i) : null;
        if (m) return Math.max(1, Math.min(5, parseInt(m[1], 10)));
    }
    return null;
}

// Couloir (0 haut, 1 centre, 2 bas) → position verticale en %.
// Seuls les matchs joués manuellement ont cette info ; sinon centre.
function parseLane(ev) {
    return Number.isFinite(ev?.lane) ? [28, 50, 72][ev.lane] ?? 50 : null;
}

// Reconstitue la progression du ballon (zone + camp possesseur) au fil des événements.
// Quand l'info de zone explicite est disponible (matchs simulés IA → "Zone N" dans details)
// on s'en sert ; sinon on déduit une progression plausible à partir du résultat de
// chaque duel (succès → progression vers le but adverse, échec → le ballon repart en
// sens inverse côté défenseur), comme le fait le moteur réel par zones.
const ballTrack = computed(() => {
    const other = (s) => (s === 'internal' ? 'external' : 'internal');

    let zone = 3;     // 1..5, 3 = milieu de terrain (côté de l'équipe `side`, qui attaque)
    let lane = 50;    // position verticale en %
    let side = props.events[0]?.team ?? 'internal'; // équipe qui porte le ballon / attaque
    // Coup d'envoi : la prochaine action se joue au centre du terrain
    // (le moteur, lui, fait abstraitement repartir le ballon de la zone 0).
    let kickoff = true;
    const track = [{ zone, lane, side }];

    for (let i = 0; i < props.events.length; i++) {
        const ev = props.events[i];
        // `ev.team` = équipe qui réalise l'action ce tour-ci (l'attaquant courant côté moteur)
        const actingTeam = ev.team ?? side;

        const explicitZone = parseZone(ev);
        lane = parseLane(ev) ?? lane;

        if (isGoalEvent(ev)) {
            // Ballon au fond des filets, côté du buteur
            side = actingTeam;
            zone = 5;
            lane = 50;
            kickoff = true; // la reprise se fera au rond central
        } else if (kickoff) {
            // Coup d'envoi (début de match ou après un but) : au centre
            kickoff = false;
            side = actingTeam;
            zone = 3;
            lane = 50;
        } else if (ev.result === 'attack') {
            // L'attaquant progresse vers le but adverse
            side = actingTeam;
            zone = explicitZone ?? Math.min(5, zone + 1);
        } else if (ev.result === 'defense') {
            // Le porteur perd le ballon → l'adversaire récupère et repart depuis sa propre moitié
            side = other(actingTeam);
            zone = explicitZone ? Math.max(1, 6 - explicitZone) : 2;
        }
        // 'neutral' (coup d'envoi, faute, etc.) : on conserve l'état courant

        track.push({ zone, lane, side });
    }
    return track;
});

// Position horizontale du ballon sur le terrain (0 = but domicile, 100 = but extérieur)
const ballPosition = computed(() => {
    const { zone, side } = ballTrack.value[cursor.value] ?? { zone: 3, side: 'internal' };
    // zone 1..5 → translation en %  (zone 1 = côté du possesseur, zone 5 = devant le but adverse)
    const ratio = (zone - 1) / 4; // 0 → 1
    return side === 'internal'
        ? 12 + ratio * 76   // l'équipe interne attaque vers la droite
        : 88 - ratio * 76;  // l'équipe externe attaque vers la gauche
});

// Position verticale du ballon (couloir — seulement connu pour les matchs joués)
const ballLane = computed(() => ballTrack.value[cursor.value]?.lane ?? 50);

// Acteurs de l'action courante (id/nom/numéro structurés dans les logs récents)
const currentActors = computed(() => {
    const ev = currentEvent.value;
    if (!ev) return null;
    if (!ev.attacker && !ev.defender) return null;
    return { attacker: ev.attacker ?? null, defender: ev.defender ?? null };
});

// ==========================
//  RECONSTITUTION DES 22 JOUEURS
// ==========================
// Position horizontale de base par zone de formation, identique au moteur
// de match (field.js getCellCenter) : les formations couvrent tout le
// terrain et s'interpénètrent (les attaquants jouent dans le camp adverse).
// Équipe domicile, attaque vers la droite — l'extérieure est en miroir.
const ZONE_X = { 0: 10, 1: 20, 2: 35, 3: 55, 4: 75 };

const roleFromPosition = (pos) => {
    const p = String(pos || '').toLowerCase();
    if (p.includes('goal')) return 'GK';
    if (p.includes('def'))  return 'DF';
    if (p.includes('mid'))  return 'MF';
    if (p.includes('for') || p.includes('att')) return 'FW';
    return null;
};

const roleForZone = (zone) => (zone === 0 ? 'GK' : zone === 1 ? 'DF' : zone === 4 ? 'FW' : 'MF');

// Assigne les 11 titulaires aux slots de la formation, en privilégiant
// le rôle naturel de chaque joueur (reconstitution, pas enregistrement :
// le lineup exact du match n'est pas historisé).
function assignSlots(squad) {
    if (!squad?.players?.length) return [];
    const formation = FORMATIONS[squad.formation] ?? FORMATIONS[DEFAULT_FORMATION];

    let starters = squad.players.filter(p => p.is_starter);
    if (starters.length < 11) {
        const rest = squad.players.filter(p => !p.is_starter);
        starters = [...starters, ...rest].slice(0, 11);
    } else {
        starters = starters.slice(0, 11);
    }

    const pool   = [...starters];
    const placed = [];

    for (const [slot, def] of Object.entries(formation.slots)) {
        const wanted = roleForZone(def.zone);
        let idx = pool.findIndex(p => roleFromPosition(p.position) === wanted);
        if (idx === -1) idx = 0;
        const player = pool.splice(idx, 1)[0];
        if (!player) break;

        placed.push({
            ...player,
            slot: Number(slot),
            baseX: ZONE_X[def.zone] ?? 30,
            baseY: def.laneIndex === null ? GK_LANE_Y : (LANE_Y[def.laneIndex] ?? 50),
        });
    }
    return placed;
}

const homePlaced = computed(() => assignSlots(props.homeSquad));
const awayPlaced = computed(() =>
    assignSlots(props.awaySquad).map(p => ({ ...p, baseX: 100 - p.baseX }))
);

const hasSquads = computed(() => homePlaced.value.length > 0 || awayPlaced.value.length > 0);

// Position effective : les joueurs "respirent" vers le ballon, et les deux
// acteurs du duel se déplacent sur l'action.
const DRIFT = 0.10;

const placedPlayers = computed(() => {
    const ballX = ballPosition.value;
    const ballY = ballLane.value;
    const actors = currentActors.value;

    const place = (p, side) => {
        const isAttacker = actors?.attacker?.id != null && p.id === actors.attacker.id;
        const isDefender = actors?.defender?.id != null && p.id === actors.defender.id;

        let x, y;
        if (isAttacker) {
            // L'attaquant arrive sur le ballon, légèrement côté possesseur
            x = ballX + (side === 'internal' ? -3 : 3);
            y = ballY;
        } else if (isDefender) {
            // Le défenseur se place entre le ballon et son propre but
            x = ballX + (side === 'internal' ? -3 : 3);
            y = ballY;
        } else {
            x = p.baseX + (ballX - p.baseX) * DRIFT;
            y = p.baseY + (ballY - p.baseY) * DRIFT;
        }

        return {
            ...p, side,
            x: Math.max(2, Math.min(98, x)),
            y: Math.max(8, Math.min(92, y)),
            isActor: isAttacker || isDefender,
            actorRole: isAttacker ? 'attacker' : isDefender ? 'defender' : null,
        };
    };

    return [
        ...homePlaced.value.map(p => place(p, 'internal')),
        ...awayPlaced.value.map(p => place(p, 'external')),
    ];
});

const ACTION_ICONS = {
    goal: '⚽', shot: '🎯', pass: '🔁', dribble: '🌀',
    intercept: '🛡️', tackle: '🦵', block: '✋', save: '🧤',
    hands: '🧤', punch: '👊',
};
function actionIcon(ev) {
    if (!ev) return '⏳';
    return ACTION_ICONS[ev.actionType] ?? '▶';
}

function resultBadgeClass(ev) {
    if (!ev) return 'bg-slate-300';
    if (isGoalEvent(ev)) return 'bg-amber-500';
    return ev.result === 'attack' ? 'bg-emerald-500' : 'bg-rose-500';
}

// ==========================
//  CONTRÔLES DE LECTURE
// ==========================
function clearTimer() {
    if (timer) { clearInterval(timer); timer = null; }
}

function step() {
    if (cursor.value >= totalSteps.value) {
        playing.value = false;
        clearTimer();
        return;
    }
    cursor.value += 1;
}

function startTimer() {
    clearTimer();
    timer = setInterval(step, BASE_DELAY / speed.value);
}

function play() {
    if (isFinished.value) cursor.value = 0;
    playing.value = true;
    startTimer();
}

function pause() {
    playing.value = false;
    clearTimer();
}

function togglePlay() {
    playing.value ? pause() : play();
}

function stepForward() {
    pause();
    step();
}

function stepBackward() {
    pause();
    if (cursor.value > 0) cursor.value -= 1;
}

function restart() {
    pause();
    cursor.value = 0;
}

function seek(value) {
    pause();
    cursor.value = Number(value);
}

function cycleSpeed() {
    const speeds = [1, 2, 4];
    const idx = speeds.indexOf(speed.value);
    speed.value = speeds[(idx + 1) % speeds.length];
    if (playing.value) startTimer();
}

watch(() => props.events, () => {
    restart();
});

onUnmounted(() => clearTimer());
</script>

<template>
    <div class="flex flex-col gap-3">

        <!-- Marque-page / score live -->
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2 min-w-0">
                <img v-if="homeLogo" :src="homeLogo" class="w-5 h-5 object-contain shrink-0" alt=""/>
                <span class="text-xs font-black text-slate-700 truncate">{{ homeName }}</span>
            </div>
            <div class="px-3 py-1 rounded-lg bg-slate-800 text-white font-black text-sm tabular-nums shrink-0">
                {{ liveScore.home }} - {{ liveScore.away }}
                <span v-if="isFinished" class="ml-1 text-[9px] font-normal text-slate-400">(final {{ finalHomeScore }}-{{ finalAwayScore }})</span>
            </div>
            <div class="flex items-center gap-2 min-w-0 flex-row-reverse">
                <img v-if="awayLogo" :src="awayLogo" class="w-5 h-5 object-contain shrink-0" alt=""/>
                <span class="text-xs font-black text-slate-700 truncate">{{ awayName }}</span>
            </div>
        </div>

        <!-- Terrain -->
        <div class="relative rounded-xl overflow-hidden border border-emerald-900/30 bg-gradient-to-r from-emerald-700 via-emerald-600 to-emerald-700 shadow-inner"
             :class="hasSquads ? 'h-56' : 'h-28'">
            <!-- bandes de terrain -->
            <div class="absolute inset-0 grid grid-cols-5">
                <div v-for="n in 5" :key="n" class="border-r border-white/10 last:border-r-0"
                     :class="n % 2 === 0 ? 'bg-white/0' : 'bg-white/5'"></div>
            </div>
            <!-- ligne médiane + rond central -->
            <div class="absolute inset-y-0 left-1/2 w-px bg-white/30"></div>
            <div v-if="hasSquads" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 rounded-full border border-white/25"></div>
            <!-- surfaces -->
            <div v-if="hasSquads" class="absolute left-0 top-1/2 -translate-y-1/2 border border-white/25 border-l-0" style="width:10%;height:55%"></div>
            <div v-if="hasSquads" class="absolute right-0 top-1/2 -translate-y-1/2 border border-white/25 border-r-0" style="width:10%;height:55%"></div>
            <!-- buts -->
            <div class="absolute inset-y-0 left-0 w-2 bg-white/40"></div>
            <div class="absolute inset-y-0 right-0 w-2 bg-white/40"></div>

            <!-- 22 joueurs (reconstitution selon formation) -->
            <template v-if="hasSquads">
                <div v-for="p in placedPlayers" :key="p.side + '-' + p.id"
                     class="absolute -translate-x-1/2 -translate-y-1/2 transition-all duration-700 ease-out flex flex-col items-center"
                     :class="p.isActor ? 'z-20' : 'z-[5]'"
                     :style="{ left: p.x + '%', top: p.y + '%' }">
                    <div class="rounded-full flex items-center justify-center font-black text-white shadow transition-all"
                         :class="[
                            p.side === 'internal' ? 'bg-sky-600' : 'bg-rose-600',
                            p.isActor
                                ? 'w-6 h-6 text-[10px] ring-2 ring-amber-300 scale-110'
                                : 'w-5 h-5 text-[9px] ring-1 ring-white/50 opacity-90',
                         ]"
                         :title="`N°${p.number ?? '?'} ${p.lastname}`">
                        {{ p.number ?? '?' }}
                    </div>
                    <div v-if="p.isActor"
                         class="mt-0.5 px-1 rounded text-[7px] font-bold leading-tight whitespace-nowrap"
                         :class="p.actorRole === 'attacker' ? 'bg-amber-300 text-slate-900' : 'bg-white/90 text-slate-700'">
                        {{ p.lastname }}
                    </div>
                </div>
            </template>

            <!-- ballon -->
            <div class="absolute -translate-y-1/2 -translate-x-1/2 transition-all duration-700 ease-out z-10"
                 :style="{ left: ballPosition + '%', top: ballLane + '%' }">
                <div class="w-5 h-5 rounded-full bg-white shadow-lg flex items-center justify-center text-[10px] ring-2 ring-amber-400">
                    ⚽
                </div>
            </div>

            <!-- pas encore démarré -->
            <div v-if="cursor === 0" class="absolute inset-0 flex items-center justify-center bg-black/30 text-white text-xs font-bold z-30">
                Appuie sur ▶ pour lancer le replay
            </div>
            <div v-else-if="isFinished" class="absolute inset-0 flex items-center justify-center bg-black/30 text-white text-xs font-bold z-30">
                🏁 Match terminé
            </div>
        </div>

        <!-- Action en cours -->
        <div class="min-h-[64px] rounded-xl border border-slate-100 bg-slate-50 p-3 flex items-start gap-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm shrink-0"
                 :class="resultBadgeClass(currentEvent)">
                {{ actionIcon(currentEvent) }}
            </div>
            <div class="min-w-0">
                <div v-if="currentEvent" class="text-xs font-bold text-slate-700">
                    <span class="text-slate-400 mr-1">T{{ currentEvent.turn }}</span>{{ currentEvent.text }}
                </div>
                <div v-else class="text-xs text-slate-400 italic">En attente du coup d'envoi…</div>
                <!-- Acteurs du duel (logs structurés) -->
                <div v-if="currentActors" class="mt-1 flex flex-wrap items-center gap-1.5">
                    <span v-if="currentActors.attacker"
                          class="text-[10px] px-1.5 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-700">
                        N°{{ currentActors.attacker.number ?? '?' }} {{ currentActors.attacker.name }}
                    </span>
                    <span v-if="currentActors.attacker && currentActors.defender" class="text-[10px] text-slate-400">⚔️</span>
                    <span v-if="currentActors.defender"
                          class="text-[10px] px-1.5 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700">
                        N°{{ currentActors.defender.number ?? '?' }} {{ currentActors.defender.name }}
                    </span>
                </div>
                <div v-if="currentEvent?.details?.length" class="mt-1 flex flex-wrap gap-1">
                    <span v-for="(d, i) in currentEvent.details" :key="i"
                          class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-500">{{ d }}</span>
                </div>
            </div>
        </div>

        <!-- Barre de progression -->
        <div class="flex items-center gap-2">
            <span class="text-[10px] text-slate-400 tabular-nums w-10 text-right">{{ cursor }}/{{ totalSteps }}</span>
            <input type="range" min="0" :max="totalSteps" :value="cursor"
                   @input="seek($event.target.value)"
                   class="flex-1 accent-emerald-600 h-1.5"/>
        </div>

        <!-- Contrôles -->
        <div class="flex items-center justify-center gap-2">
            <button type="button" @click="restart"
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-xs transition-all"
                    title="Revenir au début">⏮</button>
            <button type="button" @click="stepBackward"
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-xs transition-all"
                    title="Action précédente">◀</button>
            <button type="button" @click="togglePlay"
                    class="w-11 h-11 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center text-base shadow-md transition-all">
                {{ playing ? '⏸' : '▶' }}
            </button>
            <button type="button" @click="stepForward"
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-xs transition-all"
                    title="Action suivante">▶|</button>
            <button type="button" @click="cycleSpeed"
                    class="px-2 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-[11px] font-bold text-slate-600 transition-all"
                    title="Vitesse de lecture">x{{ speed }}</button>
        </div>
    </div>
</template>
