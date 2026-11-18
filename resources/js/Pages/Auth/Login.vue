<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AuthSubmitButton from '@/Components/AuthSubmitButton.vue';
import AuthField from '@/Components/AuthField.vue';
import PasswordToggle from '@/Components/PasswordToggle.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <h1 class="text-xl font-semibold text-slate-700 mb-1">
            Connexion
        </h1>
        <p class="text-xs text-slate-500 mb-4">
            Connecte-toi pour reprendre ta partie ou en démarrer une nouvelle.
        </p>

        <div v-if="status" class="mb-4 font-medium text-sm text-emerald-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <AuthField id="email" label="Adresse e-mail" type="email" required autofocus autocomplete="username" v-model="form.email" :error="form.errors.email" wide />

            <AuthField id="password" label="Mot de passe" class="mt-4" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" v-model="form.password" :error="form.errors.password" wide>
                <PasswordToggle v-model="showPassword" class="block" />
            </AuthField>


            <div class="flex items-center justify-between mt-4">
                <label class="flex items-center gap-2">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-xs text-slate-600">
                        Se souvenir de moi
                    </span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs text-teal-600 hover:text-teal-700"
                >
                    Mot de passe oublié ?
                </Link>
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <AuthSubmitButton :processing="form.processing">Se connecter</AuthSubmitButton>

                <p class="text-xs text-slate-600 text-center">
                    Pas encore de compte ?
                    <Link
                        :href="route('register')"
                        class="text-teal-600 hover:text-teal-700 font-semibold"
                    >
                        Créer un compte

                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
