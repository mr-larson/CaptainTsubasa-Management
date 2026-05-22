<script setup>
const props = defineProps({
    otherTeams:             { type: Array,  required: true },
    selectedOtherTeam:      { type: Object, default: null },
    selectedOtherTeamRoster:{ type: Array,  required: true },
    standings:              { type: Array,  required: true },
    injuriesCountForTeam:   { type: Function, required: true },
    suspensionsCountForTeam:{ type: Function, required: true },
    cardsCountForTeam:      { type: Function, required: true },
    averageTeamStat:        { type: Function, required: true },
});

const emit = defineEmits(['select-team']);

const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path)        return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
};

const teamLogoUrl = (t) => {
    if (t?.logo_path)       return `/storage/${t.logo_path}`;
    if (t?.team?.logo_path) return `/storage/${t.team.logo_path}`;
    return null;
};
</script>

<template>
    <div class="flex-1 grid grid-cols-12 gap-6">

        <!-- Liste équipes -->
        <div class="col-span-3 border border-slate-200 rounded-lg bg-slate-50 p-3 self-start">
            <h3 class="text-md font-semibold text-slate-700 mb-2">Équipes de la ligue</h3>
            <div v-if="otherTeams.length" class="max-h-64 overflow-y-auto space-y-1">
                <button v-for="t in otherTeams" :key="t.id" type="button"
                        @click="emit('select-team', t)"
                        :class="['w-full text-left text-sm px-2 py-1 rounded',
                        selectedOtherTeam?.id === t.id ? 'bg-teal-100 text-slate-900' : 'bg-white hover:bg-slate-100 text-slate-700']">
                    {{ t.name }}
                </button>
            </div>
            <p v-else class="text-sm text-slate-500">Aucune autre équipe trouvée.</p>
        </div>

        <!-- Détails équipe -->
        <div v-if="selectedOtherTeam" class="col-span-9 flex flex-col gap-6">
            <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                <div class="grid grid-cols-4 gap-6 items-center">
                    <div class="col-span-1">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">{{ selectedOtherTeam.name }}</h3>
                        <div class="text-sm text-slate-700 space-y-1">
                            <p v-if="standings?.length">
                                <span class="font-semibold">Place :</span>
                                {{ (standings.findIndex(r => r.id === selectedOtherTeam.id) + 1) || '—' }}<sup>e</sup> / {{ standings.length }}
                            </p>
                            <p><span class="font-semibold">Budget :</span> {{ selectedOtherTeam.budget ?? 0 }} €</p>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-slate-700 leading-relaxed">{{ selectedOtherTeam.description || '-' }}</p>
                    </div>
                    <div class="col-span-1 flex items-center justify-center">
                        <div class="h-24 w-24 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                            <img v-if="teamLogoUrl(selectedOtherTeam)" :src="teamLogoUrl(selectedOtherTeam)" class="h-full w-full object-contain" alt=""/>
                            <span v-else class="text-xs text-slate-400">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statut -->
            <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                <h4 class="text-md font-semibold text-slate-700 mb-3">Statut du club</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">
                    <div>
                        <p class="font-semibold mb-1">Matchs joués {{ (selectedOtherTeam.wins ?? 0) + (selectedOtherTeam.draws ?? 0) + (selectedOtherTeam.losses ?? 0) }}</p>
                        <p>Victoire : {{ selectedOtherTeam.wins ?? 0 }}</p>
                        <p>Nul : {{ selectedOtherTeam.draws ?? 0 }}</p>
                        <p>Défaite : {{ selectedOtherTeam.losses ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="font-semibold mb-1">Stats moyennes</p>
                        <p>Attaque : {{ averageTeamStat(selectedOtherTeamRoster, 'attack') }}</p>
                        <p>Défense : {{ averageTeamStat(selectedOtherTeamRoster, 'defense') }}</p>
                        <p>Endurance : {{ averageTeamStat(selectedOtherTeamRoster, 'stamina') }}</p>
                    </div>
                    <div>
                        <p class="font-semibold mb-1">État de l'effectif</p>
                        <p>Blessés : {{ injuriesCountForTeam(selectedOtherTeam.id) }}</p>
                        <p>Suspensions : {{ suspensionsCountForTeam(selectedOtherTeam.id) }}</p>
                        <p>Cartons : {{ cardsCountForTeam(selectedOtherTeam.id) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Effectif -->
        <div class="col-span-12 border border-slate-200 rounded-lg bg-slate-50 p-4 h-80 overflow-y-auto">
            <h4 class="text-md font-semibold text-slate-700 mb-2">Effectif</h4>
            <div v-if="selectedOtherTeamRoster.length" class="overflow-x-auto">
                <table class="w-full text-sm min-w-max text-left">
                    <thead class="text-xs uppercase text-slate-500 border-b">
                    <tr>
                        <th class="py-1 pr-2 w-10"></th>
                        <th class="py-1 pr-2">Joueur</th>
                        <th class="py-1 pr-2">Poste</th>
                        <th v-for="h in ['Vit','End','Att','Def','Tir','Passe','Dribble','Block','Interc.','Tacle','Main','Poings','Coût']" :key="h" class="py-1 pr-2 text-right">{{ h }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="player in selectedOtherTeamRoster" :key="player.id" class="border-b last:border-b-0">
                        <td class="py-1 pr-2">
                            <div class="h-7 w-7 rounded border bg-white overflow-hidden flex items-center justify-center">
                                <img v-if="playerPhotoUrl(player)" :src="playerPhotoUrl(player)" class="h-full w-full object-cover" alt=""/>
                                <span v-else class="text-[10px] text-slate-400">—</span>
                            </div>
                        </td>
                        <td class="py-1 pr-2">{{ player.firstname }} {{ player.lastname }}</td>
                        <td class="py-1 pr-2">{{ player.position }}</td>
                        <td v-for="key in ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle','hand_save','punch_save','cost']" :key="key" class="py-1 pr-2 text-right">
                            {{ player[key] ?? player.stats?.[key] ?? '-' }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-slate-500">Aucun joueur sous contrat pour cette équipe.</p>
        </div>
    </div>
</template>
