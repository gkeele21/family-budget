<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    month: String,
    category: Object,
    transactions: Array,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatMonth = (monthStr) => {
    const [year, month] = monthStr.split('-');
    const date = new Date(year, month - 1, 1); // month is 0-indexed
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today, ' + date.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday, ' + date.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
    }
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
};

// Group transactions by date
const groupedTransactions = computed(() => {
    const groups = {};
    props.transactions.forEach(t => {
        const dateKey = t.date;
        if (!groups[dateKey]) {
            groups[dateKey] = [];
        }
        groups[dateKey].push(t);
    });
    // Convert to array sorted by date descending
    return Object.entries(groups)
        .sort((a, b) => b[0].localeCompare(a[0]))
        .map(([date, transactions]) => ({
            date,
            label: formatDate(date),
            transactions,
        }));
});

const getAmountColor = (amount, type) => {
    if (type === 'transfer') return 'text-info';
    return amount < 0 ? 'text-danger' : 'text-success';
};

const toggleCleared = (transaction) => {
    router.post(route('transactions.toggle-cleared', transaction.id), {}, {
        preserveScroll: true,
    });
};

const isOverspent = props.category.available < 0;
</script>

<template>
    <Head :title="`${category.name} - Budget`" />

    <AppLayout>
        <template #title>{{ category.icon }} {{ category.name }}</template>
        <template #header-left>
            <Link :href="route('budget.index', { month })" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <div class="p-4 space-y-4">
            <!-- Month & Category Header -->
            <div class="text-center text-sm text-subtle">
                {{ formatMonth(month) }}
            </div>

            <!-- Budget Summary Card -->
            <div class="bg-surface rounded-card p-4">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-xs text-subtle uppercase">Budgeted</div>
                        <div class="font-semibold text-body">
                            {{ formatCurrency(category.budgeted) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-subtle uppercase">Spent</div>
                        <div class="font-semibold text-danger">
                            {{ formatCurrency(Math.abs(category.spent)) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-subtle uppercase">Available</div>
                        <div
                            class="font-semibold"
                            :class="isOverspent ? 'text-danger' : 'text-success'"
                        >
                            {{ formatCurrency(category.available) }}
                        </div>
                    </div>
                </div>

            </div>

            <!-- Transactions List -->
            <div v-if="transactions.length > 0" class="space-y-1">
                <template v-for="group in groupedTransactions" :key="group.date">
                    <!-- Date Header -->
                    <div class="text-xs font-semibold uppercase tracking-wide text-subtle px-1 pt-3 pb-1">
                        {{ group.label }}
                    </div>

                    <!-- Transaction Cards for this date -->
                    <Link
                        v-for="transaction in group.transactions"
                        :key="transaction.id"
                        :href="route('transactions.edit', transaction.id)"
                        class="block bg-surface rounded-card p-3 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <div class="font-medium text-body truncate">
                                    {{ transaction.payee }}
                                    <span v-if="transaction.is_split" class="text-xs text-primary ml-1">(split)</span>
                                </div>
                                <div class="text-xs text-subtle mt-0.5">
                                    {{ transaction.account }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                                <div
                                    class="font-medium"
                                    :class="getAmountColor(transaction.amount, transaction.type)"
                                >
                                    {{ formatCurrency(Math.abs(transaction.amount)) }}
                                </div>
                                <!-- Cleared Dot -->
                                <button
                                    @click.prevent.stop="toggleCleared(transaction)"
                                    class="flex-shrink-0"
                                >
                                    <div
                                        v-if="transaction.cleared"
                                        class="w-2 h-2 rounded-full bg-success"
                                    ></div>
                                    <div
                                        v-else
                                        class="w-2 h-2 rounded-full border-[1.5px] border-subtle"
                                    ></div>
                                </button>
                            </div>
                        </div>
                    </Link>
                </template>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-surface rounded-card p-8 text-center"
            >
                <div class="mb-4 flex justify-center">
                    <svg class="w-10 h-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-body mb-2">No transactions yet</h3>
                <p class="text-subtle mb-4">
                    No spending in this category for {{ formatMonth(month) }}.
                </p>
                <Link
                    :href="route('transactions.create')"
                    class="inline-flex items-center px-4 py-2 bg-primary text-body rounded-card font-medium hover:bg-primary/90 transition-colors"
                >
                    Add Transaction
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
