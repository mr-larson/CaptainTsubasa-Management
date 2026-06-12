<script setup>
import { computed } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import RosterList from '@/Pages/GameSaves/Play/components/RosterList.vue';
import PlayerStatusAlert from '@/Pages/GameSaves/Play/components/PlayerStatusAlert.vue';
import PlayerIdentityCard from '@/Pages/GameSaves/Play/components/PlayerIdentityCard.vue';
import RadarChart from '@/Pages/GameSaves/Play/components/RadarChart.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import PerfChips from '@/Pages/GameSaves/Play/components/PerfChips.vue';
import FormationCard from '@/Pages/GameSaves/Play/components/FormationCard.vue';
import PitchView from '@/Pages/GameSaves/Play/components/PitchView.vue';
import BenchCard from '@/Pages/GameSaves/Play/components/BenchCard.vue';
import ClubIdentityCard from '@/Pages/GameSaves/Play/components/ClubIdentityCard.vue';
import TeamRecordCard from '@/Pages/GameSaves/Play/components/TeamRecordCard.vue';

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
    averageTeamStat:        { type: Function, required: true },
    playerSeasonStats:      { type: Object,   default: () => ({}) },
    isPlayerInjured:        { type: Function, default: () => () => false },
    isPlayerSuspended:      { type: Function, default: () => () => false },
    playerYellowCards:      { type: Function, default: () => () => 0 },
});

const emit = defineEmits([
    'select-team', 'select-player', 'toggle-starter', 'toggle-captain',
    'save-formation', 'update-number',
    'player-click', 'drag-start', 'drag-over', 'drop-on',
]);

const otherSubstitutes = computed(() => props.otherRosterWithStatus.filter(p => !p.is_starter));

