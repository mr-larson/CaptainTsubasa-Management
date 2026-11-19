<script setup>
// Liste des piliers du mandat : cible, valeur et statut (✓ atteint, ✗ raté, · en cours / inconnu).
defineProps({
    rows:  { type: Array, required: true },
    dense: { type: Boolean, default: false },
});
</script>

<template>
    <ul class="space-y-1" :class="dense ? 'text-[11px]' : 'text-xs'">
        <li v-for="row in rows" :key="row.key" class="flex items-center gap-2">
            <span class="shrink-0">{{ row.icon }}</span>
            <span class="font-semibold text-slate-600 w-20 shrink-0">{{ row.label }}</span>
            <span class="text-slate-500 flex-1 truncate">{{ row.target }}</span>
            <span class="font-semibold tabular-nums"
                  :class="row.met === true ? 'text-emerald-600' : row.met === false ? 'text-rose-500' : 'text-slate-400'">
                {{ row.value }}
            </span>
            <span class="w-3 text-center" :class="row.met === true ? 'text-emerald-600' : row.met === false ? 'text-rose-500' : 'text-slate-300'">
                {{ row.met === true ? '✓' : row.met === false ? '✗' : '·' }}
            </span>
        </li>
    </ul>
</template>
