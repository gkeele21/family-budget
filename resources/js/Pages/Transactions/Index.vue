<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FAB from '@/Components/FAB.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    transactions: Object,
    accounts: Array,
    currentAccountId: Number,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        signDisplay: 'auto',
    }).format(amount);
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

const filterByAccount = (accountId) => {
    router.get(route('transactions.index'), accountId ? { account: accountId } : {}, {
        preserveState: true,
    });
};

const toggleCleared = (transaction) => {
    router.post(route('transactions.toggle-cleared', transaction.id), {}, {
        preserveScroll: true,
    });
};

const getAmountColor = (type) => {
    if (type === 'expense') return 'text-budget-expense';
    if (type === 'income') return 'text-budget-income';
    return 'text-budget-transfer';
};
</script>

<template>
    <Head title="Transactions" />

    <AppLayout>
        <template #title>Transactions</template>

        <div class="p-4 space-y-4">
            <!-- Account Filter -->
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4">
                <button
                    @click="filterByAccount(null)"
                    :class="[
                        'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors',
                        !currentAccountId
                            ? 'bg-budget-primary text-white'
                            : 'bg-budget-card text-budget-text-secondary hover:bg-gray-100'
                    ]"
                >
                    All Accounts
                </button>
                <button
                    v-for="account in accounts"
                    :key="account.id"
                    @click="filterByAccount(account.id)"
                    :class="[
                        'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors',
                        currentAccountId === account.id
                            ? 'bg-budget-primary text-white'
                            : 'bg-budget-card text-budget-text-secondary hover:bg-gray-100'
                    ]"
                >
                    {{ account.name }}
                </button>
            </div>

            <!-- Transactions by Date -->
            <div v-for="(dayTransactions, date) in transactions" :key="date" class="space-y-2">
                <h2 class="text-sm font-semibold text-budget-text-secondary px-1">
                    {{ formatDate(date) }}
                </h2>

                <div class="bg-budget-card rounded-card divide-y divide-gray-100">
                    <Link
                        v-for="transaction in dayTransactions"
                        :key="transaction.id"
                        :href="route('transactions.edit', transaction.id)"
                        class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <!-- Cleared Indicator -->
                            <button
                                @click.prevent="toggleCleared(transaction)"
                                :class="[
                                    'w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0',
                                    transaction.cleared
                                        ? 'bg-budget-primary border-budget-primary'
                                        : 'border-gray-300 hover:border-budget-primary'
                                ]"
                            >
                                <svg
                                    v-if="transaction.cleared"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3 w-3 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-budget-text truncate">{{ transaction.payee }}</span>
                                    <span v-if="transaction.recurring_id" class="text-budget-text-secondary text-xs">↻</span>
                                </div>
                                <div class="text-sm text-budget-text-secondary truncate">
                                    {{ transaction.account }}
                                    <span v-if="transaction.category"> · {{ transaction.category }}</span>
                                </div>
                            </div>
                        </div>

                        <div :class="['font-mono font-semibold', getAmountColor(transaction.type)]">
                            {{ formatCurrency(transaction.amount) }}
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="Object.keys(transactions).length === 0"
                class="text-center py-12"
            >
                <div class="text-4xl mb-4">📝</div>
                <h3 class="text-lg font-medium text-budget-text mb-2">No transactions yet</h3>
                <p class="text-budget-text-secondary mb-4">
                    Tap the + button to add your first transaction.
                </p>
            </div>
        </div>

        <template #fab>
            <FAB :href="route('transactions.create')" />
        </template>
    </AppLayout>
</template>
