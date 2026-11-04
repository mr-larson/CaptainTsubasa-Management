<script setup>
/**
 * Arrière-plan « HD-2D » : poussière de pixels lumineux qui monte par crans en
 * scintillant, sur plusieurs profondeurs. Le parallaxe lit la variable CSS
 * `--hero-y` (px de scroll) posée par la page. Animations en transform/opacity
 * uniquement (compositeur). Décoratif.
 *
 * Props : `count` (densité), `intensity` (0-1, luminosité globale), `seed`
 * (composition), `fixed` (couche fixée à l'écran, derrière toute la page : le
 * contenu défile devant, ce qui donne un parallaxe naturel).
 */
const props = defineProps({
    count: { type: Number, default: 22 },
    intensity: { type: Number, default: 1 },
    seed: { type: Number, default: 11 },
    fixed: { type: Boolean, default: false },
});

// Générateur pseudo-aléatoire déterministe : même composition à chaque rendu.
function rng(seed) {
    return () => {
        seed = (seed * 16807) % 2147483647;
        return (seed - 1) / 2147483646;
    };
}
const rand = rng(props.seed);

const motes = Array.from({ length: props.count }, (_, i) => {
    const depth = rand();                     // 0 = loin, 1 = proche
    return {
        left: `${(rand() * 100).toFixed(1)}%`,
        top: `${(props.fixed ? rand() * 100 : 25 + rand() * 75).toFixed(1)}%`,
        size: depth > 0.7 ? 3 : 2,
        warm: i % 6 === 0,                    // quelques grains ambrés
        dur: `${(9 + rand() * 9).toFixed(1)}s`,
        delay: `${(-rand() * 18).toFixed(1)}s`,
        parallax: (0.08 + depth * 0.25).toFixed(3),
    };
});
</script>

<template>
    <div class="pixel-dust" :class="{ 'pixel-dust--fixed': fixed }" :style="{ opacity: intensity }" aria-hidden="true">
        <span v-for="(m, i) in motes" :key="i" class="dust-layer"
              :style="{ left: m.left, top: m.top, '--p': m.parallax }">
            <span class="mote" :class="{ 'mote--warm': m.warm }"
                  :style="{ width: `${m.size}px`, height: `${m.size}px`, animationDuration: m.dur, animationDelay: m.delay }" />
        </span>
    </div>
</template>

<style scoped>
.pixel-dust {
    position: absolute;
    inset: 0 auto 0 50%;
    width: 100vw;
    transform: translateX(-50%);
    overflow: hidden;
    pointer-events: none;
    z-index: -1;
    /* Fondu en haut et en bas pour ne pas marquer de bord. */
    -webkit-mask: linear-gradient(transparent, #000 12%, #000 80%, transparent);
    mask: linear-gradient(transparent, #000 12%, #000 80%, transparent);
}
.pixel-dust--fixed {
    position: fixed;
    inset: 0;
    width: auto;
    transform: none;
    -webkit-mask: linear-gradient(transparent, #000 15%, #000 85%, transparent);
    mask: linear-gradient(transparent, #000 15%, #000 85%, transparent);
}
.dust-layer {
    position: absolute;
    transform: translate3d(0, calc(var(--hero-y, 0) * var(--p) * -1px), 0);
    will-change: transform;
}
/* Grain de lumière : monte par crans en scintillant. */
.mote {
    display: block;
    background: #ccfbf1;
    box-shadow: 0 0 6px rgb(45 212 191 / 0.9);
    opacity: 0;
    animation: mote-rise steps(24) infinite;
}
.mote--warm {
    background: #fef3c7;
    box-shadow: 0 0 6px rgb(251 191 36 / 0.85);
}
@keyframes mote-rise {
    0%   { transform: translate3d(0, 0, 0); opacity: 0; }
    15%  { opacity: 0.8; }
    50%  { opacity: 0.35; }
    70%  { opacity: 0.7; }
    100% { transform: translate3d(8px, -140px, 0); opacity: 0; }
}

@media (prefers-reduced-motion: reduce) {
    .mote {
        animation: none;
        opacity: 0.4;
    }
}
</style>
