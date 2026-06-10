<template>
    <Head :title="`Équipes — Partie #${gameSave.id}`" />

    <AuthenticatedLayout>
        <template #header></template>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-4 sm:p-6 max-w-8xl mx-auto flex flex-col gap-4">
            <H1>Édition des équipes de la partie</H1>

            <!-- SOUS-NAVIGATION GESTION -->
            <div class="flex flex-wrap items-center gap-2">
                <Link :href="route('game-saves.Play', { gameSave: gameSave.id })"
                      class="text-xs font-semibold px-3 py-1.5 rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-all">
                    ← Retour à la partie
                </Link>
                <span class="w-px h-5 bg-slate-200"></span>
                <Link :href="route('game-saves.players.index', { gameSave: gameSave.id })"
                      class="text-xs font-bold px-3 py-1.5 rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-all">
                    Joueurs
                </Link>
                <Link :href="route('game-saves.teams.index', { gameSave: gameSave.id })"
                      class="text-xs font-bold px-3 py-1.5 rounded-full bg-teal-500 text-white shadow-sm">
                    Équipes
                </Link>
            </div>

            <div class="flex flex-col lg:flex-row gap-4">
                <!-- LISTE ÉQUIPES -->
                <div class="lg:w-72 shrink-0 border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-col gap-3 lg:max-h-[calc(100vh-13rem)]">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">Équipes <span class="text-slate-400 font-normal">({{ teams.length }})</span></h3>
                        <Link
                            :href="route('game-saves.teams.create', { gameSave: gameSave.id })"
                            class="flex items-center justify-center h-7 px-2.5 rounded-full bg-teal-500 text-white text-xs font-bold hover:bg-teal-600 shadow-sm"
                            title="Créer une équipe"
                        >
                            + Ajouter
                        </Link>
                    </div>

                    <!-- Recherche -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" aria-hidden="true" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <input
                            type="search"
                            v-model="searchQuery"
                            class="block w-full py-1.5 pl-9 pr-3 text-sm text-slate-700 bg-white border border-slate-200 rounded-full focus:ring-2 focus:ring-teal-300 focus:border-teal-300 focus:outline-none"
                            placeholder="Rechercher une équipe"
                        >
                    </div>

                    <!-- Liste -->
                    <ul class="space-y-1 overflow-y-auto pr-1">
                        <li v-for="team in filteredTeams" :key="team.id">
                            <a
                                href="#"
                                @click.prevent="selectTeam(team)"
                                :class="form.selectedTeamId === team.id
                                    ? 'bg-teal-500 text-white border-teal-500 shadow-sm'
                                    : 'bg-white text-slate-700 border-slate-200 hover:border-teal-300 hover:bg-teal-50'"
                                class="flex items-center gap-2 px-3 py-2 border rounded-lg transition-all"
                            >
                                <div class="h-6 w-6 rounded-full bg-white/80 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                    <img v-if="team.logo_path" :src="`/storage/${team.logo_path}`" class="h-full w-full object-cover" />
                                    <span v-else class="text-[9px] text-slate-400">{{ (team.name || '?').charAt(0) }}</span>
                                </div>
                                <span class="truncate text-sm font-medium">{{ team.name }}</span>
                            </a>
                        </li>
                        <li v-if="filteredTeams.length === 0" class="text-xs text-slate-400 italic px-2 py-3 text-center">
                            Aucune équipe trouvée.
                        </li>
                    </ul>
                </div>

                <!-- PANNEAU DROIT -->
                <div class="flex-1 min-w-0 flex flex-col gap-4">

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 md:p-8">
                <!-- En-tête : récap équipe + ajout -->
                <div class="flex items-center justify-between mb-6 pb-6 border-b border-slate-200 flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-slate-50 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                            <img v-if="form.logo_path || logoPreviewUrl" :src="logoPreviewUrl || `/storage/${form.logo_path}`" class="h-full w-full object-cover" />
                            <span v-else class="text-sm text-slate-400">{{ (form.name || '?').charAt(0) }}</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">{{ form.name || 'Équipe' }}</h2>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                Budget : {{ Number(form.budget || 0).toLocaleString('fr-FR') }} €
                            </span>
                        </div>

                        <!-- Barres de stats : bilan -->
                        <div class="hidden md:grid grid-cols-3 gap-x-6 gap-y-2 ml-6">
                            <div v-for="stat in recordBars" :key="stat.key" class="flex items-center gap-2">
                                <span class="w-20 text-[11px] text-slate-500 shrink-0">{{ stat.label }}</span>
                                <div class="w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all" :class="stat.color"
                                         :style="{ width: stat.percent + '%' }">
                                    </div>
                                </div>
                                <span class="w-7 text-right text-[11px] font-bold text-slate-700">{{ stat.value }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- + Création équipe -->
                    <Link
                        :href="route('game-saves.teams.create', { gameSave: gameSave.id })"
                        class="flex items-center justify-center h-9 px-3 rounded-full bg-teal-500 text-white text-sm font-medium hover:bg-teal-600 shadow-md"
                        title="Créer une équipe"
                    >
                        + Ajouter
                    </Link>
                </div>

                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Nom</label>
                            <InputText
                                id="name"
                                type="text"
                                class="mt-1 w-full"
                                v-model="form.name"
                                autocomplete="off"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Budget</label>
                            <InputText
                                type="number"
                                id="budget"
                                v-model="form.budget"
                                placeholder="Budget de l'équipe"
                                class="mt-1 w-full"
                                autocomplete="budget"
                            />
                            <p v-if="form.errors.budget" class="text-sm text-red-600 mt-1">
                                {{ form.errors.budget }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Victoire(s)</label>
                            <InputText
                                type="number"
                                id="wins"
                                v-model="form.wins"
                                placeholder="Victoire(s) de l'équipe"
                                class="mt-1 w-full"
                            />
                            <p v-if="form.errors.wins" class="text-sm text-red-600 mt-1">
                                {{ form.errors.wins }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Défaite(s)</label>
                            <InputText
                                type="number"
                                id="losses"
                                v-model="form.losses"
                                placeholder="Défaite(s) de l'équipe"
                                class="mt-1 w-full"
                            />
                            <p v-if="form.errors.losses" class="text-sm text-red-600 mt-1">
                                {{ form.errors.losses }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Matchs nuls</label>
                            <InputText
                                type="number"
                                id="draws"
                                v-model="form.draws"
                                placeholder="Matchs nuls de l'équipe"
                                class="mt-1 w-full"
                            />
                            <p v-if="form.errors.draws" class="text-sm text-red-600 mt-1">
                                {{ form.errors.draws }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Style tactique</label>
                            <select
                                id="tactical_style"
                                v-model="form.tactical_style"
                                class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm text-gray-900 focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                                <option v-for="style in tacticalStyles" :key="style" :value="style">
                                    {{ tacticalLabel(style) }} {{ tacticalIcon(style) }}
                                </option>
                            </select>
                            <p v-if="form.errors.tactical_style" class="text-sm text-red-600 mt-1">
                                {{ form.errors.tactical_style }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Philosophie de gestion</label>
                            <select
                                id="management_philosophy"
                                v-model="form.management_philosophy"
                                class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm text-gray-900 focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                                <option v-for="philosophy in philosophies" :key="philosophy" :value="philosophy">
                                    {{ philosophyLabel(philosophy) }} {{ philosophyIcon(philosophy) }}
                                </option>
                            </select>
                            <p v-if="form.errors.management_philosophy" class="text-sm text-red-600 mt-1">
                                {{ form.errors.management_philosophy }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Logo</label>

                            <div class="mt-1 flex flex-col gap-2">
                                <div class="flex items-center gap-3">
                                    <!-- input file caché -->
                                    <input
                                        ref="logoInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="onLogoChange"
                                    />

                                    <!-- faux input -->
                                    <div class="flex items-center w-full md:w-56">
                                        <button
                                            type="button"
                                            class="shrink-0 px-3 py-1.5 rounded-l-full border border-gray-300 bg-stone-50 text-sm text-gray-900 hover:bg-white focus:outline-none focus:border-slate-700"
                                            @click="openLogoPicker"
                                        >
                                            Choisir
                                        </button>

                                        <div
                                            class="flex-1 px-3 py-1.5 rounded-r-full border border-l-0 border-gray-300 bg-stone-50 text-sm text-slate-600 truncate"
                                            :title="selectedLogoName || 'Aucun fichier choisi'"
                                        >
                                            {{ selectedLogoName || 'Aucun fichier choisi' }}
                                        </div>
                                    </div>

                                    <!-- preview carré -->
                                    <div class="h-16 w-16 rounded border bg-white overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="logoPreviewUrl || form.logo_path"
                                            :src="logoPreviewUrl ? logoPreviewUrl : `/storage/${form.logo_path}`"
                                            class="h-full w-full object-cover"
                                            alt="Logo équipe"
                                        />
                                        <span v-else class="text-xs text-slate-400">Aucun</span>
                                    </div>

                                    <!-- supprimer logo existant -->
                                    <button
                                        v-if="form.logo_path"
                                        type="button"
                                        class="px-3 py-1.5 rounded-full border border-red-300 bg-red-50 text-sm text-red-700 hover:bg-red-100"
                                        @click="removeLogo"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </div>

                            <p v-if="form.errors.logo" class="text-sm text-red-600 mt-1">
                                {{ form.errors.logo }}
                            </p>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-sm font-semibold text-slate-500 mb-1">Description</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Description de l'équipe (optionnel)"
                                class="mt-1 w-full rounded border border-gray-300 bg-stone-50 text-sm text-gray-900 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </div>

                    <ButtonGroup>
                        <ButtonPrimary :disabled="form.processing || !form.id">
                            Mettre à jour
                        </ButtonPrimary>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-if="form.recentlySuccessful"
                                class="text-sm text-slate-600"
                            >
                                Sauvegardé.
                            </p>
                        </Transition>

                        <ButtonDanger
                            class="w-36"
                            type="button"
                            :disabled="form.processing || !form.id"
                            @click="deleteTeam"
                        >
                            Supprimer
                        </ButtonDanger>
                    </ButtonGroup>
                </form>
            </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, defineProps, computed, onMounted } from 'vue';

// Components
import H1 from '@/Components/H1.vue';
import InputText from '@/Components/InputText.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import { useTeamStyles } from '@/Pages/GameSaves/Play/useTeamStyles.js';
import { TACTICAL_STYLES, PHILOSOPHIES } from '@/Enums/teamStyle.js';

const { tacticalLabel, tacticalIcon, philosophyLabel, philosophyIcon } = useTeamStyles();
const tacticalStyles = TACTICAL_STYLES;
const philosophies = PHILOSOPHIES;

const props = defineProps({
    gameSave: { type: Object, required: true },
    teams: { type: Array, required: true }, // GameTeam[]
});

const form = useForm({
    selectedTeamId: null,
    id: null,
    name: '',
    description: '',
    budget: '',
    wins: 0,
    draws: 0,
    losses: 0,
    logo: null,
    remove_logo: false,
    logo_path: null,
    tactical_style: 'balanced',
    management_philosophy: 'collective',
});

const searchQuery = ref('');

const recordBars = computed(() => {
    const wins = Number(form.wins ?? 0);
    const draws = Number(form.draws ?? 0);
    const losses = Number(form.losses ?? 0);
    const total = Math.max(wins + draws + losses, 1);
    return [
        { key: 'wins',   label: 'Victoires',   value: wins,   color: 'bg-emerald-400', percent: Math.round((wins / total) * 100) },
        { key: 'draws',  label: 'Nuls',        value: draws,  color: 'bg-amber-400',   percent: Math.round((draws / total) * 100) },
        { key: 'losses', label: 'Défaites',    value: losses, color: 'bg-rose-400',    percent: Math.round((losses / total) * 100) },
    ];
});

const filteredTeams = computed(() => {
    if (!searchQuery.value) return props.teams;
    return props.teams.filter(team =>
        (team.name || '').toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// LOGO UI
const logoInput = ref(null);
const selectedLogoName = ref('');
const logoPreviewUrl = ref('');

function openLogoPicker() {
    logoInput.value?.click();
}

function onLogoChange(e) {
    const file = e.target.files?.[0] ?? null;

    if (!file) {
        form.logo = null;
        selectedLogoName.value = '';
        logoPreviewUrl.value = '';
        return;
    }

    form.logo = file;
    form.remove_logo = false;
    selectedLogoName.value = file.name;
    logoPreviewUrl.value = URL.createObjectURL(file);
}

function removeLogo() {
    form.remove_logo = true;
    form.logo = null;
    selectedLogoName.value = '';
    logoPreviewUrl.value = '';
    if (logoInput.value) logoInput.value.value = '';
}

function selectTeam(team) {
    form.selectedTeamId = team.id;
    form.id             = team.id;
    form.name           = team.name;
    form.description    = team.description ?? '';
    form.budget         = team.budget ?? 0;
    form.wins           = team.wins ?? 0;
    form.draws          = team.draws ?? 0;
    form.losses         = team.losses ?? 0;

    form.tactical_style        = team.tactical_style ?? 'balanced';
    form.management_philosophy = team.management_philosophy ?? 'collective';

    form.logo_path      = team.logo_path ?? null;
    form.logo           = null;
    form.remove_logo    = false;

    selectedLogoName.value = '';
    logoPreviewUrl.value   = '';

    form.clearErrors();
}

function submit() {
    if (!form.id) return;

    form.post(route('game-saves.teams.update', {
        gameSave: props.gameSave.id,
        team: form.id,
    }), {
        preserveScroll: true,
        forceFormData: true,
    });
}

function deleteTeam() {
    if (!form.id) return;

    if (!confirm('Voulez-vous vraiment supprimer cette équipe de la partie ?')) {
        return;
    }

    form.delete(route('game-saves.teams.destroy', {
        gameSave: props.gameSave.id,
        team: form.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            if (props.teams.length > 0) {
                selectTeam(props.teams[0]);
            } else {
                form.reset();
            }
        },
    });
}

onMounted(() => {
    if (props.teams.length > 0) {
        selectTeam(props.teams[0]);
    }
});
</script>
