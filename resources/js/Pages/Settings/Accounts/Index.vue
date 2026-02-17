<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, nextTick } from 'vue';
import TextField from '@/Components/Form/TextField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import Button from '@/Components/Base/Button.vue';
import Modal from '@/Components/Base/Modal.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    accounts: Array,
});

const showAddModal = ref(false);
const nameInput = ref(null);
const orderedAccounts = ref([...props.accounts]);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.has('add')) {
        showAddModal.value = true;
    }
});

watch(() => props.accounts, (newAccounts) => {
    orderedAccounts.value = [...newAccounts];
});

const saveOrder = () => {
    const ids = orderedAccounts.value.map(a => a.id);
    router.post(route('accounts.reorder'), { ids }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const form = useForm({
    name: '',
    type: 'bank',
    icon: '',
    starting_balance: '',
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const defaultTypeIcons = {
    bank: '🏦',
    cash: '💵',
    credit: '💳',
};

const typeLabels = {
    bank: 'Bank',
    cash: 'Cash',
    credit: 'Credit Card',
};

const getAccountIcon = (account) => {
    return account.icon || defaultTypeIcons[account.type] || '💳';
};

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
    if (!form.name || accountTypes.some(t => t.label === form.name)) {
        form.name = item.label;
    }
};

const selectType = async (type) => {
    form.type = type.value;
    const userHasTypedName = form.name && !accountTypes.some(t => t.label === form.name);
    if (!userHasTypedName) {
        form.name = type.label;
    }
    await nextTick();
    nameInput.value?.$el?.querySelector('input')?.focus();
    nameInput.value?.$el?.querySelector('input')?.select();
};

const submit = () => {
    form.post(route('accounts.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const closeModal = () => {
    showAddModal.value = false;
    form.reset();
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
            <div class="bg-surface rounded-card divide-y divide-border">
                <draggable
                    v-model="orderedAccounts"
                    item-key="id"
                    handle=".drag-handle"
                    ghost-class="opacity-30"
                    :animation="200"
                    tag="div"
                    class="divide-y divide-border"
                    @end="saveOrder"
                >
                    <template #item="{ element: account }">
                        <div
                            class="flex items-center"
                            :class="{ 'opacity-50': account.is_closed }"
                        >
                            <div class="drag-handle cursor-grab active:cursor-grabbing p-4 pr-2 text-subtle">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                </svg>
                            </div>
                            <Link
                                :href="route('accounts.edit', account.id)"
                                class="flex items-center justify-between flex-1 py-4 pr-4 hover:bg-surface-overlay"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">{{ getAccountIcon(account) }}</span>
                                    <div>
                                        <div class="font-medium text-body">
                                            {{ account.name }}
                                            <span v-if="account.is_closed" class="text-xs text-subtle">(Closed)</span>
                                        </div>
                                        <div class="text-sm text-subtle capitalize">
                                            {{ typeLabels[account.type] || account.type }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-body">
                                        {{ formatCurrency(account.balance) }}
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </template>
                </draggable>
            </div>

            <!-- Add Account Button -->
            <Button
                variant="outline"
                full-width
                @click="showAddModal = true"
            >
                + Add Account
            </Button>
        </div>

        <!-- Add Account Modal -->
        <Modal :show="showAddModal" title="Add Account" @close="closeModal">
            <form @submit.prevent="submit">
                <!-- Account Type -->
                <div class="mx-3 mt-3">
                    <div class="text-xs font-semibold text-subtle uppercase tracking-wide mb-2 px-1">
                        Account Type
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="type in accountTypes"
                            :key="type.value"
                            type="button"
                            @click="selectType(type)"
                            :class="[
                                'flex items-center justify-center p-3 rounded-xl border-2 transition-colors bg-surface font-semibold text-sm',
                                form.type === type.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border text-subtle'
                            ]"
                        >
                            {{ type.label }}
                        </button>
                    </div>
                    <p class="text-xs text-subtle mt-2 px-1">
                        {{ typeDescriptions[form.type] }}
                    </p>
                </div>

                <!-- Fields Card -->
                <div class="mx-3 mt-3 bg-surface rounded-xl overflow-hidden">
                    <TextField
                        ref="nameInput"
                        v-model="form.name"
                        label="Account Name"
                        placeholder="e.g., Checking Account"
                        variant="subtle"
                        autocomplete="off"
                        required
                    />
                    <AmountField
                        v-model="form.starting_balance"
                        label="Starting Balance"
                        color="text-secondary"
                        placeholder="0.00"
                        :border-bottom="false"
                    />
                </div>

                <!-- Icon Picker -->
                <div class="mx-3 mt-3">
                    <div class="text-xs font-semibold text-subtle uppercase tracking-wide mb-2 px-1">
                        Icon
                    </div>
                    <div class="bg-surface rounded-xl p-3">
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
                </div>
            </form>

            <template #footer>
                <div class="flex gap-2">
                    <Button variant="secondary" @click="closeModal" class="flex-1">
                        Cancel
                    </Button>
                    <Button @click="submit" :loading="form.processing" class="flex-1">
                        Save
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
