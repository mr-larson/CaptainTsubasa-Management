<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import {Head, Link} from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Profile</H2>
        </template>

        <div class="p-6">
            <!-- Titre principal -->
            <div class="flex justify-center mb-8">
                <h1 class="text-3xl font-bold text-slate-600">
                    Gestion du profil
                </h1>
            </div>

            <div class="flex flex-row gap-6">

                <!-- Image illustrative -->
                <div
                    class="hidden xl:block basis-1/4 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/Teppei_Kisugi_(Shutetsu_ES-SR-Pw)_Full.webp')"
                ></div>

                <!-- CONTENU en deux colonnes -->
                <div class="basis-full xl:basis-3/4 grid grid-cols-1 md:grid-cols-2 gap-4 bg-white shadow border border-slate-200 rounded-lg">

                    <!-- Colonne gauche : Informations personnelles -->
                    <div class="p-6">

                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class=""
                        />

                        <!-- Groupe de boutons correctement espacés -->
                        <div class="flex gap-4 mt-4">
                            <Link
                                :href="route('dataBaseMenu')"
                                class="w-40 h-10 bg-slate-50 hover:bg-slate-300 text-center font-semibold py-1 px-5 border-2 border-slate-500 rounded-full drop-shadow-md"
                            >
                                Retour
                            </Link>
                        </div>

                    </div>

                    <!-- Colonne droite : Mot de passe -->
                    <div class="p-6">
                        <UpdatePasswordForm class="w-full" />
                    </div>

                </div>
            </div>

            <!-- Zone dangereuse en-dessous -->
            <div class="mt-10 mx-auto">
                <div class="p-6 bg-red-50 shadow border border-red-200 rounded-lg">
                    <DeleteUserForm class="w-full" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
