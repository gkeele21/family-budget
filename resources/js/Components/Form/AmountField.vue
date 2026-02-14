<script setup>
import { computed, ref, watch } from 'vue';
import FormRow from './FormRow.vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: 'Amount' },
    transactionType: { type: String, default: 'expense' }, // expense, income, transfer
    placeholder: { type: String, default: '0.00' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    borderBottom: { type: Boolean, default: true },
    // When true, uses transaction type colors. When false, uses primary green like other fields.
    colorByType: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'blur']);

// Local input value for display
const inputValue = ref(formatInitialValue());

function formatInitialValue() {
    if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
        return '';
    }
    const num = parseFloat(props.modelValue);
    return isNaN(num) ? '' : num.toFixed(2);
}

const colorClass = computed(() => {
    // If colorByType is false, use a neutral non-transaction color
    if (!props.colorByType) {
        return 'text-secondary';
    }
    // Color by transaction type
    switch (props.transactionType) {
        case 'income': return 'text-success';
        case 'transfer': return 'text-info';
        default: return 'text-danger';
    }
});

const onInput = (e) => {
    let value = e.target.value;

    // Strip $ and any non-numeric chars except decimal
    value = value.replace(/[^\d.]/g, '');

    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }

    // Limit decimal places to 2
    if (parts.length === 2 && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].slice(0, 2);
    }

    inputValue.value = value;
    emit('update:modelValue', value);
};

const onBlur = (e) => {
    // Format to 2 decimal places on blur if there's a value
    if (inputValue.value && inputValue.value !== '') {
        const num = parseFloat(inputValue.value);
        if (!isNaN(num)) {
            inputValue.value = num.toFixed(2);
            emit('update:modelValue', inputValue.value);
        }
    }
    emit('blur', e);
};

// Watch for external changes to modelValue
watch(() => props.modelValue, (newVal) => {
    if (newVal === '' || newVal === null || newVal === undefined) {
        inputValue.value = '';
        return;
    }
    const num = parseFloat(newVal);
    const formatted = isNaN(num) ? '' : num.toFixed(2);
    // Only update if different to avoid cursor jumping
    if (formatted !== inputValue.value && document.activeElement !== document.querySelector(`[data-amount-field="${props.label}"]`)) {
        inputValue.value = formatted;
    }
});
</script>

<template>
    <FormRow :label="label" :border-bottom="borderBottom" :error="error">
        <div class="flex items-center justify-end">
            <input
                type="text"
                inputmode="decimal"
                :data-amount-field="label"
                :value="inputValue ? '$' + inputValue : ''"
                @input="onInput"
                @blur="onBlur"
                :placeholder="'$' + placeholder"
                :required="required"
                :disabled="disabled"
                :class="[
                    'bg-transparent focus:outline-none text-base font-medium text-right w-28',
                    colorClass,
                    disabled ? 'opacity-50' : '',
                ]"
            />
        </div>
    </FormRow>
</template>
