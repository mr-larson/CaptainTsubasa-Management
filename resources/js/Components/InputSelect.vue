<script>
export default {
    name: 'InputSelect',

    // API v-model standard Vue 3 : modelValue / update:modelValue
    props: {
        modelValue: {
            type: [String, Number],
            default: ''
        },
        id: {
            type: String,
            default: ''
        },
        placeholder: {
            type: String,
            default: 'Select an option'
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },

    emits: ['update:modelValue'],

    computed: {
        internalValue: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit('update:modelValue', value);
            }
        }
    }
};
</script>

<template>
    <div class="relative">
        <select
            :id="id"
            v-model="internalValue"
            :disabled="disabled"
            class="appearance-none text-sm text-slate-900 bg-white border border-slate-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-200"
        >
            <!-- option placeholder -->
            <option v-if="placeholder" disabled value="">
                {{ placeholder }}
            </option>

            <!-- options passées par le parent -->
            <slot></slot>
        </select>
    </div>
</template>
