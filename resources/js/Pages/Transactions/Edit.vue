<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import SegmentedControl from '@/Components/Form/SegmentedControl.vue';
import TextField from '@/Components/Form/TextField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import PickerField from '@/Components/Form/PickerField.vue';
import DateField from '@/Components/Form/DateField.vue';
import ToggleField from '@/Components/Form/ToggleField.vue';
import AutocompleteField from '@/Components/Form/AutocompleteField.vue';
import SelectInput from '@/Components/Form/SelectInput.vue';
import Button from '@/Components/Base/Button.vue';
import Modal from '@/Components/Base/Modal.vue';

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
const splitItems = ref([{ category_id: '', amount: '' }]);

// Type toggle options (transfer disabled in edit mode)
const typeOptions = computed(() => [
    { value: 'expense', label: 'Expense', color: 'expense', disabled: props.transaction.type === 'transfer' },
    { value: 'income', label: 'Income', color: 'income', disabled: props.transaction.type === 'transfer' },
    { value: 'transfer', label: 'Transfer', color: 'transfer', disabled: true },
]);

const flatCategories = computed(() => {
    const result = [];
    props.categories.forEach(group => {
        group.categories.forEach(cat => {
            result.push({ ...cat, groupName: group.name });
        });
    });
    return result;
});

const selectPayee = (payee) => {
    form.payee_name = payee.name;
    if (payee.default_category_id && !form.category_id && !form.is_split) {
        form.category_id = payee.default_category_id;
    }
};

const submit = () => {
    form.put(route('transactions.update', props.transaction.id));
};

const deleteTransaction = () => {
    router.delete(route('transactions.destroy', props.transaction.id));
};

// Split transaction functions
const openSplitModal = () => {
    if (form.splits.length > 0) {
        splitItems.value = form.splits.map(s => ({ ...s }));
    } else if (form.category_id && form.amount) {
        splitItems.value = [{ category_id: form.category_id, amount: form.amount }];
    } else {
        splitItems.value = [{ category_id: '', amount: '' }];
    }
    showSplitModal.value = true;
};

const addSplitItem = () => {
    splitItems.value.push({ category_id: '', amount: '' });
};

const removeSplitItem = (index) => {
    if (splitItems.value.length > 1) {
        splitItems.value.splice(index, 1);
    }
};

const totalSplitAmount = computed(() => {
    return splitItems.value.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
});

const remainingAmount = computed(() => {
    return (parseFloat(form.amount) || 0) - totalSplitAmount.value;
});

const isSplitBalanced = computed(() => Math.abs(remainingAmount.value) < 0.01);

const saveSplit = () => {
    const validSplits = splitItems.value.filter(s => s.category_id && parseFloat(s.amount) > 0);

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

const clearSplit = () => {
    form.is_split = false;
    form.splits = [];
    form.category_id = '';
    showSplitModal.value = false;
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
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
                    :href="route('transactions.index')"
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
                <!-- Payee (not for transfers) -->
                <AutocompleteField
                    v-if="form.type !== 'transfer'"
                    v-model="form.payee_name"
                    label="Payee"
                    placeholder="Who did you pay?"
                    :suggestions="payees"
                    @select="selectPayee"
                />

                <!-- Amount -->
                <AmountField
                    v-model="form.amount"
                    label="Amount"
                    :transaction-type="form.type"
                />

                <!-- Category (not for transfers) -->
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
                        :action-option="{ label: 'Split Transaction...' }"
                        :null-option="{ label: 'Unassigned' }"
                        @action="openSplitModal"
                    />
                </template>

                <!-- Account -->
                <PickerField
                    v-model="form.account_id"
                    label="Account"
                    :options="accounts"
                    placeholder="Select account"
                />

                <!-- Date -->
                <DateField
                    v-model="form.date"
                    label="Date"
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
        <Modal :show="showSplitModal" title="Split Transaction" @close="showSplitModal = false">
            <div class="px-4 pb-2 text-sm text-subtle">
                Total: {{ formatCurrency(parseFloat(form.amount) || 0) }}
            </div>

            <div class="flex-1 overflow-y-auto">
                <div class="bg-surface mx-3 rounded-xl overflow-hidden">
                    <div
                        v-for="(item, index) in splitItems"
                        :key="index"
                        class="flex items-center px-4 py-3.5 border-b border-border last:border-b-0"
                    >
                        <div class="flex-1 min-w-0">
                            <SelectInput
                                v-model="item.category_id"
                                :options="categories"
                                placeholder="Select category"
                                variant="minimal"
                                grouped
                                value-key="id"
                                label-key="name"
                                group-label-key="name"
                                group-options-key="categories"
                            />
                        </div>
                        <AmountField
                            v-model="item.amount"
                            transaction-type="expense"
                            class="w-20 ml-3"
                        />
                        <button
                            type="button"
                            @click="removeSplitItem(index)"
                            class="ml-2 p-1 text-border-strong hover:text-danger transition-colors"
                            :class="{ 'opacity-30 pointer-events-none': splitItems.length <= 1 }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    @click="addSplitItem"
                    class="w-full mt-3 mx-3 py-3 text-sm font-medium text-primary"
                >
                    + Add Category
                </button>
            </div>

            <!-- Remaining indicator -->
            <div class="px-4 py-3 border-t border-border">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-subtle">Remaining</span>
                    <span
                        class="font-semibold"
                        :class="{
                            'text-danger': remainingAmount > 0.01,
                            'text-success': isSplitBalanced,
                            'text-danger': remainingAmount < -0.01,
                        }"
                    >
                        {{ formatCurrency(remainingAmount) }}
                    </span>
                </div>
                <div class="mt-2 h-2 bg-border rounded-full overflow-hidden">
                    <div
                        class="h-full transition-all duration-300"
                        :class="{
                            'bg-danger': remainingAmount > 0.01,
                            'bg-success': isSplitBalanced,
                            'bg-danger': remainingAmount < -0.01,
                        }"
                        :style="{ width: `${Math.min(100, (totalSplitAmount / (parseFloat(form.amount) || 1)) * 100)}%` }"
                    ></div>
                </div>
            </div>

            <template #footer>
                <div class="flex gap-2">
                    <Button variant="secondary" @click="clearSplit" class="flex-1">Clear</Button>
                    <Button :disabled="!isSplitBalanced" @click="saveSplit" class="flex-1">Save Split</Button>
                </div>
            </template>
        </Modal>
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
