<script>
export default {
    name: 'InputSelect',
    props: {
        value: {
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
    data() {
        return {
            internalValue: this.value
        };
    },
    watch: {
        value(newValue) {
            this.internalValue = newValue;
        }
    },
    methods: {
        updateValue(value) {
            this.$emit('update:value', value);
        }
    }
};
</script>
<template>
    <div class="relative">
        <select :id="id" :value="internalValue" @input="updateValue($event.target.value)" :disabled="disabled" class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-slate-700">
            <option disabled value="">{{ placeholder }}</option>
            <slot></slot>
        </select>
    </div>
</template>
