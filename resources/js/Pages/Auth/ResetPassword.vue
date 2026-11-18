<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AuthSubmitButton from '@/Components/AuthSubmitButton.vue';
import AuthField from '@/Components/AuthField.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Nouveau mot de passe" />

        <form @submit.prevent="submit">
            <AuthField id="email" label="Adresse e-mail" type="email" required autofocus autocomplete="username" v-model="form.email" :error="form.errors.email" />

            <AuthField id="password" label="Nouveau mot de passe" class="mt-4" type="password" required autocomplete="new-password" v-model="form.password" :error="form.errors.password" />

            <AuthField id="password_confirmation" label="Confirmer le mot de passe" class="mt-4" type="password" required autocomplete="new-password" v-model="form.password_confirmation" :error="form.errors.password_confirmation" />

            <div class="flex items-center justify-end mt-4">
                <AuthSubmitButton :processing="form.processing">Enregistrer le nouveau mot de passe</AuthSubmitButton>
            </div>
        </form>
    </GuestLayout>
</template>
