<script setup>
import { TEXTAREA_CLASS } from '@/Components/fieldStyles';
import { computed, ref } from 'vue';
import FormField from '@/Components/FormField.vue';
import FormErrorList from '@/Components/FormErrorList.vue';
import ImageFilePicker from '@/Components/ImageFilePicker.vue';
import InputText from '@/Components/InputText.vue';
import InputSelect from '@/Components/InputSelect.vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';
import SecondaryPositionsPicker from '@/Components/SecondaryPositionsPicker.vue';
import { PLAYER_STATS, PLAYER_POSITIONS } from '@/Enums/playerStats.js';

// Champs communs des formulaires joueur (joueurs de base de la BDD et joueurs
// d'une partie). Chaque page garde son useForm, sa route et ses champs propres
// (contrat, techniques spéciales…) ; ce composant édite le form en place.
//
// Différence de forme des données :
//  - joueurs de base : stats dans `form.stats` (statsKey = 'stats',
//    erreurs « stats.speed ») ;
//  - joueurs de partie : stats à plat sur le form (statsKey = null).
const props = defineProps({
    form: { type: Object, required: true },
    layout: { type: String, default: 'rows' }, // 'rows' | 'grid'
    statsKey: { type: String, default: null },
    positions: { type: Array, default: () => PLAYER_POSITIONS },
    positionLabels: { type: Object, default: () => ({}) },
    positionPlaceholder: { type: String, default: 'Choisir...' },
    showAge: { type: Boolean, default: false },
    costLabel: { type: String, default: 'Coût' },
    // Photo déjà enregistrée (URL), affichée tant qu'aucun fichier n'est choisi.
    photoUrl: { type: String, default: null },
    // Case « Supprimer la photo actuelle » (form.remove_photo).
    removablePhoto: { type: Boolean, default: false },
});

// Choisir un nouveau fichier annule « Supprimer la photo actuelle ».
function onPhotoChange(file) {
    props.form.photo = file;
    if (file && 'remove_photo' in props.form) props.form.remove_photo = false;
}

const selectedPhotoUrl = ref(null);
const displayedPhoto = computed(() => selectedPhotoUrl.value || props.photoUrl);

const stats = computed(() => (props.statsKey ? props.form[props.statsKey] : props.form));
const statError = (key) => props.form.errors[props.statsKey ? `${props.statsKey}.${key}` : key];

const containerClass = computed(() => (props.layout === 'grid'
    ? 'grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4'
    : 'flex flex-col md:grid lg:grid-cols-2 gap-4 text-slate-700'));
const wideClass = computed(() => (props.layout === 'grid' ? 'sm:col-span-2' : 'lg:col-span-2'));
const fullClass = computed(() => (props.layout === 'grid' ? 'sm:col-span-3' : 'lg:col-span-2'));
</script>

<template>
    <!-- Infos générales -->
    <div :class="containerClass">
        <FormField :layout="layout" label="Prénom" for="firstname" :error="form.errors.firstname">
            <InputText id="firstname" v-model="form.firstname" class="mt-1 w-full" autocomplete="given-name" />
        </FormField>

        <FormField :layout="layout" label="Nom" for="lastname" :error="form.errors.lastname">
            <InputText id="lastname" v-model="form.lastname" class="mt-1 w-full" autocomplete="family-name" />
        </FormField>

        <FormField v-if="showAge" :layout="layout" label="Âge" for="age" :error="form.errors.age">
            <InputText id="age" type="number" v-model="form.age" class="mt-1 w-full" />
        </FormField>

        <FormField :layout="layout" label="Position" for="position" :error="form.errors.position">
            <InputSelect
                id="position"
                v-model="form.position"
                :class="layout === 'grid' ? 'mt-1 w-full' : 'mt-1'"
                :placeholder="positionPlaceholder"
                required
            >
                <option v-for="pos in positions" :key="pos" :value="pos">
                    {{ positionLabels[pos] ?? pos }}
                </option>
            </InputSelect>
        </FormField>

        <FormField :layout="layout" :label="costLabel" for="cost" :error="form.errors.cost">
            <InputText id="cost" type="number" v-model="form.cost" class="mt-1 w-full" />
        </FormField>

        <div :class="layout === 'grid' ? 'sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 items-start' : 'contents'">
        <FormField
            :layout="layout"
            :class="layout === 'grid' ? 'min-w-0' : (!showAge ? wideClass : '')"
            label="Photo"
            :error="form.errors.photo"
        >
            <ImageFilePicker
                :model-value="form.photo"
                :current-url="photoUrl"
                alt="Photo joueur"
                :hide-preview="layout === 'grid'"
                :full-width="layout === 'grid'"
                @preview="selectedPhotoUrl = $event"
                @update:model-value="onPhotoChange"
            >
                <template #preview="{ src }">
                    <PlayerPhoto :src="src" class="h-full w-full object-cover" alt="Photo joueur" />
                </template>

                <label v-if="removablePhoto" class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" v-model="form.remove_photo" />
                    Supprimer la photo actuelle
                </label>
            </ImageFilePicker>
        </FormField>

        <div v-if="layout === 'grid'" class="flex items-start sm:pt-6">
            <div class="h-28 w-28 shrink-0 overflow-hidden rounded border border-slate-200 bg-white flex items-center justify-center">
                <PlayerPhoto v-if="displayedPhoto" :src="displayedPhoto" class="h-full w-full object-contain" alt="Photo du joueur" />
                <span v-else class="text-xs text-slate-400">Aucune photo</span>
            </div>
        </div>

        </div>

        <FormField :layout="layout" :class="wideClass" label="Postes secondaires" :error="form.errors.secondary_positions">
            <SecondaryPositionsPicker v-model="form.secondary_positions" :position="form.position" />
            <template #after>
                <FormErrorList :errors="form.errors" prefix="secondary_positions." />
            </template>
        </FormField>

        <FormField :layout="layout" :class="fullClass" label="Description" for="description" :error="form.errors.description">
            <textarea
                id="description"
                v-model="form.description"
                rows="3"
                placeholder="Description du joueur (optionnel)"
                class="mt-1 w-full" :class="TEXTAREA_CLASS"
            ></textarea>
        </FormField>
    </div>

    <!-- Stats -->
    <div :class="containerClass">
        <FormField
            v-for="stat in PLAYER_STATS"
            :key="stat.key"
            :layout="layout"
            :label="stat.label"
            :for="`stat-${stat.key}`"
            :error="statError(stat.key)"
        >
            <InputText :id="`stat-${stat.key}`" type="number" v-model="stats[stat.key]" class="mt-1 w-full" />
        </FormField>
    </div>
</template>
