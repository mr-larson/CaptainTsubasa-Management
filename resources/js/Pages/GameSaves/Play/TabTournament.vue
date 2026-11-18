<script setup>
import NationFlag from '@/Components/NationFlag.vue';
import Panel from '@/Components/Panel.vue';
import { computed } from 'vue';

const props = defineProps({
    tournament: { type: Object, default: null },
});

const stageLabel = computed(() => ({
    group:    'Phase de poules',
    quarter:  'Quarts de finale',
    semi:     'Demi-finales',
    final:    'Finale',
    done:     'Tournoi terminé',
}[props.tournament?.stage] ?? '—'));

// Tours à élimination directe présents, dans l'ordre ; la finale et la 3e place sont affichées à part.
const ROUND_LABELS = { quarter: 'Quarts de finale', semi: 'Demi-finales' };
const earlyRounds = computed(() => Object.entries(ROUND_LABELS)
    .map(([key, label]) => ({ key, label, matches: props.tournament?.bracket?.[key] ?? [] }))
    .filter(r => r.matches.length));
const finale = computed(() => props.tournament?.bracket?.final ?? []);
const thirdPlace = computed(() => props.tournament?.bracket?.third ?? []);
const hasBracket = computed(() => earlyRounds.value.length > 0 || finale.value.length > 0);

function isWinner(match, side) {
    if (!match.played || match.winner_team_id == null) return false;
    return match[side]?.team_id === match.winner_team_id;
}
</script>

