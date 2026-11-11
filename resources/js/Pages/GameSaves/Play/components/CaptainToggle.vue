<script setup>
// Bouton brassard : même rendu dans Mon équipe et Autres équipes (mode éditable).
defineProps({
    captain: { type: Boolean, default: false },
});
defineEmits(['toggle']);
</script>

<template>
    <button type="button"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
            :class="captain
                ? 'bg-amber-400 text-white border-amber-500 hover:bg-amber-500'
                : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50'"
            :aria-pressed="captain"
            :title="captain ? 'Retirer le brassard' : 'Nommer capitaine'"
            @click="$emit('toggle')">
        {{ captain ? '👑 Capitaine' : '👑 Nommer capitaine' }}
    </button>
</template>
