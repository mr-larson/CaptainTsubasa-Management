<script setup>
import { computed } from 'vue';
import Panel from '@/Components/Panel.vue';
import { PLAYER_STATS } from '@/Enums/playerStats.js';
import { usePlayerUtils } from '../usePlayerUtils.js';

// Carte « Moral » d'un joueur : moral et effet en match, relation coach,
// promesses, déclarations publiques et derniers changements.
// Partagée par Mon équipe (éditable) et Autres équipes (lecture seule, ou
// éditable avec l'option « Accès aux autres clubs »).
const props = defineProps({
    player:        { type: Object,  required: true },
    logs:          { type: Array,   default: () => [] },
    promises:      { type: Array,   default: () => [] },
    declarations:  { type: Array,   default: () => [] },
    currentWeek:   { type: Number,  default: 1 },
    currentSeason: { type: Number,  default: 1 },
    // false : consultation seule (pas de bouton Promettre / Presse)
    editable:      { type: Boolean, default: true },
});

const emit = defineEmits(['make-promise', 'make-declaration']);

const { moraleState, moraleSourceLabel, moraleMatchEffect, HEROIC_MORALE_THRESHOLD } = usePlayerUtils();

// ── Moral ────────────────────────────────────────────────────
const selectedPlayerMorale = computed(() => moraleState(props.player?.morale));

const selectedPlayerMoraleLogs = computed(() => (props.logs ?? []).filter(log => log.category !== 'stats'));
const statChanges = computed(() => (props.logs ?? []).filter(log => log.category === 'stats'));
const statLabel = (key) => PLAYER_STATS.find(stat => stat.key === key)?.label ?? ({ luck: 'Chance' }[key] ?? key);

const selectedPlayerMoraleEffect = computed(() => moraleMatchEffect(props.player?.morale));

const selectedPlayerIsHeroic = computed(() =>
    Number(props.player?.morale ?? 60) > HEROIC_MORALE_THRESHOLD
);

// ── Relation coach + promesses ───────────────────────────────
const selectedPlayerAffinity = computed(() => Number(props.player?.coach_affinity ?? 0));

const affinityDisplay = computed(() => {
    const a = selectedPlayerAffinity.value;
    if (a <= -50) return { label: 'Rupture',    text: 'text-rose-600',    bar: 'bg-rose-500' };
    if (a <= -30) return { label: 'Tendue',     text: 'text-orange-500',  bar: 'bg-orange-400' };
    if (a <   30) return { label: 'Neutre',     text: 'text-slate-500',   bar: 'bg-slate-400' };
    if (a <   50) return { label: 'Bonne',      text: 'text-teal-600',    bar: 'bg-teal-400' };
    return         { label: 'Excellente', text: 'text-emerald-600', bar: 'bg-emerald-500' };
});

const selectedPlayerPromises = computed(() =>
    props.promises ?? []
);

const activePromise = computed(() =>
    selectedPlayerPromises.value.find(p => p.status === 'pending') ?? null
);

const lastResolvedPromise = computed(() =>
    // 'expired' = promesse rendue caduque par l'intersaison : rien à afficher.
    selectedPlayerPromises.value.find(p => p.status === 'kept' || p.status === 'broken') ?? null
);

const PROMISE_TYPES = [
    { type: 'playing_time', icon: '🤝', label: 'Temps de jeu',  hint: 'Il jouera au moins un tiers du match (~30 min) à son prochain match', kept: 5,  broken: 10 },
    { type: 'starter',      icon: '⭐', label: 'Titularisation', hint: 'Il sera titulaire sur 4 matchs dans les 5 prochaines semaines', kept: 10, broken: 15 },
    { type: 'renewal',      icon: '📜', label: 'Prolongation',   hint: 'Tu lui signeras un nouveau contrat sous 4 semaines', kept: 15, broken: 20 },
    { type: 'rest',         icon: '💤', label: 'Repos',          hint: 'Il ne doit jouer aucun tour ni suivre d’entraînement cette semaine', kept: 5, broken: 10 },
];

const promiseTypeLabel = (type) => PROMISE_TYPES.find(t => t.type === type)?.label ?? type;

// ── Déclarations publiques ───────────────────────────────────
const DECLARATION_COOLDOWN_WEEKS = 3;

const lastDeclaration = computed(() =>
    (props.declarations ?? [])[0] ?? null
);

// Même règle que DeclarationService : le délai ne compte que dans la saison en cours.
const declarationOnCooldown = computed(() =>
    lastDeclaration.value
    && Number(lastDeclaration.value.season) === Number(props.currentSeason)
    && lastDeclaration.value.week > (props.currentWeek - DECLARATION_COOLDOWN_WEEKS)
);

