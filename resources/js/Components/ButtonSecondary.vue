<script setup>
import { computed } from 'vue';
import { BUTTON_BASE, buttonSize } from './buttonStyles';

const props = defineProps({
    type: { type: String, default: 'button' },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const classes = computed(() => [
    BUTTON_BASE,
    buttonSize(props.size),
    'border border-slate-300 bg-white font-semibold text-slate-600 hover:bg-slate-50 focus-visible:ring-slate-300',
]);
</script>

<template>
    <button :type="type" :class="classes">
        <slot />
    </button>
</template>
