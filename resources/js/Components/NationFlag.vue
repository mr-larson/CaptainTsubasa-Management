<script setup>
// Drapeau d'une nation : image du catalogue (nations.flag_path) si elle existe, sinon l'emoji.
// Les emoji de drapeaux ne s'affichent pas sous Windows, d'où l'image en priorité.
import { ref, watch } from 'vue';

const props = defineProps({
    flag: { type: String, default: '' },   // emoji de repli
    url:  { type: String, default: null }, // URL de l'image
    name: { type: String, default: '' },   // texte alternatif
});

// Une image introuvable (404) retombe sur l'emoji.
const broken = ref(false);
watch(() => props.url, () => { broken.value = false; });
</script>

<template>
    <img v-if="url && !broken" :src="url" :alt="name" loading="lazy"
         class="inline-block h-[1em] w-[1.4em] object-cover rounded-[2px] align-[-0.125em]"
         @error="broken = true" />
    <span v-else>{{ flag }}</span>
</template>
