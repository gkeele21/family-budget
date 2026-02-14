<script setup>
import ApplicationLogo from '@/Components/Domain/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Welcome back',
    },
});
</script>

<template>
    <div class="min-h-screen bg-surface-header flex flex-col">
        <!-- Dark Header with Logo Glow -->
        <div class="relative px-6 pt-10 pb-20 text-center flex flex-col items-center" style="padding-top: max(2.5rem, env(safe-area-inset-top) + 1rem);">
            <!-- Green radial glow behind logo -->
            <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(ellipse at 50% 50%, rgb(var(--color-primary) / 0.08) 0%, transparent 60%);"></div>

            <Link href="/" class="relative" style="filter: drop-shadow(0 0 24px rgb(var(--color-primary) / 0.2));">
                <ApplicationLogo size="lg" />
            </Link>
            <h1 class="text-2xl font-bold text-body mt-3">{{ title }}</h1>
        </div>

        <!-- White Curved Section -->
        <div class="flex-1 bg-surface -mt-8 rounded-t-3xl px-6 pt-8 pb-6">
            <slot />
        </div>
    </div>
</template>
