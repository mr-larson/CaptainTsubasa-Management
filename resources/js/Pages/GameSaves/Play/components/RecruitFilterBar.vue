<script setup>
import { fieldClass } from '@/Components/fieldStyles';
import { recruitmentSorts } from '../recruitment.js';

/** Barre de filtres du recrutement (Draft, Transferts). À brancher sur useRecruitFilters. */
defineProps({
    positions:  { type: Array,  required: true },     // [{ key, label, count? }]
    origins:    { type: Array,  default: () => [] },  // [] = pas de filtre d'univers
    priceLabel: { type: String, default: 'Prix max. (GL)' },
});

const search   = defineModel('search',   { type: String, default: '' });
const position = defineModel('position', { type: String, default: 'ALL' });
const origin   = defineModel('origin',   { type: String, default: 'ALL' });
const sort     = defineModel('sort',     { type: String, default: 'name' });
const maxPrice = defineModel('maxPrice', { type: [Number, String], default: '' });

const field = fieldClass({ size: 'sm', border: 'border-slate-300' });
const withCount = (option) => option.count == null ? option.label : `${option.label} (${option.count})`;
</script>

<template>
    <div class="flex flex-wrap items-end gap-2">
        <label class="min-w-[160px] flex-1 text-xs text-slate-500">
            Rechercher un joueur
            <input v-model="search" type="search" class="mt-1 block w-full" :class="field" placeholder="Nom ou prénom" />
        </label>
        <label v-if="origins.length" class="min-w-0 text-xs text-slate-500">
            Univers
            <select v-model="origin" class="mt-1 block w-full" :class="field">
                <option v-for="option in origins" :key="option.key" :value="option.key">{{ withCount(option) }}</option>
            </select>
        </label>
        <label class="min-w-0 text-xs text-slate-500">
            Poste
            <select v-model="position" class="mt-1 block w-full" :class="field">
                <option v-for="option in positions" :key="option.key" :value="option.key">{{ withCount(option) }}</option>
            </select>
        </label>
        <label class="min-w-0 text-xs text-slate-500">
            Trier par
            <select v-model="sort" class="mt-1 block w-full" :class="field">
                <option v-for="option in recruitmentSorts" :key="option.key" :value="option.key">{{ option.label }}</option>
            </select>
        </label>
        <label class="text-xs text-slate-500">
            {{ priceLabel }}
            <input v-model.number="maxPrice" type="number" min="0" class="mt-1 block w-28" :class="field" placeholder="Tous" />
        </label>
    </div>
</template>
