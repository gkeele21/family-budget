<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    month: String,
    categoryGroups: Array,
    summary: Object,
});

const editingCategory = ref(null);
const editAmount = ref(0);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatMonth = (monthStr) => {
    const date = new Date(monthStr + '-01');
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const previousMonth = computed(() => {
    const date = new Date(props.month + '-01');
    date.setMonth(date.getMonth() - 1);
    return date.toISOString().slice(0, 7);
});

const nextMonth = computed(() => {
    const date = new Date(props.month + '-01');
    date.setMonth(date.getMonth() + 1);
    return date.toISOString().slice(0, 7);
});

const navigateMonth = (month) => {
    router.get(route('budget.index', { month }));
};

const startEditing = (category) => {
    editingCategory.value = category.id;
    editAmount.value = category.budgeted;
};

const saveAmount = (category) => {
    router.put(route('budget.update', { month: props.month }), {
        budgets: [{ category_id: category.id, amount: editAmount.value }],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingCategory.value = null;
        },
    });
};

const cancelEditing = () => {
    editingCategory.value = null;
};

const getProgressWidth = (spent, budgeted) => {
    if (budgeted <= 0) return 0;
    return Math.min((spent / budgeted) * 100, 100);
};

const isOverspent = (available) => available < 0;
</script>

<template>
    <Head title="Budget" />

    <AppLayout>
        <template #title>Budget</template>

        <div class="p-4 space-y-4">
            <!-- Month Selector -->
            <div class="flex items-center justify-between bg-budget-card rounded-card p-3">
                <button
                    @click="navigateMonth(previousMonth)"
                    class="p-2 hover:bg-gray-100 rounded-full"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <span class="font-semibold text-budget-text">{{ formatMonth(month) }}</span>
                <button
                    @click="navigateMonth(nextMonth)"
                    class="p-2 hover:bg-gray-100 rounded-full"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-budget-card rounded-card p-3 text-center">
                    <div class="text-xs text-budget-text-secondary uppercase">To Budget</div>
                    <div
                        class="font-mono font-semibold"
                        :class="summary.toBudget >= 0 ? 'text-budget-income' : 'text-budget-expense'"
                    >
                        {{ formatCurrency(summary.toBudget) }}
                    </div>
                </div>
                <div class="bg-budget-card rounded-card p-3 text-center">
                    <div class="text-xs text-budget-text-secondary uppercase">Budgeted</div>
                    <div class="font-mono font-semibold text-budget-text">
                        {{ formatCurrency(summary.budgeted) }}
                    </div>
                </div>
                <div class="bg-budget-card rounded-card p-3 text-center">
                    <div class="text-xs text-budget-text-secondary uppercase">Spent</div>
                    <div class="font-mono font-semibold text-budget-expense">
                        {{ formatCurrency(summary.spent) }}
                    </div>
                </div>
            </div>

            <!-- Category Groups -->
            <div v-for="group in categoryGroups" :key="group.id" class="space-y-2">
                <h2 class="text-sm font-semibold text-budget-text-secondary uppercase tracking-wide px-1">
                    {{ group.name }}
                </h2>

                <div class="bg-budget-card rounded-card divide-y divide-gray-100">
                    <div
                        v-for="category in group.categories"
                        :key="category.id"
                        class="p-3"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span v-if="category.icon">{{ category.icon }}</span>
                                <span class="font-medium text-budget-text">{{ category.name }}</span>
                            </div>
                            <div
                                class="font-mono text-sm font-semibold"
                                :class="isOverspent(category.available) ? 'text-budget-expense' : 'text-budget-income'"
                            >
                                {{ formatCurrency(category.available) }}
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden mb-2">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="isOverspent(category.available) ? 'bg-budget-expense' : 'bg-budget-primary'"
                                :style="{ width: getProgressWidth(category.spent, category.budgeted) + '%' }"
                            ></div>
                        </div>

                        <!-- Budgeted Amount (Editable) -->
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-budget-text-secondary">Budgeted:</span>
                            <div v-if="editingCategory === category.id" class="flex items-center gap-2">
                                <input
                                    v-model.number="editAmount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-24 px-2 py-1 text-right border border-budget-primary rounded focus:ring-1 focus:ring-budget-primary"
                                    @keyup.enter="saveAmount(category)"
                                    @keyup.escape="cancelEditing"
                                    autofocus
                                />
                                <button
                                    @click="saveAmount(category)"
                                    class="text-budget-primary hover:text-budget-primary-light"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="cancelEditing"
                                    class="text-budget-text-secondary hover:text-budget-text"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <button
                                v-else
                                @click="startEditing(category)"
                                class="font-mono text-budget-text hover:text-budget-primary"
                            >
                                {{ formatCurrency(category.budgeted) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="categoryGroups.length === 0"
                class="text-center py-12"
            >
                <div class="text-4xl mb-4">📊</div>
                <h3 class="text-lg font-medium text-budget-text mb-2">No categories yet</h3>
                <p class="text-budget-text-secondary mb-4">
                    Add some categories to start budgeting.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
