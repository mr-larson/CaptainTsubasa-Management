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
    sponsorChallenges:{ type: Array,    default: () => [] },
    sponsorResults:   { type: Array,    default: () => [] },
    incomingMalus:    { type: Array,    default: () => [] },
    activeShields:    { type: Number,   default: 0 },
    targetTeams:      { type: Array,    default: () => [] },
    malusEnabled:     { type: Boolean,  default: true },
});

const emit = defineEmits(['buy', 'activate']);

// ==========================
//   ÉTAT LOCAL
// ==========================
const activeTab      = ref('shop');   // 'shop' | 'inventory'
const selectedCard   = ref(null);     // carte inventaire sélectionnée
const targetPlayerId = ref(null);     // pour cartes "player"
const targetTeamId   = ref(null);     // pour malus à cible choisie (target="team")

// Mode de ciblage d'une carte malus : 'opponent' | 'select' | 'leader'
const malusTargetMode = (card) => {
    if (!card) return null;
    if (card.target === 'opponent') return 'opponent';
    if (card.target === 'team') return card.effect_value?.target_mode ?? 'select';
    return null;
};

// Équipe en tête du classement (cible des cartes "leader") — targetTeams est
// déjà trié par classement côté Play.vue.
const leaderTeam = computed(() => props.targetTeams[0] ?? null);

const availableCards = computed(() => props.inventory.filter(c => c.status === 'available'));
const usedCards      = computed(() => props.inventory.filter(c => c.status === 'used'));

// Séparation bonus / malus (même onglet, sections distinctes)
const isMalus = (c) => c?.kind === 'malus';

const bonusOffers = computed(() => props.weeklyOffers.filter(o => !isMalus(o)));
const malusOffers = computed(() => props.malusEnabled ? props.weeklyOffers.filter(o => isMalus(o)) : []);

const availableBonus = computed(() => availableCards.value.filter(c => !isMalus(c)));
const availableMalus = computed(() => props.malusEnabled ? availableCards.value.filter(c => isMalus(c)) : []);

const offerGroups = computed(() => [
    { key: 'bonus', label: '🃏 Cartes Bonus', hint: 'Boostez votre équipe',                 items: bonusOffers.value },
    { key: 'malus', label: '☠️ Cartes Malus', hint: 'Sabotez l\'adversaire du prochain match', items: malusOffers.value },
].filter(g => g.items.length));

const inventoryGroups = computed(() => [
    { key: 'bonus', label: '🃏 Bonus', items: availableBonus.value },
    { key: 'malus', label: '☠️ Malus', items: availableMalus.value },
].filter(g => g.items.length));

// Effets qui ne ciblent que les joueurs blessés
const INJURY_EFFECTS = ['injury_reduce', 'injury_cure'];

// Joueurs proposés comme cible selon la carte sélectionnée :
// - cartes blessure  → uniquement les joueurs blessés
// - autres (moral, relation coach…) → tout l'effectif
const targetPlayers = computed(() => {
    if (!selectedCard.value) return [];
    if (INJURY_EFFECTS.includes(selectedCard.value.effect_type)) {
        return props.rosterWithStatus.filter(p => props.isPlayerInjured(p.id));
    }
    return props.rosterWithStatus;
});

const isInjuryCard = computed(() =>
    INJURY_EFFECTS.includes(selectedCard.value?.effect_type)
);

// Info contextuelle affichée à droite de chaque joueur cible
const targetMeta = (card, player) => {
    if (card?.effect_type === 'morale_boost') {
        return { label: `Moral ${player.morale ?? 0}`, class: 'text-amber-500' };
    }
    if (card?.effect_type === 'coach_affinity_boost') {
        return { label: `Relation ${player.coach_affinity ?? 0}`, class: 'text-sky-500' };
    }
    return null;
};

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
    self:     '👥 Équipe',
    player:   '👤 Joueur',
    match:    '⚽ Match',
    finance:  '💰 Finances',
    opponent: '🎯 Adversaire',
    team:     '🎯 Équipe ciblée',
}[target] ?? target);

