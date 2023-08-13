<template>
  <div :class="customClass">
    <slot></slot>
  </div>
</template>

<script>
export default {
    name: 'CardContainer',
    props: {
        customClass: {
            type: String,
            default: 'bg-white overflow-hidden shadow-sm sm:rounded-lg'
        }
    }
}
</script>
