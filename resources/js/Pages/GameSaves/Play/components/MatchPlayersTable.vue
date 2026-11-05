<script setup>
import { computed, ref } from 'vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';
import { usePlayerUtils } from '../usePlayerUtils.js';

/**
 * Stats individuelles d'une équipe sur un match : titulaires puis entrants,
 * joueurs non utilisés masqués, réussites / tentatives, tri par colonne.
 */
const props = defineProps({
    players:  { type: Array,  default: () => [] },   // effectif du camp (instantané du match ou actuel)
    stats:    { type: Object, default: () => ({}) }, // match_stats.players
    starters: { type: Array,  default: null },       // ids du 11 de départ (match_stats.starters[side])
    playtime: { type: Object, default: null },       // tours joués par joueur (match_stats.playtime)
    motmId:   { type: [Number, String], default: null },
});

const { playerPhotoUrl } = usePlayerUtils();

const isGK = (p) => String(p?.position ?? '').toLowerCase().includes('goal');
const stat = (pid, path) => {
    const s = props.stats?.[pid] ?? props.stats?.[String(pid)];
    if (!s) return 0;
    return path.split('.').reduce((o, k) => o?.[k] ?? 0, s);
};
const pair = (pid, key) => ({ ok: stat(pid, `${key}.success`), tot: stat(pid, `${key}.attempts`) });

const starterSet = computed(() => new Set((props.starters ?? []).map(String)));
const minutesOf  = (pid) => props.playtime?.[pid] ?? props.playtime?.[String(pid)] ?? null;

const rows = computed(() => props.players.map(p => {
    const pid  = p.id;
    const def  = ['intercept', 'tackle', 'block'].map(k => pair(pid, `defense.${k}`));
    const won  = stat(pid, 'duelsWon');
    const lost = stat(pid, 'duelsLost');
    const minutes = minutesOf(pid);
    const starter = props.starters ? starterSet.value.has(String(pid)) : null;
    const anyAction = won + lost + stat(pid, 'offense.pass.attempts') + stat(pid, 'offense.shot.attempts')
        + stat(pid, 'defense.hands.attempts') + stat(pid, 'defense.punch.attempts') > 0;
    return {
        p, pid, starter, minutes,
        used:     starter === true || (minutes ?? 0) > 0 || anyAction,
        gk:       isGK(p),
        goals:    stat(pid, 'offense.goals'),
        shot:     pair(pid, 'offense.shot'),
        pass:     pair(pid, 'offense.pass'),
        dribble:  pair(pid, 'offense.dribble'),
        saves:    stat(pid, 'defense.hands.success') + stat(pid, 'defense.punch.success') + stat(pid, 'defense.gkSpecial.success'),
        defense:  { ok: def.reduce((a, d) => a + d.ok, 0), tot: def.reduce((a, d) => a + d.tot, 0) },
        duelsPct: won + lost ? Math.round((won / (won + lost)) * 100) : null,
        won, lost,
    };
}));

// ── Tri ───────────────────────────────────────────────────────
const SORTS = {
    order:   () => 0,
    minutes: (r) => r.minutes ?? -1,
    goals:   (r) => r.goals,
    shot:    (r) => r.shot.tot,
    pass:    (r) => r.pass.ok,
    dribble: (r) => r.dribble.ok,
    saves:   (r) => r.saves,
    defense: (r) => r.defense.ok,
    duels:   (r) => r.won,
};
const sortKey = ref('order');
const toggleSort = (key) => { sortKey.value = sortKey.value === key ? 'order' : key; };

const showUnused = ref(false);
const unusedCount = computed(() => rows.value.filter(r => !r.used).length);

const visibleRows = computed(() => {
    const list = rows.value.filter(r => showUnused.value || r.used);
    // Ordre par défaut : titulaires, puis entrants, puis non utilisés.
    const rank = (r) => (r.starter === true ? 0 : r.used ? 1 : 2);
    const by = SORTS[sortKey.value];
    return [...list].sort((a, b) =>
        sortKey.value === 'order' ? rank(a) - rank(b) : (by(b) - by(a)) || rank(a) - rank(b)
    );
});

