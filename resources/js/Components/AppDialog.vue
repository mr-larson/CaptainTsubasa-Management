<script setup>
import Modal from '@/Components/Modal.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import { dialogState, closeDialog } from '@/Composables/useDialog.js';
</script>

<template>
    <!-- Modal gère déjà Échap, le piège de focus et le retour du focus -->
    <Modal :show="dialogState.open" max-width="md" @close="closeDialog(false)">
        <div class="p-6" :aria-labelledby="dialogState.title ? 'app-dialog-title' : undefined">
            <h2 v-if="dialogState.title" id="app-dialog-title" class="mb-2 text-lg font-bold text-slate-700">
                {{ dialogState.title }}
            </h2>
            <p class="whitespace-pre-line text-sm leading-6 text-slate-600">{{ dialogState.message }}</p>
            <ul v-if="dialogState.details.length" class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                <li v-for="(d, i) in dialogState.details" :key="i">{{ d }}</li>
            </ul>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <ButtonSecondary v-if="!dialogState.alertOnly" size="lg" class="shadow-none" @click="closeDialog(false)">
                    {{ dialogState.cancelLabel }}
                </ButtonSecondary>
                <ButtonPrimary type="button" size="lg" :tone="dialogState.danger ? 'rose' : 'teal'" @click="closeDialog(true)">
                    {{ dialogState.confirmLabel }}
                </ButtonPrimary>
            </div>
        </div>
    </Modal>
</template>
