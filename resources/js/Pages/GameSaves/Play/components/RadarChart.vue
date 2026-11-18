<script setup>
import SectionTitle from '@/Components/SectionTitle.vue';
import { computed } from 'vue';
import {
    polygonOf,
    radarAxes as buildRadarAxes,
    radarGrids as buildRadarGrids,
    radarPoints as buildRadarPoints,
} from '../radarGeometry.js';

/**
 * Carte "Profil technique" : radar à 6 axes du joueur.
 * `comparePlayer` superpose un polygone fantôme (ex : meilleur joueur au même poste).
 */
const props = defineProps({
    player: { type: Object, default: null },
    // Couleur du polygone : teal (équipe contrôlée) ou red (équipes adverses)
    accent: { type: String, default: 'teal' },
    comparePlayer: { type: Object, default: null },
    compareLabel:  { type: String, default: null },
});

const ACCENTS = {
    teal: { polygon: 'fill-teal-500/20 stroke-teal-500', dot: 'fill-teal-500' },
    red:  { polygon: 'fill-rose-500/15 stroke-rose-500', dot: 'fill-rose-500' },
};

const accentColors = computed(() => ACCENTS[props.accent] ?? ACCENTS.teal);

const SIZE = { cx: 90, cy: 90, r: 68, labelOffset: 16 };

const pointsFor = (p) => (p ? buildRadarPoints((key) => Number(p[key] ?? p.stats?.[key] ?? 0), SIZE) : []);

const radarPoints  = computed(() => pointsFor(props.player));
const radarPolygon = computed(() => polygonOf(radarPoints.value));

const comparePolygon = computed(() =>
    props.comparePlayer ? polygonOf(pointsFor(props.comparePlayer)) : null
);

const radarGrids = computed(() => buildRadarGrids(SIZE));
const radarAxes  = computed(() => buildRadarAxes(SIZE));
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-col items-center shadow-card">
        <SectionTitle class="mb-1 self-start">Profil technique</SectionTitle>
        <svg viewBox="0 0 180 180" class="w-40 h-40">
            <polygon class="stroke-slate-400 fill-slate-400/15" v-if="comparePolygon" :points="comparePolygon"
                     stroke-width="1" stroke-dasharray="3,2"/>
            <polygon class="stroke-slate-200" v-for="(pts,i) in radarGrids" :key="i" :points="pts" fill="none" stroke-width="0.8"/>
            <line class="stroke-slate-200" v-for="(ax,i) in radarAxes" :key="'a'+i" x1="90" y1="90" :x2="ax.x2" :y2="ax.y2" stroke-width="0.8"/>
            <polygon :points="radarPolygon" :class="accentColors.polygon" stroke-width="1.5" stroke-linejoin="round"/>
            <circle class="stroke-white" v-for="(pt,i) in radarPoints" :key="'p'+i" :cx="pt.x" :cy="pt.y" r="2.5" :class="accentColors.dot" stroke-width="1"/>
            <text class="fill-slate-400" v-for="(pt,i) in radarPoints" :key="'t'+i" :x="pt.lx" :y="pt.ly" text-anchor="middle" dominant-baseline="middle" font-size="8" font-weight="600">{{ pt.label }}</text>
        </svg>
        <p v-if="comparePlayer && compareLabel" class="text-3xs text-slate-400 mt-1">
            <span class="inline-block w-3 h-0.5 bg-slate-300 mr-1 align-middle"></span>
            {{ compareLabel }}
        </p>
    </div>
</template>
