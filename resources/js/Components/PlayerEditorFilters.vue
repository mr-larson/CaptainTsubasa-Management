<script setup>
import { fieldClass } from '@/Components/fieldStyles';
import { POSITION_GROUPS } from '@/Enums/positionBadges.js';
import { PLAYER_STATS } from '@/Enums/playerStats.js';

defineProps({
    filters: { type: Object, required: true },
    origins: { type: Array, required: true },
    teams: { type: Array, required: true },
    count: { type: Number, required: true },
    total: { type: Number, required: true },
});
defineEmits(['reset']);
const field = fieldClass({ size: 'sm', border: 'border-slate-300' });
</script>

<template>
    <div class="flex flex-wrap justify-end items-center gap-2" aria-label="Filtres des joueurs">
        <label class="text-xs text-slate-500">
            <span class="sr-only">Poste</span>
            <select v-model="filters.position" class="block w-full" :class="field">
                <option value="ALL">Tous les postes</option>
                <option v-for="group in POSITION_GROUPS" :key="group.key" :value="group.key">{{ group.plural }}</option>
            </select>
        </label>
        <label class="text-xs text-slate-500">
            <span class="sr-only">Univers</span>
            <select v-model="filters.origin" class="block w-full" :class="field">
                <option value="ALL">Tous les univers</option>
                <option v-for="origin in origins" :key="origin.key" :value="origin.key">{{ origin.label }}</option>
            </select>
        </label>
        <label class="text-xs text-slate-500">
            <span class="sr-only">Équipe sous contrat</span>
            <select v-model="filters.team" class="block w-full max-w-[220px]" :class="field">
                <option value="ALL">Toutes les équipes</option>
                <option value="FREE">Joueurs libres</option>
                <option v-for="team in teams" :key="team.id" :value="String(team.id)">{{ team.name }}</option>
            </select>
        </label>
        <label class="text-xs text-slate-500">
            <span class="sr-only">Trier par</span>
            <select v-model="filters.sort" class="block w-full" :class="field">
                <option value="name">Nom (A → Z)</option>
                <option value="overall">Note globale</option>
                <option value="profile">Profil au poste</option>
                <option value="cost">Coût</option>
                <option v-for="stat in PLAYER_STATS" :key="stat.key" :value="stat.key">{{ stat.label }}</option>
            </select>
        </label>
        <label v-if="filters.sort !== 'name'" class="text-xs text-slate-500">
            <span class="sr-only">Ordre</span>
            <select v-model="filters.direction" class="block w-full" :class="field">
                <option value="desc">Décroissant</option>
                <option value="asc">Croissant</option>
            </select>
        </label>
        <button type="button" class="inline-flex items-center justify-center p-2 text-teal-700 hover:text-teal-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600"
                title="Réinitialiser les filtres" aria-label="Réinitialiser les filtres" @click="$emit('reset')">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 10a9 9 0 1 1 2.6 8.4" />
                <path d="M3 4v6h6" />
            </svg>
        </button>
        <span class="py-2 text-xs text-slate-500" aria-live="polite">{{ count }} / {{ total }} joueurs</span>
    </div>
</template>
