<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Toggle from '@/Components/Base/Toggle.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, reactive, nextTick, watch } from 'vue';

const props = defineProps({
    month: String,
    categoryGroups: Array,
    summary: Object,
    earliestMonth: String,
});

// Track budget amounts reactively for each category
const budgetAmounts = reactive({});

// Move money modal state
const showMoveMoneyModal = ref(false);
const moveMoneyTarget = ref(null); // The overspent category
const moveMoneyAmount = ref(0); // Total amount needed
const selectedSources = ref([]); // Array of { id, name, amount } for selected categories

// Toast state
const toast = ref({ show: false, message: '', type: 'success' });
const moveToast = ref({ show: false, amount: '', from: '', to: '', remaining: null });
let moveToastTimeout = null;

// Context menu state
const showContextMenu = ref(false);
const showBreakdown = ref(false);

// Confirmation modal state
const confirmModal = ref({ show: false, title: '', message: '', action: null });

const showConfirm = (title, message, action) => {
    showContextMenu.value = false;
    confirmModal.value = { show: true, title, message, action };
};

const executeConfirm = () => {
    if (confirmModal.value.action) confirmModal.value.action();
    confirmModal.value.show = false;
};

const cancelConfirm = () => {
    confirmModal.value.show = false;
};

// Track edited amounts (for green border visual feedback)
const editedAmounts = reactive({});

// Track which field is being edited (to show input vs formatted)
const editingField = ref(null);
const editingValue = ref('');

// Global toggle for showing category details (default/avg)
const showDetails = ref(false);

// Calculate group totals
const getGroupTotals = (group) => {
    let budgeted = 0;
    let spent = 0;
    // Handle both array and object (Laravel Collection) formats
    const categories = Array.isArray(group.categories) ? group.categories : Object.values(group.categories);
    categories.forEach(category => {
        budgeted += budgetAmounts[category.id] || 0;
        spent += category.spent || 0;
    });
    return {
        budgeted,
        spent,
        available: budgeted - spent,
    };
};

// Initialize budget amounts from props
const syncBudgetAmounts = () => {
    props.categoryGroups.forEach(group => {
        // Handle both array and object (Laravel Collection) formats
        const categories = Array.isArray(group.categories) ? group.categories : Object.values(group.categories);
        categories.forEach(category => {
            budgetAmounts[category.id] = category.budgeted;
        });
    });
};

// Initial sync
syncBudgetAmounts();

// Re-sync when props change (e.g., after move money)
watch(() => props.categoryGroups, syncBudgetAmounts, { deep: true });

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

// Format number without $ sign (for tight columns)
const formatNumber = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

const formatMonth = (monthStr) => {
    const [year, month] = monthStr.split('-');
    const date = new Date(year, month - 1, 1); // month is 0-indexed
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const shortMonthName = computed(() => {
    const [year, month] = props.month.split('-');
    const date = new Date(year, month - 1, 1);
    return date.toLocaleDateString('en-US', { month: 'short' });
});

const previousMonth = computed(() => {
    const [year, month] = props.month.split('-').map(Number);
    const date = new Date(year, month - 1, 1); // month is 0-indexed
    date.setMonth(date.getMonth() - 1);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
});

const canGoBack = computed(() => {
    return !props.earliestMonth || props.month > props.earliestMonth;
});

const nextMonth = computed(() => {
    const [year, month] = props.month.split('-').map(Number);
    const date = new Date(year, month - 1, 1); // month is 0-indexed
    date.setMonth(date.getMonth() + 1);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
});

const navigateMonth = (month) => {
    router.get(route('budget.index', { month }));
};

const onBudgetInput = (e) => {
    let value = e.target.value;
    // Strip $ and any non-numeric chars except decimal
    value = value.replace(/[^\d.]/g, '');
    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    // Limit decimal places to 2
    if (parts.length === 2 && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].slice(0, 2);
    }
    editingValue.value = value;
};

