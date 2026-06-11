<script setup>
import { usePlayerUtils } from './usePlayerUtils.js';

/** Bandeau d'alerte blessure / suspension du joueur sélectionné. */
const props = defineProps({
    player:            { type: Object,   required: true },
    isPlayerInjured:   { type: Function, default: () => false },
    isPlayerSuspended: { type: Function, default: () => false },
    playerInjury:      { type: Function, default: () => null },
    playerSuspension:  { type: Function, default: () => null },
});

const { sanctionTypeLabel } = usePlayerUtils();
</script>

<template>
    <div v-if="isPlayerInjured(player.id)"
         class="border border-rose-200 rounded-xl bg-rose-50 px-4 py-2.5 flex items-center gap-2">
        <span class="text-lg">🤕</span>
        <div>
            <div class="text-xs font-bold text-rose-700">Joueur blessé</div>
            <div class="text-[10px] text-rose-500">
                {{ playerInjury(player.id)?.description ?? 'Blessure' }}
                — Retour semaine {{ playerInjury(player.id)?.week_return ?? '—' }}
            </div>
        </div>
    </div>
    <div v-else-if="isPlayerSuspended(player.id)"
         class="border border-amber-200 rounded-xl bg-amber-50 px-4 py-2.5 flex items-center gap-2">
        <span class="text-lg">🚫</span>
        <div>
            <div class="text-xs font-bold text-amber-700">Joueur suspendu</div>
            <div class="text-[10px] text-amber-500">
                {{ sanctionTypeLabel(playerSuspension(player.id)?.type) }}
                <template v-if="playerSuspension(player.id)?.weeks_suspended">
                    — {{ playerSuspension(player.id).weeks_suspended }} semaine(s)
                </template>
                — Retour semaine {{ playerSuspension(player.id)?.week_return ?? '—' }}
            </div>
        </div>
    </div>
</template>
