/**
 * v-pixel-reveal : apparition « HD-2D » d'un bloc au scroll.
 *
 * Le bloc apparaît pixelisé (gros pixels, palette réduite) puis se dépixelise
 * par paliers jusqu'au rendu net ; il se repixelise en quittant l'écran (par le
 * haut comme par le bas) et rejoue l'effet à son retour. Les paliers suivent la position de scroll
 * (comme les pages produit Apple) mais avancent au plus d'un cran toutes les
 * STEP_MS : à l'ouverture de la page ou après un saut d'ancre, l'effet se joue
 * donc en entier au lieu d'être sauté. Plus le scroll est rapide (dans les deux
 * sens), plus l'effet s'estompe : paliers raccourcis puis sautés, sans décalage.
 *
 * - Texte / blocs : `filter: url(#pxr-N)` vers un filtre SVG partagé. Le vrai
 *   texte reste dans le DOM (lecteurs d'écran, SEO, sélection), seul son rendu
 *   est filtré pendant l'animation.
 * - Images : variantes réduites calculées une fois sur un petit canvas, affichées
 *   avec `image-rendering: pixelated`, puis l'image d'origine est restaurée.
 *
 * Coût : le rendu ne change qu'à chaque palier (~6 fois par bloc) ; entre deux
 * paliers seuls opacity/transform transitionnent (compositeur, 60 fps).
 * `prefers-reduced-motion: reduce` : aucun effet, affichage net direct.
 *
 * Usage : v-pixel-reveal, ou v-pixel-reveal="{ delay: 120 }" (ms).
 */

// Taille des pixels (px CSS) pour chaque palier, du plus grossier au plus fin.
// Impaires pour que la dilatation donne des blocs exactement carrés.
const LEVELS = [21, 15, 11, 7, 5, 3];      // images
// Texte : grille plus fine, pour qu'une ligne devienne des lettres « en briques »
// et non une barre pleine (une cellule de 21 px avale une ligne de 15 px).
const TEXT_LEVELS = [13, 11, 9, 7, 5, 3];
// Ces tailles valent pour un texte de 22 px et plus ; en dessous, la grille est
// réduite au prorata de la taille de police (un paragraphe de 15 px plafonne à ~9 px).
const TEXT_REF_PX = 22;
const textCell = (level, k) => {
    const s = Math.max(3, Math.round(TEXT_LEVELS[level] * k));
    return s % 2 ? s : s - 1;   // tailles impaires : un filtre existe pour chacune de 3 à 13
};
const STEPS = LEVELS.length + 1;        // 0 = caché, 1..LEVELS = pixelisé, STEPS = net
const STEP_MS = 55;                     // cadence des paliers (rythme rétro ~18 i/s)
const STAGGER_MS = 65;                  // décalage auto entre blocs qui démarrent ensemble
const PALETTE_UNTIL = 3;                // images : palette réduite sur les 3 paliers les plus grossiers
const START = 1.0;                      // progression 0 quand le haut du bloc touche le bas de l'écran…
const END = 0.78;                       // …et 1 quand il atteint 78 % de la hauteur d'écran
const EXIT = 0.16;                      // en sortie par le haut : repixelisation sur les 16 % du haut
// Vitesse de scroll : plus on défile vite (dans un sens ou dans l'autre), plus
// l'effet s'estompe — paliers raccourcis puis sautés, plus de décalage entre
// blocs ni de fondu long. Un scroll lent garde l'effet complet.
const FAST_PX_PER_MS = 2.5;             // ~2 500 px/s : apparition quasi immédiate
const SPEED_DECAY_MS = 220;             // sans scroll, la vitesse retombe à 0 en ~0,2 s

const SVG_NS = 'http://www.w3.org/2000/svg';

const units = new Set();
const active = new Set();
let observer = null;
let rafId = 0;
let lastStart = 0;
let scrolled = false;                   // premier scroll de l'utilisateur
let scrollSpeed = 0;                    // px/ms, lissée
let lastScrollY = 0;
let lastScrollAt = 0;

const reducedMotion = () =>
    typeof window === 'undefined' || window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------------ */
/* Filtres SVG partagés (un par palier)                                */
/* ------------------------------------------------------------------ */

// Fondu et légère montée continus (compositeur) sous les paliers de pixels.
const CSS = `
[data-pxr]{transition:opacity .4s ease-out,transform .65s cubic-bezier(.16,1,.3,1)}
[data-pxr="hidden"]{opacity:0;transform:translate3d(0,20px,0)}
html[data-pxr-fast] [data-pxr]{transition-duration:.12s,.18s}
`;