const saveAmount = (categoryId) => {
    editingField.value = null;
    const amount = parseFloat(editingValue.value) || 0;
    budgetAmounts[categoryId] = amount;
    router.put(route('budget.update', { month: props.month }), {
        budgets: [{ category_id: categoryId, amount: amount }],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Show green border briefly to indicate successful save
            editedAmounts[categoryId] = true;
            setTimeout(() => {
                editedAmounts[categoryId] = false;
            }, 2000);
        },
    });
};

const startEditing = (categoryId) => {
    editingField.value = categoryId;
    editingValue.value = (budgetAmounts[categoryId] || 0).toString();
    nextTick(() => {
        const input = document.querySelector(`input[type="text"][inputmode="decimal"]`);
        if (input) {
            input.focus();
            input.select();
        }
    });
};

const isOverspent = (available) => available < 0;

const getAvailable = (category) => {
    const budgeted = budgetAmounts[category.id] || 0;
    return budgeted - category.spent;
};

// Check if any budget amounts exist for the current month
const hasExistingBudgetAmounts = computed(() => {
    for (const group of props.categoryGroups) {
        for (const category of group.categories) {
            if (category.budgeted > 0) {
                return true;
            }
        }
    }
    return false;
});

// Copy last month's budget - check for existing amounts first
const initiaCopyLastMonth = () => {
    showContextMenu.value = false;
    if (hasExistingBudgetAmounts.value) {
        showConfirm(
            'Copy Last Month\'s Budget',
            'This will overwrite your current budget amounts with last month\'s values.',
            doCopyLastMonth
        );
    } else {
        doCopyLastMonth();
    }
};

const doCopyLastMonth = () => {
    router.post(route('budget.copy-last-month', { month: props.month }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Copied budget from last month', 'success');
        },
    });
};

// Get all categories with surplus (positive available)
const categoriesWithSurplus = computed(() => {
    const result = [];
    props.categoryGroups.forEach(group => {
        group.categories.forEach(category => {
            const available = getAvailable(category);
            if (available > 0) {
                result.push({
                    ...category,
                    groupName: group.name,
                    available: available,
                });
            }
        });
    });
    return result.sort((a, b) => b.available - a.available);
});

// Open move money modal for an overspent category
const openMoveMoneyModal = (category) => {
    const available = getAvailable(category);
    if (available >= 0) return; // Only open for overspent categories

    moveMoneyTarget.value = {
        ...category,
        overspentBy: Math.abs(available),
    };
    moveMoneyAmount.value = Math.abs(available);
    selectedSources.value = [];
    showMoveMoneyModal.value = true;
};

// Calculate total selected amount
const totalSelectedAmount = computed(() => {
    return selectedSources.value.reduce((sum, s) => sum + s.amount, 0);
});

// Calculate remaining amount needed
const remainingNeeded = computed(() => {
    return Math.max(0, moveMoneyAmount.value - totalSelectedAmount.value);
});

// Toggle selection of a source category
const toggleSourceCategory = (sourceCategory) => {
    const existingIndex = selectedSources.value.findIndex(s => s.id === sourceCategory.id);

    if (existingIndex >= 0) {
        // Deselect - remove from list
        selectedSources.value.splice(existingIndex, 1);
    } else {
        // Select - add with amount (min of available or remaining needed)
        const amountToUse = Math.min(sourceCategory.available, remainingNeeded.value || moveMoneyAmount.value);
        if (amountToUse > 0) {
            selectedSources.value.push({
                id: sourceCategory.id,
                name: sourceCategory.name,
                amount: amountToUse,
            });
        }
    }
};

// Check if a category is selected
const isSourceSelected = (categoryId) => {
    return selectedSources.value.some(s => s.id === categoryId);
};

// Get selected amount for a category
const getSelectedAmount = (categoryId) => {
    const source = selectedSources.value.find(s => s.id === categoryId);
    return source ? source.amount : 0;
};

