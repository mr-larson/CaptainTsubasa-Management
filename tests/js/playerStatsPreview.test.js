import { describe, expect, it } from 'vitest';
import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import PlayerStatsPreview from '../../resources/js/Components/PlayerStatsPreview.vue';
import { PLAYER_STATS, defaultPlayerStats } from '../../resources/js/Enums/playerStats.js';

const render = (props) => renderToString(createSSRApp({ render: () => h(PlayerStatsPreview, props) }));

describe('statistiques modifiables dans la carte', () => {
    it('affiche tous les champs, même à zéro, pour permettre la création et la saisie', async () => {
        const html = await render({ stats: defaultPlayerStats(), editable: true });
        expect((html.match(/type="number"/g) ?? []).length).toBe(PLAYER_STATS.length);
        for (const stat of PLAYER_STATS) expect(html).toContain(`id="stat-${stat.key}"`);
        expect(html).toContain('min="0" max="100" step="1"');
    });

    it('associe les erreurs du catalogue ou de la partie au bon champ', async () => {
        for (const errorPrefix of ['', 'stats.']) {
            const html = await render({ stats: { shot: 110 }, editable: true, errorPrefix, errors: { [`${errorPrefix}shot`]: 'Maximum : 100.' } });
            expect(html).toContain('aria-invalid="true" aria-describedby="stat-error-shot"');
            expect(html).toContain('id="stat-error-shot"');
            expect(html).toContain('Maximum : 100.');
        }
    });

    it('conserve le rendu en lecture seule sans champs de saisie', async () => {
        const html = await render({ stats: { speed: 63, shot: 0 } });
        expect(html).not.toContain('<input');
        expect(html).toContain('63');
        expect(html).not.toContain('Arrêt poing');
    });
});
