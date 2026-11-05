<script setup>
import { computed } from 'vue';
import { usePlayerUtils } from '../usePlayerUtils.js';

/**
 * Aperçu d'un match à venir : classement, forme récente et confrontations
 * de la saison pour les deux équipes, raccourci vers la composition.
 */
const props = defineProps({
    match:      { type: Object, required: true },
    team:       { type: Object, required: true },   // équipe dont on affiche le calendrier
    teamById:   { type: Object, required: true },
    standings:  { type: Array,  default: () => [] },
    allMatches: { type: Array,  default: () => [] },
    myTeamId:   { type: [Number, String], default: null },
    label:      { type: String, default: '' },        // « Semaine 7 », « Demi-finale »…
});

const emit = defineEmits(['prepare-lineup', 'close']);

const { teamLogoUrl } = usePlayerUtils();

const isHome   = computed(() => props.match.home_team_id === props.team.id);
const opponent = computed(() => props.teamById[isHome.value ? props.match.away_team_id : props.match.home_team_id] ?? null);

const played = computed(() => props.allMatches.filter(m => m.status === 'played'));

const resultFor = (m, teamId) => {
    const home = m.home_team_id === teamId;
    const gf = home ? m.home_score : m.away_score;
    const ga = home ? m.away_score : m.home_score;
    return gf > ga ? 'V' : gf === ga ? 'N' : 'D';
};

const profile = (t) => {
    if (!t) return null;
    const idx  = props.standings.findIndex(r => r.id === t.id);
    const row  = idx >= 0 ? props.standings[idx] : null;
    const form = played.value
        .filter(m => m.home_team_id === t.id || m.away_team_id === t.id)
        .sort((a, b) => b.week - a.week)
        .slice(0, 5)
        .map(m => resultFor(m, t.id))
        .reverse();
    return {
        team: t,
        rank: idx >= 0 ? idx + 1 : null,
        points: row?.points ?? 0,
        record: row ? `${row.wins}V ${row.draws}N ${row.losses}D` : '—',
        goals:  row ? `${row.goals_for} - ${row.goals_against}` : '—',
        form,
    };
};

const sides = computed(() => {
    const mine  = profile(props.team);
    const other = profile(opponent.value);
    return isHome.value ? [mine, other] : [other, mine];
});

// Confrontations déjà jouées cette saison entre les deux équipes
const headToHead = computed(() => played.value
    .filter(m => [m.home_team_id, m.away_team_id].includes(props.team.id)
              && [m.home_team_id, m.away_team_id].includes(opponent.value?.id))
    .sort((a, b) => a.week - b.week));

const FORM_CLASS = { V: 'bg-emerald-500 text-white', N: 'bg-slate-300 text-slate-700', D: 'bg-rose-500 text-white' };

const canPrepare = computed(() => props.myTeamId && Number(props.myTeamId) === Number(props.team.id));
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">
                Avant-match · {{ label }} · {{ isHome ? '🏠 à domicile' : '✈️ à l\'extérieur' }}
            </h4>
            <button type="button" @click="emit('close')" class="text-[11px] text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div v-for="(s, i) in sides" :key="i" class="flex flex-col items-center gap-2 text-center">
                <template v-if="s">
                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-white border border-slate-200">
                        <img v-if="teamLogoUrl(s.team)" :src="teamLogoUrl(s.team)" class="w-full h-full object-contain" alt=""/>
                    </div>
                    <div class="text-sm font-bold text-slate-700">{{ s.team.name }}</div>
                    <div class="text-xs text-slate-500">
                        <span v-if="s.rank" class="font-bold text-slate-700">{{ s.rank }}<sup>e</sup></span>
                        <span v-if="s.rank"> · </span>{{ s.points }} pts
                    </div>
                    <div class="text-[11px] text-slate-500">{{ s.record }} · buts {{ s.goals }}</div>
                    <div class="flex items-center gap-1" title="5 derniers matchs (du plus ancien au plus récent)">
                        <span v-for="(r, k) in s.form" :key="k"
                              class="w-5 h-5 rounded-full flex items-center justify-center text-3xs font-black"
                              :class="FORM_CLASS[r]">{{ r }}</span>
                        <span v-if="!s.form.length" class="text-2xs text-slate-400 italic">Pas encore joué</span>
                    </div>
                </template>
            </div>
        </div>

        <div v-if="headToHead.length" class="border-t border-slate-200 pt-3">
            <div class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Déjà joué cette saison</div>
            <div v-for="m in headToHead" :key="m.id" class="text-xs text-slate-600">
                S{{ m.week }} : {{ teamById[m.home_team_id]?.name }}
                <span class="font-black">{{ m.home_score }} - {{ m.away_score }}</span>
                {{ teamById[m.away_team_id]?.name }}
            </div>
        </div>

        <div v-if="canPrepare" class="flex justify-center">
            <button type="button" @click="emit('prepare-lineup')"
                    class="px-4 py-1.5 rounded-full text-xs font-bold bg-teal-500 text-white hover:bg-teal-600 transition-all">
                ⚙️ Préparer ma compo
            </button>
        </div>
    </div>
</template>
