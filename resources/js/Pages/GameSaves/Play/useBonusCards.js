// resources/js/Pages/GameSaves/Play/useBonusCards.js
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useBonusCards({ gameSave, bonusCardOffers, bonusCardInventory }) {

    const selectedCard = ref(null);
    const activateTarget = ref(null); // pour les cartes "player"

    // ==========================
    //   OFFRES DE LA SEMAINE
    // ==========================
    const weeklyOffers = computed(() => bonusCardOffers?.value ?? []);

    const purchasedThisWeek = computed(() => {
        const s = Number(gameSave.value?.season ?? 0);
        const w = Number(gameSave.value?.week   ?? 0);
        if (s === 0 || w === 0) return [];

        return (bonusCardInventory?.value ?? [])
            .filter(c =>
                Number(c.purchased_season) === s &&
                Number(c.purchased_week)   === w
            )
            .map(c => Number(c.bonus_card_id))
            .filter(id => id > 0);
    });

// Une offre est "déjà achetée cette semaine" si son bonus_card_id
// figure dans les achats de la semaine courante
    const isAlreadyBought = (offer) =>
        purchasedThisWeek.value.includes(Number(offer.bonus_card_id));

    const canAfford = (offer) => {
        const budget = gameSave.value?.controlledTeam?.budget
            ?? gameSave.value?.state?.controlled_budget
            ?? 0;
        return budget >= (offer.cost ?? 0);
    };

    const buyCard = (offer) => {
        router.post(
            route('game-saves.bonus-cards.buy', { gameSave: gameSave.value.id }),
            {
                bonus_card_id: offer.bonus_card_id,
                tier:          offer.tier,
            },
            { preserveScroll: true }
        );
    };

    // ==========================
    //   INVENTAIRE
    // ==========================
    const inventory = computed(() => bonusCardInventory?.value ?? []);

    const availableCards = computed(() =>
        inventory.value.filter(c => c.status === 'available')
    );

    const usedCards = computed(() =>
        inventory.value.filter(c => c.status === 'used')
    );

    const selectCard = (card) => {
        selectedCard.value = card;
        activateTarget.value = null;
    };

    const activateCard = (card, targetPlayerId = null) => {
        router.post(
            route('game-saves.bonus-cards.activate', {
                gameSave:      gameSave.value.id,
                gameBonusCard: card.id,
            }),
            { target_player_id: targetPlayerId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    selectedCard.value = null;
                    activateTarget.value = null;
                },
            }
        );
    };

    // ==========================
    //   HELPERS UI
    // ==========================
    const tierColor = (tier) => ({
        bronze: 'border-amber-400 bg-amber-50 text-amber-800',
        silver: 'border-slate-400 bg-slate-100 text-slate-700',
        gold:   'border-yellow-400 bg-yellow-50 text-yellow-800',
    }[tier] ?? 'border-slate-200 bg-white');

    const tierBadge = (tier) => ({
        bronze: 'bg-amber-500 text-white',
        silver: 'bg-slate-400 text-white',
        gold:   'bg-yellow-400 text-slate-900',
    }[tier] ?? 'bg-slate-200');

    const phaseLabel = (phase) => ({
        immediate:    'Immédiat',
        pre_match:    'Pré-match',
        post_match:   'Post-match',
        weekly_reset: 'Hebdomadaire',
    }[phase] ?? phase);

    const targetLabel = (target) => ({
        self:    'Toute l\'équipe',
        player:  'Joueur ciblé',
        match:   'Prochain match',
        finance: 'Finances',
    }[target] ?? target);

    return {
        weeklyOffers, canAfford, buyCard,
        inventory, availableCards, usedCards,
        selectedCard, selectCard, activateCard, activateTarget,
        tierColor, tierBadge, phaseLabel, targetLabel, isAlreadyBought,
    };
}
