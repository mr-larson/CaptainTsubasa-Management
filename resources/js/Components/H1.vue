<template>
  <div class="flex justify-center">
    <h1 :class="customClass">
      <slot></slot>
    </h1>
  </div>
</template>


<script>
export default {
    name: "H1",
    props: {
        customClass: {
            type: String,
            default: "text-2xl font-bold text-slate-800 mb-4"
        }
  }
};
</script>
