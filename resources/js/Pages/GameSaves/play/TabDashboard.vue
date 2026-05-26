<script setup>
import { computed } from 'vue';

const props = defineProps({
    gameSave:      { type: Object, required: true },
    team:          { type: Object, default: null },
    season:        { type: Number, required: true },
    week:          { type: Number, required: true },
    roster:        { type: Array,  required: true },
    clubStanding:  { type: Object, default: null },
    standings:     { type: Array,  required: true },
    teamRecord:    { type: Object, required: true },
    teamBudget:    { type: Number, required: true },
    injuriesCount:    { type: Number, required: true },
    suspensionsCount: { type: Number, required: true },
    cardsCount:       { type: Number, required: true },
    averageAttack:  { type: Number, required: true },
    averageDefense: { type: Number, required: true },
    averageStamina: { type: Number, required: true },
    nextMatch:      { type: Object, default: null },
    nextMatchInfo:  { type: Object, default: null },
    isByeWeek:      { type: Boolean, required: true },
    teamById:       { type: Object, required: true },
    saving:         { type: Boolean, required: true },
    matches:        { type: Array,   default: () => [] },
});

const emit = defineEmits(['play-next-match', 'simulate-week', 'save-game', 'quit']);

// ==========================
//   HELPERS
// ==========================
const periodLabel = (p) => ({ college: 'Collège', highschool: 'Lycée', pro: 'Professionnel' }[p] ?? p);

const teamLogoUrl = (t) => {
    const path = t?.logo_path ?? t?.team?.logo_path;
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/')) return path;
    if (path.startsWith('teams/')) return '/images/' + path;
    return '/' + path;
};

const opponentTeamIdFor = (match) => {
    if (!match || !props.team) return null;
    return match.home_team_id === props.team.id ? match.away_team_id : match.home_team_id;
};

const opponentTeam = computed(() => {
    if (!props.nextMatch) return null;
    const oppId = opponentTeamIdFor(props.nextMatch);
    return oppId ? props.teamById[oppId] ?? null : null;
});

// ==========================
//   SAISON PROGRESSION
// ==========================
const totalWeeks = computed(() => {
    const n = props.standings.length;
    if (n < 2) return 28;
    return n % 2 === 1 ? n * 2 : (n - 1) * 2;
});

const seasonProgress = computed(() =>
    Math.round(((props.week - 1) / Math.max(totalWeeks.value, 1)) * 100)
);

// ==========================
//   FORME RÉCENTE
// ==========================
const recentForm = computed(() => {
    if (!props.team) return [];
    const teamId = props.team.id;
    return props.matches
        .filter(m => m.status === 'played' && (m.home_team_id === teamId || m.away_team_id === teamId))
        .slice(-5)
        .map(m => {
            const isHome   = m.home_team_id === teamId;
            const scored   = isHome ? (m.home_score ?? 0) : (m.away_score ?? 0);
            const conceded = isHome ? (m.away_score ?? 0) : (m.home_score ?? 0);
            if (scored > conceded)  return { label: 'V', bg: 'bg-emerald-500', text: 'text-white' };
            if (scored === conceded) return { label: 'N', bg: 'bg-slate-300',   text: 'text-slate-700' };
            return { label: 'D', bg: 'bg-rose-500', text: 'text-white' };
        });
});

// ==========================
//   RANK STYLE
// ==========================
const rankStyle = computed(() => {
    const pos = props.clubStanding?.position ?? 99;
    if (pos === 1) return 'text-yellow-500';
    if (pos <= 3)  return 'text-amber-500';
    if (pos <= 6)  return 'text-teal-500';
    return 'text-slate-500';
});

