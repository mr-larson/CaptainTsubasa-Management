<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import creditsIndex from 'virtual:photo-credits-index';
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
    zoomable: { type: Boolean, default: true },
});

const open = ref(false);
const openPhoto = (event) => {
    if (!props.zoomable) return;
    event.stopPropagation();
    open.value = true;
};

// Index léger généré au build (vite/photoCredits.js) : un visuel crédité y figure sous son slug,
// avec son cadrage (c, w, h) s'il s'agit d'une illustration (i) et sa position d'objet (o).
// Source et texte de crédit (u, k) ne servent qu'à la visionneuse : chargés à l'ouverture.
const creditSlug = computed(() => {
    const filename = props.src?.split('/').pop()?.split('?')[0];
    const slug = filename?.replace(/\.[^.]+$/, '') ?? '';
    return Object.prototype.hasOwnProperty.call(creditsIndex, slug) ? slug : null;
});
const credit = computed(() => (creditSlug.value ? creditsIndex[creditSlug.value] : null));
const isIllustration = computed(() => !!credit.value?.i);
const portraitCrop = computed(() => !isPixel.value && credit.value?.c);
const portraitViewBox = computed(() => portraitCrop.value?.join(' '));
const cropClipId = `portrait-${Math.random().toString(36).slice(2)}`;

const detail = ref(null);
watch(open, async (isOpen) => {
    if (!isOpen || !isIllustration.value || detail.value) return;
    const { default: details } = await import('virtual:photo-credits-detail');
    detail.value = details[creditSlug.value] ?? {};
});
// Une illustration n'affiche son lien qu'une fois le détail chargé (évite un lien provisoire erroné).
const creditUrl = computed(() => {
    if (isIllustration.value) {
        return detail.value ? (detail.value.u ?? `${route('photo-credits')}#${creditSlug.value}`) : null;
    }
    return creditSlug.value ? `${route('photo-credits')}#${creditSlug.value}` : null;
});

// Mode Pixel : variante pixel art ; retour à l'original si elle n'existe pas.
const { photoSrc } = usePixelArt();
const pixelFailed = ref(false);
watch(() => props.src, () => { pixelFailed.value = false; });
const displaySrc = computed(() => (pixelFailed.value ? props.src : photoSrc(props.src)));
const portraitStyle = computed(() => (credit.value?.o ? { objectPosition: credit.value.o } : undefined));
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
    <!-- Cadrage d'affichage uniquement : le fichier de collection reste intact. -->
    <svg v-if="src && portraitCrop" v-bind="$attrs" :viewBox="portraitViewBox" preserveAspectRatio="xMidYMid meet"
         role="img" :aria-label="alt" class="overflow-hidden" :class="{ 'cursor-zoom-in': zoomable }" @click="openPhoto">
        <defs><clipPath :id="cropClipId" clipPathUnits="userSpaceOnUse">
            <rect :x="portraitCrop[0]" :y="portraitCrop[1]" :width="portraitCrop[2]" :height="portraitCrop[3]" />
        </clipPath></defs>
        <image :href="displaySrc" :width="credit.w" :height="credit.h" :clip-path="`url(#${cropClipId})`" />
    </svg>
    <img v-else-if="src" v-bind="$attrs" :style="portraitStyle" :src="displaySrc" :alt="alt" loading="lazy" decoding="async" :class="{ 'cursor-zoom-in': zoomable }" @click="openPhoto" @error="onError" />

    <Teleport to="body">
        <div v-if="open"
             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-6"
             @click="open = false">
            <button type="button"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl flex items-center justify-center transition-colors"
                    @click.stop="open = false" aria-label="Fermer">✕</button>
            <a v-if="creditUrl" :href="creditUrl" target="_blank" rel="noopener noreferrer"
               class="absolute bottom-4 rounded-full bg-slate-900/80 px-4 py-2 text-sm text-white underline"
               :title="detail?.k" @click.stop>{{ isIllustration ? 'Source et crédits du visuel' : 'Crédits des photos' }}</a>
            <svg v-if="portraitCrop" :viewBox="portraitViewBox" preserveAspectRatio="xMidYMid meet"
                 role="img" :aria-label="alt" class="h-auto max-w-[92vw] max-h-[82vh] overflow-hidden rounded-2xl shadow-2xl"
                 :style="{ width: `min(80vw, ${82 * portraitCrop[2] / portraitCrop[3]}vh)` }" @click.stop>
                <defs><clipPath :id="`${cropClipId}-zoom`" clipPathUnits="userSpaceOnUse">
                    <rect :x="portraitCrop[0]" :y="portraitCrop[1]" :width="portraitCrop[2]" :height="portraitCrop[3]" />
                </clipPath></defs>
                <image :href="displaySrc" :width="credit.w" :height="credit.h" :clip-path="`url(#${cropClipId}-zoom)`" />
            </svg>
            <img v-else :src="displaySrc" :alt="alt" class="max-w-[92vw] max-h-[88vh] object-contain rounded-2xl shadow-2xl"
                 :class="isPixel ? 'w-[min(80vw,80vh,640px)] h-auto' : ''" @click.stop />
        </div>
    </Teleport>
</template>
