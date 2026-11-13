<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

/**
 * Carte de contenu standard : bordure arrondie, fond, marge interne et titre
 * optionnel. Toutes les cartes des écrans de jeu passent par ici pour garder
 * le même rendu partout.
 */
const props = defineProps({
    title: { type: String, default: null },
    tone: { type: String, default: 'muted' },     // muted | white | amber
    padding: { type: String, default: 'md' },     // sm | md
    as: { type: String, default: 'section' },
});

const slots = useSlots();

const TONES = {
    muted: 'border-slate-200 bg-slate-50',
    white: 'border-slate-200 bg-white',
    amber: 'border-amber-200 bg-amber-50',
};
const PADDINGS = { sm: 'p-3', md: 'p-4' };

const classes = computed(() => ['rounded-xl border', TONES[props.tone] ?? TONES.muted, PADDINGS[props.padding] ?? PADDINGS.md]);
const hasHeader = computed(() => !!props.title || !!slots.title || !!slots['header-actions']);
</script>

<template>
    <component :is="as" :class="classes">
        <div v-if="hasHeader" class="flex items-center justify-between gap-2" :class="padding === 'sm' ? 'mb-2' : 'mb-3'">
            <SectionTitle>
                <slot name="title">{{ title }}</slot>
            </SectionTitle>
            <div v-if="$slots['header-actions']" class="flex shrink-0 items-center gap-2 text-2xs text-slate-400">
                <slot name="header-actions" />
            </div>
        </div>
        <slot />
    </component>
</template>
