/** Rappels informatifs : les promesses ne bloquent jamais un remplacement. */
export function promiseReminders(promises = [], { week = null, playedTurns = null } = {}) {
    return (promises ?? []).filter(p => p.status === 'pending').flatMap(p => {
        if (week != null && (Number(p.start_week) > week || Number(p.due_week) < week)) return [];
        switch (p.type) {
        case 'rest':
            return [{ icon: '💤', text: `Repos promis${week != null && Number(p.start_week) !== week ? ` en S${p.start_week}` : ' — ne pas faire jouer'}` }];
        case 'playing_time': {
            const target = p.target_turns ?? 15;
            return [{ icon: '⏱️', text: playedTurns == null
                ? `Jouer au moins ${target} tours ce match`
                : `Temps de jeu promis : ${playedTurns}/${target} tours${playedTurns >= target ? ' ✓' : ' — à préserver'}` }];
        }
        case 'starter':
            return [{ icon: '⭐', text: `Titularisations promises : ${p.played_matches ?? 0}/${p.target_matches ?? 4} matchs (onze de départ)` }];
        case 'renewal':
            return [{ icon: '📜', text: `Prolongation promise avant la semaine ${p.due_week}` }];
        default:
            return [];
        }
    });
}
