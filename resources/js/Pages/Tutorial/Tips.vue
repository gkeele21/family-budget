<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineOptions({ layout: AppLayout });

const search = ref('');

const groups = [
    {
        label: 'Budgeting Basics',
        tips: [
            { slug: 'ready-to-assign', emoji: '💰', title: 'What is "Ready to Assign"?' },
            { slug: 'how-envelopes-work', emoji: '📦', title: 'How do envelopes work?' },
            { slug: 'overspending', emoji: '🔴', title: 'What if I overspend?' },
            { slug: 'budgeted-vs-available', emoji: '🔢', title: 'Budgeted vs. Available — what\'s the difference?' },
        ],
    },
    {
        label: 'Common Tasks',
        tips: [
            { slug: 'move-money', emoji: '🔄', title: 'Moving money between categories' },
            { slug: 'transfers', emoji: '↔️', title: 'Transfers between accounts' },
            { slug: 'split-transactions', emoji: '✂️', title: 'Splitting a transaction' },
            { slug: 'recurring-transactions', emoji: '🔁', title: 'Setting up recurring transactions' },
        ],
    },
    {
        label: 'Tips & Tricks',
        tips: [
            { slug: 'voice-transactions', emoji: '🎤', title: 'Using voice to add transactions' },
            { slug: 'plan-projections', emoji: '📊', title: 'Using the Plan view for projections' },
            { slug: 'credit-cards', emoji: '💳', title: 'How to handle credit cards' },
        ],
    },
];

const filteredGroups = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return groups;

    return groups
        .map(group => ({
            ...group,
            tips: group.tips.filter(tip => tip.title.toLowerCase().includes(q)),
        }))
        .filter(group => group.tips.length > 0);
});
</script>

<template>
    <Head title="Quick Tips" />

    <!-- Search Bar -->
    <div class="px-4 py-3">
        <div class="relative">
            <svg
                class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-subtle"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                v-model="search"
                type="text"
                placeholder="Search tips..."
                class="w-full pl-10 pr-4 py-3 bg-surface-inset border border-border rounded-xl text-body text-sm placeholder-subtle focus:outline-none focus-glow"
            />
        </div>
    </div>

    <!-- Topic Groups -->
    <div class="px-4 pb-6 space-y-5">
        <div v-for="group in filteredGroups" :key="group.label">
            <div class="text-xs font-semibold text-subtle uppercase tracking-wide px-1 mb-2">
                {{ group.label }}
            </div>
            <div class="bg-surface rounded-xl overflow-hidden border border-border">
                <Link
                    v-for="(tip, index) in group.tips"
                    :key="tip.slug"
                    :href="`/tutorial/tips/${tip.slug}`"
                    class="flex items-center justify-between p-4 hover:bg-surface-overlay active:bg-surface-overlay transition-colors"
                    :class="{ 'border-b border-border': index < group.tips.length - 1 }"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xl flex-shrink-0">{{ tip.emoji }}</span>
                        <span class="text-sm text-body truncate">{{ tip.title }}</span>
                    </div>
                    <span class="text-subtle text-lg flex-shrink-0 ml-2">›</span>
                </Link>
            </div>
        </div>

        <div v-if="filteredGroups.length === 0" class="text-center py-8">
            <p class="text-sm text-muted">No tips found for "{{ search }}"</p>
        </div>
    </div>
</template>
