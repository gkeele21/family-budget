<script setup>
import FormRow from './FormRow.vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
    type: { type: String, default: 'text' },
    inputmode: { type: String, default: null },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    borderBottom: { type: Boolean, default: true },
    textAlign: { type: String, default: 'right' }, // left, right
});

const emit = defineEmits(['update:modelValue', 'focus', 'blur']);

const onInput = (e) => {
    emit('update:modelValue', e.target.value);
};
</script>

<template>
    <FormRow :label="label" :border-bottom="borderBottom" :error="error">
        <input
            :type="type"
            :inputmode="inputmode"
            :value="modelValue"
            @input="onInput"
            @focus="$emit('focus', $event)"
            @blur="$emit('blur', $event)"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :class="[
                'flex-1 bg-transparent focus:outline-none text-base font-medium min-w-0',
                textAlign === 'right' ? 'text-right' : 'text-left',
                modelValue ? 'text-body' : 'text-subtle',
                disabled ? 'opacity-50' : '',
            ]"
        />
    </FormRow>
</template>
