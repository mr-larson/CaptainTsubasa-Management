<script setup>
import { useTeamStyles } from '../useTeamStyles.js';

const props = defineProps({
    team: { type: Object, default: null },
    size: { type: String, default: 'md' }, // 'sm' | 'md' | 'lg'
});

const {
    tacticalLabel, tacticalIcon, tacticalColor,
    philosophyLabel, philosophyIcon, philosophyColor,
} = useTeamStyles();

const sizeClass = {
    sm: 'px-2 py-0.5 text-[10px]',
    md: 'px-2.5 py-1 text-xs',
    lg: 'px-3 py-1.5 text-sm',
}[props.size] ?? 'px-2.5 py-1 text-xs';
</script>

<template>
    <div v-if="team" class="flex items-center gap-1.5 flex-wrap">
        <span :class="[sizeClass, tacticalColor(team.tactical_style)]"
              class="rounded-full font-semibold border inline-flex items-center gap-1"
              :title="`Style tactique : ${tacticalLabel(team.tactical_style)}`">
            <span>{{ tacticalIcon(team.tactical_style) }}</span>
            <span>{{ tacticalLabel(team.tactical_style) }}</span>
        </span>
        <span :class="[sizeClass, philosophyColor(team.management_philosophy)]"
              class="rounded-full font-semibold border inline-flex items-center gap-1"
              :title="`Philosophie : ${philosophyLabel(team.management_philosophy)}`">
            <span>{{ philosophyIcon(team.management_philosophy) }}</span>
            <span>{{ philosophyLabel(team.management_philosophy) }}</span>
        </span>
    </div>
</template>
