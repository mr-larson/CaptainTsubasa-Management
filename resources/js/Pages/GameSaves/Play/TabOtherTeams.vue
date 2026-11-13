<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import Panel from '@/Components/Panel.vue';
import TeamLogo from '@/Components/TeamLogo.vue';
import RosterList from '@/Pages/GameSaves/Play/components/RosterList.vue';
import PlayerCompareButton from './components/PlayerCompareButton.vue';
import PlayerComparison from './components/PlayerComparison.vue';
import FormationCard from '@/Pages/GameSaves/Play/components/FormationCard.vue';
import PitchView from '@/Pages/GameSaves/Play/components/PitchView.vue';
import BenchCard from '@/Pages/GameSaves/Play/components/BenchCard.vue';
import CaptainToggle from './components/CaptainToggle.vue';
import ClubOverview from './components/ClubOverview.vue';
import PlayerProfilePanel from './components/PlayerProfilePanel.vue';
import ShirtNumberInput from './components/ShirtNumberInput.vue';

const props = defineProps({
    // Équipes
    otherTeams:           { type: Array,    required: true },
    selectedOtherTeam:    { type: Object,   default: null },
    // Roster + joueur sélectionné
    otherRosterWithStatus:{ type: Array,    required: true },
    selectedOtherPlayer:  { type: Object,   default: null },
    // Lineup
    otherLineupForm:      { type: Array,    required: true },
    otherSelectedSlot:    { type: Number,   default: null },
    otherPlayerPosition:  { type: Function, required: true },
    otherPlayerForSlot:   { type: Function, required: true },
    getOtherSlotForPlayer:{ type: Function, required: true },
    // Formation
    otherFormation:       { type: String,   required: true },
    otherFormationData:   { type: Object,   default: null },
    isOtherPickedUp: { type: Function, default: () => false },
    // Absence
    playerInjury:        { type: Function, default: () => () => null },
    playerSuspension:    { type: Function, default: () => () => null },
    // Classement / club
    standings:            { type: Array,    required: true },
    injuriesCountForTeam:   { type: Function, required: true },
    suspensionsCountForTeam:{ type: Function, required: true },
    cardsCountForTeam:      { type: Function, required: true },
    playerSeasonStats:      { type: Object,   default: () => ({}) },
    playerChemistry:        { type: Object,   default: () => ({}) },
    isPlayerInjured:        { type: Function, default: () => () => false },
    isPlayerSuspended:      { type: Function, default: () => () => false },
    playerYellowCards:      { type: Function, default: () => () => 0 },
    // Option « Accès aux autres clubs » (équipe IA) : composition modifiable comme Mon équipe.
    editable:               { type: Boolean,  default: false },
    gameSaveId:             { type: Number,   required: true },
    currentWeek:            { type: Number,   default: 1 },
    currentSeason:          { type: Number,   default: 1 },
    nextOpponentId:         { type: Number,   default: null },
});

// Lecture seule par défaut : on choisit une équipe et on consulte un joueur.
// Avec `editable` : formation, terrain/banc (déplacer, remplacer), titulaire, capitaine, numéro.
// Prochain adversaire en tête de liste : c'est l'équipe qu'on vient le plus souvent observer.
const sortedTeams = computed(() => [...props.otherTeams].sort((a, b) =>
    (b.id === props.nextOpponentId) - (a.id === props.nextOpponentId)));

const emit = defineEmits([
    'select-team', 'select-player',
    'player-click', 'drag-start', 'drag-over', 'drop-on', 'drag-end', 'cancel-pickup',
    'change-formation', 'toggle-starter', 'toggle-captain', 'update-number',
    'make-promise', 'make-declaration',
]);

// Carte « Moral » : chargée à la demande pour le joueur affiché (rechargée après
// chaque rechargement de page, donc après une promesse ou une déclaration).
const morale = ref({ logs: [], promises: [], declarations: [] });
const moraleFor = ref(null);
let moraleRequest = 0;
watch(() => props.selectedOtherPlayer, async (player) => {
    if (!player?.id) { moraleFor.value = null; return; }
    const req = ++moraleRequest;
    try {
        const { data } = await axios.get(route('game-saves.players.morale', { gameSave: props.gameSaveId, player: player.id }));
        if (req !== moraleRequest) return;
        morale.value = data;
        moraleFor.value = player.id;
    } catch {
        if (req === moraleRequest) { morale.value = { logs: [], promises: [], declarations: [] }; moraleFor.value = player.id; }
    }
}, { immediate: true });

