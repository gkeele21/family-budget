<script setup>
import { ref, computed, watch } from 'vue';
import FormRow from './FormRow.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
    // Array of suggestion items - can be strings or objects
    suggestions: { type: Array, default: () => [] },
    // Key to use for display text when suggestions are objects (default: 'name')
    labelKey: { type: String, default: 'name' },
    // Key to use for unique identifier when suggestions are objects (default: 'id')
    valueKey: { type: String, default: 'id' },
    // Maximum suggestions to show
    maxSuggestions: { type: Number, default: 5 },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    borderBottom: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'select']);

const inputRef = ref(null);
const showSuggestions = ref(false);
const isFocused = ref(false);

const getLabel = (item) => {
    if (typeof item === 'string') return item;
    return item[props.labelKey] || item.name || item.label || String(item);
};

const getKey = (item, index) => {
    if (typeof item === 'string') return `${item}-${index}`;
    return item[props.valueKey] || item.id || index;
};

const filteredSuggestions = computed(() => {
    if (!props.modelValue || props.modelValue.length === 0) {
        return [];
    }
    const searchTerm = props.modelValue.toLowerCase();
    return props.suggestions
        .filter(item => getLabel(item).toLowerCase().includes(searchTerm))
        .slice(0, props.maxSuggestions);
});

watch(() => props.modelValue, () => {
    if (props.modelValue && props.modelValue.length > 0 && isFocused.value) {
        showSuggestions.value = filteredSuggestions.value.length > 0;
    } else {
        showSuggestions.value = false;
    }
});

const onInput = (e) => {
    emit('update:modelValue', e.target.value);
};

const onFocus = () => {
    isFocused.value = true;
    if (filteredSuggestions.value.length > 0) {
        showSuggestions.value = true;
    }
};

const onBlur = () => {
    // Delay hiding to allow click on suggestion
    setTimeout(() => {
        isFocused.value = false;
        showSuggestions.value = false;
    }, 200);
};

const selectItem = (item) => {
    emit('update:modelValue', getLabel(item));
    emit('select', item);
    showSuggestions.value = false;
};
</script>

<template>
    <div class="relative">
        <FormRow :label="label" :border-bottom="borderBottom && !showSuggestions" :error="error">
            <input
                ref="inputRef"
                type="text"
                :value="modelValue"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                :placeholder="placeholder"
                :disabled="disabled"
                :class="[
                    'flex-1 bg-transparent focus:outline-none text-base font-medium text-right min-w-0',
                    modelValue ? 'text-body' : 'text-subtle',
                    disabled ? 'opacity-50' : '',
                ]"
            />
        </FormRow>

        <!-- Suggestions dropdown -->
        <div
            v-if="showSuggestions"
            class="absolute left-0 right-0 top-full bg-surface rounded-b-xl shadow-lg z-10 border-t border-border overflow-hidden"
        >
            <button
                v-for="(item, index) in filteredSuggestions"
                :key="getKey(item, index)"
                type="button"
                @click="selectItem(item)"
                class="w-full text-left px-4 py-3 hover:bg-surface-overlay text-sm text-body border-b border-border last:border-b-0"
            >
                {{ getLabel(item) }}
            </button>
        </div>
    </div>
</template>
