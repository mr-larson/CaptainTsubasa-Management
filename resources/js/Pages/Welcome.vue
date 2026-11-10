<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';
import PixelDust from '@/Components/Welcome/PixelDust.vue';
import LedCounter from '@/Components/Welcome/LedCounter.vue';
import PixelSprite from '@/Components/Welcome/PixelSprite.vue';
import StardustCursor from '@/Components/Welcome/StardustCursor.vue';
import { vPixelReveal } from '@/Directives/pixelReveal';
import { vParallax } from '@/Directives/parallax';

const props = defineProps({
    canLogin:        { type: Boolean, default: false },
    canRegister:     { type: Boolean, default: false },
    teams:           { type: Array,   default: () => [] },
    players:         { type: Array,   default: () => [] },
    playerCount:     { type: Number,  default: null },
    // Captures réelles du jeu (public/images/welcome/{match,dashboard}.webp), null si absentes.
    screenshots:     { type: Object,  default: () => ({}) },
});

// Parallaxe du hero (poussière, sprite) : scroll exposé en variable CSS --hero-y,
// mis à jour une fois par frame et seulement tant que le hero est visible.
const hero = ref(null);
let heroRaf = 0;
const onHeroScroll = () => {
    if (heroRaf) return;
    heroRaf = requestAnimationFrame(() => {
        heroRaf = 0;
        const el = hero.value;
        if (el && window.scrollY < el.offsetTop + el.offsetHeight) el.style.setProperty('--hero-y', window.scrollY);
    });
};
onMounted(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    window.addEventListener('scroll', onHeroScroll, { passive: true });
    onHeroScroll();
});
onUnmounted(() => {
    window.removeEventListener('scroll', onHeroScroll);
    cancelAnimationFrame(heroRaf);
});

// Parallaxe sous le hero : profondeurs légèrement différentes pour donner du relief
// (positif = premier plan, monte plus vite que la page ; négatif = arrière-plan).
const STEP_DEPTH   = [0.025, 0, -0.025];
const MODE_DEPTH   = [0.02, -0.02];
const playerDepth  = (i) => (i % 2 ? 0.028 : -0.018);

const teamLogoUrl = (team) => (team?.logo_path ? `/storage/${team.logo_path}` : null);

// Accueil : un aperçu de l'univers, pas le catalogue complet.
const featuredTeams   = computed(() => props.teams.slice(0, 8));
const featuredPlayers = computed(() => props.players.filter(p => !!p.photo_path).slice(0, 6));

const POSITION_LABELS = { Goalkeeper: 'Gardien', Defender: 'Défenseur', Midfielder: 'Milieu', Forward: 'Attaquant' };
const positionLabel = (position) => POSITION_LABELS[position] ?? position;

// Boutons réutilisés sur la page. Zones tactiles d'au moins 44 px de haut.
const btnPrimary   = 'inline-flex min-h-[44px] items-center justify-center rounded-full bg-teal-500 px-6 text-sm font-bold text-slate-700 transition-colors hover:bg-teal-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 neon-btn';
const btnSecondary = 'inline-flex min-h-[44px] items-center justify-center rounded-full border border-white/15 bg-white/5 px-6 text-sm font-medium text-slate-100 transition-colors hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300 neon-btn neon-btn--ghost';

const steps = [
    { n: 1, title: 'Prépare ton équipe',      desc: "Recrute, entraîne et compose ton effectif en tenant compte du budget et de l'endurance de tes joueurs." },
    { n: 2, title: 'Décide pendant le match', desc: 'Choisis tes actions au tour par tour (passe, dribble, tir…) et place tes coups spéciaux au bon moment.' },
    { n: 3, title: 'Gère la suite',           desc: 'Adapte ton équipe aux résultats, aux blessures et aux objectifs de ta compétition.' },
];

// Exemple de tour : un vrai duel du jeu, chiffres compris. Règles réelles
// (Match/engine) : score = stat + dé d20 / 2, et +2 pour la défense si elle
// choisit le bon contre (sinon +2 pour l'attaque). Endurance pleine ici.
const GOOD_COUNTER_BONUS = 2;
const duel = {
    attacker: { name: 'Taro Misaki',  photo: '/images/Taro_Misaki.webp',  action: 'Passe',        stat: 24, roll: 7 },
    defender: { name: 'Ryo Ishizaki', photo: '/images/Ryo_Ishizaki.webp', action: 'Interception', stat: 21, roll: 14 },
};
const defenseOptions = [
    { label: 'Interception', icon: '✋', good: true },
    { label: 'Tacle',        icon: '⚔️', good: false },
    { label: 'Contre',       icon: '🧱', good: false },
];
const attackTotal  = duel.attacker.stat + duel.attacker.roll / 2;
const defenseTotal = duel.defender.stat + duel.defender.roll / 2 + GOOD_COUNTER_BONUS;
const metaDescription = "Golden Eleven, jeu de gestion de football au tour par tour sur navigateur, inspiré de Captain Tsubasa : recrute, entraîne ton équipe et choisis chaque action en match. Gratuit.";
const canonicalUrl = route('welcome');
const fmt = (n) => n.toLocaleString('fr-FR', { maximumFractionDigits: 1 });

