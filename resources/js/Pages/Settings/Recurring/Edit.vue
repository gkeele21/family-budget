<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import SegmentedControl from '@/Components/Form/SegmentedControl.vue';
import AutocompleteField from '@/Components/Form/AutocompleteField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import PickerField from '@/Components/Form/PickerField.vue';
import DateField from '@/Components/Form/DateField.vue';
import Button from '@/Components/Base/Button.vue';
import SplitModal from '@/Components/Domain/SplitModal.vue';

const props = defineProps({
    recurring: Object,
    accounts: Array,
    categories: Array,
    payees: Array,
});

const initCategories = props.recurring.categories || [];

const form = useForm({
    type: props.recurring.type,
    amount: parseFloat(props.recurring.amount).toFixed(2),
    account_id: props.recurring.account_id,
    categories: initCategories,
    payee_name: props.recurring.payee_name || '',
    frequency: props.recurring.frequency,
    next_date: props.recurring.next_date,
    end_date: props.recurring.end_date || '',
});

const showDeleteConfirm = ref(false);

// Track single category for the PickerField display
const singleCategoryId = ref(initCategories.length === 1 ? initCategories[0].category_id : '');
const isSplit = ref(initCategories.length > 1);

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
    if (payee.default_category_id && !isSplit.value && !singleCategoryId.value) {
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
    form.put(route('recurring.update', props.recurring.id));
};

const deleteRecurring = () => {
    router.delete(route('recurring.destroy', props.recurring.id));
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

const showEndDatePicker = ref(!!props.recurring.end_date);

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
    <Head title="Edit Recurring" />

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
                <span class="font-semibold text-body">Edit Recurring</span>
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

            <!-- Delete Button -->
            <div class="mx-3 mt-3">
                <Button variant="ghost" class="w-full text-danger" @click="showDeleteConfirm = true">
                    Delete Recurring
                </Button>
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
                    Save
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

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showDeleteConfirm"
                    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
                    @click.self="showDeleteConfirm = false"
                >
                    <div class="bg-surface rounded-2xl p-6 max-w-sm w-full space-y-4">
                        <h3 class="text-lg font-semibold text-body">Delete Recurring?</h3>
                        <p class="text-subtle">This will stop future transactions from being created. Past transactions will not be affected.</p>
                        <div class="flex gap-3">
                            <Button variant="secondary" @click="showDeleteConfirm = false" class="flex-1">
                                Cancel
                            </Button>
                            <Button variant="danger" @click="deleteRecurring" class="flex-1">
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
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
