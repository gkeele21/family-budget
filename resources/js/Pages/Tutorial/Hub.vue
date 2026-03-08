<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({
    hasCompletedLearn: Boolean,
    hasCompletedSetup: Boolean,
});

const cards = [
    {
        key: 'learn',
        emoji: '🎓',
        iconClasses: 'bg-success/15 border border-success/30',
        title: 'Learn Budgeting',
        subtitle: 'Try it with sample data',
        description: 'Practice with a pre-built budget. Learn how envelope budgeting works by assigning money, recording transactions, and handling overspending.',
        tag: 'Interactive',
        tagClasses: 'bg-success/15 text-success',
        time: '~5 minutes',
        action: () => router.post('/tutorial/learn'),
    },
    {
        key: 'setup',
        emoji: '🚀',
        iconClasses: 'bg-info/15 border border-info/30',
        title: 'Set Up My Budget',
        subtitle: "I'm ready to start for real",
        description: "I'll walk you through creating your accounts, categories, and first month's budget step by step.",
        tag: 'Guided Setup',
        tagClasses: 'bg-info/15 text-info',
        time: '~10 minutes',
        action: () => router.post('/tutorial/setup'),
    },
    {
        key: 'tips',
        emoji: '💡',
        iconClasses: 'bg-warning/15 border border-warning/30',
        title: 'Quick Tips',
        subtitle: 'Browse help topics',
        description: 'Browse answers to common questions about budgeting, categories, transfers, and more.',
        tag: 'Reference',
        tagClasses: 'bg-warning/15 text-warning',
        time: 'Browse anytime',
        action: () => router.visit('/tutorial/tips'),
    },
];

const isCompleted = (key) => {
    if (key === 'learn') return props.hasCompletedLearn;
    if (key === 'setup') return props.hasCompletedSetup;
    return false;
};
</script>

<template>
    <Head title="Get Started" />

    <!-- Hero Section -->
    <div class="pt-8 pb-6 px-6 text-center">
        <div class="mx-auto mb-4 w-[100px] h-[100px] rounded-full border-2 border-primary overflow-hidden shadow-[0_0_20px_rgba(var(--color-primary),0.3)]">
            <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
        </div>
        <h2 class="text-xl font-bold text-body mb-1">Welcome to Budget Guy!</h2>
        <p class="text-sm text-muted">
            I'm here to help you take control of your money. How would you like to get started?
        </p>
    </div>

    <!-- Tutorial Cards -->
    <div class="flex flex-col gap-3 px-4 pb-4">
        <button
            v-for="card in cards"
            :key="card.key"
            @click="card.action()"
            class="w-full text-left bg-surface rounded-xl p-5 border border-border active:opacity-80 transition-opacity"
        >
            <div class="flex items-center gap-3 mb-2">
                <div
                    class="w-11 h-11 rounded-lg flex items-center justify-center text-xl flex-shrink-0"
                    :class="card.iconClasses"
                >
                    {{ card.emoji }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-body">{{ card.title }}</div>
                    <div class="text-xs text-muted">{{ card.subtitle }}</div>
                </div>
                <div
                    v-if="isCompleted(card.key)"
                    class="flex items-center gap-1 text-xs font-medium text-success flex-shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Completed
                </div>
            </div>
            <p class="text-sm text-muted mb-3">{{ card.description }}</p>
            <div class="flex items-center gap-2">
                <span
                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                    :class="card.tagClasses"
                >
                    {{ card.tag }}
                </span>
                <span class="text-xs text-subtle">{{ card.time }}</span>
            </div>
        </button>
    </div>

    <!-- Skip Link -->
    <div class="text-center pb-6">
        <Link href="/budget" class="text-sm text-muted underline">
            Skip — I know what I'm doing
        </Link>
    </div>
</template>
