<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import Button from '@/Components/Base/Button.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import { useTheme } from '@/Composables/useTheme.js';

const { showCategoryIcons } = useTheme();

const props = defineProps({
    categoryGroups: Array,
    defaultMonthlyIncome: Number,
    hasProjections: Boolean,
});

// Projection state
const expectedIncome = ref(props.defaultMonthlyIncome || 0);
const projectionAmounts = reactive({}); // { categoryId: amount }

// Toast state
const toast = ref({ show: false, message: '', type: 'success' });

// Initialize projections from props
props.categoryGroups.forEach(group => {
    group.categories.forEach(category => {
        const proj = category.projections || {};
        projectionAmounts[category.id] = parseFloat(proj['1']) || 0;
    });
});

// Current month label for button text (e.g. "Feb", "Mar")
const currentMonthLabel = new Date().toLocaleString('en-US', { month: 'short' });

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const getGroupTotals = (group) => {
    let defaultTotal = 0;
    let projectedTotal = 0;
    group.categories.forEach(category => {
        defaultTotal += parseFloat(category.default_amount) || 0;
        projectedTotal += parseFloat(projectionAmounts[category.id]) || 0;
    });
    return { defaultTotal, projectedTotal };
};

const totalProjected = computed(() => {
    let total = 0;
    props.categoryGroups.forEach(group => {
        group.categories.forEach(category => {
            total += parseFloat(projectionAmounts[category.id]) || 0;
        });
    });
    return total;
});

const leftToAllocate = computed(() => {
    return expectedIncome.value - totalProjected.value;
});

// --- Auto-save with debounce ---
let saveTimer = null;

const autoSave = () => {
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
        const projectionsData = [];
        props.categoryGroups.forEach(group => {
            group.categories.forEach(category => {
                const amount = projectionAmounts[category.id];
                projectionsData.push({
                    category_id: category.id,
                    values: { '1': amount },
                });
            });
        });

        router.post(route('budget.save-projections'), {
            projections: projectionsData,
        }, {
            preserveScroll: true,
        });
    }, 800);
};

const onIncomeBlur = () => {
    autoSave();
};

// --- Actions ---
const clearProjections = () => {
    showConfirm(
        'Clear All Projections',
        'This will remove all projected amounts and reset the page. This cannot be undone.',
        () => {
            router.post(route('budget.clear-projections'), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    props.categoryGroups.forEach(group => {
                        group.categories.forEach(category => {
                            projectionAmounts[category.id] = 0;
                        });
                    });
                    showToast('All projections cleared', 'success');
                },
            });
        }
    );
};

const applyProjections = () => {
    showConfirm(
        `Use as ${currentMonthLabel} Budget`,
        `This will replace your ${currentMonthLabel} budget amounts with the projected amounts shown here. Any existing budget allocations for ${currentMonthLabel} will be overwritten.`,
        () => {
            const currentMonth = new Date().toISOString().slice(0, 7);
            router.post(route('budget.apply-projections', { month: currentMonth }), {
                projection_index: 1,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    showToast(`${currentMonthLabel} budget updated`, 'success');
                },
            });
        }
    );
};

const saveProjectionsAsDefaults = () => {
    showConfirm(
        'Save Projections as Defaults',
        'This will overwrite all category default amounts with the projected amounts shown here. Default amounts are used when copying defaults to a new month\'s budget. This cannot be undone.',
        () => {
            router.post(route('budget.apply-projections-to-defaults'), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    // Update local default_amount values to reflect the change
                    props.categoryGroups.forEach(group => {
                        group.categories.forEach(category => {
                            category.default_amount = projectionAmounts[category.id] || 0;
                        });
                    });
                    showToast('Default amounts updated from projections', 'success');
                },
            });
        }
    );
};

// Confirmation modal state
const confirmModal = ref({ show: false, title: '', message: '', action: null });

const showConfirm = (title, message, action) => {
    confirmModal.value = { show: true, title, message, action };
};

const confirmAction = () => {
    if (confirmModal.value.action) confirmModal.value.action();
    confirmModal.value.show = false;
};

const cancelConfirm = () => {
    confirmModal.value.show = false;
};

