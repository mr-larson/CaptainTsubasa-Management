<template>
  <tbody :class="customClass">
    <slot></slot>
  </tbody>
</template>

<script>
export default {
    name: 'TableBody',
    props: {
        customClass: {
            type: String,
            default: 'bg-white divide-y divide-gray-200'
        }
    }
}
</script>
