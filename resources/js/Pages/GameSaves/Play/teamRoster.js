// Accès aux joueurs d'une équipe de la partie. Selon la page, le contrat arrive avec son joueur
// sous `game_player` (JSON snake_case), `gamePlayer` (relation Eloquent) ou `player` : un seul
// endroit connaît ces trois formes.

/** Le joueur porté par un contrat (ou null). */
export const contractPlayer = (contract) =>
    contract?.game_player ?? contract?.gamePlayer ?? contract?.player ?? null;

/** Les joueurs d'une équipe, d'après ses contrats chargés. */
export const playersOf = (team) => (team?.contracts ?? []).map(contractPlayer).filter(Boolean);

/** Stats de saison d'un joueur dans la table { id → stats } (clé numérique ou texte selon l'origine). */
export const seasonStatsOf = (statsById, playerId) =>
    statsById?.[playerId] ?? statsById?.[String(playerId)] ?? null;