const doCopyDefaults = () => {
    props.categoryGroups.forEach(group => {
        group.categories.forEach(category => {
            projectionAmounts[category.id] = parseFloat(category.default_amount) || 0;
        });
    });
    autoSave();
    showToast('Defaults copied to projections', 'success');
};

const copyDefaults = () => {
    const hasExisting = totalProjected.value > 0;
    if (hasExisting) {
        showConfirm(
            'Copy Default Amounts',
            'This will fill all projected amounts with your category defaults. Any existing projections will be overwritten.',
            doCopyDefaults
        );
    } else {
        doCopyDefaults();
    }
};

// Info tooltip
const infoOpen = ref(false);

const toggleInfo = () => {
    infoOpen.value = !infoOpen.value;
};

// Context menu
const menuOpen = ref(false);

const toggleMenu = () => {
    menuOpen.value = !menuOpen.value;
};

const closeMenu = () => {
    menuOpen.value = false;
};

const menuCopyDefaults = () => {
    closeMenu();
    copyDefaults();
};

const menuApplyProjections = () => {
    closeMenu();
    applyProjections();
};

const menuSaveAsDefaults = () => {
    closeMenu();
    saveProjectionsAsDefaults();
};

const menuClearProjections = () => {
    closeMenu();
    clearProjections();
};

// Toast helper
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
};
</script>

