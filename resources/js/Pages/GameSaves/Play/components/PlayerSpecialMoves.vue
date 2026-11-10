<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import { PLAYER_STATS } from '@/Enums/playerStats';

defineProps({
    moves: { type: Array, default: () => [] },
    namesOnly: { type: Boolean, default: false },
    showEmpty: { type: Boolean, default: false },
});

const selectedMove = ref(null);
const actionLabels = {
    ...Object.fromEntries(PLAYER_STATS.map(({ key, label }) => [key, label])),
    cross: 'Centre',
    hands: 'Arrêt main',
    punch: 'Arrêt poing',
};
</script>

<template>
    <section v-if="moves.length || showEmpty" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <h4 class="mb-2 text-2xs font-bold uppercase tracking-wider text-slate-700">✨ Techniques spéciales</h4>
        <ul v-if="moves.length" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <li v-for="(move, index) in moves" :key="move.key || index" class="min-w-0">
                <button v-if="namesOnly" type="button" aria-haspopup="dialog"
                        class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-left text-xs font-bold text-slate-700 hover:border-teal-300 hover:text-teal-700 focus-visible:outline-teal-600"
                        @click="selectedMove = move">
                    {{ move.label || move.short_label || move.key }}
                </button>
                <div v-else class="rounded-lg border border-slate-200 bg-white p-2.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-slate-700">{{ move.label || move.short_label || move.key }}</span>
                        <span v-if="move.mode === 'attack' || move.mode === 'defense'"
                          class="rounded-full px-2 py-0.5 text-2xs font-semibold"
                          :class="move.mode === 'defense' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'">
                            {{ move.mode === 'defense' ? 'Défense' : 'Attaque' }}
                        </span>
                    </div>
                    <p v-if="move.description" class="mt-1 text-xs text-slate-500">{{ move.description }}</p>
                    <p v-if="move.cooldown != null" class="mt-1 text-2xs text-slate-500">
                        Recharge · {{ move.cooldown }} tour{{ move.cooldown > 1 ? 's' : '' }}
                    </p>
                </div>
            </li>
        </ul>
        <p v-else class="text-xs text-slate-400">Aucune technique spéciale.</p>
        <Modal v-if="namesOnly" :show="!!selectedMove" max-width="sm" @close="selectedMove = null">
            <div v-if="selectedMove" class="space-y-3 p-5">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="text-base font-bold text-slate-700">
                        {{ selectedMove.label || selectedMove.short_label || selectedMove.key }}
                    </h3>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-slate-700"
                            @click="selectedMove = null">Fermer</button>
                </div>
                <p class="text-sm font-semibold text-teal-700">
                    {{ selectedMove.mode === 'defense' ? 'Défense' : 'Attaque' }}<template v-if="selectedMove.base_action">
                        · {{ actionLabels[selectedMove.base_action] ?? selectedMove.base_action }}
                    </template>
                </p>
                <p v-if="selectedMove.description" class="text-sm text-slate-600">{{ selectedMove.description }}</p>
            </div>
        </Modal>
    </section>
</template>
