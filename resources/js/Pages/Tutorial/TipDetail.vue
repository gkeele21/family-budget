<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { tips as allTips } from '@/Config/tipContent.js';

defineOptions({ layout: AppLayout });

const props = defineProps({
    slug: String,
    tip: Object,
});

const tipData = computed(() => props.tip || allTips[props.slug] || {});

const relatedTipsList = computed(() => {
    if (!tipData.value.relatedTips) return [];
    return tipData.value.relatedTips
        .map(slug => {
            const t = allTips[slug];
            return t ? { slug, emoji: t.emoji, title: t.title } : null;
        })
        .filter(Boolean);
});
</script>

<template>
    <Head :title="tipData.title || 'Tip'" />

    <div class="pb-6">
        <!-- Back Button -->
        <div class="px-4 py-3">
            <Link href="/tutorial/tips" class="inline-flex items-center gap-1 text-sm text-muted">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Tips
            </Link>
        </div>

        <!-- Header -->
        <div class="px-6 pb-4 text-center">
            <div class="text-4xl mb-2">{{ tipData.emoji }}</div>
            <h1 class="text-xl font-bold text-body">{{ tipData.title }}</h1>
        </div>

        <!-- Mascot Tip -->
        <div v-if="tipData.mascotTip" class="px-4 mb-4">
            <div class="bg-info/10 border border-info/20 rounded-xl p-4 flex gap-3">
                <img
                    src="/images/Avatar.png"
                    alt="Budget Guy"
                    class="w-10 h-10 rounded-full border border-info/30 flex-shrink-0"
                />
                <p class="text-sm text-body leading-relaxed">{{ tipData.mascotTip }}</p>
            </div>
        </div>

        <!-- Content -->
        <div v-if="tipData.content" class="px-6 mb-4">
            <div class="text-sm text-muted leading-relaxed space-y-2 [&_p]:mb-2 [&_strong]:text-body [&_em]:text-body" v-html="tipData.content" />
        </div>

        <!-- Formula Box -->
        <div v-if="tipData.formula" class="px-4 mb-4">
            <div class="bg-surface rounded-xl p-4 border border-border">
                <div class="font-mono text-sm space-y-1">
                    <div v-for="line in tipData.formula.lines" :key="line" class="text-muted">
                        {{ line }}
                    </div>
                    <div class="border-t border-border pt-1 mt-1 font-semibold text-body">
                        {{ tipData.formula.result }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Box -->
        <div v-if="tipData.warning" class="px-4 mb-4">
            <div class="bg-warning/10 border border-warning/20 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-warning flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm font-semibold text-warning">{{ tipData.warning.title }}</span>
                </div>
                <p class="text-sm text-muted">{{ tipData.warning.text }}</p>
            </div>
        </div>

        <!-- Related Tips -->
        <div v-if="relatedTipsList.length > 0" class="px-4 mt-6">
            <div class="text-xs font-semibold text-subtle uppercase tracking-wide px-1 mb-2">
                Related Tips
            </div>
            <div class="bg-surface rounded-xl overflow-hidden border border-border">
                <Link
                    v-for="(related, index) in relatedTipsList"
                    :key="related.slug"
                    :href="`/tutorial/tips/${related.slug}`"
                    class="flex items-center justify-between p-4 hover:bg-surface-overlay active:bg-surface-overlay transition-colors"
                    :class="{ 'border-b border-border': index < relatedTipsList.length - 1 }"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xl flex-shrink-0">{{ related.emoji }}</span>
                        <span class="text-sm text-body truncate">{{ related.title }}</span>
                    </div>
                    <span class="text-subtle text-lg flex-shrink-0 ml-2">›</span>
                </Link>
            </div>
        </div>
    </div>
</template>
