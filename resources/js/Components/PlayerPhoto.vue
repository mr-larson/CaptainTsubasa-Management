<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { usePixelArt } from '@/utils/pixelArt.js';

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

// Mode Pixel : variante pixel art ; retour à l'original si elle n'existe pas.
const { photoSrc } = usePixelArt();
const pixelFailed = ref(false);
watch(() => props.src, () => { pixelFailed.value = false; });
const displaySrc = computed(() => (pixelFailed.value ? props.src : photoSrc(props.src)));
const onError = () => { if (displaySrc.value !== props.src) pixelFailed.value = true; };
// Variante pixel (64 px) : agrandie dans la modale, sinon elle resterait minuscule.
const isPixel = computed(() => (displaySrc.value ?? '').includes('/storage/players/pixel/'));

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && open.value) open.value = false;
};

// Listener Échap posé seulement quand la photo est agrandie : une liste de
// plusieurs centaines de miniatures n'ajoute plus autant de listeners globaux.
watch(open, (isOpen) => {
    if (isOpen) document.addEventListener('keydown', closeOnEscape);
    else document.removeEventListener('keydown', closeOnEscape);
});
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <img v-if="src" v-bind="$attrs" :src="displaySrc" :alt="alt" loading="lazy" decoding="async" class="cursor-zoom-in" @click.stop="open = true" @error="onError" />

    <Teleport to="body">
        <div v-if="open"
             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-6"
             @click="open = false">
            <button type="button"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl flex items-center justify-center transition-colors"
                    @click.stop="open = false" aria-label="Fermer">✕</button>
            <img :src="displaySrc" :alt="alt" class="max-w-[92vw] max-h-[88vh] object-contain rounded-2xl shadow-2xl"
                 :class="isPixel ? 'w-[min(80vw,80vh,640px)] h-auto' : ''" @click.stop />
        </div>
    </Teleport>
</template>
