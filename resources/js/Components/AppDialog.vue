<script setup>
import Modal from '@/Components/Modal.vue';
import { dialogState, closeDialog } from '@/Composables/useDialog.js';
</script>

<template>
    <!-- Modal gère déjà Échap, le piège de focus et le retour du focus -->
    <Modal :show="dialogState.open" max-width="md" @close="closeDialog(false)">
        <div class="p-6" :aria-labelledby="dialogState.title ? 'app-dialog-title' : undefined">
            <h2 v-if="dialogState.title" id="app-dialog-title" class="mb-2 text-lg font-bold text-slate-800">
                {{ dialogState.title }}
            </h2>
            <p class="whitespace-pre-line text-sm leading-6 text-slate-600">{{ dialogState.message }}</p>
            <ul v-if="dialogState.details.length" class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                <li v-for="(d, i) in dialogState.details" :key="i">{{ d }}</li>
            </ul>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button v-if="!dialogState.alertOnly" type="button" @click="closeDialog(false)"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    {{ dialogState.cancelLabel }}
                </button>
                <button type="button" @click="closeDialog(true)"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-full px-5 text-sm font-bold"
                        :class="dialogState.danger
                            ? 'bg-rose-500 text-white hover:bg-rose-600'
                            : 'bg-teal-500 text-slate-900 hover:bg-teal-400'">
                    {{ dialogState.confirmLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>
