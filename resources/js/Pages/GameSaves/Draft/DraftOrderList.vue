<script setup>
import SectionTitle from '@/Components/SectionTitle.vue';
import TeamLogo from '@/Components/TeamLogo.vue';

// Ordre de passage du tour de draft en cours.
defineProps({
    teams:       { type: Array,    default: () => [] }, // { id, name, rosterCount, isCurrent, logo_path }
    round:       { type: Number,   default: 1 },
    isHumanTeam: { type: Function, required: true },
});
const emit = defineEmits(['inspect-team']);
</script>

<template>
    <div class="flex max-h-[300px] flex-1 flex-col rounded-xl border border-slate-300 bg-slate-50 p-3 xl:min-h-[300px] xl:max-h-none">
        <SectionTitle class="mb-2">Ordre — Tour {{ round }}</SectionTitle>
        <div class="relative min-h-0 flex-1 overflow-y-auto xl:overflow-hidden">
            <div class="space-y-1 xl:absolute xl:inset-0 xl:overflow-y-auto">
                <button v-for="t in teams" :key="t.id" type="button"
                        class="flex w-full items-center gap-2 rounded-lg border px-2 py-1.5 text-left text-xs transition-colors hover:border-teal-300 focus-visible:ring-2 focus-visible:ring-teal-500"
                        :class="t.isCurrent ? 'border-teal-300 bg-teal-100 font-bold' : 'border-transparent bg-white'"
                        :aria-label="`Voir les joueurs piochés par ${t.name}`"
                        @click="emit('inspect-team', t.id)">
                    <TeamLogo :team="t" size="sm" shape="rounded" fallback="none" :bordered="false" />
                    <span class="flex-1 truncate" :class="isHumanTeam(t.id) ? 'text-teal-700 font-semibold' : 'text-slate-600'">
                        {{ t.name }}
                    </span>
                    <span class="text-2xs text-slate-400">{{ t.rosterCount }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
