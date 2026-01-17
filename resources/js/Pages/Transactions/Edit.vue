<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    transaction: Object,
    accounts: Array,
    categories: Array,
    payees: Array,
});

const form = useForm({
    type: props.transaction.type,
    amount: props.transaction.amount,
    account_id: props.transaction.account_id,
    category_id: props.transaction.category_id || '',
    payee_name: props.transaction.payee_name || '',
    date: props.transaction.date,
    cleared: props.transaction.cleared,
    memo: props.transaction.memo || '',
});

const showDeleteConfirm = ref(false);

const payeeSuggestions = ref([]);
const showPayeeSuggestions = ref(false);

watch(() => form.payee_name, (newValue) => {
    if (newValue && newValue.length > 0) {
        payeeSuggestions.value = props.payees.filter(p =>
            p.name.toLowerCase().includes(newValue.toLowerCase())
        ).slice(0, 5);
        showPayeeSuggestions.value = payeeSuggestions.value.length > 0;
    } else {
        showPayeeSuggestions.value = false;
    }
});

const selectPayee = (payee) => {
    form.payee_name = payee.name;
    if (payee.default_category_id && !form.category_id) {
        form.category_id = payee.default_category_id;
    }
    showPayeeSuggestions.value = false;
};

const submit = () => {
    form.put(route('transactions.update', props.transaction.id));
};

const deleteTransaction = () => {
    router.delete(route('transactions.destroy', props.transaction.id));
};

const getAmountColor = () => {
    if (form.type === 'expense') return 'text-budget-expense';
    if (form.type === 'income') return 'text-budget-income';
    return 'text-budget-transfer';
};
</script>

