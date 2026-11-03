<script setup>
import { computed } from 'vue';
import FormField from '@/Components/FormField.vue';
import ImageFilePicker from '@/Components/ImageFilePicker.vue';
import InputText from '@/Components/InputText.vue';
import { useTeamStyles } from '@/Pages/GameSaves/Play/useTeamStyles.js';
import { TACTICAL_STYLES, PHILOSOPHIES } from '@/Enums/teamStyle.js';

// Champs communs des formulaires équipe (équipes de base de la BDD et équipes
// d'une partie). Chaque page garde son useForm et sa route.
//
// Le chemin du logo enregistré n'est PAS dans le form : il arrive par
// `logoUrl` (affichage seul), le serveur gère seul `logo_path`. Le bouton
// « Supprimer » (si `logoUrl`) émet `remove-logo` ; la page positionne
// form.remove_logo. `logo-preview` relaie l'URL de l'aperçu du fichier choisi.
const props = defineProps({
    form: { type: Object, required: true },
    layout: { type: String, default: 'rows' }, // 'rows' | 'grid'
    logoUrl: { type: String, default: null },
});

const emit = defineEmits(['remove-logo', 'logo-preview']);

const { tacticalLabel, tacticalIcon, philosophyLabel, philosophyIcon } = useTeamStyles();

const selectClass = 'mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm text-gray-900 focus:outline-none focus:bg-white focus:border-purple-300';

const containerClass = computed(() => (props.layout === 'grid'
    ? 'grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4'
    : 'flex flex-col md:grid lg:grid-cols-2 gap-4 text-slate-700'));
</script>

<template>
    <div :class="containerClass">
        <FormField :layout="layout" label="Nom" for="name" :error="form.errors.name">
            <InputText id="name" type="text" v-model="form.name" class="mt-1 w-full" autocomplete="off" />
        </FormField>

        <FormField :layout="layout" label="Budget" for="budget" :error="form.errors.budget">
            <InputText id="budget" type="number" v-model="form.budget" placeholder="Budget de l'équipe" class="mt-1 w-full" />
        </FormField>

        <FormField :layout="layout" label="Victoire(s)" for="wins" :error="form.errors.wins">
            <InputText id="wins" type="number" v-model="form.wins" placeholder="Victoire(s) de l'équipe" class="mt-1 w-full" />
        </FormField>

        <FormField :layout="layout" label="Défaite(s)" for="losses" :error="form.errors.losses">
            <InputText id="losses" type="number" v-model="form.losses" placeholder="Défaite(s) de l'équipe" class="mt-1 w-full" />
        </FormField>

        <FormField :layout="layout" label="Matchs nuls" for="draws" :error="form.errors.draws">
            <InputText id="draws" type="number" v-model="form.draws" placeholder="Matchs nuls de l'équipe" class="mt-1 w-full" />
        </FormField>

        <FormField :layout="layout" label="Style tactique" for="tactical_style" :error="form.errors.tactical_style">
            <select id="tactical_style" v-model="form.tactical_style" :class="selectClass">
                <option v-for="style in TACTICAL_STYLES" :key="style" :value="style">
                    {{ tacticalLabel(style) }} {{ tacticalIcon(style) }}
                </option>
            </select>
        </FormField>

        <FormField :layout="layout" label="Philosophie de gestion" for="management_philosophy" :error="form.errors.management_philosophy">
            <select id="management_philosophy" v-model="form.management_philosophy" :class="selectClass">
                <option v-for="philosophy in PHILOSOPHIES" :key="philosophy" :value="philosophy">
                    {{ philosophyLabel(philosophy) }} {{ philosophyIcon(philosophy) }}
                </option>
            </select>
        </FormField>

        <FormField :layout="layout" :class="layout === 'grid' ? 'sm:col-span-2' : ''" label="Logo" :error="form.errors.logo">
            <ImageFilePicker
                v-model="form.logo"
                :current-url="logoUrl"
                alt="Logo équipe"
                empty-label="Aucun"
                @preview="url => emit('logo-preview', url)"
            >
                <template #actions>
                    <!-- supprimer logo existant -->
                    <button
                        v-if="logoUrl"
                        type="button"
                        class="px-3 py-1.5 rounded-full border border-red-300 bg-red-50 text-sm text-red-700 hover:bg-red-100"
                        @click="emit('remove-logo')"
                    >
                        Supprimer
                    </button>
                </template>
            </ImageFilePicker>
        </FormField>

        <FormField
            :layout="layout"
            :class="layout === 'grid' ? 'sm:col-span-3' : 'lg:col-span-2'"
            label="Description"
            for="description"
            :error="form.errors.description"
        >
            <textarea
                id="description"
                v-model="form.description"
                rows="3"
                placeholder="Description de l'équipe (optionnel)"
                class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm text-gray-900 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
            ></textarea>
        </FormField>
    </div>
</template>
