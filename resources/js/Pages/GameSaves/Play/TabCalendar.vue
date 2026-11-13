<script setup>
import TabBar from '@/Components/TabBar.vue';
import { fieldClass } from '@/Components/fieldStyles';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import { computed, ref, watch, nextTick } from 'vue';
import axios from 'axios';
import Panel from '@/Components/Panel.vue';
import PlayerAvatar from '@/Components/PlayerAvatar.vue';
import TeamLogo from '@/Components/TeamLogo.vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import MatchPreview from './components/MatchPreview.vue';
import Modal from '@/Components/Modal.vue';
import PlayerIdentityCard from './components/PlayerIdentityCard.vue';
import PlayerSpecialMoves from './components/PlayerSpecialMoves.vue';
import StatBars from './components/StatBars.vue';

/**
 * Onglet « Calendrier » : où et quand. Calendrier d'une équipe, journée
 * complète (tous les matchs d'une semaine) et avant-match des matchs à venir.
 * Un match joué s'ouvre dans l'onglet « Match » (feuille, stats, déroulé…).
 */
const props = defineProps({
    calendarTeams:       { type: Array,    required: true },
    calendarTeam:        { type: Object,   default: null },
    calendarRows:        { type: Array,    required: true },
    teamById:            { type: Object,   required: true },
    isByeMatch:          { type: Function, required: true },
    opponentNameForTeam: { type: Function, required: true },
    currentWeek:         { type: Number,   default: null },
    seasonWeeksCount:    { type: Number,   default: 0 },
    standings:           { type: Array,    default: () => [] },
    allMatches:          { type: Array,    default: () => [] },
    myTeamId:            { type: [Number, String], default: null },
    // Match dont le détail est en cours de chargement / dernier ouvert
    loadingMatchId:      { type: [Number, String], default: null },
    selectedMatchId:     { type: [Number, String], default: null },
    gameSaveId:          { type: [Number, String], required: true },
});

const emit = defineEmits(['select-team', 'open-match', 'change-tab']);

const { teamLogoUrl, moraleState } = usePlayerUtils();

// ==========================
//   HELPERS
// ==========================
const ROUND_SHORT = {
    group_a: 'Pl.A', group_b: 'Pl.B', group_c: 'Pl.C', group_d: 'Pl.D',
    semi: '½', final: 'F',
};
const roundShort = (match) => ROUND_SHORT[match?.round] ?? `S${match?.week}`;
const roundTitle = (match) => ({
    group_a: 'Poule A', group_b: 'Poule B', group_c: 'Poule C', group_d: 'Poule D',
    semi: 'Demi-finale', final: 'Finale',
}[match?.round] ?? `Semaine ${match?.week}`);

const matchResult = (match, teamId) => {
    if (!match || match.status !== 'played') return null;
    const isHome  = match.home_team_id === teamId;
    const scored  = isHome ? (match.home_score ?? 0) : (match.away_score ?? 0);
    const against = isHome ? (match.away_score ?? 0) : (match.home_score ?? 0);
    if (scored > against)  return { label: 'V', bg: 'bg-emerald-500', text: 'text-white' };
    if (scored === against) return { label: 'N', bg: 'bg-slate-300',   text: 'text-slate-700' };
    return { label: 'D', bg: 'bg-rose-500', text: 'text-white' };
};

const scoreFor = (match, teamId) => {
    if (!match || match.status !== 'played') return null;
    const isHome = match.home_team_id === teamId;
    const s = isHome ? match.home_score : match.away_score;
    const a = isHome ? match.away_score : match.home_score;
    return { scored: s ?? 0, against: a ?? 0 };
};

const opponentTeam = (match, teamId) => {
    if (!match || !teamId) return null;
    const oppId = match.home_team_id === teamId ? match.away_team_id : match.home_team_id;
    return props.teamById[oppId] ?? null;
};

// ==========================
//   CLIC SUR UN MATCH
// ==========================
const isPlayable = (m) => !props.isByeMatch(m) && m.status === 'played' && m.has_stats;
const isUpcoming = (m) => !props.isByeMatch(m) && m.status === 'scheduled';