const { teamLogoUrl } = usePlayerUtils();

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
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- LIGNE 1 : Sélecteur équipe (chips horizontales) -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Équipes de la ligue</h3>
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="t in otherTeams" :key="t.id"
                    type="button"
                    @click="emit('select-team', t)"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                    :class="selectedOtherTeam?.id === t.id
                        ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                        : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300 hover:text-teal-600'"
                >
                    <div class="w-4 h-4 rounded-full overflow-hidden shrink-0 bg-slate-100">
                        <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" class="w-full h-full object-contain" alt=""/>
                    </div>
                    {{ t.name }}
                </button>
            </div>
        </div>

        <!-- LIGNE 2 : Formation + Terrain + Banc -->
        <div class="grid grid-cols-12 gap-4" v-if="selectedOtherTeam">
            <FormationCard class="col-span-4"
                           :formation="otherFormation"
                           :formationData="otherFormationData"
                           @change="key => emit('save-formation', key)" />

            <PitchView class="col-span-6"
                       :formationData="otherFormationData"
                       :playerPosition="otherPlayerPosition"
                       :playerForSlot="otherPlayerForSlot"
                       :selectedSlot="otherSelectedSlot"
                       :isPickedUp="isOtherPickedUp"
                       :players="otherRosterWithStatus"
                       @player-click="p => emit('player-click', p)"
                       @drag-start="(p, e) => emit('drag-start', p, e)"
                       @drag-over="e => emit('drag-over', e)"
                       @drop-on="(p, e) => emit('drop-on', p, e)" />

            <BenchCard class="col-span-2"
                       :substitutes="otherSubstitutes"
                       :selectedId="selectedOtherPlayer?.id"
                       :isPickedUp="isOtherPickedUp"
                       @player-click="p => emit('player-click', p)"
                       @drag-start="(p, e) => emit('drag-start', p, e)"
                       @drag-over="e => emit('drag-over', e)"
                       @drop-on="(p, e) => emit('drop-on', p, e)" />
        </div>

        <!-- LIGNE 3 : Infos club -->
        <div class="grid grid-cols-12 gap-4" v-if="selectedOtherTeam">
            <ClubIdentityCard class="col-span-5"
                              :team="selectedOtherTeam"
                              :rank="selectedTeamRank"
                              :total="standings.length"
                              :budget="selectedOtherTeam.budget ?? 0" />

            <TeamRecordCard class="col-span-3"
                            :wins="selectedOtherTeam.wins ?? 0"
                            :draws="selectedOtherTeam.draws ?? 0"
                            :losses="selectedOtherTeam.losses ?? 0" />

            <!-- Stats + Effectif -->
            <div class="col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Club</h4>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Attaque moy.</span>
                        <span class="font-bold text-slate-800">{{ averageTeamStat(otherRosterWithStatus, 'attack') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Blessés</span>
                        <span class="font-bold" :class="injuriesCountForTeam(selectedOtherTeam.id) > 0 ? 'text-rose-500' : 'text-slate-800'">
                            {{ injuriesCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Défense moy.</span>
                        <span class="font-bold text-slate-800">{{ averageTeamStat(otherRosterWithStatus, 'defense') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Suspensions</span>
                        <span class="font-bold" :class="suspensionsCountForTeam(selectedOtherTeam.id) > 0 ? 'text-amber-500' : 'text-slate-800'">
                            {{ suspensionsCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Stamina moy.</span>
                        <span class="font-bold text-slate-800">{{ averageTeamStat(otherRosterWithStatus, 'stamina') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Cartons</span>
                        <span class="font-bold" :class="cardsCountForTeam(selectedOtherTeam.id) > 0 ? 'text-yellow-500' : 'text-slate-800'">
                            {{ cardsCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIGNE 4 : Liste joueurs + Profil -->
        <div class="grid grid-cols-12 gap-4" v-if="selectedOtherTeam">

            <!-- Liste joueurs -->
            <RosterList class="col-span-3 max-h-[630px]"
                        :players="otherRosterWithStatus"
                        :selectedId="selectedOtherPlayer?.id"
                        :isPlayerInjured="isPlayerInjured"
                        :isPlayerSuspended="isPlayerSuspended"
                        :playerYellowCards="playerYellowCards"
                        :playerInjury="playerInjury"
                        :playerSuspension="playerSuspension"
                        @select="p => emit('select-player', p)" />

            <!-- Profil joueur -->
            <div class="col-span-9 flex flex-col gap-3">
                <template v-if="selectedOtherPlayer">

                    <PlayerStatusAlert :player="selectedOtherPlayer"
                                       :isPlayerInjured="isPlayerInjured"
                                       :isPlayerSuspended="isPlayerSuspended"
                                       :playerInjury="playerInjury"
                                       :playerSuspension="playerSuspension" />

                    <!-- Identité + actions -->
                    <PlayerIdentityCard :player="selectedOtherPlayer">
                        <template #actions>
                            <button v-if="selectedOtherPlayer.contract_id" type="button"
                                    @click="emit('toggle-starter', selectedOtherPlayer.contract_id)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                                    :class="selectedOtherPlayer.is_starter
                                        ? 'bg-emerald-500 text-white border-emerald-600 hover:bg-emerald-600'
                                        : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50'">
                                {{ selectedOtherPlayer.is_starter ? '✓ Titulaire' : '+ Titulariser' }}
                            </button>
                            <button
                                v-if="selectedOtherPlayer.contract_id"
                                type="button"
                                @click="emit('toggle-captain', selectedOtherPlayer.contract_id)"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                                :class="selectedOtherPlayer.is_captain
                                    ? 'bg-amber-400 text-white border-amber-500 hover:bg-amber-500'
                                    : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50'"
                                :title="selectedOtherPlayer.is_captain ? 'Retirer le brassard' : 'Nommer capitaine'"
                            >
                                {{ selectedOtherPlayer.is_captain ? '👑 Capitaine' : '👑 Nommer capitaine' }}
                            </button>
                            <!-- Numéro de maillot -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs text-slate-500 font-semibold">N°</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    :value="selectedOtherPlayer.number"
                                    @change="emit('update-number', selectedOtherPlayer.id, $event.target.value)"
                                    class="w-14 border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-teal-300 focus:outline-none"
                                />
                            </div>
                        </template>
                        <template #footer>
                            <p v-if="selectedOtherPlayer.is_starter" class="mt-3 text-[10px] text-slate-400 italic">
                                💡 Glisse ce joueur sur un titulaire pour échanger les positions, ou sur un remplaçant pour le sortir.
                            </p>
                            <p v-else class="mt-3 text-[10px] text-emerald-600 italic">
                                💡 Glisse ce remplaçant sur un titulaire pour le faire entrer en jeu.
                            </p>
                            <p v-if="selectedOtherPlayer.description" class="mt-2 text-xs text-slate-400 italic">{{ selectedOtherPlayer.description }}</p>
                        </template>
                    </PlayerIdentityCard>

                    <!-- Radar + Barres -->
                    <div class="grid grid-cols-2 gap-3">
                        <RadarChart :player="selectedOtherPlayer" accent="red" />
                        <StatBars :player="selectedOtherPlayer" />
                    </div>

                    <!-- Perf chips -->
                    <PerfChips :player="selectedOtherPlayer" :perf="selectedPlayerPerf" />

                </template>

                <div v-else class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
                    Sélectionne un joueur dans la liste ou sur le terrain
                </div>
            </div>
        </div>

        <!-- Aucune équipe sélectionnée -->
        <div v-if="!selectedOtherTeam" class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
            Sélectionne une équipe ci-dessus
        </div>
    </div>
</template>
