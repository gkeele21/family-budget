<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextField from '@/Components/Form/TextField.vue';
import PickerField from '@/Components/Form/PickerField.vue';
import Button from '@/Components/Base/Button.vue';
import Modal from '@/Components/Base/Modal.vue';
import SearchField from '@/Components/Form/SearchField.vue';

const props = defineProps({
    payees: Array,
    categories: Array,
});

// Edit modal state
const showEditModal = ref(false);
const editingPayee = ref(null);
const editForm = useForm({
    name: '',
    default_category_id: '',
});

// Delete confirmation state
const showDeleteConfirm = ref(false);
const deletingPayee = ref(null);

// Search/filter
const searchQuery = ref('');

const filteredPayees = computed(() => {
    if (!searchQuery.value) return props.payees;
    const query = searchQuery.value.toLowerCase();
    return props.payees.filter(p => p.name.toLowerCase().includes(query));
});

const flatCategories = computed(() => {
    const result = [];
    props.categories.forEach(group => {
        group.categories.forEach(cat => {
            result.push({
                ...cat,
                groupName: group.name,
            });
        });
    });
    return result;
});

const openEditModal = (payee) => {
    editingPayee.value = payee;
    editForm.name = payee.name;
    editForm.default_category_id = payee.default_category_id || '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingPayee.value = null;
    editForm.reset();
};

const savePayee = () => {
    editForm.put(route('payees.update', editingPayee.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const openDeleteConfirm = (payee) => {
    deletingPayee.value = payee;
    showDeleteConfirm.value = true;
};

const deletePayee = () => {
    router.delete(route('payees.destroy', deletingPayee.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            deletingPayee.value = null;
        },
    });
};

const getCategoryName = (categoryId) => {
    const cat = flatCategories.value.find(c => c.id === categoryId);
    return cat ? `${cat.icon || ''} ${cat.name}`.trim() : 'None';
};
</script>

<template>
    <Head title="Payees - Settings" />

    <AppLayout>
        <template #title>Payees</template>
        <template #header-left>
            <Link :href="route('settings.index')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <div class="p-4 space-y-4">
            <!-- Search -->
            <SearchField v-model="searchQuery" placeholder="Search payees..." />

            <!-- Payee Count -->
            <div class="text-sm font-semibold text-warning uppercase tracking-wide px-1">
                {{ filteredPayees.length }} payee{{ filteredPayees.length !== 1 ? 's' : '' }}
            </div>

            <!-- Payees List -->
            <div v-if="filteredPayees.length > 0" class="bg-surface rounded-card overflow-hidden">
                <div
                    v-for="payee in filteredPayees"
                    :key="payee.id"
                    class="flex items-center justify-between px-4 py-3 border-b border-border last:border-b-0"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-body">{{ payee.name }}</div>
                        <div class="text-sm text-subtle">
                            Default: {{ payee.default_category_name || 'None' }}
                            <span v-if="payee.transaction_count > 0" class="ml-2">
                                &middot; {{ payee.transaction_count }} transaction{{ payee.transaction_count !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                    <button
                        @click="openEditModal(payee)"
                        class="p-2 text-subtle hover:text-primary"
                    >
                        <span class="text-lg">›</span>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-surface rounded-card p-8 text-center">
                <div class="mb-4 flex justify-center">
                    <svg class="w-10 h-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-body mb-2">
                    {{ searchQuery ? 'No matches found' : 'No payees yet' }}
                </h3>
                <p class="text-subtle">
                    {{ searchQuery ? 'Try a different search term.' : 'Payees are created automatically when you add transactions.' }}
                </p>
            </div>

            <!-- Info Card -->
            <div class="bg-secondary/10 border border-secondary/20 rounded-card p-4 text-sm text-secondary">
                <p>
                    <strong>Tip:</strong> Setting a default category for a payee will auto-fill the category when you select that payee in a new transaction.
                </p>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" title="Edit Payee" @close="closeEditModal">
            <form @submit.prevent="savePayee">
                <div class="bg-surface mx-3 rounded-xl overflow-hidden">
                    <TextField
                        v-model="editForm.name"
                        label="Name"
                        placeholder="Payee name"
                        :error="editForm.errors.name"
                        required
                    />
                    <PickerField
                        v-model="editForm.default_category_id"
                        label="Default Category"
                        :options="categories"
                        placeholder="None"
                        grouped
                        group-items-key="categories"
                        :border-bottom="false"
                    />
                </div>

                <!-- Delete Button (only if no transactions) -->
                <div v-if="editingPayee?.transaction_count === 0" class="mx-3 mt-4">
                    <Button variant="ghost" class="w-full text-danger" @click="openDeleteConfirm(editingPayee); showEditModal = false;">
                        Delete Payee
                    </Button>
                </div>
            </form>

            <template #footer>
                <Button
                    type="button"
                    @click="savePayee"
                    :loading="editForm.processing"
                    full-width
                >
                    Save
                </Button>
            </template>
        </Modal>

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
                        <h3 class="text-lg font-semibold text-body">Delete Payee?</h3>
                        <p class="text-subtle">
                            Delete "{{ deletingPayee?.name }}"? This action cannot be undone.
                        </p>
                        <div class="flex gap-3">
                            <Button variant="secondary" @click="showDeleteConfirm = false" class="flex-1">
                                Cancel
                            </Button>
                            <Button variant="danger" @click="deletePayee" class="flex-1">
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
