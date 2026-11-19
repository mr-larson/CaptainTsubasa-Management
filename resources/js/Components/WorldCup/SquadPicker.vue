<script setup>
// La convocation utilise les mêmes lignes et fiches que Draft / Transferts.
// Le parent conserve les ids ; le serveur reste responsable du onze projeté.
import NationFlag from '@/Components/NationFlag.vue';
import Panel from '@/Components/Panel.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import PlayerAvatar from '@/Components/PlayerAvatar.vue';
import PositionBadge from '@/Components/PositionBadge.vue';
import PositionCounts from '@/Components/PositionCounts.vue';
import Modal from '@/Components/Modal.vue';
import { fieldClass } from '@/Components/fieldStyles';
import PitchView from '@/Pages/GameSaves/Play/components/PitchView.vue';
import RecruitRow from '@/Pages/GameSaves/Play/components/RecruitRow.vue';
import PlayerIdentityCard from '@/Pages/GameSaves/Play/components/PlayerIdentityCard.vue';
import PlayerCompareButton from '@/Pages/GameSaves/Play/components/PlayerCompareButton.vue';
import PlayerComparison from '@/Pages/GameSaves/Play/components/PlayerComparison.vue';
import PlayerSpecialMoves from '@/Pages/GameSaves/Play/components/PlayerSpecialMoves.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import { positionGroup, POSITION_GROUPS, positionLabel } from '@/Enums/positionBadges.js';
import { buildLineupForm, moveToSlot, slotToPlayerMap, pitchPosition } from '@/Pages/GameSaves/Play/lineupSlots.js';
import { overallOf, usePlayerUtils } from '@/Pages/GameSaves/Play/usePlayerUtils.js';
import { positionRating } from '@/Pages/GameSaves/Play/recruitment.js';
import { masteryFor } from '@/Pages/GameSaves/Play/lineupMastery.js';
import { reconcileSelectionLineup } from './selectionLineup.js';
import { FORMATIONS, DEFAULT_FORMATION } from '@/Pages/Match/engine/formations.js';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    squad: { type: Object, required: true },
    modelValue: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    formation: { type: String, default: null },
    lineupSlots: { type: Object, default: null },
});
const emit = defineEmits(['update:modelValue', 'update:formation', 'update:lineupSlots']);
const { moraleState } = usePlayerUtils();
function changeFormation(event) {
    emit('update:formation', event.target.value);
}
const field = fieldClass({ size: 'sm' });
const selected = computed(() => new Set(props.modelValue));
const isFull = computed(() => selected.value.size >= props.squad.maxPlayers);
const players = computed(() => props.squad.players.map(p => ({ ...p, overall: overallOf(p), group: positionGroup(p.position) })));
const selectedPlayers = computed(() => players.value.filter(p => selected.value.has(p.id)));
const valid = computed(() => isValidSquad(props.squad, props.modelValue));
const hasKeeper = computed(() => selectedPlayers.value.some(p => p.group === 'GK'));

function toggle(id) {
    if (props.disabled) return;
    if (selected.value.has(id)) emit('update:modelValue', props.modelValue.filter(x => x !== id));
    else if (!isFull.value) emit('update:modelValue', [...props.modelValue, id]);
}

const search = ref('');
const position = ref('ALL');
const sort = ref('overall');
const showOnlySelected = ref(false);
const positionOptions = computed(() => [
    { key: 'ALL', label: 'Tous', count: players.value.length },
    ...POSITION_GROUPS.map(g => ({ key: g.key, label: g.plural, count: players.value.filter(p => p.group === g.key).length })),
]);
const visiblePlayers = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('fr');
    const order = POSITION_GROUPS.map(g => g.key);
    return players.value.filter(p => position.value === 'ALL' || p.group === position.value)
        .filter(p => !query || p.name.toLocaleLowerCase('fr').includes(query))
        .filter(p => !showOnlySelected.value || selected.value.has(p.id))
        .sort((a, b) => {
            const byName = () => a.name.localeCompare(b.name, 'fr');
            if (sort.value === 'name') return byName();
            if (sort.value === 'position') return order.indexOf(a.group) - order.indexOf(b.group) || b.overall - a.overall || byName();
            if (sort.value === 'profile') return positionRating(b) - positionRating(a) || byName();
            return b.overall - a.overall || byName();
        });
});

