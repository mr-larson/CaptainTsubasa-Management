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
                <div class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat" style="background-image: url('/images/wakabayashi.webp')">

                </div>

                <div class="basis-2/3 p-4 border border-slate-300 rounded-lg mx-6 bg-white">
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="name" value="Name" />
                                <InputText
                                    id="name"
                                    type="text"
                                    class="mt-1  w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                />
                            </FormCol>
                            <FormCol>
                                <InputLabel for="budget" value="Budget" />
                                <InputText type="number" id="budget" v-model="form.budget" placeholder="Budget de l'équipe"
                                           required
                                           class="mt-1 w-full"
                                           autofocus
                                           autocomplete="budget"
                                />
                            </FormCol>
                        </FormRaw>
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="wins" value="Victoire(s)" />
                                <InputText type="number" id="wins" v-model="form.wins" placeholder="Victoire(s) de l'équipe"
                                           required
                                           class="mt-1 w-full"
                                           autofocus
                                           autocomplete="wins"
                                />
                            </FormCol>
                            <FormCol>
                                <InputLabel for="losses" value="Défaite(s)" />
                                <InputText type="number" id="losses" v-model="form.losses" placeholder="Défaite(s) de l'équipe"
                                           required
                                           class="mt-1 w-full"
                                           autofocus
                                           autocomplete="losses"
                                />
                            </FormCol>
                        </FormRaw>
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="draws" value="Matchs nuls" />
                                <InputText type="number" id="draws" v-model="form.draws" placeholder="Matchs nuls de l'équipe"
                                           required
                                           class="mt-1 w-full"
                                           autofocus
                                           autocomplete="draws"
                                />
                            </FormCol>
                        </FormRaw>

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="budget" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Budget</label>
                            <input type="number" id="budget" v-model="form.budget" placeholder="Budget de l'équipe" required class="text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <div class="flex justify-around p-6">
                            <button type="submit" class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2">Création</button>
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
import FormRaw from "@/Components/FormRaw.vue";
import FormCol from "@/Components/FormCol.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from "@/Components/InputText.vue";

const form = reactive({
    name: '',
    budget: '',
    wins: '',
    losses: '',
    draws: '',
});

function submit() {
    const formData = new FormData();

    for (const key in form) {
        formData.append(key, form[key]);
    }

    Inertia.post(route('teams.store'), formData, {
        asFormData: true,
    });
}

function goToCreateTeam() {
    Inertia.visit(route('teams.create'));
}
</script>

<style scoped>

</style>
