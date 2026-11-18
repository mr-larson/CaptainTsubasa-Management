// Props de la page de jeu qu'une action recharge. Sans `only`, chaque action redirige vers la page
// entière (~440 ko, dont les agents libres, le calendrier et les cartes) alors qu'elle n'a modifié
// que quelques données.

/**
 * Actions de composition (titulaire, capitaine, poste, formation, remplacement, numéro) :
 * elles modifient les contrats et l'état de composition de la partie, donc la partie, l'équipe
 * qui a la main et la liste des équipes (qui porte aussi ces contrats). Le message flash et les
 * erreurs de validation sont toujours renvoyés par le serveur.
 */
export const LINEUP_PROPS = ['gameSave', 'controlledTeam', 'teams'];
