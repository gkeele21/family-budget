<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    categoryGroups: Array,
});

const showAddGroupModal = ref(false);
const showAddCategoryModal = ref(false);
const selectedGroupId = ref(null);
const editingCategory = ref(null);

const groupForm = useForm({
    name: '',
});

const categoryForm = useForm({
    group_id: '',
    name: '',
    icon: '',
    default_amount: '',
});

const submitGroup = () => {
    groupForm.post(route('category-groups.store'), {
        onSuccess: () => {
            showAddGroupModal.value = false;
            groupForm.reset();
        },
    });
};

const openAddCategory = (groupId) => {
    selectedGroupId.value = groupId;
    categoryForm.group_id = groupId;
    showAddCategoryModal.value = true;
};

const submitCategory = () => {
    categoryForm.post(route('categories.store'), {
        onSuccess: () => {
            showAddCategoryModal.value = false;
            categoryForm.reset();
        },
    });
};

const deleteGroup = (groupId) => {
    if (confirm('Delete this category group and all its categories?')) {
        router.delete(route('category-groups.destroy', groupId));
    }
};

const deleteCategory = (categoryId) => {
    if (confirm('Delete this category?')) {
        router.delete(route('categories.destroy', categoryId));
    }
};

const formatCurrency = (amount) => {
    if (!amount) return '-';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

// Common emoji suggestions for budgeting
const emojiSuggestions = ['🛒', '🍔', '⛽', '🏠', '💡', '📱', '🚗', '🎬', '👕', '💊', '🎁', '✈️', '💰', '📚', '🐕'];
</script>

<template>
    <Head title="Categories" />

    <AppLayout>
        <template #title>Categories</template>
        <template #header-left>
            <Link :href="route('settings.index')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <div class="p-4 space-y-4">
            <!-- Category Groups -->
            <div v-for="group in categoryGroups" :key="group.id" class="space-y-2">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm font-semibold text-budget-text-secondary uppercase tracking-wide">
                        {{ group.name }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <button
                            @click="openAddCategory(group.id)"
                            class="text-budget-primary text-sm font-medium"
                        >
                            + Add
                        </button>
                        <button
                            @click="deleteGroup(group.id)"
                            class="text-budget-text-secondary hover:text-budget-expense p-1"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="bg-budget-card rounded-card divide-y divide-gray-100">
                    <div
                        v-for="category in group.categories"
                        :key="category.id"
                        class="flex items-center justify-between p-4"
                        :class="{ 'opacity-50': category.is_hidden }"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-xl">{{ category.icon || '📁' }}</span>
                            <div>
                                <div class="font-medium text-budget-text">{{ category.name }}</div>
                                <div v-if="category.default_amount" class="text-sm text-budget-text-secondary">
                                    Default: {{ formatCurrency(category.default_amount) }}
                                </div>
                            </div>
                        </div>
                        <button
                            @click="deleteCategory(category.id)"
                            class="text-budget-text-secondary hover:text-budget-expense p-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                    <div v-if="group.categories.length === 0" class="p-4 text-center text-budget-text-secondary">
                        No categories yet
                    </div>
                </div>
            </div>

            <!-- Add Category Group Button -->
            <button
                @click="showAddGroupModal = true"
                class="w-full py-4 border-2 border-dashed border-budget-primary text-budget-primary rounded-card font-medium hover:bg-budget-primary-bg transition-colors"
            >
                + Add Category Group
            </button>
        </div>

        <!-- Add Group Modal -->
        <div
            v-if="showAddGroupModal"
            class="fixed inset-0 bg-black/50 flex items-end justify-center z-50"
            @click.self="showAddGroupModal = false"
        >
            <div class="bg-white rounded-t-2xl w-full max-w-md p-6 pb-8">
                <h3 class="text-lg font-semibold text-budget-text mb-4">Add Category Group</h3>

                <form @submit.prevent="submitGroup" class="space-y-4">
                    <div>
                        <label class="block text-sm text-budget-text-secondary mb-1">Group Name</label>
                        <input
                            v-model="groupForm.name"
                            type="text"
                            placeholder="e.g., Bills, Everyday, Savings Goals"
                            class="w-full px-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                            required
                        />
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            @click="showAddGroupModal = false"
                            class="flex-1 py-3 border border-gray-300 rounded-card font-medium"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="groupForm.processing"
                            class="flex-1 py-3 bg-budget-primary text-white rounded-card font-medium disabled:opacity-50"
                        >
                            {{ groupForm.processing ? 'Adding...' : 'Add Group' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Category Modal -->
        <div
            v-if="showAddCategoryModal"
            class="fixed inset-0 bg-black/50 flex items-end justify-center z-50"
            @click.self="showAddCategoryModal = false"
        >
            <div class="bg-white rounded-t-2xl w-full max-w-md p-6 pb-8">
                <h3 class="text-lg font-semibold text-budget-text mb-4">Add Category</h3>

                <form @submit.prevent="submitCategory" class="space-y-4">
                    <div>
                        <label class="block text-sm text-budget-text-secondary mb-1">Category Name</label>
                        <input
                            v-model="categoryForm.name"
                            type="text"
                            placeholder="e.g., Groceries, Rent, Gas"
                            class="w-full px-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm text-budget-text-secondary mb-1">Icon (Emoji)</label>
                        <input
                            v-model="categoryForm.icon"
                            type="text"
                            placeholder="🛒"
                            class="w-full px-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                            maxlength="2"
                        />
                        <div class="flex gap-2 mt-2 flex-wrap">
                            <button
                                v-for="emoji in emojiSuggestions"
                                :key="emoji"
                                type="button"
                                @click="categoryForm.icon = emoji"
                                class="w-10 h-10 flex items-center justify-center text-xl hover:bg-gray-100 rounded"
                            >
                                {{ emoji }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-budget-text-secondary mb-1">Default Amount (optional)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-budget-text-secondary">$</span>
                            <input
                                v-model="categoryForm.default_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                            />
                        </div>
                        <p class="text-sm text-budget-text-secondary mt-1">Used as a starting point when budgeting</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            @click="showAddCategoryModal = false"
                            class="flex-1 py-3 border border-gray-300 rounded-card font-medium"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="categoryForm.processing"
                            class="flex-1 py-3 bg-budget-primary text-white rounded-card font-medium disabled:opacity-50"
                        >
                            {{ categoryForm.processing ? 'Adding...' : 'Add Category' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
