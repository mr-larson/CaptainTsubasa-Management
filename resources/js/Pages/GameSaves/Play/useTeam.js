// resources/js/Pages/GameSaves/Play/useTeam.js
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { FORMATIONS, DEFAULT_FORMATION, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';
import { usePlayerUtils } from './usePlayerUtils.js';
import { countMismatches, optimizeSlots } from './lineupMastery.js';

export function useTeam({ gameSave, team }) {
    const { overallOf, playerPhotoUrl, teamLogoUrl } = usePlayerUtils();


    // ==========================
    //   ROSTER
    // ==========================
    const roster = computed(() => {
        if (!team.value || !Array.isArray(team.value.contracts)) return [];
        return team.value.contracts
            .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
            .filter(Boolean);
    });

    // Titularisations appliquées localement avant la réponse serveur (affichage
    // instantané) : playerId → bool. Vidé au rechargement des props ou en cas d'erreur.
    const starterOverrides = ref({});
    watch(() => team.value?.contracts, () => { starterOverrides.value = {}; });

    const rosterWithStatus = computed(() => {
        if (!team.value || !Array.isArray(team.value.contracts)) return [];
        return team.value.contracts
            .map(c => {
                const p = c.game_player ?? c.gamePlayer ?? c.player ?? null;
                if (!p) return null;
                return {
                    ...p,
                    contract_id:                      c.id,
                    contract_end_week:                c.end_week ?? null,
                    salary:                           c.salary ?? null,
                    is_starter:                       starterOverrides.value[p.id] ?? (c.is_starter ?? true),
                    is_captain:                       c.is_captain ?? false,
                    captain_rerolls_remaining:        c.captain_rerolls_remaining ?? 3,
                    captain_reroll_used_this_action:  c.captain_reroll_used_this_action ?? false,
                };
            })
            .filter(Boolean);
    });

    const starters = computed(() => rosterWithStatus.value.filter(p => p.is_starter));

    // ==========================
    //   SÉLECTION JOUEUR
    // ==========================
    const selectedMyPlayerId = ref(null);

    const selectedMyPlayer = computed(() => {
        if (!rosterWithStatus.value.length) return null;
        if (!selectedMyPlayerId.value) return rosterWithStatus.value[0];
        return rosterWithStatus.value.find(p => p.id === selectedMyPlayerId.value) ?? rosterWithStatus.value[0];
    });

    const selectMyPlayer = (player) => { selectedMyPlayerId.value = player.id; };

    // ==========================
    //   OVERALL
    // ==========================

    // ==========================
    //   TOGGLE STARTER
    // ==========================
    // ==========================
    //   ANNULER (dernier changement de compo)
    // ==========================
    const lastChange = ref(null);
    let undoTimer = null;

    const recordChange = (label, undo) => {
        clearTimeout(undoTimer);
        lastChange.value = { id: Date.now(), label, undo };
        undoTimer = setTimeout(() => { lastChange.value = null; }, 6000);
    };

    const undoLastChange = () => {
        const change = lastChange.value;
        clearTimeout(undoTimer);
        lastChange.value = null;
        cancelPickup();
        change?.undo();
    };

    // Refus serveur : on revient à l'état des props (le message passe par le toast global).
    const rollback = () => {
        starterOverrides.value = {};
        lastChange.value = null;
        initLineupForm();
    };

    const nameOf = (id) => rosterWithStatus.value.find(p => p.id === id)?.lastname ?? 'Joueur';

    // `slot` (optionnel) : poste libre du terrain sur lequel titulariser le joueur.
    const toggleStarter = (contractId, slot = null, record = true) => {
        if (!contractId) return;
        cancelPickup();
        const player = rosterWithStatus.value.find(p => p.contract_id === contractId);

        // Affichage instantané
        let freedSlot = null;
        if (player) {
            if (player.is_starter) {
                const row = lineupForm.value.find(r => r.player_id === player.id);
                if (row) { freedSlot = row.slot; row.player_id = null; }
            } else {
                const row = (slot && lineupForm.value.find(r => r.slot === Number(slot) && !r.player_id))
                    || lineupForm.value.find(r => !r.player_id);
                if (row) row.player_id = player.id;
            }
            starterOverrides.value = { ...starterOverrides.value, [player.id]: !player.is_starter };
        }

        if (player && record) {
            if (freedSlot !== null || player.is_starter) {
                recordChange(`${player.lastname} sort du onze`, () => toggleStarter(contractId, freedSlot, false));
            } else {
                recordChange(`${player.lastname} titularisé`, () => toggleStarter(contractId, null, false));
            }
        }

        router.patch(
            route('game-saves.contracts.toggle-starter', { gameSave: gameSave.value.id, contract: contractId }),
            slot ? { slot } : {},
            { preserveScroll: true, onError: rollback }
        );
    };

    const toggleCaptain = (contractId) => {
        if (!contractId) return;
        router.patch(route('game-saves.contracts.toggle-captain', { gameSave: gameSave.value.id, contract: contractId }), {}, { preserveScroll: true });
    };

    // `onError` : l'appelant remet l'input sur l'ancien numéro si le serveur
    // refuse (numéro déjà pris) — le message s'affiche dans le toast global.
    const updatePlayerNumber = (playerId, number, onError = null) => {
        router.patch(
            route('game-saves.players.update-number', {
                gameSave: gameSave.value.id,
                player: playerId,
            }),
            { number: parseInt(number) },
            { preserveScroll: true, onError: () => onError?.() }
        );
    };

    // ==========================
    //   LINEUP (slots)
    // ==========================
    const initialLineupSlots = computed(() => {
        const lineup = gameSave.value.state?.lineup ?? {};
        if (!team.value) return {};
        return lineup[team.value.id]?.slots ?? {};
    });

    const lineupForm = ref([]);

    const getSlotForPlayer = (playerId) => {
        if (!playerId || !Array.isArray(lineupForm.value)) return null;
        const row = lineupForm.value.find(r => r.player_id === playerId);
        return row ? row.slot : null;
    };

    const initLineupForm = () => {
        const unplaced = new Set(starters.value.map(player => player.id));
        const slots = [];
        for (let slot = 1; slot <= 11; slot++) {
            const playerId = initialLineupSlots.value[slot];
            slots.push({ slot, player_id: unplaced.delete(playerId) ? playerId : null });
        }
        for (const playerId of unplaced) {
            const available = slots.find(row => !row.player_id);
            if (!available) break;
            available.player_id = playerId;
        }
        lineupForm.value = slots;
    };

    initLineupForm();
    watch(() => [team.value?.id, team.value?.contracts, gameSave.value.state], () => initLineupForm());

    const saveLineup = () => {
        if (!team.value) return;
        router.post(
            route('game-saves.lineup.update', { gameSave: gameSave.value.id }),
            { team_id: team.value.id, slots: lineupForm.value },
            { preserveScroll: true, onError: rollback }
        );
    };

    const slotsSnapshot = () => Object.fromEntries(lineupForm.value.map(r => [r.slot, r.player_id ?? null]));
    const applySlots = (map) => {
        lineupForm.value = lineupForm.value.map(r => ({ slot: r.slot, player_id: map[r.slot] ?? null }));
    };

    const changeSelectedPlayerSlot = (newSlotValue) => {
        const player = selectedMyPlayer.value;
        if (!player) return;
        const slotNumber = Number(newSlotValue);
        if (!slotNumber || slotNumber < 1 || slotNumber > 11) return;

        const playerId    = player.id;
        const currentSlot = getSlotForPlayer(playerId);
        if (currentSlot === slotNumber) return;

        const currentRow = lineupForm.value.find(r => r.player_id === playerId) || null;
        const targetRow  = lineupForm.value.find(r => r.slot === slotNumber) || null;
        if (!targetRow) return;

        const previousPlayerAtTarget = targetRow.player_id ?? null;
        if (currentRow) {
            currentRow.player_id = previousPlayerAtTarget;
            targetRow.player_id  = playerId;
        } else {
            targetRow.player_id = playerId;
        }
        saveLineup();
    };

    // ==========================
    //   FORMATION
    // ==========================
    // Formation affichée : celle choisie localement (mise à jour immédiate au
    // clic) jusqu'au rechargement des props, sinon celle de l'équipe en base.
    const pendingFormation = ref(null);
    const currentFormation = computed(() =>
        pendingFormation.value ?? team.value?.formation ?? DEFAULT_FORMATION
    );

    watch(() => [team.value?.id, team.value?.formation], () => { pendingFormation.value = null; });

    const formationData = computed(() => FORMATIONS[currentFormation.value] ?? FORMATIONS[DEFAULT_FORMATION]);

    const saveFormation = (formationKey, record = true) => {
        if (!team.value) return;
        cancelPickup();
        const previous = currentFormation.value;
        if (record && previous !== formationKey) {
            recordChange(`Formation ${formationKey}`, () => saveFormation(previous, false));
        }
        pendingFormation.value = formationKey;
        router.post(
            route('game-saves.lineup.formation', { gameSave: gameSave.value.id }),
            { team_id: team.value.id, formation: formationKey },
            { preserveScroll: true }
        );
    };

    // ==========================
    //   SLOT ROLE INFO (mini terrain)
    // ==========================
    function slotRoleInfo(slot) {
        const s   = Number(slot);
        const def = formationData.value?.slots?.[s];
        if (!def) return { label: '—', x: 50, y: 50 };

        const zoneLabels = { 0: 'Gardien', 1: 'Défenseur', 2: 'Milieu défensif', 3: 'Milieu offensif', 4: 'Attaquant' };
        const ZONE_X     = { 0: 8, 1: 25, 2: 45, 3: 65, 4: 85 };
        const LANE_Y     = { 0: 8, 1: 27, 2: 50, 3: 73, 4: 92 };

        return {
            label: zoneLabels[def.zone] ?? '—',
            x:     ZONE_X[def.zone]     ?? 50,
            y:     def.laneIndex === null ? 50 : (LANE_Y[def.laneIndex] ?? 50),
        };
    }

    const miniPitchMarkerStyle = computed(() => {
        const p    = selectedMyPlayer.value;
        if (!p) return {};
        const slot = getSlotForPlayer(p.id);
        if (!slot) return {};
        const info = slotRoleInfo(slot);
        return { left: `${info.x}%`, top: `${info.y}%` };
    });

    // ==========================
    //   HELPERS
    // ==========================
    // ==========================
    //   TERRAIN UNIFIÉ (coordonnées visuelles)
    // ==========================
    const PITCH_ZONE_X = { 0: 6, 1: 22, 2: 40, 3: 60, 4: 78 };
    const PITCH_LANE_Y = { 0: 10, 1: 30, 2: 50, 3: 70, 4: 90 };

    const playerPosition = (slot) => {
        const def = formationData.value?.slots?.[Number(slot)];
        if (!def) return { x: 50, y: 50 };
        return {
            x: PITCH_ZONE_X[def.zone] ?? 50,
            y: def.laneIndex === null ? 50 : (PITCH_LANE_Y[def.laneIndex] ?? 50),
        };
    };

    const slotToPlayer = computed(() => {
        const map = {};
        lineupForm.value.forEach(row => { if (row.player_id) map[row.slot] = row.player_id; });
        return map;
    });

    const playerForSlot = (slot, rosterRef) => {
        const pid = slotToPlayer.value[slot];
        if (!pid) return null;
        return (rosterRef ?? rosterWithStatus.value).find(p => p.id === pid) ?? null;
    };

    const selectedSlot = computed(() => {
        if (!selectedMyPlayer.value) return null;
        return getSlotForPlayer(selectedMyPlayer.value.id);
    });

    // ==========================
    //   HELPERS URL
    // ==========================
    // ==========================
    //   SWAP VISUEL (drag & click-to-swap)
    // ==========================
    const pickedUpPlayerId = ref(null);

    const isPickedUp = (player) => !!player && pickedUpPlayerId.value === player.id;

    const swapPitchPlayers = (playerAId, playerBId, record = true) => {
        if (!playerAId || !playerBId || playerAId === playerBId) return;
        const rowA = lineupForm.value.find(r => r.player_id === playerAId);
        const rowB = lineupForm.value.find(r => r.player_id === playerBId);
        if (!rowA || !rowB) return; // les deux doivent être titulaires

        [rowA.player_id, rowB.player_id] = [rowB.player_id, rowA.player_id];
        if (record) {
            recordChange(`${nameOf(playerAId)} ⇄ ${nameOf(playerBId)}`, () => swapPitchPlayers(playerAId, playerBId, false));
        }
        saveLineup();
    };

    const substitutePlayer = (starterId, substituteId, record = true) => {
        if (!starterId || !substituteId || starterId === substituteId) return;
        if (!team.value) return;

        // Affichage instantané : le rentrant prend le slot du sortant.
        const row = lineupForm.value.find(r => r.player_id === starterId);
        if (row) row.player_id = substituteId;
        starterOverrides.value = { ...starterOverrides.value, [starterId]: false, [substituteId]: true };

        if (record) {
            recordChange(`${nameOf(substituteId)} remplace ${nameOf(starterId)}`, () => substitutePlayer(substituteId, starterId, false));
        }

        router.post(
            route('game-saves.lineup.substitute', { gameSave: gameSave.value.id }),
            { team_id: team.value.id, starter_id: starterId, substitute_id: substituteId },
            { preserveScroll: true, onError: rollback }
        );
    };

    /**
     * Clic sur un pion (terrain ou banc) :
     * - 1er clic         → sélectionne seulement (la fiche s'affiche) ;
     * - re-clic          → soulève le joueur (prêt à être échangé) ;
     * - joueur soulevé   → résout l'échange / la substitution avec la cible ;
     * - re-clic soulevé  → annule.
     * Séparer sélection et prise évite qu'un simple clic de consultation ne
     * déclenche un échange avec un joueur resté soulevé.
     */
    const handlePlayerClick = (player) => {
        if (!player) return;

        // Re-clic sur le pion soulevé → annule
        if (pickedUpPlayerId.value === player.id) {
            pickedUpPlayerId.value = null;
            return;
        }

        if (!pickedUpPlayerId.value) {
            if (selectedMyPlayer.value?.id === player.id) {
                pickedUpPlayerId.value = player.id;   // déjà sélectionné → on le prend
            } else {
                selectMyPlayer(player);               // sinon simple sélection
            }
            return;
        }

        // Un joueur est déjà soulevé → on résout selon les statuts
        const picked = rosterWithStatus.value.find(p => p.id === pickedUpPlayerId.value);
        pickedUpPlayerId.value = null;
        if (!picked) return;

        resolvePlayerInteraction(picked, player);
    };

    // Soulève un joueur explicitement (bouton « Faire entrer… » / « Déplacer… »).
    const pickUpPlayer = (player) => {
        if (!player) return;
        selectMyPlayer(player);
        pickedUpPlayerId.value = player.id;
    };

    const handleDragStart = (player, ev) => {
        if (!player) return;
        selectMyPlayer(player);
        pickedUpPlayerId.value = player.id;
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', String(player.id));
        // ghost image transparent pour un effet plus propre (optionnel)
    };

    const handleDragOver = (ev) => {
        if (!pickedUpPlayerId.value) return;
        ev.preventDefault();
        ev.dataTransfer.dropEffect = 'move';
    };

    const handleDrop = (targetPlayer, ev) => {
        ev.preventDefault();
        const sourceId = pickedUpPlayerId.value;
        pickedUpPlayerId.value = null;
        if (!sourceId || !targetPlayer || sourceId === targetPlayer.id) return;

        const source = rosterWithStatus.value.find(p => p.id === sourceId);
        if (!source) return;

        resolvePlayerInteraction(source, targetPlayer);
    };

    /**
     * Détermine l'action selon les statuts source / target :
     * - 2 titulaires    → swap des positions (slot ↔ slot)
     * - banc → terrain  → substitution (le banc monte, le titulaire descend)
     * - terrain → banc  → substitution inversée (même résultat)
     * - 2 remplaçants   → no-op
     */
    const resolvePlayerInteraction = (source, target) => {
        if (source.is_starter && target.is_starter) {
            swapPitchPlayers(source.id, target.id);
        } else if (!source.is_starter && target.is_starter) {
            substitutePlayer(target.id, source.id);
        } else if (source.is_starter && !target.is_starter) {
            substitutePlayer(source.id, target.id);
        }
        // banc ↔ banc → rien
    };

    const cancelPickup = () => { pickedUpPlayerId.value = null; };

    // ==========================
    //   POSTE VIDE (slot sans joueur)
    // ==========================
    /**
     * Place un joueur sur un slot vide :
     * - titulaire  → il quitte son slot actuel (qui devient vide) ;
     * - remplaçant → il est titularisé directement sur ce slot.
     */
    const fillEmptySlot = (slot, player, record = true) => {
        cancelPickup();
        const target = lineupForm.value.find(r => r.slot === Number(slot));
        if (!player || !target || target.player_id) return;

        if (player.is_starter) {
            const current = lineupForm.value.find(r => r.player_id === player.id);
            const from = current?.slot ?? null;
            if (current) current.player_id = null;
            target.player_id = player.id;
            if (record && from) {
                recordChange(`${player.lastname} change de poste`, () => fillEmptySlot(from, player, false));
            }
            saveLineup();
        } else {
            toggleStarter(player.contract_id, Number(slot));
        }
    };

    const handleDropOnSlot = (slot, ev) => {
        ev.preventDefault();
        const source = rosterWithStatus.value.find(p => p.id === pickedUpPlayerId.value);
        cancelPickup();
        if (source) fillEmptySlot(slot, source);
    };

    // ==========================
    //   FORMATION : impact + réorganisation
    // ==========================
    const playerById = (id) => (id ? rosterWithStatus.value.find(p => p.id === id) ?? null : null);

    // Hors poste avec le onze actuel posé tel quel sur la formation, et après réorganisation.
    const formationImpact = (formationKey) => {
        const formation = FORMATIONS[formationKey];
        const slots = slotsSnapshot();
        return {
            asIs:      countMismatches(slots, formation, playerById),
            optimized: countMismatches(optimizeSlots(slots, formation, playerById), formation, playerById),
        };
    };

    const reorganizeLineup = () => {
        cancelPickup();
        const before = slotsSnapshot();
        const after  = optimizeSlots(before, formationData.value, playerById);
        if (Object.keys(before).every(k => before[k] === after[k])) return;
        applySlots(after);
        recordChange('Onze réorganisé', () => { applySlots(before); saveLineup(); });
        saveLineup();
    };

    return {
        roster, rosterWithStatus, starters,
        lastChange, undoLastChange, formationImpact, reorganizeLineup,
        selectedMyPlayerId, selectedMyPlayer, selectMyPlayer,
        overallOf, toggleStarter, toggleCaptain, updatePlayerNumber,
        lineupForm, getSlotForPlayer, saveLineup, changeSelectedPlayerSlot,
        currentFormation, formationData, saveFormation,
        slotRoleInfo, miniPitchMarkerStyle,
        playerPhotoUrl, teamLogoUrl,
        playerPosition, slotToPlayer, playerForSlot, selectedSlot,
        pickedUpPlayerId, isPickedUp,
        handlePlayerClick, handleDragStart, handleDragOver, handleDrop, cancelPickup,
        pickUpPlayer, fillEmptySlot, handleDropOnSlot,
        swapPitchPlayers, substitutePlayer,
        FORMATIONS, FORMATION_LIST,
    };
}
