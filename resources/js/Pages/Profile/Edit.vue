<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import Button from '@/Components/Base/Button.vue';

const user = usePage().props.auth.user;

// Profile form
const profileForm = useForm({
    name: user.name,
    username: user.username,
});

const profileSaved = ref(false);

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        onSuccess: () => {
            profileSaved.value = true;
            window.setTimeout(() => profileSaved.value = false, 2000);
        },
    });
};

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const passwordSaved = ref(false);

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            passwordSaved.value = true;
            window.setTimeout(() => passwordSaved.value = false, 2000);
        },
    });
};

// Delete account
const showDeleteModal = ref(false);
const deletePasswordInput = ref(null);
const deleteForm = useForm({
    password: '',
});

const confirmDelete = () => {
    showDeleteModal.value = true;
    nextTick(() => deletePasswordInput.value?.focus());
};

const deleteAccount = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onFinish: () => deleteForm.reset(),
    });
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteForm.clearErrors();
    deleteForm.reset();
};
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <template #title>Profile</template>
        <template #header-left>
            <Link :href="route('settings.index')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <div class="p-4 space-y-6">
            <!-- Profile Information -->
            <div class="bg-surface rounded-card p-4">
                <h2 class="text-lg font-semibold text-body mb-1">Profile Information</h2>
                <p class="text-sm text-subtle mb-4">Update your name and username.</p>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-body mb-1">Name</label>
                        <input
                            id="name"
                            v-model="profileForm.name"
                            type="text"
                            class="w-full px-4 py-3 bg-surface-inset rounded-lg border-0 text-body focus:outline-none"
                            required
                        />
                        <p v-if="profileForm.errors.name" class="text-danger text-sm mt-1">{{ profileForm.errors.name }}</p>
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-body mb-1">Username</label>
                        <input
                            id="username"
                            v-model="profileForm.username"
                            type="text"
                            class="w-full px-4 py-3 bg-surface-inset rounded-lg border-0 text-body focus:outline-none"
                            required
                        />
                        <p v-if="profileForm.errors.username" class="text-danger text-sm mt-1">{{ profileForm.errors.username }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button type="submit" :loading="profileForm.processing">
                            Save
                        </Button>
                        <span v-if="profileSaved" class="text-sm text-success">Saved!</span>
                    </div>
                </form>
            </div>

            <!-- Update Password -->
            <div class="bg-surface rounded-card p-4">
                <h2 class="text-lg font-semibold text-body mb-1">Update Password</h2>
                <p class="text-sm text-subtle mb-4">Use a strong, unique password to keep your account secure.</p>

                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-body mb-1">Current Password</label>
                        <input
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            class="w-full px-4 py-3 bg-surface-inset rounded-lg border-0 text-body focus:outline-none"
                            autocomplete="current-password"
                        />
                        <p v-if="passwordForm.errors.current_password" class="text-danger text-sm mt-1">{{ passwordForm.errors.current_password }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-body mb-1">New Password</label>
                        <input
                            id="password"
                            v-model="passwordForm.password"
                            type="password"
                            class="w-full px-4 py-3 bg-surface-inset rounded-lg border-0 text-body focus:outline-none"
                            autocomplete="new-password"
                        />
                        <p v-if="passwordForm.errors.password" class="text-danger text-sm mt-1">{{ passwordForm.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-body mb-1">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="w-full px-4 py-3 bg-surface-inset rounded-lg border-0 text-body focus:outline-none"
                            autocomplete="new-password"
                        />
                        <p v-if="passwordForm.errors.password_confirmation" class="text-danger text-sm mt-1">{{ passwordForm.errors.password_confirmation }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button type="submit" :loading="passwordForm.processing">
                            Update Password
                        </Button>
                        <span v-if="passwordSaved" class="text-sm text-success">Updated!</span>
                    </div>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="bg-surface rounded-card p-4">
                <h2 class="text-lg font-semibold text-body mb-1">Delete Account</h2>
                <p class="text-sm text-subtle mb-4">
                    Once your account is deleted, all of its data will be permanently removed. Please download any data you wish to keep before deleting.
                </p>

                <Button variant="danger" @click="confirmDelete">
                    Delete Account
                </Button>
            </div>
        </div>

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
                    v-if="showDeleteModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                    @click.self="closeDeleteModal"
                >
                    <div class="w-full max-w-sm bg-surface rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-body">Delete Account?</h3>
                        <p class="text-subtle text-sm">
                            This action cannot be undone. All your data will be permanently deleted.
                            Enter your password to confirm.
                        </p>

                        <div>
                            <input
                                ref="deletePasswordInput"
                                v-model="deleteForm.password"
                                type="password"
                                placeholder="Password"
                                class="w-full px-4 py-3 bg-surface-inset rounded-lg border-0 text-body focus:outline-none"
                                @keyup.enter="deleteAccount"
                            />
                            <p v-if="deleteForm.errors.password" class="text-danger text-sm mt-1">{{ deleteForm.errors.password }}</p>
                        </div>

                        <div class="flex gap-3">
                            <Button variant="secondary" @click="closeDeleteModal" class="flex-1">
                                Cancel
                            </Button>
                            <Button variant="danger" @click="deleteAccount" :loading="deleteForm.processing" class="flex-1">
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
