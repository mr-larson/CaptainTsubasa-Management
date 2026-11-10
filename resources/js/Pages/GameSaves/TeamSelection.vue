<!-- resources/js/Pages/GameSaves/TeamSelection.vue -->
<template>
    <Head title="Choix de l'équipe" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Choix de l'équipe</H2>
        </template>

        <div class="min-h-full bg-slate-50 p-3 sm:p-6">

            <!-- Header -->
            <div class="max-w-7xl mx-auto mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-400">
                            Partie : <span class="font-semibold text-slate-600">{{ label || 'Sans nom' }}</span>
                            &nbsp;•&nbsp;
                            <span v-if="periodPackage" class="font-semibold text-teal-600">📦 {{ periodPackage.name }}</span>
                            <span v-else>Période : <span class="font-semibold text-slate-600">Collège</span></span>
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

            <div class="max-w-9xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

                    <!-- Colonne gauche : liste équipes -->
                    <!-- overflow-hidden + min-h-0 : la carte ne pilote pas la hauteur de la rangée — elle s'aligne
                         sur la colonne du milieu (profil → bouton « Jouer avec »). La liste défile à l'intérieur. -->
                    <div class="col-span-1 lg:col-span-2 border border-slate-200 rounded-xl bg-white p-3 flex flex-col gap-2 min-h-0 max-h-64 lg:max-h-none overflow-hidden">
                        <!-- Recherche -->
                        <div class="relative shrink-0">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                            </svg>
                            <input type="search" v-model="searchQuery"
                                   class="w-full pl-8 pr-3 py-1.5" :class="fieldClass({ size: 'sm' })"
                                   placeholder="Rechercher..."/>
                        </div>

                        <!-- Compteur -->
                        <div class="text-2xs text-slate-400 font-semibold px-1 shrink-0">
                            {{ filteredTeams.length }} équipe(s)
                        </div>

                        <!-- Liste scrollable -->
                        <div class="flex-1 min-h-0 overflow-y-auto space-y-1 pr-0.5">
                            <button v-for="team in filteredTeams" :key="team.id" type="button"
                                    @click="selectTeam(team)"
                                    class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-semibold border transition-all text-left"
                                    :class="(isMultiMode ? isHumanTeam(team) : startForm.team_id === team.id)
                                ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                                : (selectedTeam?.id === team.id
                                    ? 'bg-teal-50 text-slate-700 border-teal-300 ring-1 ring-teal-200'
                                    : 'bg-white text-slate-600 border-slate-100 hover:border-teal-200 hover:bg-teal-50')">
                                <div class="w-7 h-7 rounded-lg overflow-hidden shrink-0 bg-slate-100 border border-slate-200 flex items-center justify-center">
                                    <img loading="lazy" decoding="async" v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt=""/>
                                    <span v-else class="text-[8px] text-slate-400">—</span>
                                </div>
                                <span class="truncate">{{ team.name }}</span>
                                <span v-if="teamNote(team) != null"
                                      class="ml-auto shrink-0 px-1.5 py-0.5 rounded-md text-2xs font-black tabular-nums"
                                      :class="(isMultiMode ? isHumanTeam(team) : startForm.team_id === team.id) ? 'bg-white/90 text-teal-700' : 'bg-slate-100 text-slate-600'"
                                      title="Note d'équipe (niveau du onze, moral, endurance)">{{ teamNote(team) }}</span>
                                <span v-if="isMultiMode && isHumanTeam(team)"
                                      class="shrink-0 w-5 h-5 rounded-full bg-white/90 text-teal-700 text-2xs font-black flex items-center justify-center"
                                      :class="teamNote(team) == null ? 'ml-auto' : ''">
                                    {{ seatOf(team) }}
                                </span>
                            </button>
                            <div v-if="!filteredTeams.length" class="text-xs text-slate-400 italic text-center py-4">
                                Aucune équipe
                            </div>
                        </div>
                    </div>

                    <!-- Panneau principal -->
                    <div v-if="selectedTeam" class="col-span-1 lg:col-span-10 grid grid-cols-1 lg:grid-cols-12 gap-4">

                        <!-- Profil équipe -->
                        <div class="col-span-1 lg:col-span-3 flex flex-col gap-3">

                            <!-- Logo + infos -->
                            <div class="border border-slate-200 rounded-xl bg-white p-5">
                                <div class="flex flex-col items-center gap-3 mb-4">
                                    <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-50 border border-slate-200 flex items-center justify-center">
                                        <img loading="lazy" decoding="async" v-if="teamLogoUrl(selectedTeam)" :src="teamLogoUrl(selectedTeam)" class="w-full h-full object-contain" alt=""/>
                                        <span v-else class="text-4xl">🏟️</span>
                                    </div>
                                    <div class="text-center">
                                        <h2 class="text-lg font-black text-slate-700">{{ selectedTeam.name }}</h2>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ selectedTeam.description ?? '—' }}</p>
                                        <div class="mt-2 flex justify-center">
                                            <TeamStyleBadges :team="selectedTeam" size="sm" />
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-slate-50 p-2.5 text-center text-xs">
                                    <div class="text-lg font-black text-teal-600">{{ startBudget.toLocaleString('fr-FR') }} <GameBanknote /></div>
                                    <div class="font-semibold text-slate-400">Budget de départ</div>
                                    <div class="mt-0.5 text-[10px] leading-snug text-slate-400">{{ roster.length }} joueur{{ roster.length > 1 ? 's' : '' }} sous contrat</div>
                                </div>
                            </div>

                            <!-- Stats moyennes (prebuilt uniquement) -->
                            <div v-if="gameMode !== 'draft'" class="border border-slate-200 rounded-xl bg-white p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-2xs font-bold uppercase tracking-wider text-slate-400">Forces</h4>
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-teal-500"
                                            :aria-expanded="showTeamStats" aria-controls="team-selection-stats"
                                            :aria-label="showTeamStats ? 'Masquer les statistiques' : 'Afficher les statistiques'"
                                            :title="showTeamStats ? 'Masquer les statistiques' : 'Afficher les statistiques'"
                                            @click="showTeamStats = !showTeamStats">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             class="h-4 w-4" :class="{ 'rotate-180': showTeamStats }">
                                            <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-show="showTeamStats" id="team-selection-stats" class="mt-2">
                                    <div class="space-y-2">
                                        <div v-for="stat in teamStats" :key="stat.key" class="flex items-center gap-2 text-xs">
                                            <span class="w-16 text-slate-500 shrink-0">{{ stat.label }}</span>
                                            <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full" :class="stat.color"
                                                     :style="{ width: Math.min(stat.value ?? 0, 100) + '%' }"></div>
                                            </div>
                                            <span class="w-9 shrink-0 whitespace-nowrap text-right font-black" :class="stat.text ?? 'text-slate-600'">
                                                {{ stat.value ?? (stat.key === 'luck' && roster.length ? 'À tirer' : '—') }}
                                            </span>
                                        </div>
                                    </div>
                                    <p v-if="roster.length" class="mt-3 text-2xs text-slate-500">
                                        Arrêt main et Poings sont calculés sur les gardiens.
                                        <template v-if="gameConfig?.initial_stamina_random !== false">L’endurance affichée est la note de référence avant le tirage de départ.</template>
                                    </p>
                                </div>
                                <div v-if="teamRating" class="mt-3 flex items-center justify-between gap-2 border-t border-slate-200 pt-3"
                                     :title="`Niveau du onze probable ${teamRating.level} · moral ${signedPct(teamRating.morale)} · endurance ${signedPct(teamRating.stamina)}`">
                                    <div>
                                        <div class="text-xs font-semibold text-slate-700">Note globale</div>
                                        <div class="mt-0.5 text-2xs text-slate-500">
                                            Niveau {{ teamRating.level }} · moral {{ signedPct(teamRating.morale) }} · endur. {{ signedPct(teamRating.stamina) }}
                                        </div>
                                    </div>
                                    <strong class="text-lg text-violet-600">{{ teamRating.note }}</strong>
                                </div>
                            </div>

                            <!-- Info draft (draft uniquement) -->
                            <div v-if="gameMode === 'draft'" class="border border-amber-200 rounded-xl bg-amber-50 p-4">
                                <h4 class="text-2xs font-bold text-amber-600 uppercase tracking-wider mb-2">🎯 Mode Draft</h4>
                                <div class="space-y-2 text-xs text-amber-700">
                                    <p>L'équipe démarre <span class="font-bold">sans joueur</span>. Tu construiras ton effectif lors du draft.</p>
                                    <p>Chaque équipe reçoit une <span class="font-bold">enveloppe de {{ draftBudget }} <GameBanknote /></span> réservée au recrutement pendant la draft.</p>
                                    <p>Ordre de pioche : <span class="font-bold">aléatoire</span> (tirage au sort).</p>
                                    <p>Objectif : constituer un effectif de <span class="font-bold">14 à 18 joueurs</span>.</p>
                                </div>
                            </div>

                            <!-- Radar (prebuilt uniquement) -->
                            <div v-if="gameMode !== 'draft'" class="border border-slate-200 rounded-xl bg-white p-4 flex flex-col items-center">
                                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2 self-start">Profil</h4>
                                <svg viewBox="0 0 160 160" class="w-36 h-36">
                                    <polygon class="stroke-slate-200" v-for="(pts,i) in radarGrids" :key="i" :points="pts" fill="none" stroke-width="0.8"/>
                                    <line class="stroke-slate-200" v-for="(ax,i) in radarAxes" :key="'a'+i" x1="80" y1="80" :x2="ax.x2" :y2="ax.y2" stroke-width="0.8"/>
                                    <polygon class="stroke-teal-500 fill-teal-500/20" :points="radarPolygon" stroke-width="1.5" stroke-linejoin="round"/>
                                    <circle class="fill-teal-500 stroke-white" v-for="(pt,i) in radarPoints" :key="'p'+i" :cx="pt.x" :cy="pt.y" r="2" stroke-width="1"/>
                                    <text class="fill-slate-400" v-for="(pt,i) in radarPoints" :key="'t'+i" :x="pt.lx" :y="pt.ly" text-anchor="middle" dominant-baseline="middle" font-size="7" font-weight="600">{{ pt.label }}</text>
                                </svg>
                            </div>

                            <!-- Action — DRAFT : lancer directement (mono-équipe) -->
                            <ButtonPrimary v-if="!isMultiMode" type="button" size="lg" class="w-full"
                                           :disabled="!startForm.team_id || startForm.processing"
                                           @click="startWithTeam">
                                <span v-if="startForm.processing">Chargement...</span>
                                <span v-else>🎯 Drafter avec {{ selectedTeam.name }}</span>
                            </ButtonPrimary>

                            <!-- Action — PREBUILT : hot-seat multi-manager -->
                            <template v-else>
                                <!-- Solo : lancement direct, sans passer par la liste des managers -->
                                <p v-if="!humanTeams.length" class="-mb-1 text-center text-xs font-semibold text-slate-600">
                                    {{ launchRecap }}
                                </p>
                                <ButtonPrimary v-if="!humanTeams.length" type="button" size="lg" class="w-full"
                                               :disabled="startForm.processing"
                                               @click="startSolo(selectedTeam)">
                                    <span v-if="startForm.processing">Chargement...</span>
                                    <span v-else>▶ Jouer avec {{ selectedTeam.name }}</span>
                                </ButtonPrimary>
                                <button type="button"
                                        class="w-full min-h-11 px-4 rounded-full font-bold text-sm transition-colors flex items-center justify-center gap-2"
                                        :class="isHumanTeam(selectedTeam)
                        ? 'bg-white text-rose-600 border border-rose-300 hover:bg-rose-50'
                        : humanTeams.length ? 'bg-teal-500 hover:bg-teal-600 text-white shadow-md'
                        : 'bg-cyan-500 text-white shadow-md hover:bg-cyan-600'"
                                        @click="toggleHumanTeam(selectedTeam)">
                                    <template v-if="isHumanTeam(selectedTeam)">
                                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                                        <span class="truncate">Retirer le joueur {{ seatOf(selectedTeam) }}</span>
                                    </template>
                                    <template v-else>
                                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                        <span class="truncate">{{ humanTeams.length ? `Ajouter en joueur ${humanTeams.length + 1}` : 'Partie à plusieurs' }}</span>
                                    </template>
                                </button>
                                <p class="-mt-2 text-[11px] text-slate-400 text-center leading-snug">
                                    Chaque équipe pilotée est confiée à un manager humain ; les autres sont gérées par l'IA. Les joueurs jouent à tour de rôle sur cet écran.
                                </p>

                                <!-- Récap hot-seat + lancement -->
                                <div class="border border-slate-200 rounded-xl bg-white p-4">
                                    <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                        Managers humains — {{ humanTeams.length }}
                                    </h4>

                                    <div v-if="humanTeams.length" class="space-y-1.5 mb-3">
                                        <div v-for="(t, i) in humanTeams" :key="t.id"
                                             class="flex items-center gap-2 px-2 py-1.5 rounded-lg bg-slate-50 border border-slate-100">
                                            <span class="w-5 h-5 rounded-full bg-teal-500 text-white text-2xs font-black flex items-center justify-center shrink-0">{{ i + 1 }}</span>
                                            <div class="w-6 h-6 rounded-md overflow-hidden bg-white border border-slate-200 shrink-0 flex items-center justify-center">
                                                <img loading="lazy" decoding="async" v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" class="w-full h-full object-contain" alt=""/>
                                                <span v-else class="text-[8px] text-slate-400">—</span>
                                            </div>
                                            <span class="flex-1 truncate text-xs font-semibold text-slate-700">{{ t.name }}</span>
                                            <button type="button" @click="removeHumanTeam(t)"
                                                    class="shrink-0 text-slate-300 hover:text-rose-500 text-base leading-none">×</button>
                                        </div>
                                    </div>
                                    <p v-else class="text-xs text-slate-400 italic mb-3">
                                        Aucune équipe pilotée pour l'instant : choisis une équipe à gauche puis clique sur « Piloter ».
                                    </p>

                                    <p v-if="humanTeams.length" class="mb-2 text-center text-xs font-semibold text-slate-600">
                                        {{ launchRecap }}
                                    </p>

                                    <ButtonPrimary type="button" size="lg" class="w-full"
                                                   :disabled="!humanTeams.length || startForm.processing"
                                                   @click="startWithTeam">
                                        <span v-if="startForm.processing">Chargement...</span>
                                        <span v-else>{{ gameMode === 'draft' ? '🎯 Lancer le draft' : '▶ Lancer la partie' }} ({{ humanTeams.length }} joueur{{ humanTeams.length > 1 ? 's' : '' }})</span>
                                    </ButtonPrimary>
                                </div>
                            </template>
                        </div>

                        <!-- Colonne droite : effectif (prebuilt) ou aperçu style (draft) -->
                        <div class="col-span-1 lg:col-span-9 border border-slate-200 rounded-xl bg-white p-4 flex flex-col">

                            <!-- Mode prebuilt : effectif complet -->
                            <template v-if="gameMode !== 'draft'">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 shrink-0">
                                    Effectif — {{ roster.length }} joueur(s)
                                </h3>

                                <div v-if="roster.length" class="flex-1 min-h-0 overflow-y-auto pr-1 space-y-3">
                                    <!-- Groupes de postes : GK → DEF → MID → ATT, triés par note -->
                                    <div v-for="group in groupedRoster" :key="group.key">
                                        <div class="flex items-center gap-2 mb-1.5 px-0.5">
                                            <span class="text-2xs font-black uppercase tracking-wider" :class="group.text">{{ group.label }}</span>
                                            <span class="text-2xs font-bold text-slate-300 tabular-nums">{{ group.players.length }}</span>
                                            <div class="flex-1 h-px bg-slate-100"></div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <button v-for="player in group.players" :key="player.id" type="button"
                                                 @click="detailPlayer = player"
                                                 :title="`Voir la fiche de ${player.firstname} ${player.lastname}`"
                                                 class="w-full text-left flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50 hover:bg-white hover:border-teal-200 hover:shadow-sm transition-all cursor-pointer">
                                                <div class="relative shrink-0">
                                                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-200 border border-slate-100">
                                                        <PlayerPhoto :src="playerPhotoUrl(player)" class="w-full h-full object-cover" alt="" />
                                                        <div v-if="!playerPhotoUrl(player)" class="w-full h-full flex items-center justify-center text-slate-400 text-lg">👤</div>
                                                    </div>
                                                    <span v-if="nationalityFlag(player)"
                                                          class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-white border border-slate-200 flex items-center justify-center text-[11px] leading-none shadow-sm"
                                                          :title="player.nationality">
                                                        {{ nationalityFlag(player) }}
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-bold text-slate-700 truncate">
                                                            {{ player.firstname }} {{ player.lastname }}
                                                        </span>
                                                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wide"
                                                              :class="posBadgeClass(player.position)">
                                                            {{ positionLabel(player.position) }}
                                                        </span>
                                                    </div>
                                                    <!-- Stats clés (adaptées au poste) : libellé, valeur teintée, mini-barre colorée -->
                                                    <div class="flex gap-2 mt-1.5">
                                                        <div v-for="k in keyStatsFor(player.position)" :key="k" class="flex-1 min-w-0">
                                                            <div class="flex items-center justify-between gap-1">
                                                                <span class="text-[8px] font-semibold uppercase text-slate-400 truncate">{{ statLabel(k) }}</span>
                                                                <span class="text-2xs font-black tabular-nums" :class="statTone(statValue(player, k))">
                                                                    {{ statValue(player, k) }}
                                                                </span>
                                                            </div>
                                                            <div class="h-1 bg-slate-200 rounded-full overflow-hidden mt-0.5">
                                                                <div class="h-full rounded-full" :class="statColor(k)"
                                                                     :style="{ width: Math.min(statValue(player, k), 100) + '%' }"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Endurance : du rouge (épuisé) au vert (en forme) -->
                                                    <div class="flex items-center gap-1.5 mt-1.5" :title="`Endurance ${statValue(player, 'stamina')}`">
                                                        <span class="text-[8px] font-semibold uppercase text-slate-400 shrink-0">Endurance</span>
                                                        <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full" :class="staminaBar(statValue(player, 'stamina'))"
                                                                 :style="{ width: Math.min(statValue(player, 'stamina'), 100) + '%' }"></div>
                                                        </div>
                                                        <span class="text-2xs font-black tabular-nums shrink-0" :class="staminaText(statValue(player, 'stamina'))">
                                                            {{ statValue(player, 'stamina') }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col items-center gap-0.5 shrink-0">
                                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-black border-2"
                                                         :class="overallBadgeClass(overallOf(player))">
                                                        {{ overallOf(player) }}
                                                    </div>
                                                    <span class="text-[7px] font-bold uppercase text-slate-300 tracking-wider">Note</span>
                                                    <span v-if="player.morale_preview != null"
                                                          class="text-2xs font-black tabular-nums"
                                                          :class="moraleState(player.morale_preview).text"
                                                          :title="`Moral de départ : ${moraleState(player.morale_preview).label}`">
                                                        {{ moraleState(player.morale_preview).emoji }} {{ player.morale_preview }}
                                                    </span>
                                                </div>
                                            </button>
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
                                        <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">Style tactique</h4>
                                        <p class="text-sm text-slate-700">
                                            Cette équipe jouera avec un style
                                            <span class="font-bold">{{ styleLabel(selectedTeam.tactical_style) }}</span>.
                                            L'IA adaptera ses actions en match, son entraînement et sa stratégie selon ce profil.
                                        </p>
                                    </div>

                                    <!-- Philosophie -->
                                    <div class="border border-slate-100 rounded-xl bg-slate-50 p-4">
                                        <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">Philosophie de gestion</h4>
                                        <p class="text-sm text-slate-700">
                                            Approche
                                            <span class="font-bold">{{ philosophyLabel(selectedTeam.management_philosophy) }}</span> :
                                            {{ philosophyDescription(selectedTeam.management_philosophy) }}
                                        </p>
                                    </div>

                                    <!-- Règles du draft -->
                                    <div class="border border-amber-100 rounded-xl bg-amber-50/50 p-4">
                                        <h4 class="text-2xs font-bold text-amber-500 uppercase tracking-wider mb-3">Comment ça marche</h4>
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
                                                <p>Budget de draft : <span class="font-bold">{{ draftBudget }} <GameBanknote /></span>. Budget de départ en saison : <span class="font-bold">{{ startBudget }} <GameBanknote /></span>.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Placeholder si aucune équipe sélectionnée -->
                    <div v-else class="col-span-1 lg:col-span-10 min-h-[400px] border border-dashed border-slate-300 rounded-xl flex flex-col items-center justify-center text-slate-400">
                        <div class="text-4xl mb-3">⚽</div>
                        <p class="font-semibold">Sélectionne une équipe pour voir son profil</p>
                    </div>

                </div><!-- fin grid 12 -->
            </div><!-- fin max-w -->
        </div>
        <!-- Fiche détaillée d'un joueur (clic sur sa carte) -->
        <Modal :show="!!detailPlayer" max-width="2xl" @close="detailPlayer = null">
            <div v-if="detailPlayer" class="p-5 space-y-4">
                <div class="flex items-start gap-4">
                    <div class="relative shrink-0">
                        <div class="w-20 h-20 rounded-xl overflow-hidden bg-slate-200 border border-slate-100">
                            <PlayerPhoto :src="playerPhotoUrl(detailPlayer)" class="w-full h-full object-cover" alt="" />
                            <div v-if="!playerPhotoUrl(detailPlayer)" class="w-full h-full flex items-center justify-center text-slate-400 text-2xl">👤</div>
                        </div>
                        <span v-if="nationalityFlag(detailPlayer)"
                              class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-white border border-slate-200 flex items-center justify-center text-xs shadow-sm"
                              :title="detailPlayer.nationality">{{ nationalityFlag(detailPlayer) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-bold text-slate-700">{{ detailPlayer.firstname }} {{ detailPlayer.lastname }}</h3>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                            <span class="px-1.5 py-0.5 rounded text-2xs font-black uppercase tracking-wide" :class="posBadgeClass(detailPlayer.position)">
                                {{ positionLabel(detailPlayer.position) }}
                            </span>
                            <SecondaryPositions :player="detailPlayer" />
                        </div>
                        <p v-if="detailPlayer.description" class="mt-2 text-xs text-slate-500 italic">{{ detailPlayer.description }}</p>
                    </div>
                    <div class="flex flex-col items-center gap-0.5 shrink-0">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-black border-2"
                             :class="overallBadgeClass(overallOf(detailPlayer))">{{ overallOf(detailPlayer) }}</div>
                        <span class="text-[8px] font-bold uppercase text-slate-400 tracking-wider">Note</span>
                    </div>
                    <button type="button" @click="detailPlayer = null" aria-label="Fermer"
                            class="shrink-0 -mt-1 -mr-1 w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100">✕</button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Moral -->
                    <div v-if="detailPlayer.morale_preview != null" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-bold text-slate-500 uppercase tracking-wider text-2xs">Moral</span>
                            <span class="font-black" :class="moraleState(detailPlayer.morale_preview).text">
                                {{ moraleState(detailPlayer.morale_preview).emoji }} {{ moraleState(detailPlayer.morale_preview).label }} · {{ detailPlayer.morale_preview }}
                            </span>
                        </div>
                        <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full" :class="moraleState(detailPlayer.morale_preview).bar"
                                 :style="{ width: Math.min(detailPlayer.morale_preview, 100) + '%' }"></div>
                        </div>
                        <p class="mt-1.5 text-2xs text-slate-500">
                            Effet en match : {{ moraleMatchEffect(detailPlayer.morale_preview)?.pct ?? 'aucun' }}
                        </p>
                    </div>
                    <!-- Endurance -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-bold text-slate-500 uppercase tracking-wider text-2xs">Endurance</span>
                            <span class="font-black" :class="staminaText(statValue(detailPlayer, 'stamina'))">{{ statValue(detailPlayer, 'stamina') }}</span>
                        </div>
                        <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full" :class="staminaBar(statValue(detailPlayer, 'stamina'))"
                                 :style="{ width: Math.min(statValue(detailPlayer, 'stamina'), 100) + '%' }"></div>
                        </div>
                        <p class="mt-1.5 text-2xs text-slate-500">Plus elle est haute, plus il tient le match et enchaîne les actions.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <RadarChart :player="detailPlayer" accent="teal" />
                    <StatBars :player="detailPlayer" />
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { positionLabel } from '@/Enums/positionBadges.js';
import { positionGroupClass } from '@/Enums/positionBadges.js';
import { fieldClass } from '@/Components/fieldStyles';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import H2 from '@/Components/H2.vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';
import Modal from '@/Components/Modal.vue';
import RadarChart from '@/Pages/GameSaves/Play/components/RadarChart.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import SecondaryPositions from '@/Pages/GameSaves/Play/components/SecondaryPositions.vue';
import TeamStyleBadges from '@/Pages/GameSaves/Play/components/TeamStyleBadges.vue';
import { STAT_COLORS, usePlayerUtils } from '@/Pages/GameSaves/Play/usePlayerUtils.js';
import { averageTeamStat, TEAM_STATS } from '@/Pages/GameSaves/Play/teamStats.js';

const props = defineProps({
    label:    { type: String, default: null },
    period:   { type: String, required: true },
    // Équipes à afficher, façonnées comme des Team canon (contracts[].player) :
    // celles du package choisi (id synthétique = team_key) si periodPackage
    // est renseigné, sinon l'effectif standard — décidé en amont, à l'écran
    // de création (cf. GameSaveController::store()).
    teams:    { type: Array,  required: true },
    gameMode: { type: String, default: 'prebuilt' },
    competitionType: { type: String, default: 'college_league' },
    gameConfig:      { type: Object, default: null },
    // Budget de départ réel d'une équipe pilotée (selon la difficulté) ; null = budget du club.
    humanStartBudget: { type: Number, default: null },
    // Graine du moral initial : null si l'option "moral aléatoire" est désactivée
    // (également null en mode package, cf. store()).
    moraleSeed:      { type: Number, default: null },
    // Package de période choisi à la création (ou null pour l'effectif standard) :
    // remplace entièrement le roster, figé pour cet écran (pas de bascule ici).
    periodPackage:   { type: Object, default: null },
});

const searchQuery  = ref('');
const selectedTeam = ref(null);
const showTeamStats = ref(true);

// Helpers d'affichage partagés (cohérence avec le reste de l'app, cf. dashboard / effectif).
const {
    overallOf, keyStatsFor, statLabel, statColor,
    positionGroup, playerPhotoUrl, teamLogoUrl, nationalityFlag, moraleState, moraleMatchEffect,
} = usePlayerUtils();

const filteredTeams = computed(() => {
    if (!searchQuery.value) return props.teams;
    return props.teams.filter(t => t.name?.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const startForm = useForm({
    label:     props.label || '',
    period:    props.period,
    team_id:   null,
    team_ids:  [],
    game_mode: props.gameMode,
    competition_type: props.competitionType,
    game_config: props.gameConfig,
    morale_seed: props.moraleSeed,
    period_package_id: props.periodPackage?.id ?? null,
});

// Hot-seat multi-manager : équipes humaines dans l'ordre des sièges.
// Disponible dans les deux modes (prebuilt ET draft).
const isMultiMode = computed(() => true);
const humanTeams  = ref([]);

// Récapitulatif avant lancement (« Ligue · Solo · Effectifs pré-faits · Nankatsu »),
// pour vérifier ses choix après être passé par la configuration.
const launchRecap = computed(() => [
    props.competitionType === 'college_league' ? 'Ligue' : 'Coupe du Monde',
    humanTeams.value.length > 1 ? `Hot-seat ${humanTeams.value.length} joueurs` : 'Solo',
    props.gameMode === 'draft' ? 'Draft' : 'Effectifs pré-faits',
    (humanTeams.value.length ? humanTeams.value : [selectedTeam.value]).filter(Boolean).map(t => t.name).join(', '),
].filter(Boolean).join(' · '));

const isHumanTeam = (team) => humanTeams.value.some(t => t.id === team.id);
const seatOf      = (team) => humanTeams.value.findIndex(t => t.id === team.id) + 1;

function toggleHumanTeam(team) {
    const i = humanTeams.value.findIndex(t => t.id === team.id);
    if (i === -1) humanTeams.value.push(team);
    else humanTeams.value.splice(i, 1);
}

function removeHumanTeam(team) {
    const i = humanTeams.value.findIndex(t => t.id === team.id);
    if (i !== -1) humanTeams.value.splice(i, 1);
}

const roster = computed(() => {
    if (!selectedTeam.value?.contracts) return [];
    return selectedTeam.value.contracts.map(c => c.player).filter(Boolean);
});

// Effectif groupé par poste (GK → DEF → MID → ATT → autres), trié par note
// décroissante dans chaque groupe — pour scanner l'effectif plus vite.
const POSITION_GROUPS = [
    { key: 'GK',    label: 'Gardien',   text: 'text-violet-600' },
    { key: 'DEF',   label: 'Défense',   text: 'text-blue-600'   },
    { key: 'MID',   label: 'Milieu',    text: 'text-teal-600'   },
    { key: 'ATT',   label: 'Attaque',   text: 'text-orange-600' },
    { key: 'OTHER', label: 'Autres',    text: 'text-slate-500'  },
];

const groupedRoster = computed(() =>
    POSITION_GROUPS
        .map(g => ({
            ...g,
            players: roster.value
                .filter(p => positionGroup(p.position) === g.key)
                .sort((a, b) => overallOf(b) - overallOf(a)),
        }))
        .filter(g => g.players.length)
);

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
    selectedTeam.value = team;
    // En draft, la sélection vaut directement choix de l'équipe (mono).
    if (!isMultiMode.value) startForm.team_id = team.id;
}

// Solo : une seule équipe pilotée, lancement immédiat.
function startSolo(team) {
    humanTeams.value = [team];
    startWithTeam();
}

function startWithTeam() {
    // Mode draft : une seule équipe.
    if (!isMultiMode.value) {
        if (!startForm.team_id) return;
        startForm
            .transform(d => ({ label: d.label, period: d.period, game_mode: d.game_mode, competition_type: d.competition_type, team_id: d.team_id, game_config: d.game_config, morale_seed: d.morale_seed }))
            .post(route('game-saves.start'), { preserveScroll: true });
        return;
    }

    // Mode prebuilt : 1 à N équipes humaines (hot-seat), dans l'ordre des sièges.
    // En mode package, t.id est le team_key (string) du package sélectionné.
    if (!humanTeams.value.length) return;
    startForm.team_ids = humanTeams.value.map(t => t.id);
    startForm
        .transform(d => ({
            label: d.label, period: d.period, game_mode: d.game_mode, competition_type: d.competition_type,
            team_ids: d.team_ids, game_config: d.game_config, morale_seed: d.morale_seed,
            period_package_id: d.period_package_id,
        }))
        .post(route('game-saves.start'), { preserveScroll: true });
}

// ==========================
//   STATS
// ==========================
const draftBudget = computed(() => props.gameConfig?.draft_budget ?? 5000);
const avgStat = (key) => averageTeamStat(roster.value, key);
const previewStat = (key) => {
    if (!roster.value.length) return null;
    if (key === 'luck' && props.gameConfig?.luck_random === false) return 50;
    if (key === 'stamina' && props.gameConfig?.initial_stamina_random === false) return 100;
    return avgStat(key);
};
// Moral moyen prévisualisé (null si l'option "moral aléatoire" est désactivée).
const avgMorale = computed(() => {
    const values = roster.value.map(p => p.morale_preview).filter(v => v != null);
    if (!values.length) return null;
    return Math.round(values.reduce((a, v) => a + Number(v), 0) / values.length);
});

const teamStats = computed(() => {
    const rows = TEAM_STATS.map(({ key, label, color }) => ({
        key, label,
        color: color ?? STAT_COLORS[key],
        value: previewStat(key),
    }));
    if (avgMorale.value != null) {
        const morale = moraleState(avgMorale.value);
        const afterStamina = rows.findIndex(stat => stat.key === 'stamina') + 1;
        rows.splice(afterStamina, 0, { key: 'morale', label: 'Moral', value: avgMorale.value, color: morale.bar, text: morale.text });
    }
    return rows;
});

// ==========================
//   NOTE D'ÉQUIPE
// ==========================
// Niveau du onze probable (moyenne des 11 meilleures notes), corrigé par le moral
// (effet en match moyen, -10 % à +5 %) et l'endurance moyenne (-10 % à +10 %
// autour de 60). Sert à comparer les équipes d'un coup d'œil.
const moralePct = (m) => parseFloat(moraleMatchEffect(m)?.pct ?? '0') || 0;
const teamRatingOf = (players) => {
    if (!players.length) return null;
    const best  = [...players].sort((a, b) => overallOf(b) - overallOf(a)).slice(0, 11);
    const level = best.reduce((sum, p) => sum + overallOf(p), 0) / best.length;
    const morales = players.map(p => p.morale_preview).filter(v => v != null);
    const morale  = morales.length ? morales.reduce((sum, m) => sum + moralePct(m), 0) / morales.length : 0;
    const stamina = players.reduce((sum, p) => sum + statValue(p, 'stamina'), 0) / players.length;
    const staminaAdj = Math.max(-10, Math.min(10, (stamina - 60) / 4));
    return {
        note:    Math.round(level * (1 + morale / 100) * (1 + staminaAdj / 100)),
        level:   Math.round(level),
        morale:  Math.round(morale * 10) / 10,
        stamina: Math.round(staminaAdj * 10) / 10,
    };
};
const teamRating = computed(() => teamRatingOf(roster.value));
const teamNote   = (team) => teamRatingOf((team.contracts ?? []).map(c => c.player).filter(Boolean))?.note ?? null;
const signedPct  = (v) => `${v > 0 ? '+' : ''}${v.toLocaleString('fr-FR')} %`;

// Endurance : couleur du rouge (épuisé) au vert (en pleine forme).
const staminaBar  = (v) => v >= 85 ? 'bg-emerald-500' : v >= 70 ? 'bg-lime-500' : v >= 50 ? 'bg-amber-400' : v >= 30 ? 'bg-orange-500' : 'bg-rose-500';
const staminaText = (v) => v >= 85 ? 'text-emerald-600' : v >= 70 ? 'text-lime-600' : v >= 50 ? 'text-amber-600' : v >= 30 ? 'text-orange-600' : 'text-rose-600';

// Fiche détaillée d'un joueur (modale au clic sur sa carte).
const detailPlayer = ref(null);

// overallOf / keyStatsFor / statLabel / statColor / positionGroup proviennent du
// composable partagé usePlayerUtils (déstructuré plus haut) — on n'ajoute ici que
// les helpers de mise en forme propres à cet écran.
const statValue = (p, k) => Number(p?.[k] ?? p?.stats?.[k] ?? 0);

// Teinte du chiffre selon la valeur (lecture rapide des forces / faiblesses).
const statTone = (v) => Number(v) >= 70 ? 'text-emerald-600'
    : Number(v) >= 50 ? 'text-amber-600'
    : 'text-slate-500';

// Pastille de note globale (mêmes seuils que les chiffres).
const overallBadgeClass = (o) => o >= 70 ? 'bg-emerald-50 border-emerald-300 text-emerald-700'
    : o >= 50 ? 'bg-amber-50 border-amber-300 text-amber-700'
    : 'bg-slate-100 border-slate-200 text-slate-500';

// Budget affiché = budget de départ réel en partie (difficulté), sinon celui du club.
const startBudget = computed(() => props.humanStartBudget ?? selectedTeam.value?.budget ?? 0);

// Badge de poste coloré (couleurs communes, libellé brut conservé).
const posBadgeClass = (pos) => positionGroupClass(positionGroup(pos));

// ==========================
//   RADAR
// ==========================
const RADAR_STATS = [
    { key: 'shot',    label: 'Tir'     },
    { key: 'pass',    label: 'Passe'   },
    { key: 'dribble', label: 'Dribble' },
    { key: 'defense', label: 'Défense' },
    { key: 'speed',   label: 'Vitesse' },
    { key: 'stamina', label: 'Endurance' },
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

const label = props.label;
</script>
