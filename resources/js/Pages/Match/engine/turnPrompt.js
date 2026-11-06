// resources/js/Pages/Match/engine/turnPrompt.js
//
// Invite de tour affichée sous le score : on dit d'abord QUI doit agir, puis qui
// a le ballon (« Tour de l'IA » à côté de cartes jouables rendait l'action
// courante ambiguë).

/** [titre, sous-titre] quand `teamLabel` a le ballon et doit choisir son attaque. */
export function attackPrompt(teamLabel, isAI) {
    return isAI
        ? [`${teamLabel} a le ballon`, `${teamLabel} choisit son action…`]
        : [`À toi de jouer · ${teamLabel} a le ballon`, "Choisis ta carte : passe, dribble ou tir."];
}

/** Sous-titre quand `teamLabel` doit défendre (sur le terrain ou dans les buts). */
export function defensePrompt(teamLabel, isAI, keeper = false) {
    const who = keeper ? `${teamLabel} (gardien)` : teamLabel;
    return isAI
        ? `${who} choisit sa défense…`
        : `À toi de défendre · ${who} : choisis ta carte.`;
}
