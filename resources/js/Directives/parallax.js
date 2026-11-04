/**
 * v-parallax : léger décalage d'un élément selon sa position dans l'écran.
 *
 *   v-parallax="0.06"            → vertical : >0 monte plus vite que la page
 *                                  (premier plan), <0 plus lentement (fond)
 *   v-parallax="{ x: -0.12 }"    → horizontal, glisse quand on scrolle
 *   v-parallax="{ y: 0.05, max: 40 }"
 *
 * Utilise la propriété CSS `translate` (et non `transform`) : elle se cumule
 * avec les transforms existants, notamment ceux de v-pixel-reveal, sans conflit.
 * Une seule boucle rAF, active uniquement au scroll et pour les éléments
 * proches de l'écran. `prefers-reduced-motion` : aucun effet.
 */

const items = new Set();
const visible = new Set();
let observer = null;
let raf = 0;

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function update() {
    raf = 0;
    const vh = window.innerHeight;
    // Lectures d'abord, écritures ensuite : pas de layout forcé en boucle.
    const writes = [];
    for (const it of visible) {
        const rect = it.el.getBoundingClientRect();
        // Centre de l'élément hors décalage déjà appliqué, relatif au centre de l'écran.
        const d = rect.top + rect.height / 2 - it.y - vh / 2;
        const clamp = (v) => Math.max(-it.max, Math.min(it.max, v));
        writes.push([it, clamp(-d * it.sx), clamp(-d * it.sy)]);
    }
    for (const [it, x, y] of writes) {
        it.x = x;
        it.y = y;
        it.el.style.translate = `${x.toFixed(1)}px ${y.toFixed(1)}px`;
    }
}

const schedule = () => { if (!raf && visible.size) raf = requestAnimationFrame(update); };

function parse(value) {
    if (typeof value === 'number') return { sx: 0, sy: value, max: 36 };
    return { sx: value?.x ?? 0, sy: value?.y ?? 0, max: value?.max ?? 36 };
}

export const vParallax = {
    mounted(el, binding) {
        if (reducedMotion() || !('IntersectionObserver' in window)) return;
        const it = { el, x: 0, y: 0, ...parse(binding.value) };
        el.__parallax = it;
        items.add(it);
        if (!observer) {
            observer = new IntersectionObserver((entries) => {
                for (const e of entries) {
                    const item = e.target.__parallax;
                    if (!item) continue;
                    if (e.isIntersecting) visible.add(item); else visible.delete(item);
                }
                schedule();
            }, { rootMargin: '25% 0px' });
            window.addEventListener('scroll', schedule, { passive: true });
            window.addEventListener('resize', schedule, { passive: true });
        }
        observer.observe(el);
    },
    updated(el, binding) {
        const it = el.__parallax;
        if (it) Object.assign(it, parse(binding.value));
    },
    unmounted(el) {
        const it = el.__parallax;
        if (!it) return;
        observer?.unobserve(el);
        items.delete(it);
        visible.delete(it);
        el.style.translate = '';
        delete el.__parallax;
        if (!items.size && observer) {
            observer.disconnect();
            observer = null;
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
            cancelAnimationFrame(raf);
            raf = 0;
        }
    },
};

export default vParallax;
