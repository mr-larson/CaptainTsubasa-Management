<template>
    <div class="wrapper">
        <div class="sidebar">
            <Sidebar :teams="allTeams" />
        </div>
        <div class="content">
            <h1 class="p-2">Édition de l'équipe : {{ form.data.name }}</h1>

            <div class="flex">
                <form @submit.prevent="updateTeam" class="flex flex-wrap">
                    <div class="flex-1">
                        <div v-if="form.data.logo_path" class="w-40 h-40 rounded overflow-hidden mb-4">
                            <img :src="form.data.logo_path" alt="Logo de l'équipe" class="object-cover" />
                        </div>
                        <label for="logo_upload">Télécharger un nouveau logo :</label>
                        <input type="file" id="logo_upload" @change="uploadLogo" />
                    </div>
                    <div class="flex-1">
                        <div>
                            <label for="name">Nom de l'équipe:</label>
                            <input v-model="form.data.name" id="name" />
                            <div v-if="form.errors.name">{{ form.errors.name[0] }}</div>
                        </div>
                        <div>
                            <label for="budget">Budget:</label>
                            <input v-model="form.data.budget" id="budget" />
                            <div v-if="form.errors.budget">{{ form.errors.budget[0] }}</div>
                        </div>
                    </div>
                    <div class="w-full mt-4">
                        <button type="submit" :disabled="form.processing">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, toRefs } from 'vue';
import { useForm } from '@inertiajs/inertia-vue3';
import Sidebar from './Sidebar.vue';

const props = defineProps(['team', 'allTeams']);
const { name, logo_path, budget } = toRefs(props.team);

const form = useForm({
    name: name.value,
    logo_path: logo_path.value,
    budget: budget.value
});

const logoFile = ref(null);

const uploadLogo = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            form.data.logo_path = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const updateTeam = () => {
    const formData = new FormData();
    formData.append('name', form.data.name);
    formData.append('budget', form.data.budget);
    if (logoFile.value) {
        formData.append('logo', logoFile.value);
    }

    form.post('/route-to-update-team', formData);
};
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
