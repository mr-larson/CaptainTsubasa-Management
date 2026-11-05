<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// Guide débutant : chaque règle citée ici correspond au comportement réel du
// jeu (valeurs par défaut, certaines sont réglables dans « Gestion »).
const counters = [
    { attack: 'Passe',   against: 'une passe',  defense: 'Interception',   icon: '✋' },
    { attack: 'Dribble', against: 'un dribble', defense: 'Tacle',          icon: '⚔️' },
    { attack: 'Tir',     against: 'un tir',     defense: 'Contre (block)', icon: '🧱' },
];
</script>

<template>
    <Head title="Comment jouer" />

    <PublicLayout>
        <h1 class="mb-3 text-3xl font-extrabold text-slate-50">Comment jouer</h1>
        <p class="mb-10 text-slate-300">
            Golden Eleven est un jeu de gestion de football au tour par tour. Tu diriges une équipe : tu la prépares
            entre les matchs, puis tu choisis tes actions pendant les rencontres.
        </p>

        <section class="mb-10">
            <h2 class="mb-3 text-xl font-bold text-teal-300">1. Créer une partie</h2>
            <ul class="list-disc space-y-2 pl-5 text-slate-300">
                <li>Choisis une compétition : la <strong>Ligue</strong> (championnat en aller-retour) ou la <strong>Coupe du Monde</strong> (poules puis élimination directe).</li>
                <li>Choisis un mode : <strong>effectifs pré-faits</strong> (chaque équipe a déjà ses joueurs) ou <strong>draft</strong> (toutes les équipes recrutent tour par tour, entre 14 et 18 joueurs).</li>
                <li>Sélectionne l'équipe que tu pilotes. Pour jouer à plusieurs sur le même appareil, ajoute une équipe par manager humain : chacun joue à son tour.</li>
                <li>
                    Avant de lancer la partie, le panneau de <strong>configuration</strong> te permet de l'adapter à ton goût :
                    <ul class="mt-2 list-[circle] space-y-1 pl-5">
                        <li><strong>Difficulté</strong> (Ligue) : le mandat de la direction fixe l'objectif, la patience du board et le budget (bac à sable, survie, standard ou conquête).</li>
                        <li><strong>Règles du jeu</strong> : active ou non les cartes bonus et malus, les blessures, les suspensions, le moral aléatoire, les transferts et l'entraînement de l'IA, et règle la fatigue, l'entraînement ou la durée des matchs.</li>
                        <li><strong>Joueurs inclus</strong> : choisis de quels univers viennent les joueurs disponibles (Captain Tsubasa, Blue Lock, Ao Ashi…) et si les internationaux sont inclus.</li>
                    </ul>
                </li>
            </ul>
        </section>

        <section class="mb-10">
            <h2 class="mb-3 text-xl font-bold text-teal-300">2. Préparer la semaine</h2>
            <p class="mb-3 text-slate-300">Le tableau de bord résume ta situation et ce qu'il reste à faire avant le match.</p>
            <ul class="list-disc space-y-2 pl-5 text-slate-300">
                <li><strong>Mon équipe</strong> : formation, titulaires, capitaine et numéros. Un joueur blessé ou suspendu ne peut pas jouer.</li>
                <li><strong>Entraînement</strong> : jusqu'à 3 séances par semaine, chacune améliore une statistique mais coûte de l'endurance.</li>
                <li><strong>Transferts</strong> : recrute des joueurs libres pour un nombre de matchs donné, dans la limite de ton budget.</li>
                <li><strong>Cartes bonus</strong> : achète des cartes pour améliorer ton équipe, parier sur un match ou gêner ton prochain adversaire.</li>
            </ul>
        </section>

        <section class="mb-10">
            <h2 class="mb-3 text-xl font-bold text-teal-300">3. Jouer un match</h2>
            <p class="mb-3 text-slate-300">
                Un match dure 45 tours par défaut (réglable). Quand ton équipe a le ballon, tu choisis une action : passe,
                dribble, tir, centre, passe longue, une-deux avec un partenaire de duo, ou une technique spéciale.
                Quand l'adversaire attaque, tu choisis comment défendre.
            </p>
            <p class="mb-3 text-slate-300">
                Chaque action est un <strong>duel</strong> : les statistiques des deux joueurs, leur endurance et un jet de dés
                décident du résultat. Choisir la bonne défense donne un bonus :
            </p>
            <div class="mb-4 grid gap-2 sm:grid-cols-3">
                <div v-for="c in counters" :key="c.attack" class="rounded-xl border border-white/10 bg-white/5 p-4 text-sm">
                    <div class="text-slate-400">Contre {{ c.against }}</div>
                    <div class="font-semibold text-slate-50">{{ c.icon }} {{ c.defense }}</div>
                </div>
            </div>
            <ul class="list-disc space-y-2 pl-5 text-slate-300">
                <li>Face au but, ton gardien arrête avec les mains, les poings ou sa technique spéciale.</li>
                <li>Les techniques spéciales sont plus fortes mais demandent quelques tours de recharge.</li>
                <li>Ton capitaine peut relancer les dés 3 fois par match.</li>
                <li>L'endurance baisse au fil du match : tu peux faire jusqu'à 3 remplacements.</li>
                <li>Les fautes peuvent donner des cartons (deux jaunes = expulsion) et des blessures.</li>
                <li>En phase finale de Coupe du Monde, une égalité se départage aux tirs au but.</li>
            </ul>
        </section>

        <section class="mb-10">
            <h2 class="mb-3 text-xl font-bold text-teal-300">4. Gérer la suite</h2>
            <ul class="list-disc space-y-2 pl-5 text-slate-300">
                <li>Après le match, la semaine se termine : les autres matchs sont simulés, le classement, le budget et l'endurance sont mis à jour.</li>
                <li>Les joueurs progressent selon leurs performances, et leur moral évolue selon les résultats et leur temps de jeu.</li>
                <li>En fin de saison, un bilan s'affiche, puis une nouvelle saison commence. En Ligue, la direction juge tes résultats : atteins ses objectifs pour garder ta place.</li>
            </ul>
        </section>

        <div class="rounded-2xl border border-teal-300/20 bg-gradient-to-br from-teal-500/10 to-slate-900/80 p-6 text-center">
            <p class="mb-4 text-slate-200">Tes parties sont sauvegardées sur ton compte : tu peux les reprendre quand tu veux.</p>
            <Link v-if="!$page.props.auth?.user" :href="route('register')"
                  class="inline-flex min-h-11 items-center justify-center rounded-full bg-teal-500 px-6 text-sm font-bold text-slate-700 hover:bg-teal-400">
                Créer mon compte gratuit
            </Link>
            <Link v-else :href="route('game-saves.create')"
                  class="inline-flex min-h-11 items-center justify-center rounded-full bg-teal-500 px-6 text-sm font-bold text-slate-700 hover:bg-teal-400">
                Lancer une partie
            </Link>
        </div>
    </PublicLayout>
</template>
