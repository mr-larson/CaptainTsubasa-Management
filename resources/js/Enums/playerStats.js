// Statistiques d'un joueur, dans l'ordre d'affichage des formulaires.
// `short` / `color` : barres de l'aperçu des statistiques (joueurs de partie).
export const PLAYER_STATS = [
    { key: 'speed',      label: 'Vitesse',      color: 'bg-sky-400' },
    { key: 'attack',     label: 'Attaque',      color: 'bg-orange-400' },
    { key: 'defense',    label: 'Défense',      color: 'bg-blue-400' },
    { key: 'stamina',    label: 'Endurance',    color: 'bg-emerald-400' },
    { key: 'shot',       label: 'Tir',          color: 'bg-red-400' },
    { key: 'pass',       label: 'Passe',        color: 'bg-teal-400' },
    { key: 'dribble',    label: 'Dribble',      color: 'bg-yellow-400' },
    { key: 'block',      label: 'Block',        color: 'bg-indigo-400' },
    { key: 'intercept',  label: 'Interception', color: 'bg-purple-400' },
    { key: 'tackle',     label: 'Tacle',        color: 'bg-pink-400' },
    { key: 'heading',    label: 'Jeu de tête',  color: 'bg-cyan-400', short: 'Tête' },
    { key: 'hand_save',  label: 'Arrêt main',   color: 'bg-violet-400' },
    { key: 'punch_save', label: 'Arrêt poing',  color: 'bg-fuchsia-400' },
];

export const PLAYER_POSITIONS = ['Goalkeeper', 'Defender', 'Midfielder', 'Forward'];

// Valeurs initiales d'un formulaire de création (toutes à 0 sauf `heading`).
export function defaultPlayerStats({ heading = 0 } = {}) {
    return Object.fromEntries(PLAYER_STATS.map(({ key }) => [key, key === 'heading' ? heading : 0]));
}