<template>
    <div v-if="tournament" class="flex-1 flex flex-col gap-6 overflow-y-auto min-h-0 pr-1">

        <!-- En-tête / champion -->
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-xs font-bold text-indigo-500 uppercase tracking-widest">Coupe du Monde</div>
                <div class="text-lg font-bold text-slate-700">{{ stageLabel }}</div>
            </div>
            <div v-if="tournament.champion"
                 class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-r from-amber-100 to-yellow-50 border border-amber-300">
                <span class="text-3xl">🏆</span>
                <div>
                    <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Champion</div>
                    <div class="text-base font-extrabold text-amber-800">
                        <NationFlag :flag="tournament.champion.flag" :url="tournament.champion.flag_url" :name="tournament.champion.name" /> {{ tournament.champion.name }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Poules -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="group in tournament.groups" :key="group.key"
                 class="border border-slate-200 rounded-xl bg-white overflow-hidden shadow-card">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 text-sm font-bold text-slate-700">
                    Groupe {{ group.key }}
                </div>
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[440px]">
                    <thead class="border-b border-slate-200 text-2xs font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="text-left py-2 pl-4">Sélection</th>
                            <th class="px-1.5"><abbr title="Matchs joués" class="cursor-help no-underline">J</abbr></th>
                            <th class="px-1.5"><abbr title="Matchs gagnés" class="cursor-help no-underline">G</abbr></th>
                            <th class="px-1.5"><abbr title="Matchs nuls" class="cursor-help no-underline">N</abbr></th>
                            <th class="px-1.5"><abbr title="Matchs perdus" class="cursor-help no-underline">P</abbr></th>
                            <th class="px-1.5"><abbr title="Buts pour" class="cursor-help no-underline">BP</abbr></th>
                            <th class="px-1.5"><abbr title="Buts contre" class="cursor-help no-underline">BC</abbr></th>
                            <th class="px-1.5"><abbr title="Différence de buts" class="cursor-help no-underline">Diff</abbr></th>
                            <th class="px-1.5 pr-4"><abbr title="Points : 3 par victoire, 1 par nul" class="cursor-help no-underline">Pts</abbr></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in group.rows" :key="row.team_id"
                            class="border-t border-slate-200"
                            :class="row.qualified ? 'bg-emerald-50/60' : ''">
                            <td class="py-2 pl-4 flex items-center gap-2">
                                <span class="w-4 text-[11px] font-bold"
                                      :class="row.qualified ? 'text-emerald-600' : 'text-slate-300'">{{ i + 1 }}</span>
                                <span class="text-lg leading-none"><NationFlag :flag="row.flag" :url="row.flag_url" :name="row.name" /></span>
                                <span class="font-semibold text-slate-700">{{ row.name }}</span>
                                <span v-if="tournament.eliminated?.[row.team_id]" class="text-2xs text-slate-400">éliminé</span>
                            </td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.played }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.wins }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.draws }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.losses }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.gf }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.ga }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.gd > 0 ? '+' : '' }}{{ row.gd }}</td>
                            <td class="text-center font-bold text-slate-700 px-1.5 pr-4">{{ row.points }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <div class="px-4 py-1.5 text-2xs text-emerald-600 bg-emerald-50/40 border-t border-slate-200">
                    Les 2 premiers sont qualifiés.
                </div>
            </div>
        </div>

        <!-- Tableau final (bracket) -->
        <Panel as="div" tone="white" v-if="hasBracket">
            <div class="text-sm font-bold text-slate-700 mb-3">Phase à élimination directe</div>
            <div class="flex flex-col lg:flex-row items-stretch gap-4">

                <!-- Quarts / demies -->
                <template v-for="round in earlyRounds" :key="round.key">
                    <div class="flex-1 flex flex-col gap-3">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">{{ round.label }}</div>
                        <div v-for="(m, i) in round.matches" :key="round.key + i"
                             class="rounded-xl border border-slate-200 overflow-hidden">
                            <div v-for="side in ['home', 'away']" :key="side"
                                 class="flex items-center justify-between px-3 py-2"
                                 :class="[side === 'away' ? 'border-t border-slate-200' : '',
                                          isWinner(m, side) ? 'bg-indigo-50 font-bold text-indigo-700' : 'text-slate-600']">
                                <span><NationFlag v-if="m[side]" :flag="m[side].flag" :url="m[side].flag_url" :name="m[side].name" /> {{ m[side]?.name ?? '—' }}</span>
                                <span>{{ m.played ? m[side + '_score'] : '' }}<span v-if="m.shootout" class="ml-1 text-2xs font-normal text-slate-400" title="Tirs au but">({{ m.shootout[side] }})</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="hidden lg:flex items-center text-slate-300 text-2xl">→</div>
                </template>

                <!-- Finale + match pour la 3e place -->
                <div class="flex-1 flex flex-col gap-3">
                    <div class="text-[11px] font-bold text-amber-500 uppercase tracking-wide">Finale</div>
                    <template v-if="finale.length">
                        <div v-for="(m, i) in finale" :key="'f' + i"
                             class="rounded-xl border-2 border-amber-300 overflow-hidden">
                            <div v-for="side in ['home', 'away']" :key="side"
                                 class="flex items-center justify-between px-3 py-2.5"
                                 :class="[side === 'away' ? 'border-t border-amber-100' : '',
                                          isWinner(m, side) ? 'bg-amber-50 font-bold text-amber-700' : 'text-slate-600']">
                                <span><NationFlag v-if="m[side]" :flag="m[side].flag" :url="m[side].flag_url" :name="m[side].name" /> {{ m[side]?.name ?? '—' }}</span>
                                <span>{{ m.played ? m[side + '_score'] : '' }}<span v-if="m.shootout" class="ml-1 text-2xs font-normal text-slate-400" title="Tirs au but">({{ m.shootout[side] }})</span></span>
                            </div>
                        </div>
                    </template>
                    <div v-else class="rounded-xl border border-dashed border-slate-200 px-3 py-4 text-center text-xs text-slate-400">
                        En attente des qualifiés
                    </div>

                    <template v-if="thirdPlace.length">
                        <div class="text-[11px] font-bold text-orange-500 uppercase tracking-wide mt-2">Match pour la 3e place</div>
                        <div v-for="(m, i) in thirdPlace" :key="'t' + i"
                             class="rounded-xl border border-orange-200 overflow-hidden">
                            <div v-for="side in ['home', 'away']" :key="side"
                                 class="flex items-center justify-between px-3 py-2"
                                 :class="[side === 'away' ? 'border-t border-orange-100' : '',
                                          isWinner(m, side) ? 'bg-orange-50 font-bold text-orange-700' : 'text-slate-600']">
                                <span><NationFlag v-if="m[side]" :flag="m[side].flag" :url="m[side].flag_url" :name="m[side].name" /> {{ m[side]?.name ?? '—' }}</span>
                                <span>{{ m.played ? m[side + '_score'] : '' }}<span v-if="m.shootout" class="ml-1 text-2xs font-normal text-slate-400" title="Tirs au but">({{ m.shootout[side] }})</span></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </Panel>
    </div>
</template>
