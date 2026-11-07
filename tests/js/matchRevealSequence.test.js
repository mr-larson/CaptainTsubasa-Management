import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { FIELD_RULES } from '../../resources/js/Pages/Match/engine/constants.js';
import { enqueueRevealStep, showDuelDice } from '../../resources/js/Pages/Match/engine/dice.js';
import { initFieldModule } from '../../resources/js/Pages/Match/engine/field.js';
import { initResolversModule, resolveDribble, resolveShotKeeperDuel } from '../../resources/js/Pages/Match/engine/resolvers.js';
import { runFieldDuel } from '../../resources/js/Pages/Match/engine/resolvers/fieldDuel.js';
import { initStaminaModule } from '../../resources/js/Pages/Match/engine/stamina.js';
import { disposeUIModule, initUIModule, pushLogEntry, resetLogHistory } from '../../resources/js/Pages/Match/engine/ui.js';

vi.mock('../../resources/js/Pages/Match/engine/resolvers/fieldDuel.js', () => ({
    runFieldDuel: vi.fn(),
    performKeeperClearance: vi.fn(),
}));

function element() {
    const el = new EventTarget();
    const classes = new Set();
    el.dataset = {};
    el.style = { setProperty: vi.fn() };
    el.classList = {
        add: (...names) => names.forEach(name => classes.add(name)),
        remove: (...names) => names.forEach(name => classes.delete(name)),
        contains: name => classes.has(name),
    };
    el.getBoundingClientRect = vi.fn();
    el.querySelector = () => null;
    el.setAttribute = vi.fn();
    return el;
}

