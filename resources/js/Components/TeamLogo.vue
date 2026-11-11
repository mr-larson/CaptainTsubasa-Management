<script setup>
import { computed } from 'vue';

/**
 * Logo d'équipe encadré (initiale si pas de logo). Tous les écussons passent
 * par ici : une seule échelle de tailles et de formes.
 */
const props = defineProps({
    team: { type: Object, default: null },
    size: { type: String, default: 'sm' },        // xs | sm | md | lg | xl | 2xl
    shape: { type: String, default: 'circle' },   // circle | rounded
    fallback: { type: String, default: 'initial' }, // initial | none | ball | stadium
    bordered: { type: Boolean, default: true },
});

const SIZES = {
    xs:   { box: 'h-4 w-4',   text: 'text-3xs' },
    sm:   { box: 'h-6 w-6',   text: 'text-3xs' },
    md:   { box: 'h-8 w-8',   text: 'text-xs' },
    lg:   { box: 'h-10 w-10', text: 'text-sm' },
    xl:   { box: 'h-12 w-12', text: 'text-xl' },
    '2xl': { box: 'h-20 w-20', text: 'text-2xl' },
};

const logoUrl = computed(() => {
    const path = props.team?.logo_path ?? props.team?.team?.logo_path;
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/')) return path;
    return `/storage/${path}`;
});
const sizeClasses = computed(() => SIZES[props.size] ?? SIZES.sm);
const fallbackText = computed(() => {
    if (props.fallback === 'ball') return '⚽';
    if (props.fallback === 'stadium') return '🏟️';
    if (props.fallback === 'initial') return (props.team?.name || '?').charAt(0);
    return '';
});
</script>

<template>
    <div class="flex shrink-0 items-center justify-center overflow-hidden bg-white"
         :class="[sizeClasses.box, shape === 'circle' ? 'rounded-full' : 'rounded-md', bordered ? 'border border-slate-200' : '']">
        <TeamLogoImage v-if="logoUrl" loading="lazy" decoding="async" :src="logoUrl" alt="" class="h-full w-full" />
        <span v-else-if="fallbackText" aria-hidden="true" class="font-bold text-slate-400" :class="sizeClasses.text">{{ fallbackText }}</span>
    </div>
</template>
