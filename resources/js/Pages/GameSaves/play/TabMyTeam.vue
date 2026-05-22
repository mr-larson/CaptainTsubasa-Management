<script setup>
import { FORMATIONS, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';

const props = defineProps({
    rosterWithStatus:     { type: Array,   required: true },
    selectedMyPlayer:     { type: Object,  default: null },
    currentFormation:     { type: String,  required: true },
    formationData:        { type: Object,  default: null },
    miniPitchMarkerStyle: { type: Object,  required: true },
    selectedMyPlayerPerf: { type: Object,  default: null },
    lineupForm:           { type: Array,   required: true },
});

const emit = defineEmits([
    'select-player',
    'toggle-starter',
    'change-slot',
    'save-formation',
]);

const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path)        return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
};

const overallOf = (player) => {
    if (!player) return 0;
    const s = player.stats ?? player;
    const keys = ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle','hand_save','punch_save'];
    const values = keys.map(k => Number(s[k] ?? 0)).filter(v => Number.isFinite(v));
    if (!values.length) return 0;
    return Math.round(values.reduce((a, b) => a + b, 0) / values.length);
};

const getSlotForPlayer = (playerId) => {
    if (!playerId || !Array.isArray(props.lineupForm)) return null;
    const row = props.lineupForm.find(r => r.player_id === playerId);
    return row ? row.slot : null;
};

function slotRoleInfo(slot) {
    const s   = Number(slot);
    const def = props.formationData?.slots?.[s];
    if (!def) return { label: '—', x: 50, y: 50 };
    const zoneLabels = { 0: 'Gardien', 1: 'Défenseur', 2: 'Milieu défensif', 3: 'Milieu offensif', 4: 'Attaquant' };
    const ZONE_X     = { 0: 8, 1: 25, 2: 45, 3: 65, 4: 85 };
    const LANE_Y     = { 0: 8, 1: 27, 2: 50, 3: 73, 4: 92 };
    return {
        label: zoneLabels[def.zone] ?? '—',
        x:     ZONE_X[def.zone]     ?? 50,
        y:     def.laneIndex === null ? 50 : (LANE_Y[def.laneIndex] ?? 50),
    };
}
</script>

