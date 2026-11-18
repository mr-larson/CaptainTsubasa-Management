<script setup>
import { computed } from 'vue';
import { formatNumber } from '@/utils/format.js';
import GameBanknote from './GameBanknote.vue';

/** Montant en billets Golden Eleven : nombre formaté suivi de l'icône. `prefix` pour un signe (« + », « − », « ≈ »). */
const props = defineProps({
    amount: { type: Number, default: 0 },
    prefix: { type: String, default: '' },
});

const text = computed(() => `${props.prefix}${formatNumber(props.amount)}`);
</script>

<template>
    <span class="whitespace-nowrap">{{ text }} <GameBanknote /></span>
</template>