function ensureStyle() {
    if (document.getElementById('pxr-style')) return;
    const style = document.createElement('style');
    style.id = 'pxr-style';
    style.textContent = CSS;
    document.head.appendChild(style);
}

function ensureFilters() {
    if (document.getElementById('pxr-filters')) return;
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.id = 'pxr-filters';
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('focusable', 'false');
    svg.style.cssText = 'position:absolute;width:0;height:0;overflow:hidden;pointer-events:none';

    // Alpha : la couverture moyenne de chaque bloc est amplifiée puis ramenée à
    // 4 niveaux (0 / 35 % / 70 % / plein). Les blocs pleins dessinent les lettres,
    // les intermédiaires les bords et les espaces : on devine la forme des mots
    // au lieu d'une barre uniforme. Couleur du texte conservée (pas de palette).
    const alphaBoost = '<feComponentTransfer><feFuncA type="linear" slope="2.4"/></feComponentTransfer>';
    const alphaSteps = '<feComponentTransfer><feFuncA type="discrete" tableValues="0 0.35 0.7 1"/></feComponentTransfer>';

    svg.innerHTML = `<defs>${TEXT_LEVELS.map((s) => {
        const c = Math.floor(s / 2);
        return `<filter id="pxr-${s}" x="0" y="0" width="1" height="1" color-interpolation-filters="sRGB">
            <feGaussianBlur in="SourceGraphic" stdDeviation="${(s * 0.32).toFixed(2)}" result="blur"/>
            <feFlood x="${c}" y="${c}" width="1" height="1" flood-color="#fff"/>
            <feComposite x="0" y="0" width="${s}" height="${s}"/>
            <feTile result="grid"/>
            <feComposite in="blur" in2="grid" operator="in"/>
            <feMorphology operator="dilate" radius="${c}"/>
            ${alphaBoost}${alphaSteps}
        </filter>`;
    }).join('')}</defs>`;
    document.body.appendChild(svg);
}

/* ------------------------------------------------------------------ */
/* Images : variantes pixelisées pré-calculées                         */
/* ------------------------------------------------------------------ */

function quantize(ctx, w, h) {
    const data = ctx.getImageData(0, 0, w, h);
    const px = data.data;
    for (let i = 0; i < px.length; i += 4) {
        px[i] = Math.round(px[i] / 64) * 64;
        px[i + 1] = Math.round(px[i + 1] / 64) * 64;
        px[i + 2] = Math.round(px[i + 2] / 64) * 64;
    }
    ctx.putImageData(data, 0, 0);
}

async function buildImageLevels(target) {
    const img = target.el;
    if (!img.complete) {
        await new Promise((resolve) => {
            img.addEventListener('load', resolve, { once: true });
            img.addEventListener('error', resolve, { once: true });
        });
    }
    if (!img.naturalWidth || !img.isConnected) return;

    const original = img.currentSrc || img.src;
    const fit = getComputedStyle(img).objectFit;
    const cw = img.clientWidth || img.naturalWidth;
    const ch = img.clientHeight || img.naturalHeight;
    // Échelle d'affichage réelle, pour que les pixels fassent `s` px CSS à l'écran.
    const scale = fit === 'cover'
        ? Math.max(cw / img.naturalWidth, ch / img.naturalHeight)
        : fit === 'contain' || fit === 'scale-down'
            ? Math.min(cw / img.naturalWidth, ch / img.naturalHeight)
            : cw / img.naturalWidth;

    // Décodage + réduction hors du thread principal (sinon une capture 1700 px
    // bloque ~300 ms au premier drawImage). On part d'une version déjà à la
    // taille du palier le plus fin, les autres en sont dérivés.
    let source = img;
    if ('createImageBitmap' in window) {
        try {
            source = await createImageBitmap(img, {
                resizeWidth: Math.max(2, Math.round((img.naturalWidth * scale) / 2)),
                resizeHeight: Math.max(2, Math.round((img.naturalHeight * scale) / 2)),
                resizeQuality: 'high',
            });
        } catch { source = img; }
    }

    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const urls = [];
    for (let i = 0; i < LEVELS.length; i++) {
        // Toujours au moins 3 blocs sur le petit côté (logos 48 px).
        const s = Math.min(LEVELS[i], Math.max(2, Math.min(cw, ch) / 3));
        const w = Math.max(2, Math.round((img.naturalWidth * scale) / s));
        const h = Math.max(2, Math.round((img.naturalHeight * scale) / s));
        canvas.width = w;
        canvas.height = h;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(source, 0, 0, w, h);
        if (i < PALETTE_UNTIL) quantize(ctx, w, h);
        const blob = await new Promise((r) => canvas.toBlob(r, 'image/png'));
        if (!blob) return;
        urls.push(URL.createObjectURL(blob));
    }
    if (source !== img) source.close();
    // Pré-décodage : les échanges de src pendant l'animation sont instantanés.
    await Promise.all(urls.map((u) => { const t = new Image(); t.src = u; return t.decode().catch(() => {}); }));
    target.original = original;
    target.urls = urls;
}

