<script setup>
import { Link } from '@inertiajs/vue3';

// Sous-navigation des écrans d'édition : lien retour + pastilles de section.
defineProps({
    back: { type: Object, required: true }, // { href, label }
    links: { type: Array, required: true }, // [{ href, label, active }]
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Link :href="back.href"
              class="text-xs font-semibold px-3 py-1.5 rounded-full border border-slate-300 bg-white text-slate-500 hover:bg-slate-50 transition-all">
            ← {{ back.label }}
        </Link>
        <span class="w-px h-5 bg-slate-200"></span>
        <Link v-for="l in links" :key="l.href" :href="l.href"
              class="text-xs font-bold px-3 py-1.5 rounded-full transition-all"
              :class="l.active
                  ? 'bg-teal-500 text-white shadow-sm'
                  : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50'">
            {{ l.label }}
        </Link>
    </div>
</template>
