<script setup>
// Petit logo rond d'équipe (initiale si pas de logo). Chemin servi sous /storage.
defineProps({
    team: { type: Object, required: true },
});
</script>

<template>
    <div class="h-6 w-6 rounded-full bg-white/80 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
        <img v-if="team.logo_path" loading="lazy" decoding="async" :src="`/storage/${team.logo_path}`" alt=""
             class="h-full w-full object-cover" />
        <span v-else class="text-[9px] text-slate-400">{{ (team.name || '?').charAt(0) }}</span>
    </div>
</template>
