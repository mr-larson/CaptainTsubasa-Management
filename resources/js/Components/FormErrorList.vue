<script setup>
import { computed } from 'vue';

// Liste les erreurs de validation dont la clé commence par `prefix` (ex.
// "special_moves.0." ou "secondary_positions."), ou toutes sans préfixe.
// Utile pour les champs imbriqués que InputError ne couvre pas.
const props = defineProps({
    errors: { type: Object, default: () => ({}) },
    prefix: { type: String, default: '' },
});

const messages = computed(() =>
    Object.entries(props.errors ?? {})
        .filter(([key]) => key.startsWith(props.prefix))
        .map(([, message]) => message)
);
</script>

<template>
    <ul v-if="messages.length" class="mt-1 space-y-0.5 text-sm text-rose-600" role="alert">
        <li v-for="(message, i) in messages" :key="i">{{ message }}</li>
    </ul>
</template>
