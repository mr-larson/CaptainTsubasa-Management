<script setup>
import { computed } from 'vue';
import { POSITION_GROUPS, countByPositionGroup } from '@/Enums/positionBadges.js';
import PositionBadge from './PositionBadge.vue';

// Compteurs « GK 2 · DEF 5 · MID 5 · ATT 3 » d'un effectif.
const props = defineProps({
    players: { type: Array, default: null },
    counts: { type: Object, default: null },
    size: { type: String, default: 'xs' },
});

const values = computed(() => props.counts ?? countByPositionGroup(props.players ?? []));
</script>

<template>
    <div class="flex flex-wrap gap-1">
        <PositionBadge v-for="group in POSITION_GROUPS" :key="group.key" :position="group.key" :size="size">
            {{ values[group.key] ?? 0 }}
        </PositionBadge>
    </div>
</template>
