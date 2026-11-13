<script setup>
import PlayerAvatar from '@/Components/PlayerAvatar.vue';
import { positionLabel } from '@/Enums/positionBadges.js';

/**
 * Ligne d'un joueur recrutable (Draft, Transferts) : photo, nom, résumé
 * « poste · note · endurance · moral », prix. Slots : `name-suffix`, `action`.
 */
defineProps({
    player:     { type: Object,  required: true },
    selected:   { type: Boolean, default: false },
    overall:    { type: Number,  default: null },
    price:      { type: [Number, String], default: null },
    priceSub:   { type: String,  default: null },
    affordable: { type: Boolean, default: true },
});
defineEmits(['select']);
</script>

<template>
    <div class="flex items-center gap-2 rounded-lg border pr-2 transition-colors"
         :class="selected ? 'border-teal-300 bg-teal-50' : 'border-slate-200 bg-white hover:bg-slate-50'">
        <button type="button" class="flex min-w-0 flex-1 items-center gap-2 rounded-lg py-2 pl-2 text-left"
                :aria-pressed="selected" @click="$emit('select', player)">
            <PlayerAvatar :player="player" show-flag :zoomable="false" />
            <span class="min-w-0 flex-1">
                <span class="block truncate text-xs font-semibold text-slate-700">
                    {{ player.firstname }} {{ player.lastname }}
                    <slot name="name-suffix" />
                </span>
                <span class="block text-2xs text-slate-500">
                    {{ positionLabel(player.position) }} · Note {{ overall ?? '—' }} · End. {{ player.stamina ?? player.stats?.stamina ?? '—' }} · Moral {{ player.morale ?? 60 }}
                </span>
            </span>
            <span v-if="price != null" class="shrink-0 text-right text-xs font-bold tabular-nums"
                  :class="affordable ? 'text-teal-700' : 'text-rose-600'">
                {{ price }} <GameBanknote />
                <span v-if="priceSub" class="block text-2xs font-normal text-slate-500">{{ priceSub }}</span>
            </span>
        </button>
        <slot name="action" />
    </div>
</template>
