<template>
    <Head :title="`Ajouter un joueur (partie #${gameSave.id})`" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Ajouter un joueur à la partie</H2>
        </template>

        <div class="p-4">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-6">Création (partie)</h1>
            </div>

            <!-- LAYOUT FLEX -->
            <div class="flex flex-col md:flex-row">

                <!-- IMAGE / ILLUSTRATION -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat bg-[url('/images/Takeshi_Sawada.webp')]"
                ></div>

                <!-- FORM -->
                <div class="basis-full md:basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-lg mx-0 md:mx-6 bg-white">
                    <form @submit.prevent="submit">

                        <PlayerFormFields
                            :form="form"
                        />

                        <!-- Techniques spéciales -->
                        <div class="m-3">
                            <SpecialMovesEditor v-model="form.special_moves" :errors="form.errors" />
                        </div>

                        <!-- ACTIONS -->
                        <div class="flex justify-around p-6">
                            <ButtonGroup>
                                <ButtonPrimary :disabled="form.processing">
                                    Créer le joueur
                                </ButtonPrimary>
                            </ButtonGroup>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import H1 from '@/Components/H1.vue'
import H2 from '@/Components/H2.vue'
import FormContainer from '@/Components/FormContainer.vue'
import PlayerFormFields from '@/Components/PlayerFormFields.vue'
import SpecialMovesEditor from '@/Components/SpecialMovesEditor.vue'
import ButtonGroup from "@/Components/ButtonGroup.vue";
import ButtonPrimary from "@/Components/ButtonPrimary.vue";

const props = defineProps({
    gameSave: Object,
})

const form = useForm({
    firstname: '',
    lastname: '',
    position: '',
    secondary_positions: [],
    cost: 0,
    description: '',
    photo: null,
    speed: 0,
    attack: 0,
    defense: 0,
    stamina: 0,
    shot: 0,
    pass: 0,
    dribble: 0,
    block: 0,
    intercept: 0,
    tackle: 0,
    heading: 0,
    hand_save: 0,
    punch_save: 0,
    special_moves: [],
})

function submit() {
    form.post(
        route('game-saves.players.store', { gameSave: props.gameSave.id }),
        { forceFormData: true }
    )
}
</script>