// Libellés des défis sponsor (miroir de BonusCardActivationService::evaluateChallenge)
const CHALLENGE_LABELS = {
    win:         'Gagner le prochain match',
    score_3:     'Marquer au moins 3 buts au prochain match',
    clean_sheet: 'Ne pas encaisser au prochain match',
    win_by_2:    'Gagner avec au moins 2 buts d\'écart',
    win_score_3: 'Gagner en marquant au moins 3 buts',
};
const challengeLabel = (c) => CHALLENGE_LABELS[c] ?? 'Objectif au prochain match';

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
    targetTeamId.value   = null;
};

const confirmActivate = () => {
    if (!selectedCard.value) return;
    const c = selectedCard.value;
    const pid = c.target === 'player' ? targetPlayerId.value : null;
    const tid = needsTeamSelect.value ? targetTeamId.value : null;
    emit('activate', c, pid, tid);
    selectedCard.value = null;
    targetPlayerId.value = null;
    targetTeamId.value = null;
};

const cancelActivate = () => {
    selectedCard.value   = null;
    targetPlayerId.value = null;
    targetTeamId.value   = null;
};

const needsTarget = computed(() =>
    selectedCard.value?.target === 'player'
);

// Sélection d'équipe requise : malus à cible libre (target="team", mode "select")
const needsTeamSelect = computed(() =>
    malusTargetMode(selectedCard.value) === 'select'
);

const isSelectedMalus = computed(() => isMalus(selectedCard.value));

