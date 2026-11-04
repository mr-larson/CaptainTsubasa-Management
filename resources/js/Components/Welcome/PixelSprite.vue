<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Sprite pixel art généré à la volée depuis une illustration détourée :
 * réduction à `rows` pixels de haut, contours nets (alpha binaire), palette
 * réduite et liseré sombre d'un pixel, comme un sprite de JRPG. Affiché à
 * l'échelle entière `scale` en `image-rendering: pixelated`. Décoratif.
 * Chargé seulement une fois visible : masqué (mobile), il ne coûte rien.
 */
const props = defineProps({
    src: { type: String, required: true },
    rows: { type: Number, default: 80 },
    scale: { type: Number, default: 4 },
    outline: { type: String, default: '#0b1220' },
});

const canvas = ref(null);
const ready = ref(false);

const LEVEL = 36; // pas de quantification par canal (≈ 8 niveaux)

function hexToRgb(hex) {
    const n = parseInt(hex.slice(1), 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

async function render() {
    const img = new Image();
    img.decoding = 'async';
    img.src = props.src;
    try { await img.decode(); } catch { return; }

    const h = props.rows;
    const w = Math.round((h * img.naturalWidth) / img.naturalHeight);
    // 1 px de marge tout autour pour le liseré.
    const W = w + 2, H = h + 2;
    const el = canvas.value;
    if (!el) return;
    el.width = W;
    el.height = H;
    const ctx = el.getContext('2d', { willReadFrequently: true });
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(img, 1, 1, w, h);

    const data = ctx.getImageData(0, 0, W, H);
    const px = data.data;
    const solid = new Uint8Array(W * H);
    for (let i = 0, p = 0; p < W * H; p++, i += 4) {
        if (px[i + 3] > 110) {
            solid[p] = 1;
            px[i] = Math.min(255, Math.round(px[i] / LEVEL) * LEVEL);
            px[i + 1] = Math.min(255, Math.round(px[i + 1] / LEVEL) * LEVEL);
            px[i + 2] = Math.min(255, Math.round(px[i + 2] / LEVEL) * LEVEL);
            px[i + 3] = 255;
        } else {
            px[i + 3] = 0;
        }
    }
    const [or, og, ob] = hexToRgb(props.outline);
    for (let y = 0; y < H; y++) {
        for (let x = 0; x < W; x++) {
            const p = y * W + x;
            if (solid[p]) continue;
            const edge = (x > 0 && solid[p - 1]) || (x < W - 1 && solid[p + 1])
                || (y > 0 && solid[p - W]) || (y < H - 1 && solid[p + W]);
            if (edge) {
                const i = p * 4;
                px[i] = or; px[i + 1] = og; px[i + 2] = ob; px[i + 3] = 255;
            }
        }
    }
    ctx.putImageData(data, 0, 0);
    el.style.height = `${H * props.scale}px`;
    ready.value = true;
}

let observer = null;
onMounted(() => {
    if (!('IntersectionObserver' in window)) { render(); return; }
    observer = new IntersectionObserver(([entry]) => {
        if (!entry.isIntersecting) return;
        observer.disconnect();
        render();
    }, { rootMargin: '200px' });
    observer.observe(canvas.value);
});
onUnmounted(() => observer?.disconnect());
</script>

<template>
    <canvas ref="canvas" class="pixel-sprite" :class="{ 'is-ready': ready }" aria-hidden="true" />
</template>

<style scoped>
.pixel-sprite {
    display: block;
    width: auto;
    image-rendering: pixelated;
    opacity: 0;
    transition: opacity 0.4s ease;
}
.pixel-sprite.is-ready {
    opacity: 1;
}
</style>
