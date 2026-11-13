<template>
    <Head title="Équipes" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Équipes de la base</H2>
        </template>

        <div class="p-4 sm:p-6 max-w-8xl mx-auto flex flex-col gap-4">
            <EditorSubNav
                :back="{ href: route('dataBaseMenu'), label: 'Base de données' }"
                :links="[
                    { href: route('players.index'), label: 'Joueurs' },
                    { href: route('teams.index'), label: 'Équipes', active: true },
                    { href: route('contracts.index'), label: 'Contrats' },
                ]"
            />

            <EntityEditor
                title="Équipes"
                :items="teams"
                :selected-id="editor.selectedId.value"
                :creating="editor.creating.value"
                :search-text="t => t.name || ''"
                search-placeholder="Rechercher une équipe"
                empty-label="Aucune équipe trouvée."
                add-title="Créer une équipe"
                :tabs="[{ key: 'team', label: 'Fiche équipe' }]"
                active-tab="team"
                @select="editor.select"
                @create="editor.startCreating"
            >
                <template #item="{ item }">
                    <TeamLogo :team="item" />
                    <span class="truncate text-sm font-medium">{{ item.name }}</span>
                </template>

                <template #badges>
                    <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        Budget : {{ Number(form.budget || 0).toLocaleString('fr-FR') }} <GameBanknote />
                    </span>
                </template>

                <div class="bg-white border border-slate-300 rounded-xl p-4 sm:p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <TeamRecordBars v-if="!editor.creating.value" :wins="form.wins" :draws="form.draws" :losses="form.losses" />

                        <TeamFormFields
                            :form="form"
                            layout="grid"
                            :logo-url="logoPath ? `/storage/${logoPath}` : null"
                            @remove-logo="removeLogo"
                        />

                        <ButtonGroup>
                            <ButtonPrimary :disabled="form.processing">
                                {{ editor.creating.value ? "Créer l'équipe" : 'Mettre à jour' }}
                            </ButtonPrimary>
                            <ButtonDanger v-if="!editor.creating.value" class="w-36" type="button"
                                          :disabled="form.processing || !form.id" @click="deleteTeam">
                                Supprimer
                            </ButtonDanger>
                        </ButtonGroup>
                    </form>
                </div>
            </EntityEditor>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { confirmDialog } from '@/Composables/useDialog.js';
import { ref } from 'vue';

import H2 from '@/Components/H2.vue';
import EntityEditor from '@/Components/EntityEditor.vue';
import EditorSubNav from '@/Components/EditorSubNav.vue';
import TeamLogo from '@/Components/TeamLogo.vue';
import TeamRecordBars from '@/Components/TeamRecordBars.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import TeamFormFields from '@/Components/TeamFormFields.vue';
import { useEntityEditor, clone } from '@/Composables/useEntityEditor.js';

const props = defineProps({
    teams: { type: Array, required: true },
    // Équipe à sélectionner à l'ouverture (ex. juste après sa création).
    selectedId: { type: Number, default: null },
    // Ouvre directement une fiche vide (ancienne URL /teams/create).
    startCreate: { type: Boolean, default: false },
});

const BLANK = {
    id: null,
    name: '',
    description: '',
    budget: '',
    wins: 0,
    draws: 0,
    losses: 0,
    logo: null,
    remove_logo: false,
    tactical_style: 'balanced',
    management_philosophy: 'collective',
};

const form = useForm(clone(BLANK));

// Logo enregistré : affiché seulement, jamais renvoyé au serveur (il gère seul logo_path).
const logoPath = ref(null);

const editor = useEntityEditor({
    form,
    items: () => props.teams,
    blank: BLANK,
    entityLabel: 'La nouvelle équipe',
    initial: () => ({ selectedId: props.selectedId, startCreate: props.startCreate }),
    apply(team) {
        Object.assign(form, {
            id: team.id,
            name: team.name,
            description: team.description ?? '',
            budget: team.budget ?? 0,
            wins: team.wins ?? 0,
            draws: team.draws ?? 0,
            losses: team.losses ?? 0,
            tactical_style: team.tactical_style ?? 'balanced',
            management_philosophy: team.management_philosophy ?? 'collective',
            logo: null,
            remove_logo: false,
        });
        logoPath.value = team.logo_path ?? null;
    },
    onCreate() {
        logoPath.value = null;
    },
});

function removeLogo() {
    form.remove_logo = true;
    form.logo = null;
}

function submit() {
    const creating = editor.creating.value;
    if (!creating && !form.id) return;

    form.transform(({ id: _id, ...data }) => data).post(
        creating ? route('teams.store') : route('teams.update', form.id),
        {
            preserveScroll: true,
            forceFormData: true,
            // Création : le serveur renvoie l'id de l'équipe créée. Mise à jour : équipe rechargée
            // (logo à jour, fichier et « supprimer le logo » remis à zéro).
            onSuccess: () => editor.reload(creating ? props.selectedId : form.id),
        },
    );
}

async function deleteTeam() {
    if (!form.id) return;
    if (!(await confirmDialog({ title: "Supprimer l'équipe", message: 'Voulez-vous vraiment supprimer cette équipe ?', confirmLabel: 'Supprimer', danger: true }))) return;

    form.delete(route('teams.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => editor.afterDelete(),
    });
}
</script>
