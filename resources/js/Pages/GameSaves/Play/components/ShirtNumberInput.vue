<script setup>
import { fieldClass } from '@/Components/fieldStyles';

/**
 * Numéro de maillot éditable. `change` reçoit (valeur, annuler) : si le serveur
 * refuse le numéro (déjà pris), `annuler()` remet l'ancien numéro dans le champ.
 */
const props = defineProps({
    number: { type: [Number, String], default: null },
});
const emit = defineEmits(['change']);

function onChange(event) {
    const input = event.target;
    const previous = props.number ?? '';
    emit('change', input.value, () => { input.value = previous; });
}
</script>

<template>
    <label class="flex items-center gap-1.5">
        <span class="text-xs text-slate-500 font-semibold">N°</span>
        <input type="number" min="1" max="99" :value="number" @change="onChange"
               class="w-14 px-2 py-1" :class="fieldClass({ shape: 'rect', size: 'sm' })" />
    </label>
</template>
