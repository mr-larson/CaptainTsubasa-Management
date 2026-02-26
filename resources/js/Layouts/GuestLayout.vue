<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-900 to-slate-800 text-slate-100 flex items-center justify-center px-4">
        <!-- Conteneur principal -->
        <div class="w-full max-w-md">
            <!-- Carte formulaire -->
            <div
                class="w-full bg-white rounded-xl border border-slate-200 shadow-lg px-6 py-6 md:px-8 md:py-8 text-slate-900"
            >
                <!-- Header : logo + lien retour -->
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm text-slate-700">
                            Captain Tsubasa <span class="text-teal-500">Management</span>
                        </span>
                    </div>
                    <Link
                        :href="route('welcome')"
                        class="text-xs text-slate-500 hover:text-slate-700"
                    >
                        ← Retour à l’accueil
                    </Link>
                </div>
                <!-- Slot : contenu Login / Register -->
                <slot />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import {Link} from "@inertiajs/vue3";
</script>
