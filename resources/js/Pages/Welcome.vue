<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';

const { canLogin, canRegister, teams, players } = defineProps({
    canLogin:    { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
    teams:       { type: Array,   default: () => [] },
    players:     { type: Array,   default: () => [] },
    playerCount: { type: Number,  default: null },
});

const teamLogoUrl = (team) => {
    if (!team?.logo_path) return null;
    return `/storage/${team.logo_path}`;
};

const playersWithPhoto = computed(() => players.filter(p => !!p.photo_path));

const overallOf = (p) => {
    if (!p) return 0;
    const keys = ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle'];
    const vals = keys.map(k => Number(p[k] ?? p.stats?.[k] ?? 0)).filter(v => v > 0);
    return vals.length ? Math.round(vals.reduce((a,b) => a+b,0) / vals.length) : 0;
};

// Boutons réutilisés dans le hero et l'appel à l'action final.
const btnPrimary   = 'inline-block rounded-full bg-teal-500 px-6 py-2.5 text-sm font-bold text-slate-900 transition-colors hover:bg-teal-400';
const btnSecondary = 'inline-block rounded-full border border-white/10 bg-white/5 px-6 py-2.5 text-sm font-medium text-slate-200 transition-colors hover:bg-white/10';
const btnGhost     = 'inline-flex items-center gap-1.5 rounded-full border border-white/10 px-5 py-2.5 text-sm text-slate-400 transition-colors hover:text-slate-200';

const features = [
    { icon: 'ti-trophy',          title: 'Championnat complet',      desc: 'Saisons de 28+ journées avec calendrier aller-retour équilibré et classement en direct.' },
    { icon: 'ti-sword',           title: 'Matchs tour par tour',     desc: 'Passes, dribbles, tirs et tirs spéciaux, avec une IA offensive et défensive.' },
    { icon: 'ti-world',           title: 'Mode Coupe du Monde',      desc: 'Compose une sélection nationale et vise le titre : phase de poules puis élimination directe.' },
    { icon: 'ti-users',           title: 'Multi-manager hot-seat',   desc: 'Plusieurs managers humains sur une même partie, chacun son équipe, à tour de rôle.' },
    { icon: 'ti-cards',           title: 'Cartes bonus & malus',     desc: 'Paris, défis de match et coups bas pour saboter l\'adversaire de ta prochaine rencontre.' },
    { icon: 'ti-target-arrow',    title: 'Carrière & objectifs',     desc: 'Mandat du board et jauge de confiance : atteins tes objectifs… ou fais-toi licencier.' },
    { icon: 'ti-run',             title: 'Gestion de l\'effectif',   desc: 'Titulaires, remplaçants, formations tactiques, numéros de maillot et entraînement.' },
    { icon: 'ti-arrows-exchange', title: 'Transferts & finances',    desc: 'Marché des agents libres, recrutement IA, résiliations de contrat et gestion du budget.' },
    { icon: 'ti-heart-rate',      title: 'Fatigue & blessures',      desc: 'Stamina, blessures d\'épuisement, cartons jaunes et rouges.' },
];
</script>

<template>
    <Head title="Golden Eleven" />

    <div class="min-h-screen bg-slate-900 text-[15px] leading-relaxed text-slate-100">

        <!-- NAV -->
        <nav class="sticky top-0 z-50 flex flex-wrap items-center justify-between gap-3 border-b border-white/5 bg-slate-900/90 px-6 py-4 backdrop-blur md:px-10 md:py-5">
            <div class="flex items-baseline gap-1.5 text-[17px]">
                <span class="font-bold text-slate-100">Golden</span>
                <span class="text-teal-300">Eleven</span>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-5" v-if="canLogin">
                <Link v-if="$page.props.auth?.user" :href="route('mainMenu')" class="text-sm text-slate-300 transition-colors hover:text-teal-300">Menu principal</Link>
                <template v-else>
                    <Link :href="route('login')" class="text-sm text-slate-300 transition-colors hover:text-teal-300">Connexion</Link>
                    <Link v-if="canRegister" :href="route('register')"
                          class="rounded-full bg-teal-500 px-4 py-1.5 text-[13px] font-semibold text-slate-900 transition-colors hover:bg-teal-400">Jouer gratuitement</Link>
                </template>
            </div>
        </nav>

        <!-- HERO -->
        <section class="mx-auto grid max-w-[1200px] items-center gap-12 px-6 pb-8 pt-12 md:grid-cols-[1fr_420px] md:px-10 md:pb-16 md:pt-20">
            <div>
                <div class="mb-5 inline-flex items-center rounded-full border border-teal-300/25 bg-teal-500/15 px-3 py-1 text-xs font-semibold tracking-wide text-teal-300">
                    Saison 1 · En développement
                </div>
                <h1 class="mb-5 text-[clamp(2.2rem,4vw,3.2rem)] font-extrabold leading-tight text-slate-50">Deviens le meilleur<br>manager du Japon</h1>
                <p class="mb-8 max-w-[520px] text-base leading-7 text-slate-400">
                    <strong>Golden Eleven</strong> est un jeu de gestion football tour par tour inspiré de l'univers Captain Tsubasa.
                    Construis ton club, dispute championnats et Coupe du Monde, joue à plusieurs et affronte l'IA.
                </p>
                <div class="mb-10 flex flex-wrap gap-3">
                    <Link v-if="canRegister" :href="route('register')" :class="btnPrimary">Créer une partie</Link>
                    <a href="https://gautd8.notion.site/Captain-Tsubasa-Management-28c47313c8ca4fb5b0e3652491118849"
                       target="_blank" :class="btnSecondary">Documentation</a>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex flex-col"><span class="text-2xl font-bold text-teal-300">{{ teams.length }}</span><span class="text-xs text-slate-500">Équipes</span></div>
                    <div class="h-9 w-px bg-white/10"></div>
                    <div class="flex flex-col"><span class="text-2xl font-bold text-teal-300">{{ playerCount ?? players.length }}</span><span class="text-xs text-slate-500">Joueurs</span></div>
                    <div class="h-9 w-px bg-white/10"></div>
                    <div class="flex flex-col"><span class="text-2xl font-bold text-teal-300">40</span><span class="text-xs text-slate-500">Tours / match</span></div>
                </div>
            </div>
            <div class="hidden justify-center md:flex">
                <img src="/images/tsubas3.webp" alt="Captain Tsubasa" class="max-h-[440px] object-contain drop-shadow-2xl" />
            </div>
        </section>

        <!-- FEATURES -->
        <section class="mx-auto max-w-[1200px] border-t border-white/5 bg-slate-900/50 px-6 py-12 md:px-10 md:py-16">
            <h2 class="mb-8 text-[22px] font-bold text-slate-100">Ce que tu gères</h2>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-5">
                <div v-for="f in features" :key="f.title"
                     class="rounded-xl border border-white/5 bg-white/5 p-6 transition-colors hover:border-teal-300/25 hover:bg-teal-500/5">
                    <i :class="['ti', f.icon, 'mb-3 block text-[22px] text-teal-300']" aria-hidden="true"></i>
                    <div class="mb-1.5 text-sm font-semibold text-slate-200">{{ f.title }}</div>
                    <div class="text-[13px] leading-snug text-slate-500">{{ f.desc }}</div>
                </div>
            </div>
        </section>

        <!-- ÉQUIPES -->
        <section class="mx-auto max-w-[1200px] border-t border-white/5 px-6 py-12 md:px-10 md:py-16" v-if="teams.length">
            <h2 class="mb-8 text-[22px] font-bold text-slate-100">Équipes disponibles</h2>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(120px,1fr))] gap-4">
                <div v-for="t in teams" :key="t.id"
                     class="flex flex-col items-center gap-1.5 rounded-lg border border-white/5 bg-white/5 px-3 py-4 text-center transition-colors hover:border-teal-300/30">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-lg bg-white">
                        <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" :alt="t.name" loading="lazy" class="h-full w-full object-contain" />
                        <span v-else class="text-xl font-bold text-slate-700">{{ t.name[0] }}</span>
                    </div>
                    <div class="text-xs font-semibold text-slate-200">{{ t.name }}</div>
                    <div class="text-[11px] text-slate-600">{{ (t.budget ?? 0).toLocaleString() }} €</div>
                </div>
            </div>
        </section>

        <!-- JOUEURS VEDETTES -->
        <section class="mx-auto max-w-[1200px] border-t border-white/5 px-6 py-12 md:px-10 md:py-16" v-if="playersWithPhoto.length">
            <h2 class="mb-8 text-[22px] font-bold text-slate-100">Joueurs vedettes</h2>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-4">
                <div v-for="p in playersWithPhoto.slice(0, 12)" :key="p.id"
                     class="overflow-hidden rounded-xl border border-white/5 bg-white/5 transition hover:-translate-y-0.5 hover:border-teal-300/30">
                    <div class="relative">
                        <PlayerPhoto :src="`/storage/${p.photo_path}`" :alt="p.firstname" class="block aspect-square w-full object-cover object-top" />
                        <div class="absolute left-2 top-2 rounded-md bg-teal-500 px-1.5 py-0.5 text-[11px] font-bold text-slate-900">{{ overallOf(p) }}</div>
                    </div>
                    <div class="px-3 pb-0.5 pt-2 text-xs font-semibold text-slate-200">{{ p.firstname }} {{ p.lastname }}</div>
                    <div class="px-3 pb-1 text-[11px] text-teal-300">{{ p.position }}</div>
                    <div class="flex gap-2 px-3 pb-3 pt-1 text-[11px] text-slate-500">
                        <span>ATQ {{ p.stats?.attack ?? p.attack ?? '–' }}</span>
                        <span>DEF {{ p.stats?.defense ?? p.defense ?? '–' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="mx-auto max-w-[1200px] border-t border-white/5 px-6 py-12 md:px-10 md:py-16">
            <div class="rounded-2xl border border-teal-300/20 bg-gradient-to-br from-teal-500/10 to-slate-900/80 px-6 py-8 text-center md:p-12">
                <h2 class="mb-3 text-[26px] font-bold text-slate-50">Prêt à commencer ?</h2>
                <p class="mb-8 text-[15px] text-slate-400">Crée ton compte gratuitement et lance ta première saison.</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <Link v-if="canRegister" :href="route('register')" :class="btnPrimary">Créer un compte</Link>
                    <Link v-if="canLogin && !$page.props.auth?.user" :href="route('login')" :class="btnSecondary">Se connecter</Link>
                    <a href="https://github.com/mr-larson/CaptainTsubasa-Management" target="_blank" :class="btnGhost">
                        <i class="ti ti-brand-github" aria-hidden="true"></i> GitHub
                    </a>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="flex flex-wrap justify-between gap-2 border-t border-white/5 px-6 py-6 text-xs text-slate-700 md:px-10">
            <span>Golden Eleven · Projet open source</span>
            <span>Inspiré de l'œuvre de Yōichi Takahashi</span>
        </footer>
    </div>
</template>
