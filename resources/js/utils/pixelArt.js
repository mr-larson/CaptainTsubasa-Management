// resources/js/utils/pixelArt.js
// Mode Pixel (préférence d'affichage de l'utilisateur, users.pixel_art) :
// les photos joueurs pointent vers leur variante pixel art générée par
// `images:publish` (storage/players/pixel/slug.png). L'agrandissement sans
// lissage est assuré globalement en CSS (cf. app.css).
import { usePage } from '@inertiajs/vue3';

// /storage/players/slug.webp → /storage/players/pixel/slug.png.
// Les uploads (nom aléatoire de 40 caractères) n'ont pas de variante pixel.
const PLAYER_PHOTO = /^(.*\/storage\/players\/)([^/]+)\.(webp|png|jpe?g)$/i;
const UPLOAD_NAME  = /^[A-Za-z0-9]{40}$/;

export function toPixelSrc(src) {
    const m = typeof src === 'string' ? src.match(PLAYER_PHOTO) : null;
    if (!m || UPLOAD_NAME.test(m[2])) return src;
    return `${m[1]}pixel/${m[2]}.png`;
}

// À appeler dans un setup() : lit la préférence dans les props Inertia partagées.
export function usePixelArt() {
    const page = usePage();
    const enabled = () => !!page.props?.auth?.user?.pixel_art;
    return {
        isPixelArt: enabled,
        photoSrc: (src) => (src && enabled() ? toPixelSrc(src) : src),
    };
}
