<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { usePlayerUtils } from '../usePlayerUtils.js';

/**
 * Terrain : rendu du 11 selon la formation, avec drag & drop.
 * `slotMastery` (optionnel) affiche les pastilles hors-poste / poste secondaire.
 */
const props = defineProps({
    formationData:  { type: Object,   default: null },
    playerPosition: { type: Function, required: true },
    playerForSlot:  { type: Function, required: true },
    selectedSlot:   { type: Number,   default: null },
    isPickedUp:     { type: Function, default: () => false },
    players:        { type: Array,    default: () => [] },
    // (slot, slotDef) => 'primary' | 'secondary' | 'off' | null
    slotMastery:    { type: Function, default: null },
    // Postes vides actifs (Mon équipe) : clic / dépôt pour y placer un joueur.
    // (slot, slotDef) => [{ player, mastery, unavailable }] — remplaçants proposés.
    slotCandidates: { type: Function, default: null },
    // (player) => { icon, label } | null — indisponibilité (blessé, suspendu, consigné)
    playerStatus:   { type: Function, default: null },
    // (player, slotDef) => 'primary' | 'secondary' | 'off' | null — halos pendant la prise
    masteryFor:     { type: Function, default: null },
    // (player) => { yellow: n, red: bool } — cartons affichés au-dessus du joueur
    playerCards:    { type: Function, default: null },
});

const emit = defineEmits([
    'player-click', 'drag-start', 'drag-over', 'drop-on', 'drag-end',
    'fill-slot', 'drop-on-slot', 'cancel-pickup',
]);

const { playerPhotoUrl } = usePlayerUtils();

const ZONE_LABELS = ['GK', 'DEF', 'MDF', 'MOF', 'ATT'];
const ZONE_NAMES  = ['gardien', 'défenseur', 'milieu défensif', 'milieu offensif', 'attaquant'];
// Liseré du nom = couleur de la zone (même code que la « Répartition » de la carte Formation)
const ZONE_BORDER = ['border-amber-400', 'border-blue-400', 'border-emerald-400', 'border-orange-400', 'border-rose-400'];

// Pendant la prise : adéquation du joueur soulevé à chaque autre poste.
const HALO = {
    primary:   'ring-emerald-400 bg-emerald-400/25',
    secondary: 'ring-amber-300 bg-amber-300/25',
    off:       'ring-rose-500 bg-rose-500/25',
};
const HALO_TITLE = { primary: 'À son poste', secondary: 'Poste secondaire', off: 'Hors poste' };
const pickFit = (slot, slotDef) => {
    const picked = pickedPlayer.value;
    if (!picked || !props.masteryFor) return null;
    if (props.playerForSlot(slot)?.id === picked.id) return null;
    return props.masteryFor(picked, slotDef);
};

const statusOf  = (p) => (p && props.playerStatus ? props.playerStatus(p) : null);
const cardsOf   = (p) => {
    if (!p || !props.playerCards) return null;
    const c = props.playerCards(p) ?? {};
    const yellow = Math.min(Number(c.yellow) || 0, 3);
    return (yellow || c.red) ? { yellow, red: !!c.red } : null;
};
const cardsTitle = (c) => [c.yellow ? `${c.yellow} carton${c.yellow > 1 ? 's' : ''} jaune${c.yellow > 1 ? 's' : ''}` : '', c.red ? 'suspendu (rouge)' : '']
    .filter(Boolean).join(' · ');
const staminaOf = (p) => Number(p?.stamina ?? p?.stats?.stamina ?? 0);
const staminaBar = (v) => v >= 60 ? 'bg-emerald-400' : v >= 30 ? 'bg-amber-400' : 'bg-rose-500';

const pickedPlayer = computed(() => props.players.find(p => props.isPickedUp(p)) ?? null);

const pickupHint = computed(() => {
    const p = pickedPlayer.value;
    if (!p) return '';
    return p.is_starter
        ? `Choisis le remplaçant de ${p.lastname} (ou un coéquipier, un poste vide pour le déplacer)`
        : `${p.lastname} entre : choisis le titulaire qui sort`;
});

// ── Poste vide : sélecteur de remplaçants ────────────────────
const openSlot = ref(null);

const openSlotDef = computed(() => openSlot.value ? props.formationData?.slots?.[openSlot.value] : null);
const openSlotCandidates = computed(() =>
    openSlot.value && props.slotCandidates ? props.slotCandidates(openSlot.value, openSlotDef.value) : []
);

