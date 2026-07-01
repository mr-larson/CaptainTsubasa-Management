<!-- resources/js/Pages/Teams/Create.vue -->
<template>
    <Head title="Add Team" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Add Team</H2>
        </template>

        <div class="p-4">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-6">Création</h1>
            </div>

            <div class="flex flex-col md:flex-row">
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/Mamoru_Izawa_(Shutetsu_ES-SR-Tq)_Full.webp')"
                ></div>

                <div class="basis-full md:basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-lg mx-0 md:mx-6 bg-white">
                    <form @submit.prevent="submit">
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="name" value="Name" />
                                <InputText
                                    id="name"
                                    type="text"
                                    class="mt-1 w-full"
                                    v-model="form.name"
                                    autocomplete="name"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.name }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="budget" value="Budget" />
                                <InputText
                                    type="number"
                                    id="budget"
                                    v-model="form.budget"
                                    placeholder="Budget de l'équipe"
                                    class="mt-1 w-full"
                                    autocomplete="budget"
                                />
                                <p v-if="form.errors.budget" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.budget }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel for="wins" value="Victoire(s)" />
                                <InputText
                                    type="number"
                                    id="wins"
                                    v-model="form.wins"
                                    placeholder="Victoire(s) de l'équipe"
                                    class="mt-1 w-full"
                                />
                                <p v-if="form.errors.wins" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.wins }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="losses" value="Défaite(s)" />
                                <InputText
                                    type="number"
                                    id="losses"
                                    v-model="form.losses"
                                    placeholder="Défaite(s) de l'équipe"
                                    class="mt-1 w-full"
                                />
                                <p v-if="form.errors.losses" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.losses }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel for="draws" value="Matchs nuls" />
                                <InputText
                                    type="number"
                                    id="draws"
                                    v-model="form.draws"
                                    placeholder="Matchs nuls de l'équipe"
                                    class="mt-1 w-full"
                                />
                                <p v-if="form.errors.draws" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.draws }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel for="tactical_style" value="Style tactique" />
                                <select
                                    id="tactical_style"
                                    v-model="form.tactical_style"
                                    class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm text-gray-900 focus:outline-none focus:bg-white focus:border-purple-300"
                                >
                                    <option v-for="style in tacticalStyles" :key="style" :value="style">
                                        {{ tacticalLabel(style) }} {{ tacticalIcon(style) }}
                                    </option>
                                </select>
                                <p v-if="form.errors.tactical_style" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.tactical_style }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="management_philosophy" value="Philosophie de gestion" />
                                <select
                                    id="management_philosophy"
                                    v-model="form.management_philosophy"
                                    class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm text-gray-900 focus:outline-none focus:bg-white focus:border-purple-300"
                                >
                                    <option v-for="philosophy in philosophies" :key="philosophy" :value="philosophy">
                                        {{ philosophyLabel(philosophy) }} {{ philosophyIcon(philosophy) }}
                                    </option>
                                </select>
                                <p v-if="form.errors.management_philosophy" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.management_philosophy }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- ✅ LOGO UPLOAD -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Logo" />

                                <div class="mt-1 flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        <!-- input file caché -->
                                        <input
                                            ref="logoInput"
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="onLogoChange"
                                        />

                                        <!-- faux input -->
                                        <div class="flex items-center w-full md:w-56">
                                            <button
                                                type="button"
                                                class="shrink-0 px-3 py-1.5 rounded-l-full border border-gray-300 bg-stone-50 text-sm text-gray-900 hover:bg-white focus:outline-none focus:border-slate-700"
                                                @click="openLogoPicker"
                                            >
                                                Choisir
                                            </button>

                                            <div
                                                class="flex-1 px-3 py-1.5 rounded-r-full border border-l-0 border-gray-300 bg-stone-50 text-sm text-slate-600 truncate"
                                                :title="selectedLogoName || 'Aucun fichier choisi'"
                                            >
                                                {{ selectedLogoName || 'Aucun fichier choisi' }}
                                            </div>
                                        </div>

                                        <!-- preview carré -->
                                        <div class="h-16 w-16 rounded border bg-white overflow-hidden flex items-center justify-center">
                                            <img
                                                v-if="logoPreviewUrl"
                                                :src="logoPreviewUrl"
                                                class="h-full w-full object-cover"
                                                alt="Logo équipe"
                                            />
                                            <span v-else class="text-xs text-slate-400">Aucun</span>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="form.errors.logo" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.logo }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <div class="flex justify-around p-6">
                            <button
                                type="submit"
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Création
                            </button>

                            <Link
                                :href="route('dataBaseMenu')"
                                class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-2 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2"
                            >
                                Retour
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Components
import H2 from '@/Components/H2.vue';
import FormRaw from '@/Components/FormRaw.vue';
import FormCol from '@/Components/FormCol.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import { useTeamStyles } from '@/Pages/GameSaves/Play/useTeamStyles.js';
import { TACTICAL_STYLES, PHILOSOPHIES } from '@/Enums/teamStyle.js';

const { tacticalLabel, tacticalIcon, philosophyLabel, philosophyIcon } = useTeamStyles();
const tacticalStyles = TACTICAL_STYLES;
const philosophies = PHILOSOPHIES;

const form = useForm({
    name: '',
    budget: '',
    wins: 0,
    losses: 0,
    draws: 0,
    tactical_style: 'balanced',
    management_philosophy: 'collective',
    logo: null, // ✅ AJOUT
});

// ✅ LOGO UI
const logoInput = ref(null);
const selectedLogoName = ref('');
const logoPreviewUrl = ref(null);

function openLogoPicker() {
    logoInput.value?.click();
}

function onLogoChange(e) {
    const file = e.target.files?.[0] || null;

    if (!file) {
        selectedLogoName.value = '';
        logoPreviewUrl.value = null;
        form.logo = null;
        return;
    }

    selectedLogoName.value = file.name;
    form.logo = file;

    // preview
    logoPreviewUrl.value = URL.createObjectURL(file);
}

function submit() {
    form.post(route('teams.store'), {
        forceFormData: true, // ✅ indispensable pour l'upload
        onSuccess: () => {
            // rien
        },
    });
}
</script>
