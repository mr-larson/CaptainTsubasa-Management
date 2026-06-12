<script setup>
import { computed } from 'vue';

/** Carte "Performance saison" : chips d'actions du joueur (GK / champ). */
const props = defineProps({
    player: { type: Object, default: null },
    // Stats agrégées de la saison pour ce joueur (PlayerStatsService), ou null
    perf:   { type: Object, default: null },
});

const perfChips = computed(() => {
    const p    = props.player;
    const perf = props.perf;
    if (!p || !perf) return [];
    const isGK = p.position?.toLowerCase().includes('goalkeeper');

    if (isGK) return [
        { icon: '🧤', label: 'Arrêts',  val: perf.defense?.hands?.attempts     ?? 0, sub: perf.defense?.hands?.success     ?? 0, color: 'bg-violet-100 text-violet-700' },
        { icon: '👊', label: 'Poings',  val: perf.defense?.gkSpecial?.attempts ?? 0, sub: perf.defense?.gkSpecial?.success ?? 0, color: 'bg-fuchsia-100 text-fuchsia-700' },
        { icon: '🧱', label: 'Blocks',  val: perf.defense?.block?.attempts     ?? 0, sub: perf.defense?.block?.success     ?? 0, color: 'bg-slate-100 text-slate-600' },
        { icon: '🎯', label: 'Passes',  val: perf.offense?.pass?.attempts      ?? 0, sub: perf.offense?.pass?.success      ?? 0, color: 'bg-sky-100 text-sky-700' },
        { icon: '⚔️', label: 'Gagnés',  val: perf.duelsWon  ?? 0, sub: null, color: 'bg-teal-100 text-teal-700' },
        { icon: '💔', label: 'Perdus',  val: perf.duelsLost ?? 0, sub: null, color: 'bg-rose-100 text-rose-700' },
    ];

    return [
        { icon: '⚽', label: 'Buts',     val: perf.offense?.goals               ?? 0, sub: null,                                  color: 'bg-emerald-100 text-emerald-700' },
        { icon: '🎯', label: 'Tirs',     val: perf.offense?.shot?.attempts      ?? 0, sub: perf.offense?.shot?.success      ?? 0, color: 'bg-blue-100 text-blue-700' },
        { icon: '🎁', label: 'Passes',   val: perf.offense?.pass?.attempts      ?? 0, sub: perf.offense?.pass?.success      ?? 0, color: 'bg-sky-100 text-sky-700' },
        { icon: '🔥', label: 'Dribbles', val: perf.offense?.dribble?.attempts   ?? 0, sub: perf.offense?.dribble?.success   ?? 0, color: 'bg-orange-100 text-orange-700' },
        { icon: '🛡️', label: 'Interc.',  val: perf.defense?.intercept?.attempts ?? 0, sub: perf.defense?.intercept?.success ?? 0, color: 'bg-emerald-100 text-emerald-700' },
        { icon: '⚡', label: 'Tacles',   val: perf.defense?.tackle?.attempts    ?? 0, sub: perf.defense?.tackle?.success    ?? 0, color: 'bg-yellow-100 text-yellow-700' },
        { icon: '🧱', label: 'Blocks',   val: perf.defense?.block?.attempts     ?? 0, sub: perf.defense?.block?.success     ?? 0, color: 'bg-slate-100 text-slate-600' },
        { icon: '⚔️', label: 'Gagnés',   val: perf.duelsWon  ?? 0, sub: null, color: 'bg-teal-100 text-teal-700' },
        { icon: '💔', label: 'Perdus',   val: perf.duelsLost ?? 0, sub: null, color: 'bg-rose-100 text-rose-700' },
    ];
});

// Aucun match joué : toutes les valeurs à zéro
const hasNoPerf = computed(() => perfChips.value.every(c => c.val === 0));
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Performance saison</h4>
        <div v-if="perfChips.length" class="flex flex-wrap gap-2">
            <div v-for="chip in perfChips" :key="chip.label"
                 class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
                 :class="[chip.color, hasNoPerf ? 'opacity-40' : '']">
                <span>{{ chip.icon }}</span>
                <span>{{ chip.label }}</span>
                <span class="font-black">{{ chip.val }}</span>
                <span v-if="chip.sub !== null" class="opacity-50 text-[10px]">/ {{ chip.sub }} ✓</span>
            </div>
        </div>
        <p v-if="!perfChips.length" class="text-xs text-slate-400 italic">
            Aucune donnée de performance pour ce joueur.
        </p>
        <p v-else-if="hasNoPerf" class="mt-2 text-[10px] text-slate-400 italic">
            Aucun match joué pour le moment cette saison.
        </p>
    </div>
</template>
