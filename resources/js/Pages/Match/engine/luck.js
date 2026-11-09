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

export function resolveSimulationRoll(luck, roll, yellowRoll = Math.random) {
    const threshold = simulationThreshold(luck);
    const success = roll > threshold;
    const card = roll === 1 ? "red"
        : !success && roll < 10 && yellowRoll() < 0.5 ? "yellow"
        : null;
    return { threshold, success, card };
}
