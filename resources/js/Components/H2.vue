<template>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <slot></slot> <!-- Ceci est un slot pour le contenu que vous voulez mettre à l'intérieur du H2 -->
    </h2>
</template>

<script>
export default {
    name: "H2",
};
</script>

<style scoped>
/* Si vous voulez ajouter des styles spécifiques pour ce composant */
</style>
