<script setup>
import { Link } from '@inertiajs/vue3';

// Mise en page des pages publiques (guide, confidentialité) : mêmes couleurs
// que l'accueil, contenu de lecture sur fond sombre.
</script>

<template>
    <div class="min-h-screen bg-slate-900 text-[15px] leading-relaxed text-slate-100">
        <nav class="sticky top-0 z-50 flex items-center justify-between gap-3 border-b border-white/5 bg-slate-900/90 px-4 py-3 backdrop-blur md:px-10 md:py-4">
            <Link :href="route('welcome')" class="flex items-baseline gap-1.5 text-[17px]">
                <span class="font-bold text-slate-100">Golden</span>
                <span class="text-teal-300">Eleven</span>
            </Link>
            <div class="flex items-center gap-2 sm:gap-4">
                <Link v-if="$page.props.auth?.user" :href="route('mainMenu')"
                      class="inline-flex min-h-[44px] items-center rounded-full bg-teal-500 px-4 text-sm font-bold text-slate-700 hover:bg-teal-400">Menu principal</Link>
                <template v-else>
                    <Link :href="route('login')" class="inline-flex min-h-[44px] items-center px-2 text-sm text-slate-300 hover:text-teal-300">Connexion</Link>
                    <Link :href="route('register')"
                          class="inline-flex min-h-[44px] items-center whitespace-nowrap rounded-full bg-teal-500 px-4 text-sm font-bold text-slate-700 hover:bg-teal-400">S'inscrire</Link>
                </template>
            </div>
        </nav>

        <main class="mx-auto max-w-3xl px-4 py-10 md:px-6 md:py-14">
            <slot />
        </main>

        <footer class="flex flex-col gap-3 border-t border-white/5 px-4 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between md:px-10">
            <span>Golden Eleven · Projet indépendant et open source</span>
            <nav class="flex flex-wrap gap-4" aria-label="Liens du site">
                <Link :href="route('welcome')" class="hover:text-teal-300">Accueil</Link>
                <Link :href="route('guide')" class="hover:text-teal-300">Comment jouer</Link>
                <Link :href="route('privacy')" class="hover:text-teal-300">Confidentialité</Link>
            </nav>
        </footer>
    </div>
</template>
