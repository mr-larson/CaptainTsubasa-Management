<script setup>
import PlayerAvatar from '@/Components/PlayerAvatar.vue';
import { promiseReminders } from './engine/promises.js';
import { overallOf } from '../GameSaves/Play/usePlayerUtils.js';
import { positionShort, positionBadgeClass } from '@/Enums/positionBadges.js';

defineProps({ players: { type: Array, required: true } });
</script>

<template>
    <table v-if="players.length" class="w-full table-fixed text-[11px] leading-4">
        <thead class="text-left text-slate-400">
            <tr>
                <th class="w-9 py-1">Poste</th>
                <th>Joueur</th>
                <th class="w-9 text-right"><abbr title="Endurance" class="no-underline">End.</abbr></th>
                <th class="w-8 text-right"><abbr title="Note globale" class="no-underline">Note</abbr></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-300">
            <tr
                v-for="player in players"
                :key="player.id ?? `slot-${player.number}`"
                :class="player.is_available === false ? 'text-slate-400' : 'text-slate-700'"
            >
                <td class="py-1">
                    <span
                        class="rounded px-1 py-0.5 text-[10px] font-bold"
                        :class="positionBadgeClass(player.position)"
                        >{{ positionShort(player.position) || '—' }}</span
                    >
                </td>
                <td class="py-1 pr-1">
                    <span class="flex min-w-0 items-center gap-1.5"
                        ><PlayerAvatar v-if="player.id" :player="player" size="xs" :zoomable="false" /><span
                            class="truncate font-semibold"
                            :title="`${player.firstname} ${player.lastname}`"
                            >{{ player.id ? `${player.firstname} ${player.lastname}` : 'Poste vacant' }}</span
                        ><span v-for="reminder in promiseReminders(player.promises)" :key="reminder.text"
                            class="shrink-0 text-[10px]" :title="`${reminder.icon} ${reminder.text}`" :aria-label="reminder.text">{{ reminder.icon }}</span
                        ><span v-if="player.is_captain" class="shrink-0 text-amber-600" title="Capitaine">©</span
                        ><span
                            v-if="player.is_available === false"
                            class="shrink-0 text-rose-400"
                            title="Indisponible"
                            aria-label="Indisponible"
                            >×</span
                        ></span
                    >

                </td>
                <td class="text-right tabular-nums">{{ player.stats?.stamina ?? '—' }}</td>
                <td class="text-right font-bold tabular-nums">{{ player.id ? overallOf(player) : '—' }}</td>
            </tr>
        </tbody>
    </table>
    <p v-else class="py-2 text-xs text-slate-400">Aucun remplaçant.</p>
</template>