/* ------------------------------------------------------------------ */
/* Application d'un palier                                             */
/* ------------------------------------------------------------------ */

function applyStep(unit, step) {
    unit.step = step;
    const el = unit.el;
    el.dataset.pxr = step === 0 ? 'hidden' : step >= STEPS ? 'done' : 'pixel';
    const level = step >= 1 && step < STEPS ? step - 1 : -1;

    for (const t of unit.targets) {
        if (t.type === 'text') {
            t.el.style.filter = level >= 0 ? `url(#pxr-${textCell(level, t.k)})` : '';
        } else if (t.urls) {
            if (level >= 0) {
                t.el.style.imageRendering = 'pixelated';
                t.el.src = t.urls[level];
            } else if (t.el.src !== t.original) {
                t.el.src = t.original;
                t.el.style.imageRendering = '';
            }
        }
    }
}

// Démontage : rend les éléments dans leur état d'origine et libère les blobs.
function dispose(unit) {
    active.delete(unit);
    observer?.unobserve(unit.el);
    unit.el.removeEventListener('focusin', unit.onFocusIn);
    unit.el.removeEventListener('focusout', unit.onFocusOut);
    delete unit.el.dataset.pxr;
    for (const t of unit.targets) {
        t.el.style.filter = '';
        if (t.type === 'image') {
            t.el.style.imageRendering = '';
            if (t.original && t.el.src !== t.original) t.el.src = t.original;
            t.urls?.forEach((u) => URL.revokeObjectURL(u));
            t.urls = null;
        }
    }
}

/* ------------------------------------------------------------------ */
/* Boucle : uniquement tant qu'un bloc visible change de palier        */
/* ------------------------------------------------------------------ */

// Progression 0 → 1 → 0 : le bloc se dépixelise en entrant par le bas de
// l'écran et se repixelise en sortant par le haut (et inversement en remontant).
function progressOf(unit, vh, maxScroll) {
    if (unit.focused) return 1;
    const { top, bottom } = unit.el.getBoundingClientRect();
    const y = window.scrollY;

    // Entrée par le bas. En bas de page, le bloc ne peut pas monter jusqu'à
    // END : la fin de course devient sa position au scroll maximum.
    const start = vh * START;
    const end = Math.max(vh * END, top - (maxScroll - y));
    const enter = end >= start - 1
        ? (top < start ? 1 : 0)
        : (start - top) / (start - end);

    // Sortie par le haut, en miroir. En haut de page, même correction avec
    // la position du bloc à scroll 0.
    const exitEnd = Math.min(vh * EXIT, bottom + y);
    const exit = exitEnd <= 1 ? (bottom > 0 ? 1 : 0) : bottom / exitEnd;

    // Visible à l'ouverture : l'entrée est considérée comme faite (pas de bloc
    // figé à mi-pixelisation en bas d'écran tant qu'on n'a pas scrollé).
    return Math.min(1, Math.max(0, unit.full ? exit : Math.min(enter, exit)));
}

/**
 * Rythme de l'effet selon la vitesse de scroll `fast` (0 = lent, 1 = très rapide) :
 * délai entre paliers, décalage entre blocs, et nombre de paliers franchis d'un coup.
 */
export function paceForSpeed(fast) {
    const f = Math.min(1, Math.max(0, fast));
    return {
        stepMs:  STEP_MS * (1 - f),
        stagger: STAGGER_MS * (1 - f),
        stride:  1 + Math.round(f * (STEPS - 1)),
    };
}

// 0 = scroll lent ou arrêté (effet complet) … 1 = scroll très rapide (effet estompé).
function speedFactor(now) {
    const decay = Math.max(0, 1 - (now - lastScrollAt) / SPEED_DECAY_MS);
    return Math.min(1, (scrollSpeed * decay) / FAST_PX_PER_MS);
}

