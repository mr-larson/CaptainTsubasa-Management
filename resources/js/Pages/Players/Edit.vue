<template>
    <Head title="Players"/>

    <AuthenticatedLayout>
        <template #header>
            <H2>Players</H2>
        </template>

        <aside id="separator-sidebar"
               class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
               aria-label="Sidebar">
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 mb-3 border-b text-center text-gray-200">
                    <H2>Joueurs</H2>
                </div>
                <div class="mb-2">
                    <form>
                        <label for="default-search"
                               class="mb-2 text-sm font-medium text-gray-900 sr-only">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" id="default-search" v-model="searchQuery"
                                   class="block w-full p-1 pl-10 text-sm text-gray-900 border border-gray-300  bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded"
                                   placeholder="Recherche" required>
                        </div>
                    </form>
                </div>
                <ul class="space-y-1 font-medium">
                    <li v-for="player in filteredPlayers" :key="player.id" @click="selectPlayer(player)">
                        <a href="#"
                           :class="{'bg-slate-500': form.selectedPlayerId === player.id}"
                           class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500">
                            <span class="ml-3">{{ player.name }}</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-1 flex">
                        <Link :href="route('players.create')"
                              class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center">
                            Créer une joueur
                        </Link>
                    </li>
                    <li class="pt-2 flex">
                        <Link :href="route('dataBaseMenu')"
                              class="bg-slate-500 hover:bg-slate-600 border border-slate-300 shadow-gray-100	text-white p-1 w-full rounded text-center">
                            Retour
                        </Link>
                    </li>
                </ul>
            </div>
        </aside>

        <div class="p-4 sm:ml-64">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-4">Editions</h1>
            </div>
            <div class="p-4 border border-slate-300 rounded-lg mx-6 bg-white">
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="flex flex-col md:grid lg:grid-cols-3 gap-4 text-slate-700">

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="name"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Nom</label>
                            <input type="text" id="name" v-model="form.name" placeholder="Nom du joueur" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="first_name"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Prénom</label>
                            <input type="text" id="first_name" v-model="form.first_name" placeholder="Prénom du joueur" required
                                      class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- image_path -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="image_path"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Image</label>
                            <input type="text" id="image_path" v-model="form.image_path" placeholder="Chemin vers l'image" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- nationality -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="nationality"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Nationalité</label>
                            <input type="text" id="nationality" v-model="form.nationality" placeholder="Nationalité" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- birth_date -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="birth_date"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Date de naissance</label>
                            <input type="date" id="birth_date" v-model="form.birth_date" placeholder="Date de naissance" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- height -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="height"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Taille</label>
                            <input type="number" id="height" v-model="form.height" placeholder="Taille" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- weight -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="weight"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Poids</label>
                            <input type="number" id="weight" v-model="form.weight" placeholder="Poids" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- favorite_number -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="favorite_number"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Numéro favori</label>
                            <input type="number" id="favorite_number" v-model="form.favorite_number" placeholder="Numéro favori" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- period -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="period"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Période</label>
                            <select id="period" v-model="form.period" required
                                    class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                                <option value="" disabled selected>Choisir une période</option>
                                <option v-for="periods in period" :value="periods.id">
                                    {{ periods.name }}
                                </option>
                            </select>
                        </div>

                        <!-- positions -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="positions"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Positions</label>
                            <select id="positions" v-model="form.positions" required class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight h-10 focus:outline-none focus:bg-white focus:border-purple-300">
                                <option value="" disabled selected>Choisir une position</option>
                                <option v-for="position in positions" :value="position.id">
                                    {{ position.name }}
                                </option>
                            </select>
                        </div>


                        <!-- injury_risk -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="injury_risk"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Risque de blessure</label>
                            <input type="text" id="injury_risk" v-model="form.injury_risk" placeholder="Risque de blessure" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- is_injured -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="is_injured"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Est blessé ?</label>
                            <input type="checkbox" id="is_injured" v-model="form.is_injured"
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- stats -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="stats.physique" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Physique</label>
                            <input type="number" id="stats.strength" v-model="form.stats.strength" placeholder="Physique (0-100)" min="0" max="100"
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- Répétez pour les autres statistiques -->


                        <!-- special_skills -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="special_skills"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Compétences spéciales</label>
                            <input type="text" id="special_skills" v-model="form.special_skills" placeholder="Compétences spéciales en JSON"
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- special_moves -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="special_moves"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Mouvements spéciaux</label>
                            <input type="text" id="special_moves" v-model="form.special_moves" placeholder="Mouvements spéciaux en JSON"
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- cost -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="cost"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Coût</label>
                            <input type="number" id="cost" v-model="form.cost" placeholder="Coût" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- current_contract_duration -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="current_contract_duration"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Durée du contrat actuel</label>
                            <input type="number" id="current_contract_duration" v-model="form.current_contract_duration" placeholder="Durée du contrat actuel"
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- fatigue -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="fatigue"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Fatigue</label>
                            <input type="number" id="fatigue" v-model="form.fatigue" placeholder="Fatigue" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- weather_bonus -->
                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="weather_bonus"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Bonus météo</label>
                            <input type="text" id="weather_bonus" v-model="form.weather_bonus" placeholder="Bonus météorologique en JSON"
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <!-- description -->
                        <div class="flex items-start m-3 gap-4 md:gap-0">
                            <label for="description" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Description</label>
                            <textarea id="description" v-model="form.description"
                                      class="p-2 w-full h-36 text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-lg"
                                      placeholder="Description du joueur"></textarea>
                        </div>

                    </div>

                    <div class="flex justify-around pt-8">
                        <button type="submit"
                                class="w-36 bg-sky-300 hover:bg-sky-400 text-center py-1 border-2 border-sky-500 rounded-full drop-shadow-md mb-2">
                            Mettre à jour
                        </button>
                        <button type="button" @click="deletePlayer"
                                class="w-36 bg-rose-300 hover:bg-rose-400 text-rose-950 text-center py-1 border-2 border-rose-500 rounded-full drop-shadow-md mb-2">
                            Supprimer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import {Inertia} from '@inertiajs/inertia';
