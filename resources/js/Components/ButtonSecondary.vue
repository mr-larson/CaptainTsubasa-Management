<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="mb-2 inline-flex min-h-[40px] min-w-[9rem] items-center justify-center rounded-full border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:ring-offset-2 disabled:opacity-50"
    >
        <slot />
    </button>
</template>
