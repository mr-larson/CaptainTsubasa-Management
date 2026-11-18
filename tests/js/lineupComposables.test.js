import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

// Les composables parlent au serveur via Inertia : on enregistre les appels au lieu de les envoyer.
const calls = [];
vi.mock('@inertiajs/vue3', () => ({
    router: {
        patch: (url, data, opts) => calls.push({ method: 'patch', url, data, only: opts?.only, onError: !!opts?.onError }),
        post: (url, data, opts) => calls.push({ method: 'post', url, data, only: opts?.only, onError: !!opts?.onError }),
    },
    usePage: () => ({ props: {} }),
}));

import { useTeam } from '@/Pages/GameSaves/Play/useTeam.js';
import { useOtherTeam } from '@/Pages/GameSaves/Play/useOtherTeam.js';

const teamWith = (id, formation = '4-2-2-2') => ({
    id,
    formation,
    contracts: Array.from({ length: 14 }, (_, i) => ({
        id: id * 100 + (i + 1),
        is_starter: i < 11,
        is_captain: i === 0,
        game_player: { id: id * 100 + (i + 1), lastname: `P${i + 1}`, position: i === 0 ? 'GK' : 'MID', stamina: 80 },
    })),
});

const gameSaveFor = (teamId) => ref({
    id: 7,
    state: { lineup: { [teamId]: { slots: Object.fromEntries(Array.from({ length: 11 }, (_, i) => [i + 1, teamId * 100 + (i + 1)])) } } },
});

const rows = (form) => form.map((r) => `${r.slot}:${r.player_id}`).join(' ');
const snapshotCalls = () => calls.map((c) => ({ ...c, data: c.data }));

describe('useTeam — composition de mon équipe', () => {
    let t;
    beforeEach(() => {
        calls.length = 0;
        globalThis.route = (name, params) => `${name}${JSON.stringify(params ?? {})}`;
        vi.useFakeTimers();
        t = useTeam({ gameSave: gameSaveFor(1), team: ref(teamWith(1)) });
    });

    it('construit la composition initiale et les coordonnées du terrain', () => {
        expect({
            form: rows(t.lineupForm.value),
            starters: t.starters.value.map((p) => p.id),
            pos: [1, 2, 5, 9, 11].map((s) => t.playerPosition(s)),
            slotOf: [101, 105, 112].map((id) => t.getSlotForPlayer(id)),
            forSlot: [1, 5, 11].map((s) => t.playerForSlot(s)?.id ?? null),
        }).toMatchSnapshot();
    });

    it('échange deux titulaires par clics successifs (sélection, prise, échange)', () => {
        t.handlePlayerClick(t.rosterWithStatus.value.find((p) => p.id === 103)); // sélectionne
        t.handlePlayerClick(t.rosterWithStatus.value.find((p) => p.id === 103)); // soulève
        t.handlePlayerClick(t.rosterWithStatus.value.find((p) => p.id === 105)); // échange
        expect({
            form: rows(t.lineupForm.value),
            picked: t.pickedUpPlayerId.value,
            calls: snapshotCalls(),
            undo: t.lastChange.value?.label,
        }).toMatchSnapshot();
    });

    it('remplace un titulaire par un remplaçant et sait annuler', () => {
        t.selectMyPlayer(t.rosterWithStatus.value.find((p) => p.id === 112));
        t.handlePlayerClick(t.rosterWithStatus.value.find((p) => p.id === 112)); // soulève le remplaçant
        t.handlePlayerClick(t.rosterWithStatus.value.find((p) => p.id === 102)); // sur le titulaire
        const afterSub = {
            form: rows(t.lineupForm.value),
            starters: t.rosterWithStatus.value.filter((p) => p.is_starter).map((p) => p.id),
            undo: t.lastChange.value?.label,
        };
        t.undoLastChange();
        expect({ afterSub, calls: snapshotCalls() }).toMatchSnapshot();
    });

    it('déplace le joueur sélectionné vers un autre poste (libre ou occupé) et ignore les valeurs invalides', () => {
        t.selectMyPlayer(t.rosterWithStatus.value.find((p) => p.id === 105));
        t.changeSelectedPlayerSlot(9);   // échange avec le joueur du poste 9
        t.changeSelectedPlayerSlot(9);   // déjà à ce poste : rien
        t.changeSelectedPlayerSlot(0);   // invalide
        t.changeSelectedPlayerSlot(12);  // invalide
        t.changeSelectedPlayerSlot('abc');
        expect({ form: rows(t.lineupForm.value), selectedSlot: t.selectedSlot.value, calls: snapshotCalls() }).toMatchSnapshot();
    });

    it('ignore l’échange entre deux remplaçants et un échange avec soi-même', () => {
        t.swapPitchPlayers(101, 101);
        t.swapPitchPlayers(112, 113); // pas titulaires
        t.handlePlayerClick(t.rosterWithStatus.value.find((p) => p.id === 112));
        t.handlePlayerClick(t.rosterWithStatus.value.find((p) => p.id === 112));
        t.handlePlayerClick(t.rosterWithStatus.value.find((p) => p.id === 113));
        expect({ form: rows(t.lineupForm.value), calls: calls.length, picked: t.pickedUpPlayerId.value }).toMatchSnapshot();
    });
});

describe('useOtherTeam — composition d’un autre club', () => {
    let o;
    beforeEach(() => {
        calls.length = 0;
        globalThis.route = (name, params) => `${name}${JSON.stringify(params ?? {})}`;
        const teams = ref([teamWith(1), teamWith(2, '3-2-3-2')]);
        const gameSave = ref({ ...gameSaveFor(2).value, id: 7 });
        o = useOtherTeam({ gameSave, teams, controlledTeamId: ref(1) });
    });

    it('construit la composition initiale du club sélectionné', () => {
        expect({
            team: o.selectedOtherTeam.value.id,
            form: rows(o.otherLineupForm.value),
            pos: [1, 2, 5, 9, 11].map((s) => o.otherPlayerPosition(s)),
            slotOf: [201, 205, 212].map((id) => o.getOtherSlotForPlayer(id)),
            forSlot: [1, 5, 11].map((s) => o.otherPlayerForSlot(s)?.id ?? null),
        }).toMatchSnapshot();
    });

    it('échange deux titulaires (clic, clic) et remplace par un remplaçant', () => {
        const p = (id) => o.otherRosterWithStatus.value.find((x) => x.id === id);
        o.handleOtherPlayerClick(p(203)); // soulève + sélectionne
        o.handleOtherPlayerClick(p(205)); // échange
        o.handleOtherPlayerClick(p(212)); // soulève le remplaçant
        o.handleOtherPlayerClick(p(202)); // remplace le titulaire
        expect({ form: rows(o.otherLineupForm.value), calls: snapshotCalls(), picked: o.pickedUpOtherPlayerId.value }).toMatchSnapshot();
    });

    it('déplace le joueur sélectionné vers un autre poste', () => {
        o.selectOtherPlayer(o.otherRosterWithStatus.value.find((x) => x.id === 205));
        o.changeOtherPlayerSlot(9);
        o.changeOtherPlayerSlot(9);
        o.changeOtherPlayerSlot(0);
        o.changeOtherPlayerSlot(12);
        expect({ form: rows(o.otherLineupForm.value), selectedSlot: o.otherSelectedSlot.value, calls: snapshotCalls() }).toMatchSnapshot();
    });
});
