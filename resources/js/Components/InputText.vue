<script setup>
import { onMounted, ref } from 'vue';
import { fieldClass } from './fieldStyles';

defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    // md : formulaire (pilule) ; sm : contrôle compact des écrans de jeu (rectangle, text-xs).
    size: { type: String, default: 'md' },
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :class="['appearance-none w-full', size === 'sm'
            ? ['px-2.5 py-1.5', fieldClass({ shape: 'rect', size: 'sm' })]
            : fieldClass()]"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
    />
</template>
