<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AccountCard from '@/Components/Domain/AccountCard.vue';
import FAB from '@/Components/Domain/FAB.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    accounts: {
        type: Object,
        required: true,
    },
    budgetName: {
        type: String,
        required: true,
    },
});

const accountTypeLabels = {
    cash: 'Cash',
    checking: 'Checking',
    savings: 'Savings',
    credit_card: 'Credit Cards',
};

const accountTypeOrder = ['cash', 'checking', 'savings', 'credit_card'];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <template #title>{{ budgetName }}</template>

        <div class="p-4 space-y-4">
            <h1 class="text-lg font-semibold text-body">Accounts</h1>

            <!-- Accounts by Type -->
            <div v-for="type in accountTypeOrder" :key="type">
                <div v-if="accounts[type] && accounts[type].length > 0" class="space-y-2">
                    <h2 class="text-sm font-semibold text-warning uppercase tracking-wide px-1">
                        {{ accountTypeLabels[type] }}
                    </h2>
                    <div class="space-y-2">
                        <AccountCard
                            v-for="account in accounts[type]"
                            :key="account.id"
                            :account="account"
                        />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="Object.keys(accounts).length === 0"
                class="text-center py-12"
            >
                <div class="mb-4 flex justify-center">
                    <svg class="w-10 h-10 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-body mb-2">No accounts yet</h3>
                <p class="text-subtle">
                    Tap the + button to add your first account.
                </p>
            </div>
        </div>

        <template #fab>
            <FAB :href="route('settings.accounts', { add: 1 })" />
        </template>
    </AppLayout>
</template>
