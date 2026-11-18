<script setup>
// Carte « Forces face à face » du Dashboard : moyennes d'effectif des deux équipes, joueurs adverses à
// surveiller et descriptions d'équipe. Domaine : Match. Utilisée par Pages/GameSaves/Play/TabDashboard.vue.
import SectionTitle from '@/Components/SectionTitle.vue';
import { computed } from 'vue';
import { useAnimatedStats } from '@/Composables/useAnimatedStats.js';
import { contractPlayer, seasonStatsOf } from '../teamRoster.js';
import { STAT_COLORS } from '../usePlayerUtils.js';
import { averageTeamStat, TEAM_STATS } from '../teamStats.js';

const props = defineProps({
    team: { type: Object, default: null },
    opponentTeam: { type: Object, default: null },
    nextMatchInfo: { type: Object, default: null },
    roster: { type: Array, required: true },
    isByeWeek: { type: Boolean, required: true },
    playerSeasonStats: { type: Object, default: () => ({}) },
    week: { type: Number, required: true },
});

const opponentPlayers = computed(() => (props.opponentTeam?.contracts ?? [])
    .map(contractPlayer)
    .filter(Boolean));

const faceToFace = computed(() => TEAM_STATS.map(({ key, label, color }) => ({
    key, label,
    color: color ?? STAT_COLORS[key],
    me: averageTeamStat(props.roster, key),
    opp: props.isByeWeek ? null : averageTeamStat(opponentPlayers.value, key),
})));

const { root: forcesRoot, value: forcesValue, width: forcesWidth } = useAnimatedStats(
    () => Object.fromEntries(faceToFace.value.flatMap(stat => [
        [`${stat.key}:me`, stat.me], [`${stat.key}:opp`, stat.opp],
    ])),
    () => `${props.team?.id}:${props.opponentTeam?.id}:${props.week}`,
);

// Joueurs adverses à surveiller : meilleur buteur et meilleur duelliste.
const opponentThreats = computed(() => {
    const players = opponentPlayers.value;
    const statOf  = (p) => seasonStatsOf(props.playerSeasonStats, p.id);
    const best = (pick) => players
        .map(p => ({ player: p, value: Number(pick(statOf(p)) ?? 0) }))
        .filter(r => r.value > 0)
        .sort((a, b) => b.value - a.value)[0] ?? null;
    const scorer  = best(s => s?.offense?.goals);
    const duelist = best(s => s?.duelsWon);
    return [
        scorer  && { key: 'goals', text: `⚽ ${scorer.player.lastname}`,  detail: `${scorer.value} but${scorer.value > 1 ? 's' : ''}` },
        duelist && { key: 'duels', text: `💪 ${duelist.player.lastname}`, detail: `${duelist.value} duels gagnés` },
    ].filter(Boolean);
});
</script>

<template>
    <div ref="forcesRoot" class="border border-slate-200 rounded-xl bg-slate-50 p-4 shadow-card">
        <div class="flex items-center justify-between gap-2 mb-3">
            <SectionTitle class="truncate">{{ team?.name ?? 'Mon équipe' }}</SectionTitle>
            <SectionTitle class="shrink-0">Forces</SectionTitle>
            <SectionTitle class="truncate text-right">
                {{ opponentTeam && !isByeWeek ? nextMatchInfo?.opponentName : 'Pas d\'adversaire' }}
            </SectionTitle>
        </div>
        <div class="space-y-2">
            <div v-for="stat in faceToFace" :key="stat.key" class="grid grid-cols-[1.75rem_minmax(0,1fr)_4.5rem_minmax(0,1fr)_1.75rem] items-center gap-2">
                <span class="text-xs font-black text-right"
                      :class="stat.me != null && stat.opp != null && stat.me > stat.opp ? 'text-emerald-600' : 'text-slate-700'">{{ stat.me == null ? '—' : forcesValue(`${stat.key}:me`) }}</span>
                <!-- Ma barre (croît vers la gauche) -->
                <div class="h-2 bg-slate-200 rounded-full overflow-hidden flex justify-end">
                    <div class="h-full rounded-full" :class="stat.color" :style="{ width: forcesWidth(`${stat.key}:me`) }"></div>
                </div>
                <span class="text-[11px] text-slate-500 text-center">{{ stat.label }}</span>
                <!-- Barre adverse (croît vers la droite) -->
                <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div v-if="stat.opp != null" class="h-full rounded-full opacity-60" :class="stat.color"
                         :style="{ width: forcesWidth(`${stat.key}:opp`) }"></div>
                </div>
                <span class="text-xs font-black"
                      :class="stat.me != null && stat.opp != null && stat.opp > stat.me ? 'text-rose-500' : 'text-slate-700'">
                    {{ stat.opp == null ? '—' : forcesValue(`${stat.key}:opp`) }}
                </span>
            </div>
        </div>
        <p class="mt-2 text-2xs text-slate-500">Moyennes des effectifs sur 100 · Arrêt main et Poings calculés sur les gardiens.</p>
        <div v-if="opponentTeam && !isByeWeek && opponentThreats.length"
             class="mt-3 pt-3 border-t border-slate-200 flex flex-wrap items-center justify-end gap-x-3 gap-y-1 text-xs">
            <SectionTitle as="span">À surveiller</SectionTitle>
            <span v-for="t in opponentThreats" :key="t.key" class="text-slate-700">
                <span class="font-semibold">{{ t.text }}</span>
                <span class="text-slate-400"> · {{ t.detail }}</span>
            </span>
        </div>
        <div v-if="team?.description || (opponentTeam && !isByeWeek && opponentTeam.description)"
             class="mt-3 pt-3 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <p class="text-xs text-slate-500 italic leading-relaxed line-clamp-2">{{ team?.description }}</p>
            <p v-if="opponentTeam && !isByeWeek" class="text-xs text-slate-500 italic leading-relaxed line-clamp-2 sm:text-right">{{ opponentTeam.description }}</p>
        </div>
    </div>
</template>
