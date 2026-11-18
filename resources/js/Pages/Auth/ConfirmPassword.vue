<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AuthSubmitButton from '@/Components/AuthSubmitButton.vue';
import AuthField from '@/Components/AuthField.vue';
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
            <AuthField id="password" label="Mot de passe" type="password" required autocomplete="current-password" autofocus v-model="form.password" :error="form.errors.password" />

            <div class="flex justify-end mt-4">
                <AuthSubmitButton :processing="form.processing">Confirmer</AuthSubmitButton>
            </div>
        </form>
    </GuestLayout>
</template>
