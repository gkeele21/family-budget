<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FAB from '@/Components/Domain/FAB.vue';
import FilterChip from '@/Components/Base/FilterChip.vue';
import SwipeableRow from '@/Components/Base/SwipeableRow.vue';
import SegmentedControl from '@/Components/Form/SegmentedControl.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    transactions: Object,
    accounts: Array,
    currentAccountId: Number,
    searchQuery: String,
    startDate: String,
    endDate: String,
    clearedFilter: String,
    recurring: Array,
});

// Search state
const showSearch = ref(false);
const showFilters = ref(false);
const localSearchQuery = ref(props.searchQuery || '');
const localStartDate = ref(props.startDate || '');
const localEndDate = ref(props.endDate || '');
const localClearedFilter = ref(props.clearedFilter || 'all');
const localRecurringFilter = ref('all');
const searchInputRef = ref(null);

// View mode toggle: 'all' or 'recurring'
const viewMode = ref('all');

// Build params for router calls
const buildParams = () => {
    const params = {};
    if (props.currentAccountId) params.account = props.currentAccountId;
    if (localSearchQuery.value) params.search = localSearchQuery.value;
    if (localStartDate.value) params.start_date = localStartDate.value;
    if (localEndDate.value) params.end_date = localEndDate.value;
    if (localClearedFilter.value && localClearedFilter.value !== 'all') {
        params.cleared = localClearedFilter.value;
    }
    return params;
};

// Watch for search query changes with debounce
let searchTimeout = null;
watch(localSearchQuery, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('transactions.index'), buildParams(), {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
});

