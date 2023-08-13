<template>
  <td :class="customClass">
    <slot></slot>
  </td>
</template>

<script>
export default {
    name: 'TableHeaderCell',
    props: {
        customClass: {
            type: String,
            default: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'
        }
    }
}
</script>
