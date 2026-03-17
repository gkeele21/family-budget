<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/Domain/ApplicationLogo.vue';
import Button from '@/Components/Base/Button.vue';
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-surface-header flex flex-col">
        <!-- Dark Top Section with Logo + Glow -->
        <div class="relative px-6 pt-10 pb-10 text-center flex flex-col items-center" style="padding-top: max(2.5rem, env(safe-area-inset-top) + 1rem);">
            <!-- Green radial glow behind logo -->
            <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(ellipse at 50% 60%, rgb(var(--color-primary) / 0.1) 0%, transparent 60%);"></div>

            <div class="relative" style="filter: drop-shadow(0 0 30px rgb(var(--color-primary) / 0.25));">
                <ApplicationLogo size="lg" />
            </div>

            <p class="text-muted text-sm mt-3">Budgeting made simple</p>
        </div>

        <!-- Surface Body with Rounded Top -->
        <div class="flex-1 bg-surface -mt-3 rounded-t-3xl px-6 pt-6 pb-6 flex flex-col">
            <!-- Feature Cards -->
            <div class="flex-1 space-y-2.5">
                <!-- Envelope Budgeting -->
                <div class="bg-surface-elevated rounded-xl p-3.5 border-l-[3px] border-primary flex items-center gap-3.5">
                    <div class="w-9 h-9 bg-primary/12 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-[18px] h-[18px] text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">Envelope budgeting</p>
                        <p class="text-xs text-subtle">Allocate your income to categories and see exactly what's left to spend</p>
                    </div>
                </div>

                <!-- Voice Transactions -->
                <div class="bg-surface-elevated rounded-xl p-3.5 border-l-[3px] border-info flex items-center gap-3.5">
                    <div class="w-9 h-9 bg-info/12 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-[18px] h-[18px] text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">Voice transactions</p>
                        <p class="text-xs text-subtle">Just say what you spent and let AI handle the rest</p>
                    </div>
                </div>

                <!-- Plan Ahead -->
                <div class="bg-surface-elevated rounded-xl p-3.5 border-l-[3px] border-warning flex items-center gap-3.5">
                    <div class="w-9 h-9 bg-warning/12 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-[18px] h-[18px] text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">Plan ahead</p>
                        <p class="text-xs text-subtle">Project future months without touching your current budget</p>
                    </div>
                </div>

                <!-- Recurring & Splits -->
                <div class="bg-surface-elevated rounded-xl p-3.5 border-l-[3px] border-muted flex items-center gap-3.5">
                    <div class="w-9 h-9 bg-body/8 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-[18px] h-[18px] text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">Recurring & splits</p>
                        <p class="text-xs text-subtle">Set up repeating transactions and divide purchases across multiple categories</p>
                    </div>
                </div>

                <!-- Budget Together -->
                <div class="bg-surface-elevated rounded-xl p-3.5 border-l-[3px] border-danger flex items-center gap-3.5">
                    <div class="w-9 h-9 bg-danger/12 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-[18px] h-[18px] text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">Budget together</p>
                        <p class="text-xs text-subtle">Invite family and manage your budget from any device</p>
                    </div>
                </div>

                <!-- Guided Tutorials -->
                <div class="bg-surface-elevated rounded-xl p-3.5 border-l-[3px] border-white flex items-center gap-3.5">
                    <div class="w-9 h-9 bg-body/8 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-[18px] h-[18px] text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">Guided tutorials</p>
                        <p class="text-xs text-subtle">Learn as you go with interactive walkthroughs and tips</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="flex flex-col gap-4 mt-5">
                <Link :href="route('register')">
                    <Button class="w-full" size="lg">Create Account</Button>
                </Link>
                <Link :href="route('login')">
                    <Button variant="outline" class="w-full" size="lg">Sign In</Button>
                </Link>
            </div>
        </div>
    </div>
</template>
