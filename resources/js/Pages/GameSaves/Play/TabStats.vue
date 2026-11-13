<script setup>
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import TeamDetailContent from './components/TeamDetailContent.vue';
import PlayerDetailContent from './components/PlayerDetailContent.vue';
import { fieldClass } from '@/Components/fieldStyles';
import { usePlayerUtils } from './usePlayerUtils.js';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';

const props = defineProps({
    teams:             { type: Array,   required: true },
    team:              { type: Object,  default: null },
    playerSeasonStats: { type: Object,  default: () => ({}) },
});

// ==========================
//   HELPERS
// ==========================
const { playerPhotoUrl, teamLogoUrl } = usePlayerUtils();


// ==========================
//   ROSTER GLOBAL (tous joueurs de toutes équipes)
// ==========================
const allPlayers = computed(() => {
    const players = [];
    for (const t of (props.teams ?? [])) {
        for (const c of (t.contracts ?? [])) {
            const p = c.game_player ?? c.gamePlayer ?? c.player ?? null;
            if (!p) continue;
            players.push({ ...p, teamId: t.id, teamName: t.name, teamLogoPath: t.logo_path });
        }
    }
    return players;
});

// Enrichit chaque joueur avec ses stats saison
const allPlayersWithStats = computed(() =>
    allPlayers.value.map(p => ({
        ...p,
        perf: props.playerSeasonStats?.[p.id] ?? props.playerSeasonStats?.[String(p.id)] ?? props.playerSeasonStats?.[+p.id] ?? null,
    }))
);

const isMyTeam = (teamId) => teamId === props.team?.id;

// ==========================
//   FILTRE ÉQUIPE
// ==========================
const selectedPlayer = ref(null);
const selectedTeam = ref(null);
const selectedTeamFilter = ref(null)

const filteredPlayersWithStats = computed(() =>
    selectedTeamFilter.value
        ? allPlayersWithStats.value.filter(p => p.teamId === selectedTeamFilter.value)
        : allPlayersWithStats.value
)

const topByFiltered = (getter, n = 5, filter = null) =>
    [...filteredPlayersWithStats.value]
        .filter(p => p.perf && (filter ? filter(p) : true))
        .sort((a, b) => getter(b) - getter(a) || (b.perf?.duelsWon ?? 0) - (a.perf?.duelsWon ?? 0))
        .slice(0, n)

// ==========================
//   TOP PERFORMERS
// ==========================
const isGK = (p) => p?.position?.toLowerCase().includes('goalkeeper');

const topShooters  = computed(() => topByFiltered(p => p.perf?.offense?.goals ?? p.perf?.offense?.shot?.success ?? 0, 5, p => !isGK(p)));
const topPassers   = computed(() => topByFiltered(p => p.perf?.offense?.pass?.success ?? 0, 5, p => !isGK(p)));
const topDribblers = computed(() => topByFiltered(p => p.perf?.offense?.dribble?.success ?? 0, 5, p => !isGK(p)));
const topDefenders = computed(() => topByFiltered(p => p.perf?.duelsWon ?? 0, 5, p => !p.position?.toLowerCase().includes('goalkeeper')));
const topKeepers = computed(() => topByFiltered(
    p => (p.perf?.defense?.hands?.attempts ?? 0) + (p.perf?.defense?.punch?.attempts ?? 0),
    5,
    p => p.position?.toLowerCase().includes('goalkeeper')
));
const hasAnyStats = computed(() =>
    allPlayersWithStats.value.some(p => p.perf)
);

// ==========================
//   COMPARATIF ÉQUIPES
// ==========================
const sortKey  = ref('shots');
const sortDesc = ref(true);

const teamComparative = computed(() => {
    return (props.teams ?? []).map(t => {
        const playerIds = (t.contracts ?? [])
            .map(c => c.game_player?.id ?? c.gamePlayer?.id ?? c.player?.id)
            .filter(Boolean);

        const totals = { shots: 0, shotSuccess: 0, passes: 0, crosses: 0, dribbles: 0, intercepts: 0, tackles: 0, blocks: 0, headings: 0, saves: 0, duelsWon: 0, duelsLost: 0 };

        for (const pid of playerIds) {
            const s = props.playerSeasonStats?.[pid];
            if (!s) continue;
            totals.shots      += s.offense?.shot?.attempts      ?? 0;
            totals.shotSuccess += s.offense?.shot?.success      ?? 0;
            totals.passes     += s.offense?.pass?.attempts      ?? 0;
            totals.crosses    += s.offense?.cross?.attempts     ?? 0;
            totals.dribbles   += s.offense?.dribble?.attempts   ?? 0;
            totals.intercepts += s.defense?.intercept?.attempts ?? 0;
            totals.tackles    += s.defense?.tackle?.attempts    ?? 0;
            totals.blocks     += s.defense?.block?.attempts     ?? 0;
            totals.headings   += s.defense?.heading?.attempts   ?? 0;
            totals.saves += (s.defense?.hands?.attempts ?? 0) + (s.defense?.punch?.attempts ?? 0);
            totals.duelsWon   += s.duelsWon  ?? 0;
            totals.duelsLost  += s.duelsLost ?? 0;
        }

        return { ...t, ...totals };
    }).sort((a, b) => sortDesc.value ? b[sortKey.value] - a[sortKey.value] : a[sortKey.value] - b[sortKey.value]);
});

