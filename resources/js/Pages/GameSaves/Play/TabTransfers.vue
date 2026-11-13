<script setup>
import { positionLabel } from '@/Enums/positionBadges.js';
import { fieldClass } from '@/Components/fieldStyles';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { computed, ref, watch } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import Panel from '@/Components/Panel.vue';
import PlayerAvatar from '@/Components/PlayerAvatar.vue';
import RecruitFilterBar from './components/RecruitFilterBar.vue';
import RecruitRow from './components/RecruitRow.vue';
import { ORIGIN_OPTIONS, useRecruitFilters } from './useRecruitFilters.js';
import PlayerIdentityCard from './components/PlayerIdentityCard.vue';
import PlayerSpecialMoves from './components/PlayerSpecialMoves.vue';
import PlayerCompareButton from './components/PlayerCompareButton.vue';
import PlayerComparison from './components/PlayerComparison.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';

const props = defineProps({
    availableFreePlayers: { type: Array,   required: true },
    visibleOrigins:       { type: Object,  default: () => ({}) },
    team:                 { type: Object,  default: null },
    teamBudget:           { type: Number,  required: true },
    showTransferModal:    { type: Boolean, required: true },
    transferTarget:       { type: Object,  default: null },
    transferMatches:      { type: Number,  required: true },
    transferMaxWeeks:     { type: Number,  required: true },
    transferSalary:       { type: Number,  required: true },
    transferReason:       { type: String,  required: true },
    transferTotalCost:    { type: Number,  required: true },
    transferProcessing:   { type: Boolean, default: false },
    transferHistory:      { type: Array, default: () => [] },
    roster:               { type: Array,   default: () => [] },
    // Joueur à ouvrir d'office (ex. conseil de recrutement du Dashboard)
    focusPlayer:          { type: Object,  default: null },
});

const emit = defineEmits([
    'open-modal', 'close-modal', 'confirm-transfer',
    'update:transferMatches', 'update:transferSalary', 'update:transferReason',
]);

// ==========================
//   HELPERS
// ==========================
const { overallOf, positionGroup, moraleState } = usePlayerUtils();


// ==========================
//   FILTRES ET TRI
// ==========================
const costOf = (p) => p.adjusted_cost ?? p.cost ?? 0;

// Note globale calculée une fois par joueur et par liste (au lieu de 3-4 fois
// par ligne à chaque re-rendu, ex. à chaque frappe dans la recherche).
const overallById = computed(() => new Map(props.availableFreePlayers.map(p => [p.id, overallOf(p)])));
const ovr = (p) => overallById.value.get(p.id) ?? overallOf(p);

// Comme le filtre serveur, une origine absente des réglages reste visible.
const visibleOriginOptions = computed(() => ORIGIN_OPTIONS.filter(({ key }) => {
    const enabled = props.visibleOrigins?.[key];
    return enabled === undefined || enabled === true || enabled === 1 || enabled === '1';
}));

const {
    search, position, origin, sort, maxPrice,
    filtered: filteredPlayers, hasActiveFilters, positionOptions, originOptions,
} = useRecruitFilters(() => props.availableFreePlayers, { cost: costOf, overall: ovr, origins: visibleOriginOptions });

// ==========================
//   JOUEUR SÉLECTIONNÉ
// ==========================
const selectedPlayer = ref(null);
// Après une signature réussie, le joueur sort de la liste des agents libres :
// on referme son panneau (sinon il reste ouvert et invite à recliquer).
watch(() => props.availableFreePlayers, (list) => {
    if (selectedPlayer.value && !list.some(p => p.id === selectedPlayer.value.id)) {
        selectedPlayer.value = null;
    }
});
const selectPlayer = (p) => {
    selectedPlayer.value = p;
    // Le salaire fait autorité côté serveur (coût ajusté à la polyvalence) :
    // on l'affiche sans le rendre éditable, le serveur l'imposera de toute façon.
    emit('update:transferSalary', p.adjusted_cost ?? p.cost ?? 0);
    emit('update:transferMatches', Math.min(10, props.transferMaxWeeks));
};

watch(() => props.focusPlayer?.id, (id) => {
    const p = id != null ? props.availableFreePlayers.find(fp => fp.id === id) : null;
    if (p) selectPlayer(p);
}, { immediate: true });

// Meilleur joueur de mon effectif au même poste (pour comparaison)
// Titulaire que la recrue remplacerait (le plus faible à son poste) : même
// comparaison que le conseil de l'adjoint, mais pour chaque offre.
const replacedStarter = computed(() => {
    if (!selectedPlayer.value) return null;
    const group = positionGroup(selectedPlayer.value.position);
    const starters = props.roster.filter(p => p.is_starter && positionGroup(p.position) === group);
    if (!starters.length) return null;
    return starters.reduce((weak, p) => overallOf(p) < overallOf(weak) ? p : weak, starters[0]);
});
const overallGain = computed(() => replacedStarter.value ? overallOf(selectedPlayer.value) - overallOf(replacedStarter.value) : null);

