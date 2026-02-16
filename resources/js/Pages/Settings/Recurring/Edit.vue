<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SegmentedControl from '@/Components/Form/SegmentedControl.vue';
import AutocompleteField from '@/Components/Form/AutocompleteField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import PickerField from '@/Components/Form/PickerField.vue';
import DateField from '@/Components/Form/DateField.vue';
import Button from '@/Components/Base/Button.vue';

const props = defineProps({
    recurring: Object,
    accounts: Array,
    categories: Array,
    payees: Array,
});

const form = useForm({
    type: props.recurring.type,
    amount: parseFloat(props.recurring.amount).toFixed(2),
    account_id: props.recurring.account_id,
    category_id: props.recurring.category_id || '',
    payee_name: props.recurring.payee_name || '',
    frequency: props.recurring.frequency,
    next_date: props.recurring.next_date,
    end_date: props.recurring.end_date || '',
});

const showDeleteConfirm = ref(false);

const selectPayee = (payee) => {
    form.payee_name = payee.name;
    if (payee.default_category_id && !form.category_id) {
        form.category_id = payee.default_category_id;
    }
};

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
                    <span class="text-lg">×</span> Cancel
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

                <!-- Account -->
                <PickerField
                    v-model="form.account_id"
                    label="Account"
                    :options="accounts"
                    placeholder="Select account"
                />

                <!-- Category -->
                <PickerField
                    v-model="form.category_id"
                    label="Category"
                    :options="categories"
                    placeholder="Select category"
                    grouped
                    group-items-key="categories"
                />

                <!-- Frequency -->
                <PickerField
                    v-model="form.frequency"
                    label="Frequency"
                    :options="frequencyOptions"
                    placeholder="Select frequency"
                />

                <!-- Next Date -->
                <DateField
                    v-model="form.next_date"
                    label="Next Date"
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
