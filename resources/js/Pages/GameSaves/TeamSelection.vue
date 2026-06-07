<!-- resources/js/Pages/GameSaves/TeamSelection.vue -->
<template>
    <Head title="Choix de l'équipe" />

    <AuthenticatedLayout>
        <template #header></template>

        <div class="min-h-screen bg-slate-50 p-6">

            <!-- Header -->
            <div class="max-w-7xl mx-auto mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-slate-800">Choix de l'équipe</h1>
                        <p class="text-sm text-slate-400 mt-0.5">
                            Partie : <span class="font-semibold text-slate-600">{{ label || 'Sans nom' }}</span>
                            &nbsp;•&nbsp; Période : <span class="font-semibold text-slate-600">Collège</span>
                            &nbsp;•&nbsp;
                            <span v-if="gameMode === 'draft'" class="font-semibold text-amber-600">🎯 Mode Draft</span>
                            <span v-else class="font-semibold text-teal-600">🏟️ Effectifs pré-faits</span>
                        </p>
                    </div>
                    <Link :href="route('mainMenu')"
                          class="px-4 py-2 rounded-full text-sm font-semibold border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 transition-all">
                        ← Retour
                    </Link>
                </div>
            </div>

            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-12 gap-4">

                    <!-- Colonne gauche : liste équipes -->
                    <div class="col-span-3 border border-slate-200 rounded-xl bg-white p-3 flex flex-col gap-2" style="max-height: calc(100vh - 180px);">
                        <!-- Recherche -->
                        <div class="relative shrink-0">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                            </svg>
                            <input type="search" v-model="searchQuery"
                                   class="w-full pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-full bg-slate-50 focus:ring-2 focus:ring-teal-300 focus:outline-none"
                                   placeholder="Rechercher..."/>
                        </div>

                        <!-- Compteur -->
                        <div class="text-[10px] text-slate-400 font-semibold px-1 shrink-0">
                            {{ filteredTeams.length }} équipe(s)
                        </div>

                        <!-- Liste scrollable -->
                        <div class="flex-1 overflow-y-auto space-y-1 pr-0.5">
                            <button v-for="team in filteredTeams" :key="team.id" type="button"
                                    @click="selectTeam(team)"
                                    class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-semibold border transition-all text-left"
                                    :class="startForm.team_id === team.id
                                ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                                : 'bg-white text-slate-600 border-slate-100 hover:border-teal-200 hover:bg-teal-50'">
                                <div class="w-7 h-7 rounded-lg overflow-hidden shrink-0 bg-slate-100 border border-slate-200 flex items-center justify-center">
                                    <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt=""/>
                                    <span v-else class="text-[8px] text-slate-400">—</span>
                                </div>
                                <span class="truncate">{{ team.name }}</span>
                            </button>
                            <div v-if="!filteredTeams.length" class="text-xs text-slate-400 italic text-center py-4">
                                Aucune équipe
                            </div>
                        </div>
                    </div>

                    <!-- Panneau principal -->
                    <div v-if="selectedTeam" class="col-span-9 grid grid-cols-12 gap-4">

                        <!-- Profil équipe -->
                        <div class="col-span-4 flex flex-col gap-3">

                            <!-- Logo + infos -->
                            <div class="border border-slate-200 rounded-xl bg-white p-5">
                                <div class="flex flex-col items-center gap-3 mb-4">
                                    <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-50 border border-slate-200 flex items-center justify-center">
                                        <img v-if="teamLogoUrl(selectedTeam)" :src="teamLogoUrl(selectedTeam)" class="w-full h-full object-contain" alt=""/>
                                        <span v-else class="text-4xl">🏟️</span>
                                    </div>
                                    <div class="text-center">
                                        <h2 class="text-lg font-black text-slate-800">{{ selectedTeam.name }}</h2>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ selectedTeam.description ?? '—' }}</p>
                                        <div class="mt-2 flex justify-center">
                                            <TeamStyleBadges :team="selectedTeam" size="sm" />
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="bg-slate-50 rounded-lg p-2.5 text-center">
                                        <div class="text-lg font-black text-teal-600">{{ selectedTeam.budget ?? 0 }}</div>
                                        <div class="text-slate-400 font-semibold">Budget €</div>
                                    </div>
                                    <div class="bg-slate-50 rounded-lg p-2.5 text-center">
                                        <div class="text-lg font-black text-slate-700">{{ roster.length }}</div>
                                        <div class="text-slate-400 font-semibold">Joueurs</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats moyennes (prebuilt uniquement) -->
                            <div v-if="gameMode !== 'draft'" class="border border-slate-200 rounded-xl bg-white p-4">
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Forces</h4>
                                <div class="space-y-2">
                                    <div v-for="stat in [
                    { label: 'Attaque',  val: avgAttack,  color: 'bg-orange-400' },
                    { label: 'Défense',  val: avgDefense, color: 'bg-blue-400'   },
                    { label: 'Stamina',  val: avgStamina, color: 'bg-emerald-400'},
                    { label: 'Vitesse',  val: avgSpeed,   color: 'bg-sky-400'    },
                ]" :key="stat.label" class="flex items-center gap-2 text-xs">
                                        <span class="w-14 text-slate-500 shrink-0">{{ stat.label }}</span>
                                        <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full" :class="stat.color"
                                                 :style="{ width: Math.min(stat.val, 100) + '%' }"></div>
                                        </div>
                                        <span class="w-6 text-right font-black text-slate-600">{{ stat.val }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Info draft (draft uniquement) -->
                            <div v-if="gameMode === 'draft'" class="border border-amber-200 rounded-xl bg-amber-50 p-4">
                                <h4 class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-2">🎯 Mode Draft</h4>
                                <div class="space-y-2 text-xs text-amber-700">
                                    <p>L'équipe démarre <span class="font-bold">sans joueur</span>. Tu construiras ton effectif lors du draft.</p>
                                    <p>Chaque équipe reçoit un <span class="font-bold">bonus de 5 000 €</span> pour recruter.</p>
                                    <p>Ordre de pioche : <span class="font-bold">aléatoire</span> (tirage au sort).</p>
                                    <p>Objectif : constituer un effectif de <span class="font-bold">14 à 18 joueurs</span>.</p>
                                </div>
                            </div>

                            <!-- Radar (prebuilt uniquement) -->
                            <div v-if="gameMode !== 'draft'" class="border border-slate-200 rounded-xl bg-white p-4 flex flex-col items-center">
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 self-start">Profil</h4>
                                <svg viewBox="0 0 160 160" class="w-36 h-36">
                                    <polygon v-for="(pts,i) in radarGrids" :key="i" :points="pts" fill="none" stroke="#e2e8f0" stroke-width="0.8"/>
                                    <line v-for="(ax,i) in radarAxes" :key="'a'+i" x1="80" y1="80" :x2="ax.x2" :y2="ax.y2" stroke="#e2e8f0" stroke-width="0.8"/>
                                    <polygon :points="radarPolygon" fill="rgba(20,184,166,0.2)" stroke="#14b8a6" stroke-width="1.5" stroke-linejoin="round"/>
                                    <circle v-for="(pt,i) in radarPoints" :key="'p'+i" :cx="pt.x" :cy="pt.y" r="2" fill="#14b8a6" stroke="white" stroke-width="1"/>
                                    <text v-for="(pt,i) in radarPoints" :key="'t'+i" :x="pt.lx" :y="pt.ly" text-anchor="middle" dominant-baseline="middle" font-size="7" fill="#94a3b8" font-weight="600">{{ pt.label }}</text>
                                </svg>
                            </div>

                            <!-- Bouton sélectionner -->
                            <button type="button"
                                    class="w-full py-3 rounded-xl font-bold text-sm transition-all"
                                    :class="startForm.team_id
                    ? (gameMode === 'draft'
                        ? 'bg-amber-500 hover:bg-amber-400 text-white shadow-lg shadow-amber-200 hover:scale-[1.01] active:scale-[0.99]'
                        : 'bg-teal-500 hover:bg-teal-400 text-white shadow-lg shadow-teal-200 hover:scale-[1.01] active:scale-[0.99]')
                    : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
                                    :disabled="!startForm.team_id || startForm.processing"
                                    @click="startWithTeam">
                                <span v-if="startForm.processing">Chargement...</span>
                                <span v-else-if="gameMode === 'draft'">🎯 Drafter avec {{ selectedTeam.name }}</span>
                                <span v-else>▶ Jouer avec {{ selectedTeam.name }}</span>
                            </button>
                        </div>

                        <!-- Colonne droite : effectif (prebuilt) ou aperçu style (draft) -->
                        <div class="col-span-8 border border-slate-200 rounded-xl bg-white p-4">

                            <!-- Mode prebuilt : effectif complet -->
                            <template v-if="gameMode !== 'draft'">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                                    Effectif — {{ roster.length }} joueur(s)
                                </h3>

                                <div v-if="roster.length" class="grid grid-cols-2 gap-2 max-h-[620px] overflow-y-auto pr-1">
                                    <div v-for="player in roster" :key="player.id"
                                         class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50 hover:bg-white hover:border-slate-200 transition-all">
                                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-200 border border-slate-100 shrink-0">
                                            <img v-if="playerPhotoUrl(player)" :src="playerPhotoUrl(player)" class="w-full h-full object-cover" alt=""/>
                                            <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-lg">👤</div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-bold text-slate-800 truncate">
                                                {{ player.firstname }} {{ player.lastname }}
                                            </div>
                                            <div class="text-[10px] text-slate-400">{{ player.position }}</div>
                                            <div class="flex gap-2 mt-1">
                                                <div v-for="k in keyStatsFor(player.position).slice(0, 3)" :key="k" class="text-center">
                                                    <div class="text-[11px] font-black text-slate-700">
                                                        {{ player[k] ?? player.stats?.[k] ?? '—' }}
                                                    </div>
                                                    <div class="text-[8px] text-slate-400 uppercase">{{ statShortLabel(k) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-black shrink-0 border-2"
                                             :class="overallOf(player) >= 70
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-700'
                            : overallOf(player) >= 50
                                ? 'bg-amber-50 border-amber-300 text-amber-700'
                                : 'bg-slate-100 border-slate-200 text-slate-500'">
                                            {{ overallOf(player) }}
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center justify-center h-48 text-slate-400 text-sm italic">
                                    Aucun joueur sous contrat
                                </div>
                            </template>

                            <!-- Mode draft : explications + identité -->
                            <template v-else>
                                <h3 class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-4">
                                    🎯 Identité de {{ selectedTeam.name }}
                                </h3>

                                <div class="space-y-4">
                                    <!-- Style tactique -->
                                    <div class="border border-slate-100 rounded-xl bg-slate-50 p-4">
                                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Style tactique</h4>
                                        <p class="text-sm text-slate-700">
                                            Cette équipe jouera avec un style
                                            <span class="font-bold">{{ styleLabel(selectedTeam.tactical_style) }}</span>.
                                            L'IA adaptera ses actions en match, son entraînement et sa stratégie selon ce profil.
                                        </p>
                                    </div>

                                    <!-- Philosophie -->
                                    <div class="border border-slate-100 rounded-xl bg-slate-50 p-4">
                                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Philosophie de gestion</h4>
                                        <p class="text-sm text-slate-700">
                                            Approche
                                            <span class="font-bold">{{ philosophyLabel(selectedTeam.management_philosophy) }}</span> :
                                            {{ philosophyDescription(selectedTeam.management_philosophy) }}
                                        </p>
                                    </div>

                                    <!-- Règles du draft -->
                                    <div class="border border-amber-100 rounded-xl bg-amber-50/50 p-4">
                                        <h4 class="text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-3">Comment ça marche</h4>
                                        <div class="space-y-2 text-xs text-slate-600">
                                            <div class="flex items-start gap-2">
                                                <span class="text-amber-500 font-bold shrink-0">1.</span>
                                                <p>L'ordre de pioche est tiré au sort entre les 15 équipes.</p>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <span class="text-amber-500 font-bold shrink-0">2.</span>
                                                <p>Chaque équipe pioche <span class="font-bold">1 joueur par tour</span>, puis on passe à la suivante.</p>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <span class="text-amber-500 font-bold shrink-0">3.</span>
                                                <p>Le draft continue jusqu'à ce que chaque équipe ait au moins <span class="font-bold">14 joueurs</span> (max 18).</p>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <span class="text-amber-500 font-bold shrink-0">4.</span>
                                                <p>Ton budget total : <span class="font-bold">{{ (selectedTeam.budget ?? 0) + 5000 }} €</span> (budget club + bonus draft).</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Placeholder si aucune équipe sélectionnée -->
                    <div v-else class="col-span-9 border border-dashed border-slate-300 rounded-xl flex flex-col items-center justify-center text-slate-400" style="min-height: 400px;">
                        <div class="text-4xl mb-3">⚽</div>
                        <p class="font-semibold">Sélectionne une équipe pour voir son profil</p>
                    </div>

                </div><!-- fin grid 12 -->
            </div><!-- fin max-w -->
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import H2 from '@/Components/H2.vue';
import H1 from '@/Components/H1.vue';
import TeamStyleBadges from '@/Pages/GameSaves/play/TeamStyleBadges.vue';

const props = defineProps({
    label:    { type: String, default: null },
    period:   { type: String, required: true },
    teams:    { type: Array,  required: true },
    gameMode: { type: String, default: 'prebuilt' },
});

const searchQuery  = ref('');
const selectedTeam = ref(null);

const filteredTeams = computed(() => {
    if (!searchQuery.value) return props.teams;
    return props.teams.filter(t => t.name?.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const startForm = useForm({
    label:     props.label || '',
    period:    props.period,
    team_id:   null,
    game_mode: props.gameMode,
});

const roster = computed(() => {
    if (!selectedTeam.value?.contracts) return [];
    return selectedTeam.value.contracts.map(c => c.player).filter(Boolean);
});

const styleLabel = (key) => ({
    offensive:  '⚔️ Offensif',
    defensive:  '🛡️ Défensif',
    possession: '🎯 Possession',
    counter:    '⚡ Contre-attaque',
    balanced:   '⚖️ Équilibré',
}[key] ?? key ?? '—');

const philosophyLabel = (key) => ({
    stars:      '⭐ Stars',
    collective: '👥 Collectif',
    balanced:   '⚖️ Équilibré',
    economist:  '💰 Économe',
}[key] ?? key ?? '—');

const philosophyDescription = (key) => ({
    stars:      'mise sur 2-3 joueurs vedettes et complète avec des profils modestes.',
    collective: 'vise un effectif homogène, sans écarts de niveau entre les joueurs.',
    balanced:   'cherche un mix raisonnable entre quelques bons joueurs et une base solide.',
    economist:  'privilégie les bonnes affaires et garde du budget en réserve pour la saison.',
}[key] ?? '');

function selectTeam(team) {
    selectedTeam.value  = team;
    startForm.team_id   = team.id;
}

function startWithTeam() {
    if (!startForm.team_id) return;
    startForm.post(route('game-saves.start'), { preserveScroll: true });
}

// ==========================
//   STATS
// ==========================
const avgStat = (key) => {
    if (!roster.value.length) return 0;
    const sum = roster.value.reduce((acc, p) => acc + Number(p[key] ?? p.stats?.[key] ?? 0), 0);
    return Math.round(sum / roster.value.length);
};

const avgAttack  = computed(() => avgStat('attack'));
const avgDefense = computed(() => avgStat('defense'));
const avgStamina = computed(() => avgStat('stamina'));
const avgSpeed   = computed(() => avgStat('speed'));

const overallOf = (p) => {
    if (!p) return 0;
    const keys = ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle'];
    const vals = keys.map(k => Number(p[k] ?? p.stats?.[k] ?? 0)).filter(v => isFinite(v));
    return vals.length ? Math.round(vals.reduce((a, b) => a + b, 0) / vals.length) : 0;
};

const keyStatsFor = (pos) => {
    const p = (pos ?? '').toUpperCase();
    if (p.includes('GK'))  return ['hand_save', 'punch_save', 'defense'];
    if (p.includes('DEF')) return ['defense', 'tackle', 'block'];
    if (p.includes('MDF') || p.includes('MOF') || p.includes('MID')) return ['pass', 'intercept', 'attack'];
    if (p.includes('ATT') || p.includes('FOR')) return ['shot', 'dribble', 'attack'];
    return ['attack', 'defense', 'pass'];
};

const statShortLabel = (k) => ({
    shot: 'Tir', pass: 'Pass', dribble: 'Drib', attack: 'Att',
    defense: 'Def', speed: 'Vit', block: 'Blk', intercept: 'Int',
    tackle: 'Tac', stamina: 'End', hand_save: 'Main', punch_save: 'Poing',
}[k] ?? k);

// ==========================
//   RADAR
// ==========================
const RADAR_STATS = [
    { key: 'shot',    label: 'Tir'     },
    { key: 'pass',    label: 'Passe'   },
    { key: 'dribble', label: 'Dribble' },
    { key: 'defense', label: 'Défense' },
    { key: 'speed',   label: 'Vitesse' },
    { key: 'stamina', label: 'Stamina' },
];

const radarPoints = computed(() => {
    if (!roster.value.length) return RADAR_STATS.map((s, i) => {
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return { x: 80, y: 80, lx: 80 + 74 * Math.cos(angle), ly: 80 + 74 * Math.sin(angle), label: s.label };
    });
    const cx = 80, cy = 80, r = 60;
    return RADAR_STATS.map((s, i) => {
        const val   = Math.min(avgStat(s.key) / 100, 1);
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return {
            x:  cx + r * val * Math.cos(angle),
            y:  cy + r * val * Math.sin(angle),
            lx: cx + (r + 14) * Math.cos(angle),
            ly: cy + (r + 14) * Math.sin(angle),
            label: s.label,
        };
    });
});

const radarPolygon = computed(() => radarPoints.value.map(p => `${p.x},${p.y}`).join(' '));

const radarGrids = computed(() => {
    const cx = 80, cy = 80, r = 60;
    return [0.25, 0.5, 0.75, 1.0].map(scale =>
        RADAR_STATS.map((_, i) => {
            const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
            return `${cx + r * scale * Math.cos(angle)},${cy + r * scale * Math.sin(angle)}`;
        }).join(' ')
    );
});

const radarAxes = computed(() => {
    const cx = 80, cy = 80, r = 60;
    return RADAR_STATS.map((_, i) => {
        const angle = (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;
        return { x2: cx + r * Math.cos(angle), y2: cy + r * Math.sin(angle) };
    });
});

// ==========================
//   URLS
// ==========================
const teamLogoUrl = (team) => {
    const path = team?.logo_path;
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/')) return path;
    if (path.startsWith('teams/')) return '/images/' + path;
    return '/' + path;
};

const playerPhotoUrl = (player) => {
    if (player?.photo_path) return `/storage/${player.photo_path}`;
    if (player?.photo?.path) return `/storage/${player.photo.path}`;
    return null;
};

const label = props.label;
</script>
