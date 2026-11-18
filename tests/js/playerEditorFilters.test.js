import { describe, expect, it } from 'vitest';
import { usePlayerEditorFilters } from '../../resources/js/Composables/usePlayerEditorFilters.js';

const players = [
    { id: 1, firstname: 'Karl', lastname: 'Schneider', position: 'Forward', origin: 'captain_tsubasa_international', nationality: 'Allemagne' },
    { id: 2, firstname: 'Philipp', lastname: 'Lahm', position: 'Defender', origin: 'world_legends', nationality: 'Allemagne', contracts: [{ team_id: 5 }] },
    { id: 3, firstname: 'Lilian', lastname: 'Thuram', position: 'Defender', origin: 'world_legends', nationality: 'France' },
    { id: 4, firstname: 'Libre', lastname: 'Sans nationalité', position: 'Defender', origin: null, nationality: null },
];

describe('player editor nationality filters', () => {
    it('combines nationality, origin and contract filters', () => {
        const { filters, filtered, nationalities, origins } = usePlayerEditorFilters(() => players);
        expect(nationalities.value).toEqual(['Allemagne', 'France']);
        expect(origins.value.find(o => o.key === 'world_legends').label).toBe('Légendes internationales');
        filters.nationality = 'Allemagne';
        expect(filtered.value.map(p => p.id).sort()).toEqual([1, 2]);
        filters.origin = 'world_legends';
        expect(filtered.value.map(p => p.id)).toEqual([2]);
        filters.team = 'FREE';
        expect(filtered.value).toEqual([]);
        filters.team = '5';
        expect(filtered.value.map(p => p.id)).toEqual([2]);
    });

    it('supports unassigned nationality and resets all filters', () => {
        const { filters, filtered, reset } = usePlayerEditorFilters(() => players);
        filters.nationality = 'NONE';
        expect(filtered.value.map(p => p.id)).toEqual([4]);
        reset();
        expect(filters.nationality).toBe('ALL');
        expect(filtered.value).toHaveLength(4);
    });
});
