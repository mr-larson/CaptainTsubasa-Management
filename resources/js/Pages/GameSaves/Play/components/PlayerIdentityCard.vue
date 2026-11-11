<script setup>
import { positionLabel } from '@/Enums/positionBadges.js';
import { usePlayerUtils } from '../usePlayerUtils.js';
import SecondaryPositions from './SecondaryPositions.vue';
import Panel from '@/Components/Panel.vue';
import PlayerAvatar from '@/Components/PlayerAvatar.vue';

/**
 * Carte identité du joueur sélectionné : photo + badge overall + nom +
 * badge capitaine + sous-titre. Slots :
 *  - #actions : boutons d'action (titulaire, capitaine, entraînement…)
 *  - #footer  : contenu libre sous les actions (astuces, description…)
 */
const props = defineProps({
    player:           { type: Object,  required: true },
    subtitle:         { type: String,  default: null },
    showCaptainBadge: { type: Boolean, default: true },
});

const { overallOf } = usePlayerUtils();
</script>

<template>
    <Panel>
        <div class="flex items-start gap-4">
            <!-- Photo + overall -->
            <PlayerAvatar :player="player" size="lg" shape="rounded" show-flag>
                <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-[11px] font-black shadow"
                     :class="overallOf(player)>=80?'bg-emerald-500 text-white':overallOf(player)>=65?'bg-teal-500 text-white':overallOf(player)>=50?'bg-amber-400 text-slate-700':'bg-slate-400 text-white'">
                    {{ overallOf(player) }}
                </div>
            </PlayerAvatar>

            <div class="flex-1 min-w-0">
                <h3 class="text-base font-bold text-slate-700">{{ player.firstname }} {{ player.lastname }}</h3>

                <div v-if="showCaptainBadge && player.is_captain" class="flex items-center gap-1.5 mt-1">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-2xs font-bold uppercase tracking-wide">
                        👑 Capitaine
                    </span>
                    <span class="text-2xs text-slate-400">
                        {{ player.captain_rerolls_remaining ?? 3 }} relances/match
                    </span>
                </div>

                <p class="text-xs text-slate-400 mt-0.5">
                    <template v-if="subtitle != null">{{ subtitle }}</template>
                    <template v-else>{{ positionLabel(player.position) }} • {{ player.cost ?? 0 }} <GameBanknote /></template>
                </p>

                <SecondaryPositions :player="player" class="mt-1.5" />

                <div v-if="$slots.actions" class="mt-3 flex flex-wrap items-center gap-2">
                    <slot name="actions" />
                </div>

                <slot name="footer" />
            </div>
        </div>
    </Panel>
</template>
