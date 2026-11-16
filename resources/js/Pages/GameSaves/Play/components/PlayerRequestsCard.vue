<script setup>
// Demandes des joueurs en attente (congé, temps de jeu, souci physique) : l'entraîneur
// y répond ici. Le barème affiché vient du serveur (PlayerRequestService::options).
const props = defineProps({
    requests:   { type: Array,   required: true },
    budget:     { type: Number,  default: 0 },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['respond', 'open-player']);

const ICONS = { leave: '💤', playing_time: '⏱️', ailment: '🩹' };

const signed = (n) => (n > 0 ? `+${n}` : `${n}`);

// Une option est indisponible si le serveur la bloque ou si le budget ne couvre pas son coût.
const blockedReason = (option) => {
    if (option.disabled) return option.disabled_reason ?? 'Indisponible';
    if (option.cost && option.cost > props.budget) return 'Budget insuffisant';
    return null;
};
</script>

<template>
    <div class="border border-amber-200 rounded-xl bg-amber-50/60 p-4 shadow-card">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <h3 class="text-xs font-bold text-amber-700 uppercase tracking-wider">
                🙋 Demandes des joueurs <span class="font-normal text-amber-600">({{ requests.length }})</span>
            </h3>
            <span class="text-[11px] text-slate-500">Sans réponse avant la fin de la semaine, la demande est considérée comme refusée.</span>
        </div>

        <div class="space-y-3">
            <article v-for="request in requests" :key="request.id"
                     class="rounded-lg border border-slate-200 bg-white p-3">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span aria-hidden="true">{{ ICONS[request.type] ?? '🙋' }}</span>
                    <button type="button" class="text-sm font-bold text-slate-800 hover:text-teal-600 hover:underline"
                            @click="emit('open-player', request.player_id)">
                        {{ request.player_name }}
                    </button>
                    <span class="text-xs font-semibold text-slate-500">· {{ request.title }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-600 italic">« {{ request.message }} »</p>

                <div class="mt-3 grid grid-cols-1 gap-2"
                     :class="request.options.length > 2 ? 'sm:grid-cols-3' : 'sm:grid-cols-2'">
                    <button v-for="option in request.options" :key="option.key" type="button"
                            :disabled="processing || !!blockedReason(option)"
                            class="rounded-lg border px-3 py-2 text-left transition disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="option.tone === 'negative'
                                ? 'border-rose-200 bg-rose-50 hover:bg-rose-100'
                                : 'border-emerald-200 bg-emerald-50 hover:bg-emerald-100'"
                            @click="emit('respond', request, option.key)">
                        <span class="block text-sm font-bold text-slate-800">
                            {{ option.label }}
                            <span v-if="option.cost" class="font-semibold text-amber-700">· {{ option.cost.toLocaleString() }} billets</span>
                        </span>
                        <span v-if="option.note" class="block text-xs text-slate-500">{{ option.note }}</span>
                        <span v-if="option.promise" class="mt-1 block text-xs text-slate-600">
                            Tenue : <span class="font-semibold text-emerald-700">moral {{ signed(option.morale) }}, relation {{ signed(option.affinity) }}</span><br>
                            Rompue : <span class="font-semibold text-rose-600">moral {{ signed(option.broken_morale) }}, relation {{ signed(option.broken_affinity) }}</span>
                        </span>
                        <span v-else class="mt-1 block text-xs font-semibold"
                              :class="option.tone === 'negative' ? 'text-rose-600' : 'text-emerald-700'">
                            Moral {{ signed(option.morale) }}, relation {{ signed(option.affinity) }}
                        </span>
                        <span v-if="blockedReason(option)" class="mt-1 block text-xs font-semibold text-slate-500">
                            {{ blockedReason(option) }}
                        </span>
                    </button>
                </div>
            </article>
        </div>
    </div>
</template>
