<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AuthSubmitButton from '@/Components/AuthSubmitButton.vue';
import AuthField from '@/Components/AuthField.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Mot de passe oublié" />

        <div class="mb-4 text-sm text-slate-600">
            Mot de passe oublié ? Indique ton adresse e-mail : tu recevras un lien pour en choisir un nouveau.
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-emerald-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <AuthField id="email" label="Adresse e-mail" type="email" required autofocus autocomplete="username" v-model="form.email" :error="form.errors.email" />

            <div class="flex items-center justify-end mt-4">
                <AuthSubmitButton :processing="form.processing">Recevoir le lien de réinitialisation</AuthSubmitButton>
            </div>
        </form>
    </GuestLayout>
</template>
