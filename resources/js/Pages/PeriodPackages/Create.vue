<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import H2 from '@/Components/H2.vue';

const zipInput = ref(null);
const selectedZipName = ref('');

const form = useForm({
    name: '',
    description: '',
    is_shared: false,
    zip: null,
});

function openZipPicker() {
    zipInput.value?.click();
}

function onZipChange(e) {
    const file = e.target.files?.[0] ?? null;
    form.zip = file;
    selectedZipName.value = file ? file.name : '';
    e.target.value = '';
}

function submit() {
    form.post(route('period-packages.store'), { forceFormData: true });
}
</script>

<template>
    <Head title="Importer une période" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Importer une période</H2>
        </template>

        <div class="p-4">
            <div class="flex justify-center">
                <h1 class="text-2xl font-bold text-slate-600 mb-6">Nouvelle période (bibliothèque)</h1>
            </div>

            <div class="max-w-2xl mx-auto p-6 border border-slate-200 rounded-2xl bg-white shadow-sm">
                <form @submit.prevent="submit" class="flex flex-col gap-4">

                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Nom</label>
                        <input v-model="form.name" type="text"
                               class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-50 text-sm px-3 py-2" />
                        <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Description</label>
                        <textarea v-model="form.description" rows="3"
                                  class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-50 text-sm px-3 py-2"></textarea>
                        <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">{{ form.errors.description }}</p>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Fichier de période (.zip)</label>
                        <div class="mt-1 flex items-center gap-3">
                            <input ref="zipInput" type="file" accept=".zip" class="hidden" @change="onZipChange" />
                            <button type="button"
                                    class="shrink-0 px-3 py-1.5 rounded-l-full border border-gray-300 bg-stone-50 text-sm text-gray-900 hover:bg-white"
                                    @click="openZipPicker">
                                Choisir
                            </button>
                            <div class="flex-1 px-3 py-1.5 rounded-r-full border border-l-0 border-gray-300 bg-stone-50 text-sm text-slate-600 truncate">
                                {{ selectedZipName || 'Aucun fichier choisi' }}
                            </div>
                        </div>
                        <p v-if="form.errors.zip" class="text-sm text-red-600 mt-1">{{ form.errors.zip }}</p>
                        <p class="text-xs text-slate-400 mt-2">
                            Structure attendue : manifest.json, teams.json, players.json, contracts.json,
                            et un dossier images/players / images/teams. Taille max : 20 Mo.
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.is_shared" type="checkbox" class="rounded border-slate-300" />
                        Partager cette période (visible par les autres utilisateurs)
                    </label>

                    <div class="flex items-center justify-between pt-4">
                        <Link :href="route('period-packages.index')"
                              class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-500 text-sm font-semibold rounded-xl border border-slate-200 transition-all">
                            ← Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing"
                                class="px-6 py-2 bg-teal-500 hover:bg-teal-400 text-white text-sm font-semibold rounded-xl transition-all disabled:opacity-50">
                            <span v-if="form.processing">Import en cours...</span>
                            <span v-else>Importer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