const setSort = (key) => {
    if (sortKey.value === key) sortDesc.value = !sortDesc.value;
    else { sortKey.value = key; sortDesc.value = true; }
};

const sortIcon = (key) => {
    if (sortKey.value !== key) return '↕';
    return sortDesc.value ? '↓' : '↑';
};
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- Pas de stats disponibles -->
        <div v-if="!hasAnyStats"
             class="flex-1 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 p-12 text-center gap-3">
            <span class="text-4xl">📊</span>
            <p class="text-slate-600 font-semibold">Aucune statistique disponible</p>
            <p class="text-xs text-slate-500 max-w-sm leading-relaxed">
                Les stats de saison sont générées lors des matchs joués manuellement.
                Joue quelques matchs pour voir apparaître les classements !
            </p>
        </div>

        <template v-else>

            <!-- ============================================ -->
            <!-- BLOC 1 : TOP PERFORMERS                      -->
            <!-- ============================================ -->
            <!-- Filtre du palmarès -->
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
                <label class="flex w-full flex-col gap-2 text-xs font-semibold text-slate-600 sm:w-auto sm:flex-row sm:items-center">
                    Palmarès des joueurs
                    <select v-model="selectedTeamFilter" aria-label="Équipe du palmarès"
                            class="w-full sm:w-48 px-3 py-2" :class="fieldClass({ size: 'sm' })">
                        <option :value="null">Toutes les équipes</option>
                        <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </label>
                <button v-if="team" type="button" @click="selectedTeamFilter = selectedTeamFilter === team.id ? null : team.id"
                        :aria-pressed="selectedTeamFilter === team.id"
                        class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="selectedTeamFilter === team.id ? 'border-teal-300 bg-teal-50 text-teal-800' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100'">
                    Mon équipe
                </button>
            </div>
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">🏆 Meilleurs joueurs <span class="normal-case font-semibold text-slate-400 tracking-normal">· cumul de tous les matchs joués de la partie</span></h3>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div v-for="(category, ci) in [
                            { label: 'Buteurs',    icon: '⚽', color: 'border-blue-200 bg-blue-50',     badge: 'bg-blue-500',    players: topShooters,  stat: p => p.perf?.offense?.goals ?? p.perf?.offense?.shot?.success ?? 0,            unit: 'buts' },
                            { label: 'Passeurs',   icon: '🎯', color: 'border-sky-200 bg-sky-50',       badge: 'bg-sky-500',     players: topPassers,   stat: p => p.perf?.offense?.pass?.success ?? 0,     unit: 'passes' },
                            { label: 'Dribbleurs', icon: '🔥', color: 'border-orange-200 bg-orange-50', badge: 'bg-orange-500',  players: topDribblers, stat: p => p.perf?.offense?.dribble?.success ?? 0,  unit: 'dribbles' },
                            { label: 'Duel',       icon: '⚔️', color: 'border-emerald-200 bg-emerald-50',badge:'bg-emerald-500', players: topDefenders, stat: p => p.perf?.duelsWon ?? 0,                   unit: 'duels' },
                            { label: 'Gardiens', icon: '🧤', color: 'border-violet-200 bg-violet-50', badge: 'bg-violet-500', players: topKeepers, stat: p => (p.perf?.defense?.hands?.attempts ?? 0) + (p.perf?.defense?.punch?.attempts ?? 0), unit: 'arrêts' },
                        ]" :key="ci"
                         class="border rounded-xl p-3 flex flex-col gap-2"
                         :class="category.color"
                    >
                        <div class="flex items-center gap-1.5 mb-1">
                            <span>{{ category.icon }}</span>
                            <span class="text-xs font-bold text-slate-600">{{ category.label }}</span>
                        </div>

                        <button type="button" v-for="(p, rank) in category.players" :key="p.id"
                             class="flex w-full items-center gap-2 py-1 px-2 rounded-lg text-left transition-all hover:shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600"
                             @click="selectedPlayer = p"
                             :aria-label="`Voir le détail de ${p.firstname ?? ''} ${p.lastname}`"
                             :class="isMyTeam(p.teamId) ? 'bg-teal-100 border border-teal-200' : 'bg-white/70'"
                        >
                            <!-- Rang -->
                            <div class="w-5 h-5 rounded-full flex items-center justify-center text-2xs font-black text-white shrink-0"
                                 :class="rank === 0 ? 'bg-yellow-400' : rank === 1 ? 'bg-slate-400' : rank === 2 ? 'bg-amber-600' : category.badge">
                                {{ rank + 1 }}
                            </div>

                            <!-- Photo -->
                            <div class="w-6 h-6 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <PlayerPhoto :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt="" />
                                <div v-if="!playerPhotoUrl(p)" class="w-full h-full flex items-center justify-center text-[8px] text-slate-400">?</div>
                            </div>

                            <!-- Nom + équipe -->
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] font-semibold truncate" :class="isMyTeam(p.teamId) ? 'text-teal-800' : 'text-slate-700'">
                                    {{ p.lastname }}
                                </div>
                                <div class="text-3xs opacity-60 truncate">{{ p.teamName }}</div>
                            </div>

                            <!-- Valeur -->
                            <div class="text-xs font-black shrink-0" :class="isMyTeam(p.teamId) ? 'text-teal-600' : 'text-slate-600'">
                                {{ category.stat(p) }}
                            </div>
                        </button>

                        <p v-if="!category.players.length" class="text-2xs text-slate-500 italic text-center py-2">
                            Aucune donnée
                        </p>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- BLOC 2 : COMPARATIF ÉQUIPES                 -->
            <!-- ============================================ -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">📋 Comparatif équipes</h3>

                <div class="overflow-x-auto" role="region" aria-label="Comparatif des équipes, défilement horizontal" tabindex="0">
                    <table class="w-full text-xs min-w-max text-left">
                        <thead class="border-b border-slate-200 text-2xs font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="sticky left-0 z-20 bg-slate-50 py-2 pr-4 shadow-[2px_0_4px_-2px_rgb(15_23_42_/_15%)]">Équipe</th>
                            <th v-for="col in [
                                    { key: 'shots',     label: 'Tirs'    },
                                    { key: 'shotSuccess',label: 'Buts' },
                                    { key: 'passes',    label: 'Passes'  },
                                    { key: 'crosses',   label: 'Centres' },
                                    { key: 'dribbles',  label: 'Dribbles'},
                                    { key: 'saves', label: 'Arrêts' },
                                    { key: 'intercepts',label: 'Interc.', title: 'Interceptions' },
                                    { key: 'tackles',   label: 'Tacles'  },
                                    { key: 'blocks',    label: 'Contres', title: 'Tirs contrés' },
                                    { key: 'headings',  label: 'Têtes', title: 'Têtes défensives' },
                                    { key: 'duelsWon',  label: 'Duels G.', title: 'Duels gagnés' },
                                    { key: 'duelsLost', label: 'Duels P.', title: 'Duels perdus' },
                                ]" :key="col.key"
                                :title="col.title ?? null"
                                class="py-2 px-2 text-right cursor-pointer select-none hover:text-teal-600 transition-colors"
                                :class="sortKey === col.key ? 'text-teal-600 font-bold' : 'text-slate-500 font-semibold'"
                                @click="setSort(col.key)" tabindex="0" @keydown.enter.prevent="setSort(col.key)"
                            >
                                {{ col.label }} <span>{{ sortIcon(col.key) }}</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(t, i) in teamComparative" :key="t.id"
                            class="border-b border-slate-200 last:border-0 transition-colors"
                            :class="isMyTeam(t.id) ? 'bg-teal-50' : i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'"
                        >
                            <!-- Équipe -->
                            <td class="sticky left-0 z-10 py-2 pr-4 shadow-[2px_0_4px_-2px_rgb(15_23_42_/_15%)]"
                                :class="isMyTeam(t.id) ? 'bg-teal-50' : i % 2 === 0 ? 'bg-white' : 'bg-slate-50'">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded overflow-hidden bg-white border border-slate-200 shrink-0">
                                        <TeamLogoImage loading="lazy" decoding="async" v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" class="w-full h-full" alt=""/>
                                    </div>
                                    <button type="button" class="font-semibold truncate max-w-[120px] text-left hover:underline focus-visible:outline-teal-600"
                                            :class="isMyTeam(t.id) ? 'text-teal-700' : 'text-slate-700'"
                                            aria-haspopup="dialog" @click="selectedTeam = teams.find(team => team.id === t.id)">
                                            {{ t.name }}
                                        </button>
                                    <span v-if="isMyTeam(t.id)" class="text-3xs bg-teal-500 text-white px-1.5 py-0.5 rounded-full font-bold shrink-0">
                                            MON ÉQUIPE
                                        </span>
                                </div>
                            </td>

                            <td v-for="col in ['shots','shotSuccess','passes','crosses','dribbles','saves','intercepts','tackles','blocks','headings','duelsWon','duelsLost']"
                                :key="col"
                                class="py-2 px-2 text-right font-semibold"
                                :class="[
                                        sortKey === col ? 'text-teal-600' : 'text-slate-600',
                                        isMyTeam(t.id) ? 'text-teal-700' : ''
                                    ]"
                            >
                                {{ t[col] || '—' }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </template>
        <Modal :show="!!selectedTeam" title="Fiche de l’équipe" max-width="2xl" @close="selectedTeam = null">
            <TeamDetailContent v-if="selectedTeam" :team="selectedTeam" :total="teams.length" />
        </Modal>
        <Modal :show="!!selectedPlayer" title="Fiche du joueur" :subtitle="selectedPlayer?.teamName" max-width="lg" @close="selectedPlayer = null">
            <PlayerDetailContent v-if="selectedPlayer" :player="selectedPlayer" />
        </Modal>
    </div>
</template>
