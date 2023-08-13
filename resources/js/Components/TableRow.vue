<template>
  <tr :class="customClass">
    <slot></slot>
  </tr>
</template>

<script>
export default {
    props: {
        customClass: {
            type: String,
            default: '' 
        }
    }
}
</script>
