<script setup>
import { useForm } from '@inertiajs/vue3';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';

const props = defineProps({
    gameSaveId: { type: Number, required: true },
    playerId: { type: Number, required: true },
    relations: { type: Array, default: () => [] },
});

const form = useForm({ player_id: null, partner_id: null });
function propose(partnerId) {
    form.player_id = props.playerId;
    form.partner_id = partnerId;
    form.post(route('game-saves.player-chemistry.store', props.gameSaveId), { preserveScroll: true });
}
</script>

<template>
    <section class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="text-sm font-bold text-slate-700">🤝 Alchimie</h3>
        <p v-if="!relations.length" class="text-xs text-slate-500">
            Joue plusieurs matchs avec un coéquipier sur la même ligne ou une ligne voisine pour développer leurs automatismes.
        </p>
        <ul v-else class="max-h-60 space-y-2 overflow-y-auto">
            <li v-for="relation in relations" :key="relation.partner_id"
                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white p-2.5 text-xs">
                <div class="min-w-36 flex-1">
                    <div class="flex items-center justify-between gap-2 font-semibold text-slate-700">
                        <span>{{ relation.partner_name }}</span>
                        <span>{{ relation.score }}/100</span>
                    </div>
                    <progress :value="relation.score" max="100" :aria-label="`Alchimie avec ${relation.partner_name}`"
                              class="h-1.5 w-full accent-teal-500" />
                    <div class="text-slate-500">{{ relation.shared_matches }} match(s) ensemble</div>
                </div>
                <span v-if="relation.confirmed" class="rounded-full bg-teal-100 px-2 py-1 font-semibold text-teal-800">Duo confirmé</span>
                <ButtonSecondary v-else-if="relation.can_attempt" size="sm" :disabled="form.processing"
                                 @click="propose(relation.partner_id)">
                    Proposer un duo · {{ relation.chance }} %
                </ButtonSecondary>
                <span v-else-if="relation.retry_matches" class="text-slate-500">
                    Réessai dans {{ relation.retry_matches }} match(s) ensemble
                </span>
            </li>
        </ul>
        <p v-if="form.errors.chemistry" role="alert" class="text-xs text-rose-600">{{ form.errors.chemistry }}</p>
    </section>
</template>
