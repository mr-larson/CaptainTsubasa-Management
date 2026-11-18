<script setup>
import { inject } from 'vue';

/**
 * Case à cocher de réglage avec libellé et aide optionnelle (slot `hint`).
 * Grisée quand `disabled`.
 */
const model = defineModel({ type: Boolean, default: false });

defineProps({
    disabled: { type: Boolean, default: false },
});

const compact = inject('configCompact', false);
</script>

<template>
    <label class="flex gap-2 cursor-pointer" :class="[
        $slots.hint ? 'items-start' : 'items-center',
        compact ? 'text-xs' : 'text-sm',
        disabled ? 'text-slate-400' : 'text-slate-700',
    ]">
        <input type="checkbox" v-model="model" :disabled="disabled"
               class="rounded border-slate-300 text-teal-500 focus:ring-teal-400 disabled:opacity-40"
               :class="{ 'mt-0.5': $slots.hint }" />
        <span>
            <slot />
            <span v-if="$slots.hint" class="block text-slate-400" :class="compact ? 'text-2xs' : 'text-xs'"><slot name="hint" /></span>
        </span>
    </label>
</template>
