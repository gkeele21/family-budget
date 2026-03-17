<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Calculator from '@/Components/Domain/Calculator.vue';

const showCalculator = ref(false);

const page = usePage();
const currentRoute = computed(() => page.url);

const isActive = (path) => {
    return currentRoute.value.startsWith(path);
};
</script>

<template>
    <div class="h-dvh bg-bg flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-surface-header text-body px-4 py-0 flex items-center justify-between flex-shrink-0" style="padding-top: var(--safe-area-inset-top, 0px)">
            <div class="flex items-center gap-3 py-1.5">
                <slot name="header-left" />
                <img src="/images/logo.png" alt="Budget Guy" class="h-11 w-11" />
                <h1 class="text-lg font-semibold">
                    <slot name="title">Budget Guy</slot>
                </h1>
            </div>
            <div class="flex items-center gap-1">
                <slot name="header-right" />
                <button @click="showCalculator = true" class="p-2 hover:bg-white/10 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </button>
                <Link :href="route('settings.index')" class="p-2 hover:bg-white/10 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </Link>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 min-h-0 overflow-auto relative">
            <slot />
            <!-- FAB (Floating Action Button) -->
            <slot name="fab" />
        </main>

        <!-- Bottom Navigation -->
        <nav class="bg-surface-header border-t border-border px-4 py-2 flex-shrink-0" style="padding-bottom: max(0.5rem, var(--safe-area-inset-bottom))">
            <div class="flex justify-around items-center max-w-md mx-auto">
                <Link
                    :href="route('dashboard')"
                    class="flex flex-col items-center py-2 px-2"
                    :class="isActive('/dashboard') ? 'text-primary' : 'text-subtle'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span class="text-xs mt-1 font-medium">Accounts</span>
                </Link>

                <Link
                    :href="route('budget.index')"
                    class="flex flex-col items-center py-2 px-2"
                    :class="isActive('/budget') ? 'text-primary' : 'text-subtle'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs mt-1 font-medium">Budget</span>
                </Link>

                <Link
                    :href="route('transactions.index')"
                    class="flex flex-col items-center py-2 px-2"
                    :class="isActive('/transactions') ? 'text-primary' : 'text-subtle'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m0-6H9a2 2 0 00-2 2v12l3.5-2 3.5 2 3.5-2 3.5 2V6a2 2 0 00-2-2h-2" />
                    </svg>
                    <span class="text-xs mt-1 font-medium">Transactions</span>
                </Link>

                <Link
                    :href="route('plan.index')"
                    class="flex flex-col items-center py-2 px-2"
                    :class="isActive('/plan') ? 'text-primary' : 'text-subtle'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs mt-1 font-medium">Plan</span>
                </Link>
            </div>
        </nav>
        <Calculator :show="showCalculator" @close="showCalculator = false" />
    </div>
</template>
