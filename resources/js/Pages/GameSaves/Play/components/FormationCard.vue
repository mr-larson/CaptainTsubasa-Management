<script setup>
import { FORMATIONS, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';

/** Carte "Formation" : sélecteur + description + répartition par zone. */
const props = defineProps({
    formation:     { type: String, required: true },
    formationData: { type: Object, default: null },
});

const emit = defineEmits(['change']);
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col gap-3">
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Formation</h3>

        <select
            :value="formation"
            @change="emit('change', $event.target.value)"
            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold bg-white text-slate-800 focus:ring-2 focus:ring-teal-300 focus:outline-none cursor-pointer"
        >
            <option v-for="f in FORMATION_LIST" :key="f.key" :value="f.key">{{ f.label }}</option>
        </select>

        <p class="text-xs text-slate-500 leading-relaxed min-h-[2.5rem]">
            {{ FORMATIONS[formation]?.description ?? '—' }}
        </p>

        <div class="border-t border-slate-200 pt-3">
            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Répartition</h4>
            <div class="flex gap-1.5 flex-wrap">
                <template v-if="formationData">
                    <span v-for="(label, zone) in ['GK','DEF','MDF','MOF','ATT']" :key="zone"
                          class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                          :class="[zone==0?'bg-yellow-100 text-yellow-700':zone==1?'bg-blue-100 text-blue-700':zone==2?'bg-green-100 text-green-700':zone==3?'bg-orange-100 text-orange-700':'bg-red-100 text-red-700']">
                        {{ label }} ×{{ Object.values(formationData.slots).filter(s => s.zone === zone).length }}
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>
