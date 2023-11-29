<template>
  <div :class="customClass">
    <slot></slot>
  </div>
</template>


<script>
export default {
    name: "FormContainer",
    props: {
        customClass: {
            type: String,
            default: "p-4 border border-slate-300 rounded-lg mx-6 bg-white shadow-sm"
        }
  }
};
</script>