import {ref, defineProps, reactive, onMounted, computed} from 'vue';

//Component
import H2 from '@/Components/H2.vue';


// Propriétés du composant
const props = defineProps({
    players: {
        type: Array,
        required: true
    }
});

// Positions
const positions = ref([
    {id: 1, name: 'Gardien'},
    {id: 2, name: 'Défenseur'},
    {id: 3, name: 'Milieu'},
    {id: 4, name: 'Attaquant'},
]);

// Périodes
const period = ref(
    [
        {id: 1, name: 'collège'},
        {id: 2, name: 'lycée'},
        {id: 3, name: 'pro'},
    ]
)



// Réactif pour le formulaire d'édition
const form = reactive({
    selectedPlayerId: null,
    id: '',
    name: '',
    first_name: '',
    image_path:'',
    nationality: '',
    birth_date: '',
    height: '',
    weight: '',
    favorite_number: '',
    injury_risk: '',
    is_injured: false,
    cost: '',
    weather_bonus: '',
    fatigue: '',
    positions: [],
    period: [],
    description: '',
    stats: {
        speed: '',
        strength: '',
        endurance: '',
        technique: '',
        dexterity: '',
        attack: '',
        defense: '',
        goalkeeper: '',
        pass: '',
        shoot: '',
        header: '',
        dribble: '',
        tackle: '',
        interception: '',
        blockage: '',
        catch: '',
        punch: '',
    },
});

// Sélectionner une joueur dès le chargement si des joueurs existent
onMounted(() => {
    if (props.players.length > 0) {
        selectPlayer(props.players[0]);
    }
});

// Réactif pour la barre de recherche
const searchQuery = ref("");

const filteredPlayers = computed(() => {
    if (!searchQuery.value) return props.players;
    return props.players.filter(player => player.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});


const logoInput = ref(null);

// Fonction pour lancer le sélecteur de fichier du logo
function uploadLogo() {
    if (logoInput.value) {
        logoInput.value.click(
            handleImageUpload.bind(this)
        );
    } else {
        console.warn("Logo input is not yet defined.");
    }
}

function handleImageUpload() {
    form.image = URL.createObjectURL(logoInput.value.files[0]);
}

// Fonction pour mettre à jour le formulaire avec les détails d'une joueur sélectionnée
function selectPlayer(player) {
    form.id = player.id;
    form.name = player.name;
    form.first_name = player.first_name;
    form.image_path = player.image_path;
    form.nationality = player.nationality;
    form.birth_date = player.birth_date;
    form.height = player.height;
    form.weight = player.weight;
    form.favorite_number = player.favorite_number;
    form.injury_risk = player.injury_risk;
    form.is_injured = player.is_injured;
    form.cost = player.cost;
    form.weather_bonus = player.weather_bonus;
    form.fatigue = player.fatigue;
    form.positions = player.positions;
    form.period = player.period;
    form.description = player.description;
    form.selectedPlayerId = player.id;
}

// Fonction pour soumettre le formulaire et mettre à jour l'joueur
function submit() {
    const formData = new FormData();

    for (const key in form) {
        formData.append(key, form[key]);
    }
    // Après une mise à jour réussie
    Inertia.post(route('players.update', form.id), formData, {
        onSuccess: () => {
            // On recharge la page sur le player.id
            Inertia.reload({only: ['players'], data: {player: form.id}});
        }
    });

}

// Fonction pour supprimer une joueur
function deletePlayer() {
    if (confirm(" Voulez-vous vraiment supprimer cette joueur ? ")) {
        Inertia.delete(route('players.destroy', form.id));
    }
}
</script>
