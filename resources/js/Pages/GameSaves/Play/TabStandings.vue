<script setup>
import { computed } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';

const props = defineProps({
    standings: { type: Array,  required: true },
    team:      { type: Object, default: null },
    matches:   { type: Array,  default: () => [] },
});

const { teamLogoUrl } = usePlayerUtils();

const maxPoints = computed(() =>
    Math.max(1, ...props.standings.map(r => r.points ?? 0))
);

// Forme récente : 5 derniers matchs joués pour chaque équipe
const recentFormFor = (teamId) => {
    const played = props.matches
        .filter(m =>
            m.status === 'played' &&
            (Number(m.home_team_id) === teamId || Number(m.away_team_id) === teamId)
        )
        .slice(-5);

    return played.map(m => {
        const isHome   = m.home_team_id === teamId;
        const scored   = isHome ? (m.home_score ?? 0) : (m.away_score ?? 0);
        const conceded = isHome ? (m.away_score ?? 0) : (m.home_score ?? 0);
        if (scored > conceded)  return { label: 'V', class: 'bg-emerald-500 text-white' };
        if (scored === conceded) return { label: 'N', class: 'bg-slate-300 text-slate-700' };
        return { label: 'D', class: 'bg-rose-500 text-white' };
    });
};

// Buts pour/contre depuis standings ou matches
const goalsFor = (row) => {
    if (row.goals_for !== undefined) return row.goals_for;
    return props.matches
        .filter(m => m.status === 'played')
        .reduce((acc, m) => {
            if (m.home_team_id === row.id) return acc + (m.home_score ?? 0);
            if (m.away_team_id === row.id) return acc + (m.away_score ?? 0);
            return acc;
        }, 0);
};

const goalsAgainst = (row) => {
    if (row.goals_against !== undefined) return row.goals_against;
    return props.matches
        .filter(m => m.status === 'played')
        .reduce((acc, m) => {
            if (m.home_team_id === row.id) return acc + (m.away_score ?? 0);
            if (m.away_team_id === row.id) return acc + (m.home_score ?? 0);
            return acc;
        }, 0);
};

const goalDiff = (row) => {
    const diff = goalsFor(row) - goalsAgainst(row);
    return diff > 0 ? `+${diff}` : `${diff}`;
};

const myStanding = computed(() =>
    props.standings.find(r => r.id === props.team?.id) ?? null
);

const myRank = computed(() =>
    props.standings.findIndex(r => r.id === props.team?.id) + 1
);

