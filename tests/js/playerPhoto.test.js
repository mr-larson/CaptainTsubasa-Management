import { describe, expect, it, vi } from 'vitest';
import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import credits from 'virtual:photo-credits-index';
import PlayerAvatar from '../../resources/js/Components/PlayerAvatar.vue';
import PlayerPhoto from '../../resources/js/Components/PlayerPhoto.vue';

vi.mock('@inertiajs/vue3', () => ({ usePage: () => ({ props: {} }) }));
const render = (component, props) => renderToString(createSSRApp({ render: () => h(component, props) }));
const [slug, credit] = Object.entries(credits).find(([, entry]) => entry.c);
const src = `/storage/players/${slug}.jpg`;

describe('portraits miniatures', () => {
    it('cadre le portrait même quand la miniature ne peut pas être agrandie', async () => {
        const html = await render(PlayerAvatar, { player: { photo_path: `players/${slug}.jpg` }, zoomable: false });
        expect(html).toContain(`viewbox="${credit.c.join(' ')}"`);
        expect(html).toContain(`href="${src}"`);
        expect(html).toContain(`width="${credit.w}"`);
        expect(html).not.toContain('cursor-zoom-in');
        expect(html).not.toContain('<img');
    });

    it('conserve le même cadrage avec ou sans agrandissement', async () => {
        for (const zoomable of [true, false]) {
            const html = await render(PlayerPhoto, { src, zoomable });
            expect(html).toContain(`viewbox="${credit.c.join(' ')}"`);
            expect(html.includes('cursor-zoom-in')).toBe(zoomable);
        }
    });

    it('conserve les photos ordinaires et le nom accessible du joueur', async () => {
        const html = await render(PlayerAvatar, {
            player: { firstname: 'Test', lastname: 'Joueur', photo_path: 'players/upload.jpg' }, zoomable: false,
        });
        expect(html).toContain('<img');
        expect(html).toContain('src="/storage/players/upload.jpg"');
        expect(html).toContain('alt="Test Joueur"');
        expect(html).not.toContain('cursor-zoom-in');
    });
});
