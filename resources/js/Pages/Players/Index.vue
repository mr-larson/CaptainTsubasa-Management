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


const players = ref([]);

onMounted(async () => {
    try {
        const response = await axios.get('/api/players');
        players.value = response.data;
    } catch (error) {
        console.error("Erreur lors de la récupération des joueurs:", error);
    }
});


</script>

<template>
    <Head title="Players" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Players</H2>
        </template>

        <PageWrapper>
            <CardContainer>
                <TableContainer>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Name</TableHeaderCell>
                                <TableHeaderCell>First name</TableHeaderCell>
                                <TableHeaderCell>Image</TableHeaderCell>
                                <TableHeaderCell>Nationality</TableHeaderCell>
                                <TableHeaderCell>Birth date</TableHeaderCell>
                                <TableHeaderCell>Team</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="player in players" :key="player.id">
                                <TableCell>{{ player.name }}</TableCell>
                                <TableCell>{{ player.first_name }}</TableCell>
                                <TableCell>
                                    <img :src="player.image ? `/storage/${player.image}`: `https://ui-avatars.com/api/?name=${player.name}&color=7F9CF5&background=EBF4FF`" :alt="player.name" customClass="h-20 w-20 rounded-full">
                                </TableCell>
                                <TableCell>{{ player.nationality }}</TableCell>
                                <TableCell>{{ player.birth_date }}</TableCell>
                                
                            </TableRow>
                        </TableBody>
                    </Table>
                </TableContainer>
            </CardContainer>
        </PageWrapper>

    </AuthenticatedLayout>
</template>
