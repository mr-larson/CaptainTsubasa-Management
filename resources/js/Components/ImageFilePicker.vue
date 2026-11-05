<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';

// Sélecteur d'image (photo joueur, logo équipe) : bouton « Choisir », nom du
// fichier et aperçu carré. v-model = le File choisi (null = aucun nouveau
// fichier) ; `currentUrl` = l'image déjà enregistrée, affichée à défaut.
// Remettre le v-model à null (sélection d'une autre fiche, reset) efface
// l'aperçu local. L'URL de l'aperçu est émise (`preview`) pour les pages qui
// l'affichent ailleurs (en-tête de fiche).
const props = defineProps({
    modelValue: { default: null }, // File | null
    currentUrl: { type: String, default: null },
    alt: { type: String, default: '' },
    emptyLabel: { type: String, default: 'Aucune' },
});

const emit = defineEmits(['update:modelValue', 'preview']);

const input = ref(null);
const previewUrl = ref(null);
const selectedName = ref('');

const revokePreview = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
};

function openPicker() {
    input.value?.click();
}

function onChange(e) {
    const file = e.target.files?.[0] ?? null;

    revokePreview();
    selectedName.value = file ? file.name : '';
    if (file) previewUrl.value = URL.createObjectURL(file);

    emit('update:modelValue', file);

    // permet de re-choisir le même fichier
    e.target.value = '';
}

watch(() => props.modelValue, (file) => {
    if (!file) {
        revokePreview();
        selectedName.value = '';
    }
});

watch(previewUrl, (url) => emit('preview', url));

onBeforeUnmount(() => revokePreview());
</script>

<template>
    <div class="mt-1 flex flex-col gap-2">
        <div class="flex items-center gap-3">
            <!-- input file caché -->
            <input
                ref="input"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onChange"
            />

            <!-- faux input -->
            <div class="flex items-center w-full md:w-56">
                <button
                    type="button"
                    class="shrink-0 px-3 py-1.5 rounded-l-full border border-slate-300 bg-white text-sm text-slate-700 hover:bg-white focus:outline-none focus:border-slate-700"
                    @click="openPicker"
                >
                    Choisir
                </button>

                <div
                    class="flex-1 px-3 py-1.5 rounded-r-full border border-l-0 border-slate-300 bg-white text-sm text-slate-600 truncate"
                    :title="selectedName || 'Aucun fichier choisi'"
                >
                    {{ selectedName || 'Aucun fichier choisi' }}
                </div>
            </div>

            <!-- preview carré -->
            <div class="h-16 w-16 rounded border bg-white overflow-hidden flex items-center justify-center">
                <slot name="preview" :src="previewUrl || currentUrl">
                    <img
                        v-if="previewUrl || currentUrl"
                        :src="previewUrl || currentUrl"
                        class="h-full w-full object-cover"
                        :alt="alt"
                    />
                </slot>
                <span v-if="!(previewUrl || currentUrl)" class="text-xs text-slate-400">{{ emptyLabel }}</span>
            </div>

            <slot name="actions" />
        </div>

        <slot />
    </div>
</template>
