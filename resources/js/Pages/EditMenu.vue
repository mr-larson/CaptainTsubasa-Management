<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

//Component
import H2 from '@/Components/H2.vue';

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Dashboard</H2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        You're logged in!
                        <div class="mt-4">
                            <InertiaLink href="{{ route('dashboard') }}" class="underline text-sm text-gray-600 hover:text-gray-900">
                                Dashboard
                            </InertiaLink>
                        </div>
                        <div class="mt-4">
                            <InertiaLink href="{{ route('profile.show') }}" class="underline text-sm text-gray-600 hover:text-gray-900">
                                Profile
                            </InertiaLink>
                        </div>
                        <div class="mt-4">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">
                                    Logout
                                </button>
                            </form>  
                        </div>                      
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Si vous avez des styles spécifiques pour ces boutons, ajoutez-les ici. */
</style>
