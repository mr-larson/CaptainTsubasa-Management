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

// Onglet en lecture seule : on ne peut que choisir une équipe et consulter un joueur.
const emit = defineEmits(['select-team', 'select-player']);

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
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- LIGNE 1 : Sélecteur équipe (chips horizontales) -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
            <h3 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">Équipes de la ligue</h3>
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
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4" v-if="selectedOtherTeam">
            <!-- Lecture seule : la compo d'une autre équipe (IA ou autre manager
                 en hot-seat) n'est plus modifiable, le serveur le refuse. -->
            <FormationCard class="lg:col-span-4"
                           :formation="otherFormation"
                           :formationData="otherFormationData"
                           readonly />

            <PitchView class="lg:col-span-6"
                       :formationData="otherFormationData"
                       :playerPosition="otherPlayerPosition"
                       :playerForSlot="otherPlayerForSlot"
                       :selectedSlot="otherSelectedSlot"
                       :isPickedUp="isOtherPickedUp"
                       :players="otherRosterWithStatus"
                       :playerCards="p => ({ yellow: playerYellowCards(p.id), red: isPlayerSuspended(p.id) })"
                       @player-click="p => emit('select-player', p)" />

            <BenchCard class="lg:col-span-2"
                       :substitutes="otherSubstitutes"
                       :selectedId="selectedOtherPlayer?.id"
                       :isPickedUp="isOtherPickedUp"
                       @player-click="p => emit('select-player', p)" />
        </div>

        <!-- LIGNE 3 : Infos club -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4" v-if="selectedOtherTeam">
            <ClubIdentityCard class="md:col-span-5"
                              :team="selectedOtherTeam"
                              :rank="selectedTeamRank"
                              :total="standings.length"
                              :budget="selectedOtherTeam.budget ?? 0" />

            <TeamRecordCard class="md:col-span-3"
                            :wins="selectedOtherTeam.wins ?? 0"
                            :draws="selectedOtherTeam.draws ?? 0"
                            :losses="selectedOtherTeam.losses ?? 0" />

            <!-- Stats + Effectif -->
            <div class="md:col-span-4 border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">Club</h4>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Attaque moy.</span>
                        <span class="font-bold text-slate-700">{{ averageTeamStat(otherRosterWithStatus, 'attack') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Blessés</span>
                        <span class="font-bold" :class="injuriesCountForTeam(selectedOtherTeam.id) > 0 ? 'text-rose-500' : 'text-slate-700'">
                            {{ injuriesCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Défense moy.</span>
                        <span class="font-bold text-slate-700">{{ averageTeamStat(otherRosterWithStatus, 'defense') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Suspensions</span>
                        <span class="font-bold" :class="suspensionsCountForTeam(selectedOtherTeam.id) > 0 ? 'text-amber-500' : 'text-slate-700'">
                            {{ suspensionsCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Stamina moy.</span>
                        <span class="font-bold text-slate-700">{{ averageTeamStat(otherRosterWithStatus, 'stamina') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Cartons</span>
                        <span class="font-bold" :class="cardsCountForTeam(selectedOtherTeam.id) > 0 ? 'text-yellow-500' : 'text-slate-700'">
                            {{ cardsCountForTeam(selectedOtherTeam.id) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIGNE 4 : Liste joueurs + Profil -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4" v-if="selectedOtherTeam">

            <!-- Liste joueurs — hauteur calée sur la colonne profil (scroll interne) -->
            <div class="lg:col-span-3 relative min-h-[320px]">
                <RosterList class="absolute inset-0"
                            :players="otherRosterWithStatus"
                            :selectedId="selectedOtherPlayer?.id"
                            :isPlayerInjured="isPlayerInjured"
                            :isPlayerSuspended="isPlayerSuspended"
                            :playerYellowCards="playerYellowCards"
                            :playerInjury="playerInjury"
                            :playerSuspension="playerSuspension"
                            @select="p => emit('select-player', p)" />
            </div>

            <!-- Profil joueur -->
            <div class="lg:col-span-9 flex flex-col gap-3">
                <template v-if="selectedOtherPlayer">

                    <PlayerStatusAlert :player="selectedOtherPlayer"
                                       :isPlayerInjured="isPlayerInjured"
                                       :isPlayerSuspended="isPlayerSuspended"
                                       :playerInjury="playerInjury"
                                       :playerSuspension="playerSuspension" />

                    <!-- Identité + actions -->
                    <PlayerIdentityCard :player="selectedOtherPlayer">
                        <template #actions>
                            <span class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                                  :class="selectedOtherPlayer.is_starter
                                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                      : 'bg-white text-slate-500 border-slate-200'">
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
                        <template #footer>
                            <p v-if="selectedOtherPlayer.description" class="mt-2 text-xs text-slate-400 italic">{{ selectedOtherPlayer.description }}</p>
                        </template>
                    </PlayerIdentityCard>

                    <!-- Radar + Barres -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
