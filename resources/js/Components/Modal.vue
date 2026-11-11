<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, useId, watch } from 'vue';
import ButtonSecondary from './ButtonSecondary.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    // Classes ajoutées au panneau (ex. bordure de couleur, hauteur max).
    panelClass: {
        type: [String, Array, Object],
        default: '',
    },
    // Avec un titre, la modale affiche l'en-tête standard (titre + Fermer),
    // la marge interne et le pied de boutons (slot `footer`).
    title: {
        type: String,
        default: null,
    },
    subtitle: {
        type: String,
        default: null,
    },
    backdropClass: {
        type: String,
        default: 'bg-slate-900/50 backdrop-blur-sm',
    },
    closeLabel: {
        type: String,
        default: 'Fermer',
    },
});

const titleId = useId();

const emit = defineEmits(['close']);

// Focus : envoyé dans la modale à l'ouverture, gardé dedans (Tab), rendu à
// l'élément d'origine à la fermeture.
const panel = ref(null);
let previouslyFocused = null;
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

watch(
    () => props.show,
    async () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
            previouslyFocused = document.activeElement;
            await nextTick();
            (panel.value?.querySelector(FOCUSABLE) ?? panel.value)?.focus();
        } else {
            document.body.style.overflow = null;
            previouslyFocused?.focus?.();
            previouslyFocused = null;
        }
    }
);

const trapFocus = (e) => {
    if (e.key !== 'Tab' || !props.show || !panel.value) return;
    const items = [...panel.value.querySelectorAll(FOCUSABLE)];
    if (!items.length) return;
    const first = items[0];
    const last  = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
};

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
    trapFocus(e);
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <Teleport to="body">
        <Transition leave-active-class="duration-200">
            <div v-show="show" class="fixed inset-0 z-50 overflow-y-auto" scroll-region>
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0" :class="backdropClass" />
                    </div>
                </Transition>

                <!-- Centrage vertical ; un clic hors du panneau ferme la modale. -->
                <div class="relative flex min-h-full items-center justify-center px-4 py-6" @click.self="close">
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        ref="panel"
                        role="dialog"
                        aria-modal="true"
                        :aria-labelledby="title ? titleId : undefined"
                        tabindex="-1"
                        class="w-full bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all focus:outline-none"
                        :class="[maxWidthClass, panelClass]"
                    >
                        <div v-if="show && title" class="space-y-3 p-4 sm:p-5">
                            <header class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 :id="titleId" class="text-sm font-bold text-slate-700">{{ title }}</h3>
                                    <p v-if="subtitle" class="mt-0.5 text-xs text-slate-500">{{ subtitle }}</p>
                                </div>
                                <ButtonSecondary v-if="closeable" size="sm" class="shrink-0" @click="close">{{ closeLabel }}</ButtonSecondary>
                            </header>
                            <slot />
                            <footer v-if="$slots.footer" class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
                                <slot name="footer" />
                            </footer>
                        </div>
                        <slot v-else-if="show" />
                    </div>
                </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
