<script setup>
import { usePlayerUtils } from '../usePlayerUtils.js';

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

const { overallOf, playerPhotoUrl } = usePlayerUtils();
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
        <div class="flex items-start gap-4">
            <!-- Photo + overall -->
            <div class="relative shrink-0">
                <div class="w-20 h-20 rounded-xl border-2 border-slate-200 bg-white overflow-hidden">
                    <img v-if="playerPhotoUrl(player)" :src="playerPhotoUrl(player)" class="w-full h-full object-cover" alt=""/>
                    <div v-else class="w-full h-full flex items-center justify-center text-3xl text-slate-200">👤</div>
                </div>
                <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-[11px] font-black shadow"
                     :class="overallOf(player)>=80?'bg-emerald-500 text-white':overallOf(player)>=65?'bg-teal-500 text-white':overallOf(player)>=50?'bg-amber-400 text-slate-900':'bg-slate-400 text-white'">
                    {{ overallOf(player) }}
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <h3 class="text-base font-bold text-slate-800">{{ player.firstname }} {{ player.lastname }}</h3>

                <div v-if="showCaptainBadge && player.is_captain" class="flex items-center gap-1.5 mt-1">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wide">
                        👑 Capitaine
                    </span>
                    <span class="text-[10px] text-slate-400">
                        {{ player.captain_rerolls_remaining ?? 3 }} relances/match
                    </span>
                </div>

                <p class="text-xs text-slate-400 mt-0.5">
                    {{ subtitle ?? `${player.position} • ${player.cost ?? 0} €` }}
                </p>

                <div v-if="$slots.actions" class="mt-3 flex flex-wrap items-center gap-2">
                    <slot name="actions" />
                </div>

                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
