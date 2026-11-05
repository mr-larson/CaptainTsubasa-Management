// resources/js/Pages/GameSaves/Play/usePreMatchChecklist.js
// Checklist « Avant le match » : une seule source pour la colonne gauche (grand
// écran) et le Dashboard (téléphone/tablette).
//   actions : ce que l'entraîneur peut encore faire cette semaine
//   squad   : état du onze + risques (fatigue, cartons)
// Chaque item : { key, level: 'action'|'error'|'warn'|'info', text, tab, player? }
import { computed, toValue } from 'vue';

// Même seuil que les barres de stamina du terrain/banc (rouge sous 30).
export const LOW_STAMINA = 30;

const CHALLENGE_LABELS = {
    win:         'gagner',
    score_3:     'marquer au moins 3 buts',
    clean_sheet: 'ne pas encaisser',
    win_by_2:    'gagner avec 2 buts d\'écart',
    win_score_3: 'gagner en marquant au moins 3 buts',
};

const plural = (n, word) => `${n} ${word}${n > 1 ? 's' : ''}`;

export function usePreMatchChecklist({
    roster, lineupIssues, remainingTrainings, availableCards, unboughtCardsCount,
    freePlayersCount, isWorldCup, playerPromises, sponsorChallenges, playerYellowCards,
    suspensionOn3Yellows, week,
}) {
    const playerById = computed(() =>
        Object.fromEntries((toValue(roster) ?? []).map(p => [Number(p.id), p]))
    );

    const promiseItems = computed(() => {
        const items = [];
        const currentWeek = Number(toValue(week));
        Object.entries(toValue(playerPromises) ?? {}).forEach(([playerId, promises]) => {
            const p = playerById.value[Number(playerId)];
            if (!p) return;
            (promises ?? []).filter(pr => pr.status === 'pending').forEach(pr => {
                const base = { key: `pr${pr.id}`, tab: 'my-team', player: p };
                if (pr.type === 'playing_time') {
                    items.push({ ...base, level: p.is_starter ? 'info' : 'warn',
                        text: `🤝 ${p.lastname} doit jouer ${pr.target_turns ?? 15} tours ce match${p.is_starter ? '' : ' (remplaçant)'}` });
                } else if (pr.type === 'starter') {
                    items.push({ ...base, level: p.is_starter ? 'info' : 'warn',
                        text: `⭐ Titularisation promise à ${p.lastname} (${pr.played_matches ?? 0}/${pr.target_matches ?? 4}${p.is_starter ? '' : ', sur le banc'})` });
                } else if (pr.type === 'renewal') {
                    const left = Number(pr.due_week) - currentWeek;
                    items.push({ ...base, level: left <= 1 ? 'warn' : 'info',
                        text: `📜 Prolonger ${p.lastname} avant la semaine ${pr.due_week}` });
                }
            });
        });
        return items;
    });

    const actions = computed(() => {
        const items = [];
        const trainings = Number(toValue(remainingTrainings));
        if (trainings > 0) {
            items.push({ key: 'training', level: 'action', tab: 'training',
                text: `🏋️ ${plural(trainings, 'entraînement')} disponible${trainings > 1 ? 's' : ''}` });
        }

        items.push(...promiseItems.value);

        // Contrats qui se terminent cette semaine ou la suivante.
        const currentWeek = Number(toValue(week));
        (toValue(roster) ?? [])
            .filter(p => p.contract_end_week != null
                && Number(p.contract_end_week) >= currentWeek
                && Number(p.contract_end_week) <= currentWeek + 1)
            .forEach(p => items.push({ key: `ct${p.id}`, level: 'warn', tab: 'my-team', player: p,
                text: `📄 Contrat de ${p.lastname} : fin semaine ${p.contract_end_week}` }));

        (toValue(sponsorChallenges) ?? []).forEach((ch, i) => {
            items.push({ key: `sp${i}`, level: 'info', tab: 'cards',
                text: `💰 Défi sponsor : ${CHALLENGE_LABELS[ch.challenge] ?? 'objectif'} (+${ch.reward ?? 0})` });
        });

        const inHand = (toValue(availableCards) ?? []).length;
        if (inHand > 0) {
            items.push({ key: 'hand', level: 'action', tab: 'cards',
                text: `🃏 ${plural(inHand, 'carte')} en main` });
        }
        const shop = Number(toValue(unboughtCardsCount));
        if (shop > 0) {
            items.push({ key: 'shop', level: 'info', tab: 'cards',
                text: `🛒 ${plural(shop, 'carte')} en boutique` });
        }
        const free = Number(toValue(freePlayersCount));
        if (free > 0 && !toValue(isWorldCup)) {
            items.push({ key: 'market', level: 'info', tab: 'transfers',
                text: `💼 ${plural(free, 'joueur')} libre${free > 1 ? 's' : ''} sur le marché` });
        }
        return items;
    });

    const squad = computed(() => {
        const items = (toValue(lineupIssues) ?? []).map(i => ({ ...i, tab: 'my-team' }));
        const starters = (toValue(roster) ?? []).filter(p => p.is_starter);

        starters.forEach(p => {
            const stamina = Number(p.stamina ?? p.stats?.stamina ?? 0);
            if (stamina < LOW_STAMINA) {
                items.push({ key: `st${p.id}`, level: 'warn', tab: 'my-team', player: p,
                    text: `🔋 ${p.lastname} épuisé (stamina ${stamina})` });
            }
        });

        if (toValue(suspensionOn3Yellows)) {
            const yellows = toValue(playerYellowCards);
            starters.forEach(p => {
                if (Number(yellows(p.id)) === 2) {
                    items.push({ key: `y${p.id}`, level: 'info', tab: 'my-team', player: p,
                        text: `🟨 ${p.lastname} à 1 jaune de la suspension` });
                }
            });
        }

        const ORDER = { error: 0, warn: 1, info: 2 };
        return items.sort((a, b) => (ORDER[a.level] ?? 3) - (ORDER[b.level] ?? 3));
    });

    return { actions, squad };
}
