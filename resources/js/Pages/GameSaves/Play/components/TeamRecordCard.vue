<script setup>
/** Carte "Bilan" : victoires / nuls / défaites. */
const props = defineProps({
    wins:   { type: Number, default: 0 },
    draws:  { type: Number, default: 0 },
    losses: { type: Number, default: 0 },
});
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col justify-center gap-1.5">
        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Bilan</h4>
        <div class="flex gap-2">
            <div class="flex-1 text-center bg-emerald-50 rounded-lg py-2">
                <div class="text-lg font-black text-emerald-600">{{ wins }}</div>
                <div class="text-[10px] text-emerald-500 font-semibold">Victoires</div>
            </div>
            <div class="flex-1 text-center bg-slate-100 rounded-lg py-2">
                <div class="text-lg font-black text-slate-500">{{ draws }}</div>
                <div class="text-[10px] text-slate-400 font-semibold">Nuls</div>
            </div>
            <div class="flex-1 text-center bg-rose-50 rounded-lg py-2">
                <div class="text-lg font-black text-rose-500">{{ losses }}</div>
                <div class="text-[10px] text-rose-400 font-semibold">Défaites</div>
            </div>
        </div>
    </div>
</template>
