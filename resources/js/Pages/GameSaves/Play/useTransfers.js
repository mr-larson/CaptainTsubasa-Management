// resources/js/Pages/GameSaves/Play/useTransfers.js
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useTransfers({ gameSave, freePlayers, team, teams }) {

    // ==========================
    //   AGENTS LIBRES
    // ==========================
    const freeAgentSignings   = computed(() => gameSave.value.state?.free_agent_signings ?? []);
    const signedFreePlayerIds = computed(() => freeAgentSignings.value.map(s => s.player_id));

    const availableFreePlayers = computed(() => {
        if (!Array.isArray(freePlayers.value)) return [];
        return freePlayers.value.filter(p => !signedFreePlayerIds.value.includes(p.id));
    });

    // ==========================
    //   MODAL
    // ==========================
    const showTransferModal = ref(false);
    const transferTarget    = ref(null);
    const transferMatches   = ref(10);
    const transferSalary    = ref(0);
    const transferReason    = ref('');

    const transferTotalCost = computed(() =>
        (Number(transferMatches.value) || 0) * (Number(transferSalary.value) || 0)
    );

    const openTransferModal = (player) => {
        transferTarget.value    = player;
        transferMatches.value   = 10;
        // Coût ajusté = coût + légère majoration de polyvalence (postes secondaires)
        transferSalary.value    = player.adjusted_cost ?? player.cost ?? 0;
        transferReason.value    = '';
        showTransferModal.value = true;
    };

    const closeTransferModal = () => {
        showTransferModal.value = false;
        transferTarget.value    = null;
    };

    const confirmTransfer = () => {
        if (!team.value || !transferTarget.value) return;
        router.post(
            route('game-saves.free-agents.sign', { gameSave: gameSave.value.id, player: transferTarget.value.id }),
            {
                team_id:       team.value.id,
                salary:        transferSalary.value,
                matches_total: transferMatches.value,
                reason:        transferReason.value,
            },
            { preserveScroll: true, onSuccess: () => closeTransferModal() }
        );
    };

    // ==========================
    //   HISTORIQUE TRANSFERTS
    // ==========================
    const transferHistory = computed(() => {
        const history = [];
        for (const t of (teams?.value ?? [])) {
            for (const c of (t.contracts ?? [])) {
                if ((c.start_week ?? 1) <= 1) continue;
                const p = c.game_player ?? c.gamePlayer ?? null;
                if (!p) continue;
                history.push({
                    player:     p,
                    team:       t,
                    start_week: c.start_week,
                    salary:     c.salary,
                    is_starter: c.is_starter,
                });
            }
        }
        return history.sort((a, b) => b.start_week - a.start_week);
    });


    return {
        availableFreePlayers,
        showTransferModal, transferTarget,
        transferMatches, transferSalary, transferReason,
        transferTotalCost,
        openTransferModal, closeTransferModal, confirmTransfer,
        transferHistory,
    };
}
