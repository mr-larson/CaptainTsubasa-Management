<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

/**
 * <img> de photo joueur, cliquable pour l'agrandir dans un modal plein écran.
 * Drop-in replacement de <img :src="..." class="..." /> : les attributs
 * (class, etc.) passent tels quels sur l'<img> miniature.
 */
defineOptions({ inheritAttrs: false });

const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
});

const open = ref(false);

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && open.value) open.value = false;
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <img v-if="src" v-bind="$attrs" :src="src" :alt="alt" class="cursor-zoom-in" @click.stop="open = true" />

    <Teleport to="body">
        <div v-if="open"
             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-6"
             @click="open = false">
            <button type="button"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl flex items-center justify-center transition-colors"
                    @click.stop="open = false" aria-label="Fermer">✕</button>
            <img :src="src" :alt="alt" class="max-w-[92vw] max-h-[88vh] object-contain rounded-2xl shadow-2xl" @click.stop />
        </div>
    </Teleport>
</template>
