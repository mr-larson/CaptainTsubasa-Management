<script setup>
// Dashboard — « Conseils de l'adjoint » (cf. useAssistantAdvice). Chaque conseil
// a une action ; Play.vue l'applique (changement, séances préparées, offre).
defineProps({
    advice: { type: Array, default: () => [] },
});

const emit = defineEmits(['apply']);

const ACTION_STYLE = {
    rotate:  'bg-amber-500 hover:bg-amber-400 text-white',
    train:   'bg-teal-500 hover:bg-teal-400 text-white',
    recruit: 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-300',
};
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Conseils de l'adjoint</h4>
        <ul v-if="advice.length" class="flex flex-col divide-y divide-slate-200">
            <li v-for="a in advice" :key="a.key" class="flex flex-col sm:flex-row sm:items-center gap-2 py-2 first:pt-0 last:pb-0">
                <span class="text-lg shrink-0 hidden sm:block" aria-hidden="true">{{ a.icon }}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-slate-700"><span class="sm:hidden">{{ a.icon }} </span>{{ a.title }}</div>
                    <div class="text-[11px] text-slate-500">{{ a.detail }}</div>
                </div>
                <button type="button" @click="emit('apply', a)"
                        class="shrink-0 self-start sm:self-center px-3 py-1 rounded-full text-[11px] font-bold transition-colors"
                        :class="ACTION_STYLE[a.type]">
                    {{ a.actionLabel }}
                </button>
            </li>
        </ul>
        <p v-else class="text-xs text-emerald-600">✓ Rien à signaler : l'équipe est prête.</p>
    </div>
</template>
