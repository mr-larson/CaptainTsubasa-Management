// resources/js/Pages/GameSaves/Play/useAssistantAdvice.js
// « Conseils de l'adjoint » : suggestions concrètes et applicables en un clic.
//   rotate  : un titulaire indisponible ou épuisé → le meilleur remplaçant pour son poste
//   train   : qui entraîner cette semaine, et sur quelle stat (point faible du poste)
//   captain : capitaine indisponible ou sur le banc → brassard au meilleur titulaire disponible
//   recruit : le titulaire le plus faible → le meilleur joueur libre abordable à son poste
import { computed, toValue } from 'vue';
import { masteryFor } from './lineupMastery.js';
import { usePlayerUtils } from './usePlayerUtils.js';
import { LOW_STAMINA } from './usePreMatchChecklist.js';

const MASTERY_RANK = { primary: 0, secondary: 1 };
// Écart d'overall minimal pour qu'un recrutement vaille la peine.
const RECRUIT_MIN_GAIN = 5;
// Durée de contrat proposée par défaut dans la modale de transfert.
const RECRUIT_MATCHES = 10;
// Part maximale du budget que l'adjoint propose de dépenser en entraînement chaque semaine.
const TRAINING_BUDGET_SHARE = 0.4;
// Libellés complets (mêmes que l'onglet Entraînement).
const STAT_NAMES = {
    shot: 'Tir', pass: 'Passe', dribble: 'Dribble', attack: 'Attaque', defense: 'Défense',
    speed: 'Vitesse', block: 'Contre', intercept: 'Interception', tackle: 'Tacle',
    hand_save: 'Arrêt main', punch_save: 'Arrêt poings',
};

