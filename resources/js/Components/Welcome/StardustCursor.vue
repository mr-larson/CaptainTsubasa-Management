<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Traînée de poussière d'étoiles derrière la souris : petits pixels lumineux
 * (teal, blanc, quelques ambrés) semés le long du trajet du curseur, qui
 * dérivent, scintillent et s'éteignent en moins d'une seconde.
 *
 * Un seul canvas fixe, sans interaction (pointer-events: none). La boucle rAF
 * ne tourne que tant que des grains sont vivants ; au repos, rien ne tourne.
 * Désactivé sur écran tactile (pas de souris) et en prefers-reduced-motion.
 */
const canvas = ref(null);

const MAX = 60;           // grains vivants au maximum
const SPACING = 10;       // px de trajet entre deux émissions
const LIFE = [550, 950];  // durée de vie (ms)
const COLORS = ['204 251 241', '153 246 228', '45 212 191', '255 255 255', '252 211 77'];

let ctx = null;
let dpr = 1;
let raf = 0;
let last = null;          // dernière position d'émission
let prevT = 0;
const grains = [];

function resize() {
    const el = canvas.value;
    dpr = Math.min(2, window.devicePixelRatio || 1);
    el.width = Math.round(window.innerWidth * dpr);
    el.height = Math.round(window.innerHeight * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
}

function spawn(x, y, now) {
    if (grains.length >= MAX) grains.shift();
    const angle = Math.random() * Math.PI * 2;
    const speed = 0.01 + Math.random() * 0.03;          // px/ms
    grains.push({
        x: x + (Math.random() - 0.5) * 6,
        y: y + (Math.random() - 0.5) * 6,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed + 0.012,            // légère retombée
        size: Math.random() > 0.75 ? 3 : 2,
        color: COLORS[Math.random() > 0.9 ? 4 : Math.floor(Math.random() * 4)],
        born: now,
        life: LIFE[0] + Math.random() * (LIFE[1] - LIFE[0]),
        twinkle: Math.random() * Math.PI * 2,
    });
}

function frame(now) {
    raf = 0;
    const dt = Math.min(48, now - (prevT || now));
    prevT = now;
    ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
    ctx.globalCompositeOperation = 'lighter';

    for (let i = grains.length - 1; i >= 0; i--) {
        const g = grains[i];
        const age = (now - g.born) / g.life;
        if (age >= 1) { grains.splice(i, 1); continue; }
        g.x += g.vx * dt;
        g.y += g.vy * dt;
        g.vx *= 0.985;
        g.vy *= 0.985;
        // Fondu + scintillement en crans (rythme rétro).
        const flicker = Math.sin(g.twinkle + now / 70) > -0.3 ? 1 : 0.45;
        const a = (1 - age) * flicker;
        const px = Math.round(g.x), py = Math.round(g.y);   // alignés sur la grille : pixels nets
        ctx.fillStyle = `rgb(${g.color} / ${(a * 0.22).toFixed(3)})`;
        ctx.fillRect(px - g.size, py - g.size, g.size * 3, g.size * 3);   // halo
        ctx.fillStyle = `rgb(${g.color} / ${a.toFixed(3)})`;
        ctx.fillRect(px, py, g.size, g.size);
    }
    if (grains.length) raf = requestAnimationFrame(frame);
    else prevT = 0;
}

function onMove(e) {
    if (e.pointerType && e.pointerType !== 'mouse') return;
    const now = performance.now();
    const x = e.clientX, y = e.clientY;
    if (!last) { last = { x, y }; return; }
    const dx = x - last.x, dy = y - last.y;
    const dist = Math.hypot(dx, dy);
    if (dist < SPACING) return;
    // Sème des grains régulièrement le long du segment parcouru.
    const n = Math.min(4, Math.floor(dist / SPACING));
    for (let i = 1; i <= n; i++) spawn(last.x + (dx * i) / n, last.y + (dy * i) / n, now);
    last = { x, y };
    if (!raf) raf = requestAnimationFrame(frame);
}

const onLeave = () => { last = null; };

let enabled = false;
onMounted(() => {
    const fineMouse = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!fineMouse || reduced) return;
    ctx = canvas.value.getContext('2d');
    resize();
    enabled = true;
    window.addEventListener('pointermove', onMove, { passive: true });
    document.addEventListener('pointerleave', onLeave);
    window.addEventListener('resize', resize, { passive: true });
});
onUnmounted(() => {
    if (!enabled) return;
    window.removeEventListener('pointermove', onMove);
    document.removeEventListener('pointerleave', onLeave);
    window.removeEventListener('resize', resize);
    cancelAnimationFrame(raf);
});
</script>

<template>
    <canvas ref="canvas" class="stardust-cursor" aria-hidden="true" />
</template>

<style scoped>
.stardust-cursor {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    pointer-events: none;
    z-index: 40;        /* au-dessus du contenu, sous la barre de navigation (z-50) */
}
</style>
