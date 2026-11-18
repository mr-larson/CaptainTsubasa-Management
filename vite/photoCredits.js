import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

/**
 * Modules virtuels dérivés des fichiers de crédits des visuels joueurs (resources/data) :
 *
 * - `virtual:photo-credits-index` : de quoi AFFICHER la vignette, par slug (cadrage d'un portrait,
 *   dimensions, position de l'objet). Petit, chargé avec <PlayerPhoto>.
 * - `virtual:photo-credits-detail` : source et texte de crédit par slug. Lourd, utilisé seulement
 *   quand la photo est agrandie ; importé à la demande, donc dans son propre chunk.
 *
 * Générés au build à partir des JSON : ils ne peuvent pas dériver des fichiers de crédits, qui
 * restent la seule source (page des crédits, commandes artisan).
 */
const INDEX = 'virtual:photo-credits-index';
const DETAIL = 'virtual:photo-credits-detail';
const PHOTOS = resolve('resources/data/player-photo-credits.json');
const ILLUSTRATIONS = resolve('resources/data/player-illustration-credits.json');

export default function photoCredits() {
    return {
        name: 'photo-credits',

        resolveId(id) {
            if (id === INDEX || id === DETAIL) return `\0${id}`;
        },

        load(id) {
            if (id !== `\0${INDEX}` && id !== `\0${DETAIL}`) return;

            this.addWatchFile(PHOTOS);
            this.addWatchFile(ILLUSTRATIONS);
            const photos = JSON.parse(readFileSync(PHOTOS, 'utf8'));
            const illustrations = JSON.parse(readFileSync(ILLUSTRATIONS, 'utf8'));

            const out = {};
            if (id === `\0${INDEX}`) {
                for (const entry of photos) {
                    out[entry.slug] = entry.object_position ? { o: entry.object_position } : {};
                }
                for (const entry of illustrations) {
                    const row = { i: 1 }; // illustration (source détaillée dans l'autre module)
                    if (entry.object_position) row.o = entry.object_position;
                    if (entry.portrait_crop) row.c = entry.portrait_crop;
                    row.w = entry.image_width;
                    row.h = entry.image_height;
                    out[entry.slug] = row;
                }
            } else {
                for (const entry of illustrations) {
                    const row = {};
                    if (entry.source_url) row.u = entry.source_url;
                    if (entry.credit) row.k = entry.credit;
                    out[entry.slug] = row;
                }
            }

            return `export default ${JSON.stringify(out)};`;
        },
    };
}
