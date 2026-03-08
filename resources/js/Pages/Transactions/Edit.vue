<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import SegmentedControl from '@/Components/Form/SegmentedControl.vue';
import TextField from '@/Components/Form/TextField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import PickerField from '@/Components/Form/PickerField.vue';
import DateField from '@/Components/Form/DateField.vue';
import ToggleField from '@/Components/Form/ToggleField.vue';
import AutocompleteField from '@/Components/Form/AutocompleteField.vue';
import Button from '@/Components/Base/Button.vue';
import SplitModal from '@/Components/Domain/SplitModal.vue';

const props = defineProps({
    transaction: Object,
    accounts: Array,
    categories: Array,
    payees: Array,
});

const form = useForm({
    type: props.transaction.type,
    amount: parseFloat(props.transaction.amount).toFixed(2),
    account_id: props.transaction.account_id,
    to_account_id: props.transaction.to_account_id ?? '',
    category_id: props.transaction.category_id ?? '',
    payee_name: props.transaction.payee_name || '',
    date: props.transaction.date,
    cleared: props.transaction.cleared,
    memo: props.transaction.memo || '',
    is_split: props.transaction.is_split || false,
    splits: props.transaction.splits || [],
});

const showDeleteConfirm = ref(false);

// Split transaction state
const showSplitModal = ref(false);

// Type toggle options (transfer disabled in edit mode)
const typeOptions = computed(() => [
    { value: 'expense', label: 'Expense', color: 'expense', disabled: props.transaction.type === 'transfer' },
    { value: 'income', label: 'Income', color: 'income', disabled: props.transaction.type === 'transfer' },
    { value: 'transfer', label: 'Transfer', color: 'transfer', disabled: true },
]);

const selectPayee = (payee) => {
    form.payee_name = payee.name;
    if (payee.default_category_id && !form.category_id && !form.is_split) {
        form.category_id = payee.default_category_id;
    }
};

// When type changes via SegmentedControl, flip the amount sign to match
watch(() => form.type, (newType, oldType) => {
    if (!oldType || newType === oldType || newType === 'transfer' || oldType === 'transfer') return;
    const num = parseFloat(form.amount);
    if (isNaN(num) || num === 0) return;
    if (newType === 'expense' && num > 0) form.amount = (-num).toFixed(2);
    if (newType === 'income' && num < 0) form.amount = (-num).toFixed(2);
});

// Preserve all filters through edit round-trip
const searchParams = new URLSearchParams(window.location.search);
const filterParams = {};
for (const [key, value] of searchParams) {
    if (key !== 'transaction') filterParams[key] = value;
}
const buildRoute = (name, params) => {
    const base = route(name, params);
    const qs = new URLSearchParams(filterParams).toString();
    return qs ? base + '?' + qs : base;
};

const submit = () => {
    form.put(buildRoute('transactions.update', props.transaction.id));
};

const deleteTransaction = () => {
    router.delete(buildRoute('transactions.destroy', props.transaction.id));
};

const splitInitialItems = computed(() => {
    if (form.splits.length > 0) return form.splits;
    if (form.category_id && form.amount) return [{ category_id: form.category_id, amount: form.amount }];
    return [];
});

const openSplitModal = () => {
    showSplitModal.value = true;
};

const handleSplitSave = (validSplits) => {
    if (validSplits.length === 0) {
        form.is_split = false;
        form.splits = [];
        form.category_id = '';
    } else if (validSplits.length === 1) {
        form.is_split = false;
        form.splits = [];
        form.category_id = validSplits[0].category_id;
    } else {
        form.is_split = true;
        form.splits = validSplits;
        form.category_id = '';
    }
    showSplitModal.value = false;
};

const handleSplitClose = () => {
    form.is_split = false;
    form.splits = [];
    form.category_id = '';
    showSplitModal.value = false;
};

const getSaveButtonVariant = () => {
    return form.type === 'expense' ? 'expense' : form.type === 'income' ? 'income' : 'transfer';
};
</script>

