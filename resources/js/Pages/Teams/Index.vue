<template>
    <Head title="Teams" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Teams</H2>
        </template>

        <PageWrapper>
            <CardContainer>
                <TableContainer>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Nom</TableHeaderCell>
                                <TableHeaderCell>Logo</TableHeaderCell>
                                <TableHeaderCell>Budget</TableHeaderCell>
                                <TableHeaderCell>Points</TableHeaderCell>
                                <TableHeaderCell>Wins</TableHeaderCell>
                                <TableHeaderCell>Losses</TableHeaderCell>
                                <TableHeaderCell>Draws</TableHeaderCell>
                                <TableHeaderCell>Actions</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="team in teams" :key="team.id">
                                <TableCell>{{ team.name }}</TableCell>
                                <TableCell><img :src="team.logo ? `/storage/${team.logo}`: `https://ui-avatars.com/api/?name=${team.name}&color=7F9CF5&background=EBF4FF`" :alt="team.name" customClass="h-20 w-20 rounded-full"></TableCell>
                                <TableCell>{{ team.budget }} €</TableCell>
                                <TableCell>{{ team.points }}</TableCell>
                                <TableCell>{{ team.wins }}</TableCell>
                                <TableCell>{{ team.losses }}</TableCell>
                                <TableCell>{{ team.draws }}</TableCell>
                                <TableCell>
                                    <a :href="`/teams/${team.id}`" class="text-indigo-600 hover:text-indigo-900">Show</a>
                                    <!-- create -->

                                    <a :href="`/teams/${team.id}/edit`" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    <!-- Ici, nous utilisons l'événement @click pour déclencher la fonction deleteTeam -->
                                    <button @click="deleteTeam(team.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </TableContainer>
            </CardContainer>
        </PageWrapper>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

//Component
import H2 from '@/Components/H2.vue';
import PageWrapper from '@/Components/PageWrapper.vue';
import CardContainer from '@/Components/CardContainer.vue';
import TableContainer from '@/Components/TableContainer.vue';
import Table from '@/Components/Table.vue';
import TableHead from '@/Components/TableHead.vue';
import TableBody from '@/Components/TableBody.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableCell from '@/Components/TableCell.vue';


const teams = ref([]);

onMounted(async () => {
    try {
        const response = await axios.get('/api/teams');
        teams.value = response.data;
    } catch (error) {
        console.error("Erreur lors de la récupération des équipes:", error);
    }
});

// Ici, nous créons une nouvelle équipe avec les valeurs par défaut
const newTeam = ref({
    name: '',
    logo_path: '', // Cela doit correspondre à la définition dans votre modèle
    budget: 0,
    points: 0,
    wins: 0,
    draws: 0,
    losses: 0,
    team_stats_bonus: {}, // N'oubliez pas d'ajouter ceci
    active_cards: {} // et ceci aussi
});

const createTeam = async () => {
    try {
        const response = await axios.post('/api/teams', newTeam.value);
        teams.value.push(response.data.team);  // Ajoutez la nouvelle équipe à la liste
        alert('Équipe créée avec succès!');
    } catch (error) {
        console.error('Erreur lors de la création:', error);
        alert('Erreur lors de la création de l\'équipe.');
    }
};

// Fonction pour supprimer une équipe
const deleteTeam = async (teamId) => {
    if (window.confirm('Voulez-vous vraiment supprimer cette équipe?')) {
        try {
            await axios.delete(`/api/teams/${teamId}`);
            teams.value = teams.value.filter(team => team.id !== teamId);
            alert('Équipe supprimée avec succès!');
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            alert('Erreur lors de la suppression de l\'équipe.');
        }
    }
};

</script>
