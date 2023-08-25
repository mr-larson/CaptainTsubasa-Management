<template>
    <div class="wrapper">
        <div class="sidebar">
            <Sidebar :teams="allTeams" />
        </div>
        <div class="content">
            <h1 class="p-2">Édition de l'équipe : {{ form.data.name }}</h1>

            <div class="flex">

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, toRefs } from 'vue';
import { useForm } from '@inertiajs/inertia-vue3';
import Sidebar from './Sidebar.vue';

const props = defineProps({
    teams: {
        type: Array,
        required: true
    }
});
</script>


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
    padding: 0 2em;
    margin: 1em auto;
}

.content div {
    margin-bottom: 1rem;
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
