<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Toasts globaux : messages flash du serveur (success / info / error) et
// erreurs de validation renvoyées par une action (422 Inertia). Les props
// sont remplacées à chaque réponse : on réagit au changement de référence,
// donc un même message renvoyé deux fois s'affiche deux fois.
const page   = usePage();
const toasts = ref([]);
const timers = new Map();
let seq = 0;

const DURATION_MS = 5000;

function push(kind, text) {
    if (!text) return;
    const id = ++seq;
    toasts.value.push({ id, kind, text });
    timers.set(id, setTimeout(() => dismiss(id), DURATION_MS));
}

function dismiss(id) {
    clearTimeout(timers.get(id));
    timers.delete(id);
    toasts.value = toasts.value.filter(t => t.id !== id);
}

watch(() => page.props.flash, (flash) => {
    if (!flash) return;
    push('success', flash.success);
    push('info', flash.info);
    push('error', flash.error);
}, { immediate: true });

watch(() => page.props.errors, (errors) => {
    const messages = Object.values(errors ?? {}).flat().filter(Boolean);
    if (!messages.length) return;
    const extra = messages.length > 1 ? ` (+${messages.length - 1})` : '';
    push('error', messages[0] + extra);
}, { immediate: true });

onBeforeUnmount(() => timers.forEach(clearTimeout));

const STYLES = {
    success: 'bg-emerald-50 border-emerald-300 text-emerald-800',
    info:    'bg-sky-50 border-sky-300 text-sky-800',
    error:   'bg-rose-50 border-rose-300 text-rose-800',
};
const ICONS = { success: '✓', info: 'ℹ', error: '⚠' };
</script>

<template>
    <div class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:right-4 sm:w-96 z-[60] flex flex-col gap-2 pointer-events-none"
         aria-live="polite" role="status">
        <TransitionGroup enter-from-class="opacity-0 translate-y-2" enter-active-class="transition duration-200"
                         leave-to-class="opacity-0" leave-active-class="transition duration-200">
            <div v-for="t in toasts" :key="t.id"
                 class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg"
                 :class="STYLES[t.kind]"
                 :role="t.kind === 'error' ? 'alert' : undefined">
                <span class="font-bold" aria-hidden="true">{{ ICONS[t.kind] }}</span>
                <span class="flex-1">{{ t.text }}</span>
                <button type="button" class="opacity-60 hover:opacity-100" aria-label="Fermer" @click="dismiss(t.id)">✕</button>
            </div>
        </TransitionGroup>
    </div>
</template>
