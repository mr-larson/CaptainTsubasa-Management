<script setup>
import { computed } from 'vue';
import PlayerPhoto from './PlayerPhoto.vue';
import { usePlayerUtils } from '@/Pages/GameSaves/Play/usePlayerUtils.js';

/**
 * Photo de joueur encadrée, avec repli et drapeau de nationalité optionnel.
 * Toutes les miniatures de joueur passent par ici.
 */
const props = defineProps({
    player: { type: Object, default: null },
    size: { type: String, default: 'sm' },        // xs | sm | md | lg
    shape: { type: String, default: 'circle' },   // circle | rounded
    showFlag: { type: Boolean, default: false },
    zoomable: { type: Boolean, default: true },
});

const { playerPhotoUrl, nationalityFlag } = usePlayerUtils();

const SIZES = {
    xs: { box: 'h-6 w-6',   text: 'text-3xs', flag: 'h-3.5 w-3.5 text-3xs' },
    sm: { box: 'h-8 w-8',   text: 'text-xs',  flag: 'h-4 w-4 text-2xs' },
    md: { box: 'h-10 w-10', text: 'text-sm',  flag: 'h-4 w-4 text-2xs' },
    lg: { box: 'h-20 w-20', text: 'text-3xl', flag: 'h-6 w-6 text-[13px]' },
};

const sizeClasses = computed(() => SIZES[props.size] ?? SIZES.sm);
const photo = computed(() => playerPhotoUrl(props.player));
const flag = computed(() => props.showFlag ? nationalityFlag(props.player) : null);
const name = computed(() => [props.player?.firstname, props.player?.lastname].filter(Boolean).join(' '));
</script>

<template>
    <div class="relative shrink-0">
        <div class="flex items-center justify-center overflow-hidden bg-slate-100 text-slate-400 shadow-sm"
             :class="[sizeClasses.box, shape === 'circle' ? 'rounded-full' : 'rounded-xl']">
            <PlayerPhoto v-if="photo && zoomable" :src="photo" class="h-full w-full object-cover" :alt="name" />
            <img v-else-if="photo" :src="photo" class="h-full w-full object-cover" :alt="name" loading="lazy" decoding="async" />
            <span v-else aria-hidden="true" :class="sizeClasses.text">{{ size === 'lg' ? '👤' : '?' }}</span>
        </div>
        <span v-if="flag" :title="player?.nationality ?? player?.player?.nationality"
              class="absolute -bottom-0.5 -right-0.5 flex items-center justify-center rounded-full border border-slate-200 bg-white leading-none shadow-sm"
              :class="sizeClasses.flag">
            {{ flag }}
        </span>
        <slot />
    </div>
</template>
