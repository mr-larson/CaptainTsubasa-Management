<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const { canLogin, canRegister, teams, players } = defineProps({
    canLogin:    { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
    teams:       { type: Array,   default: () => [] },
    players:     { type: Array,   default: () => [] },
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

const features = [
    { icon: 'ti-trophy',      title: 'Championnat complet',   desc: 'Saisons de 28+ journées avec calendrier aller-retour équilibré.' },
    { icon: 'ti-sword',       title: 'Matchs au tour par tour', desc: '40 tours par match avec passes, dribbles, tirs et actions spéciales.' },
    { icon: 'ti-run',         title: 'Gestion de l\'effectif',  desc: 'Titulaires, remplaçants, formations tactiques et numéros de maillot.' },
    { icon: 'ti-heart-rate',  title: 'Fatigue & blessures',    desc: 'Stamina, blessures d\'épuisement, cartons jaunes et rouges.' },
    { icon: 'ti-arrows-exchange', title: 'Transferts',         desc: 'Marché des agents libres, recrutement IA et gestion du budget.' },
    { icon: 'ti-chart-line',  title: 'Statistiques',           desc: 'Performances par joueur, bilan d\'équipe et historique des matchs.' },
];
</script>

<template>
    <Head title="Captain Tsubasa Management" />

    <div class="page-wrap">

        <!-- NAV -->
        <nav class="nav-bar">
            <div class="nav-brand">
                <span class="brand-ct">Captain Tsubasa</span>
                <span class="brand-mgmt">Management</span>
            </div>
            <div class="nav-links" v-if="canLogin">
                <Link v-if="$page.props.auth?.user" :href="route('mainMenu')" class="nav-link">Menu principal</Link>
                <template v-else>
                    <Link :href="route('login')"    class="nav-link">Connexion</Link>
                    <Link v-if="canRegister" :href="route('register')" class="nav-cta">Jouer gratuitement</Link>
                </template>
            </div>
        </nav>

        <!-- HERO -->
        <section class="hero">
            <div class="hero-content">
                <div class="hero-badge">Saison 1 · En développement</div>
                <h1 class="hero-title">Deviens le meilleur<br>manager du Japon</h1>
                <p class="hero-sub">
                    Un jeu de gestion football tour par tour inspiré de l'univers Captain Tsubasa.
                    Construis ton équipe, dispute des championnats et affronte l'IA.
                </p>
                <div class="hero-actions">
                    <Link v-if="canRegister" :href="route('register')" class="btn-primary">Créer une partie</Link>
                    <a href="https://gautd8.notion.site/Captain-Tsubasa-Management-28c47313c8ca4fb5b0e3652491118849"
                       target="_blank" class="btn-secondary">Documentation</a>
                </div>
                <div class="hero-stats">
                    <div class="hstat"><span class="hstat-n">{{ teams.length }}</span><span class="hstat-l">Équipes</span></div>
                    <div class="hstat-sep"></div>
                    <div class="hstat"><span class="hstat-n">{{ players.length }}</span><span class="hstat-l">Joueurs</span></div>
                    <div class="hstat-sep"></div>
                    <div class="hstat"><span class="hstat-n">45</span><span class="hstat-l">Tours / match</span></div>
                </div>
            </div>
            <div class="hero-visual">
                <img src="/images/tsubas3.webp" alt="Captain Tsubasa" class="hero-img" />
            </div>
        </section>

        <!-- FEATURES -->
        <section class="section features-section">
            <h2 class="section-title">Ce que tu gères</h2>
            <div class="features-grid">
                <div v-for="f in features" :key="f.title" class="feature-card">
                    <i :class="['ti', f.icon, 'feature-icon']" aria-hidden="true"></i>
                    <div class="feature-title">{{ f.title }}</div>
                    <div class="feature-desc">{{ f.desc }}</div>
                </div>
            </div>
        </section>

        <!-- ÉQUIPES -->
        <section class="section" v-if="teams.length">
            <h2 class="section-title">Équipes disponibles</h2>
            <div class="teams-grid">
                <div v-for="t in teams" :key="t.id" class="team-card">
                    <div class="team-logo-wrap">
                        <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" :alt="t.name" class="team-logo" />
                        <span v-else class="team-logo-fallback">{{ t.name[0] }}</span>
                    </div>
                    <div class="team-name">{{ t.name }}</div>
                    <div class="team-budget">{{ (t.budget ?? 0).toLocaleString() }} €</div>
                </div>
            </div>
        </section>

        <!-- JOUEURS VEDETTES -->
        <section class="section" v-if="playersWithPhoto.length">
            <h2 class="section-title">Joueurs vedettes</h2>
            <div class="players-grid">
                <div v-for="p in playersWithPhoto.slice(0, 12)" :key="p.id" class="player-card">
                    <div class="player-photo-wrap">
                        <img :src="`/storage/${p.photo_path}`" :alt="p.firstname" class="player-photo" />
                        <div class="player-overall">{{ overallOf(p) }}</div>
                    </div>
                    <div class="player-name">{{ p.firstname }} {{ p.lastname }}</div>
                    <div class="player-pos">{{ p.position }}</div>
                    <div class="player-stats">
                        <span>ATQ {{ p.stats?.attack ?? p.attack ?? '–' }}</span>
                        <span>DEF {{ p.stats?.defense ?? p.defense ?? '–' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="cta-section">
            <div class="cta-card">
                <h2 class="cta-title">Prêt à commencer ?</h2>
                <p class="cta-sub">Crée ton compte gratuitement et lance ta première saison.</p>
                <div class="cta-actions">
                    <Link v-if="canRegister" :href="route('register')" class="btn-primary">Créer un compte</Link>
                    <Link v-if="canLogin && !$page.props.auth?.user" :href="route('login')" class="btn-secondary">Se connecter</Link>
                    <a href="https://github.com/mr-larson/CaptainTsubasaManagement" target="_blank" class="btn-ghost">
                        <i class="ti ti-brand-github" aria-hidden="true"></i> GitHub
                    </a>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="footer">
            <span>Captain Tsubasa Management · Projet open source</span>
            <span>Inspiré de l'œuvre de Yōichi Takahashi</span>
        </footer>
    </div>
</template>

<style scoped>
/* ── Reset & base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.page-wrap {
    min-height: 100vh;
    background: #0f172a;
    color: #f1f5f9;
    font-family: system-ui, -apple-system, sans-serif;
    font-size: 15px;
    line-height: 1.6;
}

/* ── Nav ── */
.nav-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 2.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    position: sticky; top: 0; z-index: 50;
    background: rgba(15,23,42,0.92);
    backdrop-filter: blur(8px);
}
.nav-brand { display: flex; align-items: baseline; gap: 6px; }
.brand-ct { font-size: 17px; font-weight: 700; color: #f1f5f9; }
.brand-mgmt { font-size: 17px; font-weight: 400; color: #5eead4; }
.nav-links { display: flex; align-items: center; gap: 1.25rem; }
.nav-link { color: #cbd5e1; font-size: 14px; text-decoration: none; transition: color .15s; }
.nav-link:hover { color: #5eead4; }
.nav-cta {
    background: #14b8a6; color: #0f172a; font-size: 13px; font-weight: 600;
    padding: 6px 16px; border-radius: 999px; text-decoration: none; transition: background .15s;
}
.nav-cta:hover { background: #2dd4bf; }

/* ── Hero ── */
.hero {
    display: grid; grid-template-columns: 1fr 420px;
    gap: 3rem; align-items: center;
    max-width: 1200px; margin: 0 auto;
    padding: 5rem 2.5rem 4rem;
}
.hero-badge {
    display: inline-flex; align-items: center;
    background: rgba(20,184,166,0.15); color: #5eead4;
    font-size: 12px; font-weight: 600; letter-spacing: .5px;
    padding: 4px 12px; border-radius: 999px;
    border: 1px solid rgba(94,234,212,0.25);
    margin-bottom: 1.25rem;
}
.hero-title {
    font-size: clamp(2.2rem, 4vw, 3.2rem); font-weight: 800;
    line-height: 1.15; color: #f8fafc;
    margin-bottom: 1.25rem;
}
.hero-sub {
    font-size: 16px; color: #94a3b8; max-width: 520px;
    margin-bottom: 2rem; line-height: 1.7;
}
.hero-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 2.5rem; }
.hero-stats { display: flex; align-items: center; gap: 1.5rem; }
.hstat { display: flex; flex-direction: column; }
.hstat-n { font-size: 24px; font-weight: 700; color: #5eead4; }
.hstat-l { font-size: 12px; color: #64748b; }
.hstat-sep { width: 1px; height: 36px; background: rgba(255,255,255,0.1); }
.hero-visual { display: flex; justify-content: center; }
.hero-img { max-height: 440px; object-fit: contain; filter: drop-shadow(0 8px 32px rgba(0,0,0,0.5)); }

/* ── Buttons ── */
.btn-primary {
    background: #14b8a6; color: #0f172a; font-weight: 700; font-size: 14px;
    padding: 10px 24px; border-radius: 999px; text-decoration: none;
    transition: background .15s; display: inline-block;
}
.btn-primary:hover { background: #2dd4bf; }
.btn-secondary {
    background: rgba(255,255,255,0.06); color: #e2e8f0; font-size: 14px; font-weight: 500;
    padding: 10px 24px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.12);
    text-decoration: none; transition: background .15s; display: inline-block;
}
.btn-secondary:hover { background: rgba(255,255,255,0.1); }
.btn-ghost {
    background: transparent; color: #94a3b8; font-size: 14px;
    padding: 10px 20px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.08);
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color .15s;
}
.btn-ghost:hover { color: #e2e8f0; }

/* ── Sections ── */
.section {
    max-width: 1200px; margin: 0 auto;
    padding: 4rem 2.5rem;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.section-title {
    font-size: 22px; font-weight: 700; color: #f1f5f9;
    margin-bottom: 2rem;
}

/* ── Features ── */
.features-section { background: rgba(15,23,42,0.5); }
.features-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.25rem;
}
.feature-card {
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px; padding: 1.5rem;
    transition: border-color .2s, background .2s;
}
.feature-card:hover { border-color: rgba(94,234,212,0.25); background: rgba(20,184,166,0.05); }
.feature-icon { font-size: 22px; color: #5eead4; display: block; margin-bottom: .75rem; }
.feature-title { font-size: 14px; font-weight: 600; color: #e2e8f0; margin-bottom: .4rem; }
.feature-desc { font-size: 13px; color: #64748b; line-height: 1.55; }

/* ── Teams ── */
.teams-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}
.team-card {
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 1rem .75rem;
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    text-align: center;
    transition: border-color .2s;
}
.team-card:hover { border-color: rgba(94,234,212,0.3); }
.team-logo-wrap {
    width: 44px; height: 44px; border-radius: 8px;
    background: #fff; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
}
.team-logo { width: 100%; height: 100%; object-fit: contain; }
.team-logo-fallback { font-size: 20px; font-weight: 700; color: #334155; }
.team-name { font-size: 12px; font-weight: 600; color: #e2e8f0; }
.team-budget { font-size: 11px; color: #475569; }

/* ── Players ── */
.players-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
}

.player-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color .2s, transform .2s;
}

.player-card:hover {
    border-color: rgba(94,234,212,0.3);
    transform: translateY(-2px);
}

.player-photo-wrap { position: relative; }

.player-photo {
    width: 100%;
    aspect-ratio: 1/1;  /* ← carré au lieu de 3/4 */
    object-fit: cover;
    object-position: top;  /* ← cadrer sur le visage */
    display: block;
}

.player-overall {
    position: absolute; top: 8px; left: 8px;
    background: #14b8a6; color: #0f172a;
    font-size: 11px; font-weight: 700;
    padding: 2px 6px; border-radius: 6px;
}

.player-name {
    font-size: 12px; font-weight: 600; color: #e2e8f0;
    padding: .5rem .75rem .1rem;
    text-align: left;
}

.player-pos {
    font-size: 11px; color: #5eead4;
    padding: 0 .75rem .25rem;
    text-align: left;
}

.player-stats {
    font-size: 11px; color: #64748b;
    padding: .25rem .75rem .75rem;
    display: flex; gap: 8px;
    justify-content: flex-start;
}

/* ── CTA ── */
.cta-section {
    max-width: 1200px; margin: 0 auto;
    padding: 4rem 2.5rem;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.cta-card {
    background: linear-gradient(135deg, rgba(20,184,166,0.12), rgba(15,23,42,0.8));
    border: 1px solid rgba(94,234,212,0.2);
    border-radius: 16px; padding: 3rem;
    text-align: center;
}
.cta-title { font-size: 26px; font-weight: 700; color: #f8fafc; margin-bottom: .75rem; }
.cta-sub { font-size: 15px; color: #94a3b8; margin-bottom: 2rem; }
.cta-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

/* ── Footer ── */
.footer {
    display: flex; justify-content: space-between; flex-wrap: wrap; gap: .5rem;
    padding: 1.5rem 2.5rem;
    font-size: 12px; color: #334155;
    border-top: 1px solid rgba(255,255,255,0.05);
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .hero { grid-template-columns: 1fr; padding: 3rem 1.5rem 2rem; }
    .hero-visual { display: none; }
    .nav-bar { padding: 1rem 1.5rem; }
    .section { padding: 3rem 1.5rem; }
    .cta-card { padding: 2rem 1.5rem; }
}
</style>