<template>
    <Head title="Edit Transaction" />

    <AppLayout>
        <template #title>Edit Transaction</template>
        <template #header-left>
            <Link :href="route('transactions.index')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>
        <template #header-right>
            <button
                @click="showDeleteConfirm = true"
                class="p-2 text-budget-expense hover:bg-white/10 rounded-full"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </template>

        <form @submit.prevent="submit" class="p-4 space-y-4">
            <!-- Type Toggle (disabled for transfers) -->
            <div class="flex bg-budget-card rounded-card p-1">
                <button
                    type="button"
                    @click="form.type = 'expense'"
                    :disabled="transaction.type === 'transfer'"
                    :class="[
                        'flex-1 py-2 rounded-lg font-medium transition-colors',
                        form.type === 'expense'
                            ? 'bg-budget-expense text-white'
                            : 'text-budget-text-secondary'
                    ]"
                >
                    Expense
                </button>
                <button
                    type="button"
                    @click="form.type = 'income'"
                    :disabled="transaction.type === 'transfer'"
                    :class="[
                        'flex-1 py-2 rounded-lg font-medium transition-colors',
                        form.type === 'income'
                            ? 'bg-budget-income text-white'
                            : 'text-budget-text-secondary'
                    ]"
                >
                    Income
                </button>
                <button
                    type="button"
                    disabled
                    :class="[
                        'flex-1 py-2 rounded-lg font-medium transition-colors',
                        form.type === 'transfer'
                            ? 'bg-budget-transfer text-white'
                            : 'text-budget-text-secondary'
                    ]"
                >
                    Transfer
                </button>
            </div>

            <!-- Amount -->
            <div class="bg-budget-card rounded-card p-4">
                <label class="block text-sm text-budget-text-secondary mb-1">Amount</label>
                <div class="flex items-center">
                    <span :class="['text-3xl font-bold mr-2', getAmountColor()]">$</span>
                    <input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        :class="['text-3xl font-bold font-mono w-full bg-transparent focus:outline-none', getAmountColor()]"
                        required
                    />
                </div>
            </div>

            <!-- Payee (not for transfers) -->
            <div v-if="form.type !== 'transfer'" class="bg-budget-card rounded-card p-4 relative">
                <label class="block text-sm text-budget-text-secondary mb-1">Payee</label>
                <input
                    v-model="form.payee_name"
                    type="text"
                    placeholder="Who did you pay?"
                    class="w-full bg-transparent text-lg focus:outline-none"
                    @focus="showPayeeSuggestions = payeeSuggestions.length > 0"
                    @blur="setTimeout(() => showPayeeSuggestions = false, 200)"
                />
                <div
                    v-if="showPayeeSuggestions"
                    class="absolute left-0 right-0 top-full mt-1 bg-white rounded-card shadow-lg z-10 border"
                >
                    <button
                        v-for="payee in payeeSuggestions"
                        :key="payee.id"
                        type="button"
                        @click="selectPayee(payee)"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50"
                    >
                        {{ payee.name }}
                    </button>
                </div>
            </div>

            <!-- Category (not for transfers) -->
            <div v-if="form.type !== 'transfer'" class="bg-budget-card rounded-card p-4">
                <label class="block text-sm text-budget-text-secondary mb-1">Category</label>
                <select
                    v-model="form.category_id"
                    class="w-full bg-transparent text-lg focus:outline-none"
                >
                    <option value="">Select a category</option>
                    <optgroup v-for="group in categories" :key="group.id" :label="group.name">
                        <option v-for="cat in group.categories" :key="cat.id" :value="cat.id">
                            {{ cat.icon }} {{ cat.name }}
                        </option>
                    </optgroup>
                </select>
            </div>

            <!-- Account -->
            <div class="bg-budget-card rounded-card p-4">
                <label class="block text-sm text-budget-text-secondary mb-1">Account</label>
                <select
                    v-model="form.account_id"
                    class="w-full bg-transparent text-lg focus:outline-none"
                    required
                >
                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                        {{ account.name }}
                    </option>
                </select>
            </div>

            <!-- Date -->
            <div class="bg-budget-card rounded-card p-4">
                <label class="block text-sm text-budget-text-secondary mb-1">Date</label>
                <input
                    v-model="form.date"
                    type="date"
                    class="w-full bg-transparent text-lg focus:outline-none"
                    required
                />
            </div>

            <!-- Cleared -->
            <div class="bg-budget-card rounded-card p-4 flex items-center justify-between">
                <span class="text-budget-text">Cleared</span>
                <button
                    type="button"
                    @click="form.cleared = !form.cleared"
                    :class="[
                        'w-12 h-7 rounded-full transition-colors relative',
                        form.cleared ? 'bg-budget-primary' : 'bg-gray-300'
                    ]"
                >
                    <span
                        :class="[
                            'absolute top-1 w-5 h-5 bg-white rounded-full transition-transform shadow',
                            form.cleared ? 'translate-x-6' : 'translate-x-1'
                        ]"
                    ></span>
                </button>
            </div>

            <!-- Memo -->
            <div class="bg-budget-card rounded-card p-4">
                <label class="block text-sm text-budget-text-secondary mb-1">Memo (optional)</label>
                <input
                    v-model="form.memo"
                    type="text"
                    placeholder="Add a note..."
                    class="w-full bg-transparent text-lg focus:outline-none"
                />
            </div>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-4 bg-gradient-to-r from-budget-primary to-budget-primary-light text-white font-semibold rounded-card hover:shadow-lg transition-shadow disabled:opacity-50"
            >
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
        </form>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteConfirm"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="showDeleteConfirm = false"
        >
            <div class="bg-white rounded-card p-6 max-w-sm w-full">
                <h3 class="text-lg font-semibold text-budget-text mb-2">Delete Transaction?</h3>
                <p class="text-budget-text-secondary mb-4">This action cannot be undone.</p>
                <div class="flex gap-3">
                    <button
                        @click="showDeleteConfirm = false"
                        class="flex-1 py-3 border border-gray-300 rounded-card font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteTransaction"
                        class="flex-1 py-3 bg-budget-expense text-white rounded-card font-medium"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
