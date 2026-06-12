<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';

const props = defineProps({
    events:        { type: Array,  default: () => [] },
    homeName:      { type: String, default: 'Domicile' },
    awayName:      { type: String, default: 'Extérieur' },
    homeLogo:      { type: String, default: null },
    awayLogo:      { type: String, default: null },
    finalHomeScore:{ type: Number, default: 0 },
    finalAwayScore:{ type: Number, default: 0 },
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
    const track = [{ zone, lane, side }];

    for (let i = 0; i < props.events.length; i++) {
        const ev = props.events[i];
        // `ev.team` = équipe qui réalise l'action ce tour-ci (l'attaquant courant côté moteur)
        const actingTeam = ev.team ?? side;

        const explicitZone = parseZone(ev);
        lane = parseLane(ev) ?? lane;

        if (isGoalEvent(ev)) {
            // But marqué par actingTeam → l'adversaire relance depuis son camp
            side = other(actingTeam);
            zone = 1;
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
        <div class="relative h-28 rounded-xl overflow-hidden border border-emerald-900/30 bg-gradient-to-r from-emerald-700 via-emerald-600 to-emerald-700 shadow-inner">
            <!-- bandes de terrain -->
            <div class="absolute inset-0 grid grid-cols-5">
                <div v-for="n in 5" :key="n" class="border-r border-white/10 last:border-r-0"
                     :class="n % 2 === 0 ? 'bg-white/0' : 'bg-white/5'"></div>
            </div>
            <!-- ligne médiane -->
            <div class="absolute inset-y-0 left-1/2 w-px bg-white/30"></div>
            <!-- buts -->
            <div class="absolute inset-y-0 left-0 w-2 bg-white/40"></div>
            <div class="absolute inset-y-0 right-0 w-2 bg-white/40"></div>

            <!-- ballon -->
            <div class="absolute -translate-y-1/2 -translate-x-1/2 transition-all duration-700 ease-out z-10"
                 :style="{ left: ballPosition + '%', top: ballLane + '%' }">
                <div class="w-6 h-6 rounded-full bg-white shadow-lg flex items-center justify-center text-xs ring-2 ring-amber-400">
                    ⚽
                </div>
            </div>

            <!-- pas encore démarré -->
            <div v-if="cursor === 0" class="absolute inset-0 flex items-center justify-center bg-black/30 text-white text-xs font-bold">
                Appuie sur ▶ pour lancer le replay
            </div>
            <div v-else-if="isFinished" class="absolute inset-0 flex items-center justify-center bg-black/30 text-white text-xs font-bold">
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