const modes = [
    { title: 'Ligue',          desc: 'Gère un club sur une saison complète et réponds aux objectifs fixés par la direction du club.' },
    { title: 'Coupe du Monde', desc: 'Prends en main une sélection nationale : phase de poules, puis matchs à élimination directe.' },
];

// Réponses factuelles : à garder alignées avec le jeu réel.
const faq = [
    { q: 'Faut-il installer quelque chose ?',  a: "Non. Golden Eleven se joue dans le navigateur, il suffit de créer un compte." },
    { q: 'Peut-on jouer avec des amis ?',      a: "Oui, en local : plusieurs managers partagent une même partie sur le même appareil. Chacun contrôle son équipe et joue à son tour. Il n'y a pas de jeu en ligne." },
    { q: 'Comment se déroule un match ?',      a: "Au tour par tour : quand ton équipe a le ballon, tu choisis une action (passe, dribble, tir, coup spécial) ; en défense, tu choisis comment contrer. Les statistiques des joueurs, l'endurance et un jet de dés décident de l'issue de chaque duel." },
    { q: 'Ma progression est-elle sauvegardée ?', a: 'Oui. Tes parties sont enregistrées sur ton compte et tu les reprends depuis « Mes parties ».' },
    { q: 'Le jeu est-il gratuit ?',            a: 'La création de compte est gratuite.' },
    { q: 'Peut-on jouer sur téléphone ?',      a: "Le jeu est pensé d'abord pour l'ordinateur. L'affichage s'adapte au téléphone, mais le confort de jeu sur petit écran est encore en cours d'amélioration." },
    { q: 'Le jeu est-il terminé ?',            a: 'Non, Golden Eleven est en développement : des fonctionnalités évoluent encore et certains équilibrages peuvent changer.' },
    { q: 'Quel lien avec Captain Tsubasa ?',   a: "Golden Eleven est un projet indépendant et open source, inspiré de l'œuvre de Yōichi Takahashi." },
];
</script>

