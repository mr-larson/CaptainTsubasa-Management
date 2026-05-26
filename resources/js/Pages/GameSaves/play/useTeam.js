// resources/js/Pages/GameSaves/play/useTeam.js
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { FORMATIONS, DEFAULT_FORMATION, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';

export function useTeam({ gameSave, team }) {

    // ==========================
    //   ROSTER
    // ==========================
    const roster = computed(() => {
        if (!team.value || !Array.isArray(team.value.contracts)) return [];
        return team.value.contracts
            .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
            .filter(Boolean);
    });

    const rosterWithStatus = computed(() => {
        if (!team.value || !Array.isArray(team.value.contracts)) return [];
        return team.value.contracts
            .map(c => {
                const p = c.game_player ?? c.gamePlayer ?? c.player ?? null;
                if (!p) return null;
                return { ...p, contract_id: c.id, is_starter: c.is_starter ?? true };
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
    const overallOf = (player) => {
        if (!player) return 0;
        const s = player.stats ?? player;
        const keys = ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle','hand_save','punch_save'];
        const values = keys.map(k => Number(s[k] ?? 0)).filter(v => Number.isFinite(v));
        if (!values.length) return 0;
        return Math.round(values.reduce((a, b) => a + b, 0) / values.length);
    };

    // ==========================
    //   TOGGLE STARTER
    // ==========================
    const toggleStarter = (contractId) => {
        if (!contractId) return;
        router.patch(route('game-contracts.toggle-starter', { contract: contractId }), {}, { preserveScroll: true });
    };

    const updatePlayerNumber = (playerId, number) => {
        router.patch(
            route('game-saves.players.update-number', {
                gameSave: gameSave.id,
                player: playerId,
            }),
            { number: parseInt(number) },
            { preserveScroll: true }
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
        const slots = [];
        for (let slot = 1; slot <= 11; slot++) {
            slots.push({ slot, player_id: initialLineupSlots.value[slot] ?? null });
        }
        if (!Object.keys(initialLineupSlots.value).length) {
            starters.value.forEach((p, i) => { if (i < 11) slots[i].player_id = p.id; });
        }
        lineupForm.value = slots;
    };

    initLineupForm();
    watch(() => [team.value?.id, gameSave.value.state], () => initLineupForm());

    const saveLineup = () => {
        if (!team.value) return;
        router.post(
            route('game-saves.lineup.update', { gameSave: gameSave.value.id }),
            { team_id: team.value.id, slots: lineupForm.value },
            { preserveScroll: true }
        );
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
    const currentFormation = ref(
        gameSave.value.state?.lineup?.[team.value?.id]?.formation ?? DEFAULT_FORMATION
    );

    watch(() => team.value?.id, () => {
        currentFormation.value = gameSave.value.state?.lineup?.[team.value?.id]?.formation ?? DEFAULT_FORMATION;
    });

    const formationData = computed(() => FORMATIONS[currentFormation.value] ?? FORMATIONS[DEFAULT_FORMATION]);

    const saveFormation = (formationKey) => {
        if (!team.value) return;
        currentFormation.value = formationKey;
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
    const playerPhotoUrl = (p) => {
        if (!p) return null;
        if (p.photo_path)        return `/storage/${p.photo_path}`;
        if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
        return null;
    };


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
    const teamLogoUrl = (t) => {
        const path = t?.logo_path ?? t?.team?.logo_path;
        if (!path) return null;
        if (path.startsWith('http')) return path;
        if (path.startsWith('/')) return path;
        // logo_path = "teams/xxx.png" → "/images/teams/xxx.png"
        if (path.startsWith('teams/')) return '/images/' + path;
        // logo_path = "images/teams/xxx.webp" → "/images/teams/xxx.webp"
        return '/' + path;
    };

    return {
        roster, rosterWithStatus, starters,
        selectedMyPlayerId, selectedMyPlayer, selectMyPlayer,
        overallOf, toggleStarter, updatePlayerNumber,
        lineupForm, getSlotForPlayer, saveLineup, changeSelectedPlayerSlot,
        currentFormation, formationData, saveFormation,
        slotRoleInfo, miniPitchMarkerStyle,
        playerPhotoUrl, teamLogoUrl,
        playerPosition, slotToPlayer, playerForSlot, selectedSlot,
        FORMATIONS, FORMATION_LIST,
    };
}
