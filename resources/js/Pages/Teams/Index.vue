<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const teams = ref([]);

onMounted(async () => {
    try {
        const response = await axios.get('/api/teams');
        teams.value = response.data;
    } catch (error) {
        console.error("Erreur lors de la récupération des équipes:", error);
    }
});
</script>

<template>
    <Head title="Teams" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Teams</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto w-full">
                        <table class="min-w-full border divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wins</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Losses</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Draws</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Stats Bonus</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Cards</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="team in teams" :key="team.id">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ team.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><img :src="team.logo" :alt="team.name" class="h-20 w-20 rounded-full"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ team.budget }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ team.points }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ team.wins }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ team.losses }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ team.draws }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ team.team_stats_bonus }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ team.active_cards }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
