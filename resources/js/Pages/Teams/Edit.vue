<script setup>
import { ref, toRefs } from 'vue';
import Sidebar from './Sidebar.vue';

const props = defineProps(['team', 'allTeams']); // 'allTeams' serait la liste de toutes les équipes pour la sidebar
const team = toRefs(props.team);

const updateTeam = () => {
    Inertia.post('/teams/' + team.id + '/update', team);
    // Remarque: L'URL exacte dépend de votre configuration de routage. 
    // Ajustez si nécessaire.
};
</script>

<template>
    <div class="wrapper">   
        <div class="sidebar">
            <Sidebar :teams="allTeams" />
        </div>
        <div class="content">
            <h1>Édition de l'équipe : {{ team.name }}</h1>

            <form @submit.prevent="updateTeam">
                <div>
                    <label for="name">Nom de l'équipe:</label>
                    <input v-model="team.name" id="name" />
                </div>

                <div>
                    <label for="logo_path">Chemin du logo:</label>
                    <input v-model="team.logo_path" id="logo_path" />
                </div>

                <div>
                    <label for="budget">Budget:</label>
                    <input type="number" v-model="team.budget" id="budget" />
                </div>

                <!-- Vous pouvez continuer avec les autres champs de la même manière -->

                <button type="submit">Mettre à jour</button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.wrapper {
    display: flex;
}

.sidebar {
    width: 200px;
}

.content {
    flex: 1;
    padding: 1rem;
    margin-bottom: 2rem;
}

.content h1 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
    color: #333;
}

.content form {
    max-width: 600px;
    margin: 0 auto;
}

.content div {
    margin-bottom: 1rem;
}

.content label {
    display: block;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.content input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
}

.content button {
    padding: 0.5rem 1rem;
    background-color: #007BFF;
    color: #FFF;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.content button:hover {
    background-color: #0056b3;
}
</style>
