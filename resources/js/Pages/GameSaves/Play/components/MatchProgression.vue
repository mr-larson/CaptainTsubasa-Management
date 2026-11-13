<script setup>
import { computed } from 'vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';

// Joueurs ayant progressé grâce à un match, par équipe (domicile / extérieur).
const props = defineProps({
    progression:  { type: Array,  default: () => [] }, // match_stats.progression
    homeTeamName: { type: String, default: '' },
    awayTeamName: { type: String, default: '' },
});

const STAT_LABELS = {
    shot:    { icon: '🎯', label: 'Tir',     color: 'text-rose-700    bg-rose-100' },
    pass:    { icon: '🎽', label: 'Passe',   color: 'text-emerald-700 bg-emerald-100' },
    dribble: { icon: '🔥', label: 'Dribble', color: 'text-amber-700   bg-amber-100' },
    defense: { icon: '🛡️', label: 'Défense', color: 'text-blue-700    bg-blue-100' },
};

const bySide = (side) => props.progression.filter(p => p.team_side === side).sort((a, b) => b.total - a.total);
const sides = computed(() => [
    { key: 'home', name: props.homeTeamName, players: bySide('home') },
    { key: 'away', name: props.awayTeamName, players: bySide('away') },
]);
</script>

<template>
    <div v-if="progression.length" class="border border-slate-300 rounded-xl bg-slate-50 p-4">
        <h4 class="text-2xs font-bold text-slate-500 uppercase tracking-wider mb-3">📈 Progression suite à ce match</h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="side in sides" :key="side.key">
                <div class="text-xs font-bold text-slate-600 mb-2 pb-1.5 border-b border-slate-300">
                    {{ side.name }}
                    <span class="text-2xs text-slate-400 font-normal ml-1">
                        ({{ side.players.length }} joueur{{ side.players.length > 1 ? 's' : '' }})
                    </span>
                </div>
                <div v-if="!side.players.length" class="text-[11px] text-slate-400 italic py-2">Aucune progression</div>
                <div v-else class="space-y-1.5">
                    <div v-for="p in side.players" :key="p.player_id" class="flex items-center gap-2 p-2 bg-white/80 rounded-lg">
                        <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-200 shrink-0">
                            <PlayerPhoto :src="p.photo_path ? `/storage/${p.photo_path}` : null" class="w-full h-full object-cover" alt="" />
                            <div v-if="!p.photo_path" class="w-full h-full flex items-center justify-center text-2xs text-slate-400">?</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold text-slate-700 truncate">{{ p.lastname }}</div>
                            <div class="flex flex-wrap gap-1 mt-0.5">
                                <span v-for="(detail, stat) in p.gains" :key="stat" :class="STAT_LABELS[stat]?.color"
                                      class="px-1.5 py-0.5 rounded text-3xs font-bold inline-flex items-center gap-0.5"
                                      :title="STAT_LABELS[stat]?.label">
                                    <span>{{ STAT_LABELS[stat]?.icon }}</span>
                                    <span>+{{ detail.gain }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="text-right shrink-0 px-1">
                            <div class="text-lg font-black text-emerald-600 leading-none">+{{ p.total }}</div>
                            <div class="text-[8px] text-slate-400 uppercase">pts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
