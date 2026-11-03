<script setup>
// Cellule de formulaire : libellé + champ (slot) + erreur.
// Deux mises en page, comme les écrans existants :
//  - 'rows' : libellé à gauche (FormCol / InputLabel), pages BDD et créations ;
//  - 'grid' : libellé au-dessus, grilles des écrans de gestion de partie.
const props = defineProps({
    label: { type: String, default: '' },
    for: { type: String, default: null },
    error: { type: String, default: null },
    layout: { type: String, default: 'rows' },
});
</script>

<template>
    <div :class="layout === 'grid' ? '' : 'flex items-center m-3 gap-4 md:gap-0'">
        <label
            v-if="label"
            :for="props.for"
            :class="layout === 'grid'
                ? 'block text-sm font-semibold text-slate-500 mb-1'
                : 'text-slate-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4'"
        >
            {{ label }}
        </label>
        <slot />
        <p v-if="error" class="text-sm text-red-600 mt-1">
            {{ error }}
        </p>
        <slot name="after" />
    </div>
</template>
