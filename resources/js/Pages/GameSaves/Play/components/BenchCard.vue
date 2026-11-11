<script setup>
import { positionGroupClass } from '@/Enums/positionBadges.js';
import { usePlayerUtils } from '../usePlayerUtils.js';

/** Carte "Banc" : remplaçants en grille, draggables vers le terrain. */
const props = defineProps({
    substitutes: { type: Array,    default: () => [] },
    selectedId:  { type: [Number, String], default: null },
    isPickedUp:  { type: Function, default: () => false },
    // (player) => { icon, label } | null — indisponibilité (blessé, suspendu, consigné)
    playerStatus: { type: Function, default: null },
    // (sub) => 'primary' | 'secondary' | 'off' | null — adéquation au poste du titulaire soulevé
    pickFit:      { type: Function, default: null },
});

const emit = defineEmits(['player-click', 'drag-start', 'drag-over', 'drop-on', 'drag-end']);

const { playerPhotoUrl, positionGroup } = usePlayerUtils();

const FIT_RING = {
    primary:   'ring-4 ring-emerald-400',
    secondary: 'ring-4 ring-amber-300',
    off:       'ring-2 ring-rose-400/70',
};
const fitOf = (p) => (props.pickFit && !props.isPickedUp(p) ? props.pickFit(p) : null);

const statusOf   = (p) => (props.playerStatus ? props.playerStatus(p) : null);
const staminaOf  = (p) => Number(p?.stamina ?? p?.stats?.stamina ?? 0);
const staminaBar = (v) => v >= 60 ? 'bg-emerald-400' : v >= 30 ? 'bg-amber-400' : 'bg-rose-500';
</script>

<template>
    <div class="h-[280px] border border-slate-200 rounded-xl bg-slate-100 overflow-hidden shadow-sm flex flex-col">
        <div class="px-3 py-1.5 bg-slate-200/60 border-b border-slate-200 flex items-center justify-between shrink-0">
            <h4 class="text-2xs font-bold text-slate-500 uppercase tracking-wider">Banc</h4>
            <span class="text-2xs font-bold text-slate-400">{{ substitutes.length }}</span>
        </div>

        <div class="flex-1 overflow-y-auto p-2">
            <div v-if="substitutes.length" class="grid grid-cols-3 gap-2">
                <div
                    v-for="p in substitutes" :key="p.id"
                    class="cursor-grab active:cursor-grabbing flex justify-center"
                    :draggable="true"
                    role="button"
                    tabindex="0"
                    :aria-label="`${p.lastname ?? 'Joueur'}, remplaçant`"
                    :title="[p.lastname, p.position, `Endurance ${staminaOf(p)}`, statusOf(p)?.label].filter(Boolean).join(' · ')"
                    @keydown.enter.prevent.stop="emit('player-click', p)"
                    @keydown.space.prevent.stop="emit('player-click', p)"
                    @click.stop="emit('player-click', p)"
                    @dragstart.stop="emit('drag-start', p, $event)"
                    @dragend="emit('drag-end')"
                    @dragover="emit('drag-over', $event)"
                    @drop.stop="emit('drop-on', p, $event)"
                >
                    <div class="relative flex flex-col items-center transition-transform duration-150"
                         :class="[isPickedUp(p) ? 'scale-110' : 'hover:scale-105', statusOf(p) && !isPickedUp(p) ? 'opacity-50' : '']">
                        <div v-if="statusOf(p)"
                             class="absolute -top-1 left-0 w-4 h-4 rounded-full bg-white shadow text-2xs leading-4 text-center z-10">{{ statusOf(p).icon }}</div>
                        <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center overflow-hidden shadow-md transition-all bg-slate-400"
                             :class="[
                                isPickedUp(p)
                                    ? 'border-amber-300 ring-4 ring-amber-300/60 animate-pulse'
                                    : fitOf(p) ? ['border-white', FIT_RING[fitOf(p)]]
                                    : selectedId === p.id
                                        ? 'border-teal-400 ring-2 ring-teal-300'
                                        : 'border-white/70'
                             ]">
                            <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover pointer-events-none" alt=""/>
                            <span v-else class="text-3xs font-bold text-white">?</span>
                        </div>
                        <div class="mt-0.5 px-1 rounded text-3xs font-semibold leading-tight text-center max-w-[56px] truncate pointer-events-none"
                             :class="isPickedUp(p)
                                ? 'bg-amber-300 text-slate-700'
                                : selectedId === p.id
                                    ? 'bg-teal-300 text-slate-700'
                                    : 'bg-slate-300 text-slate-700'">
                            {{ p.lastname }}
                        </div>
                        <div class="mt-0.5 flex items-center gap-1 pointer-events-none">
                            <span class="px-1 rounded text-[8px] font-bold leading-tight"
                                  :class="positionGroupClass(positionGroup(p.position))">
                                {{ positionGroup(p.position) === 'OTHER' ? '?' : positionGroup(p.position) }}
                            </span>
                            <span class="w-5 h-[3px] rounded-full bg-slate-300 overflow-hidden">
                                <span class="block h-full rounded-full" :class="staminaBar(staminaOf(p))"
                                      :style="{ width: Math.min(staminaOf(p), 100) + '%' }"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <p v-else class="text-2xs text-slate-400 text-center py-4 italic">
                Aucun remplaçant
            </p>
        </div>
    </div>
</template>
