import { inject, provide } from 'vue';

/**
 * Fonctions de statut des joueurs (blessure, suspension, cartons jaunes),
 * fournies une fois par la page Play et lues directement par les composants
 * qui en ont besoin (listes, alertes, statut du club) au lieu de traverser
 * chaque onglet en props.
 */
const KEY = Symbol('playerStatus');

const NO_STATUS = {
    isPlayerInjured:   () => false,
    isPlayerSuspended: () => false,
    playerInjury:      () => null,
    playerSuspension:  () => null,
    playerYellowCards: () => 0,
};

/** À appeler dans la page qui calcule les statuts (Play.vue). */
export function providePlayerStatus(status) {
    provide(KEY, status);
}

/** Retourne { isPlayerInjured, isPlayerSuspended, playerInjury, playerSuspension, playerYellowCards }. */
export function usePlayerStatus() {
    return inject(KEY, NO_STATUS);
}
