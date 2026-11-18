<script setup>
// Carte « Prochain match » du Dashboard : affrontement (logos, rang, forme récente, dernier duel), boutons
// jouer / simuler, et les états sans match (trêve de mi-saison, semaine de repos, tournoi terminé).
// Domaine : Match + Coupe du Monde. Utilisée par Pages/GameSaves/Play/TabDashboard.vue.
import { Link } from '@inertiajs/vue3';
import TeamLogoImage from '@/Components/TeamLogoImage.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import { computed } from 'vue';
import { usePlayerUtils } from '../usePlayerUtils.js';
import { FORM_STYLE, playedMatchesOf, recentForm, resultFor } from '../matchResults.js';
import { groupRankLabel } from '../worldCup.js';

const props = defineProps({
    gameSave: { type: Object, required: true },
    team: { type: Object, default: null },
    opponentTeam: { type: Object, default: null },
    nextMatch: { type: Object, default: null },
    nextMatchInfo: { type: Object, default: null },
    isByeWeek: { type: Boolean, required: true },
    midseasonBreakRecovery: { type: Number, default: null },
    simulating: { type: Boolean, default: false },
    launchingMatch: { type: Boolean, default: false },
    canSimulateMatch: { type: Boolean, default: false },
    isWorldCup: { type: Boolean, default: false },
    tournament: { type: Object, default: null },
    clubStanding: { type: Object, default: null },
    standings: { type: Array, default: () => [] },
    matches: { type: Array, default: () => [] },
    week: { type: Number, required: true },
});

const emit = defineEmits(['play-next-match', 'simulate-match', 'simulate-week']);

const { teamLogoUrl } = usePlayerUtils();

// 5 derniers résultats (le plus récent à droite).
const formOf = (teamId) => recentForm(props.matches, teamId).map(r => ({
    ...r,
    title: `S${r.week} : ${r.scored}-${r.conceded}`,
}));
const myForm = computed(() => formOf(props.team?.id));
const opponentForm = computed(() => formOf(props.opponentTeam?.id));

// Dernière confrontation avec l'adversaire du prochain match.
const lastHeadToHead = computed(() => {
    const me = props.team?.id, opp = props.opponentTeam?.id;
    if (me == null || opp == null) return null;
    const m = playedMatchesOf(props.matches, me).filter(x => x.home_team_id === opp || x.away_team_id === opp).at(-1);
    return m ? { ...resultFor(m, me), week: m.week } : null;
});
</script>

