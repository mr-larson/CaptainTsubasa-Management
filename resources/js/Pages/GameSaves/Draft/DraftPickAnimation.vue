<script setup>
import { usePixelArt } from '@/utils/pixelArt.js';
const { photoSrc } = usePixelArt();
// Annonce plein écran du dernier joueur pioché.
defineProps({
    show:      { type: Boolean, default: false },
    pick:      { type: Object,  default: null },
    highlight: { type: Boolean, default: false }, // pick d'une équipe humaine
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-300" leave-active-class="transition-opacity duration-300"
                    enter-from-class="opacity-0" leave-to-class="opacity-0">
            <div v-if="show && pick"
                 class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 pointer-events-none">
                <div class="bg-white rounded-2xl shadow-2xl p-8 text-center max-w-sm animate-bounce-in">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-slate-200 mx-auto mb-4 border-4"
                         :class="highlight ? 'border-amber-400' : 'border-slate-300'">
                        <img v-if="pick.photo_path" :src="photoSrc(`/storage/${pick.photo_path}`)" class="w-full h-full object-cover" alt=""/>
                        <div v-else class="w-full h-full flex items-center justify-center text-2xl text-slate-400">👤</div>
                    </div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ pick.team_name }}</div>
                    <div class="text-xl font-black text-slate-700">{{ pick.player_name }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ pick.position }} • {{ pick.cost }} GL</div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
