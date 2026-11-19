<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import H2 from '@/Components/H2.vue';
import Panel from '@/Components/Panel.vue';
import Modal from '@/Components/Modal.vue';
import EntityEditor from '@/Components/EntityEditor.vue';
import EditorSubNav from '@/Components/EditorSubNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import InputError from '@/Components/InputError.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import { confirmDialog } from '@/Composables/useDialog.js';

const props = defineProps({ users: { type: Array, required: true } });

const currentUserId = computed(() => usePage().props.auth?.user?.id);

// --- Sélection / onglets ---
const selectedId = ref(null);
const activeTab = ref('user');
const selected = computed(() => props.users.find(u => u.id === selectedId.value) ?? null);

const form = useForm({ name: '', email: '', is_admin: false, password: '' });

function select(user) {
    selectedId.value = user.id;
    form.defaults({ name: user.name, email: user.email, is_admin: user.is_admin, password: '' }).reset();
    form.clearErrors();
}
onMounted(() => { if (props.users[0]) select(props.users[0]); });

const formatDate = iso => iso ? new Date(iso).toLocaleDateString('fr-FR') : '—';

const tabs = computed(() => [
    { key: 'user', label: 'Fiche utilisateur' },
    { key: 'saves', label: 'Parties', badge: selected.value?.game_saves.length ?? 0 },
]);

// --- Utilisateur ---
function submit() {
    if (!selected.value) return;
    form.patch(route('users.update', selected.value.id), {
        preserveScroll: true,
        onSuccess: () => { form.defaults().reset(); form.password = ''; },
    });
}

async function deleteUser() {
    const u = selected.value;
    if (!u) return;
    const ok = await confirmDialog({
        title: 'Supprimer l’utilisateur',
        message: `Supprimer ${u.name} ?`,
        details: [`Ses ${u.game_saves.length} partie(s) seront supprimées avec lui.`, 'Cette action est irréversible.'],
        confirmLabel: 'Supprimer',
        danger: true,
    });
    if (!ok) return;
    router.delete(route('users.destroy', u.id), {
        preserveScroll: true,
        onSuccess: () => { const first = props.users[0]; first ? select(first) : (selectedId.value = null); },
    });
}

// --- Parties ---
const editingSave = ref(null);
const saveForm = useForm({ label: '' });

function openSave(s) {
    editingSave.value = s;
    saveForm.defaults({ label: s.label ?? '' }).reset();
    saveForm.clearErrors();
}
const closeSave = () => { editingSave.value = null; };
function saveSave() {
    saveForm.patch(route('users.game-saves.update', editingSave.value.id), {
        preserveScroll: true,
        onSuccess: closeSave,
    });
}

