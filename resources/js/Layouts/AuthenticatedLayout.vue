<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import FlashToasts from '@/Components/FlashToasts.vue';
import AppDialog from '@/Components/AppDialog.vue';

defineProps({
    // Pages plein écran (match) : pas de barre de navigation, la page a son propre bouton Retour.
    hideNav: { type: Boolean, default: false },
});

const showingNavigationDropdown = ref(false);

// Navigation disponible depuis toutes les pages (menu utilisateur et menu mobile).
const navLinks = [
    { label: 'Menu principal', route: 'mainMenu' },
    { label: 'Mes parties',    route: 'game-saves.index' },
    { label: 'Nouvelle partie', route: 'game-saves.create' },
    { label: 'Mes périodes',   route: 'period-packages.index' },
    { label: 'Base de données', route: 'dataBaseMenu' },
];
</script>

<template>
    <div>
        <div class="h-screen flex flex-col overflow-hidden">
            <nav v-if="!hideNav" class="bg-slate-700 text-slate-100 border-b border-slate-300 shrink-0">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <!-- Marque (retour au menu principal) + titre de la page -->
                        <div class="flex min-w-0 items-center gap-3">
                            <Link :href="route('mainMenu')" class="flex shrink-0 items-baseline gap-1.5 text-[17px]" title="Menu principal">
                                <span class="font-bold text-slate-100">Golden</span>
                                <span class="text-teal-300">Eleven</span>
                            </Link>
                            <header class="flex min-w-0 items-center gap-3" v-if="$slots.header">
                                <span class="h-5 w-px shrink-0 bg-white/20" aria-hidden="true"></span>
                                <div class="min-w-0 truncate"><slot name="header" /></div>
                            </header>
                        </div>
                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <!-- Settings Dropdown -->
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-500 bg-white hover:text-slate-700 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="ml-2 -mr-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink v-for="link in navLinks" :key="link.route" :href="route(link.route)">
                                            {{ link.label }}
                                        </DropdownLink>
                                        <div class="border-t border-slate-300 my-1"></div>
                                        <DropdownLink :href="route('profile.edit')"> Profil </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Déconnexion
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-slate-500 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 focus:text-slate-500 transition duration-150 ease-in-out"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-slate-300">
                        <div class="px-4">
                            <div class="font-medium text-base text-slate-700">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-slate-500">{{ $page.props.auth.user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink v-for="link in navLinks" :key="link.route" :href="route(link.route)"
                                               :active="route().current(link.route)">
                                {{ link.label }}
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('profile.edit')"> Profil </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Déconnexion
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <main class="bg-slate-100 flex-1 min-h-0 overflow-y-auto">
                <slot />
            </main>
        </div>

        <FlashToasts />
        <AppDialog />
    </div>
</template>
