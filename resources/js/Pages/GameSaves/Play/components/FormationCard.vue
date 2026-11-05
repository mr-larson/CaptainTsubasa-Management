<script setup>
import { FORMATIONS, FORMATION_LIST } from '@/Pages/Match/engine/formations.js';

/** Carte "Formation" : sélecteur + description + répartition par zone. */
const props = defineProps({
    formation:     { type: String, required: true },
    formationData: { type: Object, default: null },
    readonly:      { type: Boolean, default: false },
    // (key) => { asIs: {off, secondary}, optimized: {off, secondary} } — impact sur le onze actuel
    impact:        { type: Function, default: null },
});

// Libellé d'option : hors-poste restants une fois le onze réorganisé sur cette formation.
const optionLabel = (f) => {
    if (!props.impact) return f.label;
    const off = props.impact(f.key).optimized.off;
    return off ? `${f.label}  ·  ⚠ ${off} hors poste` : `${f.label}  ·  ✓`;
};

const emit = defineEmits(['change']);
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4 flex flex-col gap-3">
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Formation</h3>

        <div v-if="readonly" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm font-semibold bg-white text-slate-700">
            {{ FORMATION_LIST.find(f => f.key === formation)?.label ?? formation }}
        </div>
        <select v-else
            :value="formation"
            @change="emit('change', $event.target.value)"
            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold bg-white text-slate-700 focus:ring-2 focus:ring-teal-300 focus:outline-none cursor-pointer"
        >
            <option v-for="f in FORMATION_LIST" :key="f.key" :value="f.key">{{ optionLabel(f) }}</option>
        </select>

        <slot name="after-select" />

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