// Avant-match affiché à droite : { match, team } (team = point de vue)
const preview = ref(null);
const previewMatch = computed(() => preview.value?.match ?? null);

function openRow(match, team = props.calendarTeam) {
    if (!match || props.isByeMatch(match)) return;
    journeeWeek.value = Number(match.week);
    if (isPlayable(match)) {
        preview.value = null;
        emit('open-match', match);          // → onglet Match
    } else if (isUpcoming(match)) {
        preview.value = { match, team };
    }
}

function onTeamChange(id) {
    const t = props.calendarTeams.find(team => Number(team.id) === Number(id));
    if (t) emit('select-team', t);
    preview.value = null;
}
watch(() => props.calendarTeam?.id, () => { preview.value = null; });

// ==========================
//   JOURNÉE : tous les matchs d'une semaine
// ==========================
const journeeWeek = ref(Number(props.currentWeek) || 1);
const maxWeek = computed(() => Math.max(1, props.seasonWeeksCount || 0, ...props.allMatches.map(m => Number(m.week) || 0)));

const journeeMatches = computed(() => props.allMatches
    .filter(m => Number(m.week) === journeeWeek.value && !props.isByeMatch(m))
    .sort((a, b) => String(a.round ?? '').localeCompare(String(b.round ?? '')) || a.id - b.id));

// Équipes au repos cette semaine (ligue avec un nombre impair d'équipes)
const journeeRest = computed(() => {
    const playing = new Set(journeeMatches.value.flatMap(m => [m.home_team_id, m.away_team_id]));
    return props.calendarTeams.filter(t => !playing.has(t.id));
});

const isMine = (m) => props.myTeamId && [m.home_team_id, m.away_team_id].map(Number).includes(Number(props.myTeamId));

// ==========================
//   CLASSEMENTS DE LA JOURNÉE (chargés à la demande, par semaine)
// ==========================
const LEADER_TABS = [
    { key: 'motm',      label: '⭐ Hommes du match', unit: () => 'pts de note' },
    { key: 'scorers',   label: '⚽ Buteurs',         unit: (v) => (v > 1 ? 'buts' : 'but') },
    { key: 'passers',   label: '🎯 Passeurs',        unit: (v) => (v > 1 ? 'passes' : 'passe') },
    { key: 'dribblers', label: '🌀 Dribbleurs',      unit: (v) => (v > 1 ? 'dribbles' : 'dribble') },
    { key: 'keepers',   label: '🧤 Arrêts',          unit: (v) => (v > 1 ? 'arrêts' : 'arrêt') },
    { key: 'defenders', label: '🛡️ Défenseurs',      unit: (v) => (v > 1 ? 'ballons gagnés' : 'ballon gagné') },
    { key: 'duels',     label: '⚔️ Duels',           unit: (v) => (v > 1 ? 'duels gagnés' : 'duel gagné') },
    // Cartons et blessures de la semaine (les fautes ne sont pas conservées joueur par joueur)
    { key: 'fouls',     label: '🟨 Fautes',          unit: () => '' },
];
const leaderTab = ref('motm');
const leadersCache = ref({});
const leadersLoading = ref(false);

// La clé change quand un match de la semaine est joué : le classement est rechargé.
const leadersKey = computed(() => {
    const played = journeeMatches.value.filter(m => m.status === 'played').length;
    return `${journeeWeek.value}-${played}`;
});
const leaders = computed(() => leadersCache.value[leadersKey.value] ?? null);
const leaderRows = computed(() => leaders.value?.categories?.[leaderTab.value] ?? []);
const leaderUnit = (v) => LEADER_TABS.find(t => t.key === leaderTab.value)?.unit(v) ?? '';

