<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import InputText from '@/Components/InputText.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

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
        <Head title="Log in" />

        <h1 class="text-xl font-semibold text-slate-800 mb-1">
            Connexion
        </h1>
        <p class="text-xs text-slate-500 mb-4">
            Connecte-toi pour reprendre ta partie ou en démarrer une nouvelle.
        </p>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <InputText
                    id="email"
                    type="email"
                    class="mt-1 block md:w-96 bg-stone-100"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <InputText
                    id="password"
                    type="password"
                    class="mt-1 block md:w-96 bg-stone-100"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>


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
                <ButtonPrimary
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Se connecter
                </ButtonPrimary>

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
