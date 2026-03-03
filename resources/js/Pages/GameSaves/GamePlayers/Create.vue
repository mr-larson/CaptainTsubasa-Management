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
            <div class="flex flex-row">

                <!-- IMAGE / ILLUSTRATION -->
                <div
                    class="hidden md:block basis-1/3 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/Takeshi_Sawada.webp')"
                ></div>

                <!-- FORM -->
                <div class="basis-2/3 min-h-[500px] p-4 border border-slate-300 rounded-lg mx-6 bg-white">
                    <form @submit.prevent="submit">

                        <!-- Ligne 1 : Prénom / Nom -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Prénom" />
                                <InputText v-model="form.firstname" class="mt-1 w-full" />
                                <p v-if="form.errors.firstname" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.firstname }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Nom" />
                                <InputText v-model="form.lastname" class="mt-1 w-full" />
                                <p v-if="form.errors.lastname" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.lastname }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Ligne 2 : Position & Coût -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Position" />
                                <InputSelect v-model="form.position" class="mt-1">
                                    <option disabled value="">Choisir...</option>
                                    <option v-for="p in positions" :key="p" :value="p">
                                        {{ p }}
                                    </option>
                                </InputSelect>
                                <p v-if="form.errors.position" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.position }}
                                </p>
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Coût" />
                                <InputText type="number" v-model="form.cost" class="mt-1 w-full" />
                                <p v-if="form.errors.cost" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.cost }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Photo -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Photo" />

                                <div class="mt-1 flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        <input
                                            ref="photoInput"
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="onPhotoChange"
                                        />

                                        <div class="flex items-center w-full md:w-56">
                                            <button
                                                type="button"
                                                class="shrink-0 px-3 py-1.5 rounded-l-full border border-gray-300 bg-stone-50 text-sm
                                                       text-gray-900 hover:bg-white focus:outline-none focus:border-slate-700"
                                                @click="openPhotoPicker"
                                            >
                                                Choisir
                                            </button>

                                            <div
                                                class="flex-1 px-3 py-1.5 rounded-r-full border border-l-0 border-gray-300 bg-stone-50
                                                       text-sm text-slate-600 truncate"
                                            >
                                                {{ selectedPhotoName || 'Aucun fichier choisi' }}
                                            </div>
                                        </div>

                                        <div class="h-16 w-16 rounded border bg-white overflow-hidden flex items-center justify-center">
                                            <img
                                                v-if="photoPreviewUrl"
                                                :src="photoPreviewUrl"
                                                class="h-full w-full object-cover"
                                            />
                                            <span v-else class="text-xs text-slate-400">Aucune</span>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="form.errors.photo" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.photo }}
                                </p>
                            </FormCol>
                        </FormRaw>

                        <!-- Stats -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Vitesse" />
                                <InputText type="number" v-model="form.speed" class="mt-1 w-full" />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Attaque" />
                                <InputText type="number" v-model="form.attack" class="mt-1 w-full" />
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Défense" />
                                <InputText type="number" v-model="form.defense" class="mt-1 w-full" />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Endurance" />
                                <InputText type="number" v-model="form.stamina" class="mt-1 w-full" />
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Tir" />
                                <InputText type="number" v-model="form.shot" class="mt-1 w-full" />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Passe" />
                                <InputText type="number" v-model="form.pass" class="mt-1 w-full" />
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Dribble" />
                                <InputText type="number" v-model="form.dribble" class="mt-1 w-full" />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Block" />
                                <InputText type="number" v-model="form.block" class="mt-1 w-full" />
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Interception" />
                                <InputText type="number" v-model="form.intercept" class="mt-1 w-full" />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Tacle" />
                                <InputText type="number" v-model="form.tackle" class="mt-1 w-full" />
                            </FormCol>
                        </FormRaw>

                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Arrêt main" />
                                <InputText type="number" v-model="form.hand_save" class="mt-1 w-full" />
                            </FormCol>

                            <FormCol>
                                <InputLabel value="Arrêt poings" />
                                <InputText type="number" v-model="form.punch_save" class="mt-1 w-full" />
                            </FormCol>
                        </FormRaw>

                        <!-- Description -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Description" />
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                ></textarea>
                            </FormCol>
                        </FormRaw>

                        <!-- Special Moves -->
                        <FormRaw>
                            <FormCol>
                                <InputLabel value="Techniques spéciales" />

                                <div class="flex flex-col gap-4 bg-slate-100 p-3 rounded">

                                    <div
                                        v-for="(move, index) in form.special_moves"
                                        :key="index"
                                        class="p-3 rounded border bg-white shadow-sm"
                                    >
                                        <h3 class="font-semibold text-sm mb-2">
                                            Technique #{{ index + 1 }}
                                        </h3>

                                        <div class="mb-2">
                                            <InputLabel value="Label" />
                                            <InputText v-model="move.label" class="mt-1 w-full" />
                                        </div>

                                        <div class="mb-2">
                                            <InputLabel value="Clé" />
                                            <InputText v-model="move.key" class="mt-1 w-full" />
                                        </div>

                                        <div class="mb-2">
                                            <InputLabel value="Mode" />
                                            <select
                                                v-model="move.mode"
                                                class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                            >
                                                <option value="attack">Attaque</option>
                                                <option value="defense">Défense</option>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <InputLabel value="Action de base" />
                                            <select
                                                v-model="move.base_action"
                                                class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                            >
                                                <option value="shot">Tir</option>
                                                <option value="pass">Passe</option>
                                                <option value="dribble">Dribble</option>
                                                <option value="block">Block</option>
                                                <option value="intercept">Interception</option>
                                                <option value="tackle">Tacle</option>
                                                <option value="hand_save">Arrêt main</option>
                                                <option value="punch_save">Arrêt poing</option>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <InputLabel value="Cooldown" />
                                            <InputText type="number" v-model="move.cooldown" class="mt-1 w-full" />
                                        </div>

                                        <div class="mb-2">
                                            <InputLabel value="Description" />
                                            <textarea
                                                v-model="move.description"
                                                rows="3"
                                                class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm"
                                            ></textarea>
                                        </div>

                                        <button
                                            type="button"
                                            class="px-3 py-1.5 rounded-full bg-red-100 text-sm text-red-700 hover:bg-red-200"
                                            @click="removeSpecialMove(index)"
                                        >
                                            Supprimer cette technique
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        class="px-3 py-1.5 rounded bg-teal-500 text-white text-sm hover:bg-teal-600"
                                        @click="addSpecialMove"
                                    >
                                        Ajouter une technique spéciale
                                    </button>
                                </div>
                            </FormCol>
                        </FormRaw>

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
import { ref, defineProps } from 'vue'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import H1 from '@/Components/H1.vue'
import H2 from '@/Components/H2.vue'
import FormContainer from '@/Components/FormContainer.vue'
import FormRaw from '@/Components/FormRaw.vue'
import FormCol from '@/Components/FormCol.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputText from '@/Components/InputText.vue'
import InputSelect from '@/Components/InputSelect.vue'
import ButtonGroup from "@/Components/ButtonGroup.vue";
import ButtonPrimary from "@/Components/ButtonPrimary.vue";

const props = defineProps({
    gameSave: Object,
})

const positions = ['Goalkeeper', 'Defender', 'Midfielder', 'Forward']

const photoInput = ref(null)
const photoPreviewUrl = ref(null)
const selectedPhotoName = ref('')

const form = useForm({
    firstname: '',
    lastname: '',
    position: '',
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
    hand_save: 0,
    punch_save: 0,
    special_moves: [],
})

function openPhotoPicker() {
    photoInput.value?.click()
}

function onPhotoChange(e) {
    const file = e.target.files?.[0] ?? null

    form.photo = file
    selectedPhotoName.value = file ? file.name : ''

    if (file) {
        photoPreviewUrl.value = URL.createObjectURL(file)
    }

    e.target.value = ''
}

function addSpecialMove() {
    form.special_moves.push({
        key: '',
        label: '',
        short_label: '',
        mode: 'attack',
        base_action: 'shot',
        cooldown: 0,
        description: ''
    })
}

function removeSpecialMove(index) {
    form.special_moves.splice(index, 1)
}

function submit() {
    form.post(
        route('game-saves.players.store', { gameSave: props.gameSave.id }),
        { forceFormData: true }
    )
}
</script>
