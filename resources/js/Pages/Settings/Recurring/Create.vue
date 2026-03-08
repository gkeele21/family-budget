<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import SegmentedControl from '@/Components/Form/SegmentedControl.vue';
import AutocompleteField from '@/Components/Form/AutocompleteField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import PickerField from '@/Components/Form/PickerField.vue';
import DateField from '@/Components/Form/DateField.vue';
import Button from '@/Components/Base/Button.vue';
import SplitModal from '@/Components/Domain/SplitModal.vue';

const props = defineProps({
    accounts: Array,
    categories: Array,
    payees: Array,
});

const query = new URLSearchParams(window.location.search);

const parseIntOrDefault = (value, defaultValue) => {
    const parsed = parseInt(value, 10);
    return isNaN(parsed) ? defaultValue : parsed;
};

const form = useForm({
    type: query.get('type') || 'expense',
    amount: query.get('amount') || '',
    account_id: parseIntOrDefault(query.get('account_id'), props.accounts[0]?.id || ''),
    categories: query.get('category_id')
        ? [{ category_id: parseIntOrDefault(query.get('category_id'), ''), amount: parseFloat(query.get('amount')) || 0 }]
        : [],
    payee_name: query.get('payee_name') || '',
    frequency: 'monthly',
    next_date: (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; })(),
    end_date: '',
});

// Track single category for the PickerField display
const singleCategoryId = ref(form.categories.length === 1 ? form.categories[0].category_id : '');
const isSplit = ref(form.categories.length > 1);

// Sync single category changes back to form.categories
watch(singleCategoryId, (newVal) => {
    if (!isSplit.value) {
        form.categories = newVal
            ? [{ category_id: newVal, amount: parseFloat(form.amount) || 0 }]
            : [];
    }
});

// Keep single-category amount in sync with form amount
watch(() => form.amount, (newVal) => {
    if (!isSplit.value && form.categories.length === 1) {
        form.categories = [{ category_id: form.categories[0].category_id, amount: parseFloat(newVal) || 0 }];
    }
});

const selectPayee = (payee) => {
    form.payee_name = payee.name;
    if (payee.default_category_id && !isSplit.value) {
        singleCategoryId.value = payee.default_category_id;
    }
};

// When type changes via SegmentedControl, flip the amount sign to match
watch(() => form.type, (newType, oldType) => {
    if (!oldType || newType === oldType) return;
    const num = parseFloat(form.amount);
    if (isNaN(num) || num === 0) return;
    if (newType === 'expense' && num > 0) form.amount = (-num).toFixed(2);
    if (newType === 'income' && num < 0) form.amount = (-num).toFixed(2);
});

const submit = () => {
    form.post(route('recurring.store'));
};

const typeOptions = [
    { value: 'expense', label: 'Expense', color: 'expense' },
    { value: 'income', label: 'Income', color: 'income' },
];

const frequencyOptions = [
    { id: 'daily', name: 'Daily' },
    { id: 'weekly', name: 'Weekly' },
    { id: 'biweekly', name: 'Every 2 weeks' },
    { id: 'monthly', name: 'Monthly' },
    { id: 'yearly', name: 'Yearly' },
];

const endOptions = [
    { id: '', name: 'Never' },
    { id: 'custom', name: 'On date...' },
];

const showEndDatePicker = ref(false);

watch(() => form.end_date, (newValue) => {
    if (newValue && newValue !== '' && newValue !== 'custom') {
        showEndDatePicker.value = true;
    }
});

const getSaveButtonVariant = () => {
    return form.type === 'expense' ? 'expense' : 'income';
};

// Split transaction state
const showSplitModal = ref(false);

const splitInitialItems = computed(() => {
    if (isSplit.value && form.categories.length > 0) return form.categories;
    if (singleCategoryId.value && form.amount) return [{ category_id: singleCategoryId.value, amount: form.amount }];
    return [];
});

const openSplitModal = () => {
    showSplitModal.value = true;
};

const handleSplitSave = (validSplits) => {
    if (validSplits.length === 0) {
        isSplit.value = false;
        form.categories = [];
        singleCategoryId.value = '';
    } else if (validSplits.length === 1) {
        isSplit.value = false;
        singleCategoryId.value = validSplits[0].category_id;
        form.categories = validSplits;
    } else {
        isSplit.value = true;
        singleCategoryId.value = '';
        form.categories = validSplits;
    }
    showSplitModal.value = false;
};

