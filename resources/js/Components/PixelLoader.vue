<script setup>
defineProps({
    size: { type: String, default: 'md' },
    label: { type: String, default: 'Chargement…' },
});
const sizes = { sm: '16px', md: '24px', lg: '36px' };
const delay = index => {
    const x = (index - 1) % 4;
    const y = Math.floor((index - 1) / 4);
    return `${-(x + y) * 110}ms`;
};
</script>

<template>
    <span class="pixel-loader" :style="{ '--loader-size': sizes[size] ?? sizes.md }" role="status" :aria-label="label">
        <span v-for="pixel in 16" :key="pixel" class="pixel-loader__dot" :style="{ animationDelay: delay(pixel) }" aria-hidden="true"></span>
    </span>
</template>

<style scoped>
.pixel-loader {
    display: inline-grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2px;
    width: var(--loader-size);
    height: var(--loader-size);
    flex-shrink: 0;
    vertical-align: middle;
}
.pixel-loader__dot {
    background: #14b8a6;
    animation: pixel-wave 1500ms ease-in-out infinite;
}
@keyframes pixel-wave {
    0%, 100% { opacity: 0.25; transform: scale(0.55); box-shadow: none; }
    45% { opacity: 1; transform: scale(1); box-shadow: 0 0 4px rgb(20 184 166 / 45%); }
    65% { opacity: 0.7; transform: scale(0.8); }
}
@media (prefers-reduced-motion: reduce) {
    .pixel-loader__dot { animation: none; opacity: 0.65; }
}
</style>