<template>
    <Head title="Jeu de gestion de football au tour par tour">
        <meta head-key="description" name="description" :content="metaDescription" />
        <link head-key="canonical" rel="canonical" :href="canonicalUrl" />
    </Head>

    <div class="relative isolate min-h-screen overflow-x-clip bg-slate-900 text-[15px] leading-relaxed text-slate-100">

        <!-- Poussière de pixels clairsemée derrière toute la page (le hero a la sienne, plus dense) -->
        <PixelDust fixed :count="10" :intensity="0.5" :seed="23" />
        <!-- Traînée de poussière d'étoiles derrière la souris (souris uniquement) -->
        <StardustCursor />

        <!-- Lien d'évitement : visible seulement au focus clavier -->
        <a href="#contenu" class="sr-only z-[60] rounded-full bg-teal-500 px-4 py-2 text-sm font-bold text-slate-900 focus:not-sr-only focus:fixed focus:left-4 focus:top-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-200">
            Aller au contenu
        </a>

        <!-- NAV : marque et action principale sur une seule ligne, même sur mobile -->
        <nav class="welcome-nav sticky top-0 z-50 flex items-center justify-between gap-3 bg-slate-900/90 px-4 py-3 backdrop-blur md:px-10 md:py-4">
            <Link :href="route('welcome')" class="flex items-baseline gap-1.5 text-[17px]">
                <span class="font-bold text-slate-100">Golden</span>
                <span class="neon-text text-teal-300">Eleven</span>
            </Link>
            <div class="flex items-center gap-2 sm:gap-4" v-if="canLogin">
                <Link v-if="$page.props.auth?.user" :href="route('mainMenu')" :class="btnPrimary">Menu principal</Link>
                <template v-else>
                    <Link :href="route('login')" class="inline-flex min-h-[44px] items-center px-2 text-sm text-slate-300 transition-colors hover:text-teal-300">Connexion</Link>
                    <Link v-if="canRegister" :href="route('register')" :class="btnPrimary" class="whitespace-nowrap !px-4">
                        <span class="sm:hidden">S'inscrire</span><span class="hidden sm:inline">Créer mon compte</span>
                    </Link>
                </template>
            </div>
        </nav>

        <main id="contenu" tabindex="-1" class="focus:outline-none">
        <!-- 1. COMPRENDRE EN QUELQUES SECONDES -->
        <!-- Avec une capture du jeu, elle prend autant de place que le texte (lisible) ; sinon le personnage reste en colonne étroite. -->
        <section ref="hero" class="relative isolate mx-auto grid max-w-[1280px] items-center gap-10 px-4 pb-10 pt-10 md:px-10 md:pb-16 md:pt-16"
                 :class="screenshots.match ? 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]' : 'md:grid-cols-[1fr_420px]'">
            <!-- Profondeur HD-2D : poussière de pixels lumineux en arrière-plan -->
            <PixelDust />

            <div>
                <div v-pixel-reveal class="mb-5 inline-flex items-center rounded-full border border-teal-300/25 bg-teal-500/15 px-3 py-1 text-xs font-semibold tracking-wide text-teal-300">
                    Version en développement
                </div>
                <h1 v-pixel-reveal class="mb-5 text-[clamp(2rem,4vw,3rem)] font-extrabold leading-tight text-slate-50">
                    Ton équipe. Tes décisions.<br>
                    <span class="neon-text text-teal-300">Un jeu de gestion de football au tour par tour.</span>
                </h1>
                <p v-pixel-reveal class="mb-6 max-w-[560px] text-base leading-7 text-slate-300">
                    Golden Eleven est un jeu sur navigateur inspiré de l'univers Captain Tsubasa.
                    Recrute tes joueurs, prépare ton équipe et choisis tes actions pendant les matchs.
                    Dispute une Ligue ou une Coupe du Monde, seul face à l'IA ou à plusieurs en local, à tour de rôle.
                </p>
                <div v-pixel-reveal class="mb-6 flex flex-wrap gap-3">
                    <Link v-if="canRegister && !$page.props.auth?.user" :href="route('register')" :class="[btnPrimary, 'neon-breathe']">Créer mon compte gratuit</Link>
                    <Link v-else-if="$page.props.auth?.user" :href="route('mainMenu')" :class="btnPrimary">Aller au menu principal</Link>
                    <a href="#exemple-tour" :class="btnSecondary">Voir comment se joue un match</a>
                </div>
                <div v-pixel-reveal class="mb-8 space-y-1 text-sm text-slate-400">
                    <p v-if="canRegister && !$page.props.auth?.user">Une fois ton compte créé, tu pourras lancer ta première partie.</p>
                    <p><span aria-hidden="true">💻 </span>Pour jouer dans les meilleures conditions, privilégie un ordinateur.</p>
                </div>
                <ul v-pixel-reveal class="flex flex-wrap gap-2 text-sm text-slate-200" aria-label="En bref">
                    <li class="rounded-full border border-white/10 bg-white/5 px-3 py-1">🌐 Sur navigateur</li>
                    <li class="rounded-full border border-white/10 bg-white/5 px-3 py-1">🎲 Matchs au tour par tour</li>
                    <li class="rounded-full border border-white/10 bg-white/5 px-3 py-1">🎮 Solo et multijoueur local</li>
                </ul>
            </div>

            <!-- Visuel : une vraie capture de match si elle existe, sinon le personnage (décoratif) -->
            <!-- Premier plan HD-2D : le personnage en sprite pixel art devant la capture (grand écran) -->
            <div v-if="screenshots.match" class="relative">
                <figure v-pixel-reveal class="neon-screen overflow-hidden rounded-2xl border border-white/10 bg-slate-700 shadow-2xl">
                    <img :src="screenshots.match"
                         alt="Écran de match : terrain, cartes d'action (tir, passe, dribble, centre) et détail d'un duel avec les jets de dés"
                         class="block w-full" width="1690" height="994" />
                    <figcaption class="px-4 py-3 text-sm text-slate-300 lg:pl-28">
                        Choisis ton action : tirer, passer, dribbler… Chaque duel se joue avec les stats des joueurs et un jet de dés.
                    </figcaption>
                </figure>
                <div class="hero-sprite pointer-events-none absolute -bottom-8 -left-16 z-10 hidden lg:block" aria-hidden="true">
                    <div v-pixel-reveal>
                        <PixelSprite src="/images/tsubas3.webp" :rows="70" :scale="4" class="hero-sprite-idle" />
                    </div>
                </div>
            </div>
            <div v-else class="hero-sprite hero-sprite--solo hidden justify-center md:flex" aria-hidden="true">
                <div v-pixel-reveal>
                    <PixelSprite src="/images/tsubas3.webp" :rows="96" :scale="4" class="hero-sprite-idle" />
                </div>
            </div>
        </section>

        <!-- 1 bis. UN TOUR EN EXEMPLE : passe adverse → choix de défense → duel -->
        <section id="exemple-tour" class="mx-auto max-w-[1200px] scroll-mt-20 led-divider px-4 py-12 md:px-10 md:py-16" aria-labelledby="exemple-tour-titre">
            <h2 id="exemple-tour-titre" v-pixel-reveal class="neon-title mb-2 text-2xl font-bold text-slate-50">Un tour de match, en exemple</h2>
            <p v-pixel-reveal class="mb-8 max-w-[680px] text-slate-300">
                Tu défends. L'adversaire a le ballon et tente une passe : à toi de choisir comment réagir.
            </p>

            <ol class="grid gap-4 lg:grid-cols-3">
                <!-- Étape 1 : l'action adverse -->
                <li v-pixel-reveal class="neon-card flex flex-col rounded-xl border border-white/10 bg-white/5 p-5">
                    <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-500 text-[11px] font-black text-slate-700 neon-dot">1</span>
                        L'adversaire attaque
                    </div>
                    <div class="flex items-center gap-3">
                        <img :src="duel.attacker.photo" alt="" width="56" height="56" loading="lazy" decoding="async"
                             class="h-14 w-14 shrink-0 rounded-full border border-orange-300/40 bg-slate-800 object-cover object-top" />
                        <div>
                            <div class="font-semibold text-slate-100">{{ duel.attacker.name }}</div>
                            <div class="text-sm text-orange-300">tente une passe <span aria-hidden="true">➡️</span></div>
                        </div>
                    </div>
                    <div class="mt-4 rounded-lg border border-orange-300/20 bg-orange-400/10 px-3 py-2 text-sm text-slate-300">
                        Sa stat de <strong class="text-slate-100">{{ duel.attacker.action }}</strong> : <span class="font-mono text-orange-200">{{ duel.attacker.stat }}</span>
                    </div>
                </li>

                <!-- Étape 2 : ton choix défensif -->
                <li v-pixel-reveal class="neon-card flex flex-col rounded-xl border border-white/10 bg-white/5 p-5">
                    <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-500 text-[11px] font-black text-slate-700 neon-dot">2</span>
                        Tu choisis ta défense
                    </div>
                    <ul class="grid grid-cols-3 gap-2" aria-label="Défenses possibles">
                        <li v-for="o in defenseOptions" :key="o.label"
                            class="rounded-lg border px-2 py-2.5 text-center text-xs"
                            :class="o.good ? 'border-teal-300/60 bg-teal-500/15 text-teal-100 shadow-[0_0_14px_rgb(45_212_191/0.25)]' : 'border-white/10 bg-white/5 text-slate-400'">
                            <div class="mb-1 text-lg" aria-hidden="true">{{ o.icon }}</div>
                            {{ o.label }}
                            <span v-if="o.good" class="sr-only">(ton choix)</span>
                        </li>
                    </ul>
                    <p class="mt-4 text-sm leading-6 text-slate-300">
                        <strong class="text-teal-200">Intercepter</strong> est le bon contre face à une passe :
                        <span class="text-teal-200">+{{ GOOD_COUNTER_BONUS }}</span> pour ta défense.
                        Un mauvais choix aurait donné ce bonus à l'attaque.
                    </p>
                </li>

                <!-- Étape 3 : le duel -->
                <li v-pixel-reveal class="neon-card neon-card--lit flex flex-col rounded-xl border border-teal-300/20 bg-white/5 p-5">
                    <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-500 text-[11px] font-black text-slate-700 neon-dot">3</span>
                        Le duel se joue aux dés
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="rounded-lg bg-slate-950/40 px-3 py-2">
                            <dt class="mb-1 flex justify-between text-slate-400">
                                <span>{{ duel.attacker.name }}</span><span class="font-mono text-slate-100">{{ fmt(attackTotal) }}</span>
                            </dt>
                            <dd class="font-mono text-xs text-slate-400">
                                {{ duel.attacker.stat }} + dé <span class="duel-die">{{ duel.attacker.roll }}</span> ÷ 2
                            </dd>
                        </div>
                        <div class="rounded-lg bg-slate-950/40 px-3 py-2">
                            <dt class="mb-1 flex justify-between text-slate-400">
                                <span>{{ duel.defender.name }} <span class="text-slate-500">(toi)</span></span><span class="font-mono text-teal-200">{{ fmt(defenseTotal) }}</span>
                            </dt>
                            <dd class="font-mono text-xs text-slate-400">
                                {{ duel.defender.stat }} + dé <span class="duel-die">{{ duel.defender.roll }}</span> ÷ 2 <span class="text-teal-300">+ {{ GOOD_COUNTER_BONUS }} bon contre</span>
                            </dd>
                        </div>
                    </dl>
                    <p class="mt-4 rounded-lg border border-teal-300/30 bg-teal-500/10 px-3 py-2 text-sm font-semibold text-teal-100">
                        <span aria-hidden="true">✅ </span>Passe interceptée : le ballon est à toi.
                    </p>
                </li>
            </ol>

            <p v-pixel-reveal class="mt-6 max-w-[720px] text-sm leading-6 text-slate-400">
                Les stats, l'endurance et le dé décident de chaque duel. Plus de détails dans le
                <Link :href="route('guide')" class="text-teal-300 underline hover:text-teal-200">guide « Comment jouer »</Link>.
            </p>
            <div v-if="canRegister && !$page.props.auth?.user" v-pixel-reveal class="mt-6">
                <Link :href="route('register')" :class="[btnPrimary, 'neon-breathe']">Créer mon compte et jouer mon premier match</Link>
            </div>
        </section>

        <!-- 2. DÉROULEMENT D'UNE PARTIE -->
        <section id="deroulement" class="mx-auto max-w-[1200px] scroll-mt-20 led-divider px-4 py-12 md:px-10 md:py-16">
            <h2 v-pixel-reveal class="neon-title mb-2 text-2xl font-bold text-slate-50">Comment se déroule une partie</h2>
            <p v-pixel-reveal class="mb-8 max-w-[640px] text-slate-300">Chaque semaine de jeu suit la même boucle : préparer, jouer, s'adapter.</p>
            <ol class="grid gap-4 md:grid-cols-3">
                <li v-for="(s, i) in steps" :key="s.n" v-pixel-reveal v-parallax="STEP_DEPTH[i % 3]" class="neon-card rounded-xl border border-white/10 bg-white/5 p-6">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-full bg-teal-500 text-sm font-black text-slate-700 neon-dot">{{ s.n }}</div>
                    <h3 class="mb-1.5 text-base font-semibold text-slate-50">{{ s.title }}</h3>
                    <p class="text-sm leading-6 text-slate-300">{{ s.desc }}</p>
                </li>
            </ol>

            <figure v-if="screenshots.dashboard" v-pixel-reveal v-parallax="{ y: -0.03, max: 26 }" class="neon-screen mt-8 overflow-hidden rounded-2xl border border-white/10 bg-slate-700 shadow-2xl">
                <img :src="screenshots.dashboard"
                     alt="Tableau de bord d'une partie : classement, bilan, budget, effectif et prochain adversaire"
                     class="block w-full" width="1692" height="880" loading="lazy" decoding="async" />
                <figcaption class="px-4 py-3 text-sm text-slate-300">
                    Entre deux matchs : classement, budget, état de l'effectif et prochain adversaire, sur un seul écran.
                </figcaption>
            </figure>
        </section>

        <!-- 3. CHOISIR SON EXPÉRIENCE -->
        <section class="mx-auto max-w-[1200px] led-divider px-4 py-12 md:px-10 md:py-16">
            <h2 v-pixel-reveal class="neon-title mb-8 text-2xl font-bold text-slate-50">Deux compétitions</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div v-for="(m, i) in modes" :key="m.title" v-pixel-reveal v-parallax="MODE_DEPTH[i % 2]" class="neon-card rounded-xl border border-white/10 bg-white/5 p-6">
                    <h3 class="mb-1.5 text-lg font-semibold text-teal-300">{{ m.title }}</h3>
                    <p class="text-sm leading-6 text-slate-300">{{ m.desc }}</p>
                </div>
            </div>
            <p v-pixel-reveal class="mt-6 max-w-[720px] text-sm leading-6 text-slate-300">
                Joue face à l'IA ou partage une partie en local avec plusieurs managers : chacun contrôle son équipe et joue à son tour.
            </p>
            <div v-if="canRegister && !$page.props.auth?.user" v-pixel-reveal class="mt-8">
                <Link :href="route('register')" :class="btnPrimary">Créer mon compte gratuit</Link>
            </div>
        </section>

        <!-- 4. RETROUVER L'UNIVERS (aperçu court) -->
        <section class="mx-auto max-w-[1200px] led-divider px-4 py-12 md:px-10 md:py-16" v-if="featuredTeams.length || featuredPlayers.length">
            <h2 v-pixel-reveal class="neon-title mb-2 text-2xl font-bold text-slate-50">L'univers Captain Tsubasa</h2>
            <!-- Tableau d'affichage façon stade -->
            <div v-pixel-reveal class="mb-6 mt-6 flex flex-wrap gap-3">
                <LedCounter :value="teams.length" label="Équipes" />
                <LedCounter :value="playerCount ?? players.length" label="Joueurs à recruter" />
            </div>
            <p v-pixel-reveal class="mb-8 text-slate-300">Quelques exemples :</p>

            <ul v-if="featuredTeams.length" v-parallax="{ x: -0.06, max: 26 }" class="mb-8 grid grid-cols-4 gap-3 sm:grid-cols-8" aria-label="Équipes">
                <li v-for="t in featuredTeams" :key="t.id" v-pixel-reveal class="flex flex-col items-center gap-1.5 text-center">
                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg bg-white">
                        <TeamLogoImage v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" alt="" loading="lazy" class="h-full w-full" />
                        <span v-else class="text-xl font-bold text-slate-700" aria-hidden="true">{{ t.name[0] }}</span>
                    </div>
                    <span class="text-xs font-medium text-slate-200">{{ t.name }}</span>
                </li>
            </ul>

            <ul v-if="featuredPlayers.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6" aria-label="Joueurs">
                <li v-for="(p, i) in featuredPlayers" :key="p.id" v-pixel-reveal v-parallax="playerDepth(i)" class="neon-card overflow-hidden rounded-xl border border-white/10 bg-white/5">
                    <PlayerPhoto :src="`/storage/${p.photo_path}`" :alt="`${p.firstname} ${p.lastname}`" class="block aspect-square w-full object-cover object-top" />
                    <div class="px-3 py-2">
                        <div class="text-sm font-semibold text-slate-100">{{ p.firstname }} {{ p.lastname }}</div>
                        <div class="text-xs text-teal-300">{{ positionLabel(p.position) }}</div>
                    </div>
                </li>
            </ul>
        </section>

        <!-- 5. QUESTIONS AVANT L'INSCRIPTION -->
        <section class="mx-auto max-w-[1200px] led-divider px-4 py-12 md:px-10 md:py-16">
            <h2 v-pixel-reveal class="neon-title mb-2 text-2xl font-bold text-slate-50">Questions fréquentes</h2>
            <p v-pixel-reveal class="mb-8 text-slate-300">
                Pour le détail des règles, lis le <Link :href="route('guide')" class="text-teal-300 underline hover:text-teal-200">guide « Comment jouer »</Link>.
            </p>
            <div class="grid gap-3 md:grid-cols-2">
                <details v-for="item in faq" :key="item.q" v-pixel-reveal class="neon-card group rounded-xl border border-white/10 bg-white/5 px-5 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold text-slate-100 [&::-webkit-details-marker]:hidden">
                        {{ item.q }}
                        <span class="text-teal-300 transition-transform group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-6 text-slate-300">{{ item.a }}</p>
                </details>
            </div>
        </section>

        <!-- 6. INVITATION FINALE : un peu plus de poussière pour finir comme on a commencé -->
        <section class="relative isolate mx-auto max-w-[1200px] led-divider px-4 py-12 md:px-10 md:py-16">
            <PixelDust :count="12" :intensity="0.8" :seed="5" />
            <div v-pixel-reveal v-parallax="0.025" class="neon-card neon-card--lit rounded-2xl border border-teal-300/20 bg-gradient-to-br from-teal-500/10 to-slate-900/80 px-6 py-8 text-center md:p-12">
                <h2 class="neon-title mb-3 text-[26px] font-bold text-slate-50">Prêt à commencer ?</h2>
                <p class="mb-8 text-[15px] text-slate-300">Crée ton compte, puis lance ta première partie depuis le menu principal.</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <Link v-if="canRegister && !$page.props.auth?.user" :href="route('register')" :class="btnPrimary">Créer mon compte gratuit</Link>
                    <Link v-if="canLogin && !$page.props.auth?.user" :href="route('login')" :class="btnSecondary">J'ai déjà un compte</Link>
                    <Link v-if="$page.props.auth?.user" :href="route('mainMenu')" :class="btnPrimary">Aller au menu principal</Link>
                </div>
            </div>
        </section>

        </main>

        <!-- PIED DE PAGE : liens pour les curieux et les développeurs -->
        <footer class="flex flex-col gap-3 border-t border-white/5 px-4 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between md:px-10">
            <span>Golden Eleven · Projet indépendant et open source, inspiré de l'œuvre de Yōichi Takahashi</span>
            <nav class="flex flex-wrap gap-4" aria-label="Liens du projet">
                <Link :href="route('guide')" class="hover:text-teal-300">Comment jouer</Link>
                <Link :href="route('privacy')" class="hover:text-teal-300">Confidentialité</Link>
                <a href="https://gautd8.notion.site/Captain-Tsubasa-Management-28c47313c8ca4fb5b0e3652491118849"
                   target="_blank" rel="noopener" class="hover:text-teal-300">Documentation du projet</a>
                <a href="https://github.com/mr-larson/CaptainTsubasa-Management" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1 hover:text-teal-300">
                    <i class="ti ti-brand-github" aria-hidden="true"></i> GitHub
                </a>
            </nav>
        </footer>
    </div>