async function deleteSave(s) {
    const ok = await confirmDialog({
        title: 'Supprimer la partie',
        message: `Supprimer la partie « ${s.label || 'Partie #' + s.id} » ?`,
        details: ['Cette action est irréversible.'],
        confirmLabel: 'Supprimer',
        danger: true,
    });
    if (ok) router.delete(route('users.game-saves.destroy', s.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Utilisateurs" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Utilisateurs</H2>
        </template>

        <div class="p-4 sm:p-6 max-w-8xl mx-auto flex flex-col gap-4">
            <EditorSubNav
                :back="{ href: route('dataBaseMenu'), label: 'Base de données' }"
                :links="[
                    { href: route('players.index'), label: 'Joueurs' },
                    { href: route('teams.index'), label: 'Équipes' },
                    { href: route('contracts.index'), label: 'Contrats' },
                    { href: route('users.index'), label: 'Utilisateurs', active: true },
                ]"
            />

            <EntityEditor
                title="Utilisateurs"
                :items="users"
                :selected-id="selectedId"
                :can-create="false"
                :search-text="u => `${u.name} ${u.email}`"
                search-placeholder="Nom ou e-mail"
                empty-label="Aucun utilisateur trouvé."
                :tabs="tabs"
                v-model:active-tab="activeTab"
                @select="select"
            >
                <template #item="{ item }">
                    <span class="flex-1 min-w-0">
                        <span class="block truncate text-sm font-medium">{{ item.name }}</span>
                        <span class="block truncate text-[11px] text-slate-400">{{ item.email }}</span>
                    </span>
                    <span v-if="item.is_admin" class="text-2xs font-bold px-2 py-0.5 rounded-full bg-teal-50 text-teal-700">Admin</span>
                </template>

                <template #badges>
                    <span v-if="selected" class="text-2xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        Inscrit le {{ formatDate(selected.created_at) }}
                    </span>
                </template>

                <template v-if="selected">
                    <!-- Fiche utilisateur -->
                    <Panel v-if="activeTab === 'user'" as="div" class="sm:p-6">
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="u_name" value="Nom" />
                                    <InputText id="u_name" v-model="form.name" required class="mt-1" />
                                    <InputError class="mt-1" :message="form.errors.name" />
                                </div>
                                <div>
                                    <InputLabel for="u_email" value="E-mail" />
                                    <InputText id="u_email" type="email" v-model="form.email" required class="mt-1" />
                                    <InputError class="mt-1" :message="form.errors.email" />
                                </div>
                                <div class="md:col-span-2">
                                    <InputLabel for="u_password" value="Nouveau mot de passe (laisser vide pour ne pas changer)" />
                                    <InputText id="u_password" type="password" v-model="form.password" autocomplete="new-password" class="mt-1" />
                                    <InputError class="mt-1" :message="form.errors.password" />
                                </div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" v-model="form.is_admin" class="rounded border-slate-300 text-teal-500 focus:ring-teal-400" />
                                Administrateur
                            </label>
                            <InputError :message="form.errors.is_admin" />

                            <ButtonGroup>
                                <ButtonPrimary :disabled="form.processing">Mettre à jour</ButtonPrimary>
                                <ButtonDanger v-if="selected.id !== currentUserId" type="button" class="w-36"
                                              :disabled="form.processing" @click="deleteUser">
                                    Supprimer
                                </ButtonDanger>
                            </ButtonGroup>
                        </form>
                    </Panel>

                    <!-- Parties de l'utilisateur -->
                    <Panel v-else as="div" class="sm:p-6">
                        <ul v-if="selected.game_saves.length" class="divide-y divide-slate-100">
                            <li v-for="s in selected.game_saves" :key="s.id" class="flex items-center gap-3 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-slate-700">{{ s.label || `Partie #${s.id}` }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        Saison {{ s.season }} · semaine {{ s.week }} · {{ s.game_mode }} · modifiée le {{ formatDate(s.updated_at) }}
                                    </div>
                                </div>
                                <button type="button" class="text-xs font-bold px-2.5 py-1 rounded-full border border-slate-300 bg-white hover:bg-slate-50" @click="openSave(s)">Renommer</button>
                                <button type="button" class="text-xs font-bold px-2.5 py-1 rounded-full border border-rose-300 bg-white text-rose-600 hover:bg-rose-50" @click="deleteSave(s)">Supprimer</button>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-slate-400 italic">Aucune partie.</p>
                    </Panel>
                </template>
            </EntityEditor>
        </div>

        <!-- Modale : renommage d'une partie -->
        <Modal :show="!!editingSave" max-width="md" title="Renommer la partie" @close="closeSave">
            <form id="save-form" class="space-y-4" @submit.prevent="saveSave">
                <div>
                    <InputLabel for="s_label" value="Nom de la partie" />
                    <InputText id="s_label" v-model="saveForm.label" class="mt-1" />
                    <InputError class="mt-1" :message="saveForm.errors.label" />
                </div>
            </form>
            <template #footer>
                <ButtonSecondary type="button" @click="closeSave">Annuler</ButtonSecondary>
                <ButtonPrimary type="submit" form="save-form" :disabled="saveForm.processing">Enregistrer</ButtonPrimary>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>
