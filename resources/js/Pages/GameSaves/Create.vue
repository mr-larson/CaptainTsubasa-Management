<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';

const form = useForm({
    label: '',
    period: 'college',
    game_mode: 'prebuilt',
});

function submit() {
    form.post(route('game-saves.store'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Nouvelle partie" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Nouvelle partie</H2>
        </template>

        <div class="p-4">
            <div class="flex flex-row">
                <!-- Visuel gauche -->
                <div class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                     style="background-image: url('/images/hyuga2.webp')"></div>

                <!-- Carte formulaire -->
                <div class="basis-2/3 p-6 border border-slate-200 rounded-2xl mx-6 bg-white min-h-[500px] flex flex-col shadow-sm">

                    <!-- Header -->
                    <div class="mb-8">
                        <div class="text-xs font-bold text-teal-500 uppercase tracking-widest mb-1">Nouvelle sauvegarde</div>
                        <h1 class="text-xl font-bold text-slate-800">Création d'une partie</h1>
                        <p class="text-xs text-slate-400 mt-1">Configure ta nouvelle saison et choisis ton niveau de jeu.</p>
                    </div>

                    <form @submit.prevent="submit" class="flex flex-col flex-1 gap-6">

                        <!-- Nom de la partie -->
                        <div class="flex flex-col gap-1.5">
                            <label for="label" class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Nom de la partie
                            </label>
                            <input
                                id="label"
                                type="text"
                                v-model="form.label"
                                placeholder="ex: Saison Nankatsu 1"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-300 transition-all"
                            />
                            <p v-if="form.errors.label" class="text-xs text-rose-500">{{ form.errors.label }}</p>
                        </div>

                        <!-- Période -->
                        <div class="flex flex-col gap-1.5">
                            <label for="period" class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Période
                            </label>
                            <select
                                id="period"
                                v-model="form.period"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-300 transition-all"
                            >
                                <option value="college">Collège</option>
                                <option value="highschool">Lycée</option>
                                <option value="pro">Professionnel</option>
                            </select>
                            <p v-if="form.errors.period" class="text-xs text-rose-500">{{ form.errors.period }}</p>
                        </div>

                        <!-- Mode de jeu -->
                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Mode de démarrage
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Effectifs pré-faits -->
                                <button type="button"
                                        @click="form.game_mode = 'prebuilt'"
                                        class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                        :class="form.game_mode === 'prebuilt'
                                            ? 'border-teal-500 bg-teal-50 shadow-sm'
                                            : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🏟️</span>
                                        <span class="text-sm font-bold"
                                              :class="form.game_mode === 'prebuilt' ? 'text-teal-700' : 'text-slate-700'">
                                            Effectifs pré-faits
                                        </span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed"
                                       :class="form.game_mode === 'prebuilt' ? 'text-teal-600' : 'text-slate-400'">
                                        Chaque équipe démarre avec son effectif Captain Tsubasa classique. Idéal pour jouer directement.
                                    </p>
                                    <div v-if="form.game_mode === 'prebuilt'"
                                         class="absolute top-2 right-2 w-5 h-5 rounded-full bg-teal-500 flex items-center justify-center">
                                        <span class="text-white text-[10px] font-bold">✓</span>
                                    </div>
                                </button>

                                <!-- Draft -->
                                <button type="button"
                                        @click="form.game_mode = 'draft'"
                                        class="relative flex flex-col gap-2 p-4 rounded-xl border-2 transition-all text-left"
                                        :class="form.game_mode === 'draft'
                                            ? 'border-amber-500 bg-amber-50 shadow-sm'
                                            : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🎯</span>
                                        <span class="text-sm font-bold"
                                              :class="form.game_mode === 'draft' ? 'text-amber-700' : 'text-slate-700'">
                                            Draft initial
                                        </span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed"
                                       :class="form.game_mode === 'draft' ? 'text-amber-600' : 'text-slate-400'">
                                        Toutes les équipes partent à zéro. Chaque manager pioche ses joueurs tour par tour. Mode gestionnaire pur.
                                    </p>
                                    <div v-if="form.game_mode === 'draft'"
                                         class="absolute top-2 right-2 w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center">
                                        <span class="text-white text-[10px] font-bold">✓</span>
                                    </div>
                                </button>
                            </div>
                            <p v-if="form.errors.game_mode" class="text-xs text-rose-500">{{ form.errors.game_mode }}</p>
                        </div>

                        <!-- Spacer -->
                        <div class="flex-1"></div>

                        <!-- Boutons -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                            <Link :href="route('mainMenu')"
                                  class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-500 text-sm font-semibold rounded-xl border border-slate-200 transition-all">
                                ← Retour
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex items-center gap-2 px-6 py-2.5 text-white text-sm font-bold rounded-xl transition-all disabled:opacity-50 active:scale-[0.98]"
                                :class="form.game_mode === 'draft'
                                    ? 'bg-amber-500 hover:bg-amber-400'
                                    : 'bg-teal-500 hover:bg-teal-400'">
                                <span v-if="form.processing">Création...</span>
                                <span v-else-if="form.game_mode === 'draft'">🎯 Lancer le draft</span>
                                <span v-else>⚽ Démarrer la partie</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
