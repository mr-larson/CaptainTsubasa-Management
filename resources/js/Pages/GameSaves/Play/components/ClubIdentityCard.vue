<script setup>
import Panel from '@/Components/Panel.vue';
import TeamLogo from '@/Components/TeamLogo.vue';
import TeamStyleBadges from '@/Pages/GameSaves/Play/components/TeamStyleBadges.vue';

/** Carte club : logo, nom, classement, budget, description et styles. */
const props = defineProps({
    team:   { type: Object, default: null },
    rank:   { type: Number, default: null },
    total:  { type: Number, default: null },
    budget: { type: Number, default: null },
    averageOverall: { type: Number, default: null },
});
</script>

<template>
    <Panel class="flex items-center gap-4">
        <TeamLogo :team="team" size="2xl" shape="rounded" fallback="stadium" />
        <!-- Texte -->
        <div class="min-w-0 flex flex-col gap-1">
            <h3 class="text-base font-bold text-slate-700 truncate">{{ team?.name ?? '—' }}</h3>
            <p v-if="rank" class="text-xs text-slate-500">
                {{ rank }}<sup>e</sup> / {{ total }} &nbsp;•&nbsp; {{ budget ?? 0 }} <GameBanknote />
            </p>
            <p v-if="averageOverall !== null" class="text-xs text-slate-500">
                Note d’équipe <strong class="tabular-nums text-slate-700">{{ averageOverall }}</strong>
            </p>
            <p v-if="team?.description" class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                {{ team.description }}
            </p>
            <TeamStyleBadges :team="team" size="sm" />
        </div>
    </Panel>
</template>