<template>
    <div class="rounded-xl overflow-hidden bg-white shadow-sm"
         :class="{
     'border-2 border-teal-400':   midseasonBreakRecovery === null && !isByeWeek && nextMatchInfo?.isHome,
     'border-2 border-orange-400': midseasonBreakRecovery === null && !isByeWeek && !nextMatchInfo?.isHome,
     'border border-slate-200':    isByeWeek || midseasonBreakRecovery !== null,
 }">

        <div v-if="midseasonBreakRecovery !== null" class="p-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="text-sm font-bold text-slate-700">Trêve de mi-saison</div>
                <div class="mt-1 text-xs text-slate-600">
                    Une semaine sans match. Tous les joueurs récupèrent jusqu’à {{ midseasonBreakRecovery }} points
                    d’endurance et le compteur de cartons jaunes repart à zéro.
                </div>
            </div>
            <ButtonSecondary :disabled="simulating" @click="emit('simulate-week')">
                Terminer la trêve
            </ButtonSecondary>
        </div>
        <div v-else-if="!isByeWeek && nextMatch && nextMatchInfo" class="p-1.5">
            <!-- Label -->
            <div class="text-center mb-3">
                <h3 class="text-xs font-bold text-slate-700 mb-1">Prochain match</h3>
        <span class="text-2xs font-bold uppercase tracking-widest"
              :class="nextMatchInfo.isHome ? 'text-teal-500' : 'text-orange-500'">
            {{ nextMatchInfo.isHome ? '🏠 Domicile' : '✈️ Extérieur' }}
            &nbsp;•&nbsp; Semaine {{ nextMatchInfo.week }}
        </span>
            </div>

            <!-- Affrontement -->
            <div class="flex flex-wrap items-center justify-around gap-3 sm:gap-4">
                <!-- Mon équipe -->
                <div class="flex flex-col items-center gap-2 flex-1">
                    <div class="w-14 h-14 rounded-2xl overflow-hidden flex items-center justify-center">
                        <TeamLogoImage v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full" alt=""/>
                        <span v-else class="text-2xl">🏟️</span>
                    </div>
                    <div class="text-sm font-bold text-slate-700 text-center">{{ team?.name ?? '—' }}</div>
                    <div class="text-2xs text-slate-400">{{ isWorldCup ? (groupRankLabel(tournament, team?.id) ?? '') : (clubStanding ? `${clubStanding.position}e au classement` : '') }}</div>
                    <div v-if="myForm.length" class="flex gap-1" aria-label="Forme récente">
                        <span v-for="(r, i) in myForm" :key="i" :title="r.title"
                              class="w-5 h-5 rounded text-2xs font-black flex items-center justify-center" :class="FORM_STYLE[r.label]">{{ r.label }}</span>
                    </div>
                </div>

                <!-- VS + Bouton central -->
                <div class="text-center shrink-0 flex flex-col items-center gap-3">
                    <div class="text-3xl font-black text-slate-300">VS</div>
                    <div v-if="lastHeadToHead" class="text-2xs text-slate-400 -mt-2">
                        Dernier duel (S{{ lastHeadToHead.week }}) :
                        <span class="font-bold" :class="{ 'text-emerald-600': lastHeadToHead.label === 'V', 'text-rose-500': lastHeadToHead.label === 'D', 'text-slate-500': lastHeadToHead.label === 'N' }">
                            {{ lastHeadToHead.scored }}-{{ lastHeadToHead.conceded }}
                        </span>
                    </div>
                    <ButtonSecondary size="sm" v-if="isByeWeek" :disabled="simulating" @click="emit('simulate-week')">
                        ⏭ Simuler S{{ week }}
                    </ButtonSecondary>
                    <div v-else class="flex flex-wrap items-center justify-center gap-2">
                        <button type="button"
                                class="px-6 py-2.5 rounded-full font-bold text-sm text-white shadow-sm transition-all hover:scale-105 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="nextMatchInfo.isHome ? 'bg-teal-500 hover:bg-teal-600' : 'bg-orange-500 hover:bg-orange-600'"
                                :disabled="simulating || launchingMatch"
                                @click="emit('play-next-match')">
                            ▶ Jouer le match
                        </button>
                        <ButtonSecondary v-if="canSimulateMatch" size="sm"
                                         :disabled="simulating || launchingMatch" @click="emit('simulate-match')">
                            ⏩ Simuler le match
                        </ButtonSecondary>
                    </div>
                </div>

                <!-- Adversaire -->
                <div class="flex flex-col items-center gap-2 flex-1">
                    <div class="w-16 h-16 rounded-2xl overflow-hidden flex items-center justify-center">
                        <TeamLogoImage v-if="teamLogoUrl(opponentTeam)" :src="teamLogoUrl(opponentTeam)" class="w-full h-full" alt=""/>
                        <span v-else class="text-2xl">⚽</span>
                    </div>
                    <div class="text-sm font-bold text-slate-700 text-center">{{ nextMatchInfo.opponentName }}</div>
                    <div class="text-2xs text-slate-400">
                        {{ isWorldCup ? (groupRankLabel(tournament, opponentTeam?.id) ?? '')
                        : (standings.findIndex(r => r.id === opponentTeam?.id) + 1 > 0
                            ? `${standings.findIndex(r => r.id === opponentTeam?.id) + 1}e au classement`
                            : '') }}
                    </div>
                    <div v-if="opponentForm.length" class="flex gap-1" aria-label="Forme récente de l'adversaire">
                        <span v-for="(r, i) in opponentForm" :key="i" :title="r.title"
                              class="w-5 h-5 rounded text-2xs font-black flex items-center justify-center" :class="FORM_STYLE[r.label]">{{ r.label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coupe du Monde terminée : plus de semaine à simuler -->
        <div v-else-if="isWorldCup && tournament?.stage === 'done'" class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-slate-600">Tournoi terminé</div>
                <div class="text-xs text-slate-400 mt-0.5">Plus aucun match à jouer : consulte le bilan du tournoi.</div>
            </div>
            <Link :href="route('game-saves.tournament-end', gameSave.id)"
                  class="px-4 py-2 rounded-xl bg-amber-500 text-white text-sm font-bold hover:bg-amber-600 text-center">
                Bilan du tournoi
            </Link>
        </div>

        <!-- Semaine sans match -->
        <div v-else class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-slate-600">Semaine {{ week }} — Pas de match</div>
                <div class="text-xs text-slate-400 mt-0.5">Semaine de repos ou en attente du calendrier</div>
            </div>
            <ButtonSecondary size="md" :disabled="simulating" @click="emit('simulate-week')">
                ⏭ Simuler la semaine
            </ButtonSecondary>
        </div>
    </div>
</template>
