<template>
    <Head :title="`Modifier ${player.firstname} ${player.lastname} (partie #${gameSave.id})`" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Modifier un joueur de la partie</H2>
        </template>

        <div class="p-4">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-6">
                    Édition — {{ player.firstname }} {{ player.lastname }}
                </h1>
            </div>

            <!-- LAYOUT FLEX -->
            <div class="flex flex-col md:flex-row">

                <!-- IMAGE / ILLUSTRATION -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat bg-[url('/images/Takeshi_Sawada.webp')]"
                ></div>

                <!-- FORM -->
                <div class="basis-full md:basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-xl shadow-sm mx-0 md:mx-6 bg-white">
                    <form @submit.prevent="submit">

                        <PlayerFormFields
                            :form="form"
                            :photo-url="player.photo_path && !form.remove_photo ? `/storage/${player.photo_path}` : null"
                            :removable-photo="!!player.photo_path"
                        />

                        <!-- Techniques spéciales -->
                        <div class="m-3">
                            <SpecialMovesEditor v-model="form.special_moves" :errors="form.errors" />
                        </div>

                        <!-- ACTIONS -->
                        <div class="flex justify-around p-6">
                            <ButtonGroup>
                                <ButtonPrimary :disabled="form.processing">
                                    Enregistrer les modifications
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
import { Head, useForm } from '@inertiajs/vue3'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import H2 from '@/Components/H2.vue'
import PlayerFormFields from '@/Components/PlayerFormFields.vue'
import SpecialMovesEditor from '@/Components/SpecialMovesEditor.vue'
import ButtonGroup from "@/Components/ButtonGroup.vue";
import ButtonPrimary from "@/Components/ButtonPrimary.vue";

const props = defineProps({
    gameSave: Object,
    player: Object,
})

const form = useForm({
    firstname: props.player.firstname ?? '',
    lastname: props.player.lastname ?? '',
    position: props.player.position ?? '',
    secondary_positions: [...(props.player.secondary_positions ?? [])], // copie : ne pas muter la prop
    cost: props.player.cost ?? 0,
    description: props.player.description ?? '',
    photo: null,
    remove_photo: false,
    speed: props.player.speed ?? 0,
    attack: props.player.attack ?? 0,
    defense: props.player.defense ?? 0,
    stamina: props.player.stamina ?? 0,
    shot: props.player.shot ?? 0,
    pass: props.player.pass ?? 0,
    dribble: props.player.dribble ?? 0,
    block: props.player.block ?? 0,
    intercept: props.player.intercept ?? 0,
    tackle: props.player.tackle ?? 0,
    heading: props.player.heading ?? 0,
    hand_save: props.player.hand_save ?? 0,
    punch_save: props.player.punch_save ?? 0,
    special_moves: JSON.parse(JSON.stringify(props.player.special_moves ?? [])), // copie : ne pas muter la prop
})

function submit() {
    form.transform(data => ({
        ...data,
        _method: 'PUT',
    })).post(
        route('game-saves.players.update', {
            gameSave: props.gameSave.id,
            player: props.player.id,
        }),
        { forceFormData: true }
    )
}
</script>
