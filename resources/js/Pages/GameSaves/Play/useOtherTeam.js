// resources/js/Pages/GameSaves/Play/useOtherTeam.js
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { FORMATIONS, DEFAULT_FORMATION, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';

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
                const p = c.game_player ?? c.gamePlayer ?? c.player ?? null;
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
            route('game-contracts.toggle-starter', { contract: contractId }),
            {},
            { preserveScroll: true }
        );
    };

    const toggleOtherCaptain = (contractId) => {
        if (!contractId) return;
        router.patch(route('game-contracts.toggle-captain', { contract: contractId }), {}, { preserveScroll: true });
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
        const slots = [];
        for (let slot = 1; slot <= 11; slot++) {
            slots.push({ slot, player_id: otherLineupSlots.value[slot] ?? null });
        }
        if (!Object.keys(otherLineupSlots.value).length) {
            const starters = otherRosterWithStatus.value.filter(p => p.is_starter);
            starters.forEach((p, i) => { if (i < 11) slots[i].player_id = p.id; });
        }
        otherLineupForm.value = slots;
    };

    initOtherLineupForm();
    watch(
        () => [selectedOtherTeam.value?.id, gameSave.value.state],
        () => initOtherLineupForm()
    );

    const getOtherSlotForPlayer = (playerId) => {
        if (!playerId || !Array.isArray(otherLineupForm.value)) return null;
        const row = otherLineupForm.value.find(r => r.player_id === playerId);
        return row ? row.slot : null;
    };

    const saveOtherLineup = () => {
        if (!selectedOtherTeam.value) return;
        router.post(
            route('game-saves.lineup.update', { gameSave: gameSave.value.id }),
            { team_id: selectedOtherTeam.value.id, slots: otherLineupForm.value },
            { preserveScroll: true }
        );
    };

    const changeOtherPlayerSlot = (newSlotValue) => {
        const player = selectedOtherPlayer.value;
        if (!player) return;
        const slotNumber = Number(newSlotValue);
        if (!slotNumber || slotNumber < 1 || slotNumber > 11) return;

        const playerId    = player.id;
        const currentSlot = getOtherSlotForPlayer(playerId);
        if (currentSlot === slotNumber) return;

        const currentRow = otherLineupForm.value.find(r => r.player_id === playerId) || null;
        const targetRow  = otherLineupForm.value.find(r => r.slot === slotNumber) || null;
        if (!targetRow) return;

        const prev = targetRow.player_id ?? null;
        if (currentRow) {
            currentRow.player_id = prev;
            targetRow.player_id  = playerId;
        } else {
            targetRow.player_id = playerId;
        }
        saveOtherLineup();
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
            { preserveScroll: true }
        );
    };

    // ==========================
    //   TERRAIN UNIFIÉ
    // ==========================
    const PITCH_ZONE_X = { 0: 6, 1: 22, 2: 40, 3: 60, 4: 78 };
    const PITCH_LANE_Y = { 0: 10, 1: 30, 2: 50, 3: 70, 4: 90 };

    const otherPlayerPosition = (slot) => {
        const def = otherFormationData.value?.slots?.[Number(slot)];
        if (!def) return { x: 50, y: 50 };
        return {
            x: PITCH_ZONE_X[def.zone] ?? 50,
            y: def.laneIndex === null ? 50 : (PITCH_LANE_Y[def.laneIndex] ?? 50),
        };
    };

    const otherSlotToPlayer = computed(() => {
        const map = {};
        otherLineupForm.value.forEach(row => { if (row.player_id) map[row.slot] = row.player_id; });
        return map;
    });

    const otherPlayerForSlot = (slot) => {
        const pid = otherSlotToPlayer.value[slot];
        if (!pid) return null;
        return otherRosterWithStatus.value.find(p => p.id === pid) ?? null;
    };

    const otherSelectedSlot = computed(() => {
        if (!selectedOtherPlayer.value) return null;
        return getOtherSlotForPlayer(selectedOtherPlayer.value.id);
    });

    const updatePlayerNumber = (playerId, number) => {
        router.patch(
            route('game-saves.players.update-number', {
                gameSave: gameSave.value.id,
                player: playerId,
            }),
            { number: parseInt(number) },
            { preserveScroll: true }
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
        if (!playerAId || !playerBId || playerAId === playerBId) return;
        const rowA = otherLineupForm.value.find(r => r.player_id === playerAId);
        const rowB = otherLineupForm.value.find(r => r.player_id === playerBId);
        if (!rowA || !rowB) return;

        [rowA.player_id, rowB.player_id] = [rowB.player_id, rowA.player_id];
        saveOtherLineup();
    };

    const substituteOtherPlayer = (starterId, substituteId) => {
        if (!starterId || !substituteId || starterId === substituteId) return;
        if (!selectedOtherTeam.value) return;
        router.post(
            route('game-saves.lineup.substitute', { gameSave: gameSave.value.id }),
            { team_id: selectedOtherTeam.value.id, starter_id: starterId, substitute_id: substituteId },
            { preserveScroll: true }
        );
    };

    /**
     * 4 cas : starter↔starter (swap), banc→terrain (sub), terrain→banc (sub), banc↔banc (no-op).
     */
    const resolveOtherPlayerInteraction = (source, target) => {
        if (source.is_starter && target.is_starter) {
            swapOtherPitchPlayers(source.id, target.id);
        } else if (!source.is_starter && target.is_starter) {
            substituteOtherPlayer(target.id, source.id);
        } else if (source.is_starter && !target.is_starter) {
            substituteOtherPlayer(source.id, target.id);
        }
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