// Execute all selected move money actions
const executeMoveMoney = async () => {
    if (selectedSources.value.length === 0) return;

    const sourcesToMove = [...selectedSources.value];
    const targetName = moveMoneyTarget.value.name;
    const targetId = moveMoneyTarget.value.id;
    let completedCount = 0;

    // Execute moves sequentially
    for (const source of sourcesToMove) {
        await new Promise((resolve) => {
            router.post(route('budget.move-money', { month: props.month }), {
                from_category_id: source.id,
                to_category_id: targetId,
                amount: source.amount,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    completedCount++;
                    resolve();
                },
                onError: () => resolve(),
            });
        });
    }

    // Close modal and show toast
    showMoveMoneyModal.value = false;

    // Build toast message
    const totalMoved = sourcesToMove.reduce((sum, s) => sum + s.amount, 0);
    const sourceNames = sourcesToMove.map(s => s.name).join(', ');
    showMoveToast(formatCurrency(totalMoved), sourceNames, targetName);
};

// Apply default amounts to budget
const initiateApplyDefaults = () => {
    showContextMenu.value = false;
    if (hasExistingBudgetAmounts.value) {
        showConfirm(
            'Apply Default Amounts',
            'This will overwrite your current budget amounts with the default amount set on each category.',
            doApplyDefaults
        );
    } else {
        doApplyDefaults();
    }
};

const doApplyDefaults = () => {
    router.post(route('budget.apply-defaults', { month: props.month }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Applied default amounts to budget', 'success');
        },
    });
};

// Apply projections to budget
const initiateApplyProjections = () => {
    showContextMenu.value = false;
    if (hasExistingBudgetAmounts.value) {
        showConfirm(
            'Apply Projections',
            'This will overwrite your current budget amounts with your projected amounts.',
            doApplyProjections
        );
    } else {
        doApplyProjections();
    }
};

const doApplyProjections = () => {
    router.post(route('budget.apply-projections', { month: props.month }), {
        projection_index: 1,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Applied projections to budget', 'success');
        },
    });
};

// Toast helper
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
};

// Move money toast helper
const showMoveToast = (amount, from, to, remaining = null) => {
    if (moveToastTimeout) clearTimeout(moveToastTimeout);

    moveToast.value = {
        show: true,
        amount,
        from,
        to,
        remaining,
    };

    moveToastTimeout = setTimeout(() => {
        moveToast.value.show = false;
    }, 4000);
};
</script>

