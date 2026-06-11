<script setup>
import { usePlayerUtils } from './usePlayerUtils.js';

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
});

const emit = defineEmits(['player-click', 'drag-start', 'drag-over', 'drop-on']);

const { playerPhotoUrl } = usePlayerUtils();
</script>

<template>
    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm" style="height:240px;">
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
                        <template v-if="slotMastery">
                            <div v-if="slotMastery(slot, slotDef) === 'off'"
                                 class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-red-500 border border-white text-[8px] leading-[12px] text-white text-center font-bold pointer-events-none"
                                 title="Hors poste : malus appliqué en match">!</div>
                            <div v-else-if="slotMastery(slot, slotDef) === 'secondary'"
                                 class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-amber-400 border border-white text-[8px] leading-[12px] text-slate-900 text-center font-bold pointer-events-none"
                                 title="Poste secondaire : bonus réduit">2</div>
                        </template>
                        <div class="mt-0.5 px-1 rounded text-[7px] font-semibold leading-tight text-center max-w-[48px] truncate pointer-events-none"
                             :class="isPickedUp(playerForSlot(slot))
                                ? 'bg-amber-300 text-slate-900'
                                : selectedSlot === Number(slot) ? 'bg-yellow-300 text-slate-900' : 'bg-black/50 text-white'">
                            {{ playerForSlot(slot)?.lastname ?? '—' }}
                        </div>
                    </div>
                </div>

                <div v-if="players.some(p => isPickedUp(p))"
                     class="absolute top-1 left-1/2 -translate-x-1/2 bg-amber-400/95 text-slate-900 text-[10px] font-bold px-3 py-1 rounded-full shadow-md pointer-events-none">
                    ⚡ Tap un autre joueur pour échanger / titulariser
                </div>
            </template>
        </div>
    </div>
</template>
