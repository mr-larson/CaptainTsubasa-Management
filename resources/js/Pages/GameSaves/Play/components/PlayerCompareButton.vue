<script setup>
import { computed } from 'vue';

const props = defineProps({
    player: { type: Object, required: true },
    modelValue: { type: Array, required: true },
});
const emit = defineEmits(['update:modelValue']);
const selected = computed(() => props.modelValue.some(player => player.id === props.player.id));
const label = computed(() => selected.value ? 'Retirer du comparatif' : 'Ajouter au comparatif');

function toggle() {
    emit('update:modelValue', selected.value
        ? props.modelValue.filter(player => player.id !== props.player.id)
        : [...props.modelValue.slice(-1), props.player]);
}
</script>

<template>
    <button type="button"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border bg-white hover:border-teal-500 focus-visible:ring-2 focus-visible:ring-teal-500"
            :class="selected ? 'border-teal-500 text-teal-700 ring-1 ring-teal-500' : 'border-slate-300 text-slate-500'"
            :aria-pressed="selected"
            :aria-label="`${label} · ${player.firstname} ${player.lastname}`"
            :title="label"
            @click.stop="toggle">
        <span aria-hidden="true">⚖️</span>
    </button>
</template>