const handleSplitClose = () => {
    isSplit.value = false;
    form.categories = [];
    singleCategoryId.value = '';
    showSplitModal.value = false;
};
</script>

<template>
    <Head title="New Recurring" />

    <div class="min-h-screen bg-bg flex flex-col">
        <!-- Header with Cancel / Title / Save -->
        <div class="bg-surface border-b border-border px-4 py-3 safe-area-top">
            <div class="flex items-center justify-between">
                <Link
                    :href="route('recurring.index')"
                    class="text-subtle font-medium flex items-center gap-1"
                >
                    <span class="text-lg">&times;</span> Cancel
                </Link>
                <span class="font-semibold text-body">New Recurring</span>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    :class="[
                        'font-semibold',
                        form.type === 'expense' ? 'text-danger' : 'text-success'
                    ]"
                >
                    Save
                </button>
            </div>
        </div>

        <form @submit.prevent="submit" class="flex-1 flex flex-col">
            <!-- Type Toggle -->
            <div class="mx-3 mt-3">
                <SegmentedControl
                    v-model="form.type"
                    :options="typeOptions"
                />
            </div>

            <!-- Compact Fields Card -->
            <div class="mx-3 mt-3 bg-surface rounded-xl overflow-hidden">
                <!-- Next Date -->
                <DateField
                    v-model="form.next_date"
                    label="Next Date"
                />

                <!-- Account -->
                <PickerField
                    v-model="form.account_id"
                    label="Account"
                    :options="accounts"
                    placeholder="Select account"
                />

                <!-- Payee -->
                <AutocompleteField
                    v-model="form.payee_name"
                    label="Payee"
                    placeholder="Who is this for?"
                    :suggestions="payees"
                    @select="selectPayee"
                />

                <!-- Amount -->
                <AmountField
                    v-model="form.amount"
                    label="Amount"
                    :transaction-type="form.type"
                    :error="form.errors.amount"
                />

                <!-- Category -->
                <template v-if="form.type !== 'transfer'">
                    <!-- Split display -->
                    <div v-if="isSplit" class="flex items-center justify-between px-4 py-3.5 border-b border-border">
                        <span class="text-sm text-subtle">Category</span>
                        <button
                            type="button"
                            @click="openSplitModal"
                            class="text-sm font-medium text-primary"
                        >
                            Split ({{ form.categories.length }}) &rarr;
                        </button>
                    </div>
                    <!-- Regular category picker -->
                    <PickerField
                        v-else
                        v-model="singleCategoryId"
                        label="Category"
                        :options="categories"
                        placeholder="Select category"
                        grouped
                        group-items-key="categories"
                        searchable
                        :action-option="{ label: 'Split Transaction...' }"
                        :null-option="{ label: 'Unassigned' }"
                        @action="openSplitModal"
                    />
                </template>

                <!-- Frequency -->
                <PickerField
                    v-model="form.frequency"
                    label="Frequency"
                    :options="frequencyOptions"
                    placeholder="Select frequency"
                />

                <!-- End -->
                <PickerField
                    v-if="!showEndDatePicker"
                    v-model="form.end_date"
                    label="End"
                    :options="endOptions"
                    placeholder="Never"
                    :border-bottom="false"
                    @update:model-value="(val) => { if (val === 'custom') showEndDatePicker = true; }"
                />
                <DateField
                    v-else
                    v-model="form.end_date"
                    label="End"
                    :border-bottom="false"
                    clearable
                    @clear="showEndDatePicker = false"
                />
            </div>

            <!-- Spacer -->
            <div class="flex-1"></div>

            <!-- Submit Button -->
            <div class="p-3 safe-area-bottom">
                <Button
                    type="submit"
                    :variant="getSaveButtonVariant()"
                    :loading="form.processing"
                    full-width
                    size="lg"
                >
                    Save Recurring
                </Button>
            </div>
        </form>

        <!-- Split Transaction Modal -->
        <SplitModal
            :show="showSplitModal"
            :categories="categories"
            :total-amount="form.amount"
            :default-type="form.type"
            :initial-items="splitInitialItems"
            @save="handleSplitSave"
            @close="handleSplitClose"
        />
    </div>
</template>

<style scoped>
.safe-area-top {
    padding-top: max(12px, env(safe-area-inset-top));
}
.safe-area-bottom {
    padding-bottom: max(12px, env(safe-area-inset-bottom));
}
</style>
