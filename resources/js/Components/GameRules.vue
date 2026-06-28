<script setup>
import { computed } from 'vue';

/**
 * Règles du jeu, réutilisable :
 *  - sur l'écran de création de partie (statique, sans `career`),
 *  - dans l'onglet Gestion en partie (avec `career` → affiche aussi l'état live
 *    du mandat : jauge de confiance, titres, dernier verdict, historique).
 */
const props = defineProps({
    career: { type: Object, default: null },
});

// Barème statique (miroir de CareerObjectiveService).
const DIFFICULTY_PRESETS = [
    { key: 'survival', label: 'Survie',   confidence: 45, titles: 1, rank: 'Objectif indulgent (rang toléré +2)' },
    { key: 'standard', label: 'Standard', confidence: 50, titles: 2, rank: 'Objectif = classement attendu' },
    { key: 'conquest', label: 'Conquête', confidence: 55, titles: 3, rank: 'Objectif exigeant (rang −2)' },
];

// Présentation des pages/onglets du jeu.
const PAGES = [
    { icon: '📊', title: 'Dashboard',     text: 'Vue d\'ensemble : objectif de la saison, classement, bilan, budget, prochain match. Point de départ pour simuler la semaine ou jouer le match.' },
    { icon: '👥', title: 'Mon équipe',    text: 'Compose ton onze, ta formation et tes titulaires (glisser-déposer). Gère le moral, la relation avec chaque joueur (promesses, déclarations presse) et le capitaine.' },
    { icon: '🛡️', title: 'Autres équipes', text: 'Inspecte les effectifs, formations et stats des clubs adverses pour préparer tes matchs.' },
    { icon: '📅', title: 'Calendrier',    text: 'Le calendrier de la saison et le détail de chaque match joué (score, stats, événements).' },
    { icon: '🏆', title: 'Classement',    text: 'Le classement du championnat (ou le bracket en Coupe du Monde).' },
    { icon: '📈', title: 'Stats',         text: 'Statistiques agrégées des joueurs sur la saison (buts, passes, etc.).' },
    { icon: '🏃', title: 'Entraînement',  text: 'Entraîne tes joueurs pour faire progresser leurs stats. Limité par semaine, coûte de la stamina et de l\'argent par séance (selon la config).' },
    { icon: '🔁', title: 'Transferts',    text: 'Recrute des joueurs libres en cours de saison : tu fixes durée de contrat et salaire. L\'IA recrute aussi.' },
    { icon: '🃏', title: 'Cartes bonus',  text: 'Achète des cartes (bonus pour toi, malus pour l\'adversaire, défis sponsors) à activer avant un match.' },
    { icon: '⚙️', title: 'Gestion',       text: 'Base de données (joueurs/équipes), profil, configuration de la partie, et ces règles.' },
];

const lastVerdict = computed(() => props.career?.last_verdict ?? null);
const careerHistory = computed(() => props.career?.history ?? []);

const verdictOutcomeLabel = (o) => ({
    won:      '🏆 Carrière gagnée',
    fired:    '❌ Licencié',
    retained: '✅ Maintenu',
}[o] ?? o);
</script>

