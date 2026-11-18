// resources/js/Pages/GameSaves/Play/useBonusCards.js
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { CARD_BUY_PROPS } from './reloadScopes.js';

export function useBonusCards({ gameSave, bonusCardOffers, bonusCardInventory }) {

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

    const buyCard = (offer) => {
        router.post(
            route('game-saves.bonus-cards.buy', { gameSave: gameSave.value.id }),
            {
                bonus_card_id: offer.bonus_card_id,
                tier:          offer.tier,
            },
            { preserveScroll: true, only: CARD_BUY_PROPS }
        );
    };

    // ==========================
    //   INVENTAIRE
    // ==========================
    const inventory = computed(() => bonusCardInventory?.value ?? []);

    const availableCards = computed(() =>
        inventory.value.filter(c => c.status === 'available')
    );

    const activateCard = (card, targetPlayerId = null, targetTeamId = null) => {
        router.post(
            route('game-saves.bonus-cards.activate', {
                gameSave:      gameSave.value.id,
                gameBonusCard: card.id,
            }),
            { target_player_id: targetPlayerId, target_team_id: targetTeamId },
            { preserveScroll: true }
        );
    };

    return {
        weeklyOffers, buyCard,
        inventory, availableCards,
        activateCard, isAlreadyBought,
    };
}