describe('enchaînement des révélations et du terrain', () => {
    let state, carrier, overlay, diceOverlay, ballEl, finishDice, animateAndThen;

    beforeEach(() => {
        vi.useFakeTimers();
        const diceDisplay = element();
        diceOverlay = element();
        vi.stubGlobal('document', {
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            createElement: element,
            getElementById: id => ({
                'duel-dice-display': diceDisplay,
                'duel-roll-overlay': diceOverlay,
            })[id] ?? null,
            body: { appendChild: vi.fn() },
        });
        vi.stubGlobal('localStorage', { getItem: () => 'normal' });
        state = {
            ball: { team: 'internal', number: 8, zoneIndex: 3, laneIndex: 1 },
            currentTeam: 'internal',
            score: { internal: 0, external: 0 },
            stamina: {},
            staminaMax: {},
            actionEvents: [],
            _applyKickoffFn: vi.fn(),
            _moveBallFn: vi.fn(),
        };
        overlay = undefined;
        carrier = element();
        carrier.style.left = '55%';
        carrier.style.top = '50%';
        ballEl = element();
        ballEl.style.left = '55%';
        ballEl.style.top = '50%';

        const keeper = { style: { left: '95%', top: '50%' } };
        const field = { appendChild: el => { overlay = el; } };
        const rootEl = {
            querySelector: selector => ({
                '#field': field,
                '[data-player="I8"]': carrier,
                '.player.external.goalkeeper[data-player="E1"]': keeper,
            })[selector] ?? null,
        };
        const ui = { ballEl };
        const roster = {
            getPlayerInfo: () => ({ number: 8 }),
            attackBaseFor: () => 10,
            defenseBaseFor: () => 1,
            attackZoneMultiplier: () => 1,
            getStat: () => 50,
        };
        const TEAMS = { internal: { label: 'Équipe locale' } };
        initFieldModule(rootEl, roster, state, ui);
        initStaminaModule(state, roster, rootEl);
        initUIModule(rootEl, roster, ui, state, TEAMS);
        animateAndThen = vi.fn();
        initResolversModule({ state, roster, ui, TEAMS, rootEl, animateAndThen, advanceTurn: vi.fn() });
        runFieldDuel.mockImplementation(() => {
            enqueueRevealStep(done => { finishDice = done; });
            return { duelResult: 'attack' };
        });
    });

    afterEach(() => {
        resetLogHistory();
        disposeUIModule();
        vi.useRealTimers();
        vi.unstubAllGlobals();
        vi.resetAllMocks();
        vi.restoreAllMocks();
    });

    it('garde le pion et le ballon en place jusqu’à la fin des dés', () => {
        resolveDribble('internal', 'external', 'tackle');
        expect(state.isAnimating).toBe(true);
        expect(state.ball.zoneIndex).toBe(3);
        expect(carrier.style.left).toBe('55%');
        expect(ballEl.style.left).toBe('55%');
        expect(carrier.classList.contains('player-anim-dribble')).toBe(false);

        finishDice();

        expect(state.ball.zoneIndex).toBe(4);
        expect(carrier.style.left).toBe('75%');
        expect(ballEl.style.left).toBe('75%');
        expect(carrier.classList.contains('player-anim-dribble')).toBe(true);
    });

    it.each(['attendre', 'passer'])('attend les dés, le carton et la fermeture de la bannière avant de tirer (%s)', mode => {
        const resolveDuel = runFieldDuel.getMockImplementation();
        runFieldDuel.mockImplementation(() => {
            const result = resolveDuel();
            pushLogEntry('foulCardTitle', ['🟥 Carton rouge'], null, state);
            state.pendingFreeKick = { team: 'internal', takerSlot: 8, zoneIndex: 3, laneIndex: 1 };
            return result;
        });

        resolveDribble('internal', 'external', 'tackle');
        expect(overlay).toBeUndefined();
        expect(ballEl.style.left).toBe('55%');

        finishDice();
        expect(overlay.className).toContain('event-overlay--red');
        expect(ballEl.style.left).toBe('55%');

        vi.advanceTimersByTime(1400);
        expect(overlay.className).toContain('event-overlay--freekick');
        expect(overlay.classList.contains('visible')).toBe(true);
        expect(carrier.classList.contains('player-anim-shoot')).toBe(false);
        expect(state.pendingShotContext).toBeUndefined();

        if (mode === 'passer') overlay.dispatchEvent(new Event('click'));
        else vi.advanceTimersByTime(1400);

        expect(overlay.classList.contains('visible')).toBe(false);
        expect(carrier.classList.contains('player-anim-shoot')).toBe(true);
        expect(ballEl.style.left).toBe('95%');
        expect(state.pendingShotContext?.isFreeKick).toBe(true);
    });

    it('montre le but après les dés puis attend la fin du mouvement avant l’engagement', async () => {
        vi.spyOn(Math, 'random').mockReturnValue(0.5);
        resolveShotKeeperDuel({
            attackTeam: 'internal',
            defenseTeam: 'external',
            originZone: 3,
            gkAttackBase: 100,
            logParts: ['Coup franc'],
        }, 'hands');

        expect(diceOverlay.classList.contains('visible')).toBe(true);
        expect(ballEl.style.left).toBe('55%');
        expect(animateAndThen).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(5199);
        expect(diceOverlay.classList.contains('visible')).toBe(true);
        expect(ballEl.style.left).toBe('55%');
        await vi.advanceTimersByTimeAsync(1);
        expect(diceOverlay.classList.contains('visible')).toBe(false);
        expect(ballEl.style.left).toBe(`${FIELD_RULES.GOAL_X.internal}%`);
        expect(state._applyKickoffFn).not.toHaveBeenCalled();

        const [[finishGoal]] = animateAndThen.mock.calls;
        finishGoal();
        expect(state._applyKickoffFn).toHaveBeenCalledOnce();
        expect(state._moveBallFn).toHaveBeenCalledWith('external', 8);
    });

    it('garde la défense bleue à gauche et montre le verdict rapide pendant 1,5 seconde', async () => {
        vi.stubGlobal('localStorage', { getItem: () => 'fast' });
        const verdict = element();
        diceOverlay.querySelector = selector => selector === '.droll-verdict' ? verdict : null;
        const roll = { roll: 10, bonus: 2 };
        showDuelDice(10, 12, roll, roll, {
            meta: { attacker: { team: 'external' }, defender: { team: 'internal' } },
            result: { winner: 'defense' },
        });

        const html = diceOverlay.innerHTML;
        expect(html).toMatch(/droll-die--internal" data-die="defense"[\s\S]*droll-die--external" data-die="attack"/);
        await vi.advanceTimersByTimeAsync(560);
        expect(verdict.textContent).toContain('La défense tient bon');
        await vi.advanceTimersByTimeAsync(1499);
        expect(diceOverlay.classList.contains('visible')).toBe(true);
        await vi.advanceTimersByTimeAsync(1);
        expect(diceOverlay.classList.contains('visible')).toBe(false);
    });
});
