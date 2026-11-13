// resources/js/Pages/GameSaves/Play/useLineupIssues.js
// État du onze : ce qui mérite attention avant le match (onze incomplet,
// titulaire indisponible, joueur hors poste, capitaine). Partagé entre l'onglet
// Mon équipe et les rappels de la colonne gauche.
import { computed, toValue } from 'vue';
import { masteryFor, roleFromPosition } from './lineupMastery.js';

const ZONE_SHORT = ['gardien', 'défense', 'milieu déf.', 'milieu off.', 'attaque'];
const POS_SHORT  = { GK: 'GK', DF: 'DEF', MF: 'MIL', FW: 'ATT' };

export const ISSUE_CHIP = {
    error: 'bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100',
    warn:  'bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100',
    info:  'bg-white border-slate-200 text-slate-500 hover:bg-slate-100',
};

export function useLineupIssues({ roster, formationData, playerForSlot, isPlayerInjured, isPlayerSuspended, isPlayerBenched, positionTraining = null }) {
    const unavailability = (p) => {
        if (toValue(isPlayerInjured)(p.id))   return { label: 'Blessé',   icon: '🤕' };
        if (toValue(isPlayerSuspended)(p.id)) return { label: 'Suspendu', icon: '🚫' };
        if (toValue(isPlayerBenched)(p.id))   return { label: 'Consigné par l\'adversaire', icon: '⛔' };
        if (toValue(positionTraining)?.(p.id)) return { label: 'En formation', icon: '🎓' };
        return null;
    };

    const lineupIssues = computed(() => {
        const players  = toValue(roster) ?? [];
        const issues   = [];
        const starters = players.filter(p => p.is_starter);
        const forSlot  = toValue(playerForSlot);

        if (starters.length < 11) {
            issues.push({ key: 'count', level: 'warn', text: `Onze incomplet (${starters.length}/11) — clique un poste « + » sur le terrain` });
        }

        starters.forEach(p => {
            const u = unavailability(p);
            if (u) issues.push({ key: `u${p.id}`, level: 'error', player: p, text: `${u.icon} ${p.lastname} titulaire mais ${u.label.toLowerCase()}` });
        });

        Object.entries(toValue(formationData)?.slots ?? {}).forEach(([slot, def]) => {
            const p = forSlot(slot);
            const m = masteryFor(p, def);
            const natural = POS_SHORT[roleFromPosition(p?.position)] ?? '?';
            if (m === 'off') {
                issues.push({ key: `o${slot}`, level: 'error', player: p, text: `⚠ ${p.lastname} hors poste (${natural} en ${ZONE_SHORT[def.zone]}) — malus en match` });
            } else if (m === 'secondary') {
                issues.push({ key: `s${slot}`, level: 'info', player: p, text: `${p.lastname} en poste secondaire (${ZONE_SHORT[def.zone]})` });
            }
        });

        const captain = players.find(p => p.is_captain);
        if (!captain) {
            issues.push({ key: 'cap', level: 'warn', text: '👑 Aucun capitaine désigné' });
        } else if (!captain.is_starter) {
            issues.push({ key: 'cap', level: 'warn', player: captain, text: `👑 Le capitaine ${captain.lastname} est sur le banc` });
        }
        return issues;
    });

    return { unavailability, lineupIssues };
}
