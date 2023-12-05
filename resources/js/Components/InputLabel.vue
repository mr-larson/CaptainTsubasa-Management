<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="text-slate-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">
        <span v-if="value">{{ value.charAt(0).toUpperCase() + value.slice(1) }}</span>
        <span v-else><slot /></span>
    </label>
</template>
