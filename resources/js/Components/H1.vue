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
            default: "text-3xl font-bold text-slate-600 mb-4"
        }
  }
};
</script>

<style scoped>
/* Si vous voulez ajouter des styles spécifiques pour ce composant */
</style>
