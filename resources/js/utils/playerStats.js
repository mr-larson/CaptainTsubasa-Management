// Lecture des caractéristiques d'un joueur, quelle que soit la forme reçue :
// colonnes à plat (`player.stamina`) ou objet `stats` (`player.stats.stamina`).

/** Endurance courante du joueur (0 si inconnue). */
export const staminaOf = (player) => Number(player?.stamina ?? player?.stats?.stamina ?? 0);

/** Valeur d'une caractéristique du joueur (0 si inconnue). */
export const statOf = (player, key) => Number(player?.stats?.[key] ?? player?.[key] ?? 0);

/** Le joueur est-il gardien ? (poste « Goalkeeper » ou équivalent) */
export const isGoalkeeper = (player) => String(player?.position ?? '').toLowerCase().includes('goal');
