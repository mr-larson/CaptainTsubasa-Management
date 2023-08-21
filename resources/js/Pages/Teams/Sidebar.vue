<template>
    <div class="sidebar">
        <ul>
            <li v-for="team in teams" :key="team.id" @click="selectTeam(team)">
                {{ team.name }}
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    props: {
        teams: Array
    },
    methods: {
        selectTeam(team) {
            this.$inertia.visit(`/teams/${team.id}/edit`);
        }
    }
}
</script>

<style scoped>
/* Vos styles pour la sidebar */
.sidebar {
    width: 200px;
    height: 100vh;
    background-color: #333;
    color: #fff;
    padding: 1rem;
}

.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar ul li {
    padding: 0.5rem 0;
    cursor: pointer;
}

.sidebar ul li:hover {
    background-color: #555;
}

.sidebar ul li.active {
    background-color: #777;
}

.sidebar ul li.active:hover {
    background-color: #777;
}
</style>
