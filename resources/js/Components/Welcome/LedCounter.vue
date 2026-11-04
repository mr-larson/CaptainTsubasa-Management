<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

/**
 * Compteur façon tableau d'affichage de stade : chiffres en matrice de points
 * 5×7 (SVG), qui défilent jusqu'à la valeur quand le panneau entre à l'écran
 * et repartent de zéro quand il en sort. Le nombre réel reste lisible par les
 * lecteurs d'écran (texte masqué), le SVG est décoratif.
 */
const props = defineProps({
    value: { type: Number, required: true },
    label: { type: String, required: true },
});

// Glyphes 5×7 : une chaîne de 5 bits par ligne.
const GLYPHS = {
    0: ['01110', '10001', '10011', '10101', '11001', '10001', '01110'],
    1: ['00100', '01100', '00100', '00100', '00100', '00100', '01110'],
    2: ['01110', '10001', '00001', '00010', '00100', '01000', '11111'],
    3: ['11111', '00010', '00100', '00010', '00001', '10001', '01110'],
    4: ['00010', '00110', '01010', '10010', '11111', '00010', '00010'],
    5: ['11111', '10000', '11110', '00001', '00001', '10001', '01110'],
    6: ['00110', '01000', '10000', '11110', '10001', '10001', '01110'],
    7: ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
    8: ['01110', '10001', '10001', '01110', '10001', '10001', '01110'],
    9: ['01110', '10001', '10001', '01111', '00001', '00010', '01100'],
};

const DURATION = 1300;   // ms
const TICKS = 26;        // le compteur avance par crans, comme un afficheur

const shown = ref(props.value);
const root = ref(null);
const digits = computed(() => Math.max(2, String(props.value).length));

// Points de chaque chiffre (cases éteintes incluses, pour la grille du panneau).
const dots = computed(() => {
    const str = String(shown.value).padStart(digits.value, ' ');
    const out = [];
    [...str].forEach((ch, d) => {
        const glyph = GLYPHS[ch];
        for (let y = 0; y < 7; y++) {
            for (let x = 0; x < 5; x++) {
                out.push({ x: d * 6 + x + 0.5, y: y + 0.5, on: !!glyph && glyph[y][x] === '1' });
            }
        }
    });
    return out;
});
const width = computed(() => digits.value * 6 - 1);

let observer = null;
let raf = 0;

function countUp() {
    cancelAnimationFrame(raf);
    const t0 = performance.now();
    let lastTick = -1;
    const frame = (now) => {
        const p = Math.min(1, (now - t0) / DURATION);
        const tick = Math.floor(p * TICKS);
        if (tick !== lastTick) {
            lastTick = tick;
            const eased = 1 - Math.pow(1 - tick / TICKS, 3);
            shown.value = Math.round(props.value * eased);
        }
        if (p < 1) raf = requestAnimationFrame(frame);
    };
    raf = requestAnimationFrame(frame);
}

onMounted(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
    shown.value = 0;
    observer = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) countUp();
        else { cancelAnimationFrame(raf); shown.value = 0; }
    }, { threshold: 0.6 });
    observer.observe(root.value);
});
onUnmounted(() => { observer?.disconnect(); cancelAnimationFrame(raf); });
</script>

<template>
    <div ref="root" class="led-panel">
        <span class="sr-only">{{ value }}</span>
        <svg :viewBox="`0 0 ${width} 7`" class="led-digits" aria-hidden="true" focusable="false">
            <circle v-for="(d, i) in dots" :key="i" :cx="d.x" :cy="d.y" r="0.36" :class="d.on ? 'led-on' : 'led-off'" />
        </svg>
        <span class="led-label">{{ label }}</span>
    </div>
</template>

<style scoped>
.led-panel {
    display: inline-flex;
    flex-direction: column;
    gap: 0.55rem;
    padding: 0.85rem 1rem 0.7rem;
    border-radius: 0.75rem;
    border: 1px solid rgb(94 234 212 / 0.14);
    /* Fond de panneau : noir profond + trame de lignes très légère. */
    background:
        repeating-linear-gradient(0deg, rgb(255 255 255 / 0.018) 0 1px, transparent 1px 3px),
        rgb(2 6 23 / 0.75);
    box-shadow: inset 0 0 24px rgb(0 0 0 / 0.6), 0 0 24px rgb(45 212 191 / 0.06);
}
.led-digits {
    height: 2.6rem;
    width: auto;
    overflow: visible;
    filter: drop-shadow(0 0 1.5px rgb(94 234 212 / 0.9)) drop-shadow(0 0 6px rgb(45 212 191 / 0.45));
}
.led-on  { fill: #a7f3e6; }
.led-off { fill: rgb(148 163 184 / 0.09); }
.led-label {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgb(94 234 212 / 0.75);
}
</style>
