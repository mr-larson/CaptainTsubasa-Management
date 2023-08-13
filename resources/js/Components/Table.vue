<template>
  <table :class="customClass">
    <slot></slot>
  </table>
</template>

<script>
export default {
    name: 'Table',
    props: {
        customClass: {
            type: String,
            default: 'min-w-full border divide-y divide-gray-200'
        }
    }
}
</script>
