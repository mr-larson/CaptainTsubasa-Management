<script setup>
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { averageOverall, usePlayerUtils } from '../GameSaves/Play/usePlayerUtils.js';
import PreMatchRoster from './PreMatchRoster.vue';

const props = defineProps({ show: Boolean, config: { type: Object, required: true } });
defineEmits(['start']);
const activeTeam = ref(0);
const benchPages = ref([0, 0]);
const benchPageSize = 6;
const { teamLogoUrl } = usePlayerUtils();
const teams = computed(() => {
    const sides = props.config.internalIsHome === false ? ['external', 'internal'] : ['internal', 'external'];
    return sides.map((side, index) => {
        const team = props.config.teams[side];
        const starters = team.players.filter((p) => p.is_starter);
        return {
            ...team,
            venue: index === 0 ? 'Domicile' : 'Extérieur',
            starters,
            substitutes: team.players.filter((p) => !p.is_starter),
            overall: averageOverall(starters.filter((p) => p.id && !p.is_placeholder)),
        };
    });
});
</script>

<template>
    <Modal
        :show="show"
        :closeable="false"
        max-width="none"
        panel-class="max-w-[1240px]"
        backdrop-class="bg-slate-900/20 backdrop-blur-[1px]"
        title="Avant-match"
        :subtitle="`Semaine ${config.week} · Présentation des équipes`"
    >
        <div class="flex gap-2 xl:hidden" role="tablist" aria-label="Équipes">
            <button
                v-for="(team, index) in teams"
                :id="`prematch-tab-${index}`"
                :key="team.id"
                type="button"
                role="tab"
                :aria-selected="activeTeam === index"
                :aria-controls="`prematch-team-${index}`"
                class="flex-1 rounded-lg px-3 py-2 text-sm font-bold"
                :class="activeTeam === index ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600'"
                @click="activeTeam = index"
            >
                {{ team.venue }} · {{ team.name }}
            </button>
        </div>
        <div class="grid gap-3 xl:grid-cols-2">
            <section
                v-for="(team, index) in teams"
                :id="`prematch-team-${index}`"
                :key="team.id"
                :aria-label="team.venue"
                class="min-w-0 overflow-hidden rounded-xl border border-slate-300"
                :class="activeTeam === index ? '' : 'hidden xl:block'"
            >
                <h4
                    class="border-b border-slate-300 bg-slate-50 px-3 py-2 text-xs font-bold uppercase tracking-wider text-teal-700"
                >
                    {{ team.venue }}
                </h4>
                <div class="grid grid-cols-2">
                    <div class="min-w-0 border-r border-slate-300">
                        <div class="bg-gradient-to-br from-slate-50 to-teal-50 p-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm"
                                >
                                    <TeamLogoImage
                                        v-if="teamLogoUrl(team)"
                                        :src="teamLogoUrl(team)"
                                        :alt="`Logo ${team.name}`"
                                        class="h-full w-full"
                                    />
                                    <span v-else class="text-2xl" aria-hidden="true">⚽</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">
                                        {{ team.formation }}
                                    </p>
                                    <h5 class="mt-0.5 text-base font-extrabold text-slate-800">{{ team.name }}</h5>
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{
                                            team.rank
                                                ? `${team.rank}${team.rank === 1 ? 'er' : 'e'} au classement`
                                                : 'Classement indisponible'
                                        }}
                                        · Note du onze : <strong>{{ team.overall ?? '—' }}</strong>
                                    </p>
                                </div>
                            </div>
                            <p class="mt-2 line-clamp-2 text-xs leading-4 text-slate-600" :title="team.description">
                                {{ team.description || 'Aucune description disponible.' }}
                            </p>
                        </div>
                        <div class="p-3">
                            <h5 class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Remplaçants <span class="font-normal">({{ team.substitutes.length }})</span>
                            </h5>
                            <PreMatchRoster
                                :players="
                                    team.substitutes.slice(
                                        benchPages[index] * benchPageSize,
                                        (benchPages[index] + 1) * benchPageSize,
                                    )
                                "
                            />
                            <div
                                v-if="team.substitutes.length > benchPageSize"
                                class="mt-2 flex items-center justify-between text-[10px] text-slate-500"
                            >
                                <button
                                    type="button"
                                    :disabled="benchPages[index] === 0"
                                    class="rounded px-2 py-1 hover:bg-slate-100 disabled:opacity-30"
                                    aria-label="Remplaçants précédents"
                                    @click="benchPages[index]--"
                                >
                                    ←
                                </button>
                                <span
                                    >{{ benchPages[index] + 1 }} /
                                    {{ Math.ceil(team.substitutes.length / benchPageSize) }}</span
                                >
                                <button
                                    type="button"
                                    :disabled="(benchPages[index] + 1) * benchPageSize >= team.substitutes.length"
                                    class="rounded px-2 py-1 hover:bg-slate-100 disabled:opacity-30"
                                    aria-label="Remplaçants suivants"
                                    @click="benchPages[index]++"
                                >
                                    →
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="min-w-0 p-3">
                        <h5 class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Onze titulaires <span class="font-normal">({{ team.starters.length }})</span>
                        </h5>
                        <PreMatchRoster :players="team.starters" />
                    </div>
                </div>
            </section>
        </div>
        <template #footer><ButtonPrimary @click="$emit('start')">Passer au coup d’envoi →</ButtonPrimary></template>
    </Modal>
</template>
