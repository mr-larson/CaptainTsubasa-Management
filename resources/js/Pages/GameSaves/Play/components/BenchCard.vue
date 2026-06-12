<script setup>
import { usePlayerUtils } from '../usePlayerUtils.js';

/** Carte "Banc" : remplaçants en grille, draggables vers le terrain. */
const props = defineProps({
    substitutes: { type: Array,    default: () => [] },
    selectedId:  { type: [Number, String], default: null },
    isPickedUp:  { type: Function, default: () => false },
});

const emit = defineEmits(['player-click', 'drag-start', 'drag-over', 'drop-on']);

const { playerPhotoUrl } = usePlayerUtils();
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-100 overflow-hidden shadow-sm flex flex-col" style="height:240px;">
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
                                    : selectedId === p.id
                                        ? 'border-teal-400 ring-2 ring-teal-300'
                                        : 'border-white/70'
                             ]">
                            <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover pointer-events-none" alt=""/>
                            <span v-else class="text-[9px] font-bold text-white">?</span>
                        </div>
                        <div class="mt-0.5 px-1 rounded text-[7px] font-semibold leading-tight text-center max-w-[48px] truncate pointer-events-none"
                             :class="isPickedUp(p)
                                ? 'bg-amber-300 text-slate-900'
                                : selectedId === p.id
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
</template>
