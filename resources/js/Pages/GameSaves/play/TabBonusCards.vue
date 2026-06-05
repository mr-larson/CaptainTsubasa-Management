<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    weeklyOffers:     { type: Array,    default: () => [] },
    inventory:        { type: Array,    default: () => [] },
    teamBudget:       { type: Number,   default: 0 },
    rosterWithStatus: { type: Array,    default: () => [] },
    season:           { type: Number,   required: true },
    week:             { type: Number,   required: true },
    isAlreadyBought:  { type: Function, default: () => false },
    isPlayerInjured:  { type: Function, default: () => false },
});

const emit = defineEmits(['buy', 'activate']);

// ==========================
//   ÉTAT LOCAL
// ==========================
const activeTab      = ref('shop');   // 'shop' | 'inventory'
const selectedCard   = ref(null);     // carte inventaire sélectionnée
const targetPlayerId = ref(null);     // pour cartes "player"

const availableCards = computed(() => props.inventory.filter(c => c.status === 'available'));
const usedCards      = computed(() => props.inventory.filter(c => c.status === 'used'));

// Joueurs blessés disponibles comme cible
const injuredPlayers = computed(() =>
    props.rosterWithStatus.filter(p => props.isPlayerInjured(p.id))
);

// ==========================
//   HELPERS VISUELS
// ==========================
const tierClasses = (tier) => ({
    bronze: { border: 'border-amber-300', bg: 'bg-amber-50',   badge: 'bg-amber-500 text-white',      label: 'Bronze', glow: 'shadow-amber-100' },
    silver: { border: 'border-slate-300', bg: 'bg-slate-50',   badge: 'bg-slate-400 text-white',      label: 'Argent', glow: 'shadow-slate-100' },
    gold:   { border: 'border-yellow-300',bg: 'bg-yellow-50',  badge: 'bg-yellow-400 text-slate-900', label: 'Or',     glow: 'shadow-yellow-100' },
}[tier] ?? { border: 'border-slate-200', bg: 'bg-white', badge: 'bg-slate-200', label: '?', glow: '' });

const phaseLabel = (phase) => ({
    immediate:    '⚡ Immédiat',
    pre_match:    '🏟️ Pré-match',
    post_match:   '🔚 Post-match',
    weekly_reset: '📅 Hebdo',
}[phase] ?? phase);

const targetLabel = (target) => ({
    self:    '👥 Équipe',
    player:  '👤 Joueur',
    match:   '⚽ Match',
    finance: '💰 Finances',
}[target] ?? target);

const canAfford = (cost) => props.teamBudget >= cost;

// ==========================
//   ACTIONS
// ==========================
const buyOffer = (offer) => {
    if (!canAfford(offer.cost)) return;
    emit('buy', offer);
};

const openActivate = (card) => {
    selectedCard.value   = card;
    targetPlayerId.value = null;
};

const confirmActivate = () => {
    if (!selectedCard.value) return;
    emit('activate', selectedCard.value, selectedCard.value.target === 'player' ? targetPlayerId.value : null);
    selectedCard.value = null;
};

const cancelActivate = () => {
    selectedCard.value   = null;
    targetPlayerId.value = null;
};

const needsTarget = computed(() =>
    selectedCard.value?.target === 'player'
);