// Consulter une fiche ne change jamais la convocation.
const inspectedId = ref(props.modelValue[0] ?? props.squad.players[0]?.id);
const inspectedPlayer = computed(() => players.value.find(p => p.id === inspectedId.value) ?? null);
const detail = ref(null);
const comparison = ref([]);
const showPitch = ref(false);
const showOverview = ref(false);
function inspect(player) {
    inspectedId.value = player.id;
    if (window.matchMedia('(max-width: 1279px)').matches) {
        nextTick(() => detail.value?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
    }
}

const lineup = ref(null);
const loadingLineup = ref(false);
const lineupError = ref(false);
let timer;
let requestId = 0;
let controller;
function refreshLineup() {
    clearTimeout(timer);
    controller?.abort();
    const current = ++requestId;
    lineup.value = null;
    lineupError.value = false;
    loadingLineup.value = false;
    if (!valid.value) return;
    const ids = [...props.modelValue];
    loadingLineup.value = true;
    timer = setTimeout(async () => {
        controller = new AbortController();
        try {
            const { data } = await window.axios.post(route('game-saves.world-cup-lineup-preview'), {
                nation: props.squad.name, player_ids: ids, formation: props.formation,
            }, { signal: controller.signal });
            if (current === requestId) {
                lineup.value = data.lineup;
                if (props.lineupSlots && data.lineup) emit('update:lineupSlots', reconcileSelectionLineup(props.lineupSlots, data.lineup.slots, props.modelValue));
            }
        } catch {
            if (current === requestId) lineupError.value = true;
        } finally {
            if (current === requestId) loadingLineup.value = false;
        }
    }, 250);
}
watch(() => [props.modelValue, props.formation], refreshLineup, { immediate: true, deep: true });
onBeforeUnmount(() => { clearTimeout(timer); ++requestId; controller?.abort(); });
const formationKey = computed(() => lineup.value?.formation ?? props.formation ?? props.squad.formation ?? DEFAULT_FORMATION);
const formationData = computed(() => FORMATIONS[formationKey.value] ?? FORMATIONS[DEFAULT_FORMATION]);
const playerById = computed(() => new Map(players.value.map(p => [p.id, p])));
const effectiveSlots = computed(() => props.lineupSlots ?? lineup.value?.slots ?? {});
const starterIds = computed(() => new Set(Object.values(effectiveSlots.value).map(Number)));
const playerForSlot = slot => {
    const id = Number(effectiveSlots.value[slot]);
    return selected.value.has(id) ? playerById.value.get(id) ?? null : null;
};
const pitchPlayers = computed(() => selectedPlayers.value.map(p => ({ ...p, is_starter: starterIds.value.has(p.id) })));
const pickedId = ref(null);
const pickedPlayer = computed(() => playerById.value.get(pickedId.value));
const selectedSlot = computed(() => Number(Object.keys(effectiveSlots.value).find(slot => effectiveSlots.value[slot] === pickedId.value)) || null);
function pickPlayer(player) {
    if (props.disabled || loadingLineup.value || !selected.value.has(player.id)) return;
    inspectedId.value = player.id;
    if (pickedId.value === player.id) { pickedId.value = null; return; }
    if (pickedId.value != null) {
        const slot = Object.keys(effectiveSlots.value).find(slot => effectiveSlots.value[slot] === player.id);
        if (slot) { placeAt(slot); return; }
    }
    pickedId.value = player.id;
}
function placeAt(slot, player = pickedPlayer.value) {
    if (!player || props.disabled || loadingLineup.value || !selected.value.has(player.id)) return;
    const form = buildLineupForm([...starterIds.value], effectiveSlots.value);
    if (moveToSlot(form, player.id, slot)) emit('update:lineupSlots', slotToPlayerMap(form));
    pickedId.value = null;
}
function dragStart(player, event) {
    if (props.disabled || loadingLineup.value) { event.preventDefault(); return; }
    pickedId.value = player.id;
    inspectedId.value = player.id;
    if (event.dataTransfer) { event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', String(player.id)); }
}
function dropOn(player, event) {
    event?.preventDefault();
    const slot = Object.keys(effectiveSlots.value).find(slot => effectiveSlots.value[slot] === player.id);
    if (slot) placeAt(slot);
}
function cancelPick(event) { if (event.key === 'Escape') pickedId.value = null; }
watch(pickedId, id => {
    window.removeEventListener('keydown', cancelPick);
    if (id != null) window.addEventListener('keydown', cancelPick);
});
onBeforeUnmount(() => window.removeEventListener('keydown', cancelPick));
watch(() => [props.modelValue, props.formation], () => { pickedId.value = null; }, { deep: true });
const slotCandidates = (_slot, def) => benchPlayers.value.map(player => ({ player, mastery: masteryFor(player, def), unavailable: false }));
const benchPlayers = computed(() => selectedPlayers.value.filter(p => !starterIds.value.has(p.id)).sort((a, b) => b.overall - a.overall));
const lineupHint = computed(() => {
    if (!valid.value) return `Convoque au moins ${props.squad.minPlayers} joueurs, dont un gardien, pour voir le onze.`;
    if (loadingLineup.value) return 'Composition du onze en cours…';
    if (lineupError.value) return 'L’aperçu est indisponible. Tu peux réessayer ou poursuivre la convocation.';
    if (!lineup.value) return 'Le onze sera composé à la création de la partie.';
    return pickedPlayer.value ? `${pickedPlayer.value.lastname} : choisis un poste ou un joueur à remplacer. Échap pour annuler.` : 'Clique un joueur puis sa cible, ou glisse-le vers un autre poste. Tu peux aussi choisir un remplaçant.';
});
</script>

<script>
/** Une convocation valide comprend 11 à 20 joueurs dont au moins un gardien. */
export function isValidSquad(squad, ids) {
    const set = new Set(ids);
    return !!squad && set.size >= squad.minPlayers && set.size <= squad.maxPlayers
        && [...set].every(id => squad.players.some(p => p.id === id))
        && squad.players.some(p => p.position === 'Goalkeeper' && set.has(p.id));
}
</script>

<template>
    <div class="grid min-w-0 gap-4 lg:grid-cols-12">
        <aside class="min-w-0 space-y-4 lg:col-span-4">
            <Panel tone="white">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-2xl">
                        <NationFlag :flag="squad.flag" :url="squad.flag_url" :name="squad.name" />
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-bold text-teal-700">{{ squad.name }}</h1>
                        <p class="text-xs text-slate-500">{{ selected.size }} / {{ squad.maxPlayers }} joueurs convoqués</p>
                    </div>
                </div>
                <PositionCounts :players="selectedPlayers" size="sm" class="mt-3" />
                <p class="mt-3 text-xs text-slate-500">De {{ squad.minPlayers }} à {{ squad.maxPlayers }} joueurs, dont au moins un gardien.</p>
                <p v-if="!hasKeeper" class="mt-2 text-xs text-rose-600" role="status">Ajoute un gardien à ta sélection.</p>
                <p v-else-if="isFull" class="mt-2 text-xs text-slate-500" role="status">Effectif complet. Retire un joueur pour en convoquer un autre.</p>
            </Panel>

            <ButtonSecondary class="lg:hidden" :aria-expanded="showOverview" @click="showOverview = !showOverview">
                {{ showOverview ? 'Masquer le onze et le banc' : 'Voir le onze et le banc' }}
            </ButtonSecondary>
            <div :class="showOverview ? 'space-y-4' : 'hidden space-y-4 lg:block'">
            <Panel title="Onze de départ" padding="sm">
                <template #header-actions>
                    <select :value="formationKey" :class="field" :disabled="disabled"
                            class="max-w-[140px] py-1 pl-2 pr-7" aria-label="Formation de départ" @change="changeFormation">
                        <option v-for="(shape, key) in FORMATIONS" :key="key" :value="key">{{ shape.label }}</option>
                    </select>
                </template>
                <PitchView whiteboard :formation-data="formationData" :players="pitchPlayers" :selected-slot="selectedSlot" :is-picked-up="p => p.id === pickedId"
                           :player-position="slot => pitchPosition(formationData, slot)" :player-for-slot="playerForSlot"
                           :slot-mastery="(slot, def) => masteryFor(playerForSlot(slot), def)" :mastery-for="masteryFor" :slot-candidates="slotCandidates"
                           @player-click="pickPlayer" @drag-start="dragStart" @drag-over="event => event.preventDefault()"
                           @drop-on="dropOn" @drag-end="pickedId = null" @fill-slot="placeAt"
                           @drop-on-slot="(slot, event) => { event.preventDefault(); placeAt(slot); }" @cancel-pickup="pickedId = null" />
                <p class="mt-3 text-2xs text-slate-500" aria-live="polite">{{ lineupHint }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <ButtonSecondary size="sm" @click="showPitch = true">Agrandir le terrain</ButtonSecondary>
                    <ButtonSecondary v-if="lineupError" size="sm" @click="refreshLineup">Réessayer</ButtonSecondary>
                </div>
            </Panel>

            <Panel v-if="lineup && benchPlayers.length" title="Remplaçants" padding="sm">
                <ul class="max-h-64 space-y-1 overflow-y-auto">
                    <li v-for="player in benchPlayers" :key="player.id" class="flex items-center gap-1">
                        <button type="button" class="flex min-w-0 flex-1 items-center gap-2 rounded-lg p-1.5 text-left hover:bg-white"
                                :class="pickedId === player.id ? 'bg-amber-50 text-amber-700' : inspectedId === player.id ? 'bg-teal-50 text-teal-700' : 'text-slate-700'" :disabled="disabled || loadingLineup" :draggable="!disabled && !loadingLineup" @dragstart="dragStart(player, $event)" @dragend="pickedId = null" @click="pickPlayer(player)">
                            <PlayerAvatar :player="player" size="xs" :zoomable="false" />
                            <span class="min-w-0 flex-1 truncate text-xs">{{ player.name }}</span>
                            <PositionBadge :position="player.position" />
                        </button>
                        <button type="button" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-base text-slate-400 hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="disabled || loadingLineup" :aria-label="`Retirer ${player.name} de la convocation`"
                                title="Retirer de la convocation" @click.stop="toggle(player.id)">
                            <span aria-hidden="true">×</span>
                        </button>
                    </li>
                </ul>
            </Panel>
            </div>
        </aside>

        <section class="min-w-0 lg:col-span-8">
            <Panel tone="white" padding="sm" class="mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <span class="font-semibold text-slate-700">Compose ta sélection <span class="ml-2 font-normal text-slate-500">{{ players.length }} joueurs disponibles</span></span>
                    <span class="text-teal-700">Consulte une fiche, compare puis convoque tes joueurs.</span>
                </div>
            </Panel>
            <div class="mb-4 flex flex-wrap items-end gap-2">
                <label class="min-w-[160px] flex-1 text-xs text-slate-500">Rechercher un joueur
                    <input v-model="search" type="search" placeholder="Nom ou prénom" class="mt-1 block w-full px-3 py-2" :class="field">
                </label>
                <label class="text-xs text-slate-500">Poste
                    <select v-model="position" class="mt-1 block w-full py-2 pl-3 pr-8" :class="field">
                        <option v-for="o in positionOptions" :key="o.key" :value="o.key">{{ o.label }} ({{ o.count }})</option>
                    </select>
                </label>
                <label class="text-xs text-slate-500">Trier par
                    <select v-model="sort" class="mt-1 block w-full py-2 pl-3 pr-8" :class="field">
                        <option value="overall">Note globale</option><option value="profile">Meilleur profil au poste</option>
                        <option value="name">Nom (A → Z)</option><option value="position">Poste</option>
                    </select>
                </label>
            </div>
            <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,7fr)_minmax(0,9fr)]">
                <Panel tone="white" padding="sm" class="min-w-0 self-start">
                    <template #title>Joueurs disponibles · {{ visiblePlayers.length }}</template>
                    <template #header-actions>
                        <label class="flex items-center gap-1.5 text-slate-500">
                            <input v-model="showOnlySelected" type="checkbox" class="rounded border-slate-300 text-teal-500 focus:ring-teal-400"> Convoqués
                        </label>
                    </template>
                    <div class="max-h-[70vh] space-y-1.5 overflow-y-auto pr-1">
                        <RecruitRow v-for="player in visiblePlayers" :key="player.id" :player="player" :overall="player.overall"
                                    :selected="inspectedId === player.id" @select="inspect">
                            <template #name-suffix>
                                <span v-if="selected.has(player.id)" class="ml-1 text-2xs font-semibold text-teal-700">✓ <span class="sr-only">Convoqué</span></span>
                            </template>
                            <template #action>
                                <PlayerCompareButton v-model="comparison" :player="player" />
                                <button type="button" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-sm disabled:opacity-40"
                                        :class="selected.has(player.id) ? 'border-teal-300 bg-teal-50 text-teal-700' : 'border-slate-300 bg-white text-slate-500 hover:border-teal-400'"
                                        :aria-pressed="selected.has(player.id)" :aria-label="`${selected.has(player.id) ? 'Retirer' : 'Convoquer'} ${player.name}`"
                                        :title="selected.has(player.id) ? 'Retirer de la sélection' : 'Convoquer'"
                                        :disabled="disabled || (!selected.has(player.id) && isFull)" @click="toggle(player.id)">{{ selected.has(player.id) ? '✓' : '+' }}</button>
                            </template>
                        </RecruitRow>
                        <p v-if="!visiblePlayers.length" class="p-4 text-center text-xs text-slate-500">Aucun joueur ne correspond aux filtres.</p>
                    </div>
                </Panel>
                <div ref="detail" class="min-w-0 space-y-3">
                    <template v-if="inspectedPlayer">
                        <PlayerIdentityCard :player="inspectedPlayer" :show-captain-badge="false"
                                            :subtitle="`${positionLabel(inspectedPlayer.position)} · Note au poste ${positionRating(inspectedPlayer)}`">
                            <template #footer>
                                <span class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-1 text-2xs font-semibold" :class="moraleState(inspectedPlayer.morale).chip">
                                    {{ moraleState(inspectedPlayer.morale).emoji }} Moral · {{ moraleState(inspectedPlayer.morale).label }} · {{ inspectedPlayer.morale }}/100
                                </span>
                            </template>
                        </PlayerIdentityCard>
                        <Panel tone="white" padding="sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="text-xs" :class="selected.has(inspectedPlayer.id) ? 'font-semibold text-teal-700' : 'text-slate-500'">
                                    {{ selected.has(inspectedPlayer.id) ? (starterIds.has(inspectedPlayer.id) ? '✓ Convoqué · Onze projeté' : '✓ Convoqué') : 'Non convoqué' }}
                                </span>
                                <ButtonSecondary v-if="selected.has(inspectedPlayer.id)" size="sm" :disabled="disabled" @click="toggle(inspectedPlayer.id)">Retirer de la sélection</ButtonSecondary>
                                <ButtonPrimary v-else type="button" size="sm" :disabled="disabled || isFull" @click="toggle(inspectedPlayer.id)">{{ isFull ? 'Effectif complet' : 'Convoquer' }}</ButtonPrimary>
                            </div>
                        </Panel>
                        <StatBars :player="inspectedPlayer" highlight-position />
                        <PlayerSpecialMoves :moves="inspectedPlayer.special_moves ?? []" names-only show-empty />
                    </template>
                    <PlayerComparison v-model="comparison" />
                </div>
            </div>
        </section>
        <Modal :show="showPitch" title="Onze de départ projeté" max-width="3xl" @close="showPitch = false">
            <div class="mb-3 flex items-center justify-between text-sm text-slate-700"><strong>{{ squad.name }}</strong><span>{{ formationKey }}</span></div>
            <PitchView whiteboard :formation-data="formationData" :players="pitchPlayers" :selected-slot="selectedSlot" :is-picked-up="p => p.id === pickedId"
                       :player-position="slot => pitchPosition(formationData, slot)" :player-for-slot="playerForSlot"
                       :slot-mastery="(slot, def) => masteryFor(playerForSlot(slot), def)" :mastery-for="masteryFor" :slot-candidates="slotCandidates"
                       @player-click="pickPlayer" @drag-start="dragStart" @drag-over="event => event.preventDefault()"
                       @drop-on="dropOn" @drag-end="pickedId = null" @fill-slot="placeAt"
                       @drop-on-slot="(slot, event) => { event.preventDefault(); placeAt(slot); }" @cancel-pickup="pickedId = null" />
            <p class="mt-3 text-xs text-slate-500">{{ lineupHint }}</p>
        </Modal>
    </div>
</template>
