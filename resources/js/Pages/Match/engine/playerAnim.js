// resources/js/Pages/Match/engine/playerAnim.js
import { getCarrierElement } from './field.js';

const ANIM_DURATIONS = {
    dribble:  400,
    tackle:   380,
    passer:   320,
    receiver: 380,
    shoot:    450,
    save:     480,
};

// Mémorise les animations en cours par élément pour pouvoir les annuler
const _activeAnims = new WeakMap();

/**
 * Convention : +1 = équipe attaque vers la droite, -1 = vers la gauche.
 * À ajuster si ton moteur utilise une convention inverse.
 */
export function getAttackDirection(team) {
    return team === 'internal' ? 1 : -1;
}

/**
 * Joue une animation expressive sur un joueur.
 *
 * @param {string} team       'internal' | 'external'
 * @param {number} number     Numéro du joueur (1-11)
 * @param {string} kind       'dribble' | 'tackle' | 'passer' | 'receiver' | 'shoot' | 'save'
 * @param {object} [opts]
 * @param {number} [opts.direction]  +1 ou -1 (sinon dérivé de l'équipe)
 */
export function playPlayerAnimation(team, number, kind, opts = {}) {
    const el = getCarrierElement(team, number);
    if (!el) return;

    const className = `player-anim-${kind}`;
    const duration  = ANIM_DURATIONS[kind] ?? 400;
    const direction = opts.direction ?? getAttackDirection(team);

    el.style.setProperty('--anim-dir', String(direction));

    // Annule une anim précédente sur le même élément
    const prev = _activeAnims.get(el);
    if (prev) {
        clearTimeout(prev.timer);
        el.classList.remove(prev.className);
    }

    // Force un reflow pour permettre la ré-exécution de l'animation
    void el.offsetWidth;

    el.classList.add(className);
    const timer = setTimeout(() => {
        el.classList.remove(className);
        if (_activeAnims.get(el)?.timer === timer) _activeAnims.delete(el);
    }, duration + 30);

    _activeAnims.set(el, { className, timer });
}
