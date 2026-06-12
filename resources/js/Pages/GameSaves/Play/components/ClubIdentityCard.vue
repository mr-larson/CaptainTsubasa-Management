<script setup>
import { usePlayerUtils } from '../usePlayerUtils.js';
import TeamStyleBadges from '@/Pages/GameSaves/Play/components/TeamStyleBadges.vue';

/** Carte club : logo, nom, classement, budget, description et styles. */
const props = defineProps({
    team:   { type: Object, default: null },
    rank:   { type: Number, default: null },
    total:  { type: Number, default: null },
    budget: { type: Number, default: null },
});

const { teamLogoUrl } = usePlayerUtils();
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4 flex items-center gap-4">
        <!-- Logo -->
        <div class="w-20 h-20 rounded-xl overflow-hidden bg-white border border-slate-200 shrink-0 flex items-center justify-center">
            <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt="Logo équipe"/>
            <span v-else class="text-3xl">🏟️</span>
        </div>
        <!-- Texte -->
        <div class="min-w-0 flex flex-col gap-1">
            <h3 class="text-base font-bold text-slate-800 truncate">{{ team?.name ?? '—' }}</h3>
            <p v-if="rank" class="text-xs text-slate-500">
                {{ rank }}<sup>e</sup> / {{ total }} &nbsp;•&nbsp; {{ budget ?? 0 }} €
            </p>
            <p v-if="team?.description" class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                {{ team.description }}
            </p>
            <TeamStyleBadges :team="team" size="sm" />
        </div>
    </div>
</template>
