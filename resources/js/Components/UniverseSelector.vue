<script setup>
import { universeEnabled, setUniverseEnabled, toggleLegendCountry } from './universeSelection';
const props = defineProps({
    config: { type: Object, required: true },
    options: { type: Object, default: () => ({ counts: {}, countries: [] }) },
    inGame: { type: Boolean, default: false },
});
const groups = [
    { title: 'Captain Tsubasa', items: [
        { key: 'captain_tsubasa', label: 'Clubs et joueurs japonais', description: 'Les équipes du collège et les joueurs japonais libres.' },
        { key: 'captain_tsubasa_international', label: 'Internationaux Captain Tsubasa', description: 'Les rivaux étrangers du même univers.' },
    ] },
    { title: 'Autres mangas et animés', items: [
        { key: 'ecole_des_champions', label: 'École des Champions' },
        { key: 'hungry_heart', label: 'Hungry Heart' },
        { key: 'blue_lock', label: 'Blue Lock' },
        { key: 'ao_ashi', label: 'Ao Ashi' },
    ] },
    { title: 'Football réel', items: [
        { key: 'world_legends', label: 'Légendes internationales', description: 'Des joueurs réels de différentes époques. Choisis aussi les pays.' },
    ] },
    { title: 'Créations personnelles', items: [
        { key: 'original', label: 'Créations et clins d’œil', description: 'Les personnages originaux illustrés, les proches et les références personnelles.' },
    ] },
];
const enabled = (key) => universeEnabled(props.config, key);
const selectCountry = (name, checked) => toggleLegendCountry(props.config, name, checked, (props.options.countries ?? []).map(c => c.name));
</script>

<template>
    <section class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4 space-y-4" aria-label="Univers de joueurs">
        <div>
            <h4 class="text-sm font-bold text-slate-700">Univers de joueurs</h4>
            <p class="mt-1 text-xs text-slate-500">Choisis les joueurs disponibles au draft et sur le marché. Les effectifs déjà constitués restent en place.</p>
            <p class="mt-1 text-xs text-slate-500">{{ inGame ? 'Effectifs présents dans cette sauvegarde ; les nouveaux ajouts du catalogue ne sont pas importés ici.' : 'Effectifs du catalogue actuel, avant les autres filtres de la partie.' }}</p>
        </div>
        <fieldset v-for="group in groups" :key="group.title" class="space-y-2">
            <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ group.title }}</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label v-for="item in group.items" :key="item.key" class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors" :class="enabled(item.key) ? 'border-teal-300 bg-teal-50' : 'border-slate-200 bg-white'">
                    <input type="checkbox" :checked="enabled(item.key)" @change="setUniverseEnabled(config, item.key, $event.target.checked)" class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-400" />
                    <span class="flex-1 min-w-0">
                        <span class="flex flex-wrap justify-between gap-x-3 gap-y-1 text-xs font-semibold text-slate-700"><span>{{ item.label }}</span><span class="font-normal text-slate-500">{{ options.counts?.[item.key] ?? 0 }} joueurs</span></span>
                        <span v-if="item.description" class="mt-1 block text-xs text-slate-500">{{ item.description }}</span>
                    </span>
                </label>
            </div>
            <div v-if="group.title === 'Football réel'" class="rounded-lg border border-slate-200 bg-white p-3 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-slate-700">Pays des légendes</span>
                    <div class="flex gap-3 text-xs">
                        <button type="button" :disabled="!enabled('world_legends')" @click="config.legends_countries = null" class="text-teal-700 underline disabled:opacity-40">Tous les pays</button>
                        <button type="button" :disabled="!enabled('world_legends')" @click="config.legends_countries = []" class="text-slate-600 underline disabled:opacity-40">Aucun</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2" :class="{ 'opacity-50': !enabled('world_legends') }">
                    <label v-for="country in options.countries ?? []" :key="country.name" class="flex items-center gap-2 text-xs text-slate-700">
                        <input type="checkbox" :disabled="!enabled('world_legends')" :checked="config.legends_countries == null || config.legends_countries.includes(country.name)" @change="selectCountry(country.name, $event.target.checked)" class="rounded border-slate-300 text-teal-600 focus:ring-teal-400" />
                        <span>{{ country.flag }} {{ country.name }} <span class="text-slate-500">({{ country.count }})</span></span>
                    </label>
                </div>
                <p v-if="enabled('world_legends') && config.legends_countries?.length === 0" class="text-xs text-amber-700">Aucun pays choisi : les légendes ne seront pas disponibles sur le marché ou au draft.</p>
            </div>
        </fieldset>
    </section>
</template>
