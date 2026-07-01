<template>
    <Head title="Add Player" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Add Player</H2>
        </template>

        <div class="p-4">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-6">Création de joueur</h1>
            </div>

            <div class="flex flex-col md:flex-row">
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/tsubasa.webp')"
                ></div>

                <div class="basis-full md:basis-2/3 p-4 border border-slate-300 rounded-lg mx-0 md:mx-6 bg-white">
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
                                    autocomplete="given-name"
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
                                    autocomplete="family-name"
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
                                <InputText
                                    id="position"
                                    type="text"
                                    class="mt-1 w-full"
                                    v-model="form.position"
                                />
                                <p v-if="form.errors.position" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.position }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 3 : coût -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Coût par match (€)" />
                                <InputText type="number" class="mt-1 w-full" v-model="form.cost" required />
                                <p v-if="form.errors.cost" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.cost }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 4 : stats attaque / défense -->
                        <FormRaw>
                            <FormCol>
                                <label
                                    for="stats.attack"
                                    class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                                >
                                    Attaque
                                </label>
                                <input
                                    type="number"
                                    id="stats.attack"
                                    v-model="form.stats.attack"
                                    placeholder="Attaque"
                                    class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                                >
                                <p v-if="form.errors['stats.attack']" class="text-sm text-red-600 mt-1">
                                    {{ form.errors['stats.attack'] }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <label
                                    for="stats.defender"
                                    class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                                >
                                    Défense
                                </label>
                                <input
                                    type="number"
                                    id="stats.defender"
                                    v-model="form.stats.defender"
                                    placeholder="Défense"
                                    class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                                >
                                <p v-if="form.errors['stats.defender']" class="text-sm text-red-600 mt-1">
                                    {{ form.errors['stats.defender'] }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 5 : stats speed / stamina -->
                        <FormRaw>
                            <FormCol>
                                <label
                                    for="stats.speed"
                                    class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                                >
                                    Vitesse
                                </label>
                                <input
                                    type="number"
                                    id="stats.speed"
                                    v-model="form.stats.speed"
                                    placeholder="Vitesse"
                                    class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                                >
                                <p v-if="form.errors['stats.speed']" class="text-sm text-red-600 mt-1">
                                    {{ form.errors['stats.speed'] }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <label
                                    for="stats.stamina"
                                    class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                                >
                                    Stamina
                                </label>
                                <input
                                    type="number"
                                    id="stats.stamina"
                                    v-model="form.stats.stamina"
                                    placeholder="Stamina"
                                    class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                                >
                                <p v-if="form.errors['stats.stamina']" class="text-sm text-red-600 mt-1">
                                    {{ form.errors['stats.stamina'] }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <div class="flex justify-around p-6">
                            <button
                                type="submit"
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Création
                            </button>

                            <Link
                                :href="route('players')"
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
import FormRaw from '@/Components/FormRaw.vue';
import FormCol from '@/Components/FormCol.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';

const form = useForm({
    firstname: '',
    lastname: '',
    age: '',
    position: '',
    cost: '',
    stats: {
        speed: 0,
        attack: 0,
        defender: 0,
        stamina: 0,
    },
});

function submit() {
    form.post(route('players.store'), {
        onSuccess: () => {
            // si tu veux reset:
            // form.reset();
        },
    });
}
</script>