<template>
    <Head title="Budget" />

    <AppLayout>
        <template #title>Budget</template>

        <div class="p-4 space-y-4">
            <!-- Toast Notification -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform opacity-0 -translate-y-2"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 -translate-y-2"
            >
                <div
                    v-if="toast.show"
                    class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-lg shadow-lg text-sm"
                    :class="{
                        'bg-primary text-body': toast.type === 'success',
                        'bg-info text-white': toast.type === 'info',
                        'bg-danger text-white': toast.type === 'error',
                    }"
                >
                    {{ toast.message }}
                </div>
            </Transition>

            <!-- Move Money Toast Notification -->
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
                        v-if="moveToast.show"
                        class="fixed bottom-24 left-4 right-4 z-50 bg-surface-header text-body rounded-card px-4 py-3 shadow-lg"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-success">✓</span>
                            <span>Moved {{ moveToast.amount }} from {{ moveToast.from }} to {{ moveToast.to }}</span>
                        </div>
                        <div v-if="moveToast.remaining" class="text-subtle text-sm mt-1 ml-6">
                            Still need {{ moveToast.remaining }}
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <!-- Month Selector -->
            <div class="flex items-center justify-between bg-surface rounded-card px-3 py-2">
                <button
                    @click="navigateMonth(previousMonth)"
                    :disabled="!canGoBack"
                    :class="[
                        'p-2 rounded-full',
                        canGoBack ? 'hover:bg-surface-overlay' : 'opacity-30 cursor-not-allowed'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <span class="font-semibold text-body">{{ formatMonth(month) }}</span>
                <button
                    @click="navigateMonth(nextMonth)"
                    class="p-2 hover:bg-surface-overlay rounded-full"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-surface rounded-card px-3 py-2 text-center">
                    <div class="text-xs text-subtle uppercase">Budgeted</div>
                    <div class="font-semibold text-body">
                        {{ formatCurrency(summary.budgeted) }}
                    </div>
                </div>
                <div class="bg-surface rounded-card px-3 py-2 text-center">
                    <div class="text-xs text-subtle uppercase">Spent</div>
                    <div class="font-semibold text-danger">
                        {{ formatCurrency(summary.spent) }}
                    </div>
                </div>
            </div>

            <!-- Ready to Assign -->
            <div
                class="rounded-card px-4 py-2 flex items-center justify-between relative"
                :class="summary.toBudget >= 0 ? 'bg-primary' : 'bg-danger'"
            >
                <button
                    @click="showBreakdown = !showBreakdown"
                    class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-inverse/90"
                >
                    Ready to Assign
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
                <!-- Breakdown popover -->
                <div
                    v-if="showBreakdown"
                    class="fixed inset-0 z-40"
                    @click="showBreakdown = false"
                ></div>
                <div
                    v-if="showBreakdown"
                    class="absolute left-3 top-12 z-50 bg-surface rounded-card shadow-lg border border-border p-4 min-w-[300px]"
                >
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-muted">{{ summary.isFirstMonth ? 'Starting account balances' : 'Carried forward' }}</span>
                            <span class="font-medium text-body">{{ formatCurrency(summary.carriedForward ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-muted">+ {{ shortMonthName }} income</span>
                            <span class="font-medium text-success">{{ formatCurrency(summary.thisMonthIncome ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-muted">− {{ shortMonthName }} assigned</span>
                            <span class="font-medium text-body">{{ formatCurrency(summary.budgeted ?? 0) }}</span>
                        </div>
                        <div class="border-t border-border my-1"></div>
                        <div class="flex justify-between gap-4 font-semibold">
                            <span class="text-body">Ready to Assign</span>
                            <span :class="summary.toBudget >= 0 ? 'text-success' : 'text-danger'">{{ formatCurrency(summary.toBudget) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-2xl font-semibold text-inverse">
                        {{ formatCurrency(summary.toBudget) }}
                    </div>
                    <!-- 3-dot menu -->
                    <button
                        @click="showContextMenu = !showContextMenu"
                        class="p-1 rounded-full hover:bg-white/20 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-inverse" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                        </svg>
                    </button>
                    <!-- Dropdown menu -->
                    <div
                        v-if="showContextMenu"
                        class="fixed inset-0 z-40"
                        @click="showContextMenu = false"
                    ></div>
                    <div
                        v-if="showContextMenu"
                        class="absolute right-4 top-14 z-50 bg-surface rounded-card shadow-lg border border-body py-1 min-w-[220px]"
                    >
                        <button
                            @click="initiateApplyDefaults"
                            class="w-full text-left px-4 py-3 hover:bg-surface-overlay transition-colors flex items-start gap-3"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <div>
                                <div class="text-sm text-body">Apply Category Defaults</div>
                                <div class="text-xs text-subtle mt-0.5">Sets each category to its saved default amount</div>
                            </div>
                        </button>
                        <div class="border-t border-border"></div>
                        <button
                            @click="initiaCopyLastMonth"
                            class="w-full text-left px-4 py-3 hover:bg-surface-overlay transition-colors flex items-start gap-3"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <div class="text-sm text-body">Copy Last Month's Budget</div>
                                <div class="text-xs text-subtle mt-0.5">Copies all budget amounts from the previous month</div>
                            </div>
                        </button>
                        <div class="border-t border-border"></div>
                        <button
                            @click="initiateApplyProjections"
                            class="w-full text-left px-4 py-3 hover:bg-surface-overlay transition-colors flex items-start gap-3"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-hover flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <div>
                                <div class="text-sm text-body">Apply Projections</div>
                                <div class="text-xs text-subtle mt-0.5">Uses amounts from the Plan page</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Show Details Toggle -->
            <Toggle v-model="showDetails" label="Show defaults & averages" />

            <!-- Category Groups -->
            <div v-for="group in categoryGroups" :key="group.id" class="space-y-2">
                <h2 class="text-sm font-semibold text-warning uppercase tracking-wide px-1">
                    {{ group.name }}
                </h2>

                <div class="bg-surface rounded-card overflow-hidden tabular-nums">
                    <!-- Column Headers -->
                    <div class="grid grid-cols-12 gap-1 px-3 py-2 bg-surface-header text-xs text-subtle uppercase border-b border-border">
                        <div class="col-span-4">Category</div>
                        <div class="col-span-3 text-right">Budget</div>
                        <div class="col-span-2 text-right">Spent</div>
                        <div class="col-span-3 text-right">Balance</div>
                    </div>

                    <!-- Category Rows -->
                    <div
                        v-for="category in group.categories"
                        :key="category.id"
                        class="border-b border-border last:border-b-0"
                    >
                        <!-- Main Row -->
                        <div class="grid grid-cols-12 gap-1 px-3 pt-3 pb-1 items-center">
                            <!-- Category Name (Clickable) -->
                            <a
                                :href="route('budget.category-detail', { month: month, category: category.id })"
                                class="col-span-4 flex items-center gap-1 min-w-0 hover:text-primary transition-colors"
                            >
                                <span v-if="category.icon" class="flex-shrink-0 text-sm">{{ category.icon }}</span>
                                <span class="text-sm text-body truncate hover:text-primary">{{ category.name }}</span>
                            </a>

                            <!-- Budgeted (Editable) -->
                            <div class="col-span-3 text-right">
                                <input
                                    v-if="editingField === category.id"
                                    :value="editingValue ? '$' + editingValue : '$'"
                                    type="text"
                                    inputmode="decimal"
                                    class="w-full px-1 py-1 text-right text-base bg-surface rounded border border-primary outline-none"
                                    @input="onBudgetInput($event)"
                                    @blur="saveAmount(category.id)"
                                    @keyup.enter="$event.target.blur()"
                                />
                                <div
                                    v-else
                                    @click="startEditing(category.id)"
                                    :class="[
                                        'w-full px-1 py-1 text-right text-sm rounded cursor-text transition-colors',
                                        editedAmounts[category.id]
                                            ? 'border-2 border-success bg-primary-bg'
                                            : 'border border-transparent hover:bg-surface-overlay'
                                    ]"
                                >
                                    ${{ formatNumber(budgetAmounts[category.id]) }}
                                </div>
                            </div>

                            <!-- Spent -->
                            <div class="col-span-2 text-right text-sm text-subtle">
                                ${{ formatNumber(category.spent) }}
                            </div>

                            <!-- Balance/Available (Clickable if overspent) -->
                            <div
                                class="col-span-3 text-right text-sm font-semibold"
                                :class="[
                                    isOverspent(getAvailable(category)) ? 'text-danger cursor-pointer hover:underline' : 'text-success',
                                ]"
                                @click="isOverspent(getAvailable(category)) && openMoveMoneyModal(category)"
                            >
                                {{ formatCurrency(getAvailable(category)) }}
                            </div>
                        </div>

                        <!-- Detail Row (Default & Avg Spent) -->
                        <div
                            v-if="showDetails && (category.default_amount > 0 || category.avg_spent > 0)"
                            class="grid grid-cols-12 gap-1 px-3 pb-2 items-center"
                        >
                            <div class="col-span-4"></div>
                            <div class="col-span-8 flex items-center gap-3 text-xs text-subtle">
                                <span v-if="category.default_amount > 0">
                                    Default: {{ formatNumber(category.default_amount) }}
                                </span>
                                <span v-if="category.default_amount > 0 && category.avg_spent > 0">•</span>
                                <span v-if="category.avg_spent > 0">
                                    Avg: {{ formatNumber(category.avg_spent) }}/mo
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Group Totals Row -->
                    <div class="grid grid-cols-12 gap-1 px-3 py-2 bg-info/30 text-sm font-semibold border-t-2 border-info/40">
                        <div class="col-span-4 text-info uppercase">Total</div>
                        <div class="col-span-3 text-right text-body px-1">
                            ${{ formatNumber(getGroupTotals(group).budgeted) }}
                        </div>
                        <div class="col-span-2 text-right text-subtle">
                            ${{ formatNumber(getGroupTotals(group).spent) }}
                        </div>
                        <div
                            class="col-span-3 text-right"
                            :class="getGroupTotals(group).available >= 0 ? 'text-success' : 'text-danger'"
                        >
                            {{ formatCurrency(getGroupTotals(group).available) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="categoryGroups.length === 0"
                class="text-center py-12"
            >
                <div class="mb-4 flex justify-center">
                    <svg class="w-10 h-10 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-body mb-2">No categories yet</h3>
                <p class="text-subtle">
                    Go to Settings to add categories and start budgeting.
                </p>
            </div>
        </div>

        <!-- Move Money Modal -->
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
                    v-if="showMoveMoneyModal"
                    class="fixed inset-0 z-50 flex items-end justify-center bg-black/50"
                    @click.self="showMoveMoneyModal = false"
                >
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="transform translate-y-full"
                        enter-to-class="transform translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="transform translate-y-0"
                        leave-to-class="transform translate-y-full"
                    >
                        <div
                            v-if="showMoveMoneyModal"
                            class="w-full max-w-lg bg-surface rounded-t-2xl max-h-[80vh] flex flex-col"
                        >
                            <!-- Modal Header -->
                            <div class="p-4 border-b border-border">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-body">
                                        {{ moveMoneyTarget?.icon }} {{ moveMoneyTarget?.name }}
                                    </h3>
                                    <button
                                        @click="showMoveMoneyModal = false"
                                        class="p-2 hover:bg-surface-overlay rounded-full"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-sm text-danger mt-1">
                                    Over by {{ formatCurrency(moveMoneyTarget?.overspentBy || 0) }}
                                </p>
                                <!-- Progress indicator -->
                                <div v-if="selectedSources.length > 0" class="mt-2 p-2 bg-primary-bg rounded-lg">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-body">Selected: {{ formatCurrency(totalSelectedAmount) }}</span>
                                        <span v-if="remainingNeeded > 0" class="text-subtle">Need: {{ formatCurrency(remainingNeeded) }}</span>
                                        <span v-else class="text-success font-medium">✓ Covered</span>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-subtle mt-2">
                                    Tap categories to select funds to move
                                </p>
                            </div>

                            <!-- Categories with Surplus -->
                            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                                <div
                                    v-for="category in categoriesWithSurplus"
                                    :key="category.id"
                                    @click="toggleSourceCategory(category)"
                                    :class="[
                                        'flex items-center justify-between p-3 rounded-lg cursor-pointer transition-colors',
                                        isSourceSelected(category.id)
                                            ? 'bg-primary-bg border-2 border-primary'
                                            : 'bg-surface-overlay hover:bg-border-strong'
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <span v-if="category.icon">{{ category.icon }}</span>
                                        <div>
                                            <div class="font-medium text-body">{{ category.name }}</div>
                                            <div class="text-xs text-subtle">{{ category.groupName }}</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-success">
                                            {{ formatCurrency(category.available) }}
                                        </div>
                                        <div v-if="isSourceSelected(category.id)" class="text-xs text-primary font-medium">
                                            Using {{ formatCurrency(getSelectedAmount(category.id)) }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="categoriesWithSurplus.length === 0"
                                    class="text-center py-8 text-subtle"
                                >
                                    No categories with available funds
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="p-4 border-t border-border space-y-2">
                                <button
                                    v-if="selectedSources.length > 0"
                                    @click="executeMoveMoney"
                                    class="w-full py-3 bg-primary text-body rounded-card font-medium hover:bg-primary-hover transition-colors"
                                >
                                    Move {{ formatCurrency(totalSelectedAmount) }}
                                </button>
                                <button
                                    @click="showMoveMoneyModal = false"
                                    class="w-full py-3 bg-surface-overlay text-body rounded-card font-medium hover:bg-border transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Confirmation Modal -->
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
                    v-if="confirmModal.show"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                    @click.self="cancelConfirm"
                >
                    <div class="w-full max-w-sm bg-surface rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-body">{{ confirmModal.title }}</h3>
                        <p class="text-subtle">{{ confirmModal.message }}</p>
                        <div class="flex gap-3">
                            <button
                                @click="cancelConfirm"
                                class="flex-1 py-3 bg-surface-overlay text-body rounded-card font-medium hover:bg-border transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="executeConfirm"
                                class="flex-1 py-3 bg-primary text-body rounded-card font-medium hover:bg-primary/90 transition-colors"
                            >
                                Continue
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