<template>
    <Head title="Plan" />

    <AppLayout>
        <template #title>Plan</template>

        <div class="px-2 py-4 space-y-4">
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

            <!-- Projection Header (Sticky) -->
            <div class="sticky top-0 z-10 bg-surface rounded-card">
                <!-- Top Row: Title + Income + Menu -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-border">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-body">Plan Budget</h3>
                        <button @click="toggleInfo" class="text-subtle hover:text-body">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-subtle uppercase">Income</span>
                        <AmountField
                            v-model="expectedIncome"
                            transaction-type="income"
                            @blur="onIncomeBlur"
                            class="w-28"
                        />
                        <div class="relative">
                            <button
                                @click="toggleMenu"
                                class="p-1 rounded hover:bg-surface-overlay"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-body" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <!-- Backdrop to close menu -->
                            <div v-if="menuOpen" class="fixed inset-0 z-20" @click="closeMenu"></div>
                            <!-- Dropdown -->
                            <Transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div v-if="menuOpen" class="absolute right-0 mt-1 w-72 bg-surface rounded-card shadow-xl border border-body z-30 py-1">
                                    <button
                                        @click="menuCopyDefaults"
                                        class="w-full text-left px-4 py-3 hover:bg-surface-overlay transition-colors flex items-start gap-3"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                        <div>
                                            <div class="text-sm text-body">Copy Defaults to Projections</div>
                                            <div class="text-xs text-subtle mt-0.5">Fill projections with your category default amounts</div>
                                        </div>
                                    </button>
                                    <div class="border-t border-border"></div>
                                    <button
                                        @click="menuApplyProjections"
                                        class="w-full text-left px-4 py-3 hover:bg-surface-overlay transition-colors flex items-start gap-3"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <div>
                                            <div class="text-sm text-body">Apply Projections to {{ currentMonthLabel }} Budget</div>
                                            <div class="text-xs text-subtle mt-0.5">Apply these projections to your monthly budget</div>
                                        </div>
                                    </button>
                                    <div class="border-t border-border"></div>
                                    <button
                                        @click="menuSaveAsDefaults"
                                        class="w-full text-left px-4 py-3 hover:bg-surface-overlay transition-colors flex items-start gap-3"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div>
                                            <div class="text-sm text-body">Save Projections as Defaults</div>
                                            <div class="text-xs text-subtle mt-0.5">Overwrite category defaults with these projected amounts</div>
                                        </div>
                                    </button>
                                    <div class="border-t border-border"></div>
                                    <button
                                        @click="menuClearProjections"
                                        class="w-full text-left px-4 py-3 hover:bg-surface-overlay transition-colors flex items-start gap-3"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-danger flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <div>
                                            <div class="text-sm text-danger">Clear All Projections</div>
                                            <div class="text-xs text-subtle mt-0.5">Reset all projected amounts to zero</div>
                                        </div>
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>

                <!-- Info panel (collapsible, inside card) -->
                <Transition
                    enter-active-class="transition ease-out duration-150"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="infoOpen" class="px-4 py-3 border-b border-border text-sm text-subtle space-y-1">
                        <p>Set your expected income and adjust projected amounts for each category.</p>
                        <p>Changes save automatically.</p>
                        <p>Tap the <strong class="text-body">three-dot menu</strong> to copy defaults, apply projections to your budget, or clear all.</p>
                        <button @click="infoOpen = false" class="text-xs text-subtle hover:text-body underline mt-1">Dismiss</button>
                    </div>
                </Transition>

                <!-- Bottom Row: Two stats -->
                <div class="grid grid-cols-2 gap-4 px-4 py-2 text-center">
                    <div>
                        <div class="text-xs text-subtle uppercase">Total Projected</div>
                        <div class="font-semibold text-body text-base">
                            {{ formatCurrency(totalProjected) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-subtle uppercase">Left to Allocate</div>
                        <div
                            class="font-semibold text-base"
                            :class="leftToAllocate >= 0 ? 'text-success' : 'text-danger'"
                        >
                            {{ formatCurrency(leftToAllocate) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Groups -->
            <div v-for="group in categoryGroups" :key="group.id" class="space-y-2">
                <h2 class="text-sm font-semibold text-warning uppercase tracking-wide px-1">
                    {{ group.name }}
                </h2>

                <div class="bg-surface rounded-card overflow-hidden tabular-nums">
                    <!-- Column Headers -->
                    <div class="grid grid-cols-[1fr_4.5rem_6rem] gap-px px-2 py-2 bg-secondary/10 text-xs text-subtle uppercase border-b border-secondary/10">
                        <div>Category</div>
                        <div class="text-right">Default</div>
                        <div class="text-right">Projected</div>
                    </div>

                    <!-- Category Rows -->
                    <div
                        v-for="category in group.categories"
                        :key="category.id"
                        class="grid grid-cols-[1fr_4.5rem_6rem] gap-px px-2 pt-3 pb-1 items-center border-b border-secondary/10 last:border-b-0"
                    >
                        <div class="flex items-center gap-1 min-w-0">
                            <span v-if="showCategoryIcons && category.icon" class="flex-shrink-0 text-sm">{{ category.icon }}</span>
                            <span class="font-medium text-body truncate">{{ category.name }}</span>
                        </div>

                        <div class="text-right text-sm text-subtle">
                            {{ formatCurrency(category.default_amount) }}
                        </div>

                        <AmountField
                            :modelValue="projectionAmounts[category.id]"
                            @update:modelValue="val => { projectionAmounts[category.id] = parseFloat(val) || 0 }"
                            @blur="autoSave"
                            color="text-body"
                        />
                    </div>

                    <!-- Group Total Row -->
                    <div class="grid grid-cols-[1fr_4.5rem_6rem] gap-px px-2 py-2 bg-info/30 text-sm font-semibold border-t-2 border-info/40">
                        <div class="text-info uppercase">Total</div>
                        <div class="text-right text-body">
                            {{ formatCurrency(getGroupTotals(group).defaultTotal) }}
                        </div>
                        <div class="text-right text-body">
                            {{ formatCurrency(getGroupTotals(group).projectedTotal) }}
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
                    Go to Settings to add categories and start planning.
                </p>
            </div>

            <!-- Confirmation Modal -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="confirmModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50" @click="cancelConfirm"></div>
                    <div class="relative bg-surface rounded-card p-6 max-w-sm w-full shadow-xl space-y-4">
                        <h3 class="font-semibold text-body text-lg">{{ confirmModal.title }}</h3>
                        <p class="text-sm text-subtle">{{ confirmModal.message }}</p>
                        <div class="flex gap-3 justify-end">
                            <Button variant="ghost" size="sm" @click="cancelConfirm">
                                Cancel
                            </Button>
                            <Button size="sm" @click="confirmAction">
                                Confirm
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AppLayout>
</template>
