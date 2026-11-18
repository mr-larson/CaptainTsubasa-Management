import { describe, expect, it } from 'vitest';
import { universeEnabled, setUniverseEnabled, toggleLegendCountry } from '../../resources/js/Components/universeSelection';

describe('sélection des univers', () => {
    it('convertit le filtre international historique sans activer un autre univers', () => {
        const config = { internationals_visible: false, visible_origins: { captain_tsubasa_international: true, world_legends: false } };
        expect(universeEnabled(config, 'captain_tsubasa_international')).toBe(false);
        setUniverseEnabled(config, 'world_legends', true);
        expect(universeEnabled(config, 'world_legends')).toBe(true);
        expect(universeEnabled(config, 'captain_tsubasa_international')).toBe(false);
        setUniverseEnabled(config, 'captain_tsubasa_international', true);
        setUniverseEnabled(config, 'world_legends', false);
        expect(universeEnabled(config, 'captain_tsubasa_international')).toBe(true);
    });
    it('distingue tous les pays, une sélection et aucun pays', () => {
        const config = { legends_countries: null };
        toggleLegendCountry(config, 'France', false, ['France', 'Belgique']);
        expect(config.legends_countries).toEqual(['Belgique']);
        toggleLegendCountry(config, 'Belgique', false, ['France', 'Belgique']);
        expect(config.legends_countries).toEqual([]);
        toggleLegendCountry(config, 'France', true, ['France', 'Belgique']);
        expect(config.legends_countries).toEqual(['France']);
    });
});
