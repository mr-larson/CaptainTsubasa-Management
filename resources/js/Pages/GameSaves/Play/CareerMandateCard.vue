<script setup>
defineProps({
    career:  { type: Object, required: true },
    compact: { type: Boolean, default: false },
});
defineEmits(['open-rules']);
</script>

<template>
    <!-- Variante compacte : empilée verticalement, pensée pour la colonne visuelle de gauche -->
    <div v-if="compact"
         class="px-3 py-3 rounded-xl border bg-white"
         :class="career.alert ? 'border-rose-300 ring-1 ring-rose-100' : 'border-slate-200'">
        <div class="flex items-center gap-1.5 min-w-0">
            <span class="text-base shrink-0">🎯</span>
            <p class="text-xs font-bold text-slate-700 truncate">{{ career.mandate.label }}</p>
            <button type="button" @click="$emit('open-rules')"
                    class="shrink-0 w-4 h-4 rounded-full bg-slate-200 text-slate-500 text-[10px] font-bold flex items-center justify-center hover:bg-teal-500 hover:text-white transition-colors"
                    title="Voir le détail des règles et objectifs">ℹ</button>
        </div>
        <p class="text-[11px] text-slate-400 mt-1">{{ career.difficulty_label }}</p>
        <p class="text-[11px] text-slate-400">
            🏆 {{ career.titles_won }} / {{ career.titles_required }} titre(s)
            <span v-if="career.alert" class="text-rose-500 font-semibold block">board en alerte</span>
        </p>
        <div class="flex items-center gap-2 mt-2">
            <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full transition-all"
                     :class="career.confidence <= 25 ? 'bg-rose-500'
                         : career.confidence <= 50 ? 'bg-amber-400' : 'bg-emerald-500'"
                     :style="{ width: career.confidence + '%' }"></div>
            </div>
            <span class="text-xs font-black w-6 text-right"
                  :class="career.confidence <= 25 ? 'text-rose-500' : 'text-slate-700'">
                {{ career.confidence }}
            </span>
        </div>
    </div>

    <!-- Variante large : bandeau horizontal, utilisée sur mobile (pas de colonne visuelle) -->
    <div v-else
         class="px-4 py-3 rounded-xl border bg-white"
         :class="career.alert ? 'border-rose-300 ring-1 ring-rose-100' : 'border-slate-200'">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-lg shrink-0">🎯</span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-700 truncate flex items-center gap-1.5">
                        Objectif : {{ career.mandate.label }}
                        <span class="text-slate-400 font-normal">· {{ career.difficulty_label }}</span>
                        <button type="button" @click="$emit('open-rules')"
                                class="shrink-0 w-4 h-4 rounded-full bg-slate-200 text-slate-500 text-[10px] font-bold flex items-center justify-center hover:bg-teal-500 hover:text-white transition-colors"
                                title="Voir le détail des règles et objectifs">ℹ</button>
                    </p>
                    <p class="text-[11px] text-slate-400">
                        🏆 {{ career.titles_won }} / {{ career.titles_required }} titre(s) pour gagner
                        <span v-if="career.alert" class="text-rose-500 font-semibold">· board en alerte</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <div class="w-28 h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                         :class="career.confidence <= 25 ? 'bg-rose-500'
                             : career.confidence <= 50 ? 'bg-amber-400' : 'bg-emerald-500'"
                         :style="{ width: career.confidence + '%' }"></div>
                </div>
                <span class="text-sm font-black w-7 text-right"
                      :class="career.confidence <= 25 ? 'text-rose-500' : 'text-slate-700'">
                    {{ career.confidence }}
                </span>
            </div>
        </div>
    </div>
</template>
