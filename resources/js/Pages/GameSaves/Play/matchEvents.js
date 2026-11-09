// resources/js/Pages/GameSaves/Play/matchEvents.js
//
// Lecture des événements de match_stats.events, quelle que soit leur source :
// - MatchSimulator (matchs simulés) : actionType 'goal' | 'shot' | 'pass' | 'dribble'
//   | 'cross' | 'long_pass' | 'heroic', result 'attack' | 'defense' ;
// - moteur joué (engine/ui.js _detectType) : 'shot-goal', 'special-goal',
//   'shot-saved', 'pass-success', 'dribble-failed', 'gk-restart', 'unknown'…,
//   result 'success' | 'failed' | 'neutral', avec `action` = geste tenté.

export const EVENT_ICONS = {
    goal: '⚽', shot: '🔥', pass: '🎯', dribble: '🌀',
    intercept: '🛡️', tackle: '⚔️', block: '🧱',
    'card-yellow': '🟨', 'card-red': '🟥', foul: '⚠️', 'free-kick': '🦶',
    kickoff: '🚩', injury: '🤕', substitution: '🔄', 'gk-restart': '🧤',
    matchend: '🏁', heroic: '⭐', simulation: '🎭',
};

export const isGoalEvent = (ev) => /goal/i.test(ev?.actionType ?? '');

/** Catégorie canonique d'un événement (clé de EVENT_ICONS, ou 'other'). */
export function eventKind(ev) {
    const type = String(ev?.actionType ?? '');
    if (isGoalEvent(ev)) return 'goal';
    if (type === 'heroic' || /captain reroll|dépassement/i.test(ev?.text ?? '')) return 'heroic';
    if (EVENT_ICONS[type]) return type;

    const base = type.split('-')[0];                      // shot-saved → shot, pass-success → pass
    if (EVENT_ICONS[base] && base !== 'card') return base;
    if (type === 'cross' || type === 'long_pass') return 'pass';

    const action = ev?.action;                            // 'unknown' : geste tenté
    if (action === 'cross' || action === 'long_pass') return 'pass';
    if (action === 'special') return 'shot';             // tir spécial
    if (EVENT_ICONS[action]) return action;
    return 'other';
}

export const eventIcon = (ev) => EVENT_ICONS[eventKind(ev)] ?? '▫️';

/** 'success' (l'attaquant réussit) | 'failed' | 'neutral', toutes sources confondues. */
export function eventOutcome(ev) {
    const r = ev?.result;
    if (r === 'attack' || r === 'success') return 'success';
    if (r === 'defense' || r === 'failed') return 'failed';
    return 'neutral';
}
