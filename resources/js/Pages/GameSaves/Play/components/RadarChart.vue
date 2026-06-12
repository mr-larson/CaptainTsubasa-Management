<script setup>
import { computed } from 'vue';

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
    teal: { fill: 'rgba(20,184,166,0.2)',  stroke: '#14b8a6' },
    red:  { fill: 'rgba(239,68,68,0.15)',  stroke: '#ef4444' },
};

const accentColors = computed(() => ACCENTS[props.accent] ?? ACCENTS.teal);

const RADAR_STATS = [
    { key: 'shot',    label: 'Tir'     },
    { key: 'pass',    label: 'Passe'   },
    { key: 'dribble', label: 'Dribble' },
    { key: 'defense', label: 'Défense' },
    { key: 'speed',   label: 'Vitesse' },
    { key: 'stamina', label: 'Stamina' },
];

const CX = 90, CY = 90, R = 68;

const pointsFor = (p) => {
    if (!p) return [];
    return RADAR_STATS.map((s, i) => {
        const val   = Math.min(Number(p[s.key] ?? p.stats?.[s.key] ?? 0) / 100, 1);
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return {
            x:  CX + R * val * Math.cos(angle),
            y:  CY + R * val * Math.sin(angle),
            lx: CX + (R + 16) * Math.cos(angle),
            ly: CY + (R + 16) * Math.sin(angle),
            label: s.label,
        };
    });
};

const radarPoints  = computed(() => pointsFor(props.player));
const radarPolygon = computed(() => radarPoints.value.map(p => `${p.x},${p.y}`).join(' '));

const comparePolygon = computed(() =>
    props.comparePlayer ? pointsFor(props.comparePlayer).map(p => `${p.x},${p.y}`).join(' ') : null
);

const radarGrids = computed(() =>
    [0.25, 0.5, 0.75, 1.0].map(scale =>
        RADAR_STATS.map((_, i) => {
            const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
            return `${CX + R * scale * Math.cos(angle)},${CY + R * scale * Math.sin(angle)}`;
        }).join(' ')
    )
);

const radarAxes = computed(() =>
    RADAR_STATS.map((_, i) => {
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return { x2: CX + R * Math.cos(angle), y2: CY + R * Math.sin(angle) };
    })
);
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-col items-center">
        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 self-start">Profil technique</h4>
        <svg viewBox="0 0 180 180" class="w-40 h-40">
            <polygon v-if="comparePolygon" :points="comparePolygon"
                     fill="rgba(148,163,184,0.15)" stroke="#94a3b8" stroke-width="1" stroke-dasharray="3,2"/>
            <polygon v-for="(pts,i) in radarGrids" :key="i" :points="pts" fill="none" stroke="#e2e8f0" stroke-width="0.8"/>
            <line v-for="(ax,i) in radarAxes" :key="'a'+i" x1="90" y1="90" :x2="ax.x2" :y2="ax.y2" stroke="#e2e8f0" stroke-width="0.8"/>
            <polygon :points="radarPolygon" :fill="accentColors.fill" :stroke="accentColors.stroke" stroke-width="1.5" stroke-linejoin="round"/>
            <circle v-for="(pt,i) in radarPoints" :key="'p'+i" :cx="pt.x" :cy="pt.y" r="2.5" :fill="accentColors.stroke" stroke="white" stroke-width="1"/>
            <text v-for="(pt,i) in radarPoints" :key="'t'+i" :x="pt.lx" :y="pt.ly" text-anchor="middle" dominant-baseline="middle" font-size="8" fill="#94a3b8" font-weight="600">{{ pt.label }}</text>
        </svg>
        <p v-if="comparePlayer && compareLabel" class="text-[9px] text-slate-400 mt-1">
            <span class="inline-block w-3 h-0.5 bg-slate-300 mr-1 align-middle"></span>
            {{ compareLabel }}
        </p>
    </div>
</template>
