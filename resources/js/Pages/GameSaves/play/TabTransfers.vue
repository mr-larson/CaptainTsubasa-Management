<script setup>
const props = defineProps({
    availableFreePlayers: { type: Array,   required: true },
    team:                 { type: Object,  default: null },
    teamBudget:           { type: Number,  required: true },
    showTransferModal:    { type: Boolean, required: true },
    transferTarget:       { type: Object,  default: null },
    transferMatches:      { type: Number,  required: true },
    transferSalary:       { type: Number,  required: true },
    transferReason:       { type: String,  required: true },
    transferTotalCost:    { type: Number,  required: true },
});

const emit = defineEmits(['open-modal', 'close-modal', 'confirm-transfer', 'update:transferMatches', 'update:transferSalary', 'update:transferReason']);

const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path)        return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
};
</script>

<template>
    <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4 relative">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-slate-700">Joueurs libres</h3>
            <p class="text-xs text-slate-500">Les signatures impactent le budget de ton club dans cette sauvegarde.</p>
        </div>

        <div v-if="availableFreePlayers.length" class="max-h-96 overflow-y-auto">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left min-w-max">
                    <thead class="text-xs uppercase text-slate-500 border-b">
                    <tr>
                        <th class="py-1 pr-2 w-10"></th>
                        <th class="py-1 pr-2">Joueur</th>
                        <th class="py-1 pr-2">Poste</th>
                        <th v-for="h in ['Vit','End','Att','Def','Tir','Passe','Dribble','Block','Interc.','Tacle','Main','Poings','Action']" :key="h" class="py-1 pr-2 text-right">{{ h }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="player in availableFreePlayers" :key="player.id" class="border-b last:border-b-0">
                        <td class="py-1 pr-2">
                            <div class="h-7 w-7 rounded border bg-white overflow-hidden flex items-center justify-center">
                                <img v-if="playerPhotoUrl(player)" :src="playerPhotoUrl(player)" class="h-full w-full object-cover" alt=""/>
                                <span v-else class="text-[10px] text-slate-400">—</span>
                            </div>
                        </td>
                        <td class="py-1 pr-2">{{ player.firstname }} {{ player.lastname }}</td>
                        <td class="py-1 pr-2">{{ player.position }}</td>
                        <td v-for="key in ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle','hand_save','punch_save']" :key="key" class="py-1 pr-2 text-right">
                            {{ player[key] ?? player.stats?.[key] ?? '-' }}
                        </td>
                        <td class="py-1 pr-2 text-right">
                            <button type="button"
                                    class="text-xs px-3 py-0.5 rounded-full border border-teal-500 bg-teal-100 hover:bg-teal-200 font-semibold disabled:opacity-50"
                                    :disabled="!team" @click="emit('open-modal', player)">
                                Offre
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <p v-else class="text-sm text-slate-500">Aucun joueur libre disponible.</p>

        <!-- Modal transfert -->
        <div v-if="showTransferModal && transferTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-5">
                <h3 class="text-lg font-semibold text-slate-800 mb-3">Proposer un contrat à {{ transferTarget.firstname }} {{ transferTarget.lastname }}</h3>
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-10 w-10 rounded border bg-white overflow-hidden flex items-center justify-center">
                        <img v-if="playerPhotoUrl(transferTarget)" :src="playerPhotoUrl(transferTarget)" class="h-full w-full object-cover" alt=""/>
                        <span v-else class="text-[10px] text-slate-400">—</span>
                    </div>
                    <p class="text-sm text-slate-600">Club : <span class="font-semibold">{{ team?.name }}</span> — Budget actuel : <span class="font-semibold">{{ teamBudget }} €</span></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre de matchs</label>
                        <input type="number" min="1" max="60" :value="transferMatches" @input="emit('update:transferMatches', +$event.target.value)" class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Coût par match (€)</label>
                        <input type="number" min="0" :value="transferSalary" @input="emit('update:transferSalary', +$event.target.value)" class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"/>
                    </div>
                </div>
                <p class="text-sm text-slate-700 mb-3">Coût total estimé : <span class="font-semibold">{{ transferTotalCost }} €</span></p>
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Raison du recrutement</label>
                    <textarea rows="3" :value="transferReason" @input="emit('update:transferReason', $event.target.value)" class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm" placeholder="Ex. : Renforcer l'aile gauche…"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="px-3 py-1.5 text-sm rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100" @click="emit('close-modal')">Annuler</button>
                    <button type="button" class="px-4 py-1.5 text-sm rounded-md bg-teal-500 hover:bg-teal-600 text-white font-semibold disabled:opacity-50"
                            :disabled="!team || transferMatches <= 0 || transferSalary < 0" @click="emit('confirm-transfer')">
                        Confirmer l'offre
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
