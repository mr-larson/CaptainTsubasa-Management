<script setup>
import { usePlayerUtils } from '../usePlayerUtils.js';

/**
 * Liste d'effectif partagée (Mon Équipe / Entraînement / Autres équipes) :
 * photo, nom, poste, moral, statuts (capitaine, blessure, suspension, cartons),
 * stamina et point titulaire. Slot #badge pour un badge spécifique par onglet.
 */
const props = defineProps({
    players:           { type: Array,  required: true },
    selectedId:        { type: [Number, String], default: null },
    title:             { type: String, default: 'Effectif' },
    isPlayerInjured:   { type: Function, default: () => false },
    isPlayerSuspended: { type: Function, default: () => false },
    playerYellowCards: { type: Function, default: () => 0 },
    playerInjury:      { type: Function, default: () => null },
    playerSuspension:  { type: Function, default: () => null },
    showMorale:        { type: Boolean, default: true },
    showStarterDot:    { type: Boolean, default: true },
    // Ligne teintée même non sélectionnée (ex : déjà entraîné cette semaine)
    rowHighlight:      { type: Function, default: () => false },
});

const emit = defineEmits(['select']);

const { playerPhotoUrl, sanctionTypeLabel, moraleState } = usePlayerUtils();

const staminaOf = (p) => p.stamina ?? p.stats?.stamina ?? 0;

const injuryTitle = (p) => {
    const inj = props.playerInjury(p.id);
    return inj ? `${inj.description ?? 'Blessé'} — Retour S${inj.week_return}` : 'Blessé';
};

const suspensionTitle = (p) => {
    const s = props.playerSuspension(p.id);
    return s ? `${sanctionTypeLabel(s.type)} — Retour S${s.week_return ?? '—'}` : 'Suspendu';
};
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-3 overflow-y-auto">
        <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">{{ title }}</h3>
        <div v-if="players.length" class="space-y-1">
            <button v-for="p in players" :key="p.id" type="button"
                    @click="emit('select', p)"
                    class="w-full text-left rounded-lg px-2 py-1.5 transition-all border"
                    :class="selectedId === p.id
                        ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                        : rowHighlight(p)
                            ? 'bg-teal-50 border-teal-200 text-slate-700'
                            : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-100'">
                <div class="flex items-center gap-2">
                    <!-- Photo -->
                    <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-200 shrink-0">
                        <img v-if="playerPhotoUrl(p)" :src="playerPhotoUrl(p)" class="w-full h-full object-cover" alt=""/>
                        <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                    </div>

                    <!-- Nom + poste -->
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold truncate">{{ p.lastname }}</div>
                        <div class="text-[10px] opacity-60 truncate">{{ p.position }}</div>
                    </div>

                    <!-- Icônes statut -->
                    <div class="flex items-center gap-0.5 shrink-0">
                        <span v-if="showMorale"
                              :title="`Moral : ${p.morale ?? 60} (${moraleState(p.morale).label})`"
                              class="text-[11px]">{{ moraleState(p.morale).emoji }}</span>
                        <span v-if="p.is_captain" title="Capitaine" class="text-[11px]">👑</span>
                        <span v-if="isPlayerInjured(p.id)" :title="injuryTitle(p)" class="text-[11px]">🤕</span>
                        <span v-else-if="isPlayerSuspended(p.id)" :title="suspensionTitle(p)" class="text-[11px]">🚫</span>
                        <span v-else-if="playerYellowCards(p.id) > 0"
                              :title="`${playerYellowCards(p.id)} carton(s) jaune`"
                              class="text-[9px] font-black bg-yellow-400 text-yellow-900 px-1 rounded">
                            {{ playerYellowCards(p.id) }}🟨
                        </span>
                    </div>

                    <!-- Stamina bar + valeur -->
                    <div class="w-12 flex flex-col items-end gap-0.5 shrink-0">
                        <div class="text-[10px] font-bold"
                             :class="selectedId === p.id ? 'text-white/80' : 'text-slate-500'">
                            {{ p.stamina ?? p.stats?.stamina ?? '—' }}
                        </div>
                        <div class="w-full h-1 rounded-full overflow-hidden"
                             :class="selectedId === p.id ? 'bg-white/30' : 'bg-slate-200'">
                            <div class="h-full rounded-full transition-all"
                                 :class="staminaOf(p) >= 60 ? 'bg-emerald-400'
                                     : staminaOf(p) >= 30 ? 'bg-amber-400' : 'bg-rose-400'"
                                 :style="{ width: Math.min(staminaOf(p), 100) + '%' }">
                            </div>
                        </div>
                    </div>

                    <!-- Badge spécifique à l'onglet (ex : ✓ entraîné) -->
                    <slot name="badge" :player="p" :selected="selectedId === p.id" />

                    <!-- Titulaire dot -->
                    <div v-if="showStarterDot" class="w-2 h-2 rounded-full shrink-0"
                         :class="p.is_starter ? 'bg-emerald-400' : 'bg-slate-300'"></div>
                </div>
            </button>
        </div>
        <p v-else class="text-xs text-slate-400">Aucun joueur.</p>
    </div>
</template>
