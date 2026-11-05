<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import AuthSubmitButton from '@/Components/AuthSubmitButton.vue';
import InputText from '@/Components/InputText.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirmer le mot de passe" />

        <div class="mb-4 text-sm text-slate-600">
            Zone sécurisée : confirme ton mot de passe pour continuer.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Mot de passe" />
                <InputText
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex justify-end mt-4">
                <AuthSubmitButton :processing="form.processing">Confirmer</AuthSubmitButton>
            </div>
        </form>
    </GuestLayout>
</template>
