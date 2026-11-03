<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// Décrit uniquement ce que l'application fait réellement (vérifié dans le code).
// À relire et compléter par le responsable du site (contact, hébergement).
</script>

<template>
    <Head title="Confidentialité" />

    <PublicLayout>
        <h1 class="mb-3 text-3xl font-extrabold text-slate-50">Confidentialité</h1>
        <p class="mb-10 text-slate-300">
            Golden Eleven est un projet indépendant. Cette page explique quelles données le site utilise et pourquoi.
        </p>

        <section class="mb-8">
            <h2 class="mb-3 text-xl font-bold text-teal-300">Données de ton compte</h2>
            <ul class="list-disc space-y-2 pl-5 text-slate-300">
                <li><strong>Nom de manager</strong> et <strong>adresse e-mail</strong> : pour te connecter et t'envoyer, si tu le demandes, un lien de réinitialisation du mot de passe.</li>
                <li><strong>Mot de passe</strong> : il n'est jamais stocké en clair, seulement sous forme chiffrée (hachée).</li>
                <li><strong>Parties</strong> : tes sauvegardes (équipes, joueurs, résultats, réglages) sont enregistrées sur ton compte pour que tu puisses les reprendre.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="mb-3 text-xl font-bold text-teal-300">Cookies et stockage dans le navigateur</h2>
            <ul class="list-disc space-y-2 pl-5 text-slate-300">
                <li>Un <strong>cookie de session</strong> et un <strong>cookie de sécurité</strong> (protection contre les requêtes frauduleuses) sont nécessaires au fonctionnement du site.</li>
                <li>Si tu coches « Se souvenir de moi », un cookie garde ta connexion plus longtemps.</li>
                <li>Ta préférence de vitesse d'animation des dés est gardée dans le stockage local de ton navigateur.</li>
                <li>Le site n'utilise ni publicité ni outil de mesure d'audience.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="mb-3 text-xl font-bold text-teal-300">Services tiers</h2>
            <ul class="list-disc space-y-2 pl-5 text-slate-300">
                <li>Les polices de caractères sont chargées depuis <strong>Bunny Fonts</strong> (fonts.bunny.net), un service européen qui ne dépose pas de cookie.</li>
                <li>Les liens vers la documentation (Notion) et le code source (GitHub) mènent vers des sites externes, soumis à leurs propres règles.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="mb-3 text-xl font-bold text-teal-300">Tes droits</h2>
            <ul class="list-disc space-y-2 pl-5 text-slate-300">
                <li>Tu peux modifier ton nom, ton adresse e-mail et ton mot de passe depuis ton <strong>profil</strong>.</li>
                <li>Tu peux <strong>supprimer ton compte</strong> depuis ton profil : tes sauvegardes sont supprimées avec lui.</li>
                <li>Pour toute question, ouvre un ticket sur le
                    <a href="https://github.com/mr-larson/CaptainTsubasa-Management/issues" target="_blank" rel="noopener" class="text-teal-300 underline hover:text-teal-200">dépôt GitHub du projet</a>.
                </li>
            </ul>
        </section>
    </PublicLayout>
</template>
