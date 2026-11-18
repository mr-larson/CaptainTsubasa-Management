import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import photoCredits from '../../vite/photoCredits.js';

const plugin = photoCredits();
const ctx = { addWatchFile() {} };
const load = (id) => {
    const resolved = plugin.resolveId(id);
    const code = plugin.load.call(ctx, resolved);
    return JSON.parse(code.replace(/^export default /, '').replace(/;$/, ''));
};
const read = (file) => JSON.parse(readFileSync(`resources/data/${file}`, 'utf8'));

describe('modules virtuels de crédits photo', () => {
    const index = load('virtual:photo-credits-index');
    const detail = load('virtual:photo-credits-detail');
    const photos = read('player-photo-credits.json');
    const illustrations = read('player-illustration-credits.json');

    it('ignore les autres identifiants de module', () => {
        expect(plugin.resolveId('autre-module')).toBeUndefined();
        expect(plugin.load.call(ctx, 'autre-module')).toBeUndefined();
    });

    it("indexe chaque photo et chaque illustration sous son slug", () => {
        for (const entry of [...photos, ...illustrations]) {
            expect(index).toHaveProperty([entry.slug]);
        }
        expect(Object.keys(index)).toHaveLength(photos.length + illustrations.length);
    });

    it("garde dans l'index le cadrage et la position, mais ni la source ni le texte de crédit", () => {
        const framed = illustrations.find((entry) => entry.portrait_crop);
        expect(index[framed.slug]).toMatchObject({ i: 1, c: framed.portrait_crop, w: framed.image_width, h: framed.image_height });
        for (const row of Object.values(index)) {
            expect(row).not.toHaveProperty('u');
            expect(row).not.toHaveProperty('k');
        }
        const positioned = photos.find((entry) => entry.object_position);
        if (positioned) expect(index[positioned.slug].o).toBe(positioned.object_position);
    });

    it("marque les photos sans i et les illustrations avec i, y compris sans dimensions", () => {
        for (const entry of photos) expect(index[entry.slug].i).toBeUndefined();
        for (const entry of illustrations) expect(index[entry.slug].i).toBe(1);
    });

    it('le détail porte la source et le crédit des illustrations', () => {
        for (const entry of illustrations) {
            expect(detail[entry.slug].u).toBe(entry.source_url);
            expect(detail[entry.slug].k).toBe(entry.credit);
        }
    });
});
