<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import TextField from '@/Components/Form/TextField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import ToggleField from '@/Components/Form/ToggleField.vue';
import Button from '@/Components/Base/Button.vue';

const props = defineProps({
    account: Object,
});

const form = useForm({
    name: props.account.name,
    type: props.account.type,
    starting_balance: parseFloat(props.account.starting_balance).toFixed(2),
    is_closed: props.account.is_closed,
});

const showDeleteConfirm = ref(false);

const accountTypes = [
    { value: 'checking', label: 'Checking', icon: '🏦' },
    { value: 'savings', label: 'Savings', icon: '💰' },
    { value: 'credit_card', label: 'Credit Card', icon: '💳' },
    { value: 'cash', label: 'Cash', icon: '💵' },
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

        <form @submit.prevent="submit" class="p-4 pb-8 space-y-4">
            <!-- Account Name -->
            <div class="bg-surface rounded-card overflow-hidden">
                <TextField
                    v-model="form.name"
                    label="Account Name"
                    placeholder="e.g., Main Checking"
                    variant="subtle"
                    :border-bottom="false"
                    required
                />
            </div>

            <!-- Account Type -->
            <div class="bg-surface rounded-card p-4">
                <label class="block text-sm text-subtle mb-2">Account Type</label>
                <div class="grid grid-cols-4 gap-2">
                    <button
                        v-for="type in accountTypes"
                        :key="type.value"
                        type="button"
                        @click="form.type = type.value"
                        :class="[
                            'flex flex-col items-center p-3 rounded-xl border-2 transition-colors',
                            form.type === type.value
                                ? 'border-primary bg-primary/10'
                                : 'border-border bg-surface'
                        ]"
                    >
                        <span class="text-2xl mb-1">{{ type.icon }}</span>
                        <span
                            :class="[
                                'text-xs font-semibold',
                                form.type === type.value ? 'text-primary' : 'text-subtle'
                            ]"
                        >{{ type.label }}</span>
                    </button>
                </div>
            </div>

            <!-- Starting Balance -->
            <div class="bg-surface rounded-card overflow-hidden">
                <AmountField
                    v-model="form.starting_balance"
                    label="Starting Balance"
                    color="text-secondary"
                    :border-bottom="false"
                />
            </div>

            <!-- Close Account Toggle -->
            <div class="bg-surface rounded-card overflow-hidden">
                <ToggleField
                    v-model="form.is_closed"
                    label="Close Account"
                    on-label="Closed"
                    off-label="Active"
                    variant="switch"
                    :border-bottom="false"
                />
                <p class="px-4 pb-3 text-xs text-subtle">
                    Closed accounts are hidden from main views but keep their history.
                </p>
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                :loading="form.processing"
                full-width
                size="lg"
            >
                Save
            </Button>

            <!-- Delete Button -->
            <Button variant="ghost" class="w-full text-danger" @click="showDeleteConfirm = true">
                Delete Account
            </Button>
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
                        <h3 class="text-lg font-semibold text-body">Delete Account?</h3>
                        <p class="text-subtle">
                            This will delete all transactions in this account. This action cannot be undone.
                        </p>
                        <div class="flex gap-3">
                            <Button variant="secondary" @click="showDeleteConfirm = false" class="flex-1">
                                Cancel
                            </Button>
                            <Button variant="danger" @click="deleteAccount" class="flex-1">
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
