<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import FormRow from './FormRow.vue';
import SignToggle from './SignToggle.vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    // When set, renders inside FormRow with this label. When empty, renders a bare inline input.
    label: { type: String, default: '' },
    transactionType: { type: String, default: 'expense' }, // expense, income, transfer
    placeholder: { type: String, default: '0.00' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    borderBottom: { type: Boolean, default: true },
    // Override color class (e.g. 'text-secondary', 'text-body'). When empty, derives from transactionType.
    color: { type: String, default: '' },
    // Show a +/− toggle for negative values (mobile keyboards lack minus key)
    allowNegative: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'blur']);

// Local input value for display
const inputValue = ref(formatInitialValue());
const isEditing = ref(false);
const inputRef = ref(null);

function formatInitialValue() {
    if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
        return '';
    }
    const num = parseFloat(props.modelValue);
    return isNaN(num) ? '' : num.toFixed(2);
}

const colorClass = computed(() => {
    if (props.color) return props.color;
    switch (props.transactionType) {
        case 'income': return 'text-success';
        case 'transfer': return 'text-info';
        default: return 'text-danger';
    }
});

const displayValue = computed(() => {
    if (inputValue.value) {
        return inputValue.value.startsWith('-') ? '-$' + inputValue.value.slice(1) : '$' + inputValue.value;
    }
    return '';
});

const isNegative = computed(() => inputValue.value.startsWith('-'));

const toggleSign = () => {
    if (inputValue.value.startsWith('-')) {
        inputValue.value = inputValue.value.slice(1);
    } else if (inputValue.value) {
        inputValue.value = '-' + inputValue.value;
    } else {
        inputValue.value = '-';
    }
    emit('update:modelValue', inputValue.value);
};

const startEditing = () => {
    if (props.disabled) return;
    isEditing.value = true;
    nextTick(() => {
        if (inputRef.value) {
            inputRef.value.focus();
            inputRef.value.select();
        }
    });
};

const onInput = (e) => {
    let value = e.target.value;

    // Strip $ and any non-numeric chars except decimal and minus
    const isNegative = value.includes('-');
    value = value.replace(/[^\d.]/g, '');
    if (isNegative) value = '-' + value;

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
    isEditing.value = false;
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
    // Only update if different and not currently editing
    if (formatted !== inputValue.value && !isEditing.value) {
        inputValue.value = formatted;
    }
});
</script>

<template>
    <!-- With label: FormRow wrapped -->
    <FormRow v-if="label" :label="label" :border-bottom="borderBottom" :error="error">
        <div class="flex items-center justify-end gap-1.5">
            <SignToggle
                v-if="allowNegative && isEditing"
                :negative="isNegative"
                @toggle="toggleSign"
            />
            <input
                v-if="isEditing"
                ref="inputRef"
                type="text"
                inputmode="decimal"
                :value="inputValue ? displayValue : '$'"
                @input="onInput"
                @blur="onBlur"
                @keyup.enter="$event.target.blur()"
                :class="[
                    'bg-transparent focus:outline-none text-base font-medium text-right w-28',
                    colorClass,
                ]"
            />
            <div
                v-else
                @click="startEditing"
                :class="[
                    'text-base font-medium text-right cursor-text',
                    displayValue ? colorClass : 'text-subtle',
                    disabled ? 'opacity-50' : '',
                ]"
            >
                {{ displayValue || '$' + placeholder }}
            </div>
        </div>
    </FormRow>
    <!-- Without label: bare inline, editing -->
    <div v-else-if="isEditing" class="flex items-center gap-1">
        <SignToggle
            v-if="allowNegative"
            :negative="isNegative"
            @toggle="toggleSign"
        />
        <input
            ref="inputRef"
            type="text"
            inputmode="decimal"
            :value="inputValue ? displayValue : '$'"
            @input="onInput"
            @blur="onBlur"
            @keyup.enter="$event.target.blur()"
            :class="[
                'bg-transparent focus:outline-none font-semibold text-right flex-1 min-w-0 origin-right',
                colorClass,
            ]"
            style="font-size: 16px; transform: scale(0.875); transform-origin: right center;"
        />
    </div>
    <!-- Without label: bare inline, display -->
    <div
        v-else
        @click="startEditing"
        :class="[
            'font-semibold text-right text-sm cursor-text',
            displayValue ? colorClass : 'text-subtle',
            disabled ? 'opacity-50' : '',
        ]"
    >
        {{ displayValue || '$' + placeholder }}
    </div>
</template>
