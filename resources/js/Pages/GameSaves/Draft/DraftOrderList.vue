<script setup>
// Ordre de passage du tour de draft en cours.
defineProps({
    teams:       { type: Array,    default: () => [] }, // { id, name, rosterCount, isCurrent, logo_path }
    round:       { type: Number,   default: 1 },
    isHumanTeam: { type: Function, required: true },
    teamLogoUrl: { type: Function, required: true },
});
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-white p-3 flex-1 max-h-[300px] overflow-y-auto">
        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Ordre — Tour {{ round }}</h4>
        <div class="space-y-1">
            <div v-for="t in teams" :key="t.id"
                 class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs transition-all"
                 :class="t.isCurrent ? 'bg-amber-100 border border-amber-300 font-bold' : 'bg-slate-50'">
                <div class="w-5 h-5 rounded overflow-hidden bg-white shrink-0">
                    <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" loading="lazy" decoding="async" class="w-full h-full object-contain" alt=""/>
                </div>
                <span class="flex-1 truncate" :class="isHumanTeam(t.id) ? 'text-amber-700 font-semibold' : 'text-slate-600'">
                    {{ t.name }}
                </span>
                <span class="text-[10px] text-slate-400">{{ t.rosterCount }}</span>
            </div>
        </div>
    </div>
</template>
