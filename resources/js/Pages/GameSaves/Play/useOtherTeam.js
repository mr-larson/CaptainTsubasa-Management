// resources/js/Pages/GameSaves/Play/useOtherTeam.js
import { contractPlayer } from './teamRoster.js';
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { FORMATIONS, DEFAULT_FORMATION, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';
import { LINEUP_PROPS } from './reloadScopes.js';
import { buildLineupForm, slotOfPlayer, moveToSlot, swapSlots, slotToPlayerMap, pitchPosition, interactionKind } from './lineupSlots.js';

export function useOtherTeam({ gameSave, teams, controlledTeamId }) {

    // ==========================
    //   LISTE DES AUTRES ÉQUIPES
    // ==========================
    const otherTeams = computed(() =>
        (teams.value ?? []).filter(t => t.id !== controlledTeamId.value)
    );

    const selectedOtherTeamId = ref(null);

    const selectedOtherTeam = computed(() => {
        if (!otherTeams.value.length) return null;
        if (!selectedOtherTeamId.value) return otherTeams.value[0];
        return otherTeams.value.find(t => t.id === selectedOtherTeamId.value) ?? otherTeams.value[0];
    });

    const selectOtherTeam = (t) => { selectedOtherTeamId.value = t.id; };

    // ==========================
    //   ROSTER DE L'ÉQUIPE SÉLECTIONNÉE
    // ==========================
    const otherRosterWithStatus = computed(() => {
        if (!selectedOtherTeam.value || !Array.isArray(selectedOtherTeam.value.contracts)) return [];
        return selectedOtherTeam.value.contracts
            .map(c => {
                const p = contractPlayer(c);
                if (!p) return null;
                return {
                    ...p,
                    contract_id:                      c.id,
                    is_starter:                       c.is_starter ?? true,
                    is_captain:                       c.is_captain ?? false,
                    captain_rerolls_remaining:        c.captain_rerolls_remaining ?? 3,
                    captain_reroll_used_this_action:  c.captain_reroll_used_this_action ?? false,
                };            })
            .filter(Boolean);
    });

    // ==========================
    //   JOUEUR SÉLECTIONNÉ
    // ==========================
    const selectedOtherPlayerId = ref(null);

    const selectedOtherPlayer = computed(() => {
        if (!otherRosterWithStatus.value.length) return null;
        if (!selectedOtherPlayerId.value) return otherRosterWithStatus.value[0];
        return otherRosterWithStatus.value.find(p => p.id === selectedOtherPlayerId.value)
            ?? otherRosterWithStatus.value[0];
    });

    const selectOtherPlayer = (player) => { selectedOtherPlayerId.value = player.id; };

    // Reset joueur quand on change d'équipe
    watch(() => selectedOtherTeam.value?.id, () => {
        selectedOtherPlayerId.value = null;
    });

    // ==========================
    //   TOGGLE STARTER
    // ==========================
    const toggleOtherStarter = (contractId) => {
        if (!contractId) return;
        router.patch(
            route('game-saves.contracts.toggle-starter', { gameSave: gameSave.value.id, contract: contractId }),
            {},
            { preserveScroll: true, only: LINEUP_PROPS }
        );
    };

    const toggleOtherCaptain = (contractId) => {
        if (!contractId) return;
        router.patch(route('game-saves.contracts.toggle-captain', { gameSave: gameSave.value.id, contract: contractId }), {}, { preserveScroll: true, only: LINEUP_PROPS });
    };

    // ==========================
    //   LINEUP (slots)
    // ==========================
    const otherLineupSlots = computed(() => {
        const lineup = gameSave.value.state?.lineup ?? {};
        if (!selectedOtherTeam.value) return {};
        return lineup[selectedOtherTeam.value.id]?.slots ?? {};
    });

    const otherLineupForm = ref([]);

    const initOtherLineupForm = () => {
        const starterIds = otherRosterWithStatus.value.filter(p => p.is_starter).map(p => p.id);
        otherLineupForm.value = buildLineupForm(starterIds, otherLineupSlots.value);
    };

    initOtherLineupForm();
    watch(
        () => [selectedOtherTeam.value?.id, selectedOtherTeam.value?.contracts, gameSave.value.state],
        () => initOtherLineupForm()
    );

    const getOtherSlotForPlayer = (playerId) => slotOfPlayer(otherLineupForm.value, playerId);

    const saveOtherLineup = () => {
        if (!selectedOtherTeam.value) return;
        router.post(
            route('game-saves.lineup.update', { gameSave: gameSave.value.id }),
            { team_id: selectedOtherTeam.value.id, slots: otherLineupForm.value },
            { preserveScroll: true, only: LINEUP_PROPS }
        );
    };

    const changeOtherPlayerSlot = (newSlotValue) => {
        const player = selectedOtherPlayer.value;
        if (!player) return;
        if (moveToSlot(otherLineupForm.value, player.id, newSlotValue)) saveOtherLineup();
    };

    // ==========================
    //   FORMATION
    // ==========================
    const otherFormation = computed(() =>
        selectedOtherTeam.value?.formation ?? DEFAULT_FORMATION
    );

    const otherFormationData = computed(() =>
        FORMATIONS[otherFormation.value] ?? FORMATIONS[DEFAULT_FORMATION]
    );

    const saveOtherFormation = (formationKey) => {
        if (!selectedOtherTeam.value) return;
        router.post(
            route('game-saves.lineup.formation', { gameSave: gameSave.value.id }),
            { team_id: selectedOtherTeam.value.id, formation: formationKey },
            { preserveScroll: true, only: LINEUP_PROPS }
        );
    };

    // ==========================
    //   TERRAIN UNIFIÉ
    // ==========================
    const otherPlayerPosition = (slot) => pitchPosition(otherFormationData.value, slot);

    const otherSlotToPlayer = computed(() => slotToPlayerMap(otherLineupForm.value));

    const otherPlayerForSlot = (slot) => {
        const pid = otherSlotToPlayer.value[slot];
        if (!pid) return null;
        return otherRosterWithStatus.value.find(p => p.id === pid) ?? null;
    };

    const otherSelectedSlot = computed(() => {
        if (!selectedOtherPlayer.value) return null;
        return getOtherSlotForPlayer(selectedOtherPlayer.value.id);
    });

    // `onError` : l'appelant remet l'input sur l'ancien numéro si le serveur
    // refuse (numéro déjà pris) — le message s'affiche dans le toast global.
    const updatePlayerNumber = (playerId, number, onError = null) => {
        router.patch(
            route('game-saves.players.update-number', {
                gameSave: gameSave.value.id,
                player: playerId,
            }),
            { number: parseInt(number) },
            { preserveScroll: true, only: LINEUP_PROPS, onError: () => onError?.() }
        );
    };

    // ==========================
    //   SWAP VISUEL (drag & click-to-swap) — mode debug édition complète
    // ==========================
    const pickedUpOtherPlayerId = ref(null);

    const isOtherPickedUp = (player) => !!player && pickedUpOtherPlayerId.value === player.id;

    // Reset le pickup quand on change d'équipe sélectionnée
    watch(() => selectedOtherTeam.value?.id, () => {
        pickedUpOtherPlayerId.value = null;
    });

    const swapOtherPitchPlayers = (playerAId, playerBId) => {
        if (!swapSlots(otherLineupForm.value, playerAId, playerBId)) return;
        saveOtherLineup();
    };

    const substituteOtherPlayer = (starterId, substituteId) => {
        if (!starterId || !substituteId || starterId === substituteId) return;
        if (!selectedOtherTeam.value) return;
        router.post(
            route('game-saves.lineup.substitute', { gameSave: gameSave.value.id }),
            { team_id: selectedOtherTeam.value.id, starter_id: starterId, substitute_id: substituteId },
            { preserveScroll: true, only: LINEUP_PROPS }
        );
    };

    /**
     * 4 cas : starter↔starter (swap), banc→terrain (sub), terrain→banc (sub), banc↔banc (no-op).
     */
    const resolveOtherPlayerInteraction = (source, target) => {
        const kind = interactionKind(source, target);
        if (kind === 'swap') swapOtherPitchPlayers(source.id, target.id);
        else if (kind === 'bench-in') substituteOtherPlayer(target.id, source.id);
        else if (kind === 'bench-out') substituteOtherPlayer(source.id, target.id);
    };

    const handleOtherPlayerClick = (player) => {
        if (!player) return;

        if (pickedUpOtherPlayerId.value === player.id) {
            pickedUpOtherPlayerId.value = null;
            return;
        }

        if (!pickedUpOtherPlayerId.value) {
            pickedUpOtherPlayerId.value = player.id;
            selectOtherPlayer(player);
            return;
        }

        const picked = otherRosterWithStatus.value.find(p => p.id === pickedUpOtherPlayerId.value);
        pickedUpOtherPlayerId.value = null;
        if (!picked) return;

        resolveOtherPlayerInteraction(picked, player);
    };

    const handleOtherDragStart = (player, ev) => {
        if (!player) return;
        pickedUpOtherPlayerId.value = player.id;
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', String(player.id));
    };

    const handleOtherDragOver = (ev) => {
        if (!pickedUpOtherPlayerId.value) return;
        ev.preventDefault();
        ev.dataTransfer.dropEffect = 'move';
    };

    const handleOtherDrop = (targetPlayer, ev) => {
        ev.preventDefault();
        const sourceId = pickedUpOtherPlayerId.value;
        pickedUpOtherPlayerId.value = null;
        if (!sourceId || !targetPlayer || sourceId === targetPlayer.id) return;

        const source = otherRosterWithStatus.value.find(p => p.id === sourceId);
        if (!source) return;

        resolveOtherPlayerInteraction(source, targetPlayer);
    };

    const cancelOtherPickup = () => { pickedUpOtherPlayerId.value = null; };

    return {
        otherTeams, selectedOtherTeamId, selectedOtherTeam, selectOtherTeam,
        otherRosterWithStatus, selectedOtherPlayer, selectOtherPlayer,
        toggleOtherStarter,toggleOtherCaptain,
        otherLineupForm, getOtherSlotForPlayer, changeOtherPlayerSlot,
        otherFormation, otherFormationData, saveOtherFormation,
        otherPlayerPosition, otherPlayerForSlot, otherSelectedSlot,
        pickedUpOtherPlayerId, isOtherPickedUp,
        handleOtherPlayerClick, handleOtherDragStart, handleOtherDragOver, handleOtherDrop, cancelOtherPickup,
        swapOtherPitchPlayers, substituteOtherPlayer, updatePlayerNumber,
        FORMATIONS, FORMATION_LIST,
    };
}
