@<template>
  <td :class="customClass">
    <slot></slot>
  </td>
</template>

<script>
export default {
    name: 'TableCell',
    props: {
        customClass: {
            type: String,
            default: 'px-6 py-4 whitespace-nowrap'
        }
    }
}
</script>
