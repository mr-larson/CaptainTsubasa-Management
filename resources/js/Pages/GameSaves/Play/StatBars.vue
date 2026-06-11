<script setup>
import { computed } from 'vue';

/**
 * Carte "Statistiques" : barres horizontales, liste adaptée GK / joueur de champ.
 * `comparePlayer` affiche une barre fantôme et la valeur comparée (ex : transferts).
 */
const props = defineProps({
    player:        { type: Object, default: null },
    comparePlayer: { type: Object, default: null },
    compareLabel:  { type: String, default: null },
});

const GK_STATS = [
    { label: 'Vitesse',  key: 'speed',      color: 'bg-sky-400' },
    { label: 'Stamina',  key: 'stamina',    color: 'bg-emerald-400' },
    { label: 'Défense',  key: 'defense',    color: 'bg-blue-400' },
    { label: 'Arrêt ✋', key: 'hand_save',  color: 'bg-violet-400' },
    { label: 'Arrêt 👊', key: 'punch_save', color: 'bg-fuchsia-400' },
    { label: 'Attaque',  key: 'attack',     color: 'bg-orange-400' },
    { label: 'Block',    key: 'block',      color: 'bg-indigo-400' },
];

const FIELD_STATS = [
    { label: 'Vitesse', key: 'speed',     color: 'bg-sky-400' },
    { label: 'Stamina', key: 'stamina',   color: 'bg-emerald-400' },
    { label: 'Attaque', key: 'attack',    color: 'bg-orange-400' },
    { label: 'Défense', key: 'defense',   color: 'bg-blue-400' },
    { label: 'Tir',     key: 'shot',      color: 'bg-red-400' },
    { label: 'Passe',   key: 'pass',      color: 'bg-teal-400' },
    { label: 'Dribble', key: 'dribble',   color: 'bg-yellow-400' },
    { label: 'Block',   key: 'block',     color: 'bg-indigo-400' },
    { label: 'Interc.', key: 'intercept', color: 'bg-purple-400' },
    { label: 'Tacle',   key: 'tackle',    color: 'bg-pink-400' },
    { label: 'Tête',    key: 'heading',   color: 'bg-cyan-400' },
];

const stats = computed(() =>
    props.player?.position?.toLowerCase().includes('goalkeeper') ? GK_STATS : FIELD_STATS
);

const valueOf = (key) => props.player?.[key] ?? props.player?.stats?.[key] ?? null;
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Statistiques</h4>
        <div class="space-y-1.5">
            <div v-for="stat in stats" :key="stat.key" class="flex items-center gap-2 text-xs">
                <span class="w-16 text-slate-500 shrink-0 text-[11px]">{{ stat.label }}</span>
                <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden relative">
                    <div v-if="comparePlayer"
                         class="absolute h-full rounded-full opacity-30 bg-slate-500"
                         :style="{ width: Math.min(comparePlayer[stat.key] ?? comparePlayer.stats?.[stat.key] ?? 0, 100) + '%' }">
                    </div>
                    <div class="h-full rounded-full" :class="stat.color"
                         :style="{ width: Math.min(valueOf(stat.key) ?? 0, 100) + '%' }">
                    </div>
                </div>
                <span class="w-6 text-right font-bold text-slate-700 text-[11px]">
                    {{ valueOf(stat.key) ?? '—' }}
                </span>
                <span v-if="comparePlayer" class="w-5 text-right text-[10px] text-slate-400">
                    {{ comparePlayer[stat.key] ?? comparePlayer.stats?.[stat.key] ?? '—' }}
                </span>
            </div>
        </div>
        <p v-if="comparePlayer && compareLabel" class="text-[9px] text-slate-400 mt-2">
            Comparé à {{ compareLabel }}
        </p>
    </div>
</template>