const canConfirm = computed(() => {
    if (!selectedCard.value) return false;
    if (needsTarget.value && !targetPlayerId.value) return false;
    if (needsTeamSelect.value && !targetTeamId.value) return false;
    return true;
});
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

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
        <!-- DÉFIS SPONSOR (cartes finance)                    -->
        <!-- ================================================ -->
        <div v-if="sponsorChallenges.length || sponsorResults.length" class="flex flex-col gap-2">

            <!-- Défis en cours -->
            <div v-for="(ch, i) in sponsorChallenges" :key="'pending-' + i"
                 class="flex items-center gap-3 border border-sky-200 bg-sky-50 rounded-xl px-3 py-2">
                <span class="text-xl">{{ ch.icon || '🤝' }}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-sky-800">Défi en cours — {{ ch.card_name }}</div>
                    <div class="text-[11px] text-sky-600">{{ challengeLabel(ch.challenge) }}</div>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-sm font-black text-sky-700">+{{ ch.reward }} €</div>
                    <div class="text-[9px] font-semibold text-sky-500">si réussi</div>
                </div>
            </div>

            <!-- Résultats de la dernière clôture -->
            <div v-for="(r, i) in sponsorResults" :key="'result-' + i"
                 class="flex items-center gap-3 border rounded-xl px-3 py-2"
                 :class="r.success ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50'">
                <span class="text-xl">{{ r.icon || '🤝' }}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold" :class="r.success ? 'text-emerald-800' : 'text-rose-800'">
                        {{ r.success ? '✓ Défi réussi' : '✗ Défi manqué' }} — {{ r.card_name }}
                    </div>
                    <div class="text-[11px]" :class="r.success ? 'text-emerald-600' : 'text-rose-600'">
                        {{ challengeLabel(r.challenge) }} · résultat {{ r.scored }}–{{ r.against }}
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-sm font-black" :class="r.success ? 'text-emerald-700' : 'text-rose-400'">
                        {{ r.success ? '+' + r.reward + ' €' : '0 €' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================ -->
        <!-- BOUCLIER ANTI-MALUS ACTIF (Cellule de crise)      -->
        <!-- ================================================ -->
        <div v-if="activeShields > 0"
             class="flex items-center gap-3 border border-emerald-300 bg-emerald-50 rounded-xl px-3 py-2">
            <span class="text-xl">🛡️</span>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-emerald-800">
                    Cellule de crise active<template v-if="activeShields > 1"> ×{{ activeShields }}</template>
                </div>
                <div class="text-[11px] text-emerald-600">
                    Le prochain malus infligé à votre équipe sera automatiquement annulé.
                </div>
            </div>
        </div>

        <!-- ================================================ -->
        <!-- MALUS REÇUS (titulaire consigné contre vous)      -->
        <!-- ================================================ -->
        <div v-if="malusEnabled && incomingMalus.length" class="flex flex-col gap-2">
            <div v-for="(m, i) in incomingMalus" :key="'malus-in-' + i"
                 class="flex items-center gap-3 border border-rose-300 bg-rose-50 rounded-xl px-3 py-2">
                <span class="text-xl">{{ m.icon || '🚫' }}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-rose-800">Malus subi — {{ m.card_name || 'Titulaire consigné' }}</div>
                    <div class="text-[11px] text-rose-600">
                        {{ m.player_name }} ne pourra pas débuter votre prochain match.
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================ -->
        <!-- BOUTIQUE                                          -->
        <!-- ================================================ -->
        <div v-if="activeTab === 'shop'">

            <div v-if="weeklyOffers.length" class="flex flex-col gap-5">
                <div v-for="group in offerGroups" :key="group.key">
                    <div class="flex items-baseline gap-2 mb-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider"
                            :class="group.key === 'malus' ? 'text-rose-600' : 'text-slate-500'">
                            {{ group.label }}
                        </h4>
                        <span class="text-[10px] text-slate-400">{{ group.hint }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="(offer, i) in group.items" :key="group.key + '-' + i"
                             class="border-2 rounded-2xl p-4 flex flex-col gap-3 shadow-sm transition-all"
                             :class="[ tierClasses(offer.tier).border,tierClasses(offer.tier).bg,tierClasses(offer.tier).glow,
                                       isAlreadyBought(offer) ? 'opacity-60' : '',
                                       group.key === 'malus' ? 'ring-1 ring-rose-300' : '']">
                            <!-- Header carte -->
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-3xl">{{ offer.icon }}</span>
                                    <div>
                                        <div class="text-sm font-black text-slate-800">{{ offer.name }}</div>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                  :class="tierClasses(offer.tier).badge">
                                                {{ tierClasses(offer.tier).label }}
                                            </span>
                                            <span v-if="group.key === 'malus'"
                                                  class="text-[9px] font-black px-1.5 py-0.5 rounded-full bg-rose-500 text-white">
                                                MALUS
                                            </span>
                                        </div>
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
                                    : !canAfford(offer.cost)
                                        ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
                                        : group.key === 'malus'
                                            ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-sm active:scale-95'
                                            : 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm active:scale-95'">
                                    {{ isAlreadyBought(offer) ? '✓ Déjà achetée' : canAfford(offer.cost) ? 'Acheter' : 'Budget insuffisant' }}
                            </button>
                        </div>
                    </div>
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

                <div v-if="availableCards.length" class="flex flex-col gap-4">
                    <div v-for="group in inventoryGroups" :key="group.key">
                        <h5 class="text-[11px] font-bold uppercase tracking-wider mb-2"
                            :class="group.key === 'malus' ? 'text-rose-600' : 'text-slate-400'">
                            {{ group.label }} ({{ group.items.length }})
                        </h5>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="card in group.items" :key="card.id"
                                 class="border-2 rounded-xl p-3 flex flex-col gap-2 cursor-pointer transition-all hover:shadow-md active:scale-95"
                                 :class="[tierClasses(card.tier).border, tierClasses(card.tier).bg,
                                          group.key === 'malus' ? 'ring-1 ring-rose-300' : '']"
                                 @click="openActivate(card)">

                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ card.icon }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-black text-slate-800 truncate">{{ card.name }}</div>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                                                  :class="tierClasses(card.tier).badge">
                                                {{ tierClasses(card.tier).label }}
                                            </span>
                                            <span v-if="group.key === 'malus'"
                                                  class="text-[9px] font-black px-1.5 py-0.5 rounded-full bg-rose-500 text-white">
                                                MALUS
                                            </span>
                                        </div>
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

                                <div class="text-[10px] font-semibold text-center rounded-lg py-1"
                                     :class="group.key === 'malus' ? 'text-rose-600 bg-rose-50' : 'text-teal-600 bg-teal-50'">
                                    Cliquer pour activer
                                </div>
                            </div>
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

                        <div v-if="targetPlayers.length" class="space-y-1 max-h-40 overflow-y-auto">
                            <button v-for="p in targetPlayers" :key="p.id"
                                    type="button"
                                    @click="targetPlayerId = p.id"
                                    class="w-full flex items-center px-3 py-2 rounded-lg text-xs border transition-all"
                                    :class="targetPlayerId === p.id
                                        ? 'bg-teal-500 text-white border-teal-600'
                                        : 'bg-white border-slate-200 hover:border-teal-300 text-slate-700'">
                                <span class="font-semibold">{{ p.lastname }}</span>
                                <span class="opacity-70 ml-1">— {{ p.position }}</span>
                                <span v-if="isInjuryCard" class="ml-1 text-rose-400">🤕</span>
                                <span v-if="targetMeta(selectedCard, p)"
                                      class="ml-auto font-semibold"
                                      :class="targetPlayerId === p.id ? 'text-white/90' : targetMeta(selectedCard, p).class">
                                    {{ targetMeta(selectedCard, p).label }}
                                </span>
                            </button>
                        </div>

                        <p v-else class="text-xs text-slate-400 italic">
                            {{ isInjuryCard ? 'Aucun joueur blessé dans votre effectif.' : 'Aucun joueur dans votre effectif.' }}
                        </p>
                    </div>

                    <!-- Malus : cible = adversaire du prochain match -->
                    <div v-if="isSelectedMalus && malusTargetMode(selectedCard) === 'opponent'" class="p-5 border-b border-slate-100">
                        <div class="flex items-center gap-2 text-rose-700 text-sm font-semibold">
                            <span class="text-lg">🎯</span>
                            <span>Cible : l'adversaire de votre prochain match</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            L'effet s'applique automatiquement à l'adversaire de votre prochain match programmé.
                        </p>
                    </div>

                    <!-- Malus : cible = 1er du classement (auto) -->
                    <div v-else-if="isSelectedMalus && malusTargetMode(selectedCard) === 'leader'" class="p-5 border-b border-slate-100">
                        <div class="flex items-center gap-2 text-rose-700 text-sm font-semibold">
                            <span class="text-lg">👑</span>
                            <span>Cible : le 1<sup>er</sup> du classement<template v-if="leaderTeam"> — {{ leaderTeam.name }}</template></span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            L'effet vise automatiquement l'équipe en tête du classement.
                        </p>
                    </div>

                    <!-- Malus : sélection libre de l'équipe cible -->
                    <div v-else-if="needsTeamSelect" class="p-5 border-b border-slate-100">
                        <p class="text-xs font-bold text-rose-600 uppercase tracking-wider mb-2">
                            🎯 Choisir l'équipe à saboter
                        </p>
                        <div v-if="targetTeams.length" class="space-y-1 max-h-44 overflow-y-auto">
                            <button v-for="t in targetTeams" :key="t.id"
                                    type="button"
                                    @click="targetTeamId = t.id"
                                    class="w-full flex items-center px-3 py-2 rounded-lg text-xs border transition-all"
                                    :class="targetTeamId === t.id
                                        ? 'bg-rose-500 text-white border-rose-600'
                                        : 'bg-white border-slate-200 hover:border-rose-300 text-slate-700'">
                                <span class="font-semibold">{{ t.name }}</span>
                                <span class="ml-auto font-semibold"
                                      :class="targetTeamId === t.id ? 'text-white/90' : 'text-slate-400'">
                                    {{ t.rank }}<sup>e</sup>
                                </span>
                            </button>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Aucune autre équipe à cibler.</p>
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
                                :class="!canConfirm
                                    ? 'bg-slate-200 text-slate-400'
                                    : isSelectedMalus
                                        ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-sm'
                                        : 'bg-teal-500 hover:bg-teal-600 text-white shadow-sm'">
                            {{ isSelectedMalus ? '☠️ Infliger le malus' : '✨ Activer la carte' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </div>
</template>
