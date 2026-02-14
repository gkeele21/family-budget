<script setup>
import { computed } from 'vue';

const model = defineModel({
    required: true,
});

const props = defineProps({
    placeholder: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    // For grouped options
    grouped: {
        type: Boolean,
        default: false,
    },
    // Configurable keys for flexibility with different data structures
    valueKey: {
        type: String,
        default: 'value',
    },
    labelKey: {
        type: String,
        default: 'label',
    },
    groupLabelKey: {
        type: String,
        default: 'label',
    },
    groupOptionsKey: {
        type: String,
        default: 'options',
    },
    // Optional function to format the label: (option) => string
    labelFormatter: {
        type: Function,
        default: null,
    },
    variant: {
        type: String,
        default: 'default', // 'default' | 'minimal'
        validator: (value) => ['default', 'minimal'].includes(value),
    },
});

const hasValue = computed(() => model.value !== '' && model.value !== null && model.value !== undefined);

const selectClasses = computed(() => {
    const base = 'w-full appearance-none cursor-pointer focus:outline-none';

    if (props.variant === 'minimal') {
        return [
            base,
            'bg-transparent text-base font-medium truncate',
            hasValue.value ? 'text-primary' : 'text-subtle',
        ];
    }

    // Default variant
    return [
        base,
        'px-4 py-2.5 bg-surface-inset border border-border rounded-xl shadow-sm text-body',
        'focus:outline-none',
        !hasValue.value && 'text-subtle',
    ];
});

// Helper to get option value
const getOptionValue = (option) => option[props.valueKey];

// Helper to get option label
const getOptionLabel = (option) => {
    if (props.labelFormatter) {
        return props.labelFormatter(option);
    }
    return option[props.labelKey];
};

// Helper to get group label
const getGroupLabel = (group) => group[props.groupLabelKey];

// Helper to get group options
const getGroupOptions = (group) => group[props.groupOptionsKey];
</script>

<template>
    <select
        v-model="model"
        :class="selectClasses"
    >
        <option v-if="placeholder" value="">{{ placeholder }}</option>

        <!-- Grouped options -->
        <template v-if="grouped">
            <optgroup
                v-for="group in options"
                :key="getGroupLabel(group)"
                :label="getGroupLabel(group)"
            >
                <option
                    v-for="option in getGroupOptions(group)"
                    :key="getOptionValue(option)"
                    :value="getOptionValue(option)"
                >
                    {{ getOptionLabel(option) }}
                </option>
            </optgroup>
        </template>

        <!-- Flat options -->
        <template v-else>
            <option
                v-for="option in options"
                :key="getOptionValue(option)"
                :value="getOptionValue(option)"
            >
                {{ getOptionLabel(option) }}
            </option>
        </template>
    </select>
</template>