const COLUMNS = [
    { key: 'minutes', label: 'Tours',   title: 'Tours joués' },
    { key: 'goals',   label: 'Buts',    title: 'Buts marqués' },
    { key: 'shot',    label: 'Tirs',    title: 'Tirs réussis / tentés' },
    { key: 'pass',    label: 'Passes',  title: 'Passes réussies / tentées' },
    { key: 'dribble', label: 'Drib.',   title: 'Dribbles réussis / tentés' },
    { key: 'saves',   label: 'Arrêts',  title: 'Arrêts réussis (mains, poings, spécial)' },
    { key: 'defense', label: 'Déf.',    title: 'Interceptions, tacles et contres réussis / tentés' },
    { key: 'duels',   label: 'Duels',   title: 'Duels gagnés (% de réussite)' },
];
</script>

<template>
    <div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs min-w-max text-left">
                <thead class="border-b border-slate-200">
                <tr>
                    <th class="py-1.5 pr-3 text-slate-500 font-semibold">Joueur</th>
                    <th v-for="c in COLUMNS" :key="c.key" :title="`${c.title} — cliquer pour trier`"
                        class="py-1.5 px-2 text-right font-semibold cursor-pointer select-none hover:text-teal-600"
                        :class="sortKey === c.key ? 'text-teal-600' : 'text-slate-500'"
                        @click="toggleSort(c.key)">
                        {{ c.label }}<span v-if="sortKey === c.key"> ▾</span>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="r in visibleRows" :key="r.pid"
                    class="border-b border-slate-100 last:border-0 hover:bg-white transition-colors"
                    :class="r.used ? '' : 'opacity-50'">
                    <td class="py-1.5 pr-3">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full overflow-hidden bg-slate-200 shrink-0"
                                 :class="String(r.pid) === String(motmId) ? 'ring-2 ring-amber-400' : ''">
                                <PlayerPhoto :src="playerPhotoUrl(r.p)" class="w-full h-full object-cover" alt="" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-medium text-slate-700 truncate max-w-[110px]">
                                    {{ r.p.lastname }}
                                    <span v-if="String(r.pid) === String(motmId)" title="Homme du match">⭐</span>
                                </div>
                                <div class="text-3xs text-slate-400">
                                    {{ r.starter === true ? 'Titulaire' : r.starter === false && r.used ? 'Entré en jeu' : r.used ? '' : 'Non utilisé' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="py-1.5 px-2 text-right text-slate-500">{{ r.minutes ?? '—' }}</td>
                    <td class="py-1.5 px-2 text-right font-black" :class="r.goals ? 'text-emerald-600' : 'text-slate-300'">{{ r.goals || '·' }}</td>
                    <td class="py-1.5 px-2 text-right" :class="r.gk ? 'text-slate-300' : 'text-slate-600'">
                        <template v-if="r.gk">—</template><template v-else>{{ r.shot.ok }}/{{ r.shot.tot }}</template>
                    </td>
                    <td class="py-1.5 px-2 text-right text-slate-600">{{ r.pass.ok }}/{{ r.pass.tot }}</td>
                    <td class="py-1.5 px-2 text-right" :class="r.gk ? 'text-slate-300' : 'text-slate-600'">
                        <template v-if="r.gk">—</template><template v-else>{{ r.dribble.ok }}/{{ r.dribble.tot }}</template>
                    </td>
                    <td class="py-1.5 px-2 text-right font-semibold" :class="r.gk ? 'text-violet-600' : 'text-slate-300'">
                        {{ r.gk ? r.saves : '—' }}
                    </td>
                    <td class="py-1.5 px-2 text-right text-slate-600">{{ r.defense.ok }}/{{ r.defense.tot }}</td>
                    <td class="py-1.5 px-2 text-right">
                        <span class="font-bold text-emerald-600">{{ r.won }}</span>
                        <span v-if="r.duelsPct !== null" class="text-2xs text-slate-400"> ({{ r.duelsPct }} %)</span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <button v-if="unusedCount" type="button" @click="showUnused = !showUnused"
                class="mt-2 text-[11px] font-semibold text-slate-500 hover:text-teal-600">
            {{ showUnused ? '▴ Masquer' : '▾ Afficher' }} les {{ unusedCount }} joueur(s) non utilisé(s)
        </button>
    </div>
</template>
