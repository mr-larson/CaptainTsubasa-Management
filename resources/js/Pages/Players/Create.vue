<template>
    <Head title="Add Player" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Add Player</H2>
        </template>

        <div class="p-4">
            <!-- Titre -->
            <div class="flex justify-center mb-6">
                <h1 class="text-3xl font-bold text-slate-600">
                    Création d'un joueur
                </h1>
            </div>

            <div class="flex flex-row">
                <!-- Visuel gauche -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/Mamoru_Izawa_(Shutetsu_ES-SR-Tq)_Full.webp')"
                ></div>

                <!-- Carte formulaire -->
                <div class="basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-lg mx-6 bg-white">
                    <form @submit.prevent="submit">
                        <!-- Ligne 1 : prénom / nom -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="firstname" value="Prénom" />
                                <InputText
                                    id="firstname"
                                    type="text"
                                    class="mt-1 w-full"
                                    v-model="form.firstname"
                                />
                                <p v-if="form.errors.firstname" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.firstname }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="lastname" value="Nom" />
                                <InputText
                                    id="lastname"
                                    type="text"
                                    class="mt-1 w-full"
                                    v-model="form.lastname"
                                />
                                <p v-if="form.errors.lastname" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.lastname }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 2 : âge / position -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="age" value="Âge" />
                                <InputText
                                    id="age"
                                    type="number"
                                    class="mt-1 w-full"
                                    v-model="form.age"
                                />
                                <p v-if="form.errors.age" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.age }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="position" value="Position" />
                                <InputSelect
                                    id="position"
                                    v-model="form.position"
                                    class="mt-1"
                                    placeholder="Sélectionne une position"
                                >
                                    <option disabled value="">
                                        -- Sélectionne une position --
                                    </option>
                                    <option
                                        v-for="pos in positions"
                                        :key="pos"
                                        :value="pos"
                                    >
                                        {{ positionLabels[pos] ?? pos }}
                                    </option>
                                </InputSelect>
                                <p v-if="form.errors.position" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.position }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 3 : coût par match -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="cost" value="Coût par match" />
                                <InputText
                                    id="cost"
                                    type="number"
                                    class="mt-1 w-full"
                                    v-model="form.cost"
                                />
                                <p v-if="form.errors.cost" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.cost }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 4 : stats attaque / défense -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="stats.attack" value="Attaque" />
                                <InputText
                                    id="stats.attack"
                                    type="number"
                                    class="mt-1 w-full"
                                    v-model="form.stats.attack"
                                />
                                <p v-if="form.errors['stats.attack']" class="text-sm text-red-600 mt-1">
                                    {{ form.errors['stats.attack'] }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="stats.defender" value="Défense" />
                                <InputText
                                    id="stats.defender"
                                    type="number"
                                    class="mt-1 w-full"
                                    v-model="form.stats.defender"
                                />
                                <p v-if="form.errors['stats.defender']" class="text-sm text-red-600 mt-1">
                                    {{ form.errors['stats.defender'] }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 5 : stats vitesse / stamina -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel for="stats.speed" value="Vitesse" />
                                <InputText
                                    id="stats.speed"
                                    type="number"
                                    class="mt-1 w-full"
                                    v-model="form.stats.speed"
                                />
                                <p v-if="form.errors['stats.speed']" class="text-sm text-red-600 mt-1">
                                    {{ form.errors['stats.speed'] }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel for="stats.stamina" value="Stamina" />
                                <InputText
                                    id="stats.stamina"
                                    type="number"
                                    class="mt-1 w-full"
                                    v-model="form.stats.stamina"
                                />
                                <p v-if="form.errors['stats.stamina']" class="text-sm text-red-600 mt-1">
                                    {{ form.errors['stats.stamina'] }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Boutons -->
                        <div class="flex justify-around p-6">
                            <button
                                type="submit"
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Création
                            </button>

                            <Link
                                :href="route('players.index')"
                                class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-2 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2"
                            >
                                Retour
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// Components
import H2 from '@/Components/H2.vue';
import FormContainer from '@/Components/FormContainer.vue';
import FormRaw from '@/Components/FormRaw.vue';
import FormCol from '@/Components/FormCol.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import InputSelect from '@/Components/InputSelect.vue';

const props = defineProps({
    positions: {
        type: Array,
        required: true,
    },
    positionLabels: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    firstname: '',
    lastname: '',
    age: '',
    position: '',
    cost: '',
    stats: {
        attack: 0,
        defender: 0,
        speed: 0,
        stamina: 0,
    },
    description: '',
});

function submit() {
    form.post(route('players.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}
</script>
