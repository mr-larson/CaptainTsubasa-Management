<script setup>
/**
 * Pastille arrondie (statut, étiquette, compteur). Une teinte et une taille
 * nommées remplacent les chaînes de classes recopiées partout ; les cas
 * dynamiques (couleur calculée) gardent leurs propres classes.
 */
defineProps({
    tone:   { type: String, default: 'slate' },    // voir TONES
    size:   { type: String, default: 'sm' },       // xs (3xs) | sm (2xs)
    weight: { type: String, default: 'bold' },     // semibold | bold | black
});

const TONES = {
    slate:           'bg-slate-100 text-slate-600',
    'slate-muted':   'bg-slate-100 text-slate-500',
    teal:            'bg-teal-50 text-teal-700',
    'teal-strong':   'bg-teal-100 text-teal-700',
    'solid-teal':    'bg-teal-500 text-white',
    'solid-rose':    'bg-rose-500 text-white',
    'solid-indigo':  'bg-indigo-500 text-white',
    white:           'bg-white border border-slate-200 text-slate-600',
    outline:         'bg-white/70 border border-slate-200 text-slate-600',
    'outline-muted': 'bg-white/80 border border-slate-200 text-slate-500',
};
const SIZES = { xs: 'text-3xs px-1.5 py-0.5', sm: 'text-2xs px-2 py-0.5' };
const WEIGHTS = { semibold: 'font-semibold', bold: 'font-bold', black: 'font-black' };
</script>

<template>
    <span class="rounded-full" :class="[TONES[tone] ?? TONES.slate, SIZES[size] ?? SIZES.sm, WEIGHTS[weight] ?? WEIGHTS.bold]">
        <slot />
    </span>
</template>
