<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    teams:             { type: Array,   required: true },
    team:              { type: Object,  default: null },
    playerSeasonStats: { type: Object,  default: () => ({}) },
});

// ==========================
//   HELPERS
// ==========================
const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path)         return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
};

const teamLogoUrl = (t) => {
    const path = t?.logo_path ?? t?.team?.logo_path;
    if (!path) return null;
    if (path.startsWith('http')) return path;
    if (path.startsWith('/'))    return path;
    if (path.startsWith('teams/')) return '/images/' + path;
    return '/' + path;
};

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
//   TOP PERFORMERS
// ==========================
const topBy = (getter, n = 5) =>
    [...allPlayersWithStats.value]
        .filter(p => p.perf)
        .sort((a, b) => getter(b) - getter(a))
        .slice(0, n);

const topShooters = computed(() => topBy(p => p.perf?.offense?.goals ?? 0));
const topPassers   = computed(() => topBy(p => p.perf?.offense?.pass?.success    ?? 0));
const topDribblers = computed(() => topBy(p => p.perf?.offense?.dribble?.success ?? 0));
const topDefenders = computed(() => topBy(p => p.perf?.duelsWon                 ?? 0));

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

        const totals = { shots: 0, shotSuccess: 0, passes: 0, dribbles: 0, intercepts: 0, tackles: 0, blocks: 0, duelsWon: 0, duelsLost: 0 };

        for (const pid of playerIds) {
            const s = props.playerSeasonStats?.[pid];
            if (!s) continue;
            totals.shots      += s.offense?.shot?.attempts      ?? 0;
            totals.shotSuccess += s.offense?.shot?.success      ?? 0;
            totals.passes     += s.offense?.pass?.attempts      ?? 0;
            totals.dribbles   += s.offense?.dribble?.attempts   ?? 0;
            totals.intercepts += s.defense?.intercept?.attempts ?? 0;
            totals.tackles    += s.defense?.tackle?.attempts    ?? 0;
            totals.blocks     += s.defense?.block?.attempts     ?? 0;
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

// ==========================
//   MES JOUEURS DANS LES CLASSEMENTS
// ==========================
const myPlayersRanking = computed(() => {
    if (!props.team) return [];

    const categories = [
        { label: 'Tirs réussis',   icon: '⚽', getter: p => p.perf?.offense?.shot?.success    ?? 0 },
        { label: 'Passes réussies',icon: '🎯', getter: p => p.perf?.offense?.pass?.success    ?? 0 },
        { label: 'Dribbles réussis',icon:'🔥', getter: p => p.perf?.offense?.dribble?.success ?? 0 },
        { label: 'Duels gagnés',   icon: '⚔️', getter: p => p.perf?.duelsWon                 ?? 0 },
        { label: 'Interceptions',  icon: '🛡️', getter: p => p.perf?.defense?.intercept?.success ?? 0 },
    ];

    return categories.map(cat => {
        const sorted = [...allPlayersWithStats.value]
            .filter(p => p.perf)
            .sort((a, b) => cat.getter(b) - cat.getter(a));

        const myBest = sorted.find(p => p.teamId === props.team.id);
        if (!myBest || cat.getter(myBest) === 0) return null;

        const rank = sorted.indexOf(myBest) + 1;
        return { ...cat, player: myBest, rank, total: sorted.length, value: cat.getter(myBest) };
    }).filter(Boolean);
});
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- Pas de stats disponibles -->
        <div v-if="!hasAnyStats"
             class="flex-1 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 p-12 text-center gap-3">
            <span class="text-4xl">📊</span>
            <p class="text-slate-600 font-semibold">Aucune statistique disponible</p>
            <p class="text-xs text-slate-400 max-w-sm leading-relaxed">
                Les stats de saison sont générées lors des matchs joués manuellement.
                Joue quelques matchs pour voir apparaître les classements !
            </p>
        </div>

        <template v-else>

            <!-- ============================================ -->
            <!-- BLOC 1 : TOP PERFORMERS                      -->
            <!-- ============================================ -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">🏆 Top performers</h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">

                    <!-- Colonne top -->
                    <div v-for="(category, ci) in [
                        { label: 'Buteurs',    icon: '⚽', color: 'border-blue-200 bg-blue-50',    badge: 'bg-blue-500',    players: topShooters,  stat: p => p.perf?.offense?.goals ?? 0, unit: 'buts' },
                        { label: 'Passeurs',   icon: '🎯', color: 'border-sky-200 bg-sky-50',      badge: 'bg-sky-500',     players: topPassers,   stat: p => p.perf?.offense?.pass?.success ?? 0,    unit: 'passes' },
                        { label: 'Dribbleurs', icon: '🔥', color: 'border-orange-200 bg-orange-50',badge: 'bg-orange-500',  players: topDribblers, stat: p => p.perf?.offense?.dribble?.success ?? 0, unit: 'dribbles' },
                        { label: 'Duel', icon: '⚔️', color: 'border-emerald-200 bg-emerald-50',badge:'bg-emerald-500',players: topDefenders, stat: p => p.perf?.duelsWon ?? 0,                  unit: 'duels' },
                    ]" :key="ci"
                         class="border rounded-xl p-3 flex flex-col gap-2"
                         :class="category.color"
                    >
                        <div class="flex items-center gap-1.5 mb-1">
                            <span>{{ category.icon }}</span>
                            <span class="text-xs font-bold text-slate-600">{{ category.label }}</span>
                        </div>

                        <div v-for="(p, rank) in category.players" :key="p.id"
                             class="flex items-center gap-2 py-1 px-2 rounded-lg transition-all"
                             :class="isMyTeam(p.teamId) ? 'bg-teal-100 border border-teal-200' : 'bg-white/70'"
                        >
                            <!-- Rang -->
                            <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black text-white shrink-0"
                                 :class="rank === 0 ? 'bg-yellow-400' : rank === 1 ? 'bg-slate-400' : rank === 2 ? 'bg-amber-600' : category.badge">
                                {{ rank + 1 }}
                            </div>

                            <!-- Photo -->
                            <div class="w-6 h-6 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt=""/>
                                <div v-else class="w-full h-full flex items-center justify-center text-[8px] text-slate-400">?</div>
                            </div>

                            <!-- Nom + équipe -->
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] font-semibold truncate" :class="isMyTeam(p.teamId) ? 'text-teal-800' : 'text-slate-700'">
                                    {{ p.lastname }}
                                </div>
                                <div class="text-[9px] opacity-60 truncate">{{ p.teamName }}</div>
                            </div>

                            <!-- Valeur -->
                            <div class="text-xs font-black shrink-0" :class="isMyTeam(p.teamId) ? 'text-teal-600' : 'text-slate-600'">
                                {{ category.stat(p) }}
                            </div>
                        </div>

                        <p v-if="!category.players.length" class="text-[10px] text-slate-400 italic text-center py-2">
                            Aucune donnée
                        </p>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- BLOC 2 : MES JOUEURS DANS LES CLASSEMENTS   -->
            <!-- ============================================ -->
            <div v-if="myPlayersRanking.length" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                    ⭐ {{ team?.name }} dans les classements
                </h3>
                <div class="flex flex-wrap gap-2">
                    <div v-for="entry in myPlayersRanking" :key="entry.label"
                         class="flex items-center gap-2 px-3 py-2 rounded-xl border border-teal-200 bg-teal-50">
                        <span>{{ entry.icon }}</span>
                        <div class="w-6 h-6 rounded-full overflow-hidden bg-slate-200 shrink-0">
                            <img v-if="playerPhotoUrl(entry.player)" :src="playerPhotoUrl(entry.player)" class="w-full h-full object-cover" alt=""/>
                            <div v-else class="w-full h-full flex items-center justify-center text-[8px] text-slate-400">?</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-teal-800">{{ entry.player.lastname }}</div>
                            <div class="text-[10px] text-teal-600">{{ entry.label }}</div>
                        </div>
                        <div class="ml-1 text-center">
                            <div class="text-lg font-black leading-none"
                                 :class="entry.rank === 1 ? 'text-yellow-500' : entry.rank <= 3 ? 'text-amber-500' : 'text-teal-600'">
                                {{ entry.rank }}<sup class="text-xs">e</sup>
                            </div>
                            <div class="text-[9px] text-slate-400">/ {{ entry.total }}</div>
                        </div>
                        <div class="text-xs font-black text-slate-500 ml-1">{{ entry.value }}</div>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- BLOC 3 : COMPARATIF ÉQUIPES                 -->
            <!-- ============================================ -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">📋 Comparatif équipes</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs min-w-max text-left">
                        <thead class="border-b border-slate-200">
                        <tr>
                            <th class="py-2 pr-4 text-slate-500 font-semibold">Équipe</th>
                            <th v-for="col in [
                                    { key: 'shots',     label: 'Tirs'    },
                                    { key: 'shotSuccess',label: '⚽ Buts' },
                                    { key: 'passes',    label: 'Passes'  },
                                    { key: 'dribbles',  label: 'Dribbles'},
                                    { key: 'intercepts',label: 'Interc.' },
                                    { key: 'tackles',   label: 'Tacles'  },
                                    { key: 'blocks',    label: 'Blocks'  },
                                    { key: 'duelsWon',  label: 'D. Won'  },
                                    { key: 'duelsLost', label: 'D. Lost' },
                                ]" :key="col.key"
                                class="py-2 px-2 text-right cursor-pointer select-none hover:text-teal-600 transition-colors"
                                :class="sortKey === col.key ? 'text-teal-600 font-bold' : 'text-slate-500 font-semibold'"
                                @click="setSort(col.key)"
                            >
                                {{ col.label }} <span class="text-[10px]">{{ sortIcon(col.key) }}</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(t, i) in teamComparative" :key="t.id"
                            class="border-b border-slate-100 last:border-0 transition-colors"
                            :class="isMyTeam(t.id) ? 'bg-teal-50' : i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'"
                        >
                            <!-- Équipe -->
                            <td class="py-2 pr-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded overflow-hidden bg-white border border-slate-100 shrink-0">
                                        <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" class="w-full h-full object-contain" alt=""/>
                                    </div>
                                    <span class="font-semibold truncate max-w-[120px]"
                                          :class="isMyTeam(t.id) ? 'text-teal-700' : 'text-slate-700'">
                                            {{ t.name }}
                                        </span>
                                    <span v-if="isMyTeam(t.id)" class="text-[9px] bg-teal-500 text-white px-1.5 py-0.5 rounded-full font-bold shrink-0">
                                            MON ÉQUIPE
                                        </span>
                                </div>
                            </td>

                            <td v-for="col in ['shots','shotSuccess','passes','dribbles','intercepts','tackles','blocks','duelsWon','duelsLost']"
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
    </div>
</template>
