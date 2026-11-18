<script setup>
import ButtonDanger from '@/Components/ButtonDanger.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import InputText from '@/Components/InputText.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-rose-600">Supprimer le compte</h2>

            <p class="mt-1 text-sm text-slate-500">
                Une fois ton compte supprimé, toutes ses données seront définitivement effacées. Avant de le supprimer,
                télécharge les informations que tu souhaites conserver.
            </p>
        </header>

        <ButtonDanger @click="confirmUserDeletion">Supprimer le compte</ButtonDanger>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-700">
                    Veux-tu vraiment supprimer ton compte ?
                </h2>

                <p class="mt-1 text-sm text-slate-600">
                    Une fois ton compte supprimé, toutes ses données seront définitivement effacées. Saisis ton mot
                    de passe pour confirmer la suppression définitive.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Mot de passe" class="sr-only" />

                    <InputText
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Mot de passe"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <ButtonSecondary @click="closeModal"> Annuler </ButtonSecondary>

                    <ButtonDanger
                        class="ml-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Supprimer le compte
                    </ButtonDanger>
                </div>
            </div>
        </Modal>
    </section>
</template>
