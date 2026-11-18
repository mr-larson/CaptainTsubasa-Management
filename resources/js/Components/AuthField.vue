<script setup>
import { computed, useAttrs } from 'vue';
import InputError from './InputError.vue';
import InputLabel from './InputLabel.vue';
import InputText from './InputText.vue';

/**
 * Champ des pages d'authentification : libellé, champ de saisie, aide facultative, contenu libre
 * sous le champ (slot, ex. bouton « Afficher le mot de passe ») puis message d'erreur.
 * `class` s'applique à l'ensemble du champ ; les autres attributs (type, required, autocomplete…)
 * vont au champ de saisie.
 */
defineOptions({ inheritAttrs: false });

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    error: { type: String, default: null },
    help: { type: String, default: null },
    // Largeur et fond des écrans de connexion et d'inscription (sinon pleine largeur).
    wide: { type: Boolean, default: false },
    // Message d'erreur plus proche du champ.
    compact: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const attrs = useAttrs();
const inputAttrs = computed(() => {
    const { class: _class, ...rest } = attrs;
    return rest;
});
const helpId = computed(() => `${props.id}-help`);
</script>

<template>
    <div :class="attrs.class">
        <InputLabel :for="id" :value="label" />
        <InputText
            :id="id"
            :class="wide ? 'mt-1 block md:w-96 bg-stone-100' : 'mt-1 block w-full'"
            :model-value="modelValue"
            :aria-describedby="help ? helpId : undefined"
            v-bind="inputAttrs"
            @update:model-value="$emit('update:modelValue', $event)"
        />
        <p v-if="help" :id="helpId" class="mt-1 text-xs text-slate-500">{{ help }}</p>
        <slot />
        <InputError :class="compact ? 'mt-1' : 'mt-2'" :message="error" />
    </div>
</template>