watch(leadersKey, async (key) => {
    const played = journeeMatches.value.some(m => m.status === 'played');
    if (!played || leadersCache.value[key]) return;
    leadersLoading.value = true;
    try {
        const { data } = await axios.get(route('game-saves.weeks.leaders', { gameSave: props.gameSaveId, week: journeeWeek.value }));
        leadersCache.value = { ...leadersCache.value, [key]: data };
    } catch {
        // Classements indisponibles : la carte affiche simplement « aucun »
    } finally {
        leadersLoading.value = false;
    }
}, { immediate: true });

const MEDALS = ['🥇', '🥈', '🥉'];
const selectedLeader = ref(null);
const leaderPlayer = computed(() => leaders.value?.players?.[selectedLeader.value?.player_id]);
const leaderMorale = computed(() => moraleState(leaderPlayer.value?.morale));
const leaderMatch = computed(() => props.allMatches.find(m => m.id === selectedLeader.value?.match_id));

function openLeaderMatch() {
    const match = leaderMatch.value;
    selectedLeader.value = null;
    if (match) openRow(match, props.teamById[match.home_team_id]);
}

// ==========================
//   SEMAINE EN COURS DANS LA LISTE
// ==========================
const listEl = ref(null);
watch(() => [props.calendarTeam?.id, props.currentWeek], async () => {
    await nextTick();
    const box = listEl.value;
    const row = box?.querySelector(`[data-week="${props.currentWeek}"]`);
    if (box && row) box.scrollTop = Math.max(0, row.offsetTop - box.offsetTop - box.clientHeight / 3);
}, { immediate: true });
</script>