const rankIcon = (rank) => {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return null;
};
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- Mon classement -->
        <div v-if="myStanding" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Ta position</h3>
            <div class="flex items-center gap-6">

                <!-- Rang -->
                <div class="text-center shrink-0">
                    <div class="text-4xl font-black"
                         :class="myRank === 1 ? 'text-yellow-500' : myRank <= 3 ? 'text-amber-500' : myRank <= 6 ? 'text-teal-500' : 'text-slate-500'">
                        {{ myRank }}<sup class="text-xl">e</sup>
                    </div>
                    <div class="text-[10px] text-slate-400 font-semibold">/ {{ standings.length }}</div>
                </div>

                <!-- Logo + nom -->
                <div class="flex items-center gap-3 shrink-0">
                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-white border border-slate-200 flex items-center justify-center">
                        <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt=""/>
                        <span v-else class="text-xl">🏟️</span>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-800">{{ team?.name }}</div>
                        <div class="text-xs text-slate-400">
                            {{ myStanding.wins }}V · {{ myStanding.draws }}N · {{ myStanding.losses }}D
                        </div>
                    </div>
                </div>

                <!-- Stats rapides -->
                <div class="flex gap-4 flex-1">
                    <div class="text-center">
                        <div class="text-xl font-black text-teal-600">{{ myStanding.points }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Points</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-black text-slate-700">{{ goalsFor(myStanding) }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Buts marqués</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-black text-slate-700">{{ goalsAgainst(myStanding) }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Buts encaissés</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-black"
                             :class="(goalsFor(myStanding) - goalsAgainst(myStanding)) >= 0 ? 'text-emerald-600' : 'text-rose-500'">
                            {{ goalDiff(myStanding) }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-semibold">Diff. buts</div>
                    </div>
                </div>

                <!-- Forme récente -->
                <div class="shrink-0">
                    <div class="text-[10px] text-slate-400 font-semibold mb-1.5">Forme récente</div>
                    <div class="flex gap-1">
                        <span v-for="(r, i) in recentFormFor(team?.id)" :key="i"
                              class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black"
                              :class="r.class">
                            {{ r.label }}
                        </span>
                        <span v-if="!recentFormFor(team?.id).length" class="text-xs text-slate-400 italic">—</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau classement -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Classement général</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-xs min-w-max text-left">
                    <thead class="border-b border-slate-200">
                    <tr>
                        <th class="py-2 w-8 text-center text-slate-400 font-semibold">#</th>
                        <th class="py-2 pr-4 text-slate-500 font-semibold">Équipe</th>
                        <th class="py-2 px-2 text-right text-slate-500 font-semibold">J</th>
                        <th class="py-2 px-2 text-right text-slate-500 font-semibold">V</th>
                        <th class="py-2 px-2 text-right text-slate-500 font-semibold">N</th>
                        <th class="py-2 px-2 text-right text-slate-500 font-semibold">D</th>
                        <th class="py-2 px-2 text-right text-slate-500 font-semibold">BP</th>
                        <th class="py-2 px-2 text-right text-slate-500 font-semibold">BC</th>
                        <th class="py-2 px-2 text-right text-slate-500 font-semibold">Diff</th>
                        <th class="py-2 px-2 text-right text-slate-500 font-semibold">Pts</th>
                        <th class="py-2 pl-4 text-slate-500 font-semibold">Forme</th>
                        <th class="py-2 pl-4 text-slate-500 font-semibold w-32">Progression</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(row, index) in standings" :key="row.id"
                        class="border-b border-slate-100 last:border-0 transition-colors"
                        :class="team?.id === row.id ? 'bg-teal-50' : index % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'">

                        <!-- Rang -->
                        <td class="py-2 text-center">
                            <span v-if="rankIcon(index + 1)" class="text-base">{{ rankIcon(index + 1) }}</span>
                            <span v-else class="font-bold text-slate-400">{{ index + 1 }}</span>
                        </td>

                        <!-- Équipe -->
                        <td class="py-2 pr-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded overflow-hidden bg-white border border-slate-100 shrink-0">
                                    <img v-if="teamLogoUrl(row)" :src="teamLogoUrl(row)" class="w-full h-full object-contain" alt=""/>
                                </div>
                                <span class="font-semibold truncate max-w-[110px]"
                                      :class="team?.id === row.id ? 'text-teal-700' : 'text-slate-700'">
                                        {{ row.name }}
                                    </span>
                                <span v-if="team?.id === row.id"
                                      class="text-[9px] bg-teal-500 text-white px-1.5 py-0.5 rounded-full font-bold shrink-0">
                                        MOI
                                    </span>
                            </div>
                        </td>

                        <!-- Stats -->
                        <td class="py-2 px-2 text-right font-semibold text-slate-600">{{ row.played }}</td>
                        <td class="py-2 px-2 text-right font-bold text-emerald-600">{{ row.wins }}</td>
                        <td class="py-2 px-2 text-right font-semibold text-slate-500">{{ row.draws }}</td>
                        <td class="py-2 px-2 text-right font-semibold text-rose-500">{{ row.losses }}</td>
                        <td class="py-2 px-2 text-right font-semibold text-slate-600">{{ goalsFor(row) }}</td>
                        <td class="py-2 px-2 text-right font-semibold text-slate-600">{{ goalsAgainst(row) }}</td>
                        <td class="py-2 px-2 text-right font-bold"
                            :class="(goalsFor(row) - goalsAgainst(row)) >= 0 ? 'text-emerald-600' : 'text-rose-500'">
                            {{ goalDiff(row) }}
                        </td>
                        <td class="py-2 px-2 text-right font-black text-slate-800 text-sm">{{ row.points }}</td>

                        <!-- Forme -->
                        <td class="py-2 pl-4">
                            <div class="flex gap-0.5">
                                    <span v-for="(r, i) in recentFormFor(row.id)" :key="i"
                                          class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-black"
                                          :class="r.class">
                                        {{ r.label }}
                                    </span>
                                <span v-if="!recentFormFor(row.id).length" class="text-[10px] text-slate-300">—</span>
                            </div>
                        </td>

                        <!-- Barre progression -->
                        <td class="py-2 pl-4 w-32">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all"
                                         :class="team?.id === row.id ? 'bg-teal-500' : 'bg-slate-400'"
                                         :style="{ width: (((row.points ?? 0) / maxPoints) * 100) + '%' }">
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-400 w-4 text-right">{{ row.points }}</span>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
