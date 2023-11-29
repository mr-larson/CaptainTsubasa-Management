<template>
  <div :class="customClass">
    <slot></slot>
  </div>
</template>


<script>
export default {
    name: "FormCol",
    props: {
        customClass: {
            type: String,
            default: "flex items-center m-3 gap-4 md:gap-0"
        }
  }
};
</script>

