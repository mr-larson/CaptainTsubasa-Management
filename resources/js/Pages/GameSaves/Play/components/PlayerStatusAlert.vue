<script setup>
import { usePlayerStatus } from '../usePlayerStatus.js';
import { usePlayerUtils } from '../usePlayerUtils.js';

/** Bandeau d'alerte blessure / suspension / malus du joueur sélectionné. */
defineProps({
    player:            { type: Object,   required: true },
    isPlayerBenched:   { type: Function, default: () => false },
    playerBench:       { type: Function, default: () => null },
});

const { sanctionTypeLabel } = usePlayerUtils();
const { isPlayerInjured, isPlayerSuspended, playerInjury, playerSuspension } = usePlayerStatus();
</script>

<template>
    <div v-if="isPlayerInjured(player.id)"
         class="border border-rose-200 rounded-xl bg-rose-50 px-4 py-2.5 flex items-center gap-2 shadow-card">
        <span class="text-lg">🤕</span>
        <div>
            <div class="text-xs font-bold text-rose-700">Joueur blessé</div>
            <div class="text-2xs text-rose-500">
                {{ playerInjury(player.id)?.description ?? 'Blessure' }}
                — Retour semaine {{ playerInjury(player.id)?.week_return ?? '—' }}
            </div>
        </div>
    </div>
    <div v-else-if="isPlayerSuspended(player.id)"
         class="border border-amber-200 rounded-xl bg-amber-50 px-4 py-2.5 flex items-center gap-2 shadow-card">
        <span class="text-lg">🚫</span>
        <div>
            <div class="text-xs font-bold text-amber-700">Joueur suspendu</div>
            <div class="text-2xs text-amber-500">
                {{ sanctionTypeLabel(playerSuspension(player.id)?.type) }}
                <template v-if="playerSuspension(player.id)?.weeks_suspended">
                    — {{ playerSuspension(player.id).weeks_suspended }} semaine(s)
                </template>
                — Retour semaine {{ playerSuspension(player.id)?.week_return ?? '—' }}
            </div>
        </div>
    </div>
    <div v-else-if="isPlayerBenched(player.id)"
         class="border border-rose-300 rounded-xl bg-rose-50 px-4 py-2.5 flex items-center gap-2 shadow-card">
        <span class="text-lg">⛔</span>
        <div>
            <div class="text-xs font-bold text-rose-700">Joueur consigné par l'adversaire</div>
            <div class="text-2xs text-rose-500">
                {{ playerBench(player.id)?.card_name ?? 'Malus adverse' }}
                — ne peut pas débuter le prochain match. Sortez-le du onze de départ.
            </div>
        </div>
    </div>
</template>
