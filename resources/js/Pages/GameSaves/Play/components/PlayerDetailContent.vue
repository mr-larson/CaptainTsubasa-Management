<script setup>
import { computed } from 'vue';
import PlayerIdentityCard from './PlayerIdentityCard.vue';
import PlayerSpecialMoves from './PlayerSpecialMoves.vue';
import StatBars from './StatBars.vue';
import { usePlayerUtils } from '../usePlayerUtils.js';

const props = defineProps({ player: { type: Object, required: true } });
const { moraleState } = usePlayerUtils();
const morale = computed(() => moraleState(props.player.morale));
</script>

<template>
    <div class="flex flex-col gap-3">
        <PlayerIdentityCard :player="player">
            <template #footer>
                <p v-if="player.morale != null" class="mt-1 text-xs" :class="morale.text">
                    <span aria-hidden="true">{{ morale.emoji }}</span>
                    Moral · {{ morale.label }} · {{ player.morale }}/100
                </p>
                <p v-if="player.description" class="mt-2 text-xs italic text-slate-500">{{ player.description }}</p>
            </template>
        </PlayerIdentityCard>
        <StatBars :player="player" highlight-position />
        <PlayerSpecialMoves :moves="player.special_moves ?? []" show-empty />
    </div>
</template>
