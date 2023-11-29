<template>
  <div :class="customClass">
    <slot></slot>
  </div>
</template>


<script>
export default {
    name: "FormRaw",
    props: {
        customClass: {
            type: String,
            default: "flex flex-col md:grid lg:grid-cols-2 gap-4 text-slate-700"
        }
  }
};
</script>

