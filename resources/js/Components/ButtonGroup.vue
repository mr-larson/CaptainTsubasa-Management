<template>
  <div :class="customClass">
    <slot></slot>
  </div>
</template>


<script>
export default {
    name: "ButtonGroup",
    props: {
        customClass: {
            type: String,
            default: "flex justify-around pt-8"
        }
  }
};
</script>

