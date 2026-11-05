<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AuthSubmitButton from '@/Components/AuthSubmitButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Vérification de l'adresse e-mail" />

        <div class="mb-4 text-sm text-gray-600">
            Merci pour ton inscription ! Avant de commencer, confirme ton adresse e-mail en cliquant sur le lien que nous venons de t'envoyer. Rien reçu ? Tu peux en demander un nouveau.
        </div>

        <div class="mb-4 font-medium text-sm text-green-600" v-if="verificationLinkSent">
            Un nouveau lien de vérification vient d'être envoyé à l'adresse indiquée lors de l'inscription.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <AuthSubmitButton :processing="form.processing">Renvoyer l'e-mail de vérification</AuthSubmitButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="underline text-sm text-gray-600 hover:text-slate-700 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >Se déconnecter</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
