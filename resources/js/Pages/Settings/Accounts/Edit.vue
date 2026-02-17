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
    icon: props.account.icon || '',
    starting_balance: parseFloat(props.account.starting_balance).toFixed(2),
    is_closed: props.account.is_closed,
});

const showDeleteConfirm = ref(false);

const accountTypes = [
    { value: 'bank', label: 'Bank', icon: '🏦' },
    { value: 'cash', label: 'Cash', icon: '💵' },
    { value: 'credit', label: 'Credit', icon: '💳' },
];

const typeDescriptions = {
    bank: 'Checking, savings, and other bank accounts.',
    cash: 'Auto-cleared transactions. e.g. Wallet, Venmo, PayPal, gift cards.',
    credit: 'Credit cards and store credit. e.g. Visa, Target Card, Kohl\'s.',
};

const accountEmojiGrid = [
    { emoji: '🏦', label: 'Bank' },
    { emoji: '💰', label: 'Savings' },
    { emoji: '💳', label: 'Credit Card' },
    { emoji: '💵', label: 'Cash' },
    { emoji: '📲', label: 'Mobile Pay' },
    { emoji: '🎓', label: 'Student Loan' },
    { emoji: '🏠', label: 'Mortgage' },
    { emoji: '🛍️', label: 'Store Card' },
    { emoji: '🎁', label: 'Gift Card' },
    { emoji: '💎', label: 'Investment' },
    { emoji: '🔗', label: 'Linked' },
    { emoji: '🌐', label: 'Online' },
];

const selectAccountIcon = (item) => {
    if (form.icon === item.emoji) {
        form.name = item.label;
        return;
    }
    form.icon = item.emoji;
};

const selectType = (type) => {
    form.type = type.value;
};

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
                    placeholder="e.g., Checking Account"
                    variant="subtle"
                    :border-bottom="false"
                    required
                />
            </div>

            <!-- Account Type -->
            <div class="bg-surface rounded-card p-4">
                <label class="block text-sm text-subtle mb-2">Account Type</label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="type in accountTypes"
                        :key="type.value"
                        type="button"
                        @click="selectType(type)"
                        :class="[
                            'flex items-center justify-center p-3 rounded-xl border-2 transition-colors font-semibold text-sm',
                            form.type === type.value
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-border bg-surface text-subtle'
                        ]"
                    >
                        {{ type.label }}
                    </button>
                </div>
                <p class="text-xs text-subtle mt-2">
                    {{ typeDescriptions[form.type] }}
                </p>
            </div>

            <!-- Icon Picker -->
            <div class="bg-surface rounded-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm text-subtle">Icon</label>
                    <span class="text-2xl">{{ form.icon || '💳' }}</span>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <button
                        v-for="item in accountEmojiGrid"
                        :key="item.emoji"
                        type="button"
                        @click="selectAccountIcon(item)"
                        :class="[
                            'flex flex-col items-center gap-0.5 py-1.5 rounded-lg transition-colors',
                            form.icon === item.emoji
                                ? 'bg-primary/20 ring-2 ring-primary'
                                : 'bg-surface-overlay hover:bg-border-strong'
                        ]"
                    >
                        <span class="text-xl">{{ item.emoji }}</span>
                        <span class="text-[10px] text-muted leading-tight">{{ item.label }}</span>
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
