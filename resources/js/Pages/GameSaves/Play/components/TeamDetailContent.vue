<script setup>
import { computed } from 'vue';
import Panel from '@/Components/Panel.vue';
import PlayerAvatar from '@/Components/PlayerAvatar.vue';
import { positionLabel } from '@/Enums/positionBadges.js';
import ClubIdentityCard from './ClubIdentityCard.vue';
import { averageTeamStat, TEAM_STATS } from '../teamStats.js';
import { STAT_COLORS, usePlayerUtils } from '../usePlayerUtils.js';
const props = defineProps({
    team: { type: Object, required: true },
    rank: { type: Number, default: null },
    total: { type: Number, default: 0 },
});
const { overallOf } = usePlayerUtils();
const selectedRoster = computed(() => (props.team.contracts ?? [])
    .map(contract => contract.game_player ?? contract.gamePlayer ?? contract.player).filter(Boolean));
const selectedStats = computed(() => [
    ...TEAM_STATS.slice(0, 2), { key: 'morale', label: 'Moral', color: 'bg-teal-400' }, ...TEAM_STATS.slice(2),
].map(stat => ({ ...stat, value: averageTeamStat(selectedRoster.value, stat.key) })));
</script>
<template>
    <div class="flex flex-col gap-3">
                <ClubIdentityCard :team="team" :rank="rank" :total="total"
                                  :budget="team.budget" :average-overall="team.average_overall" />

                <div class="grid items-start gap-3 sm:grid-cols-2">
                    <Panel padding="sm" title="Forces" class="space-y-2">
                        <div v-for="stat in selectedStats" :key="stat.key" class="flex items-center gap-2 text-xs">
                            <span class="w-16 shrink-0 text-slate-500">{{ stat.label }}</span>
                            <div aria-hidden="true" class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full" :class="stat.color ?? STAT_COLORS[stat.key]"
                                     :style="{ width: Math.min(stat.value ?? 0, 100) + '%' }"></div>
                            </div>
                            <span class="w-6 shrink-0 text-right font-bold tabular-nums text-slate-700">{{ stat.value ?? '—' }}</span>
                        </div>
                    </Panel>

                    <Panel padding="sm" :title="`Effectif · ${selectedRoster.length}`">
                        <ul v-if="selectedRoster.length" class="max-h-96 space-y-1 overflow-y-auto">
                            <li v-for="player in selectedRoster" :key="player.id"
                                class="flex items-center justify-between gap-2 rounded-lg bg-white px-2 py-1.5 text-xs">
                                <PlayerAvatar :player="player" show-flag />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-slate-700">{{ player.firstname }} {{ player.lastname }}</p>
                                    <p class="text-slate-500">{{ positionLabel(player.position) }}</p>
                                </div>
                                <span class="shrink-0 font-bold tabular-nums text-slate-700">{{ overallOf(player) }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-xs text-slate-500">Aucun joueur sous contrat.</p>
                    </Panel>
                </div>
    </div>
</template>
