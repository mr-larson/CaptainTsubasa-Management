<script setup>
import ClubIdentityCard from './ClubIdentityCard.vue';
import ClubSummaryCard from './ClubSummaryCard.vue';
import TeamRecordCard from './TeamRecordCard.vue';

/** Bandeau club : identité + bilan + résumé de l'effectif (Mon équipe, Autres équipes). */
defineProps({
    team:           { type: Object, default: null },
    rank:           { type: Number, default: null },
    total:          { type: Number, default: null },
    averageOverall: { type: Number, default: null },
    budget:         { type: Number, default: 0 },
    record:         { type: Object, default: () => ({ wins: 0, draws: 0, losses: 0 }) },
    players:        { type: Array,  default: () => [] },
    injuries:       { type: Number, default: 0 },
    suspensions:    { type: Number, default: 0 },
    cards:          { type: Number, default: 0 },
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <ClubIdentityCard class="md:col-span-5" :team="team" :rank="rank" :total="total"
                          :average-overall="averageOverall" :budget="budget" />
        <TeamRecordCard class="md:col-span-3" :wins="record.wins ?? 0" :draws="record.draws ?? 0" :losses="record.losses ?? 0" />
        <ClubSummaryCard class="md:col-span-4" :players="players" :injuries="injuries" :suspensions="suspensions" :cards="cards" />
    </div>
</template>
