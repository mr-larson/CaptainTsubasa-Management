<script setup>
import { computed } from 'vue';
import { BUTTON_BASE, buttonSize } from './buttonStyles';

// Action destructive secondaire (contour rose). Pour une confirmation pleine : ButtonPrimary tone="rose".
const props = defineProps({
    type: { type: String, default: 'button' },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const classes = computed(() => [
    BUTTON_BASE,
    buttonSize(props.size),
    'border border-rose-300 bg-white font-semibold text-rose-600 hover:bg-rose-50 focus-visible:ring-rose-300',
]);
</script>

<template>
    <button :type="type" :class="classes">
        <slot />
    </button>
</template>