</template>

<style scoped>
/*
 * Touche néon rétro-futuriste, volontairement discrète : la page reste sombre,
 * la lumière souligne les éléments interactifs et les séparations.
 * Les segments lumineux avancent par crans (steps) comme une rampe LED, en écho
 * à l'apparition pixelisée. Glows en box-/text-shadow statiques ; les seules
 * animations continues portent sur opacity/transform (compositeur).
 */
@property --neon-a {
    syntax: '<angle>';
    inherits: false;
    initial-value: 0deg;
}

/* Dé d20 de l'exemple de tour : petit carré façon LED. */
.duel-die {
    display: inline-flex;
    min-width: 1.6em;
    justify-content: center;
    padding: 0 0.25em;
    border: 1px solid rgb(94 234 212 / 0.45);
    border-radius: 3px;
    background: rgb(15 23 42 / 0.8);
    color: rgb(204 251 241);
    box-shadow: 0 0 8px rgb(45 212 191 / 0.25);
}

.neon-text {
    text-shadow: 0 0 6px rgb(94 234 212 / 0.35), 0 0 22px rgb(45 212 191 / 0.18);
}

.neon-title {
    text-shadow: 0 0 18px rgb(94 234 212 / 0.12);
}
/* « Allumage » du néon quand le titre finit de se dépixeliser. */
.neon-title[data-pxr='done'] {
    animation: neon-power-on 0.9s steps(1, end) both;
}
@keyframes neon-power-on {
    0%   { text-shadow: 0 0 18px rgb(94 234 212 / 0.12); }
    12%  { text-shadow: 0 0 6px rgb(94 234 212 / 0.55), 0 0 26px rgb(45 212 191 / 0.35); }
    24%  { text-shadow: none; }
    38%  { text-shadow: 0 0 6px rgb(94 234 212 / 0.5), 0 0 26px rgb(45 212 191 / 0.3); }
    50%  { text-shadow: 0 0 4px rgb(94 234 212 / 0.2); }
    64%  { text-shadow: 0 0 6px rgb(94 234 212 / 0.45), 0 0 30px rgb(45 212 191 / 0.28); }
    100% { text-shadow: 0 0 18px rgb(94 234 212 / 0.12); }
}

