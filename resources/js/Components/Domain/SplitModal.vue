<script setup>
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Base/Modal.vue';
import BottomSheet from '@/Components/Base/BottomSheet.vue';
import Button from '@/Components/Base/Button.vue';
import SegmentedControl from '@/Components/Form/SegmentedControl.vue';
import AmountField from '@/Components/Form/AmountField.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    categories: { type: Array, required: true },
    totalAmount: { type: [String, Number], default: 0 },
    defaultType: { type: String, default: 'expense' }, // expense or income
    initialItems: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'save']);

const splitItems = ref([{ category_id: '', amount: '' }]);
const splitCategorySheetIndex = ref(null);
const splitCategorySearch = ref('');

const inferType = (amount, fallback) => {
    const amt = parseFloat(amount);
    if (!isNaN(amt) && amt < 0) return 'expense';
    if (!isNaN(amt) && amt > 0) return 'income';
    return fallback;
};

const makeSplitItem = (item) => ({
    category_id: item.category_id || '',
    amount: item.amount || '',
    type: item.type || inferType(item.amount, props.defaultType),
});

// Initialize items when modal opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        splitItems.value = props.initialItems.length > 0
            ? props.initialItems.map(s => makeSplitItem(s))
            : [makeSplitItem({})];
    }
});

// Flat categories for display lookup
const flatCategories = computed(() => {
    const result = [];
    props.categories.forEach(group => {
        group.categories.forEach(cat => {
            result.push({ ...cat, groupName: group.name });
        });
    });
    return result;
});

const filteredSplitCategories = computed(() => {
    const query = splitCategorySearch.value.toLowerCase().trim();
    if (!query) return props.categories;
    return props.categories
        .map(group => ({
            ...group,
            categories: group.categories.filter(cat =>
                cat.name.toLowerCase().includes(query) || group.name.toLowerCase().includes(query)
            ),
        }))
        .filter(group => group.categories.length > 0);
});

const getSplitCategoryDisplay = (categoryId) => {
    if (!categoryId) return null;
    const cat = flatCategories.value.find(c => c.id === categoryId);
    return cat ? (cat.icon ? `${cat.icon} ${cat.name}` : cat.name) : null;
};

const selectSplitCategory = (index, categoryId) => {
    splitItems.value[index].category_id = categoryId;
    splitCategorySheetIndex.value = null;
    splitCategorySearch.value = '';
};

const addSplitItem = () => {
    splitItems.value.push(makeSplitItem({}));
};

const removeSplitItem = (index) => {
    if (splitItems.value.length > 1) {
        splitItems.value.splice(index, 1);
    }
};

const totalSplitAmount = computed(() => {
    const sum = splitItems.value.reduce((s, item) => s + (parseFloat(item.amount) || 0), 0);
    return Math.abs(sum);
});

const absTotal = computed(() => Math.abs(parseFloat(props.totalAmount) || 0));

const remainingAmount = computed(() => {
    return absTotal.value - totalSplitAmount.value;
});

const isSplitBalanced = computed(() => Math.abs(remainingAmount.value) < 0.01);

const splitTypeOptions = [
    { value: 'expense', label: 'Expense', color: 'expense' },
    { value: 'income', label: 'Income', color: 'income' },
];

// Type is the source of truth — amount sign follows the type
const onSplitTypeChange = (item, newType) => {
    item.type = newType;
    const amt = parseFloat(item.amount);
    if (isNaN(amt) || amt === 0) {
        item.amount = newType === 'expense' ? '-' : '';
        return;
    }
    if (newType === 'expense' && amt > 0) item.amount = (-amt).toFixed(2);
    if (newType === 'income' && amt < 0) item.amount = (-amt).toFixed(2);
};

// When amount changes, enforce sign based on the item's type
const onSplitAmountUpdate = (item, value) => {
    item.amount = value;
    // Enforce sign: if user types a positive number but type is expense, make it negative
    const num = parseFloat(value);
    if (!isNaN(num) && num !== 0) {
        if (item.type === 'expense' && num > 0) item.amount = (-num).toFixed(2);
        if (item.type === 'income' && num < 0) item.amount = (-num).toFixed(2);
    }
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
};

const handleSave = () => {
    const validSplits = splitItems.value.filter(s => {
        const amt = parseFloat(s.amount);
        return !isNaN(amt) && amt !== 0;
    });
    emit('save', validSplits);
};

