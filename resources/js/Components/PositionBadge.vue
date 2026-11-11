<script setup>
import { computed } from 'vue';
import { POSITION_GROUPS, positionBadgeClass, positionGroup, positionGroupClass, positionLabel, positionShort } from '@/Enums/positionBadges.js';

/**
 * Pastille de poste colorée (couleurs de Enums/positionBadges.js).
 * `position` accepte un poste complet (Goalkeeper) ou une famille (GK, DEF…).
 */
const props = defineProps({
    position: { type: String, default: null },
    label: { type: String, default: 'group' },   // group (GK/DEF) | short (GB/DEF) | full (Gardien)
    size: { type: String, default: 'xs' },       // xs | sm
});

const groupKey = computed(() => POSITION_GROUPS.some(g => g.key === props.position) ? props.position : positionGroup(props.position));
const colorClass = computed(() => POSITION_GROUPS.some(g => g.key === props.position)
    ? positionGroupClass(props.position)
    : positionBadgeClass(props.position));
const text = computed(() => {
    if (props.label === 'full') return positionLabel(props.position);
    if (props.label === 'short') return positionShort(props.position);
    return groupKey.value === 'OTHER' ? '?' : groupKey.value;
});
</script>

<template>
    <span class="inline-flex shrink-0 items-center gap-1 rounded px-1.5 py-0.5 font-bold leading-none"
          :class="[colorClass, size === 'sm' ? 'text-2xs' : 'text-3xs']"
          :title="positionLabel(position)">
        <span>{{ text }}</span><span v-if="$slots.default"><slot /></span>
    </span>
</template>
