<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    account: {
        type: Object,
        required: true,
    },
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const getAccountIcon = (type) => {
    const icons = {
        checking: '🏦',
        savings: '💰',
        credit_card: '💳',
        cash: '💵',
    };
    return icons[type] || '💳';
};
</script>

<template>
    <Link
        :href="route('transactions.index', { account: account.id })"
        class="flex items-center justify-between p-4 bg-budget-card rounded-card hover:bg-gray-50 transition-colors"
    >
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ getAccountIcon(account.type) }}</span>
            <div>
                <div class="font-medium text-budget-text">{{ account.name }}</div>
                <div class="text-sm text-budget-text-secondary">
                    {{ account.type.replace('_', ' ') }}
                </div>
            </div>
        </div>
        <div class="text-right">
            <div
                class="font-mono font-semibold"
                :class="account.balance >= 0 ? 'text-budget-text' : 'text-budget-expense'"
            >
                {{ formatCurrency(account.balance) }}
            </div>
            <div v-if="account.cleared_balance !== account.balance" class="text-xs text-budget-text-secondary">
                Cleared: {{ formatCurrency(account.cleared_balance) }}
            </div>
        </div>
    </Link>
</template>