function onEmptySlotClick(slot) {
    if (!props.slotCandidates) return;
    // Un joueur est soulevé → il prend ce poste directement.
    if (pickedPlayer.value) {
        emit('fill-slot', Number(slot), pickedPlayer.value);
        return;
    }
    openSlot.value = Number(slot);
}

function chooseCandidate(player) {
    emit('fill-slot', openSlot.value, player);
    openSlot.value = null;
}

const onKey = (e) => { if (e.key === 'Escape') openSlot.value = null; };
watch(openSlot, (v) => {
    if (v) window.addEventListener('keydown', onKey);
    else   window.removeEventListener('keydown', onKey);
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));

const MASTERY_CHIP = {
    primary:   { label: 'Poste', class: 'bg-emerald-100 text-emerald-700' },
    secondary: { label: 'Secondaire', class: 'bg-amber-100 text-amber-700' },
    off:       { label: 'Hors poste', class: 'bg-rose-100 text-rose-700' },
};
</script>

<template>
    <div class="h-[280px] border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="relative w-full h-full">
            <div class="absolute inset-0 bg-gradient-to-r from-green-800 via-green-700 to-green-800"></div>
            <div v-for="i in 8" :key="i" class="absolute top-0 h-full bg-green-900/15" :style="{ left:((i-1)*12.5)+'%', width:'12.5%' }"></div>
            <div class="absolute inset-2 border border-white/40 rounded pointer-events-none"></div>
            <div class="absolute top-2 bottom-2 left-1/2 w-px bg-white/40"></div>
            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-20 h-20 rounded-full border border-white/30"></div>
            <div class="absolute left-2 top-1/2 -translate-y-1/2 border border-white/30 w-[9%] h-[55%]"></div>
            <div class="absolute right-2 top-1/2 -translate-y-1/2 border border-white/30 w-[9%] h-[55%]"></div>
            <div class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/15 border border-white/50 w-[2%] h-[22%]"></div>
            <div class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/15 border border-white/50 w-[2%] h-[22%]"></div>

            <template v-if="formationData">
                <div
                    v-for="(slotDef, slot) in formationData.slots"
                    :key="slot"
                    class="absolute -translate-x-1/2 -translate-y-1/2"
                    :class="playerForSlot(slot) ? 'cursor-grab active:cursor-grabbing' : slotCandidates ? 'cursor-pointer' : 'cursor-default'"
                    :style="{ left: playerPosition(slot).x+'%', top: playerPosition(slot).y+'%' }"
                    :title="playerForSlot(slot) ? `Endurance ${staminaOf(playerForSlot(slot))}` : undefined"
                    :draggable="!!playerForSlot(slot)"
                    :role="playerForSlot(slot) || slotCandidates ? 'button' : undefined"
                    :tabindex="playerForSlot(slot) || slotCandidates ? 0 : undefined"
                    :aria-label="playerForSlot(slot)
                        ? `${playerForSlot(slot).lastname ?? 'Joueur'}, poste ${slot}, endurance ${staminaOf(playerForSlot(slot))}`
                        : slotCandidates ? `Poste ${slot} vide (${ZONE_NAMES[slotDef.zone] ?? ''}) : placer un joueur` : undefined"
                    @keydown.enter.prevent.stop="playerForSlot(slot) ? emit('player-click', playerForSlot(slot)) : onEmptySlotClick(slot)"
                    @keydown.space.prevent.stop="playerForSlot(slot) ? emit('player-click', playerForSlot(slot)) : onEmptySlotClick(slot)"
                    @click.stop="playerForSlot(slot) ? emit('player-click', playerForSlot(slot)) : onEmptySlotClick(slot)"
                    @dragstart.stop="playerForSlot(slot) && emit('drag-start', playerForSlot(slot), $event)"
                    @dragend="emit('drag-end')"
                    @dragover="emit('drag-over', $event)"
                    @drop.stop="playerForSlot(slot) ? emit('drop-on', playerForSlot(slot), $event) : slotCandidates && emit('drop-on-slot', Number(slot), $event)"
                >
                    <!-- Halo d'adéquation du joueur soulevé pour ce poste -->
                    <div v-if="pickFit(slot, slotDef)"
                         class="absolute left-1/2 top-4 -translate-x-1/2 -translate-y-1/2 w-12 h-12 rounded-full ring-[3px] pointer-events-none"
                         :class="HALO[pickFit(slot, slotDef)]"
                         :title="HALO_TITLE[pickFit(slot, slotDef)]"></div>
                    <!-- Poste vide (Mon équipe) : cercle pointillé « + ZONE » -->
                    <div v-if="!playerForSlot(slot) && slotCandidates"
                         class="flex flex-col items-center transition-transform duration-150 hover:scale-110"
                         :class="pickedPlayer ? 'scale-110' : ''">
                        <div class="w-8 h-8 rounded-full border-2 border-dashed flex items-center justify-center text-white font-black text-sm shadow-md"
                             :class="pickedPlayer ? 'border-amber-300 bg-amber-300/30 animate-pulse' : 'border-white/80 bg-black/25'">+</div>
                        <div class="mt-0.5 px-1 rounded border-l-2 text-3xs font-semibold leading-tight bg-black/60 text-white pointer-events-none"
                             :class="ZONE_BORDER[slotDef.zone]">
                            {{ ZONE_LABELS[slotDef.zone] ?? slot }} vide
                        </div>
                    </div>
                    <div v-else class="relative flex flex-col items-center transition-transform duration-150"
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
                                        ? 'border-teal-300 ring-2 ring-teal-200'
                                        : statusOf(playerForSlot(slot)) ? 'border-rose-500 ring-2 ring-rose-500/70'
                                        : 'border-white/70',
                                slotDef.zone===0?'bg-amber-500':slotDef.zone===1?'bg-blue-500':slotDef.zone===2?'bg-emerald-500':slotDef.zone===3?'bg-orange-500':'bg-rose-500'
                            ]">
                            <img v-if="playerForSlot(slot) && playerPhotoUrl(playerForSlot(slot))"
                                 :src="playerPhotoUrl(playerForSlot(slot))" class="w-full h-full object-cover pointer-events-none" alt=""/>
                            <span v-else class="text-3xs font-bold text-white">{{ slot }}</span>
                        </div>
                        <!-- Cartons (coin bas gauche de la photo, en miroir du capitaine) -->
                        <div v-if="cardsOf(playerForSlot(slot))"
                             class="absolute top-5 -left-1.5 z-10 flex gap-0.5 pointer-events-none"
                             :title="cardsTitle(cardsOf(playerForSlot(slot)))">
                            <span v-for="i in cardsOf(playerForSlot(slot)).yellow" :key="'y' + i"
                                  class="w-1.5 h-2.5 rounded-[1px] bg-yellow-400 ring-1 ring-black/30 shadow-sm"></span>
                            <span v-if="cardsOf(playerForSlot(slot)).red"
                                  class="w-1.5 h-2.5 rounded-[1px] bg-red-600 ring-1 ring-black/30 shadow-sm"></span>
                        </div>
                        <!-- Indisponible (coin haut gauche) -->
                        <div v-if="statusOf(playerForSlot(slot))"
                             class="absolute -top-1.5 -left-1.5 w-4 h-4 rounded-full bg-white shadow text-2xs leading-4 text-center"
                             :title="statusOf(playerForSlot(slot)).label">{{ statusOf(playerForSlot(slot)).icon }}</div>
                        <!-- Capitaine (coin bas droit de la photo) -->
                        <div v-if="playerForSlot(slot)?.is_captain"
                             class="absolute top-5 -right-1.5 w-3.5 h-3.5 rounded-sm bg-amber-400 border border-white text-[8px] leading-[11px] text-slate-700 text-center font-black pointer-events-none"
                             title="Capitaine">C</div>
                        <!-- Endurance -->
                        <div v-if="playerForSlot(slot)" class="mt-0.5 w-8 h-[3px] rounded-full bg-black/40 overflow-hidden pointer-events-none"
                             :title="`Endurance ${staminaOf(playerForSlot(slot))}`">
                            <div class="h-full rounded-full" :class="staminaBar(staminaOf(playerForSlot(slot)))"
                                 :style="{ width: Math.min(staminaOf(playerForSlot(slot)), 100) + '%' }"></div>
                        </div>
                        <template v-if="slotMastery">
                            <div v-if="slotMastery(slot, slotDef) === 'off'"
                                 class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-rose-500 border border-white text-[8px] leading-[12px] text-white text-center font-bold pointer-events-none"
                                 title="Hors poste : malus appliqué en match">!</div>
                            <div v-else-if="slotMastery(slot, slotDef) === 'secondary'"
                                 class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-amber-400 border border-white text-[8px] leading-[12px] text-slate-700 text-center font-bold pointer-events-none"
                                 title="Poste secondaire : bonus réduit">2</div>
                        </template>
                        <div class="mt-0.5 px-1 rounded border-l-2 text-3xs font-semibold leading-tight text-center max-w-[64px] truncate pointer-events-none"
                             :title="ZONE_NAMES[slotDef.zone]"
                             :class="[ZONE_BORDER[slotDef.zone], isPickedUp(playerForSlot(slot))
                                ? 'bg-amber-300 text-slate-700'
                                : selectedSlot === Number(slot) ? 'bg-amber-300 text-slate-700'
                                : statusOf(playerForSlot(slot)) ? 'bg-rose-600/90 text-white' : 'bg-black/60 text-white']">
                            {{ playerForSlot(slot)?.lastname ?? '—' }}
                        </div>
                    </div>
                </div>

                <div v-if="pickedPlayer"
                     class="absolute bottom-1 left-1/2 -translate-x-1/2 max-w-[95%] flex items-center gap-2 bg-amber-400/95 text-slate-700 text-2xs font-bold pl-3 pr-1 py-0.5 rounded-full shadow-md pointer-events-none whitespace-nowrap">
                    <span class="truncate">⚡ {{ pickupHint }}</span>
                    <span v-if="masteryFor" class="shrink-0 flex items-center gap-1 text-3xs font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>poste
                        <span class="w-2 h-2 rounded-full bg-amber-300 ml-1"></span>2nd
                        <span class="w-2 h-2 rounded-full bg-rose-500 ml-1"></span>hors
                    </span>
                    <button type="button" @click.stop="emit('cancel-pickup')"
                            class="shrink-0 px-1.5 rounded-full bg-white/60 hover:bg-white text-2xs pointer-events-auto"
                            title="Annuler (Échap)">✕ Échap</button>
                </div>

                <!-- Sélecteur de remplaçant pour un poste vide -->
                <div v-if="openSlot && openSlotDef"
                     class="absolute inset-2 z-10 bg-white/95 rounded-lg shadow-xl flex flex-col overflow-hidden"
                     @click.stop>
                    <div class="flex items-center justify-between px-3 py-1.5 border-b border-slate-200 shrink-0">
                        <span class="text-[11px] font-bold text-slate-700">
                            Poste {{ ZONE_LABELS[openSlotDef.zone] }} vide — qui faire entrer ?
                        </span>
                        <button type="button" @click="openSlot = null"
                                class="text-2xs font-bold text-slate-400 hover:text-slate-700 px-1.5">✕</button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-1.5 space-y-1">
                        <button v-for="c in openSlotCandidates" :key="c.player.id" type="button"
                                @click="chooseCandidate(c.player)"
                                class="w-full flex items-center gap-2 px-2 py-1 rounded-md border text-left transition-all"
                                :class="c.unavailable ? 'border-slate-300 bg-slate-50 opacity-60 hover:opacity-100' : 'border-slate-300 bg-white hover:bg-teal-50 hover:border-teal-300'">
                            <img v-if="playerPhotoUrl(c.player)" :src="playerPhotoUrl(c.player)" class="w-6 h-6 rounded-full object-cover" alt="" />
                            <span class="flex-1 min-w-0 text-xs font-semibold text-slate-700 truncate">{{ c.player.lastname }}</span>
                            <span v-if="c.unavailable" class="text-[11px]" :title="c.unavailable">{{ c.unavailableIcon }}</span>
                            <span v-if="c.mastery" class="px-1.5 py-0.5 rounded-full text-3xs font-bold" :class="MASTERY_CHIP[c.mastery].class">
                                {{ MASTERY_CHIP[c.mastery].label }}
                            </span>
                            <span class="w-8 text-right text-2xs font-bold text-slate-400" title="Endurance">{{ c.player.stamina ?? c.player.stats?.stamina ?? '—' }}</span>
                        </button>
                        <p v-if="!openSlotCandidates.length" class="text-xs text-slate-500 italic text-center py-4">
                            Aucun remplaçant disponible.
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
