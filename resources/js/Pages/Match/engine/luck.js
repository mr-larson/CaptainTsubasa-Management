// Les valeurs disciplinaires correspondent à App\Helpers\PlayerLuck.
export const LUCK_RULES = { criticalRed: 0.10, criticalYellow: 0.65, tieYellow: 0.20 };
export const SIMULATION_COST = 20;

const normalizedLuck = luck => {
    const value = Number(luck ?? 50);
    return Number.isFinite(value) ? Math.max(20, Math.min(80, value)) : 50;
};

export function incidentMultiplier(luck) {
    return 1 - (normalizedLuck(luck) - 50) / 150;
}

export function simulationThreshold(luck) {
    return Math.round(15 - (normalizedLuck(luck) - 20) / 6);
}

export function resolveSimulationRoll(luck, roll, yellowRoll = Math.random, criticalsEnabled = true) {
    const effectiveLuck = normalizedLuck(luck);
    const threshold = simulationThreshold(effectiveLuck);
    const success = roll > threshold;
    const criticalFailure = criticalsEnabled && roll === 1;
    const sanctionRoll = !criticalFailure && !success && roll < 10 ? yellowRoll() : null;
    const card = criticalFailure ? "red" : sanctionRoll !== null && sanctionRoll < 0.5 ? "yellow" : null;
    return { effectiveLuck, roll, threshold, success, card, sanctionRoll };
}
