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


</script>

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
                                <TableHeaderCell>Team Stats Bonus</TableHeaderCell>
                                <TableHeaderCell>Team Stats Malus</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="team in teams" :key="team.id">
                                <TableCell>{{ team.name }}</TableCell>
                                <TableCell><img :src="team.logo" :alt="team.name" customClass="h-20 w-20 rounded-full"></TableCell>
                                <TableCell>{{ team.budget }} €</TableCell>
                                <TableCell>{{ team.points }}</TableCell>
                                <TableCell>{{ team.wins }}</TableCell>
                                <TableCell>{{ team.losses }}</TableCell>
                                <TableCell>{{ team.draws }}</TableCell>
                                <TableCell>{{ team.team_stats_bonus }}</TableCell>
                                <TableCell>{{ team.active_cards }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </TableContainer>
            </CardContainer>
        </PageWrapper>

    </AuthenticatedLayout>
</template>
