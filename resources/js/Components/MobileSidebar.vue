<script setup>
import { ref } from 'vue';

/**
 * Sidebar latérale fixe (w-64) toujours visible sur >= sm.
 * Sur mobile, elle est masquée hors-écran et accessible via un bouton
 * hamburger + overlay (off-canvas). Le contenu est fourni en slot.
 */
const open = ref(false);
</script>

<template>
    <!-- Bouton d'ouverture (mobile uniquement, masqué quand la sidebar est ouverte) -->
    <button
        v-show="!open"
        type="button"
        @click="open = true"
        aria-label="Ouvrir le menu"
        class="sm:hidden fixed top-[4.5rem] left-3 z-50 inline-flex items-center justify-center p-2 rounded-lg bg-slate-700 text-white shadow-lg hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <!-- Fond cliquable (mobile, quand ouvert) -->
    <div
        v-if="open"
        @click="open = false"
        class="sm:hidden fixed inset-0 z-40 bg-black/50"
        aria-hidden="true"
    ></div>

    <!-- Sidebar -->
    <aside
        id="separator-sidebar"
        aria-label="Sidebar"
        class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform sm:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Bouton de fermeture (mobile) -->
        <button
            type="button"
            @click="open = false"
            aria-label="Fermer le menu"
            class="sm:hidden absolute top-2 right-2 z-10 inline-flex items-center justify-center p-1.5 rounded-lg text-gray-300 hover:bg-slate-600 hover:text-white"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <slot />
    </aside>
</template>