<template>
    <div class="flex-1 grid grid-cols-12 gap-6">

        <!-- Sélecteur de formation -->
        <div class="col-span-12 border border-slate-200 rounded-lg bg-slate-50 p-4 mb-2">
            <div class="flex items-center gap-6 flex-wrap">
                <div>
                    <h3 class="text-md font-semibold text-slate-700 mb-1">Formation</h3>
                    <div class="flex gap-2 flex-wrap">
                        <button v-for="f in FORMATION_LIST" :key="f.key" type="button"
                                @click="emit('save-formation', f.key)"
                                :class="['px-4 py-1.5 rounded-full text-sm font-semibold border transition',
                                currentFormation === f.key
                                    ? 'bg-teal-500 text-white border-teal-600'
                                    : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100']">
                            {{ f.label }}
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ FORMATIONS[currentFormation]?.description }}</p>
                </div>

                <!-- Mini terrain formation -->
                <div class="relative w-48 h-32 rounded-lg overflow-hidden border border-slate-300 shadow-sm shrink-0">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-700 via-green-600 to-green-700"></div>
                    <div class="absolute inset-0 border-2 border-white/70 pointer-events-none"></div>
                    <div class="absolute left-1/2 top-0 h-full w-px bg-white/60"></div>
                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 rounded-full border border-white/50"></div>
                    <template v-if="formationData">
                        <div v-for="(slotDef, slot) in formationData.slots" :key="slot"
                             class="absolute h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white flex items-center justify-center text-[9px] font-bold shadow"
                             :class="slotDef.zone === 0 ? 'bg-yellow-400' : slotDef.zone <= 1 ? 'bg-blue-400' : slotDef.zone <= 2 ? 'bg-green-400' : slotDef.zone <= 3 ? 'bg-orange-400' : 'bg-red-400'"
                             :style="{ left: slotRoleInfo(slot).x + '%', top: slotRoleInfo(slot).y + '%' }">
                            {{ slot }}
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Liste joueurs -->
        <div class="col-span-3 border border-slate-200 rounded-lg bg-slate-50 p-3 self-start">
            <h3 class="text-md font-semibold text-slate-700 mb-2">Joueurs</h3>
            <div v-if="rosterWithStatus.length" class="max-h-96 overflow-y-auto space-y-1">
                <button v-for="p in rosterWithStatus" :key="p.id" type="button"
                        @click="emit('select-player', p)"
                        :class="['w-full text-left text-sm px-2 py-1 rounded',
                        selectedMyPlayer?.id === p.id ? 'bg-teal-100 text-slate-900' : 'bg-white hover:bg-slate-100 text-slate-700']">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex flex-col">
                            <span class="truncate">{{ p.firstname }} {{ p.lastname }}</span>
                            <span class="text-[11px] text-slate-500">{{ p.position }}</span>
                        </div>
                        <span class="ml-2 shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                              :class="p.is_starter ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'">
                            {{ p.is_starter ? 'Titulaire' : 'Remplaçant' }}
                        </span>
                    </div>
                </button>
            </div>
            <p v-else class="text-sm text-slate-500">Aucun joueur sous contrat.</p>
        </div>

        <!-- Profil joueur sélectionné -->
        <div class="col-span-9 flex flex-col gap-6">
            <template v-if="selectedMyPlayer">

                <!-- Carte profil -->
                <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                    <div class="flex items-start gap-6">

                        <!-- Photo + overall -->
                        <div class="relative w-24 h-24 rounded-lg border border-slate-200 bg-white overflow-hidden flex items-center justify-center">
                            <img v-if="playerPhotoUrl(selectedMyPlayer)" :src="playerPhotoUrl(selectedMyPlayer)" class="h-full w-full object-cover" alt="Photo joueur"/>
                            <span v-else class="text-xs text-slate-400 text-center px-2">Aucune<br>photo</span>
                            <div v-if="overallOf(selectedMyPlayer) > 0"
                                 :class="['absolute -top-2 -left-2 h-9 w-9 rounded-full border-2 border-white flex items-center justify-center shadow-md',
                                    overallOf(selectedMyPlayer) >= 80 ? 'bg-emerald-600' : overallOf(selectedMyPlayer) >= 70 ? 'bg-emerald-500' : overallOf(selectedMyPlayer) >= 60 ? 'bg-teal-500' : 'bg-slate-500']">
                                <span class="text-xs font-extrabold text-white">{{ overallOf(selectedMyPlayer) }}</span>
                            </div>
                        </div>

                        <!-- Infos + actions -->
                        <div class="flex-1 flex flex-col gap-2">
                            <h3 class="text-lg font-semibold text-slate-800">{{ selectedMyPlayer.firstname }} {{ selectedMyPlayer.lastname }}</h3>
                            <p class="text-sm text-slate-600">
                                Poste : <span class="font-semibold">{{ selectedMyPlayer.position }}</span>
                                <span class="text-slate-400 mx-2">•</span>
                                Coût : <span class="font-semibold">{{ selectedMyPlayer.cost ?? 0 }} €</span>
                            </p>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                                <button v-if="selectedMyPlayer.contract_id" type="button"
                                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm"
                                        :class="selectedMyPlayer.is_starter ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-slate-300 text-slate-800 hover:bg-slate-400'"
                                        @click="emit('toggle-starter', selectedMyPlayer.contract_id)">
                                    {{ selectedMyPlayer.is_starter ? 'Titulaire' : 'Remplaçant' }}
                                </button>
                                <div class="flex items-center gap-2 ml-auto">
                                    <label class="text-[11px] text-slate-500">Slot terrain</label>
                                    <select :value="getSlotForPlayer(selectedMyPlayer.id) ?? ''"
                                            :disabled="!selectedMyPlayer.is_starter"
                                            @change="emit('change-slot', $event.target.value)"
                                            class="border border-slate-300 rounded-md px-2 py-1 text-xs bg-white disabled:bg-slate-100 disabled:text-slate-400 focus:ring focus:ring-teal-200">
                                        <option value="">{{ selectedMyPlayer.is_starter ? '(non assigné)' : 'Titulaire requis' }}</option>
                                        <option v-for="n in 11" :key="n" :value="n">{{ n }}</option>
                                    </select>
                                </div>
                            </div>
                            <p v-if="selectedMyPlayer.description" class="mt-3 text-sm text-slate-600">{{ selectedMyPlayer.description }}</p>
                            <p v-else class="mt-3 text-sm text-slate-400 italic">Aucune description.</p>
                        </div>

                        <!-- Mini terrain joueur -->
                        <div class="w-40 h-32 relative rounded-lg overflow-hidden border border-slate-300 shadow-sm">
                            <div class="absolute inset-0 bg-gradient-to-r from-green-700 via-green-600 to-green-700"></div>
                            <div v-for="i in 6" :key="i" class="absolute top-0 h-full w-[16.66%] bg-green-800/20" :style="{ left: ((i-1)*16.66)+'%' }"></div>
                            <div class="absolute inset-0 border-2 border-white/70 pointer-events-none"></div>
                            <div class="absolute left-1/2 top-0 h-full w-px bg-white/60"></div>
                            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 rounded-full border border-white/70"></div>
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-8 h-24 border border-white/60"></div>
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-4 h-12 border border-white/70"></div>
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-8 h-24 border border-white/60"></div>
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-12 border border-white/70"></div>
                            <div v-if="selectedMyPlayer.is_starter && getSlotForPlayer(selectedMyPlayer.id)"
                                 class="absolute h-5 w-5 rounded-full border-2 border-white bg-yellow-300 shadow-md flex items-center justify-center text-[10px] font-bold"
                                 :style="miniPitchMarkerStyle">
                                {{ getSlotForPlayer(selectedMyPlayer.id) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                    <h4 class="text-md font-semibold text-slate-700 mb-3">Statistiques</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div v-for="stat in [
                            { label: 'Vitesse',       key: 'speed' },
                            { label: 'Stamina',       key: 'stamina' },
                            { label: 'Attaque',       key: 'attack' },
                            { label: 'Défense',       key: 'defense' },
                            { label: 'Tir',           key: 'shot' },
                            { label: 'Passe',         key: 'pass' },
                            { label: 'Dribble',       key: 'dribble' },
                            { label: 'Block',         key: 'block' },
                            { label: 'Interception',  key: 'intercept' },
                            { label: 'Tacle',         key: 'tackle' },
                            { label: 'Arrêt main',    key: 'hand_save' },
                            { label: 'Arrêt poings',  key: 'punch_save' },
                        ]" :key="stat.key" class="flex justify-between border-b border-slate-200 pb-1">
                            <span class="text-slate-600">{{ stat.label }}</span>
                            <span class="font-semibold text-slate-800">{{ selectedMyPlayer[stat.key] ?? selectedMyPlayer.stats?.[stat.key] ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </template>
            <p v-else class="text-sm text-slate-500">Sélectionne un joueur dans la liste à gauche.</p>
        </div>

        <!-- Historique & performance -->
        <div class="col-span-12 border border-slate-200 rounded-lg bg-slate-50 p-6 mt-2">
            <h4 class="text-md font-semibold text-slate-800 mb-4">📊 Historique & performance</h4>
            <template v-if="selectedMyPlayerPerf">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-slate-700">
                    <div>
                        <p class="font-semibold mb-2">⚽ Actions offensives</p>
                        <ul class="space-y-1">
                            <li><span class="font-medium">Tirs :</span> {{ selectedMyPlayerPerf.offense.shot.attempts }}, {{ selectedMyPlayerPerf.offense.shot.success }} réussis</li>
                            <li><span class="font-medium">Passes :</span> {{ selectedMyPlayerPerf.offense.pass.attempts }}, {{ selectedMyPlayerPerf.offense.pass.success }} réussies</li>
                            <li><span class="font-medium">Dribbles :</span> {{ selectedMyPlayerPerf.offense.dribble.attempts }}, {{ selectedMyPlayerPerf.offense.dribble.success }} réussis</li>
                            <li><span class="font-medium">Spéciaux :</span> {{ selectedMyPlayerPerf.offense.special.attempts }}, {{ selectedMyPlayerPerf.offense.special.success }} réussis</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold mb-2">🛡️ Actions défensives</p>
                        <ul class="space-y-1">
                            <li><span class="font-medium">Interceptions :</span> {{ selectedMyPlayerPerf.defense.intercept.attempts }}, {{ selectedMyPlayerPerf.defense.intercept.success }} réussies</li>
                            <li><span class="font-medium">Tacles :</span> {{ selectedMyPlayerPerf.defense.tackle.attempts }}, {{ selectedMyPlayerPerf.defense.tackle.success }} réussis</li>
                            <li><span class="font-medium">Blocks :</span> {{ selectedMyPlayerPerf.defense.block.attempts }}, {{ selectedMyPlayerPerf.defense.block.success }} réussis</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold mb-2">🧤 Actions gardien</p>
                        <ul class="space-y-1">
                            <li><span class="font-medium">Arrêts main :</span> {{ selectedMyPlayerPerf.defense.hands.attempts }}, {{ selectedMyPlayerPerf.defense.hands.success }} réussis</li>
                            <li><span class="font-medium">Poings :</span> {{ selectedMyPlayerPerf.defense.punch.attempts }}, {{ selectedMyPlayerPerf.defense.punch.success }} réussis</li>
                            <li><span class="font-medium">Special GK :</span> {{ selectedMyPlayerPerf.defense.gkSpecial.attempts }}, {{ selectedMyPlayerPerf.defense.gkSpecial.success }} réussis</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold mb-2">⚔️ Duels</p>
                        <ul class="space-y-1">
                            <li><span class="font-medium">Duels gagnés :</span> {{ selectedMyPlayerPerf.duelsWon }}</li>
                            <li><span class="font-medium">Duels perdus :</span> {{ selectedMyPlayerPerf.duelsLost }}</li>
                        </ul>
                    </div>
                </div>
            </template>
            <p v-else class="text-sm text-slate-500">Aucune action enregistrée pour ce joueur dans cette sauvegarde.</p>
        </div>
    </div>
</template>