const canConfirm = computed(() => {
    if (!selectedCard.value) return false;
    if (needsTarget.value && !targetPlayerId.value) return false;
    return true;
});
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto max-h-[75vh] pr-1">

        <!-- HEADER -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cartes Bonus</h3>
                    <p class="text-sm text-slate-600 mt-1">Saison {{ season }} — Semaine {{ week }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-center px-4 py-2 rounded-xl border bg-emerald-50 border-emerald-200">
                        <div class="text-lg font-black text-emerald-600">{{ teamBudget }} €</div>
                        <div class="text-[10px] font-semibold text-emerald-500">Budget</div>
                    </div>
                    <div class="text-center px-4 py-2 rounded-xl border bg-teal-50 border-teal-200">
                        <div class="text-lg font-black text-teal-600">{{ availableCards.length }}</div>
                        <div class="text-[10px] font-semibold text-teal-500">En inventaire</div>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <div class="flex gap-1 mt-4 border-b border-slate-200">
                <button type="button"
                        @click="activeTab = 'shop'"
                        class="px-4 py-2 text-xs font-semibold border-b-2 transition-all"
                        :class="activeTab === 'shop' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    🛒 Boutique
                </button>
                <button type="button"
                        @click="activeTab = 'inventory'"
                        class="px-4 py-2 text-xs font-semibold border-b-2 transition-all"
                        :class="activeTab === 'inventory' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    🎒 Inventaire
                    <span v-if="availableCards.length"
                          class="ml-1 bg-teal-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full">
                        {{ availableCards.length }}
                    </span>
                </button>
            </div>
        </div>

        <!-- ================================================ -->
        <!-- BOUTIQUE                                          -->
        <!-- ================================================ -->
        <div v-if="activeTab === 'shop'">

            <div v-if="weeklyOffers.length" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="(offer, i) in weeklyOffers" :key="i"
                     class="border-2 rounded-2xl p-4 flex flex-col gap-3 shadow-sm transition-all"
                     :class="[ tierClasses(offer.tier).border,tierClasses(offer.tier).bg,tierClasses(offer.tier).glow,isAlreadyBought(offer) ? 'opacity-60' : '']">
                    <!-- Header carte -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-3xl">{{ offer.icon }}</span>
                            <div>
                                <div class="text-sm font-black text-slate-800">{{ offer.name }}</div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                      :class="tierClasses(offer.tier).badge">
                                    {{ tierClasses(offer.tier).label }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-lg font-black" :class="canAfford(offer.cost) ? 'text-emerald-600' : 'text-rose-500'">
                                {{ offer.cost }} €
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-xs text-slate-600 leading-relaxed flex-1">{{ offer.description }}</p>

                    <!-- Tags -->
                    <div class="flex gap-1.5 flex-wrap">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white/70 border border-slate-200 text-slate-600">
                            {{ phaseLabel(offer.execution_phase) }}
                        </span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white/70 border border-slate-200 text-slate-600">
                            {{ targetLabel(offer.target) }}
                        </span>
                    </div>

                    <!-- Bouton achat -->
                    <button type="button"
                            @click="buyOffer(offer)"
                            :disabled="!canAfford(offer.cost) || isAlreadyBought(offer)"
                            class="w-full py-2 rounded-xl text-xs font-bold transition-all"
                            :class="isAlreadyBought(offer)
                            ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
                            : canAfford(offer.cost)
                                ? 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm active:scale-95'
                                : 'bg-slate-200 text-slate-400 cursor-not-allowed'">
                            {{ isAlreadyBought(offer) ? '✓ Déjà achetée' : canAfford(offer.cost) ? 'Acheter' : 'Budget insuffisant' }}
                    </button>
                </div>
            </div>

            <div v-else class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-12 text-center">
                <div>
                    <div class="text-4xl mb-2">🃏</div>
                    <p class="text-slate-600 font-semibold">Boutique indisponible</p>
                    <p class="text-xs text-slate-400 mt-1">Jouez ou simulez la semaine pour débloquer les offres.</p>
                </div>
            </div>
        </div>

        <!-- ================================================ -->
        <!-- INVENTAIRE                                        -->
        <!-- ================================================ -->
        <div v-if="activeTab === 'inventory'" class="flex flex-col gap-4">

            <!-- Cartes disponibles -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                    ✅ Disponibles ({{ availableCards.length }})
                </h4>

                <div v-if="availableCards.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="card in availableCards" :key="card.id"
                         class="border-2 rounded-xl p-3 flex flex-col gap-2 cursor-pointer transition-all hover:shadow-md active:scale-95"
                         :class="[tierClasses(card.tier).border, tierClasses(card.tier).bg]"
                         @click="openActivate(card)">

                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ card.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-black text-slate-800 truncate">{{ card.name }}</div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                                      :class="tierClasses(card.tier).badge">
                                    {{ tierClasses(card.tier).label }}
                                </span>
                            </div>
                        </div>

                        <p class="text-[10px] text-slate-600 leading-relaxed">{{ card.description }}</p>

                        <div class="flex gap-1 flex-wrap">
                            <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full bg-white/80 border border-slate-200 text-slate-500">
                                {{ phaseLabel(card.execution_phase) }}
                            </span>
                            <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full bg-white/80 border border-slate-200 text-slate-500">
                                {{ targetLabel(card.target) }}
                            </span>
                        </div>

                        <div class="text-[10px] text-teal-600 font-semibold text-center bg-teal-50 rounded-lg py-1">
                            Cliquer pour activer
                        </div>
                    </div>
                </div>

                <p v-else class="text-xs text-slate-400 italic text-center py-4">
                    Aucune carte disponible. Achetez des cartes dans la boutique !
                </p>
            </div>

            <!-- Cartes utilisées -->
            <div v-if="usedCards.length" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                    ✓ Utilisées ({{ usedCards.length }})
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <div v-for="card in usedCards" :key="card.id"
                         class="border rounded-xl p-2 opacity-50 flex items-center gap-2 border-slate-200 bg-slate-100">
                        <span class="text-xl">{{ card.icon }}</span>
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold text-slate-600 truncate">{{ card.name }}</div>
                            <div class="text-[9px] text-slate-400">S{{ card.used_season }}·S{{ card.used_week }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================ -->
        <!-- MODAL ACTIVATION                                  -->
        <!-- ================================================ -->
        <Teleport to="body">
            <div v-if="selectedCard"
                 class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
                 @click.self="cancelActivate">

                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border-2"
                     :class="tierClasses(selectedCard.tier).border">

                    <!-- Header -->
                    <div class="p-5 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <span class="text-4xl">{{ selectedCard.icon }}</span>
                            <div>
                                <h3 class="font-black text-slate-800">{{ selectedCard.name }}</h3>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                                      :class="tierClasses(selectedCard.tier).badge">
                                    {{ tierClasses(selectedCard.tier).label }}
                                </span>
                            </div>
                        </div>
                        <p class="text-sm text-slate-600 mt-3 leading-relaxed">{{ selectedCard.description }}</p>
                    </div>

                    <!-- Sélection joueur si target=player -->
                    <div v-if="needsTarget" class="p-5 border-b border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            Sélectionner un joueur cible
                        </p>

                        <div v-if="injuredPlayers.length" class="space-y-1 max-h-40 overflow-y-auto">
                            <button v-for="p in injuredPlayers" :key="p.id"
                                    type="button"
                                    @click="targetPlayerId = p.id"
                                    class="w-full text-left px-3 py-2 rounded-lg text-xs border transition-all"
                                    :class="targetPlayerId === p.id
                                        ? 'bg-teal-500 text-white border-teal-600'
                                        : 'bg-white border-slate-200 hover:border-teal-300 text-slate-700'">
                                <span class="font-semibold">{{ p.lastname }}</span>
                                <span class="opacity-70 ml-1">— {{ p.position }}</span>
                                <span class="ml-1 text-rose-400">🤕</span>
                            </button>
                        </div>

                        <p v-else class="text-xs text-slate-400 italic">
                            Aucun joueur blessé dans votre effectif.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 flex gap-2 justify-end">
                        <button type="button"
                                @click="cancelActivate"
                                class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                            Annuler
                        </button>
                        <button type="button"
                                @click="confirmActivate"
                                :disabled="!canConfirm"
                                class="px-5 py-2 text-xs font-bold rounded-xl transition-all disabled:opacity-40"
                                :class="canConfirm
                                    ? 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm'
                                    : 'bg-slate-200 text-slate-400'">
                            ✨ Activer la carte
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </div>
</template>