const lastDeclarationSummary = computed(() => {
    const d = lastDeclaration.value;
    if (!d) return null;
    if (d.outcome === 'match_malus') return { text: '📣 Félicité — relâchement', positive: false };
    if (d.type === 'praise' && d.outcome === 'match_boost') return { text: '📣 Félicité — bonus au prochain match', positive: true };
    if (d.type === 'praise') return { text: '📣 Félicité', positive: true };
    if (d.outcome === 'match_boost') return { text: '📢 Critiqué — réaction d’orgueil, −3 moral', positive: false };
    if (d.outcome === 'proud_reaction') return { text: '📢 Critiqué — réaction d\'orgueil', positive: true };
    return { text: '📢 Critiqué — mal vécu', positive: false };
});
</script>

<template>
    <Panel title="Moral">
        <template #header-actions>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                  :class="selectedPlayerMorale.chip">
                {{ selectedPlayerMorale.emoji }} {{ selectedPlayerMorale.label }}
                <span class="opacity-60">{{ player.morale ?? 60 }}/100</span>
            </span>
        </template>

        <div class="h-2 bg-slate-200 rounded-full overflow-hidden mb-2">
            <div class="h-full rounded-full transition-all"
                 :class="selectedPlayerMorale.bar"
                 :style="{ width: Math.min(player.morale ?? 60, 100) + '%' }"></div>
        </div>

        <!-- Effets en match -->
        <div class="flex flex-wrap items-center gap-1.5 mb-3">
            <span v-if="selectedPlayerMoraleEffect"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-bold"
                  :class="selectedPlayerMoraleEffect.positive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  title="Appliqué à toutes les actions en match (duels, tirs, arrêts)">
                ⚔️ Effet en match : {{ selectedPlayerMoraleEffect.pct }}
            </span>
            <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-semibold bg-slate-100 text-slate-500">
                ⚔️ Aucun effet en match
            </span>
            <span v-if="selectedPlayerIsHeroic"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-bold bg-amber-100 text-amber-700"
                  title="5 % de chance de relancer gratuitement un duel perdu (une fois par match)">
                🔥 Dépassement de soi
            </span>
        </div>

        <!-- Relation coach -->
        <div class="border-t border-slate-200 pt-3 mb-3">
            <div class="flex items-center justify-between mb-1.5">
                <h5 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Relation coach</h5>
                <span class="text-[11px] font-bold" :class="affinityDisplay.text">
                    {{ affinityDisplay.label }}
                    <span class="opacity-60">{{ selectedPlayerAffinity > 0 ? '+' : '' }}{{ selectedPlayerAffinity }}</span>
                </span>
            </div>
            <div class="relative h-1.5 bg-slate-200 rounded-full overflow-hidden mb-2">
                <div class="absolute top-0 bottom-0 w-px bg-slate-400/60 left-1/2"></div>
                <div class="h-full rounded-full transition-all"
                     :class="affinityDisplay.bar"
                     :style="{ width: ((selectedPlayerAffinity + 100) / 2) + '%' }"></div>
            </div>

            <!-- Promesses -->
            <div class="flex flex-wrap items-center gap-1.5 mb-2">
                <span v-if="activePromise"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-bold bg-sky-100 text-sky-700"
                      :title="activePromise.type === 'renewal'
                          ? `Signe-lui un nouveau contrat avant la semaine ${activePromise.due_week}`
                          : activePromise.type === 'playing_time'
                              ? `Il doit jouer au moins ${activePromise.target_turns} tours à son prochain match où il est disponible (un match manqué pour blessure ou suspension ne compte pas)`
                              : activePromise.type === 'rest'
                                  ? 'Aucun match ni entraînement cette semaine'
                                  : `Il doit être titulaire sur ${activePromise.target_matches} matchs entre la semaine ${activePromise.start_week} et la semaine ${activePromise.due_week}`">
                    🤝 Promesse en cours — {{ promiseTypeLabel(activePromise.type) }}
                    <template v-if="activePromise.type === 'playing_time'">: ≥{{ activePromise.target_turns }} tours au prochain match</template>
                    <template v-else-if="activePromise.type === 'starter'">: {{ activePromise.target_matches }} matchs titulaire avant S{{ activePromise.due_week }}</template>
                    <template v-else-if="activePromise.type === 'rest'">: aucun match ni entraînement en S{{ activePromise.due_week }}</template>
                    <template v-else>avant S{{ activePromise.due_week }}</template>
                </span>
                <template v-else-if="editable">
                    <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Promettre :</span>
                    <button v-for="pt in PROMISE_TYPES" :key="pt.type" type="button"
                            @click="emit('make-promise', player, pt.type)"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-2xs font-bold border border-sky-300 bg-white text-sky-600 hover:bg-sky-50 transition-all"
                            :title="`${pt.hint}. Tenue : +${pt.kept} relation, +${pt.kept} moral. Rompue : −${pt.broken} relation, −${pt.broken} moral.`">
                        {{ pt.icon }} {{ pt.label }}
                    </button>
                </template>
                <span v-if="lastResolvedPromise"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-semibold"
                      :class="lastResolvedPromise.status === 'kept' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                    {{ lastResolvedPromise.status === 'kept' ? '✓ Promesse tenue' : '✗ Promesse rompue' }}
                    <template v-if="lastResolvedPromise.type === 'playing_time'">
                        ({{ lastResolvedPromise.played_turns ?? 0 }}/{{ lastResolvedPromise.target_turns }} tours)
                    </template>
                    <template v-else-if="lastResolvedPromise.type === 'starter'">
                        ({{ lastResolvedPromise.played_matches ?? 0 }}/{{ lastResolvedPromise.target_matches }})
                    </template>
                    <template v-else-if="lastResolvedPromise.type === 'rest'">· Repos</template>
                    <template v-else-if="lastResolvedPromise.type === 'renewal'">· Prolongation</template>
                </span>
            </div>

            <!-- Déclarations publiques -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span v-if="editable || lastDeclarationSummary" class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Presse :</span>
                <template v-if="editable && !declarationOnCooldown">
                    <button type="button"
                            @click="emit('make-declaration', player, 'praise')"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-2xs font-bold border border-emerald-300 bg-white text-emerald-600 hover:bg-emerald-50 transition-all"
                            title="Après un match avec plus de duels gagnés que perdus, +8 relation, +4 moral et +2 en attaque, défense et vitesse au prochain match. Avec plus de duels perdus que gagnés, 25 % de risque de relâchement (−2 en attaque, défense et vitesse au prochain match). Dans les autres cas, +4 relation.">
                        📣 Féliciter
                    </button>
                    <button type="button"
                            @click="emit('make-declaration', player, 'criticize')"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-2xs font-bold border border-rose-300 bg-white text-rose-600 hover:bg-rose-50 transition-all"
                            title="En méforme, 60 % de chances de gagner +3 en attaque, défense et vitesse pour le prochain match (−3 moral). Sinon, −5 moral. En forme, −8 moral et aucun bonus.">
                        📢 Critiquer
                    </button>
                </template>
                <span v-else-if="editable" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-semibold bg-slate-100 text-slate-400"
                      :title="`Prochaine déclaration possible en semaine ${lastDeclaration.week + 3}`">
                    ⏳ Déjà exprimé récemment
                </span>
                <span v-if="lastDeclarationSummary"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-semibold"
                      :class="lastDeclarationSummary.positive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                    {{ lastDeclarationSummary.text }} (S{{ lastDeclaration.week }})
                </span>
            </div>
        </div>

        <section class="space-y-2">
            <h5 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Derniers changements</h5>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div class="min-w-0 rounded-xl border border-slate-200 bg-white/60 p-3 space-y-2">
                    <h6 class="text-xs font-bold text-slate-600">🙂 Moral et relation coach</h6>
                    <div v-for="(log, i) in selectedPlayerMoraleLogs" :key="i" class="flex items-start gap-2 text-xs">
                        <span class="w-7 text-right font-black shrink-0" :class="log.value > 0 ? 'text-emerald-600' : 'text-rose-500'">{{ log.value > 0 ? '+' : '' }}{{ log.value }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-slate-600 break-words">{{ log.label }}</p>
                            <p class="text-2xs text-slate-400">{{ log.metric ?? 'Moral' }} · {{ log.source_label ?? moraleSourceLabel(log.source) }}</p>
                        </div>
                        <span class="text-2xs text-slate-400 shrink-0" :title="`Saison ${log.season}, semaine ${log.week}`">S{{ log.week }}</span>
                    </div>
                    <p v-if="!selectedPlayerMoraleLogs.length" class="text-xs text-slate-500 italic">Aucun changement récent de moral ou de relation.</p>
                </div>
                <div class="min-w-0 rounded-xl border border-slate-200 bg-white/60 p-3 space-y-2">
                    <h6 class="text-xs font-bold text-slate-600">📈 Progression des statistiques</h6>
                    <div v-for="(log, i) in statChanges" :key="i" class="flex items-start gap-2 text-xs">
                        <span class="w-7 text-right font-black shrink-0" :class="log.value > 0 ? 'text-emerald-600' : 'text-rose-500'">{{ log.value > 0 ? '+' : '' }}{{ log.value }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-600">{{ statLabel(log.stat) }} <span v-if="log.old != null && log.new != null" class="font-normal text-slate-400">{{ log.old }} → {{ log.new }}</span></p>
                            <p class="text-2xs text-slate-400">{{ log.source === 'training' ? '🏋️' : '⚽' }} {{ log.source_label }}</p>
                        </div>
                        <span class="text-2xs text-slate-400 shrink-0" :title="`Saison ${log.season}, semaine ${log.week}`">S{{ log.week }}</span>
                    </div>
                    <p v-if="!statChanges.length" class="text-xs text-slate-500 italic">Aucun gain de statistique enregistré récemment.</p>
                </div>
            </div>
        </section>
    </Panel>
</template>