const onPlayerClick = (p) => emit(props.editable ? 'player-click' : 'select-player', p);
const onDragStart   = (p, e) => { if (props.editable) emit('drag-start', p, e); };
const onDragOver    = (e) => { if (props.editable) emit('drag-over', e); };
const onDrop        = (p, e) => { if (props.editable) emit('drop-on', p, e); };
const onDragEnd     = () => { if (props.editable) emit('drag-end'); };

const onKey = (e) => { if (e.key === 'Escape') emit('cancel-pickup'); };
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    emit('cancel-pickup');
});

const otherSubstitutes = computed(() => props.otherRosterWithStatus.filter(p => !p.is_starter));

const overallByTeamId = computed(() => new Map(props.standings.map(row => [row.id, row.average_overall])));

// Indisponibilité (icône + contour rouge sur le terrain et le banc), comme dans Mon équipe.
const otherUnavailability = (p) => {
    if (props.isPlayerInjured(p.id))   return { label: 'Blessé',   icon: '🤕' };
    if (props.isPlayerSuspended(p.id)) return { label: 'Suspendu', icon: '🚫' };
    return null;
};

const selectedTeamRank = computed(() => {
    if (!props.selectedOtherTeam || !props.standings?.length) return null;
    const idx = props.standings.findIndex(r => r.id === props.selectedOtherTeam.id);
    return idx >= 0 ? idx + 1 : null;
});

