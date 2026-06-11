<template>
    <div class="mt-1 flex flex-wrap gap-2">
        <label
            v-for="p in positions"
            :key="p"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-sm select-none"
            :class="p === position
                ? 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                : (model.includes(p)
                    ? 'border-teal-500 bg-teal-50 text-teal-700 cursor-pointer'
                    : 'border-gray-300 bg-stone-50 text-slate-600 hover:bg-white cursor-pointer')"
        >
            <input
                type="checkbox"
                class="hidden"
                :disabled="p === position"
                :checked="model.includes(p)"
                @change="toggle(p)"
            />
            {{ labels[p] ?? p }}
        </label>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue'

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    // Poste principal : exclu de la sélection (un poste ne peut pas être à la fois principal et secondaire)
    position: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const positions = ['Goalkeeper', 'Defender', 'Midfielder', 'Forward']
const labels = {
    Goalkeeper: 'Gardien',
    Defender: 'Défenseur',
    Midfielder: 'Milieu',
    Forward: 'Attaquant',
}

const model = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []))

function toggle(p) {
    if (p === props.position) return
    const next = model.value.includes(p)
        ? model.value.filter(x => x !== p)
        : [...model.value, p]
    emit('update:modelValue', next)
}

// Si le poste principal change, on le retire des postes secondaires
watch(() => props.position, (pos) => {
    if (pos && model.value.includes(pos)) {
        emit('update:modelValue', model.value.filter(x => x !== pos))
    }
})
</script>
