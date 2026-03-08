<script setup>
import { Head, useForm, Link, router, usePage } from '@inertiajs/vue3';
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
import VoiceOverlay from '@/Components/Domain/VoiceOverlay.vue';
import { useSpeechRecognition } from '@/Composables/useSpeechRecognition.js';
import { useTheme } from '@/Composables/useTheme.js';

const { isSupported: voiceSupported } = useSpeechRecognition();
const { voiceInputEnabled } = useTheme();
const aiEnabled = usePage().props.auth.user.ai_enabled;

const props = defineProps({
    accounts: Array,
    categories: Array,
    payees: Array,
});

// Use account from URL query param if present, otherwise first account
const urlParams = new URLSearchParams(window.location.search);
const defaultAccountId = (() => {
    const paramId = parseInt(urlParams.get('account'));
    if (paramId && props.accounts.some(a => a.id === paramId)) return paramId;
    return props.accounts[0]?.id || '';
})();

const form = useForm({
    type: 'expense',
    amount: '',  // Signed: negative for expenses, positive for income
    account_id: defaultAccountId,
    category_id: '',
    payee_name: '',
    date: (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; })(),
    cleared: false,
    memo: '',
    to_account_id: '',
    is_split: false,
    splits: [],
    update_payee_default: false,
});

const showPayeeDefaultPrompt = ref(false);
const selectedPayeeForUpdate = ref(null);

// Split transaction state
const showSplitModal = ref(false);

// Type toggle options
const typeOptions = [
    { value: 'expense', label: 'Expense', color: 'expense' },
    { value: 'income', label: 'Income', color: 'income' },
    { value: 'transfer', label: 'Transfer', color: 'transfer' },
];

// Filter accounts for "To" picker (exclude selected "From" account)
const toAccountOptions = computed(() => {
    return props.accounts.filter(a => a.id !== form.account_id);
});

const selectPayee = (payee) => {
    form.payee_name = payee.name;
    if (payee.default_category_id && !form.is_split) {
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

const submit = () => {
    const categoryId = form.is_split ? form.splits[0]?.category_id : form.category_id;
    const existingPayee = props.payees.find(p => p.name.toLowerCase() === form.payee_name?.toLowerCase());

    if (existingPayee && categoryId && existingPayee.default_category_id !== categoryId && form.type !== 'transfer') {
        selectedPayeeForUpdate.value = existingPayee;
        showPayeeDefaultPrompt.value = true;
        return;
    }

    form.post(route('transactions.store'));
};

const submitWithPayeeUpdate = (updateDefault) => {
    form.update_payee_default = updateDefault;
    showPayeeDefaultPrompt.value = false;
    form.post(route('transactions.store'));
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

// Voice input
const showVoiceOverlay = ref(false);

const handleVoiceCreated = ({ batchId }) => {
    showVoiceOverlay.value = false;
    // Redirect to transactions index — the new transactions show there with highlights
    router.visit(route('transactions.index'));
};
</script>

<template>
    <Head title="Add Transaction" />

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
                <span class="font-semibold text-body">New Transaction</span>
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
                    :label="form.type === 'transfer' ? 'From' : 'Account'"
                    :options="accounts"
                    placeholder="Select account"
                />

                <!-- To Account (transfers only) -->
                <PickerField
                    v-if="form.type === 'transfer'"
                    v-model="form.to_account_id"
                    label="To"
                    :options="toAccountOptions"
                    placeholder="Select destination"
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
                    :error="form.errors.amount"
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
                        :error="form.errors.category_id"
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

                <!-- Cleared (not for transfers) -->
                <ToggleField
                    v-if="form.type !== 'transfer'"
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

            <!-- Voice input button -->
            <button
                v-if="aiEnabled && voiceSupported && voiceInputEnabled && form.type !== 'transfer'"
                type="button"
                @click="showVoiceOverlay = true"
                class="mx-3 mt-3 flex items-center justify-center gap-2 py-3 rounded-xl bg-primary/10 border border-dashed border-primary/30 text-primary text-sm font-medium"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
                Tap to speak transaction
            </button>

            <!-- Transfer hint -->
            <p v-if="form.type === 'transfer'" class="text-xs text-subtle text-center mt-3 px-4">
                {{ form.category_id ? 'Transfer will also deduct from the selected budget category' : 'Creates linked transactions in both accounts' }}
            </p>

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
                    {{ form.type === 'transfer' ? 'Save Transfer' : 'Save Transaction' }}
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

        <!-- Voice Overlay -->
        <VoiceOverlay
            :show="showVoiceOverlay"
            :accounts="accounts"
            :categories="categories"
            @close="showVoiceOverlay = false"
            @created="handleVoiceCreated"
        />

        <!-- Update Payee Default Prompt -->
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
                    v-if="showPayeeDefaultPrompt"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                    @click.self="submitWithPayeeUpdate(false)"
                >
                    <div class="w-full max-w-sm bg-surface rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-body">Update default category?</h3>
                        <p class="text-subtle">
                            You're using a different category for <strong>{{ selectedPayeeForUpdate?.name }}</strong>.
                            Would you like to update its default category?
                        </p>
                        <div class="flex gap-3">
                            <Button variant="secondary" @click="submitWithPayeeUpdate(false)" class="flex-1">
                                No, just save
                            </Button>
                            <Button @click="submitWithPayeeUpdate(true)" class="flex-1">
                                Yes, update
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
