<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';

const props = defineProps({
    canLogin:        { type: Boolean, default: false },
    canRegister:     { type: Boolean, default: false },
    teams:           { type: Array,   default: () => [] },
    players:         { type: Array,   default: () => [] },
    playerCount:     { type: Number,  default: null },
    // Captures réelles du jeu (public/images/welcome/{match,dashboard}.webp), null si absentes.
    screenshots:     { type: Object,  default: () => ({}) },
});

const teamLogoUrl = (team) => (team?.logo_path ? `/storage/${team.logo_path}` : null);

// Accueil : un aperçu de l'univers, pas le catalogue complet.
const featuredTeams   = computed(() => props.teams.slice(0, 8));
const featuredPlayers = computed(() => props.players.filter(p => !!p.photo_path).slice(0, 6));

const POSITION_LABELS = { Goalkeeper: 'Gardien', Defender: 'Défenseur', Midfielder: 'Milieu', Forward: 'Attaquant' };
const positionLabel = (position) => POSITION_LABELS[position] ?? position;

// Boutons réutilisés sur la page. Zones tactiles d'au moins 44 px de haut.
const btnPrimary   = 'inline-flex min-h-[44px] items-center justify-center rounded-full bg-teal-500 px-6 text-sm font-bold text-slate-900 transition-colors hover:bg-teal-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900';
const btnSecondary = 'inline-flex min-h-[44px] items-center justify-center rounded-full border border-white/15 bg-white/5 px-6 text-sm font-medium text-slate-100 transition-colors hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300';