<template>
    <div class="flex flex-col gap-4">

        <!-- ÉTAT LIVE : mandat de la saison en cours (uniquement en partie) -->
        <div v-if="career" class="border rounded-xl bg-white p-4"
             :class="career.alert ? 'border-rose-300 ring-1 ring-rose-100' : 'border-slate-200'">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Objectif de la saison</h4>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                      :class="career.status === 'won' ? 'bg-emerald-100 text-emerald-700'
                          : career.status === 'fired' ? 'bg-rose-100 text-rose-700'
                          : 'bg-slate-100 text-slate-600'">
                    Difficulté · {{ career.difficulty_label }}
                </span>
            </div>

            <div v-if="career.mandate" class="flex items-baseline gap-2 mb-1 flex-wrap">
                <span class="text-lg">🎯</span>
                <span class="text-base font-bold text-slate-800">{{ career.mandate.label }}</span>
                <span class="text-xs text-slate-400">
                    (classement attendu : {{ career.mandate.expected_rank }}<sup>e</sup> / {{ career.mandate.team_count }})
                </span>
            </div>
            <p v-else class="text-xs text-slate-400 italic mb-1">Objectif en attente (effectif pas encore constitué).</p>

            <!-- Jauge de confiance -->
            <div class="mt-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Confiance de la direction</span>
                    <span class="text-sm font-black"
                          :class="career.confidence <= 25 ? 'text-rose-500' : career.confidence <= 50 ? 'text-amber-500' : 'text-emerald-600'">
                        {{ career.confidence }} / 100
                    </span>
                </div>
                <div class="relative h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="absolute top-0 bottom-0 w-px bg-rose-300" style="left: 25%"></div>
                    <div class="h-full rounded-full transition-all"
                         :class="career.confidence <= 25 ? 'bg-rose-500' : career.confidence <= 50 ? 'bg-amber-400' : 'bg-emerald-500'"
                         :style="{ width: career.confidence + '%' }"></div>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">⚠️ Sous 25 le board est en alerte · à 0 c'est le licenciement.</p>
            </div>

            <!-- Titres -->
            <div class="mt-3 flex items-center gap-2 flex-wrap">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Titres</span>
                <div class="flex items-center gap-1">
                    <span v-for="n in career.titles_required" :key="n"
                          class="w-5 h-5 rounded-full flex items-center justify-center text-[11px]"
                          :class="n <= career.titles_won ? 'bg-amber-100' : 'bg-slate-100 grayscale opacity-50'">🏆</span>
                </div>
                <span class="text-xs font-semibold text-slate-600">{{ career.titles_won }} / {{ career.titles_required }} pour gagner</span>
            </div>

            <div v-if="career.status === 'won'" class="mt-3 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700">
                🏆 Carrière accomplie : objectif de titres atteint !
            </div>
            <div v-else-if="career.status === 'fired'" class="mt-3 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                ❌ Tu as été licencié{{ career.fired_reason === 'mid_season' ? ' en cours de saison' : ' en fin de saison' }}.
            </div>
        </div>

        <!-- Dernier verdict -->
        <div v-if="lastVerdict" class="border border-slate-200 rounded-xl bg-white p-4">
            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Dernier verdict (saison {{ lastVerdict.season }})</h4>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                <span>Classé <strong>{{ lastVerdict.rank }}<sup>e</sup></strong> (objectif top {{ lastVerdict.target_rank }})</span>
                <span :class="lastVerdict.met ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-semibold'">
                    {{ lastVerdict.met ? '✅ Objectif atteint' : '❌ Objectif manqué' }}
                </span>
                <span :class="lastVerdict.delta >= 0 ? 'text-emerald-600' : 'text-rose-600'" class="font-bold">
                    Confiance {{ lastVerdict.delta >= 0 ? '+' : '' }}{{ lastVerdict.delta }}
                </span>
                <span class="font-semibold">{{ verdictOutcomeLabel(lastVerdict.outcome) }}</span>
            </div>
        </div>

        <!-- Historique des saisons -->
        <div v-if="careerHistory.length" class="border border-slate-200 rounded-xl bg-white p-4">
            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Historique des saisons</h4>
            <table class="w-full text-xs">
                <thead>
                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                    <th class="text-left py-1">Saison</th>
                    <th class="text-center py-1">Objectif</th>
                    <th class="text-center py-1">Classé</th>
                    <th class="text-center py-1">Confiance</th>
                    <th class="text-right py-1">Résultat</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="v in careerHistory" :key="v.season" class="border-b border-slate-50">
                    <td class="py-1.5 font-semibold text-slate-700">S{{ v.season }}</td>
                    <td class="py-1.5 text-center text-slate-500">top {{ v.target_rank }}</td>
                    <td class="py-1.5 text-center font-semibold" :class="v.met ? 'text-emerald-600' : 'text-rose-500'">{{ v.rank }}<sup>e</sup></td>
                    <td class="py-1.5 text-center font-bold" :class="v.delta >= 0 ? 'text-emerald-600' : 'text-rose-500'">{{ v.delta >= 0 ? '+' : '' }}{{ v.delta }}</td>
                    <td class="py-1.5 text-right">
                        <span v-if="v.champion" title="Champion">🏆</span>
                        <span class="text-slate-500">{{ verdictOutcomeLabel(v.outcome) }}</span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- ====== LES PAGES DU JEU ====== -->
        <div class="border border-slate-200 rounded-xl bg-white p-4">
            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">Les pages du jeu</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                <div v-for="p in PAGES" :key="p.title" class="flex gap-2.5">
                    <span class="text-lg shrink-0">{{ p.icon }}</span>
                    <div>
                        <div class="text-xs font-bold text-slate-700">{{ p.title }}</div>
                        <p class="text-[11px] text-slate-500 leading-snug">{{ p.text }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== LA DRAFT ====== -->
        <div class="border border-slate-200 rounded-xl bg-white p-4">
            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">🎯 La draft</h4>
            <p class="text-xs text-slate-600 mb-2">
                En début de partie (et à chaque intersaison), les équipes se constituent en piochant à tour de rôle dans le pool de joueurs libres.
            </p>
            <ul class="text-xs text-slate-600 space-y-1 list-disc pl-4">
                <li><strong>Ordre « serpent »</strong> : l'ordre des équipes s'inverse à chaque tour, pour équilibrer les choix.</li>
                <li><strong>Budget</strong> : chaque équipe reçoit un bonus de draft. Le coût d'un joueur = salaire hebdo × semaines de la saison × <strong>réduction de 50 %</strong>.</li>
                <li><strong>Effectif</strong> : entre 14 (minimum pour terminer) et 18 joueurs. Tu peux clôturer ta draft dès 14.</li>
                <li><strong>Relation coach</strong> : un joueur en rupture avec toi peut refuser d'être drafté par ton équipe.</li>
                <li>Clique sur le nom d'un joueur pour voir sa fiche complète (stats, radar). Les stats clés de son poste sont surlignées.</li>
                <li>L'IA pioche selon ses besoins de poste, son style et sa philosophie de gestion.</li>
            </ul>
        </div>

        <!-- ====== LES MATCHS ====== -->
        <div class="border border-slate-200 rounded-xl bg-white p-4">
            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">⚽ Les matchs</h4>
            <p class="text-xs text-slate-600 mb-2">
                Les matchs se jouent en <strong>tours</strong> : à chaque action (duel, tir, arrêt…), les stats des joueurs concernés s'affrontent, modulées par le hasard, le moral et les cartes.
            </p>
            <ul class="text-xs text-slate-600 space-y-1 list-disc pl-4">
                <li><strong>Stamina</strong> : chaque match en consomme ; le repos en récupère. Un joueur fatigué est moins performant.</li>
                <li><strong>Moral</strong> : influe sur toutes les actions en match (de −10 % à +5 %), avec un possible « dépassement de soi » au-delà d'un certain seuil.</li>
                <li><strong>Fautes & cartons</strong> : une faute peut blesser (selon la config) ; cumuler des cartons jaunes entraîne une suspension.</li>
                <li><strong>Cartes bonus/malus</strong> : activées avant le match, elles avantagent ton équipe ou pénalisent l'adversaire.</li>
                <li>Le résultat fait évoluer le classement, le moral des joueurs et la confiance de la direction.</li>
            </ul>
        </div>

        <!-- ====== OBJECTIFS DE CARRIÈRE (règles) ====== -->
        <div class="border border-slate-200 rounded-xl bg-white p-4 flex flex-col gap-3">
            <div>
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">🎖️ Objectifs de carrière</h4>
                <p class="text-xs text-slate-600">
                    En mode Ligue, la direction te fixe un objectif de classement chaque saison et suit une <strong>jauge de confiance</strong>.
                    Atteins assez de titres pour gagner ta carrière… ou tombe à 0 et tu es licencié.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-lg bg-emerald-50 border border-emerald-100 py-2">
                    <div class="font-black text-emerald-600">+5</div>
                    <div class="text-slate-500">Victoire</div>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-100 py-2">
                    <div class="font-black text-slate-500">+1</div>
                    <div class="text-slate-500">Match nul</div>
                </div>
                <div class="rounded-lg bg-rose-50 border border-rose-100 py-2">
                    <div class="font-black text-rose-500">−5</div>
                    <div class="text-slate-500">Défaite</div>
                </div>
            </div>
            <ul class="text-xs text-slate-600 space-y-1 list-disc pl-4">
                <li><strong>Exploit</strong> (+3) : battre une équipe plus forte que la tienne.</li>
                <li><strong>Contre-performance</strong> (−3) : perdre contre une équipe plus faible.</li>
                <li><strong>Fin de saison</strong> : gros bonus si l'objectif est atteint (et plus encore en le dépassant), forte pénalité sinon — d'autant plus que le ratage est large.</li>
                <li><strong>Titre de champion</strong> : compte pour ta condition de victoire de carrière.</li>
            </ul>
            <p class="text-[10px] text-slate-400">💡 Les clubs IA paient aussi leurs entraînements : un club fauché s'entraîne moins.</p>
        </div>

        <!-- Barème par difficulté -->
        <div class="border border-slate-200 rounded-xl bg-white p-4">
            <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Niveaux de difficulté</h4>
            <table class="w-full text-xs">
                <thead>
                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                    <th class="text-left py-1">Niveau</th>
                    <th class="text-center py-1">Confiance départ</th>
                    <th class="text-center py-1">Titres à gagner</th>
                    <th class="text-left py-1 pl-3">Exigence</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="d in DIFFICULTY_PRESETS" :key="d.key" class="border-b border-slate-50"
                    :class="career && career.difficulty === d.key ? 'bg-teal-50' : ''">
                    <td class="py-1.5 font-semibold text-slate-700">
                        {{ d.label }}
                        <span v-if="career && career.difficulty === d.key" class="text-[9px] font-bold text-teal-600 ml-1">• en cours</span>
                    </td>
                    <td class="py-1.5 text-center text-slate-600">{{ d.confidence }}</td>
                    <td class="py-1.5 text-center text-slate-600">{{ d.titles }} 🏆</td>
                    <td class="py-1.5 pl-3 text-slate-500">{{ d.rank }}</td>
                </tr>
                </tbody>
            </table>
            <p class="text-[10px] text-slate-400 mt-2">En mode « Bac à sable », aucun objectif n'est suivi : les saisons s'enchaînent librement.</p>
        </div>
    </div>
</template>
