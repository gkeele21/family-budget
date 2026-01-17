<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentRoute = computed(() => page.url);

const isActive = (path) => {
    return currentRoute.value.startsWith(path);
};
</script>

<template>
    <div class="min-h-screen bg-budget-background flex flex-col">
        <!-- Header -->
        <header class="bg-budget-header text-white px-4 py-3 flex items-center justify-between sticky top-0 z-50">
            <div class="flex items-center gap-3">
                <slot name="header-left" />
                <h1 class="text-lg font-semibold">
                    <slot name="title">Family Budget</slot>
                </h1>
            </div>
            <div class="flex items-center gap-2">
                <slot name="header-right" />
                <Link :href="route('settings.index')" class="p-2 hover:bg-white/10 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </Link>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto pb-20">
            <slot />
        </main>

        <!-- FAB (Floating Action Button) -->
        <slot name="fab" />

        <!-- Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-6 py-2 z-50">
            <div class="flex justify-around items-center max-w-md mx-auto">
                <Link
                    :href="route('dashboard')"
                    class="flex flex-col items-center py-2 px-4"
                    :class="isActive('/dashboard') ? 'text-budget-primary' : 'text-budget-text-secondary'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span class="text-xs mt-1 font-medium">Accounts</span>
                </Link>

                <Link
                    :href="route('budget.index')"
                    class="flex flex-col items-center py-2 px-4"
                    :class="isActive('/budget') ? 'text-budget-primary' : 'text-budget-text-secondary'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs mt-1 font-medium">Budget</span>
                </Link>

                <Link
                    :href="route('transactions.index')"
                    class="flex flex-col items-center py-2 px-4"
                    :class="isActive('/transactions') ? 'text-budget-primary' : 'text-budget-text-secondary'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="text-xs mt-1 font-medium">Transactions</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
