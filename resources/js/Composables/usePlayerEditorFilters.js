import { computed, reactive, toValue } from 'vue';
import { PLAYER_STATS } from '@/Enums/playerStats.js';
import { positionGroup } from '@/Enums/positionBadges.js';
import { ORIGIN_OPTIONS } from '@/Pages/GameSaves/Play/useRecruitFilters.js';
import { compareRecruits, playerStat } from '@/Pages/GameSaves/Play/recruitment.js';

export function usePlayerEditorFilters(players, { gameSave = null } = {}) {
    const filters = reactive({ position: 'ALL', origin: 'ALL', team: 'ALL', sort: 'name', direction: 'desc' });
    const origins = computed(() => {
        const keys = [...new Set(toValue(players).map(p => p.origin ?? 'unknown'))];
        return keys.map(key => ({ key, label: ORIGIN_OPTIONS.find(o => o.key === key)?.label
            ?? ({ unknown: 'Non renseigné', fictional: 'Fictifs' }[key] ?? key) }));
    });
    const currentContracts = player => (player.contracts ?? []).filter(contract => {
        const save = toValue(gameSave);
        if (!save) return contract.team_id != null;
        return contract.game_team_id != null
            && (contract.season == null || Number(contract.season) === Number(save.season))
            && Number(contract.start_week ?? 1) <= Number(save.week ?? 1)
            && (contract.end_week == null || Number(contract.end_week) >= Number(save.week ?? 1));
    });
    const overall = player => {
        const values = PLAYER_STATS.map(({ key }) => playerStat(player, key)).filter(v => Number.isFinite(v) && v > 0);
        return values.length ? Math.round(values.reduce((a, b) => a + b, 0) / values.length) : 0;
    };
    const filtered = computed(() => toValue(players).filter(player => {
        if (filters.position !== 'ALL' && positionGroup(player.position) !== filters.position) return false;
        if (filters.origin !== 'ALL' && (player.origin ?? 'unknown') !== filters.origin) return false;
        if (filters.team === 'ALL') return true;
        const contracts = currentContracts(player);
        return filters.team === 'FREE' ? contracts.length === 0
            : contracts.some(c => String(c.game_team_id ?? c.team_id) === String(filters.team));
    }).sort((a, b) => {
        const result = filters.sort === 'cost'
            ? Number(b.cost ?? 0) - Number(a.cost ?? 0) || compareRecruits(a, b, 'name', () => 0, overall)
            : compareRecruits(a, b, filters.sort, p => Number(p.cost ?? 0), overall);
        if (filters.sort === 'name') return result;
        return filters.direction === 'asc' ? -result : result;
    }));
    const reset = () => Object.assign(filters, { position: 'ALL', origin: 'ALL', team: 'ALL', sort: 'name', direction: 'desc' });
    return { filters, origins, filtered, reset };
}