const selectedPlayerPerf = computed(() => {
    const p = props.selectedOtherPlayer;
    if (!p) return null;
    return props.playerSeasonStats?.[p.id] ?? props.playerSeasonStats?.[String(p.id)] ?? {};
});
const comparison = ref([]);
const comparisonAvailability = p => otherUnavailability(p)?.label ?? 'Disponible';
const otherStatus = computed(() => ({
    isPlayerInjured: props.isPlayerInjured, isPlayerSuspended: props.isPlayerSuspended,
    playerInjury: props.playerInjury, playerSuspension: props.playerSuspension,
}));
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- LIGNE 1 : Sélecteur équipe (chips horizontales) -->
        <Panel padding="sm" title="Équipes de la ligue">
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="t in sortedTeams" :key="t.id"
                    type="button"
                    @click="emit('select-team', t)"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                    :class="selectedOtherTeam?.id === t.id
                        ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                        : 'bg-white text-slate-600 border-slate-300 hover:border-teal-300 hover:text-teal-600'"
                >
                    <TeamLogo :team="t" size="xs" fallback="none" :bordered="false" />
                    {{ t.name }}
                    <span class="tabular-nums opacity-80" title="Moyenne des notes de l’effectif">
                        · {{ overallByTeamId.get(t.id) ?? '—' }}
                    </span>
                    <span v-if="t.id === nextOpponentId"
                          class="ml-0.5 px-1.5 rounded-full text-3xs font-bold uppercase"
                          :class="selectedOtherTeam?.id === t.id ? 'bg-white/25 text-white' : 'bg-orange-100 text-orange-600'">🎯 Prochain adversaire</span>
                </button>
            </div>
        </Panel>

        <!-- LIGNE 2 : Formation + Terrain + Banc -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4" v-if="selectedOtherTeam">
            <!-- Lecture seule sauf option « Accès aux autres clubs » (équipes IA uniquement,
                 le serveur refuse le reste). -->
            <FormationCard class="lg:col-span-4"
                           :formation="otherFormation"
                           :formationData="otherFormationData"
                           :readonly="!editable"
                           @change="f => emit('change-formation', f)" />

            <PitchView class="lg:col-span-6"
                       :formationData="otherFormationData"
                       :playerPosition="otherPlayerPosition"
                       :playerForSlot="otherPlayerForSlot"
                       :selectedSlot="otherSelectedSlot"
                       :isPickedUp="isOtherPickedUp"
                       :players="otherRosterWithStatus"
                       :playerStatus="otherUnavailability"
                       :playerCards="p => ({ yellow: playerYellowCards(p.id), red: isPlayerSuspended(p.id) })"
                       @player-click="onPlayerClick"
                       @drag-start="onDragStart"
                       @drag-end="onDragEnd"
                       @drag-over="onDragOver"
                       @drop-on="onDrop"
                       @cancel-pickup="emit('cancel-pickup')" />

            <BenchCard class="lg:col-span-2"
                       :substitutes="otherSubstitutes"
                       :selectedId="selectedOtherPlayer?.id"
                       :isPickedUp="isOtherPickedUp"
                       :playerStatus="otherUnavailability"
                       @player-click="onPlayerClick"
                       @drag-start="onDragStart"
                       @drag-end="onDragEnd"
                       @drag-over="onDragOver"
                       @drop-on="onDrop" />
        </div>

        <p v-if="editable && selectedOtherTeam" class="-mt-2 text-xs text-amber-700">
            🔓 Accès aux autres clubs activé : clique un joueur sur le terrain ou le banc puis sa cible pour le déplacer ou le remplacer (Échap pour annuler).
        </p>

        <!-- LIGNE 3 : Infos club -->
        <ClubOverview v-if="selectedOtherTeam"
                      :team="selectedOtherTeam"
                      :rank="selectedTeamRank"
                      :total="standings.length"
                      :average-overall="overallByTeamId.get(selectedOtherTeam.id) ?? null"
                      :budget="selectedOtherTeam.budget ?? 0"
                      :record="selectedOtherTeam"
                      :players="otherRosterWithStatus"
                      :injuries="injuriesCountForTeam(selectedOtherTeam.id)"
                      :suspensions="suspensionsCountForTeam(selectedOtherTeam.id)"
                      :cards="cardsCountForTeam(selectedOtherTeam.id)" />

        <!-- LIGNE 4 : Liste joueurs + Profil -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4" v-if="selectedOtherTeam">

            <!-- Liste joueurs — hauteur calée sur la colonne profil (scroll interne) -->
            <div class="lg:col-span-4 relative min-h-[320px]">
                <RosterList class="absolute inset-0"
                            :players="otherRosterWithStatus"
                            :selectedId="selectedOtherPlayer?.id"
                            :isPlayerInjured="isPlayerInjured"
                            :isPlayerSuspended="isPlayerSuspended"
                            :playerYellowCards="playerYellowCards"
                            :playerInjury="playerInjury"
                            :playerSuspension="playerSuspension"
                            @select="p => emit('select-player', p)" >
                    <template #row-action="{ player }">
                        <PlayerCompareButton v-model="comparison" :player="{ ...player, comparisonTeamName: selectedOtherTeam?.name }" />
                    </template>
                </RosterList>
            </div>

            <!-- Profil joueur -->
            <PlayerProfilePanel class="lg:col-span-8"
                                :player="selectedOtherPlayer"
                                :status="otherStatus"
                                :morale="morale"
                                :morale-loading="moraleFor !== selectedOtherPlayer?.id"
                                :morale-editable="editable"
                                :chemistry="playerChemistry[selectedOtherPlayer?.id] ?? []"
                                :chemistry-editable="false"
                                :game-save-id="gameSaveId"
                                :current-week="currentWeek" :current-season="currentSeason"
                                :perf="selectedPlayerPerf"
                                accent="red"
                                @make-promise="(p, t) => emit('make-promise', p, t)"
                                @make-declaration="(p, t) => emit('make-declaration', p, t)">
                <template v-if="editable" #actions>
                    <ButtonSecondary size="sm" @click="emit('toggle-starter', selectedOtherPlayer.contract_id)">
                        {{ selectedOtherPlayer.is_starter ? '↓ Sortir du onze' : '+ Titulariser' }}
                    </ButtonSecondary>
                    <CaptainToggle :captain="!!selectedOtherPlayer.is_captain"
                                   @toggle="emit('toggle-captain', selectedOtherPlayer.contract_id)" />
                    <ShirtNumberInput :number="selectedOtherPlayer.number"
                                      @change="(value, revert) => emit('update-number', selectedOtherPlayer.id, value, revert)" />
                </template>
                <template v-else #actions>
                    <span class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                          :class="selectedOtherPlayer.is_starter
                              ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                              : 'bg-white text-slate-500 border-slate-300'">
                        {{ selectedOtherPlayer.is_starter ? '✓ Titulaire' : 'Remplaçant' }}
                    </span>
                    <span v-if="selectedOtherPlayer.is_captain"
                          class="px-3 py-1.5 rounded-full text-xs font-semibold border bg-amber-50 text-amber-700 border-amber-200">
                        👑 Capitaine
                    </span>
                    <span v-if="selectedOtherPlayer.number" class="text-xs text-slate-500 font-semibold">
                        N° {{ selectedOtherPlayer.number }}
                    </span>
                </template>
            </PlayerProfilePanel>
        </div>

        <!-- Aucune équipe sélectionnée -->
        <div v-if="!selectedOtherTeam" class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
            Sélectionne une équipe ci-dessus
        </div>
        <PlayerComparison v-model="comparison" :availability="comparisonAvailability" />
    </div>
</template>
