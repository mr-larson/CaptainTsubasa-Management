<template>
    <div :class="customClass">
        <slot></slot>
    </div>
</template>

<script>
export default {
    name: 'TableContainer',
    props: {
        customClass: {
            type: String,
            default: 'overflow-x-auto w-full'
        }
    }
};
</script>