export function useAssistantAdvice({
    roster, formationData, playerForSlot, unavailability,
    remainingTrainings, hasBeenTrained, trainingCost, minStamina, staminaCost,
    freePlayers, budget, isWorldCup,
}) {
    const { overallOf, keyStatsFor, positionGroup } = usePlayerUtils();

    const staminaOf = (p) => Number(p?.stamina ?? p?.stats?.stamina ?? 0);
    const statOf    = (p, k) => Number(p?.stats?.[k] ?? p?.[k] ?? 0);

    // ── Rotations ────────────────────────────────────────────
    const rotations = computed(() => {
        const players = toValue(roster) ?? [];
        const subs    = players.filter(p => !p.is_starter);
        const forSlot = toValue(playerForSlot);
        const isOut   = toValue(unavailability);
        const used    = new Set();
        const out     = [];

        Object.entries(toValue(formationData)?.slots ?? {}).forEach(([slot, def]) => {
            const starter = forSlot(slot);
            if (!starter) return;
            const status  = isOut(starter);
            const tired   = staminaOf(starter) < LOW_STAMINA;
            if (!status && !tired) return;

            const candidates = subs
                .filter(s => !used.has(s.id) && !isOut(s) && staminaOf(s) >= LOW_STAMINA)
                .map(s => ({ sub: s, mastery: masteryFor(s, def) }));
            const byMastery = (a, b) => (MASTERY_RANK[a.mastery] - MASTERY_RANK[b.mastery])
                || (overallOf(b.sub) - overallOf(a.sub))
                || (staminaOf(b.sub) - staminaOf(a.sub));
            let best = candidates.filter(c => c.mastery in MASTERY_RANK).sort(byMastery)[0];
            // Titulaire indisponible sans remplaçant du poste : mieux vaut un joueur
            // hors poste qu'un trou (le meilleur, puis le plus frais).
            if (!best && status) {
                best = candidates
                    .sort((a, b) => (overallOf(b.sub) - overallOf(a.sub)) || (staminaOf(b.sub) - staminaOf(a.sub)))[0];
            }
            if (!best) return;

            used.add(best.sub.id);
            out.push({
                key: `rot${starter.id}`,
                type: 'rotate',
                severity: status ? 0 : 1,
                icon: status ? status.icon : '🔋',
                title: `${best.sub.lastname} à la place de ${starter.lastname}`,
                detail: (status ? `${starter.lastname} : ${status.label.toLowerCase()}` : `${starter.lastname} épuisé (endurance ${staminaOf(starter)})`)
                    + ` · ${best.sub.lastname} : endurance ${staminaOf(best.sub)}${best.mastery === 'secondary' ? ', poste secondaire' : best.mastery in MASTERY_RANK ? '' : ', hors poste (malus)'}`,
                actionLabel: 'Faire le changement',
                starter, sub: best.sub,
            });
        });
        return out;
    });

    // ── Entraînements ────────────────────────────────────────
    // Candidats : pas encore entraînés, disponibles, et qui restent hors du rouge
    // après le coût en stamina de la séance. Titulaires d'abord, puis le point
    // faible le plus bas (stat clé du poste).
    const trainingPlan = computed(() => {
        const slots = Number(toValue(remainingTrainings)) || 0;
        const cost  = Number(toValue(trainingCost)) || 0;
        // L'adjoint garde une réserve : au plus TRAINING_BUDGET_SHARE du budget par semaine.
        const affordable = cost > 0 ? Math.floor((Number(toValue(budget)) * TRAINING_BUDGET_SHARE) / cost) : slots;
        const n = Math.min(slots, affordable);
        if (n <= 0) return [];

        const floor   = Math.max(Number(toValue(minStamina)) || 0, LOW_STAMINA) + (Number(toValue(staminaCost)) || 0);
        const trained = toValue(hasBeenTrained);
        const isOut   = toValue(unavailability);

        return (toValue(roster) ?? [])
            .filter(p => !trained(p.id) && !isOut(p) && staminaOf(p) >= floor)
            .map(p => {
                const stat = [...keyStatsFor(p.position)].sort((a, b) => statOf(p, a) - statOf(p, b))[0];
                return { player: p, stat, value: statOf(p, stat) };
            })
            .filter(c => c.stat && c.value < 99)
            .sort((a, b) => (Number(b.player.is_starter) - Number(a.player.is_starter)) || (a.value - b.value))
            .slice(0, n);
    });

    const training = computed(() => {
        const plan = trainingPlan.value;
        if (!plan.length) return null;
        const cost = (Number(toValue(trainingCost)) || 0) * plan.length;
        return {
            key: 'train',
            type: 'train',
            severity: 2,
            icon: '🏋️',
            title: `Entraîner ${plan.map(c => c.player.lastname).join(', ')}`,
            detail: plan.map(c => `${c.player.lastname} → ${STAT_NAMES[c.stat] ?? c.stat} (${c.value})`).join(' · ')
                + (cost ? ` · coût ${cost.toLocaleString('fr-FR')}` : ''),
            actionLabel: 'Préparer les séances',
            plan,
        };
    });

    // ── Capitaine ────────────────────────────────────────────
    const captain = computed(() => {
        const players = toValue(roster) ?? [];
        const isOut   = toValue(unavailability);
        const current = players.find(p => p.is_captain);
        const problem = !current ? 'aucun capitaine'
            : isOut(current) ? `${current.lastname} ${isOut(current).label.toLowerCase()}`
            : !current.is_starter ? `${current.lastname} sur le banc`
            : null;
        if (!problem) return null;

        const best = players
            .filter(p => p.is_starter && !isOut(p) && p.id !== current?.id)
            .sort((a, b) => overallOf(b) - overallOf(a))[0];
        if (!best) return null;

        return {
            key: 'captain',
            type: 'captain',
            severity: 1,
            icon: '👑',
            title: `Brassard pour ${best.lastname}`,
            detail: `Capitaine actuel : ${problem} · ${best.lastname}, meilleur titulaire disponible (${overallOf(best)})`,
            actionLabel: 'Donner le brassard',
            player: best,
        };
    });

    // ── Recrutement ──────────────────────────────────────────
    const recruit = computed(() => {
        if (toValue(isWorldCup)) return null;
        const starters = (toValue(roster) ?? []).filter(p => p.is_starter);
        if (!starters.length) return null;

        const weakest = [...starters].sort((a, b) => overallOf(a) - overallOf(b))[0];
        const group   = positionGroup(weakest.position);
        const money   = Number(toValue(budget)) || 0;

        const best = (toValue(freePlayers) ?? [])
            .filter(fp => positionGroup(fp.position) === group)
            .filter(fp => overallOf(fp) >= overallOf(weakest) + RECRUIT_MIN_GAIN)
            .filter(fp => Number(fp.adjusted_cost ?? fp.cost ?? 0) * RECRUIT_MATCHES <= money)
            .sort((a, b) => overallOf(b) - overallOf(a))[0];
        if (!best) return null;

        const price = Number(best.adjusted_cost ?? best.cost ?? 0);
        return {
            key: 'recruit',
            type: 'recruit',
            severity: 3,
            icon: '💼',
            title: `Recruter ${best.firstname ?? ''} ${best.lastname}`.replace(/\s+/g, ' ').trim(),
            detail: `Remplace ${weakest.lastname}, ton titulaire le plus faible (${overallOf(weakest)} → ${overallOf(best)})`
                + ` · ${price.toLocaleString('fr-FR')}/match, ${(price * RECRUIT_MATCHES).toLocaleString('fr-FR')} pour ${RECRUIT_MATCHES} matchs`,
            actionLabel: 'Voir l\'offre',
            candidate: best,
        };
    });

    const advice = computed(() => [...rotations.value, captain.value, training.value, recruit.value]
        .filter(Boolean)
        .sort((a, b) => a.severity - b.severity));

    return { advice };
}