/* ---------- Boutons : halo + segment LED qui fait le tour au survol ---------- */
.neon-btn {
    position: relative;
    isolation: isolate;
}
.neon-btn::before,
.neon-btn::after,
.neon-card::before,
.neon-card::after {
    content: '';
    position: absolute;
    border-radius: inherit;
    pointer-events: none;
}
/* Halo : box-shadow figé, seule son opacité varie. */
.neon-btn::after {
    inset: 0;
    z-index: -1;
    box-shadow: 0 0 10px rgb(45 212 191 / 0.55), 0 0 32px rgb(45 212 191 / 0.3);
    opacity: 0.35;
    transition: opacity 0.3s ease;
}
.neon-btn--ghost::after {
    box-shadow: 0 0 8px rgb(45 212 191 / 0.35), inset 0 0 10px rgb(45 212 191 / 0.15);
    opacity: 0;
}
/* Anneau lumineux : un segment conique masqué en bordure, qui tourne par crans. */
.neon-btn::before,
.neon-card::before {
    inset: -1px;
    padding: 1.5px;
    background: conic-gradient(from var(--neon-a), transparent 0 62%, rgb(45 212 191 / 0.9) 78%, #e6fffb 84%, transparent 90%);
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
    mask: linear-gradient(#000 0 0) content-box exclude, linear-gradient(#000 0 0);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.neon-btn:hover::after,
.neon-btn:focus-visible::after {
    opacity: 1;
}
.neon-btn:hover::before,
.neon-btn:focus-visible::before {
    opacity: 1;
    animation: neon-chase 1.8s steps(36) infinite;
}
.neon-btn--ghost:hover,
.neon-btn--ghost:focus-visible {
    border-color: rgb(94 234 212 / 0.45);
    color: rgb(204 251 241);
}
/* Bouton plein : l'anneau est décollé d'1 px pour se détacher du fond teal. */
.neon-btn:not(.neon-btn--ghost)::before {
    inset: -3.5px;
    padding: 2px;
}
@keyframes neon-chase {
    to { --neon-a: 360deg; }
}

/* CTA principal : respiration lente du halo. */
.neon-breathe::after {
    animation: neon-breathe 3.6s ease-in-out infinite;
}
/* Au survol, le halo passe à pleine intensité (l'animation l'emporterait sinon). */
.neon-breathe:hover::after,
.neon-breathe:focus-visible::after {
    animation: none;
}
@keyframes neon-breathe {
    0%, 100% { opacity: 0.3; }
    50%      { opacity: 0.8; }
}

/* ---------- Cartes : bordure qui s'allume au survol ---------- */
.neon-card {
    position: relative;
}
.neon-card::before {
    background: conic-gradient(from var(--neon-a), transparent 0 70%, rgb(45 212 191 / 0.7) 84%, rgb(204 251 241 / 0.9) 88%, transparent 93%);
}
.neon-card::after {
    inset: 0;
    box-shadow: inset 0 0 0 1px rgb(94 234 212 / 0.28), inset 0 0 24px rgb(45 212 191 / 0.08);
    opacity: 0;
    transition: opacity 0.35s ease;
}
.neon-card:hover::after,
.neon-card:focus-within::after,
.neon-card--lit::after {
    opacity: 1;
}
.neon-card:hover::before {
    opacity: 1;
    animation: neon-chase 3.2s steps(48) infinite;
}
/* Bloc d'invitation finale : déjà « allumé », le segment tourne lentement en permanence. */
.neon-card--lit::before {
    opacity: 0.55;
    animation: neon-chase 9s steps(72) infinite;
}

/* ---------- Captures : rétroéclairage d'écran ---------- */
.neon-screen {
    box-shadow: 0 0 0 1px rgb(94 234 212 / 0.12), 0 0 40px rgb(45 212 191 / 0.1), 0 25px 50px -12px rgb(0 0 0 / 0.6);
}

.neon-dot {
    box-shadow: 0 0 10px rgb(45 212 191 / 0.55), 0 0 2px rgb(204 251 241 / 0.8);
}

/* ---------- Hero HD-2D : sprite au premier plan ---------- */
.hero-sprite {
    /* Premier plan : défile un peu plus vite que la page (parallaxe). */
    transform: translate3d(0, calc(var(--hero-y, 0) * -0.12px), 0);
}
/* Ombre portée au sol, sous les pieds. */
.hero-sprite::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: -6px;
    width: 70%;
    height: 18px;
    transform: translateX(-50%);
    border-radius: 50%;
    background: radial-gradient(closest-side, rgb(0 0 0 / 0.55), transparent);
    z-index: -1;
}
/* Liseré de lumière teal (rim light) + halo, et respiration « idle » à 2 frames. */
.hero-sprite-idle {
    filter: drop-shadow(1px 0 0 rgb(94 234 212 / 0.55)) drop-shadow(0 0 14px rgb(45 212 191 / 0.28));
    animation: sprite-idle 1.4s steps(1, end) infinite;
}
@keyframes sprite-idle {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-4px); }
}