const bestTeamPlayerSamePos = computed(() => {
    if (!selectedPlayer.value || !props.roster.length) return null;
    const group = positionGroup(selectedPlayer.value.position);
    const samePos = props.roster.filter(p => positionGroup(p.position) === group);
    if (!samePos.length) return null;
    return samePos.reduce((best, p) => overallOf(p) > overallOf(best) ? p : best, samePos[0]);
});

// ==========================
//   COMPARATIF DE 2 JOUEURS (comme dans le draft)
// ==========================
const compareList = ref([]);

// ==========================
//   BUDGET
// ==========================
const budgetAfter = computed(() => props.teamBudget - props.transferTotalCost);
const canAfford   = computed(() => budgetAfter.value >= 0);

const historyTeamFilter = ref(null);

const historyTeams = computed(() => {
    const seen = new Set();
    return props.transferHistory
        .filter(e => {
            if (seen.has(e.team.id)) return false;
            seen.add(e.team.id);
            return true;
        })
        .map(e => e.team);
});

const filteredHistory = computed(() =>
    historyTeamFilter.value !== null
        ? props.transferHistory.filter(e => Number(e.team.id) === Number(historyTeamFilter.value))
        : props.transferHistory
);
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <RecruitFilterBar v-model:search="search" v-model:position="position" v-model:origin="origin"
                          v-model:sort="sort" v-model:max-price="maxPrice"
                          :positions="positionOptions" :origins="originOptions"
                          price-label="Salaire max. / sem. (GL)" />

        <PlayerComparison v-model="compareList" :cost="costOf" cost-label="Salaire / sem." />

        <!-- Contenu principal -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

            <!-- ============================================ -->
            <!-- LISTE JOUEURS LIBRES                         -->
            <!-- ============================================ -->
            <!-- Hauteur calée sur la colonne de droite (grand écran), scroll interne -->
            <div class="lg:col-span-5 relative lg:min-h-[520px]">
            <Panel padding="sm" class="max-h-[690px] lg:max-h-none lg:absolute lg:inset-0 overflow-y-auto"
                   :title="`Agents libres · ${filteredPlayers.length}`">

                <div v-if="filteredPlayers.length" class="space-y-1">
                    <RecruitRow v-for="p in filteredPlayers" :key="p.id" :player="p"
                                :selected="selectedPlayer?.id === p.id" :overall="ovr(p)"
                                :price="costOf(p)" price-sub="/ sem." @select="selectPlayer">
                        <template #action>
                            <PlayerCompareButton v-model="compareList" :player="p" />
                        </template>
                    </RecruitRow>
                </div>

                <div v-else class="flex items-center justify-center py-8 text-slate-400 text-xs italic">
                    {{ hasActiveFilters
                        ? 'Aucun agent libre ne correspond aux filtres'
                        : 'Aucun agent libre disponible' }}
                </div>
            </Panel>
            </div>

            <!-- ============================================ -->
            <!-- PROFIL + OFFRE                               -->
            <!-- ============================================ -->
            <div v-if="selectedPlayer" class="lg:col-span-7 flex flex-col gap-3">

                <PlayerIdentityCard :player="selectedPlayer" :show-captain-badge="false"
                                    :subtitle="positionLabel(selectedPlayer.position)">
                    <template #footer>
                        <span class="mt-1.5 inline-flex rounded-full px-2 py-0.5 text-2xs font-semibold"
                              :class="moraleState(selectedPlayer.morale).chip">
                            {{ moraleState(selectedPlayer.morale).emoji }}
                            Moral · {{ moraleState(selectedPlayer.morale).label }} · {{ selectedPlayer.morale ?? 60 }}/100
                        </span>
                        <p v-if="selectedPlayer.description" class="mt-2 text-xs italic text-slate-500">{{ selectedPlayer.description }}</p>
                    </template>
                </PlayerIdentityCard>

                <!-- Formulaire offre -->
                <Panel title="💼 Proposer un contrat">
                    <template #header-actions>
                        <span class="text-xs normal-case text-slate-500">
                            Budget actuel <strong class="text-teal-700">{{ teamBudget }} <GameBanknote /></strong>
                        </span>
                    </template>

                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Durée (semaines, max. {{ transferMaxWeeks }})</label>
                            <input type="number" min="1" :max="transferMaxWeeks" :disabled="!transferMaxWeeks"
                                   :value="transferMatches"
                                   @input="emit('update:transferMatches', +$event.target.value)"
                                   class="w-full px-3 py-1.5" :class="fieldClass({ shape: 'rect' })"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Salaire / semaine (<GameBanknote />)</label>
                            <div class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-slate-100 text-slate-600 font-semibold flex items-center justify-between">
                                <span>{{ transferSalary }} <GameBanknote /></span>
                                <span class="text-2xs text-slate-400 font-normal">fixé</span>
                            </div>
                        </div>
                    </div>

                    <!-- Comparaison avec le titulaire remplacé -->
                    <div class="mb-3 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-600">
                        <template v-if="replacedStarter">
                            🔁 Remplacerait <span class="font-bold">{{ replacedStarter.lastname }}</span>, ton titulaire le plus faible à ce poste :
                            note {{ overallOf(replacedStarter) }} → {{ overallOf(selectedPlayer) }}
                            <span class="font-bold" :class="overallGain > 0 ? 'text-emerald-600' : overallGain < 0 ? 'text-rose-500' : 'text-slate-500'">({{ overallGain > 0 ? '+' : '' }}{{ overallGain }})</span>
                            · {{ transferSalary }} <GameBanknote />/semaine × {{ transferMatches }} = {{ transferTotalCost }} <GameBanknote />
                        </template>
                        <template v-else>Aucun titulaire à ce poste : il renforcerait directement ton onze.</template>
                    </div>

                    <!-- Résumé coût -->
                    <div class="flex items-center gap-3 p-3 rounded-lg mb-3"
                         :class="canAfford ? 'bg-emerald-50 border border-emerald-200' : 'bg-rose-50 border border-rose-200'">
                        <div class="flex-1">
                            <div class="text-xs text-slate-500">Coût total</div>
                            <div class="text-lg font-black" :class="canAfford ? 'text-emerald-600' : 'text-rose-500'">
                                {{ transferTotalCost }} <GameBanknote />
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-500">Budget restant</div>
                            <div class="text-sm font-bold" :class="canAfford ? 'text-emerald-600' : 'text-rose-500'">
                                {{ budgetAfter }} <GameBanknote />
                            </div>
                        </div>
                        <div v-if="!canAfford" class="text-rose-500 text-sm">⚠️ Budget insuffisant</div>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <ButtonSecondary size="sm" @click="selectedPlayer = null">
                            Annuler
                        </ButtonSecondary>
                        <ButtonPrimary type="button" size="sm" :disabled="!canAfford || !team || !Number.isInteger(transferMatches) || transferMatches < 1 || transferMatches > transferMaxWeeks || transferProcessing" @click="emit('confirm-transfer', selectedPlayer)">
                            Confirmer l'offre
                        </ButtonPrimary>
                    </div>
                </Panel>

                <StatBars :player="selectedPlayer" highlight-position
                          :compare-player="bestTeamPlayerSamePos" :compare-label="bestTeamPlayerSamePos?.lastname" />

                <PlayerSpecialMoves :moves="selectedPlayer.special_moves ?? []" names-only show-empty />
            </div>

            <!-- Placeholder si aucun joueur sélectionné -->
            <div v-else class="lg:col-span-7 flex items-center justify-center rounded-xl border border-dashed border-slate-300 p-10 text-slate-400 text-sm">
                <div class="text-center">
                    <div class="text-3xl mb-2">👤</div>
                    <p>Sélectionne un joueur pour voir son profil et faire une offre</p>
                </div>
            </div>
        </div>
        <!-- ============================================ -->
        <!-- HISTORIQUE TRANSFERTS                         -->
        <!-- ============================================ -->
        <Panel class="mt-4" title="📋 Historique des transferts">
            <template #header-actions>
                <select :value="historyTeamFilter ?? 'null'"
                        @change="historyTeamFilter = $event.target.value === 'null' ? null : $event.target.value"
                        class="min-w-[200px] pl-3 pr-9 py-1 cursor-pointer" :class="fieldClass({ shape: 'rect', size: 'sm' })">
                    <option value="null">Toutes les équipes</option>
                    <option v-for="t in historyTeams" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </template>

            <div v-if="filteredHistory.length" class="space-y-1.5 max-h-[300px] overflow-y-auto pr-1">
                <div v-for="entry in filteredHistory" :key="entry.player.id + '-' + entry.start_week"
                     class="flex items-center gap-3 px-3 py-2 rounded-lg bg-white border border-slate-300">
                    <!-- Photo -->
                    <PlayerAvatar :player="entry.player" />
                    <!-- Nom -->
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-slate-700 truncate">
                            {{ entry.player.firstname }} {{ entry.player.lastname }}
                        </div>
                        <div class="text-2xs text-slate-400">{{ positionLabel(entry.player.position) }}</div>
                    </div>
                    <!-- Équipe -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-xs text-slate-600 font-semibold">→ {{ entry.team.name }}</span>
                    </div>
                    <!-- Semaine -->
                    <div class="text-2xs text-slate-400 shrink-0">S{{ entry.start_week }}</div>
                    <!-- Salaire -->
                    <div class="text-xs font-bold text-teal-600 shrink-0">{{ entry.salary }} <GameBanknote /></div>
                </div>
            </div>

            <div v-else class="text-xs text-slate-400 italic py-3 text-center">
                Aucun transfert enregistré pour le moment.
            </div>
        </Panel>

    </div>
</template>