const toggleSearch = () => {
    showSearch.value = !showSearch.value;
    if (showSearch.value) {
        setTimeout(() => {
            searchInputRef.value?.focus();
        }, 100);
    } else {
        // Clear search when closing
        if (localSearchQuery.value) {
            localSearchQuery.value = '';
        }
    }
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const applyFilters = () => {
    router.get(route('transactions.index'), buildParams(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    localStartDate.value = '';
    localEndDate.value = '';
    localClearedFilter.value = 'all';
    localRecurringFilter.value = 'all';
    router.get(route('transactions.index'), buildParams(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const hasActiveFilters = computed(() => {
    return localStartDate.value || localEndDate.value ||
           (localClearedFilter.value && localClearedFilter.value !== 'all');
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
    const params = buildParams();
    if (accountId) {
        params.account = accountId;
    } else {
        delete params.account;
    }

    router.get(route('transactions.index'), params, {
        preserveState: true,
    });
};

const toggleCleared = (transaction) => {
    const newClearedState = !transaction.cleared;
    router.post(route('transactions.toggle-cleared', transaction.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showClearedToast(transaction, newClearedState);
        },
    });
};

const getAmountColor = (type) => {
    if (type === 'expense') return 'text-expense';
    if (type === 'income') return 'text-income';
    return 'text-transfer';
};

const transactionCount = computed(() => {
    return Object.values(props.transactions).reduce((sum, day) => sum + day.length, 0);
});

// Toast state for cleared notifications
const toast = ref({ show: false, message: '', payee: '', transactionId: null, wasCleared: false });
let toastTimeout = null;

const showClearedToast = (transaction, newClearedState) => {
    // Clear any existing timeout
    if (toastTimeout) clearTimeout(toastTimeout);

    toast.value = {
        show: true,
        message: newClearedState ? 'cleared' : 'uncleared',
        payee: transaction.payee,
        transactionId: transaction.id,
        wasCleared: newClearedState,
    };

    toastTimeout = setTimeout(() => {
        toast.value.show = false;
    }, 4000);
};

const undoClear = () => {
    if (toast.value.transactionId) {
        router.post(route('transactions.toggle-cleared', toast.value.transactionId), {}, {
            preserveScroll: true,
        });
        toast.value.show = false;
    }
};

// Swipe-to-delete refs
const swipeRefs = ref({});

const closeOtherSwipes = (exceptId) => {
    Object.entries(swipeRefs.value).forEach(([id, ref]) => {
        if (parseInt(id) !== exceptId && ref?.reset) {
            ref.reset();
        }
    });
};

const deleteTransaction = (transaction) => {
    router.delete(route('transactions.destroy', transaction.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (toastTimeout) clearTimeout(toastTimeout);
            toast.value = {
                show: true,
                message: 'deleted',
                payee: transaction.payee,
                transactionId: null,
                wasCleared: false,
            };
            toastTimeout = setTimeout(() => {
                toast.value.show = false;
            }, 3000);
        },
    });
};

const activeFilterDescription = computed(() => {
    const parts = [];
    if (localStartDate.value && localEndDate.value) {
        parts.push(`${localStartDate.value} to ${localEndDate.value}`);
    } else if (localStartDate.value) {
        parts.push(`from ${localStartDate.value}`);
    } else if (localEndDate.value) {
        parts.push(`until ${localEndDate.value}`);
    }
    if (localClearedFilter.value === 'cleared') {
        parts.push('cleared only');
    } else if (localClearedFilter.value === 'uncleared') {
        parts.push('uncleared only');
    }
    return parts.length > 0 ? parts.join(', ') : '';
});

// Recurring view grouping
const frequencyLabels = {
    daily: 'Daily',
    weekly: 'Weekly',
    biweekly: 'Every 2 weeks',
    monthly: 'Monthly',
    yearly: 'Yearly',
};

const frequencyOrder = ['daily', 'weekly', 'biweekly', 'monthly', 'yearly'];

const groupedRecurring = computed(() => {
    if (!props.recurring) return [];
    const groups = {};
    for (const freq of frequencyOrder) groups[freq] = [];
    for (const item of props.recurring) {
        if (groups[item.frequency]) groups[item.frequency].push(item);
    }
    return frequencyOrder
        .filter(freq => groups[freq].length > 0)
        .map(freq => ({
            frequency: freq,
            label: frequencyLabels[freq],
            items: groups[freq],
        }));
});

const formatNextDate = (dateStr, frequency) => {
    const date = new Date(dateStr + 'T00:00:00');
    if (frequency === 'monthly') {
        const day = date.getDate();
        const suffix = day === 1 || day === 21 || day === 31 ? 'st'
            : day === 2 || day === 22 ? 'nd'
            : day === 3 || day === 23 ? 'rd' : 'th';
        return `${day}${suffix}`;
    } else if (frequency === 'weekly' || frequency === 'biweekly') {
        return date.toLocaleDateString('en-US', { weekday: 'short' });
    } else if (frequency === 'yearly') {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Transactions" />

    <AppLayout>
        <template #title>Transactions</template>
        <template #header-right>
            <button
                @click="toggleFilters"
                class="p-2 hover:bg-surface/10 rounded-full transition-colors relative"
                :class="{ 'bg-surface/20': showFilters }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span v-if="hasActiveFilters" class="absolute top-1 right-1 w-2 h-2 bg-income rounded-full"></span>
            </button>
            <button
                @click="toggleSearch"
                class="p-2 hover:bg-surface/10 rounded-full transition-colors"
                :class="{ 'bg-surface/20': showSearch }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </template>

        <!-- Cleared Toast Notification -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform translate-y-full opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-full opacity-0"
            >
                <div
                    v-if="toast.show"
                    class="fixed bottom-24 left-4 right-4 z-50 bg-body text-inverse rounded-card px-4 py-3 shadow-lg flex items-center justify-between"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-income">✓</span>
                        <span>{{ toast.payee }} {{ toast.message }}</span>
                    </div>
                    <button
                        @click="undoClear"
                        class="text-secondary font-medium hover:underline"
                    >
                        Undo
                    </button>
                </div>
            </Transition>
        </Teleport>

        <div class="p-4 space-y-4">
            <!-- All | Recurring Toggle -->
            <SegmentedControl
                v-model="viewMode"
                :options="[
                    { value: 'all', label: 'All' },
                    { value: 'recurring', label: 'Recurring' },
                ]"
                size="sm"
            />

            <!-- ALL TRANSACTIONS VIEW -->
            <template v-if="viewMode === 'all'">
                <!-- Search Bar -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform -translate-y-2 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform -translate-y-2 opacity-0"
                >
                    <div v-if="showSearch" class="relative">
                        <input
                            ref="searchInputRef"
                            v-model="localSearchQuery"
                            type="text"
                            placeholder="Search payee, memo, amount..."
                            class="w-full px-4 py-3 pl-10 bg-surface rounded-card text-body placeholder-subtle focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 text-subtle"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <button
                            v-if="localSearchQuery"
                            @click="localSearchQuery = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 hover:bg-surface-secondary rounded-full"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </Transition>

                <!-- Filters Panel -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform -translate-y-2 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform -translate-y-2 opacity-0"
                >
                    <div v-if="showFilters" class="bg-surface rounded-card overflow-hidden">
                        <!-- Date Range -->
                        <div class="divide-y divide-border">
                            <div class="flex items-center justify-between px-4 py-3.5">
                                <span class="text-sm text-subtle">From</span>
                                <input
                                    type="date"
                                    v-model="localStartDate"
                                    :max="localEndDate || undefined"
                                    :class="[
                                        'bg-transparent text-right text-sm font-medium focus:outline-none',
                                        localStartDate ? 'text-primary' : 'text-subtle'
                                    ]"
                                />
                            </div>
                            <div class="flex items-center justify-between px-4 py-3.5">
                                <span class="text-sm text-subtle">To</span>
                                <input
                                    type="date"
                                    v-model="localEndDate"
                                    :min="localStartDate || undefined"
                                    :class="[
                                        'bg-transparent text-right text-sm font-medium focus:outline-none',
                                        localEndDate ? 'text-primary' : 'text-subtle'
                                    ]"
                                />
                            </div>
                        </div>

                        <!-- Cleared Status -->
                        <div class="px-4 py-3 border-t border-border">
                            <label class="block text-xs text-subtle mb-2">Status</label>
                            <div class="flex gap-2">
                                <button
                                    @click="localClearedFilter = 'all'"
                                    :class="[
                                        'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                                        localClearedFilter === 'all'
                                            ? 'bg-primary text-body'
                                            : 'bg-surface-secondary text-subtle'
                                    ]"
                                >
                                    All
                                </button>
                                <button
                                    @click="localClearedFilter = 'cleared'"
                                    :class="[
                                        'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                                        localClearedFilter === 'cleared'
                                            ? 'bg-primary text-body'
                                            : 'bg-surface-secondary text-subtle'
                                    ]"
                                >
                                    Cleared
                                </button>
                                <button
                                    @click="localClearedFilter = 'uncleared'"
                                    :class="[
                                        'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                                        localClearedFilter === 'uncleared'
                                            ? 'bg-primary text-body'
                                            : 'bg-surface-secondary text-subtle'
                                    ]"
                                >
                                    Uncleared
                                </button>
                            </div>
                        </div>

                        <!-- Filter Actions -->
                        <div class="flex gap-2 px-4 py-3 border-t border-border">
                            <button
                                @click="applyFilters"
                                class="flex-1 py-2.5 bg-primary text-body rounded-xl text-sm font-semibold"
                            >
                                Apply Filters
                            </button>
                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="px-4 py-2.5 text-subtle text-sm font-medium hover:bg-surface-secondary rounded-xl"
                            >
                                Clear
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Active Filters Display -->
                <div v-if="hasActiveFilters && !showFilters" class="text-xs text-subtle px-1">
                    Filtered: {{ activeFilterDescription }}
                    <button @click="clearFilters" class="text-primary ml-2">Clear</button>
                </div>

                <!-- Account Filter -->
                <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4">
                    <FilterChip
                        :active="!currentAccountId"
                        @click="filterByAccount(null)"
                    >
                        All Accounts
                    </FilterChip>
                    <FilterChip
                        v-for="account in accounts"
                        :key="account.id"
                        :active="currentAccountId === account.id"
                        @click="filterByAccount(account.id)"
                    >
                        {{ account.name }}
                    </FilterChip>
                </div>

                <!-- Results Count (when searching or filtering) -->
                <div v-if="searchQuery || hasActiveFilters" class="text-sm text-subtle px-1">
                    {{ transactionCount }} result{{ transactionCount !== 1 ? 's' : '' }}
                    <template v-if="searchQuery"> for "{{ searchQuery }}"</template>
                </div>

                <!-- Transactions by Date -->
                <div v-for="(dayTransactions, date) in transactions" :key="date" class="space-y-2">
                    <h2 class="text-sm font-semibold text-subtle px-1">
                        {{ formatDate(date) }}
                    </h2>

                    <div class="space-y-1.5">
                        <SwipeableRow
                            v-for="transaction in dayTransactions"
                            :key="transaction.id"
                            :ref="el => { if (el) swipeRefs[transaction.id] = el }"
                            @action="deleteTransaction(transaction)"
                            @swipe-open="closeOtherSwipes(transaction.id)"
                        >
                            <Link
                                :href="route('transactions.edit', transaction.id)"
                                class="block bg-surface rounded-card p-3 shadow-sm"
                            >
                                <div class="flex items-start justify-between">
                                    <!-- Left side: Payee + Category/Splits -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-medium text-body truncate">
                                                <template v-if="transaction.type === 'transfer'">
                                                    <span class="text-transfer">↔</span>
                                                    {{ transaction.payee }}
                                                </template>
                                                <template v-else>{{ transaction.payee }}</template>
                                            </span>
                                            <span v-if="transaction.recurring_id" class="text-primary text-xs">↻</span>
                                        </div>
                                        <!-- Split categories with amounts -->
                                        <div v-if="transaction.is_split && transaction.splits" class="mt-0.5 grid grid-cols-[auto_auto] gap-x-1 gap-y-0.5 text-xs text-subtle w-fit">
                                            <template v-for="split in transaction.splits" :key="split.id">
                                                <span>{{ split.category }}:</span>
                                                <span>{{ formatCurrency(Math.abs(split.amount)) }}</span>
                                            </template>
                                        </div>
                                        <!-- Single category -->
                                        <div v-else-if="transaction.category" class="text-xs text-subtle mt-0.5 truncate">
                                            {{ transaction.category }}
                                        </div>
                                    </div>

                                    <!-- Right side: Amount + Account + Cleared dot -->
                                    <div class="flex items-start gap-2 flex-shrink-0 ml-3">
                                        <div class="text-right">
                                            <div :class="['font-medium', getAmountColor(transaction.type)]">
                                                {{ transaction.type === 'transfer' ? formatCurrency(Math.abs(transaction.amount)) : formatCurrency(transaction.amount) }}
                                            </div>
                                            <div v-if="!currentAccountId && transaction.type !== 'transfer'" class="text-xs text-subtle mt-0.5">
                                                {{ transaction.account }}
                                            </div>
                                        </div>
                                        <!-- Cleared Dot -->
                                        <button
                                            @click.prevent.stop="toggleCleared(transaction)"
                                            class="flex-shrink-0 p-1 mt-0.5"
                                        >
                                            <div
                                                v-if="transaction.cleared"
                                                class="w-2 h-2 rounded-full bg-income"
                                            ></div>
                                            <div
                                                v-else
                                                class="w-2 h-2 rounded-full border-[1.5px] border-subtle"
                                            ></div>
                                        </button>
                                    </div>
                                </div>
                            </Link>
                        </SwipeableRow>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="Object.keys(transactions).length === 0"
                    class="text-center py-12"
                >
                    <div class="text-4xl mb-4">{{ searchQuery || hasActiveFilters ? '🔍' : '📝' }}</div>
                    <h3 class="text-lg font-medium text-body mb-2">
                        {{ searchQuery || hasActiveFilters ? 'No results found' : 'No transactions yet' }}
                    </h3>
                    <p class="text-subtle mb-4">
                        {{ searchQuery || hasActiveFilters ? 'Try different search terms or filters.' : 'Tap the + button to add your first transaction.' }}
                    </p>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="text-primary font-medium"
                    >
                        Clear Filters
                    </button>
                </div>
            </template>

            <!-- RECURRING VIEW -->
            <template v-else>
                <template v-if="groupedRecurring.length > 0">
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
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-body truncate">
                                            {{ item.payee }}
                                            <span class="text-subtle font-normal">({{ formatNextDate(item.next_date, item.frequency) }})</span>
                                        </div>
                                        <div v-if="item.category" class="text-xs text-subtle mt-0.5 truncate">
                                            {{ item.category }}
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ml-3 text-right">
                                        <div
                                            class="font-medium"
                                            :class="item.type === 'expense' ? 'text-expense' : 'text-income'"
                                        >
                                            {{ formatCurrency(Math.abs(item.amount)) }}
                                        </div>
                                        <div class="text-xs text-subtle mt-0.5">{{ item.account }}</div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </template>
                <div v-else class="text-center py-12">
                    <div class="text-4xl mb-4">🔄</div>
                    <h3 class="text-lg font-medium text-body mb-2">No recurring transactions</h3>
                    <p class="text-subtle">Set up recurring bills and income to auto-create transactions.</p>
                </div>
            </template>
        </div>

        <template #fab>
            <FAB :href="viewMode === 'all' ? route('transactions.create') : route('recurring.create')" />
        </template>
    </AppLayout>
</template>
