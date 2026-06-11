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

    // Moral : 0-20 révolté, 21-40 mécontent, 41-60 neutre, 61-80 satisfait, 81-100 très satisfait
    const moraleState = (value) => {
        const v = Number(value ?? 60);
        if (v <= 20) return { label: 'Révolté',        emoji: '😡', text: 'text-rose-600',    bar: 'bg-rose-500',    chip: 'bg-rose-100 text-rose-700' };
        if (v <= 40) return { label: 'Mécontent',      emoji: '😞', text: 'text-orange-500',  bar: 'bg-orange-400',  chip: 'bg-orange-100 text-orange-700' };
        if (v <= 60) return { label: 'Neutre',         emoji: '😐', text: 'text-slate-500',   bar: 'bg-slate-400',   chip: 'bg-slate-100 text-slate-600' };
        if (v <= 80) return { label: 'Satisfait',      emoji: '🙂', text: 'text-teal-600',    bar: 'bg-teal-400',    chip: 'bg-teal-100 text-teal-700' };
        return        { label: 'Très satisfait', emoji: '🤩', text: 'text-emerald-600', bar: 'bg-emerald-500', chip: 'bg-emerald-100 text-emerald-700' };
    };

    // Effet du moral en match (miroir de MORALE_FACTORS du moteur / MoraleService::matchFactor)
    const moraleMatchEffect = (value) => {
        const v = Number(value ?? 60);
        if (v <= 20) return { pct: '-10 %', positive: false };
        if (v <= 40) return { pct: '-5 %',  positive: false };
        if (v <= 60) return null; // neutre : aucun effet
        if (v <= 80) return { pct: '+2 %',  positive: true };
        return { pct: '+5 %', positive: true };
    };

    // Seuil du moment héroïque ("Dépassement de soi") — moral strictement supérieur
    const HEROIC_MORALE_THRESHOLD = 85;

    const moraleSourceLabel = (source) => {
        switch (source) {
            case 'result':       return 'Résultats';
            case 'playing_time': return 'Temps de jeu';
            case 'salary':       return 'Salaire';
            default:             return source;
        }
    };

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
        moraleState, moraleSourceLabel, moraleMatchEffect, HEROIC_MORALE_THRESHOLD,
        positionGroup, keyStatsFor, statLabel, statColor,
        STAT_KEYS, STAT_LABELS, STAT_COLORS,
    };
}
