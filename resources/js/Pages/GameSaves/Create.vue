<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';
import FormRaw from '@/Components/FormRaw.vue';
import FormCol from '@/Components/FormCol.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import InputSelect from '@/Components/InputSelect.vue';

const form = useForm({
    label: '',
    period: 'college',
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
                                class="flex items-center gap-2 px-6 py-2.5 bg-teal-500 hover:bg-teal-400 text-white text-sm font-bold rounded-xl transition-all disabled:opacity-50 active:scale-[0.98]">
                                <span v-if="form.processing">Création...</span>
                                <span v-else>⚽ Démarrer la partie</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
