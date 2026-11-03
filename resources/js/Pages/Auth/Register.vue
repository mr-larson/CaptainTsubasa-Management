<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import AuthSubmitButton from '@/Components/AuthSubmitButton.vue';
import InputText from '@/Components/InputText.vue';
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

        <h1 class="text-xl font-semibold text-slate-800 mb-1">
            Créer un compte
        </h1>
        <p class="text-xs text-slate-500 mb-4">
            C'est gratuit. Une fois ton compte créé, tu pourras lancer ta première partie.
        </p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Nom de manager" />

                <InputText
                    id="name"
                    type="text"
                    class="mt-1 block md:w-96 bg-stone-100"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="nickname"
                    aria-describedby="name-help"
                />
                <p id="name-help" class="mt-1 text-xs text-slate-500">Le nom affiché dans le jeu. Un pseudo convient.</p>

                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Adresse e‑mail" />

                <InputText
                    id="email"
                    type="email"
                    class="mt-1 block md:w-96 bg-stone-100"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Mot de passe" />

                <InputText
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    class="mt-1 block md:w-96 bg-stone-100"
                    v-model="form.password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    aria-describedby="password-help"
                />
                <p id="password-help" class="mt-1 text-xs text-slate-500">8 caractères minimum.</p>
                <button type="button" @click="showPassword = !showPassword"
                        class="mt-1 text-xs font-semibold text-teal-600 hover:text-teal-700"
                        :aria-pressed="showPassword">
                    {{ showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe' }}
                </button>

                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />

                <InputText
                    id="password_confirmation"
                    :type="showPassword ? 'text' : 'password'"
                    class="mt-1 block md:w-96 bg-stone-100"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <div class="pt-8 flex flex-col gap-3">
                <AuthSubmitButton :processing="form.processing">Créer mon compte</AuthSubmitButton>

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
