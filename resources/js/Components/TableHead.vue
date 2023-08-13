<template>
  <thead :class="customClass">
    <slot></slot>
  </thead>
</template>

<script>
export default {
    name: 'TableHead',
    props: {
        customClass: {
            type: String,
            default: 'bg-gray-50'
        }
    }
}
</script>