const steps = [
    { n: 1, title: 'Prépare ton équipe',      desc: "Recrute, entraîne et compose ton effectif en tenant compte du budget et de l'endurance de tes joueurs." },
    { n: 2, title: 'Décide pendant le match', desc: 'Choisis tes actions au tour par tour (passe, dribble, tir…) et place tes coups spéciaux au bon moment.' },
    { n: 3, title: 'Gère la suite',           desc: 'Adapte ton équipe aux résultats, aux blessures et aux objectifs de ta compétition.' },
];

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
    <Head title="Golden Eleven — jeu de gestion de football au tour par tour" />

    <div class="min-h-screen bg-slate-900 text-[15px] leading-relaxed text-slate-100">

        <!-- NAV : marque et action principale sur une seule ligne, même sur mobile -->
        <nav class="sticky top-0 z-50 flex items-center justify-between gap-3 border-b border-white/5 bg-slate-900/90 px-4 py-3 backdrop-blur md:px-10 md:py-4">
            <Link :href="route('welcome')" class="flex items-baseline gap-1.5 text-[17px]">
                <span class="font-bold text-slate-100">Golden</span>
                <span class="text-teal-300">Eleven</span>
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

        <!-- 1. COMPRENDRE EN QUELQUES SECONDES -->
        <!-- Avec une capture du jeu, elle prend autant de place que le texte (lisible) ; sinon le personnage reste en colonne étroite. -->
        <section class="mx-auto grid max-w-[1280px] items-center gap-10 px-4 pb-10 pt-10 md:px-10 md:pb-16 md:pt-16"
                 :class="screenshots.match ? 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]' : 'md:grid-cols-[1fr_420px]'">
            <div>
                <div class="mb-5 inline-flex items-center rounded-full border border-teal-300/25 bg-teal-500/15 px-3 py-1 text-xs font-semibold tracking-wide text-teal-300">
                    Version en développement
                </div>
                <h1 class="mb-5 text-[clamp(2rem,4vw,3rem)] font-extrabold leading-tight text-slate-50">
                    Ton équipe. Tes décisions.<br>
                    <span class="text-teal-300">Un jeu de gestion de football au tour par tour.</span>
                </h1>
                <p class="mb-6 max-w-[560px] text-base leading-7 text-slate-300">
                    Golden Eleven est un jeu sur navigateur inspiré de l'univers Captain Tsubasa.
                    Recrute tes joueurs, prépare ton équipe et choisis tes actions pendant les matchs.
                    Dispute une Ligue ou une Coupe du Monde, seul face à l'IA ou à plusieurs en local, à tour de rôle.
                </p>
                <div class="mb-6 flex flex-wrap gap-3">
                    <Link v-if="canRegister && !$page.props.auth?.user" :href="route('register')" :class="btnPrimary">Créer mon compte gratuit</Link>
                    <Link v-else-if="$page.props.auth?.user" :href="route('mainMenu')" :class="btnPrimary">Aller au menu principal</Link>
                    <a href="#deroulement" :class="btnSecondary">Voir comment se joue un match</a>
                </div>
                <p v-if="canRegister && !$page.props.auth?.user" class="mb-8 text-sm text-slate-400">
                    Une fois ton compte créé, tu pourras lancer ta première partie.
                </p>
                <ul class="flex flex-wrap gap-2 text-sm text-slate-200" aria-label="En bref">
                    <li class="rounded-full border border-white/10 bg-white/5 px-3 py-1">🌐 Sur navigateur</li>
                    <li class="rounded-full border border-white/10 bg-white/5 px-3 py-1">🎲 Matchs au tour par tour</li>
                    <li class="rounded-full border border-white/10 bg-white/5 px-3 py-1">🎮 Solo et multijoueur local</li>
                </ul>
            </div>

            <!-- Visuel : une vraie capture de match si elle existe, sinon le personnage (décoratif) -->
            <figure v-if="screenshots.match" class="overflow-hidden rounded-2xl border border-white/10 bg-slate-800 shadow-2xl">
                <img :src="screenshots.match"
                     alt="Écran de match : terrain, cartes d'action (tir, passe, dribble, centre) et détail d'un duel avec les jets de dés"
                     class="block w-full" width="1690" height="994" />
                <figcaption class="px-4 py-3 text-sm text-slate-300">
                    Choisis ton action : tirer, passer, dribbler… Chaque duel se joue avec les stats des joueurs et un jet de dés.
                </figcaption>
            </figure>
            <div v-else class="hidden justify-center md:flex" aria-hidden="true">
                <img src="/images/tsubas3.webp" alt="" class="max-h-[420px] object-contain drop-shadow-2xl" />
            </div>
        </section>

        <!-- 2. DÉROULEMENT D'UNE PARTIE -->
        <section id="deroulement" class="mx-auto max-w-[1200px] scroll-mt-20 border-t border-white/5 px-4 py-12 md:px-10 md:py-16">
            <h2 class="mb-2 text-2xl font-bold text-slate-50">Comment se déroule une partie</h2>
            <p class="mb-8 max-w-[640px] text-slate-300">Chaque semaine de jeu suit la même boucle : préparer, jouer, s'adapter.</p>
            <ol class="grid gap-4 md:grid-cols-3">
                <li v-for="s in steps" :key="s.n" class="rounded-xl border border-white/10 bg-white/5 p-6">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-full bg-teal-500 text-sm font-black text-slate-900">{{ s.n }}</div>
                    <h3 class="mb-1.5 text-base font-semibold text-slate-50">{{ s.title }}</h3>
                    <p class="text-sm leading-6 text-slate-300">{{ s.desc }}</p>
                </li>
            </ol>

            <figure v-if="screenshots.dashboard" class="mt-8 overflow-hidden rounded-2xl border border-white/10 bg-slate-800 shadow-2xl">
                <img :src="screenshots.dashboard"
                     alt="Tableau de bord d'une partie : classement, bilan, budget, effectif et prochain adversaire"
                     class="block w-full" width="1692" height="880" loading="lazy" decoding="async" />
                <figcaption class="px-4 py-3 text-sm text-slate-300">
                    Entre deux matchs : classement, budget, état de l'effectif et prochain adversaire, sur un seul écran.
                </figcaption>
            </figure>
        </section>

        <!-- 3. CHOISIR SON EXPÉRIENCE -->
        <section class="mx-auto max-w-[1200px] border-t border-white/5 px-4 py-12 md:px-10 md:py-16">
            <h2 class="mb-8 text-2xl font-bold text-slate-50">Deux compétitions</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div v-for="m in modes" :key="m.title" class="rounded-xl border border-white/10 bg-white/5 p-6">
                    <h3 class="mb-1.5 text-lg font-semibold text-teal-300">{{ m.title }}</h3>
                    <p class="text-sm leading-6 text-slate-300">{{ m.desc }}</p>
                </div>
            </div>
            <p class="mt-6 max-w-[720px] text-sm leading-6 text-slate-300">
                Joue face à l'IA ou partage une partie en local avec plusieurs managers : chacun contrôle son équipe et joue à son tour.
            </p>
            <div v-if="canRegister && !$page.props.auth?.user" class="mt-8">
                <Link :href="route('register')" :class="btnPrimary">Créer mon compte gratuit</Link>
            </div>
        </section>

        <!-- 4. RETROUVER L'UNIVERS (aperçu court) -->
        <section class="mx-auto max-w-[1200px] border-t border-white/5 px-4 py-12 md:px-10 md:py-16" v-if="featuredTeams.length || featuredPlayers.length">
            <h2 class="mb-2 text-2xl font-bold text-slate-50">L'univers Captain Tsubasa</h2>
            <p class="mb-8 text-slate-300">
                {{ teams.length }} équipes et {{ playerCount ?? players.length }} joueurs à recruter. Quelques exemples :
            </p>

            <ul v-if="featuredTeams.length" class="mb-8 grid grid-cols-4 gap-3 sm:grid-cols-8" aria-label="Équipes">
                <li v-for="t in featuredTeams" :key="t.id" class="flex flex-col items-center gap-1.5 text-center">
                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg bg-white">
                        <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" alt="" loading="lazy" class="h-full w-full object-contain" />
                        <span v-else class="text-xl font-bold text-slate-700" aria-hidden="true">{{ t.name[0] }}</span>
                    </div>
                    <span class="text-xs font-medium text-slate-200">{{ t.name }}</span>
                </li>
            </ul>

            <ul v-if="featuredPlayers.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6" aria-label="Joueurs">
                <li v-for="p in featuredPlayers" :key="p.id" class="overflow-hidden rounded-xl border border-white/10 bg-white/5">
                    <PlayerPhoto :src="`/storage/${p.photo_path}`" :alt="`${p.firstname} ${p.lastname}`" class="block aspect-square w-full object-cover object-top" />
                    <div class="px-3 py-2">
                        <div class="text-sm font-semibold text-slate-100">{{ p.firstname }} {{ p.lastname }}</div>
                        <div class="text-xs text-teal-300">{{ positionLabel(p.position) }}</div>
                    </div>
                </li>
            </ul>
        </section>

        <!-- 5. QUESTIONS AVANT L'INSCRIPTION -->
        <section class="mx-auto max-w-[1200px] border-t border-white/5 px-4 py-12 md:px-10 md:py-16">
            <h2 class="mb-8 text-2xl font-bold text-slate-50">Questions fréquentes</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <details v-for="item in faq" :key="item.q" class="group rounded-xl border border-white/10 bg-white/5 px-5 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold text-slate-100 [&::-webkit-details-marker]:hidden">
                        {{ item.q }}
                        <span class="text-teal-300 transition-transform group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-6 text-slate-300">{{ item.a }}</p>
                </details>
            </div>
        </section>

        <!-- 6. INVITATION FINALE -->
        <section class="mx-auto max-w-[1200px] border-t border-white/5 px-4 py-12 md:px-10 md:py-16">
            <div class="rounded-2xl border border-teal-300/20 bg-gradient-to-br from-teal-500/10 to-slate-900/80 px-6 py-8 text-center md:p-12">
                <h2 class="mb-3 text-[26px] font-bold text-slate-50">Prêt à commencer ?</h2>
                <p class="mb-8 text-[15px] text-slate-300">Crée ton compte, puis lance ta première partie depuis le menu principal.</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <Link v-if="canRegister && !$page.props.auth?.user" :href="route('register')" :class="btnPrimary">Créer mon compte gratuit</Link>
                    <Link v-if="canLogin && !$page.props.auth?.user" :href="route('login')" :class="btnSecondary">J'ai déjà un compte</Link>
                    <Link v-if="$page.props.auth?.user" :href="route('mainMenu')" :class="btnPrimary">Aller au menu principal</Link>
                </div>
            </div>
        </section>

        <!-- PIED DE PAGE : liens pour les curieux et les développeurs -->
        <footer class="flex flex-col gap-3 border-t border-white/5 px-4 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between md:px-10">
            <span>Golden Eleven · Projet indépendant et open source, inspiré de l'œuvre de Yōichi Takahashi</span>
            <nav class="flex flex-wrap gap-4" aria-label="Liens du projet">
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
