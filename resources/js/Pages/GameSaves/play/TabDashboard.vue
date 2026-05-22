<script setup>
const props = defineProps({
    gameSave:      { type: Object, required: true },
    team:          { type: Object, default: null },
    season:        { type: Number, required: true },
    week:          { type: Number, required: true },
    roster:        { type: Array,  required: true },

    // dashboard
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

    // calendar
    nextMatch:      { type: Object, default: null },
    nextMatchInfo:  { type: Object, default: null },
    isByeWeek:      { type: Boolean, required: true },
    teamById:       { type: Object, required: true },

    // ui
    saving:         { type: Boolean, required: true },
});

const emit = defineEmits(['play-next-match', 'simulate-week', 'save-game', 'quit']);

const periodLabel = (period) => {
    if (period === 'college')    return 'Collège';
    if (period === 'highschool') return 'Lycée';
    if (period === 'pro')        return 'Professionnel';
    return period;
};

const teamLogoUrl = (t) => {
    if (t?.logo_path)        return `/storage/${t.logo_path}`;
    if (t?.team?.logo_path)  return `/storage/${t.team.logo_path}`;
    return null;
};

const opponentTeamIdFor = (match) => {
    if (!match) return null;
    return match.home_team_id === props.team?.id ? match.away_team_id : match.home_team_id;
};
</script>

<template>
    <div class="flex-1 flex flex-col">
        <!-- Infos générales -->
        <div class="mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-around gap-2">
            <p class="text-slate-600">Période : <span class="font-semibold">{{ periodLabel(gameSave.period) }}</span></p>
            <p class="text-slate-600">Saison {{ season }} — Semaine {{ week }}</p>
            <p class="text-slate-600" v-if="team">Équipe contrôlée : <span class="font-semibold">{{ team.name }}</span></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1">

            <!-- Prochain match -->
            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                <div class="flex items-start justify-between gap-4 mb-2">
                    <div v-if="nextMatch && nextMatchInfo" class="text-sm text-slate-700">
                        <h3 class="text-lg font-semibold text-slate-700">Prochain match</h3>
                        <ul class="space-y-1">
                            <li><span class="font-semibold">Semaine :</span> {{ isByeWeek ? week : nextMatchInfo.week }}</li>
                            <li><span class="font-semibold">Adversaire :</span> {{ nextMatchInfo.opponentName }}</li>
                            <li><span class="font-semibold">Lieu :</span> {{ nextMatchInfo.isHome ? 'Domicile' : 'Extérieur' }}</li>
                            <li><span class="font-semibold">Contexte :</span> Saison {{ season }} — Championnat</li>
                        </ul>
                    </div>
                    <div v-else class="text-sm text-slate-600">
                        <p class="mb-2">Aucun match de championnat planifié pour le moment.</p>
                        <p class="text-xs text-slate-500">Vérifie que le calendrier a bien été généré.</p>
                    </div>
                    <div class="h-24 w-24 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                        <img
                            v-if="nextMatch && opponentTeamIdFor(nextMatch)"
                            :src="teamLogoUrl(teamById[opponentTeamIdFor(nextMatch)])"
                            class="h-full w-full object-contain" alt="Logo adversaire"
                        />
                        <span v-else class="text-xs text-slate-400">—</span>
                    </div>
                </div>
                <div class="mt-4 pt-6 flex justify-center gap-3">
                    <button v-if="!isByeWeek" type="button"
                            class="w-60 bg-teal-300 hover:bg-teal-400 text-center font-semibold py-1 px-5 border-2 border-teal-500 rounded-full drop-shadow-md"
                            @click="emit('play-next-match')">
                        Jouer le prochain match
                    </button>
                    <button v-else type="button"
                            class="w-60 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-1 px-5 border-2 border-slate-500 rounded-full drop-shadow-md"
                            @click="emit('simulate-week')">
                        Simuler la semaine
                    </button>
                </div>
            </div>

            <!-- Résumé du club -->
            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                <div class="flex items-start justify-between gap-4 mb-2">
                    <div class="text-sm text-slate-700">
                        <h3 class="text-lg font-semibold text-slate-700 mb-1">Résumé du club</h3>
                        <p><span class="font-semibold">Nom :</span> {{ team?.name }}</p>
                        <p><span class="font-semibold">Budget :</span> {{ teamBudget }} €</p>
                        <p><span class="font-semibold">Joueurs sous contrat :</span> {{ roster.length }}</p>
                    </div>
                    <div class="h-24 w-24 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                        <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="h-full w-full object-contain" alt="Logo équipe"/>
                        <span v-else class="text-xs text-slate-400">—</span>
                    </div>
                </div>
                <div v-if="team?.description" class="mt-6 text-sm text-slate-600">{{ team.description }}</div>
            </div>

            <!-- Statut du club -->
            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 lg:col-span-2">
                <h3 class="text-lg font-semibold text-slate-700 mb-2">Statut du club</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">
                    <div>
                        <p class="font-semibold mb-1">Classement</p>
                        <p v-if="clubStanding">Place : {{ clubStanding.position }}<sup>e</sup> / {{ standings.length }}</p>
                        <p v-else>Classement non disponible.</p>
                        <p class="mt-1">Bilan : {{ teamRecord.wins }} V / {{ teamRecord.draws }} N / {{ teamRecord.losses }} D</p>
                        <p>Matchs joués : {{ teamRecord.wins + teamRecord.draws + teamRecord.losses }}</p>
                    </div>
                    <div>
                        <p class="font-semibold mb-1">Forces moyennes</p>
                        <p>Attaque : {{ averageAttack }}</p>
                        <p>Défense : {{ averageDefense }}</p>
                        <p>Endurance : {{ averageStamina }}</p>
                    </div>
                    <div>
                        <p class="font-semibold mb-1">État de l'effectif</p>
                        <p>Blessés : {{ injuriesCount }}</p>
                        <p>Suspensions : {{ suspensionsCount }}</p>
                        <p>Cartons en cours : {{ cardsCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Boutons bas -->
        <div class="flex justify-around mt-6">
            <button type="button"
                    class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-1 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                    :disabled="saving" @click="emit('save-game')">
                {{ saving ? 'Sauvegarde...' : 'Sauvegarder' }}
            </button>
            <button type="button"
                    class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-1 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2"
                    @click="emit('quit')">
                Quitter
            </button>
        </div>
    </div>
</template>
