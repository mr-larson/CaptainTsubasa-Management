// resources/js/Pages/GameSaves/Play/usePlayerUtils.js

const STAT_KEYS = [
    'speed', 'stamina', 'attack', 'defense',
    'shot', 'pass', 'dribble', 'block',
    'intercept', 'tackle', 'hand_save', 'punch_save',
];

const STAT_LABELS = {
    shot: 'Tir', pass: 'Passe', dribble: 'Dribble', attack: 'Att',
    defense: 'Def', speed: 'Vit', block: 'Block', intercept: 'Interc',
    tackle: 'Tacle', stamina: 'End', hand_save: 'Main', punch_save: 'Poings',
};

const STAT_COLORS = {
    shot: 'bg-red-400', pass: 'bg-teal-400', dribble: 'bg-yellow-400',
    attack: 'bg-orange-400', defense: 'bg-blue-400', speed: 'bg-sky-400',
    block: 'bg-indigo-400', intercept: 'bg-purple-400', tackle: 'bg-pink-400',
    stamina: 'bg-emerald-400', hand_save: 'bg-violet-400', punch_save: 'bg-fuchsia-400',
};

const KEY_STATS_BY_POSITION = {
    GK:  ['hand_save', 'punch_save', 'defense', 'block'],
    DEF: ['defense', 'tackle', 'block', 'intercept'],
    MID: ['pass', 'intercept', 'attack', 'dribble'],
    ATT: ['shot', 'dribble', 'attack', 'speed'],
};

export function usePlayerUtils() {

    const overallOf = (player) => {
        if (!player) return 0;
        const src    = player.stats ?? player;
        const values = STAT_KEYS
            .map(k => Number(src?.[k] ?? player?.[k] ?? 0))
            .filter(v => Number.isFinite(v) && v > 0);
        if (!values.length) return 0;
        return Math.round(values.reduce((a, b) => a + b, 0) / values.length);
    };

    const playerPhotoUrl = (p) => {
        if (!p) return null;
        if (p.photo_path)         return `/storage/${p.photo_path}`;
        if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
        return null;
    };

    const teamLogoUrl = (t) => {
        const path = t?.logo_path ?? t?.team?.logo_path;
        if (!path) return null;
        if (path.startsWith('http') || path.startsWith('/')) return path;
        if (path.startsWith('teams/')) return '/images/' + path;
        return '/' + path;
    };

    const positionGroup = (pos) => {
        const p = (pos ?? '').toUpperCase();
        if (p.includes('GK')  || p.includes('GOAL')) return 'GK';
        if (p.includes('DEF') || p.includes('BACK')) return 'DEF';
        if (p.includes('MDF') || p.includes('MID') || p.includes('MOF')) return 'MID';
        if (p.includes('ATT') || p.includes('FOR') || p.includes('FORWARD')) return 'ATT';
        return 'OTHER';
    };

    const keyStatsFor = (pos) => {
        const g = positionGroup(pos);
        return KEY_STATS_BY_POSITION[g] ?? ['attack', 'defense', 'pass', 'shot'];
    };

    const statLabel = (k) => STAT_LABELS[k] ?? k;
    const statColor = (k) => STAT_COLORS[k] ?? 'bg-slate-400';

    const sanctionTypeLabel = (type) => {
        switch (type) {
            case 'red':           return 'Carton rouge';
            case 'double_yellow': return 'Cumul de cartons jaunes';
            case 'yellow':        return 'Carton jaune';
            default:              return 'Sanction';
        }
    };

    return {
        overallOf, playerPhotoUrl, teamLogoUrl, sanctionTypeLabel,
        positionGroup, keyStatsFor, statLabel, statColor,
        STAT_KEYS, STAT_LABELS, STAT_COLORS,
    };
}
