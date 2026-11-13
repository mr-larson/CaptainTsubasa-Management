<script setup>
import { computed } from 'vue';

const props = defineProps({
    tournament: { type: Object, default: null },
});

const stageLabel = computed(() => ({
    group:    'Phase de poules',
    semi:     'Demi-finales',
    final:    'Finale',
    done:     'Tournoi terminé',
}[props.tournament?.stage] ?? '—'));

const semis = computed(() => props.tournament?.bracket?.semi ?? []);
const finale = computed(() => props.tournament?.bracket?.final ?? []);

function isWinner(match, side) {
    if (!match.played || match.winner_team_id == null) return false;
    return match[side]?.team_id === match.winner_team_id;
}

function score(match) {
    if (!match.played) return 'vs';
    return `${match.home_score} – ${match.away_score}`;
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
                        {{ tournament.champion.flag }} {{ tournament.champion.name }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Poules -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="group in tournament.groups" :key="group.key"
                 class="border border-slate-300 rounded-xl bg-white overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-300 text-sm font-bold text-slate-700">
                    Groupe {{ group.key }}
                </div>
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[380px]">
                    <thead class="border-b border-slate-300 text-2xs font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="text-left py-2 pl-4">Sélection</th>
                            <th class="px-1.5"><abbr title="Matchs joués" class="cursor-help no-underline">J</abbr></th>
                            <th class="px-1.5"><abbr title="Matchs gagnés" class="cursor-help no-underline">G</abbr></th>
                            <th class="px-1.5"><abbr title="Matchs nuls" class="cursor-help no-underline">N</abbr></th>
                            <th class="px-1.5"><abbr title="Matchs perdus" class="cursor-help no-underline">P</abbr></th>
                            <th class="px-1.5"><abbr title="Différence de buts" class="cursor-help no-underline">Diff</abbr></th>
                            <th class="px-1.5 pr-4"><abbr title="Points : 3 par victoire, 1 par nul" class="cursor-help no-underline">Pts</abbr></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in group.rows" :key="row.team_id"
                            class="border-t border-slate-300"
                            :class="row.qualified ? 'bg-emerald-50/60' : ''">
                            <td class="py-2 pl-4 flex items-center gap-2">
                                <span class="w-4 text-[11px] font-bold"
                                      :class="row.qualified ? 'text-emerald-600' : 'text-slate-300'">{{ i + 1 }}</span>
                                <span class="text-lg leading-none">{{ row.flag }}</span>
                                <span class="font-semibold text-slate-700">{{ row.name }}</span>
                            </td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.played }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.wins }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.draws }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.losses }}</td>
                            <td class="text-center text-slate-500 px-1.5">{{ row.gd > 0 ? '+' : '' }}{{ row.gd }}</td>
                            <td class="text-center font-bold text-slate-700 px-1.5 pr-4">{{ row.points }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <div class="px-4 py-1.5 text-2xs text-emerald-600 bg-emerald-50/40 border-t border-slate-300">
                    Les 2 premiers sont qualifiés.
                </div>
            </div>
        </div>

        <!-- Tableau final (bracket) -->
        <div v-if="semis.length || finale.length"
             class="border border-slate-300 rounded-xl bg-white p-4">
            <div class="text-sm font-bold text-slate-700 mb-3">Phase à élimination directe</div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">

                <!-- Demi-finales -->
                <div class="flex-1 flex flex-col gap-3">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">Demi-finales</div>
                    <div v-for="(m, i) in semis" :key="'s' + i"
                         class="rounded-xl border border-slate-300 overflow-hidden">
                        <div class="flex items-center justify-between px-3 py-2"
                             :class="isWinner(m, 'home') ? 'bg-indigo-50 font-bold text-indigo-700' : 'text-slate-600'">
                            <span>{{ m.home?.flag }} {{ m.home?.name ?? '—' }}</span>
                            <span>{{ m.played ? m.home_score : '' }}<span v-if="m.shootout" class="ml-1 text-2xs font-normal text-slate-400" title="Tirs au but">({{ m.shootout.home }})</span></span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2 border-t border-slate-300"
                             :class="isWinner(m, 'away') ? 'bg-indigo-50 font-bold text-indigo-700' : 'text-slate-600'">
                            <span>{{ m.away?.flag }} {{ m.away?.name ?? '—' }}</span>
                            <span>{{ m.played ? m.away_score : '' }}<span v-if="m.shootout" class="ml-1 text-2xs font-normal text-slate-400" title="Tirs au but">({{ m.shootout.away }})</span></span>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:block text-slate-300 text-2xl">→</div>

                <!-- Finale -->
                <div class="flex-1 flex flex-col gap-3">
                    <div class="text-[11px] font-bold text-amber-500 uppercase tracking-wide">Finale</div>
                    <template v-if="finale.length">
                    <div v-for="(m, i) in finale" :key="'f' + i"
                         class="rounded-xl border-2 border-amber-300 overflow-hidden">
                        <div class="flex items-center justify-between px-3 py-2.5"
                             :class="isWinner(m, 'home') ? 'bg-amber-50 font-bold text-amber-700' : 'text-slate-600'">
                            <span>{{ m.home?.flag }} {{ m.home?.name ?? '—' }}</span>
                            <span>{{ m.played ? m.home_score : '' }}<span v-if="m.shootout" class="ml-1 text-2xs font-normal text-slate-400" title="Tirs au but">({{ m.shootout.home }})</span></span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2.5 border-t border-amber-100"
                             :class="isWinner(m, 'away') ? 'bg-amber-50 font-bold text-amber-700' : 'text-slate-600'">
                            <span>{{ m.away?.flag }} {{ m.away?.name ?? '—' }}</span>
                            <span>{{ m.played ? m.away_score : '' }}<span v-if="m.shootout" class="ml-1 text-2xs font-normal text-slate-400" title="Tirs au but">({{ m.shootout.away }})</span></span>
                        </div>
                    </div>
                    </template>
                    <div v-else class="rounded-xl border border-dashed border-slate-300 px-3 py-4 text-center text-xs text-slate-400">
                        En attente des qualifiés
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
