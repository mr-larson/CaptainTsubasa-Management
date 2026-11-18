<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AuthSubmitButton from '@/Components/AuthSubmitButton.vue';
import AuthField from '@/Components/AuthField.vue';
import PasswordToggle from '@/Components/PasswordToggle.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Inscription" />

        <h1 class="text-xl font-semibold text-slate-700 mb-1">
            Créer un compte
        </h1>
        <p class="text-xs text-slate-500 mb-4">
            C'est gratuit. Une fois ton compte créé, tu pourras lancer ta première partie.
        </p>

        <form @submit.prevent="submit" class="space-y-4">
            <AuthField id="name" label="Nom de manager" type="text" required autofocus autocomplete="nickname" v-model="form.name" :error="form.errors.name" help="Le nom affiché dans le jeu. Un pseudo convient." wide compact />

            <AuthField id="email" label="Adresse e‑mail" type="email" required autocomplete="username" v-model="form.email" :error="form.errors.email" wide compact />

            <AuthField id="password" label="Mot de passe" :type="showPassword ? 'text' : 'password'" required minlength="8" autocomplete="new-password" v-model="form.password" :error="form.errors.password" help="8 caractères minimum." wide compact>
                <PasswordToggle v-model="showPassword" />
            </AuthField>

            <AuthField id="password_confirmation" label="Confirmer le mot de passe" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password" v-model="form.password_confirmation" :error="form.errors.password_confirmation" wide compact />

            <div class="pt-8 flex flex-col gap-3">
                <AuthSubmitButton :processing="form.processing">Créer mon compte</AuthSubmitButton>
                <p class="text-center text-xs text-slate-500">
                    Ce que nous faisons de tes données :
                    <Link :href="route('privacy')" class="font-semibold text-teal-600 hover:text-teal-700">confidentialité</Link>.
                </p>

                <p class="text-xs text-slate-600 text-center">
                    Déjà inscrit ?
                    <Link
                        :href="route('login')"
                        class="text-teal-600 hover:text-teal-700 font-semibold"
                    >
                        Se connecter
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
