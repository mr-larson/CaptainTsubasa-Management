<script setup>
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import TeamDetailContent from './components/TeamDetailContent.vue';
import Panel from '@/Components/Panel.vue';
import TeamLogo from '@/Components/TeamLogo.vue';

const props = defineProps({
    standings: { type: Array,  required: true },
    team:      { type: Object, default: null },
    matches:   { type: Array,  default: () => [] },
});

const selectedTeam = ref(null);
const selectedRank = computed(() => props.standings.findIndex(row => row.id === selectedTeam.value?.id) + 1);
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

// Buts pour/contre : maintenus sur game_teams et fournis par chaque ligne du classement.
const goalsFor     = (row) => row.goals_for     ?? 0;
const goalsAgainst = (row) => row.goals_against ?? 0;

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
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- Mon classement -->
        <Panel v-if="myStanding" title="Ta position">
            <div class="flex flex-wrap items-center gap-4 sm:gap-6">

                <!-- Rang -->
                <div class="text-center shrink-0">
                    <div class="text-4xl font-black"
                         :class="myRank === 1 ? 'text-yellow-500' : myRank <= 3 ? 'text-amber-500' : myRank <= 6 ? 'text-teal-500' : 'text-slate-500'">
                        {{ myRank }}<sup class="text-xl">e</sup>
                    </div>
                    <div class="text-2xs text-slate-400 font-semibold">/ {{ standings.length }}</div>
                </div>

                <!-- Logo + nom -->
                <div class="flex items-center gap-3 shrink-0">
                    <TeamLogo :team="team" size="xl" shape="rounded" fallback="stadium" />
                    <div>
                        <div class="text-sm font-bold text-slate-700">{{ team?.name }}</div>
                        <div class="text-xs text-slate-400">
                            {{ myStanding.wins }}V · {{ myStanding.draws }}N · {{ myStanding.losses }}D
                        </div>
                    </div>
                </div>

                <!-- Stats rapides -->
                <div class="flex gap-4 flex-1">
                    <div class="text-center">
                        <div class="text-xl font-black text-slate-700">{{ myStanding.average_overall ?? '—' }}</div>
                        <div class="text-2xs text-slate-400 font-semibold">Note d’équipe</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-black text-teal-600">{{ myStanding.points }}</div>
                        <div class="text-2xs text-slate-400 font-semibold">Points</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-black text-slate-700">{{ goalsFor(myStanding) }}</div>
                        <div class="text-2xs text-slate-400 font-semibold">Buts marqués</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-black text-slate-700">{{ goalsAgainst(myStanding) }}</div>
                        <div class="text-2xs text-slate-400 font-semibold">Buts encaissés</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-black"
                             :class="(goalsFor(myStanding) - goalsAgainst(myStanding)) >= 0 ? 'text-emerald-600' : 'text-rose-500'">
                            {{ goalDiff(myStanding) }}
                        </div>
                        <div class="text-2xs text-slate-400 font-semibold">Diff. buts</div>
                    </div>
                </div>

                <!-- Forme récente -->
                <div class="shrink-0">
                    <div class="text-2xs text-slate-400 font-semibold mb-1.5">Forme récente</div>
                    <div class="flex gap-1">
                        <span v-for="(r, i) in recentFormFor(team?.id)" :key="i"
                              class="w-6 h-6 rounded-full flex items-center justify-center text-2xs font-black"
                              :class="r.class">
                            {{ r.label }}
                        </span>
                        <span v-if="!recentFormFor(team?.id).length" class="text-xs text-slate-500 italic">—</span>
                    </div>
                </div>
            </div>
        </Panel>

        <!-- Tableau classement -->
        <Panel title="Classement général">

            <div class="overflow-x-auto">
                <table class="w-full text-xs min-w-max text-left">
                    <thead class="border-b border-slate-200 text-2xs font-bold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="py-2 w-8 text-center">#</th>
                        <th class="py-2 pr-4">Équipe</th>
                        <th class="py-2 px-2 text-right"><abbr title="Moyenne des notes de l’effectif" class="cursor-help no-underline">Note</abbr></th>
                        <th class="py-2 px-2 text-right"><abbr title="Matchs joués" class="cursor-help no-underline">J</abbr></th>
                        <th class="py-2 px-2 text-right"><abbr title="Victoires" class="cursor-help no-underline">V</abbr></th>
                        <th class="py-2 px-2 text-right"><abbr title="Matchs nuls" class="cursor-help no-underline">N</abbr></th>
                        <th class="py-2 px-2 text-right"><abbr title="Défaites" class="cursor-help no-underline">D</abbr></th>
                        <th class="py-2 px-2 text-right"><abbr title="Buts pour (marqués)" class="cursor-help no-underline">BP</abbr></th>
                        <th class="py-2 px-2 text-right"><abbr title="Buts contre (encaissés)" class="cursor-help no-underline">BC</abbr></th>
                        <th class="py-2 px-2 text-right"><abbr title="Différence de buts (BP − BC)" class="cursor-help no-underline">Diff</abbr></th>
                        <th class="py-2 px-2 text-right"><abbr title="Points : 3 par victoire, 1 par nul" class="cursor-help no-underline">Pts</abbr></th>
                        <th class="py-2 pl-4">Forme</th>
                        <th class="py-2 pl-4 w-32">Progression</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(row, index) in standings" :key="row.id"
                        class="cursor-pointer border-b border-slate-200 last:border-0 transition-colors hover:bg-teal-100/60"
                        @click="selectedTeam = row"
                        :class="team?.id === row.id ? 'bg-teal-50' : index % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'">

                        <!-- Rang -->
                        <td class="py-2 text-center">
                            <span v-if="rankIcon(index + 1)" class="text-base">{{ rankIcon(index + 1) }}</span>
                            <span v-else class="font-bold text-slate-400">{{ index + 1 }}</span>
                        </td>

                        <!-- Équipe -->
                        <td class="py-2 pr-4">
                            <div class="flex items-center gap-2">
                                <TeamLogo :team="row" shape="rounded" fallback="none" />
                                <button type="button" aria-haspopup="dialog" :aria-label="`Voir la fiche de ${row.name}`"
                                        class="max-w-[110px] truncate rounded text-left font-semibold focus-visible:ring-2 focus-visible:ring-teal-500"
                                        @click.stop="selectedTeam = row"
                                      :class="team?.id === row.id ? 'text-teal-700' : 'text-slate-700'">
                                        {{ row.name }}
                                </button>
                                <span v-if="team?.id === row.id"
                                      class="text-3xs bg-teal-500 text-white px-1.5 py-0.5 rounded-full font-bold shrink-0">
                                        MOI
                                    </span>
                            </div>
                        </td>

                        <!-- Stats -->
                        <td class="py-2 px-2 text-right font-bold tabular-nums text-slate-700">{{ row.average_overall ?? '—' }}</td>
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
                        <td class="py-2 px-2 text-right font-black text-slate-700 text-sm">{{ row.points }}</td>

                        <!-- Forme -->
                        <td class="py-2 pl-4">
                            <div class="flex gap-0.5">
                                    <span v-for="(r, i) in recentFormFor(row.id)" :key="i"
                                          class="w-5 h-5 rounded-full flex items-center justify-center text-3xs font-black"
                                          :class="r.class">
                                        {{ r.label }}
                                    </span>
                                <span v-if="!recentFormFor(row.id).length" class="text-2xs text-slate-300">—</span>
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
                                <span class="text-2xs text-slate-400 w-4 text-right">{{ row.points }}</span>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </Panel>
        <Modal :show="!!selectedTeam" max-width="2xl" title="Fiche de l’équipe" @close="selectedTeam = null">
            <template v-if="selectedTeam">

                <TeamDetailContent :team="selectedTeam" :rank="selectedRank" :total="standings.length" />
            </template>
        </Modal>
    </div>
</template>