/* ---------- Séparateurs : rampe LED avec un point lumineux qui la parcourt ---------- */
.led-divider {
    position: relative;
}
.led-divider::before,
.led-divider::after,
.welcome-nav::before,
.welcome-nav::after {
    content: '';
    position: absolute;
    top: 0;
    height: 1px;
    pointer-events: none;
}
.led-divider::before {
    left: 0;
    right: 0;
    background: linear-gradient(90deg, transparent, rgb(94 234 212 / 0.28) 30%, rgb(94 234 212 / 0.28) 70%, transparent);
    box-shadow: 0 0 8px rgb(45 212 191 / 0.25);
}
.welcome-nav::before {
    inset: auto 0 0;
    background: linear-gradient(90deg, transparent, rgb(94 234 212 / 0.24), transparent);
    box-shadow: 0 0 6px rgb(45 212 191 / 0.15);
}
.welcome-nav::after {
    inset: auto 0 0;
    background: linear-gradient(90deg, transparent, rgb(204 251 241 / 0.65), transparent) no-repeat;
    background-size: 90px 100%;
    animation: welcome-nav-sweep 2.8s ease-in-out 0.4s both;
}
@keyframes welcome-nav-sweep {
    from { background-position: left -90px center; }
    to { background-position: right -90px center; }
}
.led-divider::after {
    left: 0;
    width: 90px;
    background: linear-gradient(90deg, transparent, rgb(204 251 241 / 0.85), transparent);
    box-shadow: 0 0 10px rgb(45 212 191 / 0.6);
    opacity: 0;
    animation: led-sweep 9s steps(60) infinite;
}
.led-divider:nth-of-type(odd)::after {
    animation-delay: -4.5s;
}
@keyframes led-sweep {
    0%   { transform: translateX(-90px); opacity: 0; }
    4%   { opacity: 0.7; }
    36%  { opacity: 0.7; }
    40%  { transform: translateX(min(1200px, 100vw)); opacity: 0; }
    100% { transform: translateX(min(1200px, 100vw)); opacity: 0; }
}

@media (prefers-reduced-motion: reduce) {
    .neon-title[data-pxr='done'],
    .neon-breathe::after,
    .led-divider::after,
    .neon-btn:hover::before,
    .neon-btn:focus-visible::before,
    .neon-card:hover::before,
    .neon-card--lit::before,
    .hero-sprite-idle {
        animation: none;
    }
    .led-divider::after,
    .welcome-nav::after {
        display: none;
    }
}
</style>