function tick(now) {
    rafId = 0;
    const vh = window.innerHeight;
    const maxScroll = document.documentElement.scrollHeight - vh;
    const fast = speedFactor(now);
    const { stepMs, stagger, stride } = paceForSpeed(fast);
    if (fast >= 0.5) document.documentElement.dataset.pxrFast = '';
    else delete document.documentElement.dataset.pxrFast;
    let busy = false;
    for (const unit of active) {
        if (!unit.ready) { busy = true; continue; }
        const target = Math.round(progressOf(unit, vh, maxScroll) * STEPS);
        if (target === unit.step) { unit.startAt = 0; continue; }
        busy = true;

        if (unit.step === 0) {
            // Démarrage : décalage automatique entre blocs qui entrent ensemble.
            if (!unit.startAt) {
                unit.startAt = fast >= 0.5
                    ? now
                    : Math.max(now + unit.delay * (1 - fast), lastStart + stagger);
                lastStart = unit.startAt;
            }
            if (now < unit.startAt) continue;
        }
        if (now - unit.lastStep < stepMs) continue;
        unit.lastStep = now;
        applyStep(unit, target > unit.step
            ? Math.min(target, unit.step + stride)
            : Math.max(target, unit.step - stride));
    }
    // Rien ne bouge : la boucle s'arrête, le prochain scroll/resize la relance.
    if (busy) rafId = requestAnimationFrame(tick);
}

const schedule = () => { if (!rafId && active.size) rafId = requestAnimationFrame(tick); };
const onScroll = () => {
    scrolled = true;
    const now = performance.now();
    const y = window.scrollY;
    if (lastScrollAt) {
        const v = Math.abs(y - lastScrollY) / Math.max(1, now - lastScrollAt);
        scrollSpeed = scrollSpeed * 0.6 + v * 0.4;
    }
    lastScrollY = y;
    lastScrollAt = now;
    schedule();
};

function onIntersect(entries) {
    for (const entry of entries) {
        const unit = entry.target.__pxr;
        if (!unit) continue;
        if (entry.isIntersecting) {
            if (!scrolled) unit.full = true;
            active.add(unit);
        } else {
            // Hors écran : retour à l'état initial, l'effet se rejouera au retour.
            active.delete(unit);
            unit.full = false;
            applyStep(unit, 0);
            unit.startAt = 0;
        }
    }
    schedule();
}

/* ------------------------------------------------------------------ */
/* Directive                                                           */
/* ------------------------------------------------------------------ */

// Les images sont pixelisées par canvas, le reste par filtre SVG : on descend
// dans l'arbre seulement là où il y a des images à séparer du texte.
function collectTargets(el, out = []) {
    if (el.tagName === 'IMG') out.push({ type: 'image', el });
    else if (!el.querySelector('img')) {
        const fontSize = parseFloat(getComputedStyle(el).fontSize) || 16;
        out.push({ type: 'text', el, k: Math.min(1, fontSize / TEXT_REF_PX) });
    }
    else for (const child of el.children) collectTargets(child, out);
    return out;
}

export const vPixelReveal = {
    beforeMount(el) {
        if (reducedMotion()) return;
        ensureStyle();
        el.dataset.pxr = 'hidden';
    },
    mounted(el, binding) {
        if (reducedMotion() || !('IntersectionObserver' in window)) {
            delete el.dataset.pxr;
            return;
        }
        ensureFilters();
        const unit = {
            el,
            step: 0,
            delay: binding.value?.delay ?? 0,
            lastStep: 0,
            startAt: 0,
            full: false,
            focused: false,
            ready: false,
            targets: collectTargets(el),
        };
        // Navigation clavier : un élément focalisé doit être lisible immédiatement.
        unit.onFocusIn = () => { unit.focused = true; applyStep(unit, STEPS); };
        unit.onFocusOut = (e) => { if (!el.contains(e.relatedTarget)) { unit.focused = false; schedule(); } };
        el.addEventListener('focusin', unit.onFocusIn);
        el.addEventListener('focusout', unit.onFocusOut);
        el.__pxr = unit;
        units.add(unit);


        Promise.all(unit.targets.filter((t) => t.type === 'image').map(buildImageLevels))
            .catch(() => {})
            .finally(() => { unit.ready = true; schedule(); });

        if (!observer) {
            observer = new IntersectionObserver(onIntersect);
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', schedule, { passive: true });
        }
        observer.observe(el);
    },
    unmounted(el) {
        const unit = el.__pxr;
        if (!unit) return;
        dispose(unit);
        units.delete(unit);
        delete el.__pxr;
        if (!units.size && observer) {
            observer.disconnect();
            observer = null;
            scrolled = false;
            scrollSpeed = 0;
            lastScrollAt = 0;
            delete document.documentElement.dataset.pxrFast;
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', schedule);
            cancelAnimationFrame(rafId);
            rafId = 0;
        }
    },
};

export default vPixelReveal;
