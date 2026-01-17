<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    budgetName: String,
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Head title="Settings" />

    <AppLayout>
        <template #title>Settings</template>

        <div class="p-4 space-y-6">
            <!-- Budget Section -->
            <div>
                <h2 class="text-sm font-semibold text-budget-text-secondary uppercase tracking-wide px-1 mb-2">
                    Budget
                </h2>
                <div class="bg-budget-card rounded-card divide-y divide-gray-100">
                    <Link
                        :href="route('settings.accounts')"
                        class="flex items-center justify-between p-4 hover:bg-gray-50"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-xl">🏦</span>
                            <span class="text-budget-text">Accounts</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-budget-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <Link
                        :href="route('settings.categories')"
                        class="flex items-center justify-between p-4 hover:bg-gray-50"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-xl">📁</span>
                            <span class="text-budget-text">Categories</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-budget-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Account Section -->
            <div>
                <h2 class="text-sm font-semibold text-budget-text-secondary uppercase tracking-wide px-1 mb-2">
                    Account
                </h2>
                <div class="bg-budget-card rounded-card divide-y divide-gray-100">
                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center justify-between p-4 hover:bg-gray-50"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-xl">👤</span>
                            <div>
                                <div class="text-budget-text">{{ user.name }}</div>
                                <div class="text-sm text-budget-text-secondary">{{ user.email }}</div>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-budget-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <button
                        @click="logout"
                        class="w-full flex items-center gap-3 p-4 hover:bg-gray-50 text-left"
                    >
                        <span class="text-xl">🚪</span>
                        <span class="text-budget-expense">Sign Out</span>
                    </button>
                </div>
            </div>

            <!-- Current Budget Info -->
            <div class="text-center text-budget-text-secondary text-sm py-4">
                <p>Current Budget: <span class="font-medium text-budget-text">{{ budgetName }}</span></p>
            </div>
        </div>
    </AppLayout>
</template>
