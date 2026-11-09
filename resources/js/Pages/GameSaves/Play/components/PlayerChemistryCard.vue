<script setup>
import { useForm } from '@inertiajs/vue3';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';

const props = defineProps({
    gameSaveId: { type: Number, required: true },
    playerId: { type: Number, required: true },
    relations: { type: Array, default: () => [] },
    editable: { type: Boolean, default: true },
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
        <h3 class="text-2xs font-bold uppercase tracking-wider text-slate-400">Alchimie</h3>
        <p v-if="!relations.length" class="text-xs text-slate-500">
            Joue plusieurs matchs avec un coéquipier sur la même ligne ou une ligne voisine pour développer leurs automatismes.
        </p>
        <ul v-else class="max-h-60 divide-y divide-slate-200 overflow-y-auto overscroll-contain">
            <li v-for="relation in relations" :key="relation.partner_id"
                class="py-3 first:pt-0 last:pb-0">
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-700">{{ relation.partner_name }}</span>
                    <span class="shrink-0 rounded-full bg-teal-100 px-2.5 py-1 text-[11px] font-bold text-teal-700">
                        {{ relation.score }}<span class="opacity-60">/100</span>
                    </span>
                </div>
                <div class="mb-2 h-1.5 overflow-hidden rounded-full bg-slate-200"
                     role="progressbar" :aria-label="`Alchimie avec ${relation.partner_name}`"
                     :aria-valuenow="relation.score" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-teal-400 transition-all"
                         :style="{ width: relation.score + '%' }"></div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-2xs font-semibold text-slate-500">
                        {{ relation.shared_matches }} {{ relation.shared_matches === 1 ? 'match' : 'matchs' }} ensemble
                    </span>
                    <span v-if="relation.confirmed" class="rounded-full bg-teal-100 px-2 py-0.5 text-2xs font-bold text-teal-700">🤝 Duo confirmé</span>
                    <ButtonSecondary v-else-if="editable && relation.can_attempt" size="sm" :disabled="form.processing"
                                     @click="propose(relation.partner_id)">
                        Proposer un duo · {{ relation.chance }} %
                    </ButtonSecondary>
                    <span v-else-if="relation.retry_matches" class="rounded-full bg-amber-100 px-2 py-0.5 text-2xs font-semibold text-amber-700">
                        Réessai dans {{ relation.retry_matches }} {{ relation.retry_matches === 1 ? 'match' : 'matchs' }} ensemble
                    </span>
                </div>
            </li>
        </ul>
        <p v-if="form.errors.chemistry" role="alert" class="text-xs text-rose-600">{{ form.errors.chemistry }}</p>
    </section>
</template>