const matchesPlayed = computed(() =>
    (props.teamRecord.wins ?? 0) + (props.teamRecord.draws ?? 0) + (props.teamRecord.losses ?? 0)
);
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[72vh] pr-1">

        <!-- ============================================ -->
        <!-- LIGNE 1 : Contexte + progression saison      -->
        <!-- ============================================ -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between gap-4 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-white border border-slate-200 shrink-0">
                        <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt=""/>
                        <span v-else class="w-full h-full flex items-center justify-center text-lg">🏟️</span>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-800">{{ team?.name ?? '—' }}</div>
                        <div class="text-xs text-slate-400">{{ periodLabel(gameSave.period) }}</div>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-lg font-black text-slate-700">{{ season }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Saison</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black text-teal-600">{{ week }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Semaine</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black text-slate-700">{{ matchesPlayed }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Matchs joués</div>
                    </div>
                </div>

                <!-- Boutons actions -->
                <div class="flex gap-2 shrink-0">
                    <button type="button"
                            class="px-4 py-1.5 text-xs rounded-full font-semibold border transition-all disabled:opacity-40"
                            :class="saving ? 'bg-slate-100 text-slate-400 border-slate-200' : 'bg-cyan-50 text-cyan-600 border-cyan-300 hover:bg-cyan-100'"
                            :disabled="saving" @click="emit('save-game')">
                        {{ saving ? '...' : '💾 Sauvegarder' }}
                    </button>
                    <button type="button"
                            class="px-4 py-1.5 text-xs rounded-full font-semibold border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-all"
                            @click="emit('quit')">
                        Quitter
                    </button>
                </div>
            </div>

            <!-- Barre progression saison -->
            <div>
                <div class="flex justify-between text-[10px] text-slate-400 mb-1">
                    <span>Semaine 1</span>
                    <span class="font-semibold text-teal-500">{{ seasonProgress }}% de la saison</span>
                    <span>Semaine {{ totalWeeks }}</span>
                </div>
                <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-teal-400 to-teal-500 rounded-full transition-all"
                         :style="{ width: seasonProgress + '%' }">
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 2 : Prochain match (hero)              -->
        <!-- ============================================ -->
        <div class="border border-slate-200 rounded-xl overflow-hidden"
             :class="isByeWeek ? 'bg-slate-50' : 'bg-gradient-to-br from-slate-600 to-slate-700'">

            <div v-if="!isByeWeek && nextMatch && nextMatchInfo" class="p-5">
                <!-- Label -->
                <div class="text-center mb-4">
                    <span class="text-[10px] font-bold uppercase tracking-widest"
                          :class="nextMatchInfo.isHome ? 'text-teal-400' : 'text-orange-400'">
                        {{ nextMatchInfo.isHome ? '🏠 Domicile' : '✈️ Extérieur' }}
                        &nbsp;•&nbsp; Semaine {{ nextMatchInfo.week }}
                    </span>
                </div>

                <!-- Affrontement -->
                <div class="flex items-center justify-around gap-4">
                    <!-- Mon équipe -->
                    <div class="flex flex-col items-center gap-2 flex-1">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden bg-white border border-white/20 flex items-center justify-center">
                            <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt=""/>
                            <span v-else class="text-2xl">🏟️</span>
                        </div>
                        <div class="text-sm font-bold text-white text-center">{{ team?.name ?? '—' }}</div>
                        <div class="text-[10px] text-white/50">{{ clubStanding ? `${clubStanding.position}e au classement` : '' }}</div>
                    </div>

                    <!-- VS + Bouton central -->
                    <div class="text-center shrink-0 flex flex-col items-center gap-3">
                        <div class="text-3xl font-black text-white/20">VS</div>
                        <button v-if="isByeWeek" type="button"
                                class="px-5 py-2 rounded-full font-bold text-xs bg-slate-500 hover:bg-slate-400 text-white transition-all"
                                @click="emit('simulate-week')">
                            ⏭ Simuler S{{ week }}
                        </button>
                        <button v-else type="button"
                                class="px-6 py-2.5 rounded-full font-bold text-sm bg-teal-500 hover:bg-teal-400 text-white shadow-lg shadow-teal-900/30 transition-all hover:scale-105 active:scale-95"
                                @click="emit('play-next-match')">
                            ▶ Jouer
                        </button>
                    </div>

                    <!-- Adversaire -->
                    <div class="flex flex-col items-center gap-2 flex-1">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden bg-white border border-white/20 flex items-center justify-center">
                            <img v-if="teamLogoUrl(opponentTeam)" :src="teamLogoUrl(opponentTeam)" class="w-full h-full object-contain" alt=""/>
                            <span v-else class="text-2xl">⚽</span>
                        </div>
                        <div class="text-sm font-bold text-white text-center">{{ nextMatchInfo.opponentName }}</div>
                        <div class="text-[10px] text-white/50">
                            {{ standings.findIndex(r => r.id === opponentTeam?.id) + 1 > 0
                            ? `${standings.findIndex(r => r.id === opponentTeam?.id) + 1}e au classement`
                            : '' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Semaine sans match -->
            <div v-else class="p-5 flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold text-slate-600">Semaine {{ week }} — Pas de match</div>
                    <div class="text-xs text-slate-400 mt-0.5">Semaine de repos ou en attente du calendrier</div>
                </div>
                <button type="button"
                        class="px-6 py-2 rounded-full font-semibold text-sm bg-slate-200 hover:bg-slate-300 text-slate-600 transition-all"
                        @click="emit('simulate-week')">
                    ⏭ Simuler la semaine
                </button>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 3 : 4 tuiles                          -->
        <!-- ============================================ -->
        <div class="grid grid-cols-4 gap-3">

            <!-- Classement -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col items-center gap-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Classement</div>
                <div class="text-3xl font-black mt-1" :class="rankStyle">
                    {{ clubStanding?.position ?? '—' }}<sup v-if="clubStanding" class="text-base">e</sup>
                </div>
                <div class="text-[10px] text-slate-400">/ {{ standings.length }} équipes</div>
                <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden mt-1">
                    <div class="h-full bg-teal-500 rounded-full"
                         :style="{ width: clubStanding ? (100 - ((clubStanding.position - 1) / Math.max(standings.length - 1, 1)) * 100) + '%' : '0%' }">
                    </div>
                </div>
            </div>

            <!-- Bilan -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Bilan</div>
                <div class="flex gap-2">
                    <div class="flex-1 text-center bg-emerald-50 rounded-lg py-1.5">
                        <div class="text-xl font-black text-emerald-600">{{ teamRecord.wins ?? 0 }}</div>
                        <div class="text-[9px] text-emerald-500 font-semibold">V</div>
                    </div>
                    <div class="flex-1 text-center bg-slate-100 rounded-lg py-1.5">
                        <div class="text-xl font-black text-slate-500">{{ teamRecord.draws ?? 0 }}</div>
                        <div class="text-[9px] text-slate-400 font-semibold">N</div>
                    </div>
                    <div class="flex-1 text-center bg-rose-50 rounded-lg py-1.5">
                        <div class="text-xl font-black text-rose-500">{{ teamRecord.losses ?? 0 }}</div>
                        <div class="text-[9px] text-rose-400 font-semibold">D</div>
                    </div>
                </div>
                <div class="text-center mt-1.5">
                    <span class="text-xs font-black text-teal-600">{{ clubStanding?.points ?? 0 }} pts</span>
                </div>
            </div>

            <!-- Budget -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col items-center gap-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Budget</div>
                <div class="text-2xl font-black text-teal-600 mt-1">{{ teamBudget }}</div>
                <div class="text-[10px] text-slate-400">euros</div>
                <div class="text-[10px] text-slate-500 mt-1">{{ roster.length }} joueur(s)</div>
            </div>

            <!-- Effectif -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Effectif</div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Blessés</span>
                        <span class="font-black" :class="injuriesCount > 0 ? 'text-rose-500' : 'text-slate-400'">
                            {{ injuriesCount }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Suspensions</span>
                        <span class="font-black" :class="suspensionsCount > 0 ? 'text-amber-500' : 'text-slate-400'">
                            {{ suspensionsCount }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Cartons</span>
                        <span class="font-black" :class="cardsCount > 0 ? 'text-yellow-500' : 'text-slate-400'">
                            {{ cardsCount }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 4 : Forces + Forme récente            -->
        <!-- ============================================ -->
        <div class="grid grid-cols-2 gap-3">

            <!-- Forces moyennes -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Forces de l'équipe</h4>
                <div class="space-y-2">
                    <div v-for="stat in [
                        { label: 'Attaque',  val: averageAttack,  color: 'bg-orange-400' },
                        { label: 'Défense',  val: averageDefense, color: 'bg-blue-400'   },
                        { label: 'Stamina',  val: averageStamina, color: 'bg-emerald-400'},
                    ]" :key="stat.label" class="flex items-center gap-3">
                        <span class="w-16 text-xs text-slate-500 shrink-0">{{ stat.label }}</span>
                        <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" :class="stat.color"
                                 :style="{ width: Math.min(stat.val, 100) + '%' }">
                            </div>
                        </div>
                        <span class="w-8 text-right text-xs font-black text-slate-700">{{ stat.val }}</span>
                    </div>
                </div>
            </div>

            <!-- Forme récente -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Forme récente</h4>
                <div v-if="recentForm.length" class="flex items-center gap-2 flex-wrap">
                    <div v-for="(r, i) in recentForm" :key="i"
                         class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-black shadow-sm"
                         :class="[r.bg, r.text]">
                        {{ r.label }}
                    </div>
                    <div class="ml-2 text-xs text-slate-400 italic">
                        {{ recentForm.filter(r => r.label === 'V').length }} victoire(s) sur {{ recentForm.length }} dernier(s) match(s)
                    </div>
                </div>
                <div v-else class="flex items-center justify-center h-16 text-slate-400 text-xs italic">
                    Aucun match joué pour le moment
                </div>

                <!-- Description équipe -->
                <div v-if="team?.description" class="mt-3 pt-3 border-t border-slate-200">
                    <p class="text-xs text-slate-400 italic leading-relaxed line-clamp-2">{{ team.description }}</p>
                </div>
            </div>
        </div>

    </div>
</template>
