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
            default: "p-4 sm:p-6 border border-slate-200 rounded-xl md:mx-6 bg-white shadow-sm"
        }
  }
};
</script>

