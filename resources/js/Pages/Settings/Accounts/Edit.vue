<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    account: Object,
});

const form = useForm({
    name: props.account.name,
    type: props.account.type,
    starting_balance: props.account.starting_balance,
    is_closed: props.account.is_closed,
});

const showDeleteConfirm = ref(false);

const accountTypes = [
    { value: 'checking', label: 'Checking' },
    { value: 'savings', label: 'Savings' },
    { value: 'credit_card', label: 'Credit Card' },
    { value: 'cash', label: 'Cash' },
];

const submit = () => {
    form.put(route('accounts.update', props.account.id));
};

const deleteAccount = () => {
    router.delete(route('accounts.destroy', props.account.id));
};
</script>

<template>
    <Head title="Edit Account" />

    <AppLayout>
        <template #title>Edit Account</template>
        <template #header-left>
            <Link :href="route('settings.accounts')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <form @submit.prevent="submit" class="p-4 space-y-4">
            <div class="bg-budget-card rounded-card p-4">
                <label class="block text-sm text-budget-text-secondary mb-1">Account Name</label>
                <input
                    v-model="form.name"
                    type="text"
                    class="w-full bg-transparent text-lg focus:outline-none"
                    required
                />
            </div>

            <div class="bg-budget-card rounded-card p-4">
                <label class="block text-sm text-budget-text-secondary mb-1">Account Type</label>
                <select
                    v-model="form.type"
                    class="w-full bg-transparent text-lg focus:outline-none"
                >
                    <option v-for="type in accountTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
            </div>

            <div class="bg-budget-card rounded-card p-4">
                <label class="block text-sm text-budget-text-secondary mb-1">Starting Balance</label>
                <div class="flex items-center">
                    <span class="text-lg mr-2 text-budget-text-secondary">$</span>
                    <input
                        v-model="form.starting_balance"
                        type="number"
                        step="0.01"
                        class="w-full bg-transparent text-lg focus:outline-none"
                        required
                    />
                </div>
            </div>

            <div class="bg-budget-card rounded-card p-4 flex items-center justify-between">
                <div>
                    <span class="text-budget-text">Close Account</span>
                    <p class="text-sm text-budget-text-secondary">Hidden from main views but keeps history</p>
                </div>
                <button
                    type="button"
                    @click="form.is_closed = !form.is_closed"
                    :class="[
                        'w-12 h-7 rounded-full transition-colors relative',
                        form.is_closed ? 'bg-budget-expense' : 'bg-gray-300'
                    ]"
                >
                    <span
                        :class="[
                            'absolute top-1 w-5 h-5 bg-white rounded-full transition-transform shadow',
                            form.is_closed ? 'translate-x-6' : 'translate-x-1'
                        ]"
                    ></span>
                </button>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-4 bg-gradient-to-r from-budget-primary to-budget-primary-light text-white font-semibold rounded-card hover:shadow-lg transition-shadow disabled:opacity-50"
            >
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>

            <button
                type="button"
                @click="showDeleteConfirm = true"
                class="w-full py-4 text-budget-expense font-medium"
            >
                Delete Account
            </button>
        </form>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteConfirm"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="showDeleteConfirm = false"
        >
            <div class="bg-white rounded-card p-6 max-w-sm w-full">
                <h3 class="text-lg font-semibold text-budget-text mb-2">Delete Account?</h3>
                <p class="text-budget-text-secondary mb-4">
                    This will delete all transactions in this account. This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button
                        @click="showDeleteConfirm = false"
                        class="flex-1 py-3 border border-gray-300 rounded-card font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteAccount"
                        class="flex-1 py-3 bg-budget-expense text-white rounded-card font-medium"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
