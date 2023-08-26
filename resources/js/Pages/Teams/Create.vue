<template>
    <Head title="Add Team" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Add Team</H2>
        </template>

        <div class="p-4 ">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-6">Creation</h1>
            </div>
            <div class="flex flex-row">
                <div class="basis-1/3 p-4 bg-contain bg-center bg-no-repeat" style="background-image: url('/images/wakabayashi.webp')">

                </div>

                <div class="basis-2/3 p-4 border border-slate-300 rounded-lg mx-6 bg-white">
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="grid grid-cols-2 gap-4 text-slate-700 py-6">
                            <div class="md:flex md:items-center m-3">
                                <div class="md:w-1/3">
                                    <label for="name" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                        Nom :
                                    </label>
                                </div>
                                <div class="md:w-2/3">
                                    <input type="text" id="name" v-model="form.name" placeholder="Nom de l'équipe" required class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                                </div>
                            </div>

                            <div class="md:flex md:items-center m-3">
                                <div class="md:w-1/3">
                                    <label for="wins" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                        Victoire(s) :
                                    </label>
                                </div>
                                <div class="md:w-2/3">
                                    <input type="number" id="wins" v-model="form.wins" placeholder="Victoire(s) de l'équipe" required class=" text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                                </div>
                            </div>

                            <div class="md:flex md:items-center mb-3">
                                <div class="md:w-1/3">
                                    <label for="points" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                        Points :
                                    </label>
                                </div>
                                <div class="md:w-2/3">
                                    <input type="number" id="points" v-model="form.points" placeholder="Points de l'équipe" required class=" text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                                </div>
                            </div>

                            <div class="md:flex md:items-center mb-3">
                                <div class="md:w-1/3">
                                    <label for="losses" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                        Défaite(s) :
                                    </label>
                                </div>
                                <div class="md:w-2/3">
                                    <input type="number" id="losses" v-model="form.losses" placeholder="Défaites de l'équipe" required class=" text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                                </div>
                            </div>

                            <div class="md:flex md:items-center mb-3">
                                <div class="md:w-1/3">
                                    <label for="budget" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                        Budget :
                                    </label>
                                </div>
                                <div class="md:w-2/3">
                                    <input type="number" id="budget" v-model="form.budget" placeholder="Budget de l'équipe" required class=" text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                                </div>
                            </div>

                            <div class="md:flex md:items-center mb-3">
                                <div class="md:w-1/3">
                                    <label for="draws" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                        Matchs nul(s) :
                                    </label>
                                </div>
                                <div class="md:w-2/3">
                                    <input type="number" id="draws" v-model="form.draws" placeholder="Matchs nuls de l'équipe" required class="text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                                </div>
                            </div>

                            <div class="md:flex md:items-center mb-3">
                                <div class="md:w-1/3">
                                    <label for="logo_path" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                        Logo :
                                    </label>
                                </div>
                                <div class="md:w-2/3">
                                    <input type="file" name="logo_path" id="logo_path" ref="logoInput" @change="handleLogoChange" class="hidden">
                                    <img :src="form.logo_path || 'default-image-path.jpg'" alt="Logo de l'équipe" class="rounded-lg cursor-pointer w-40" @click="uploadLogo">
                                </div>
                            </div>

                            <div class="md:flex md:items-center mb-3">
                                <div class="md:w-1/3">
                                    <label for="description" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                        Description :
                                    </label>
                                </div>
                                <div class="md:w-2/3">
                                    <textarea id="description" v-model="form.description" class="p-2 w-72 h-24 text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-lg" placeholder="Description de l'équipe"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-around p-6">
                            <button type="submit" class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2">Creation</button>
                            <Link :href="route('dataBaseMenu')" class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-2 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2">
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
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref } from 'vue';
import { defineProps } from 'vue';
import { reactive } from "vue";

//Component
import H2 from '@/Components/H2.vue';

const form = reactive({
    name: '',
    logo_path: null,
    budget: '',
    points: '',
    wins: '',
    draws: '',
    losses: '',
    description: ''
});
const logoInput = ref(null);

function handleLogoChange(event) {
    form.logo_path = event.target.files[0];
}

function uploadLogo() {
    logoInput.value.click();
}

function submit() {
    const formData = new FormData();

    // Ajoutez tous les champs du formulaire à formData
    for (const key in form) {
        formData.append(key, form[key]);
    }

    Inertia.post(route('teams.store'), formData, {
        // Indiquez à Inertia de traiter cela comme un formulaire avec un fichier
        // (ceci est une option spécifique d'Inertia pour gérer les fichiers)
        asFormData: true,
    });
}

function goToCreateTeam() {
    Inertia.visit(route('teams.create'));
}
</script>

<style scoped>
/* ... Vos styles restent les mêmes, sauf si vous souhaitez ajouter ou modifier quelque chose spécifiquement pour cette vue ... */
</style>
