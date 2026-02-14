<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/Components/Base/Button.vue';

const props = defineProps({
    recurring: Array,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Math.abs(amount));
};

const formatNextDate = (dateStr, frequency) => {
    const date = new Date(dateStr + 'T00:00:00');

    if (frequency === 'monthly') {
        // Show day of month (e.g., "1st", "15th")
        const day = date.getDate();
        const suffix = day === 1 || day === 21 || day === 31 ? 'st'
            : day === 2 || day === 22 ? 'nd'
            : day === 3 || day === 23 ? 'rd'
            : 'th';
        return `${day}${suffix}`;
    } else if (frequency === 'weekly' || frequency === 'biweekly') {
        // Show day of week (e.g., "Fri")
        return date.toLocaleDateString('en-US', { weekday: 'short' });
    } else if (frequency === 'yearly') {
        // Show month + day (e.g., "Mar 15")
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    // Default: full date
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const frequencyLabels = {
    daily: 'Daily',
    weekly: 'Weekly',
    biweekly: 'Every 2 weeks',
    monthly: 'Monthly',
    yearly: 'Yearly',
};

const frequencyOrder = ['daily', 'weekly', 'biweekly', 'monthly', 'yearly'];

// Group recurring transactions by frequency
const groupedRecurring = computed(() => {
    const groups = {};

    // Initialize groups in order
    for (const freq of frequencyOrder) {
        groups[freq] = [];
    }

    // Sort items into groups
    for (const item of props.recurring) {
        if (groups[item.frequency]) {
            groups[item.frequency].push(item);
        }
    }

    // Filter out empty groups and return as array of [frequency, items]
    return frequencyOrder
        .filter(freq => groups[freq].length > 0)
        .map(freq => ({
            frequency: freq,
            label: frequencyLabels[freq],
            items: groups[freq],
        }));
});

const toggleActive = (id) => {
    router.post(route('recurring.toggle', id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Recurring Transactions" />

    <AppLayout>
        <template #title>Recurring</template>
        <template #header-left>
            <Link :href="route('settings.index')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <div class="p-4 space-y-6">
            <!-- Grouped Recurring List -->
            <template v-if="recurring.length > 0">
                <div v-for="group in groupedRecurring" :key="group.frequency" class="space-y-2">
                    <h2 class="text-sm font-semibold text-subtle uppercase tracking-wide px-1">
                        {{ group.label }}
                    </h2>
                    <div class="space-y-1.5">
                        <Link
                            v-for="item in group.items"
                            :key="item.id"
                            :href="route('recurring.edit', item.id)"
                            class="block bg-surface rounded-card p-3 shadow-sm"
                            :class="{ 'opacity-50': !item.is_active }"
                        >
                            <div class="flex items-start justify-between">
                                <!-- Left: Payee (next date), Category -->
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-body truncate">
                                        {{ item.payee }}
                                        <span class="text-subtle font-normal">({{ formatNextDate(item.next_date, item.frequency) }})</span>
                                    </div>
                                    <div v-if="item.category" class="text-xs text-subtle mt-0.5 truncate">
                                        {{ item.category }}
                                    </div>
                                </div>
                                <!-- Right: Amount, Account -->
                                <div class="flex-shrink-0 ml-3 text-right">
                                    <div
                                        class="font-medium"
                                        :class="item.type === 'expense' ? 'text-danger' : 'text-success'"
                                    >
                                        {{ formatCurrency(item.amount) }}
                                    </div>
                                    <div class="text-xs text-subtle mt-0.5">
                                        {{ item.account }}
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <div class="mb-4 flex justify-center">
                    <svg class="w-10 h-10 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-body mb-2">No recurring transactions</h3>
                <p class="text-subtle mb-4">
                    Set up recurring bills and income to auto-create transactions.
                </p>
            </div>

            <!-- Add Button -->
            <Button
                variant="outline"
                full-width
                :href="route('recurring.create')"
            >
                + Add Recurring Transaction
            </Button>
        </div>
    </AppLayout>
</template>
