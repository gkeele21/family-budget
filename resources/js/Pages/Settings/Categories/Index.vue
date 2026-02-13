<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import TextField from '@/Components/Form/TextField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import PickerField from '@/Components/Form/PickerField.vue';
import Button from '@/Components/Base/Button.vue';
import Modal from '@/Components/Base/Modal.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    categoryGroups: Array,
});

// Transform category groups for PickerField
const groupOptions = computed(() => {
    return orderedGroups.value.map(g => ({ id: g.id, name: g.name }));
});

const showAddGroupModal = ref(false);
const showEditGroupModal = ref(false);
const showAddCategoryModal = ref(false);
const showEditCategoryModal = ref(false);
const selectedGroupId = ref(null);
const editingCategory = ref(null);
const editingGroup = ref(null);
const showIconPicker = ref(false);

const orderedGroups = ref(props.categoryGroups.map(g => ({
    ...g,
    categories: [...g.categories],
})));

watch(() => props.categoryGroups, (newGroups) => {
    orderedGroups.value = newGroups.map(g => ({
        ...g,
        categories: [...g.categories],
    }));
});

const saveGroupOrder = () => {
    const groupIds = orderedGroups.value.map(g => g.id);
    router.post(route('category-groups.reorder'), { ids: groupIds }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const saveCategoryOrder = (group) => {
    const categoryIds = group.categories.map(c => c.id);
    if (categoryIds.length > 0) {
        router.post(route('categories.reorder'), {
            group_id: group.id,
            ids: categoryIds,
        }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

// Track collapsed state for each group (all expanded by default)
const collapsedGroups = ref({});

const groupForm = useForm({
    name: '',
});

const editGroupForm = useForm({
    id: null,
    name: '',
});

const categoryForm = useForm({
    group_id: '',
    name: '',
    icon: '',
    default_amount: '',
});

const editForm = useForm({
    id: null,
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

const openEditGroup = (group) => {
    editingGroup.value = group;
    editGroupForm.id = group.id;
    editGroupForm.name = group.name;
    showEditGroupModal.value = true;
};

const submitEditGroup = () => {
    editGroupForm.put(route('category-groups.update', editGroupForm.id), {
        onSuccess: () => {
            showEditGroupModal.value = false;
            editGroupForm.reset();
            editingGroup.value = null;
        },
    });
};

const deleteGroup = (groupId) => {
    if (confirm('Delete this category group and all its categories?')) {
        router.delete(route('category-groups.destroy', groupId));
        showEditGroupModal.value = false;
    }
};

const deleteCategory = (categoryId) => {
    if (confirm('Delete this category? Transactions will become uncategorized.')) {
        router.delete(route('categories.destroy', categoryId));
        showEditCategoryModal.value = false;
    }
};

const openEditCategory = (category, groupId) => {
    editForm.id = category.id;
    editForm.group_id = groupId;
    editForm.name = category.name;
    editForm.icon = category.icon || '';
    editForm.default_amount = category.default_amount || '';
    showEditCategoryModal.value = true;
    showIconPicker.value = false;
};

const submitEditCategory = () => {
    editForm.put(route('categories.update', editForm.id), {
        onSuccess: () => {
            showEditCategoryModal.value = false;
            editForm.reset();
        },
    });
};

const formatCurrency = (amount) => {
    if (!amount) return '-';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

// Common emoji suggestions for budgeting - organized grid from wireframe
const emojiGrid = [
    '🛒', '🍎', '🥗', '🛍️', '🏠', '⚡', '💧', '📱',
    '🌐', '🚗', '⛽', '🍽️', '☕', '🎬', '🎮', '🎵',
    '💪', '💊', '👕', '✂️', '🎁', '✈️', '🏖️', '📚',
];

const toggleGroup = (groupId) => {
    collapsedGroups.value[groupId] = !collapsedGroups.value[groupId];
};

const isGroupCollapsed = (groupId) => {
    return collapsedGroups.value[groupId] === true;
};
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
            <!-- Add Group Button -->
            <Button
                variant="primary"
                full-width
                @click="showAddGroupModal = true"
            >
                + Add Group
            </Button>

            <draggable
                v-model="orderedGroups"
                item-key="id"
                handle=".group-drag-handle"
                ghost-class="opacity-30"
                :animation="200"
                @end="saveGroupOrder"
            >
                <template #item="{ element: group }">
                    <div class="space-y-2 mb-4">
                        <!-- Group Header -->
                        <div class="flex items-center gap-1 px-1">
                            <div class="group-drag-handle cursor-grab active:cursor-grabbing p-1 -ml-1 text-subtle">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                </svg>
                            </div>
                            <!-- Expand/Collapse chevron -->
                            <button
                                @click="toggleGroup(group.id)"
                                class="p-1 -m-1"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 text-subtle transition-transform"
                                    :class="{ '-rotate-90': isGroupCollapsed(group.id) }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <!-- Group name - clickable to edit -->
                            <button
                                @click="openEditGroup(group)"
                                class="text-sm font-semibold text-subtle uppercase tracking-wide hover:text-body"
                            >
                                {{ group.name }}
                            </button>
                            <span class="text-xs text-subtle font-normal">({{ group.categories.length }})</span>
                        </div>

                        <!-- Collapsible categories list -->
                        <Transition
                            enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 max-h-0"
                            enter-to-class="opacity-100 max-h-[2000px]"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 max-h-[2000px]"
                            leave-to-class="opacity-0 max-h-0"
                        >
                            <div v-show="!isGroupCollapsed(group.id)">
                                <div class="bg-surface rounded-card divide-y divide-border overflow-hidden">
                                    <draggable
                                        v-model="group.categories"
                                        item-key="id"
                                        handle=".category-drag-handle"
                                        ghost-class="opacity-30"
                                        :animation="200"
                                        @end="saveCategoryOrder(group)"
                                    >
                                        <template #item="{ element: category }">
                                            <div
                                                class="flex items-center"
                                                :class="{ 'opacity-50': category.is_hidden }"
                                            >
                                                <div class="category-drag-handle cursor-grab active:cursor-grabbing pl-4 pr-2 py-4 text-subtle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                    </svg>
                                                </div>
                                                <button
                                                    @click="openEditCategory(category, group.id)"
                                                    class="flex-1 flex items-center justify-between py-4 pr-4 hover:bg-surface-secondary text-left"
                                                >
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-xl">{{ category.icon || '📁' }}</span>
                                                        <div>
                                                            <div class="font-medium text-body">{{ category.name }}</div>
                                                            <div v-if="category.default_amount" class="text-sm text-subtle">
                                                                Default: {{ formatCurrency(category.default_amount) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span class="text-subtle">›</span>
                                                </button>
                                            </div>
                                        </template>
                                    </draggable>
                                </div>

                                <!-- Add Category button for this group -->
                                <div class="mt-2">
                                    <Button
                                        variant="outline"
                                        full-width
                                        @click="openAddCategory(group.id)"
                                    >
                                        + Add Category
                                    </Button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </template>
            </draggable>
        </div>

        <!-- Add Group Modal -->
        <Modal :show="showAddGroupModal" title="Add Category Group" @close="showAddGroupModal = false">
            <form @submit.prevent="submitGroup">
                <div class="bg-surface mx-3 rounded-xl overflow-hidden">
                    <TextField
                        v-model="groupForm.name"
                        label="Group Name"
                        placeholder="e.g., Bills, Everyday, Savings Goals"
                        :border-bottom="false"
                        required
                    />
                </div>
            </form>

            <template #footer>
                <div class="flex gap-2">
                    <Button variant="secondary" @click="showAddGroupModal = false" class="flex-1">
                        Cancel
                    </Button>
                    <Button @click="submitGroup" :loading="groupForm.processing" class="flex-1">
                        Add Group
                    </Button>
                </div>
            </template>
        </Modal>

        <!-- Add Category Modal -->
        <Modal :show="showAddCategoryModal" title="Add Category" @close="showAddCategoryModal = false">
            <form @submit.prevent="submitCategory">
                <div class="bg-surface mx-3 rounded-xl overflow-hidden">
                    <TextField
                        v-model="categoryForm.name"
                        label="Name"
                        placeholder="e.g., Groceries, Rent, Gas"
                        required
                    />
                    <AmountField
                        v-model="categoryForm.default_amount"
                        label="Default Amount"
                        :color-by-type="false"
                        placeholder="0.00"
                    />
                </div>

                <!-- Icon Picker -->
                <div class="mx-3 mt-3">
                    <div class="text-xs font-semibold text-subtle uppercase tracking-wide mb-2 px-1">
                        Icon
                    </div>
                    <div class="bg-surface rounded-xl p-3">
                        <div class="grid grid-cols-8 gap-1.5">
                            <button
                                v-for="emoji in emojiGrid"
                                :key="emoji"
                                type="button"
                                @click="categoryForm.icon = emoji"
                                :class="[
                                    'w-9 h-9 flex items-center justify-center text-xl rounded-lg transition-colors',
                                    categoryForm.icon === emoji
                                        ? 'bg-primary/20 ring-2 ring-primary'
                                        : 'bg-surface-secondary hover:bg-border'
                                ]"
                            >
                                {{ emoji }}
                            </button>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-subtle text-center mt-3 px-4">
                    Default amount is used as reference when budgeting
                </p>
            </form>

            <template #footer>
                <div class="flex gap-2">
                    <Button variant="secondary" @click="showAddCategoryModal = false" class="flex-1">
                        Cancel
                    </Button>
                    <Button @click="submitCategory" :loading="categoryForm.processing" class="flex-1">
                        Add Category
                    </Button>
                </div>
            </template>
        </Modal>

        <!-- Edit Category Modal -->
        <Modal :show="showEditCategoryModal" title="Edit Category" @close="showEditCategoryModal = false">
            <form @submit.prevent="submitEditCategory">
                <div class="bg-surface mx-3 rounded-xl overflow-hidden">
                    <TextField
                        v-model="editForm.name"
                        label="Name"
                        placeholder="Category name"
                        required
                    />
                    <PickerField
                        v-model="editForm.group_id"
                        label="Group"
                        :options="groupOptions"
                        placeholder="Select group"
                    />
                    <AmountField
                        v-model="editForm.default_amount"
                        label="Default Amount"
                        :color-by-type="false"
                        placeholder="0.00"
                        :border-bottom="false"
                    />
                </div>

                <!-- Icon Picker -->
                <div class="mx-3 mt-3">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <span class="text-xs font-semibold text-subtle uppercase tracking-wide">Icon</span>
                        <span class="text-2xl">{{ editForm.icon || '📁' }}</span>
                    </div>
                    <div class="bg-surface rounded-xl p-3">
                        <div class="grid grid-cols-8 gap-1.5">
                            <button
                                v-for="emoji in emojiGrid"
                                :key="emoji"
                                type="button"
                                @click="editForm.icon = emoji"
                                :class="[
                                    'w-9 h-9 flex items-center justify-center text-xl rounded-lg transition-colors',
                                    editForm.icon === emoji
                                        ? 'bg-primary/20 ring-2 ring-primary'
                                        : 'bg-surface-secondary hover:bg-border'
                                ]"
                            >
                                {{ emoji }}
                            </button>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-subtle text-center mt-3 px-4">
                    Default amount is used as reference when budgeting
                </p>

                <!-- Delete Button -->
                <div class="mx-3 mt-4">
                    <button
                        type="button"
                        @click="deleteCategory(editForm.id)"
                        class="w-full py-3 text-expense font-medium text-sm"
                    >
                        Delete Category
                    </button>
                </div>
            </form>

            <template #footer>
                <Button
                    type="button"
                    @click="submitEditCategory"
                    :loading="editForm.processing"
                    full-width
                >
                    Save Changes
                </Button>
            </template>
        </Modal>

        <!-- Edit Group Modal -->
        <Modal :show="showEditGroupModal" title="Edit Group" @close="showEditGroupModal = false">
            <form @submit.prevent="submitEditGroup">
                <div class="bg-surface mx-3 rounded-xl overflow-hidden">
                    <TextField
                        v-model="editGroupForm.name"
                        label="Group Name"
                        placeholder="e.g., Bills, Everyday, Savings Goals"
                        :border-bottom="false"
                        required
                    />
                </div>

                <!-- Delete Button -->
                <div class="mx-3 mt-4">
                    <button
                        type="button"
                        @click="deleteGroup(editGroupForm.id)"
                        class="w-full py-3 text-expense font-medium text-sm"
                    >
                        Delete Group
                    </button>
                </div>
            </form>

            <template #footer>
                <Button
                    type="button"
                    @click="submitEditGroup"
                    :loading="editGroupForm.processing"
                    full-width
                >
                    Save Changes
                </Button>
            </template>
        </Modal>
    </AppLayout>
</template>