<template>
    <Head title="Edit Transaction" />

    <div class="min-h-screen bg-bg flex flex-col">
        <!-- Header -->
        <div class="bg-surface border-b border-border px-4 py-3 safe-area-top">
            <div class="flex items-center justify-between">
                <Link
                    :href="route('transactions.index', { ...filterParams, scroll_to: props.transaction.id })"
                    class="text-subtle font-medium flex items-center gap-1"
                >
                    <span class="text-lg">×</span> Cancel
                </Link>
                <span class="font-semibold text-body">Edit Transaction</span>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    :class="[
                        'font-semibold',
                        form.type === 'expense' ? 'text-danger' :
                        form.type === 'income' ? 'text-success' : 'text-info'
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

            <!-- Fields Card -->
            <div class="mx-3 mt-3 bg-surface rounded-xl overflow-hidden">
                <!-- Date -->
                <DateField
                    v-model="form.date"
                    label="Date"
                />

                <!-- Account / From -->
                <PickerField
                    v-model="form.account_id"
                    :label="form.type === 'transfer' ? 'From Account' : 'Account'"
                    :options="accounts"
                    placeholder="Select account"
                />

                <!-- To Account (transfers only) -->
                <PickerField
                    v-if="form.type === 'transfer'"
                    v-model="form.to_account_id"
                    label="To Account"
                    :options="accounts"
                    placeholder="Select account"
                />

                <!-- Payee (not for transfers) -->
                <AutocompleteField
                    v-if="form.type !== 'transfer'"
                    v-model="form.payee_name"
                    label="Payee"
                    :placeholder="form.type === 'income' ? 'Who paid you?' : 'Who did you pay?'"
                    :suggestions="payees"
                    @select="selectPayee"
                />

                <!-- Amount -->
                <AmountField
                    v-model="form.amount"
                    label="Amount"
                    :transaction-type="form.type"
                />

                <!-- Category -->
                <template v-if="form.type !== 'transfer'">
                    <!-- Split display -->
                    <div v-if="form.is_split" class="flex items-center justify-between px-4 py-3.5 border-b border-border">
                        <span class="text-sm text-subtle">Category</span>
                        <button
                            type="button"
                            @click="openSplitModal"
                            class="text-sm font-medium text-primary"
                        >
                            Split ({{ form.splits.length }}) →
                        </button>
                    </div>
                    <!-- Regular category picker -->
                    <PickerField
                        v-else
                        v-model="form.category_id"
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
                <!-- Optional category for transfers (deducts from budget envelope) -->
                <PickerField
                    v-if="form.type === 'transfer'"
                    v-model="form.category_id"
                    label="Category"
                    :options="categories"
                    placeholder="None (optional)"
                    grouped
                    group-items-key="categories"
                    searchable
                    :null-option="{ label: 'None' }"
                />

                <!-- Cleared -->
                <ToggleField
                    v-model="form.cleared"
                    label="Cleared"
                    on-label="Cleared"
                    off-label="Not yet"
                />

                <!-- Memo -->
                <TextField
                    v-model="form.memo"
                    label="Memo"
                    placeholder="Add note..."
                    :border-bottom="false"
                />
            </div>

            <!-- Make Recurring Link (only for expense/income, not transfers or already recurring) -->
            <div v-if="form.type !== 'transfer' && !props.transaction.recurring_id" class="mx-3 mt-4">
                <Link
                    :href="route('recurring.create', {
                        type: form.type,
                        payee_name: form.payee_name,
                        amount: form.amount,
                        account_id: form.account_id,
                        category_id: form.category_id,
                    })"
                    class="block w-full py-3 text-center text-secondary font-medium text-sm"
                >
                    Make this recurring →
                </Link>
            </div>

            <!-- Delete Button -->
            <div class="mx-3 mt-1">
                <Button variant="ghost" class="w-full text-danger" @click="showDeleteConfirm = true">
                    Delete Transaction
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
                        <h3 class="text-lg font-semibold text-body">Delete Transaction?</h3>
                        <p class="text-subtle">This action cannot be undone.</p>
                        <div class="flex gap-3">
                            <Button variant="secondary" @click="showDeleteConfirm = false" class="flex-1">
                                Cancel
                            </Button>
                            <Button variant="danger" @click="deleteTransaction" class="flex-1">
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

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
