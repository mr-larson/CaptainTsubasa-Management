<script setup>
// Convocation d'une sélection : liste des joueurs de la nation à cocher (11 à 20, dont un gardien).
// Domaine : Coupe du Monde. Utilisé par Pages/GameSaves/NationSelection.vue, une fois par manager humain.
// Le parent garde les ids cochés (v-model) et valide la convocation via isValidSquad().
import NationFlag from '@/Components/NationFlag.vue';
import { computed } from 'vue';

const props = defineProps({
    squad:    { type: Object, required: true },            // aperçu renvoyé par NewWorldCupBuilder::squadPreview
    modelValue: { type: Array, default: () => [] },        // ids des joueurs convoqués
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const POSITIONS = { Goalkeeper: 'Gardien', Defender: 'Défenseur', Midfielder: 'Milieu', Forward: 'Attaquant' };
const selected = computed(() => new Set(props.modelValue));

function toggle(id) {
    if (selected.value.has(id)) {
        emit('update:modelValue', props.modelValue.filter(x => x !== id));
    } else if (selected.value.size < props.squad.maxPlayers) {
        emit('update:modelValue', [...props.modelValue, id]);
    }
}
</script>

<script>
/** Une convocation est valide avec 11 à 20 joueurs dont au moins un gardien. */
export function isValidSquad(squad, ids) {
    const set = new Set(ids);
    return !!squad && set.size >= squad.minPlayers && set.size <= squad.maxPlayers
        && squad.players.some(p => p.position === 'Goalkeeper' && set.has(p.id));
}
</script>

<template>
    <div>
        <div>
            <h1 class="text-xl font-bold text-slate-700">
                <NationFlag :flag="squad.flag" :url="squad.flag_url" :name="squad.name" /> {{ squad.name }}
            </h1>
            <p class="mt-1 text-sm text-slate-600">{{ squad.description }}</p>
            <p class="mt-2 text-sm font-semibold text-indigo-700">
                Formation recommandée pour la présélection initiale · {{ squad.formation }}
            </p>
        </div>

        <fieldset class="mt-6">
            <legend class="text-sm font-semibold text-slate-700">Joueurs à convoquer</legend>
            <p class="mb-3 text-sm text-slate-600">
                Sélectionne de {{ squad.minPlayers }} à {{ squad.maxPlayers }} joueurs, dont au moins un gardien.
            </p>
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <label v-for="player in squad.players" :key="player.id"
                       class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2"
                       :class="{ 'border-indigo-300 bg-indigo-50': selected.has(player.id) }">
                    <input type="checkbox" class="rounded border-slate-300 text-indigo-600"
                           :checked="selected.has(player.id)"
                           :disabled="disabled || (!selected.has(player.id) && selected.size >= squad.maxPlayers)"
                           @change="toggle(player.id)">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-slate-700">{{ player.name }}</span>
                        <span class="block text-xs text-slate-600">{{ POSITIONS[player.position] ?? player.position }}</span>
                    </span>
                    <span class="text-sm tabular-nums text-slate-600" title="Note générale">{{ player.overall }}</span>
                </label>
            </div>
        </fieldset>
    </div>
</template>
