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
        class="items-center w-36 px-4 py-2 bg-stone-100 border-2 border-slate-600 rounded-full font-semibold text-xs text-slate-600 hover:text-white uppercase tracking-widest hover:bg-slate-400 focus:bg-slate-600 active:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition ease-in-out duration-200 text-center drop-shadow-md mb-2"
    >
        <slot />
    </button>
</template>
