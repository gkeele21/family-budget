<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    accounts: Array,
});

const showAddModal = ref(false);

const form = useForm({
    name: '',
    type: 'checking',
    starting_balance: '',
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

const accountTypes = [
    { value: 'checking', label: 'Checking' },
    { value: 'savings', label: 'Savings' },
    { value: 'credit_card', label: 'Credit Card' },
    { value: 'cash', label: 'Cash' },
];

const submit = () => {
    form.post(route('accounts.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Accounts" />

    <AppLayout>
        <template #title>Accounts</template>
        <template #header-left>
            <Link :href="route('settings.index')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <div class="p-4 space-y-4">
            <!-- Account List -->
            <div class="bg-budget-card rounded-card divide-y divide-gray-100">
                <Link
                    v-for="account in accounts"
                    :key="account.id"
                    :href="route('accounts.edit', account.id)"
                    class="flex items-center justify-between p-4 hover:bg-gray-50"
                    :class="{ 'opacity-50': account.is_closed }"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ getAccountIcon(account.type) }}</span>
                        <div>
                            <div class="font-medium text-budget-text">
                                {{ account.name }}
                                <span v-if="account.is_closed" class="text-xs text-budget-text-secondary">(Closed)</span>
                            </div>
                            <div class="text-sm text-budget-text-secondary capitalize">
                                {{ account.type.replace('_', ' ') }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-mono font-semibold text-budget-text">
                            {{ formatCurrency(account.balance) }}
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Add Account Button -->
            <button
                @click="showAddModal = true"
                class="w-full py-4 border-2 border-dashed border-budget-primary text-budget-primary rounded-card font-medium hover:bg-budget-primary-bg transition-colors"
            >
                + Add Account
            </button>
        </div>

        <!-- Add Account Modal -->
        <div
            v-if="showAddModal"
            class="fixed inset-0 bg-black/50 flex items-end justify-center z-50"
            @click.self="showAddModal = false"
        >
            <div class="bg-white rounded-t-2xl w-full max-w-md p-6 pb-8">
                <h3 class="text-lg font-semibold text-budget-text mb-4">Add Account</h3>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm text-budget-text-secondary mb-1">Account Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g., Main Checking"
                            class="w-full px-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm text-budget-text-secondary mb-1">Account Type</label>
                        <select
                            v-model="form.type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                        >
                            <option v-for="type in accountTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-budget-text-secondary mb-1">Starting Balance</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-budget-text-secondary">$</span>
                            <input
                                v-model="form.starting_balance"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                                required
                            />
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="flex-1 py-3 border border-gray-300 rounded-card font-medium"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-3 bg-budget-primary text-white rounded-card font-medium disabled:opacity-50"
                        >
                            {{ form.processing ? 'Adding...' : 'Add Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