const handleCancel = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" title="Split Transaction" @close="$emit('close')">
        <div class="px-4 pb-2 text-sm text-subtle">
            Total: {{ formatCurrency(absTotal) }}
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="bg-surface mx-3 rounded-xl overflow-hidden">
                <div
                    v-for="(item, index) in splitItems"
                    :key="index"
                    class="px-4 py-3 border-b border-border last:border-b-0"
                >
                    <div class="flex items-center justify-between mb-2">
                        <button
                            type="button"
                            @click="splitCategorySheetIndex = index"
                            class="flex items-center gap-1 text-sm font-medium min-w-0"
                            :class="item.category_id ? 'text-secondary' : 'text-subtle'"
                        >
                            <span class="truncate">{{ getSplitCategoryDisplay(item.category_id) || 'Select category' }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-subtle shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="removeSplitItem(index)"
                            class="p-1 text-border-strong hover:text-danger transition-colors"
                            :class="{ 'opacity-30 pointer-events-none': splitItems.length <= 0 }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-3">
                        <SegmentedControl
                            :model-value="item.type"
                            :options="splitTypeOptions"
                            size="md"
                            @update:model-value="onSplitTypeChange(item, $event)"
                            class="flex-1"
                        />
                        <AmountField
                            :model-value="item.amount"
                            :transaction-type="item.type"
                            @update:model-value="onSplitAmountUpdate(item, $event)"
                            class="w-24 flex-shrink-0"
                        />
                    </div>
                </div>
            </div>

            <button
                type="button"
                @click="addSplitItem"
                class="w-full mt-3 mx-3 py-3 text-sm font-medium text-primary"
            >
                + Add Category
            </button>
        </div>

        <!-- Remaining indicator -->
        <div class="px-4 py-3 border-t border-border">
            <div class="flex justify-between items-center">
                <span class="text-sm text-subtle">Remaining</span>
                <span
                    class="font-semibold"
                    :class="{
                        'text-danger': !isSplitBalanced,
                        'text-success': isSplitBalanced,
                    }"
                >
                    {{ formatCurrency(remainingAmount) }}
                </span>
            </div>
            <div class="mt-2 h-2 bg-border rounded-full overflow-hidden">
                <div
                    class="h-full transition-all duration-300"
                    :class="{
                        'bg-danger': !isSplitBalanced,
                        'bg-success': isSplitBalanced,
                    }"
                    :style="{ width: `${Math.min(100, (totalSplitAmount / (absTotal || 1)) * 100)}%` }"
                ></div>
            </div>
        </div>

        <template #footer>
            <div class="flex gap-2">
                <Button variant="secondary" @click="handleCancel" class="flex-1">Cancel</Button>
                <Button :disabled="!isSplitBalanced" @click="handleSave" class="flex-1">Save Split</Button>
            </div>
        </template>
    </Modal>

    <!-- Split Category Picker -->
    <BottomSheet :show="splitCategorySheetIndex !== null" title="Category" @close="splitCategorySheetIndex = null; splitCategorySearch = ''">
        <div class="px-4 pt-3 pb-2 border-b border-border">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="splitCategorySearch"
                    type="text"
                    placeholder="Search..."
                    class="w-full pl-9 pr-8 py-2 text-sm bg-surface-inset border border-border rounded-lg text-body placeholder:text-subtle focus-glow"
                />
                <button
                    v-if="splitCategorySearch"
                    type="button"
                    @click="splitCategorySearch = ''"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-subtle hover:text-body"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="py-2">
            <button
                v-if="!splitCategorySearch"
                type="button"
                @click="selectSplitCategory(splitCategorySheetIndex, null)"
                class="w-full px-4 py-3 text-left text-sm hover:bg-surface-overlay flex items-center justify-between border-b border-border"
                :class="splitCategorySheetIndex !== null && !splitItems[splitCategorySheetIndex]?.category_id ? 'text-secondary font-medium' : 'text-body'"
            >
                <span>Unassigned</span>
                <svg v-if="splitCategorySheetIndex !== null && !splitItems[splitCategorySheetIndex]?.category_id" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
            <div v-for="group in filteredSplitCategories" :key="group.name">
                <div class="px-4 py-2 text-xs font-semibold text-subtle uppercase tracking-wide bg-surface-header">
                    {{ group.name }}
                </div>
                <button
                    v-for="cat in group.categories"
                    :key="cat.id"
                    type="button"
                    @click="selectSplitCategory(splitCategorySheetIndex, cat.id)"
                    class="w-full px-4 py-3 text-left text-sm hover:bg-surface-overlay flex items-center justify-between"
                    :class="splitCategorySheetIndex !== null && splitItems[splitCategorySheetIndex]?.category_id === cat.id ? 'text-secondary font-medium' : 'text-body'"
                >
                    <span>{{ cat.icon ? `${cat.icon} ${cat.name}` : cat.name }}</span>
                    <svg v-if="splitCategorySheetIndex !== null && splitItems[splitCategorySheetIndex]?.category_id === cat.id" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </BottomSheet>
</template>
