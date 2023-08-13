<template>
  <div class="py-12">
    <div :class="customClass">
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PageWrapper',
  props: {
    customClass: {
      type: String,
      default: 'sm:px-4 lg:px-10 mx-auto'
    }
  }
}
</script>
