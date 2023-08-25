<template>
  <h2 :class="customClass">
    <slot></slot>
  </h2>
</template>


<script>
export default {
    name: "H2",
    props: {
        customClass: {
            type: String,
            default: "text-lg font-semibold"
        }
  }
};
</script>

<style scoped>
/* Si vous voulez ajouter des styles spécifiques pour ce composant */
</style>
