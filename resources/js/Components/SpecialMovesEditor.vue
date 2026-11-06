<script setup>
import { TEXTAREA_CLASS } from '@/Components/fieldStyles';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import InputSelect from '@/Components/InputSelect.vue';
import FormErrorList from '@/Components/FormErrorList.vue';

// Éditeur des techniques spéciales d'un joueur de partie.
// v-model = form.special_moves : ajout / suppression émettent un nouveau
// tableau, les champs d'une technique sont édités en place (le formulaire
// travaille déjà sur une copie profonde des props).
const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const BASE_ACTIONS = [
    { value: 'shot',       label: 'Tir' },
    { value: 'pass',       label: 'Passe' },
    { value: 'dribble',    label: 'Dribble' },
    { value: 'block',      label: 'Contre' },
    { value: 'intercept',  label: 'Interception' },
    { value: 'tackle',     label: 'Tacle' },
    { value: 'hand_save',  label: 'Arrêt main' },
    { value: 'punch_save', label: 'Arrêt poing' },
];

function addSpecialMove() {
    emit('update:modelValue', [
        ...props.modelValue,
        {
            key: '',
            label: '',
            short_label: '',
            mode: 'attack',
            base_action: 'shot',
            cooldown: null, // vide = cooldown par défaut du moteur
            description: '',
        },
    ]);
}

function removeSpecialMove(index) {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index));
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-700">
                Techniques spéciales
            </h2>

            <!-- Bouton + en haut à droite -->
            <button
                type="button"
                class="flex items-center justify-center h-9 px-3 rounded-full bg-teal-500 text-white text-sm font-medium hover:bg-teal-600 shadow-md"
                @click="addSpecialMove"
            >
                + Ajouter
            </button>
        </div>

        <!-- Liste des techniques -->
        <div class="flex flex-col gap-4 max-h-[520px] overflow-y-auto pr-1">
            <div
                v-for="(move, index) in modelValue"
                :key="index"
                class="border border-slate-200 bg-white rounded-xl shadow-sm px-4 py-3"
            >
                <FormErrorList :errors="errors" :prefix="`special_moves.${index}.`" />

                <!-- Header de la technique -->
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-700">
                        Technique #{{ index + 1 }}
                        <span v-if="move.label" class="text-slate-500 font-normal">
                            — {{ move.label }}
                        </span>
                    </h3>

                    <button
                        type="button"
                        class="text-xs px-2 py-1 rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200"
                        @click="removeSpecialMove(index)"
                    >
                        Supprimer
                    </button>
                </div>

                <div class="space-y-3">
                    <!-- Label + Clé -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Label" />
                            <InputText v-model="move.label" class="mt-1 w-full" />
                        </div>

                        <div>
                            <InputLabel value="Clé (identifiant)" />
                            <InputText v-model="move.key" class="mt-1 w-full" />
                        </div>
                    </div>

                    <!-- Mode + Action de base -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Mode" />
                            <InputSelect v-model="move.mode" class="mt-1" required>
                                <option value="attack">Attaque</option>
                                <option value="defense">Défense</option>
                            </InputSelect>
                        </div>

                        <div>
                            <InputLabel value="Action de base" />
                            <InputSelect v-model="move.base_action" class="mt-1" required>
                                <option v-for="action in BASE_ACTIONS" :key="action.value" :value="action.value">
                                    {{ action.label }}
                                </option>
                            </InputSelect>
                        </div>
                    </div>

                    <!-- Cooldown + Description -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Cooldown (tours)" />
                            <!-- vide = cooldown par défaut du moteur -->
                            <InputText
                                type="number"
                                v-model="move.cooldown"
                                min="0"
                                class="mt-1 w-full"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Description</label>
                            <textarea
                                v-model="move.description"
                                rows="3"
                                class="mt-1 w-full" :class="TEXTAREA_CLASS"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message si aucune technique -->
            <p v-if="modelValue.length === 0" class="text-sm text-slate-500 italic">
                Aucune technique spéciale définie pour ce joueur.
            </p>
        </div>
    </div>
</template>
