<script setup>
import TabBar from '@/Components/TabBar.vue';
import { fieldClass } from '@/Components/fieldStyles';
import { computed, ref } from 'vue';

// Écran d'édition commun (joueurs / équipes, en partie ou en base) :
// liste filtrable à gauche, carte à onglets à droite. Sur grand écran les deux
// colonnes ont la même hauteur et seul le contenu de l'onglet défile.
const props = defineProps({
    title: { type: String, required: true },
    items: { type: Array, required: true },
    selectedId: { type: [Number, String], default: null },
    creating: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Rechercher' },
    // Texte sur lequel porte la recherche pour un élément.
    searchText: { type: Function, required: true },
    emptyLabel: { type: String, default: 'Aucun résultat.' },
    addTitle: { type: String, default: 'Créer' },
    // [{ key, label, badge?, disabled?, disabledHint? }]
    tabs: { type: Array, required: true },
    activeTab: { type: String, required: true },
});

const emit = defineEmits(['select', 'create', 'update:activeTab']);

const searchQuery = ref('');
const filteredItems = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.items;
    return props.items.filter(item => props.searchText(item).toLowerCase().includes(q));
});

const isSelected = (item) => !props.creating && props.selectedId === item.id;
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-2 lg:h-[calc(100vh-11rem)] lg:min-h-[480px]">
        <!-- LISTE -->
        <div class="lg:w-72 shrink-0 border border-slate-200 rounded-xl bg-slate-50 p-3 flex flex-col gap-3 max-h-[60vh] lg:max-h-none lg:h-full">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">
                    {{ title }} <span class="text-slate-400 font-normal">({{ items.length }})</span>
                </h3>
                <button
                    type="button"
                    @click="emit('create')"
                    class="flex items-center justify-center h-7 px-2.5 rounded-full text-xs font-bold shadow-sm transition-colors"
                    :class="creating ? 'bg-teal-700 text-white ring-2 ring-teal-200' : 'bg-teal-500 text-white hover:bg-teal-600'"
                    :title="addTitle"
                >
                    + Ajouter
                </button>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" aria-hidden="true" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                    </svg>
                </div>
                <input
                    type="search"
                    v-model="searchQuery"
                    class="block w-full py-1.5 pl-9 pr-3" :class="fieldClass()"
                    :placeholder="searchPlaceholder"
                >
            </div>

            <ul class="space-y-1 overflow-y-auto pr-1 min-h-0 flex-1">
                <li v-for="item in filteredItems" :key="item.id">
                    <a
                        href="#"
                        @click.prevent="emit('select', item)"
                        :class="isSelected(item)
                            ? 'bg-teal-500 text-white border-teal-500 shadow-sm'
                            : 'bg-white text-slate-700 border-slate-200 hover:border-teal-300 hover:bg-teal-50'"
                        class="flex items-center gap-2 px-3 py-2 border rounded-lg transition-all"
                    >
                        <slot name="item" :item="item" :selected="isSelected(item)" />
                    </a>
                </li>
                <li v-if="filteredItems.length === 0" class="text-xs text-slate-400 italic px-2 py-3 text-center">
                    {{ emptyLabel }}
                </li>
            </ul>
        </div>

        <!-- CARTE À ONGLETS -->
        <div class="flex-1 min-w-0 min-h-0 bg-slate-50 border border-slate-200 rounded-2xl shadow-sm flex flex-col">
            <div class="shrink-0 flex items-end justify-between flex-wrap gap-3 px-6 md:px-8 pt-4 border-b border-slate-200">
                <TabBar :model-value="activeTab" :tabs="tabs" @update:model-value="key => emit('update:activeTab', key)" />

                <div class="flex items-center gap-2 pb-2">
                    <span v-if="creating" class="text-2xs font-bold px-2 py-0.5 rounded-full bg-teal-50 text-teal-700">
                        Nouveau
                    </span>
                    <slot name="badges" />
                </div>
            </div>

            <!-- Contenu de l'onglet : seule zone qui défile -->
            <div class="flex-1 min-h-0 overflow-y-auto p-4 md:p-6">
                <slot />
            </div>
        </div>
    </div>
</template>