<template>
    <!-- ≥ lg : les deux colonnes prennent la hauteur de l'onglet et défilent chacune ;
         en dessous, la page défile d'un bloc. -->
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto lg:overflow-hidden min-h-0 pr-1 lg:pr-0 [&>*]:shrink-0">
        <div v-if="calendarTeam" class="grid grid-cols-1 lg:grid-cols-12 lg:grid-rows-1 lg:flex-1 lg:min-h-0 gap-4">

            <!-- ============================================ -->
            <!-- CALENDRIER                                    -->
            <!-- ============================================ -->
            <Panel as="div" class="lg:col-span-5 lg:min-h-0 lg:flex-col"
                 :class="previewMatch ? 'hidden lg:flex' : 'lg:flex'">
                <!-- Équipe affichée : liste déroulante (remplace les 15 puces) -->
                <div class="flex items-center gap-2 mb-3">
                    <TeamLogo :team="calendarTeam" size="md" shape="rounded" fallback="none" />
                    <select :value="calendarTeam.id" @change="onTeamChange($event.target.value)"
                            aria-label="Équipe affichée"
                            class="flex-1 min-w-0 px-2 py-1.5 cursor-pointer" :class="fieldClass({ shape: 'rect' })">
                        <option v-for="t in calendarTeams" :key="t.id" :value="t.id">
                            {{ t.name }}{{ Number(t.id) === Number(myTeamId) ? ' (mon équipe)' : '' }}
                        </option>
                    </select>
                    <button v-if="myTeamId && Number(calendarTeam.id) !== Number(myTeamId)" type="button"
                            @click="onTeamChange(myTeamId)"
                            class="shrink-0 px-2 py-1.5 rounded-lg text-[11px] font-bold border border-teal-300 text-teal-600 bg-white hover:bg-teal-50"
                            title="Revenir au calendrier de mon équipe">🏠 Mon équipe</button>
                </div>

                <div ref="listEl" class="relative space-y-1 max-h-[65vh] lg:max-h-none lg:flex-1 lg:min-h-0 overflow-y-auto pr-1">
                    <div v-for="match in calendarRows" :key="match.id"
                         :data-week="match.week"
                         class="flex items-center gap-2 px-3 py-2 rounded-lg border transition-all"
                         :class="[
                            isByeMatch(match) ? 'bg-slate-50 border-slate-300 opacity-50' :
                            isPlayable(match) || isUpcoming(match)
                                ? 'bg-white border-slate-300 cursor-pointer hover:border-teal-300 hover:bg-teal-50'
                                : 'bg-white border-slate-300',
                            (previewMatch ? previewMatch.id === match.id : String(selectedMatchId) === String(match.id)) ? 'border-teal-400 bg-teal-50 shadow-sm' : '',
                            currentWeek && Number(match.week) === Number(currentWeek) ? 'ring-2 ring-inset ring-amber-400 !bg-amber-50 shadow-sm' : ''
                        ]"
                         :title="isUpcoming(match) ? 'Voir l\'avant-match' : isPlayable(match) ? 'Ouvrir le match' : undefined"
                         @click="openRow(match)">

                        <!-- Tour (Coupe du Monde) ou semaine (ligue) -->
                        <div class="w-8 text-center shrink-0" :title="roundTitle(match)">
                            <div class="text-2xs font-bold"
                                 :class="currentWeek && Number(match.week) === Number(currentWeek) ? 'text-amber-600' : 'text-slate-400'">{{ roundShort(match) }}</div>
                            <div v-if="currentWeek && Number(match.week) === Number(currentWeek)"
                                 class="text-3xs font-black uppercase text-amber-600 leading-none">▶ en cours</div>
                        </div>

                        <!-- Résultat badge -->
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-2xs font-black shrink-0"
                             :class="isByeMatch(match) ? 'bg-slate-200 text-slate-400' :
                                    matchResult(match, calendarTeam.id)
                                        ? [matchResult(match, calendarTeam.id).bg, matchResult(match, calendarTeam.id).text]
                                        : 'bg-slate-100 text-slate-400'">
                            {{ isByeMatch(match) ? '—' : (matchResult(match, calendarTeam.id)?.label ?? '?') }}
                        </div>

                        <!-- Adversaire -->
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <TeamLogo v-if="!isByeMatch(match)" :team="opponentTeam(match, calendarTeam.id)" size="xs" fallback="none" :bordered="false" />
                            <span class="text-xs font-medium text-slate-700 truncate">
                                {{ isByeMatch(match) ? 'Repos' : opponentNameForTeam(match, calendarTeam.id) }}
                            </span>
                        </div>

                        <!-- Domicile/Extérieur -->
                        <div v-if="!isByeMatch(match)"
                             class="text-xs shrink-0"
                             :title="match.home_team_id === calendarTeam.id ? 'À domicile' : 'À l\'extérieur'">
                            {{ match.home_team_id === calendarTeam.id ? '🏠' : '✈️' }}
                        </div>

                        <!-- Score -->
                        <div class="text-xs font-black text-slate-700 w-12 text-right shrink-0">
                            <template v-if="!isByeMatch(match) && match.status === 'played'">
                                <span :class="(scoreFor(match, calendarTeam.id)?.scored ?? 0) > (scoreFor(match, calendarTeam.id)?.against ?? 0) ? 'text-emerald-600' : (scoreFor(match, calendarTeam.id)?.scored ?? 0) < (scoreFor(match, calendarTeam.id)?.against ?? 0) ? 'text-rose-500' : 'text-slate-500'">
                                    {{ scoreFor(match, calendarTeam.id)?.scored }}
                                </span>
                                <span class="text-slate-300 mx-0.5">-</span>
                                <span>{{ scoreFor(match, calendarTeam.id)?.against }}</span>
                            </template>
                            <span v-else-if="!isByeMatch(match)" class="text-slate-300 text-2xs">vs</span>
                        </div>

                        <!-- Icône stats dispo -->
                        <div class="w-4 shrink-0">
                            <span v-if="loadingMatchId === match.id"
                                  class="inline-block w-3 h-3 rounded-full border-2 border-teal-400 border-t-transparent animate-spin"
                                  title="Chargement…"></span>
                            <span v-else-if="match.status === 'played' && match.has_stats" class="text-teal-400 text-xs">📊</span>
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- ============================================ -->
            <!-- DROITE : avant-match ou journée               -->
            <!-- ============================================ -->
            <div class="lg:col-span-7 flex flex-col gap-3 min-w-0 lg:min-h-0 lg:overflow-y-auto lg:pr-1 [&>*]:shrink-0">
                <template v-if="preview">
                    <ButtonSecondary size="sm" class="lg:hidden self-start" @click="preview = null">
                        ← Calendrier
                    </ButtonSecondary>
                    <MatchPreview :match="preview.match"
                                  :team="preview.team"
                                  :teamById="teamById"
                                  :standings="standings"
                                  :allMatches="allMatches"
                                  :myTeamId="myTeamId"
                                  :label="roundTitle(preview.match)"
                                  @close="preview = null"
                                  @prepare-lineup="emit('change-tab', 'my-team')" />
                </template>

                <!-- Journée -->
                <Panel v-else>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <button type="button" :disabled="journeeWeek <= 1" @click="journeeWeek--"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40">◀</button>
                        <div class="text-center">
                            <div class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Journée</div>
                            <div class="text-sm font-black text-slate-700">
                                Semaine {{ journeeWeek }}
                                <span v-if="Number(journeeWeek) === Number(currentWeek)" class="ml-1 text-2xs font-bold text-amber-600">en cours</span>
                            </div>
                        </div>
                        <button type="button" :disabled="journeeWeek >= maxWeek" @click="journeeWeek++"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40">▶</button>
                    </div>

                    <div class="space-y-1.5">
                        <div v-for="m in journeeMatches" :key="m.id"
                             class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 px-3 py-2 rounded-lg border bg-white transition-all"
                             :class="[
                                isPlayable(m) || isUpcoming(m) ? 'cursor-pointer hover:border-teal-300 hover:bg-teal-50' : '',
                                isMine(m) ? 'border-teal-300 ring-1 ring-teal-200' : 'border-slate-300',
                             ]"
                             :title="isPlayable(m) ? 'Ouvrir le match' : isUpcoming(m) ? 'Voir l\'avant-match' : undefined"
                             @click="openRow(m, teamById[m.home_team_id])">
                            <div class="flex items-center justify-end gap-2 min-w-0">
                                <span class="text-xs font-semibold text-slate-700 truncate"
                                      :class="m.status === 'played' && m.home_score > m.away_score ? 'font-black' : ''">{{ teamById[m.home_team_id]?.name }}</span>
                                <TeamLogoImage v-if="teamLogoUrl(teamById[m.home_team_id])" :src="teamLogoUrl(teamById[m.home_team_id])" class="w-5 h-5 shrink-0" alt=""/>
                            </div>
                            <div class="text-center min-w-[64px]">
                                <span v-if="m.status === 'played'" class="px-2 py-0.5 rounded bg-slate-700 text-white text-xs font-black tabular-nums">
                                    {{ m.home_score }} - {{ m.away_score }}
                                </span>
                                <span v-else class="text-[11px] text-slate-400 font-semibold">vs</span>
                                <div v-if="m.round" class="text-3xs text-slate-400 mt-0.5">{{ roundTitle(m) }}</div>
                                <div v-if="loadingMatchId === m.id" class="mx-auto mt-0.5 w-3 h-3 rounded-full border-2 border-teal-400 border-t-transparent animate-spin"></div>
                            </div>
                            <div class="flex items-center gap-2 min-w-0">
                                <TeamLogoImage v-if="teamLogoUrl(teamById[m.away_team_id])" :src="teamLogoUrl(teamById[m.away_team_id])" class="w-5 h-5 shrink-0" alt=""/>
                                <span class="text-xs font-semibold text-slate-700 truncate"
                                      :class="m.status === 'played' && m.away_score > m.home_score ? 'font-black' : ''">{{ teamById[m.away_team_id]?.name }}</span>
                            </div>
                        </div>
                        <p v-if="!journeeMatches.length" class="text-xs text-slate-400 italic text-center py-6">Aucun match cette semaine.</p>
                    </div>

                    <p v-if="journeeMatches.length && journeeRest.length && !journeeMatches.some(m => m.round)" class="mt-3 text-[11px] text-slate-400">
                        Au repos : {{ journeeRest.map(t => t.name).join(', ') }}
                    </p>
                </Panel>

                <!-- Classements de la journée -->
                <Panel v-if="!preview && journeeMatches.some(m => m.status === 'played')"
                       :title="`Classements de la semaine ${journeeWeek}`">
                    <div class="border-b border-slate-300 mb-3">
                        <TabBar v-model="leaderTab" :tabs="LEADER_TABS" size="sm" wrap label="Classements de la semaine" />
                    </div>

                    <div v-if="leadersLoading && !leaders" class="py-6 flex justify-center">
                        <div class="w-6 h-6 rounded-full border-4 border-teal-400 border-t-transparent animate-spin"></div>
                    </div>
                    <div v-else-if="leaderRows.length" class="space-y-1">
                        <button v-for="(r, i) in leaderRows" :key="`${r.player_id}-${r.match_id}`" type="button"
                                @click="selectedLeader = r" aria-haspopup="dialog"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg border border-slate-300 bg-white text-left hover:border-teal-300 hover:bg-teal-50 transition-all"
                                title="Voir la fiche du joueur">
                            <span v-if="leaderTab !== 'fouls'" class="w-6 text-center text-sm shrink-0" :class="i > 2 ? 'text-xs font-bold text-slate-400' : ''">
                                {{ MEDALS[i] ?? i + 1 }}
                            </span>
                            <PlayerAvatar :player="r" :zoomable="false" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-slate-700 truncate">{{ r.firstname }} {{ r.lastname }}</span>
                                <span class="flex items-center gap-1 text-2xs text-slate-400 truncate">
                                    <TeamLogoImage v-if="teamLogoUrl(teamById[r.team_id])" :src="teamLogoUrl(teamById[r.team_id])" class="w-3.5 h-3.5" alt=""/>
                                    {{ teamById[r.team_id]?.name ?? '—' }}
                                    <template v-if="(leaderTab === 'motm' || leaderTab === 'fouls') && r.detail"> · {{ r.detail }}</template>
                                </span>
                            </span>
                            <span class="text-right shrink-0">
                                <span class="block text-sm font-black text-slate-700 tabular-nums">{{ r.value }}</span>
                                <span class="block text-2xs text-slate-400">
                                    {{ leaderUnit(r.value) }}<template v-if="leaderTab === 'duels' && r.detail"> · {{ r.detail }}</template>
                                </span>
                            </span>
                        </button>
                    </div>
                    <p v-else class="text-xs text-slate-400 italic text-center py-6">
                        {{ leaderTab === 'fouls' ? 'Aucun carton ni blessure cette semaine.' : 'Personne dans cette catégorie cette semaine.' }}
                    </p>
                </Panel>
            </div>
        </div>

        <div v-if="!calendarTeam" class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
            Aucune équipe à afficher
        </div>
        <Modal :show="!!selectedLeader" max-width="lg" title="Fiche du joueur" @close="selectedLeader = null">
            <template v-if="selectedLeader">
                <template v-if="leaderPlayer">
                    <PlayerIdentityCard :player="leaderPlayer">
                        <template #footer>
                            <p v-if="leaderPlayer.morale != null" class="mt-1 text-xs" :class="leaderMorale.text">
                                <span aria-hidden="true">{{ leaderMorale.emoji }}</span>
                                Moral · {{ leaderMorale.label }} · {{ leaderPlayer.morale }}/100
                            </p>
                            <p v-if="leaderPlayer.description" class="mt-2 text-xs italic text-slate-500">{{ leaderPlayer.description }}</p>
                        </template>
                    </PlayerIdentityCard>
                    <StatBars :player="leaderPlayer" highlight-position />
                    <PlayerSpecialMoves :moves="leaderPlayer.special_moves ?? []" show-empty />
                </template>
                <p v-else class="text-sm text-slate-600">
                    La fiche de {{ selectedLeader.firstname }} {{ selectedLeader.lastname }} est indisponible.
                </p>
            </template>
            <template v-if="leaderMatch" #footer>
                <ButtonSecondary size="sm" @click="openLeaderMatch">Ouvrir le match</ButtonSecondary>
            </template>
        </Modal>
    </div>
</template>
