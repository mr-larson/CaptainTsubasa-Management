<script setup>
import { Head, Link } from '@inertiajs/vue3';

const { canLogin, canRegister, teams, players } = defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
    teams: {
        type: Array,
        default: () => [],
    },
    players: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Captain Tsubasa Management" />

    <div class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-900 to-slate-800 text-slate-100">

        <!-- BARRE HAUT : login / register / main menu -->
        <header class="flex justify-end p-6">
            <div v-if="canLogin" class="space-x-4 text-sm">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('mainMenu')"
                    class="font-semibold text-slate-100 hover:text-teal-300"
                >
                    Menu principal
                </Link>

                <template v-if="canLogin">
                    <Link
                        :href="route('login')"
                        class="font-semibold text-slate-100 hover:text-teal-300"
                    >
                        Connexion
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="font-semibold text-slate-100 hover:text-teal-300"
                    >
                        Inscription
                    </Link>
                </template>
            </div>
        </header>

        <!-- CONTENU PRINCIPAL -->
        <main class="max-w-6xl mx-auto px-4 pb-16">
            <!-- Titre / Hero -->
            <section class="text-center mb-10">
                <h1 class="text-3xl md:text-5xl font-extrabold tracking-wide">
                    Captain Tsubasa <span class="text-teal-300">Management</span>
                </h1>
            </section>

            <!-- Carte style dashboard : visuel + contenu -->
            <section class="flex flex-col md:flex-row gap-6 items-stretch">

                <!-- Visuel gauche, même esprit que Show.vue -->
                <div
                    class="hidden md:block basis-1/3 p-2 bg-cover bg-center bg-no-repeat"
                    style="background-image: url('/images/tsubas3.webp')"
                ></div>

                <!-- Carte principale -->
                <div class="basis-full md:basis-2/3 bg-slate-900/80 border border-slate-700 rounded-xl shadow-xl p-6 flex flex-col gap-6">

                    <!-- Présentation du projet (résumé) -->
                    <div>
                        <p class="text-sm md:text-base text-slate-200 leading-relaxed">
                            Captain Tsubasa Management te permet de gérer un club complet :
                            constituer ton effectif, gérer ton budget, préparer des fiches de match,
                            utiliser des techniques spéciales, gérer la fatigue, les blessures,
                            et participer à des championnats.
                        </p>

                        <div class="mt-3 flex flex-col gap-3">
                            <a
                                href="https://gautd8.notion.site/Captain-Tsubasa-Management-28c47313c8ca4fb5b0e3652491118849"
                                target="_blank"
                                class="inline-flex items-center gap-2 p-2 rounded-md bg-slate-700/40 border border-slate-600 text-slate-200 hover:bg-slate-700/60 transition"
                            >
                                📘 Documentation complète du projet
                            </a>

                            <a
                                href="https://github.com/mr-larson/CaptainTsubasaManagement"
                                target="_blank"
                                class="inline-flex items-center gap-2 p-2 rounded-md bg-slate-700/40 border border-slate-600 text-slate-200 hover:bg-slate-700/60 transition"
                            >
                                💻 Code source sur GitHub
                            </a>
                        </div>
                    </div>

                    <!-- Grille : Equipes + Joueurs vedettes -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <!-- Équipes -->
                        <div class="bg-slate-800/80 border border-slate-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-teal-300 mb-2">
                                Équipes disponibles
                            </h3>
                            <p class="text-xs text-slate-400 mb-3">
                                Un aperçu des équipes présentes dans la base de données.
                            </p>

                            <div v-if="teams.length" class="space-y-1 max-h-64 overflow-y-auto pr-1 text-sm">
                                <div
                                    v-for="team in teams"
                                    :key="team.id"
                                    class="flex items-center justify-between py-1 border-b border-slate-700/60 last:border-b-0"
                                >
                                    <span class="font-medium text-slate-100">
                                        {{ team.name }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        Budget : {{ team.budget ?? 0 }} €
                                    </span>
                                </div>
                            </div>

                            <p v-else class="text-sm text-slate-400">
                                Aucune équipe n’est encore définie. Lance les seeders pour les créer.
                            </p>
                        </div>

                        <!-- Joueurs vedettes -->
                        <div class="bg-slate-800/80 border border-slate-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-teal-300 mb-2">
                                Joueurs vedettes
                            </h3>
                            <p class="text-xs text-slate-400 mb-3">
                                Quelques stars et seconds rôles connus issus de l’univers Captain Tsubasa.
                            </p>

                            <div v-if="players.length" class="grid grid-cols-2 gap-3 max-h-64 overflow-y-auto pr-1">
                                <div
                                    v-for="p in players"
                                    :key="p.id"
                                    class="bg-slate-900/90 rounded-md border border-slate-700 p-2 text-xs flex flex-col items-center text-center"
                                >
                                    <img
                                        v-if="p.photo_path"
                                        :src="`/storage/${p.photo_path}`"
                                        alt="Portrait joueur"
                                        class="w-20 h-20 object-cover rounded mb-1 border border-slate-600"
                                    />
                                    <div class="font-semibold text-slate-100 leading-tight">
                                        {{ p.firstname }} {{ p.lastname }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ p.position }}
                                    </div>
                                    <div class="text-[11px] text-slate-300 mt-1">
                                        ATQ {{ p.stats?.attack ?? '-' }} · DEF {{ p.stats?.defense ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <p v-else class="text-sm text-slate-400">
                                Aucun joueur n’est encore disponible. Exécute le seeder des joueurs pour remplir la base.
                            </p>
                        </div>
                    </div>

                    <!-- Call-to-action bas de carte -->
                    <div class="mt-2 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-400 max-w-md">
                            Pour commencer à jouer, crée un compte ou connecte-toi, puis lance une nouvelle partie
                            depuis le menu principal.
                        </p>

                        <div class="flex flex-wrap gap-2 justify-end">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="px-4 py-1.5 text-sm rounded-full bg-teal-500 hover:bg-teal-400 text-slate-900 font-semibold shadow"
                            >
                                Créer un compte
                            </Link>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="px-4 py-1.5 text-sm rounded-full border border-teal-400 text-teal-200 hover:bg-teal-500/10 font-semibold"
                            >
                                Se connecter
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
