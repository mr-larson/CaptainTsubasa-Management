<script setup>
import PixelLoader from '@/Components/PixelLoader.vue';
// Voile plein écran pendant une action serveur longue (simulation de semaine,
// enregistrement d'un match). Annoncé aux lecteurs d'écran.
defineProps({
    show:    { type: Boolean, default: false },
    message: { type: String,  default: 'Chargement…' },
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-from-class="opacity-0" enter-active-class="transition-opacity duration-200"
                    leave-to-class="opacity-0" leave-active-class="transition-opacity duration-200">
            <div v-if="show" class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
                 role="status" aria-live="polite">
                <div class="flex items-center gap-3 rounded-2xl bg-white px-6 py-4 shadow-2xl">
                    <PixelLoader aria-hidden="true" />
                    <span class="text-sm font-semibold text-slate-700">{{ message }}</span>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
