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

            <!-- Envelope Budgeting badge -->
            <span class="mt-3 inline-block bg-primary/15 text-primary text-[0.7rem] font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                Envelope Budgeting
            </span>

            <p class="text-muted text-sm mt-2">Simple budgeting for real life</p>
        </div>

        <!-- Surface Body with Rounded Top -->
        <div class="flex-1 bg-surface -mt-3 rounded-t-3xl px-6 pt-6 pb-6 flex flex-col">
            <!-- Feature Cards -->
            <div class="flex-1 space-y-3">
                <!-- Card 1 - Plan -->
                <div class="bg-surface-elevated rounded-xl p-4 border-l-[3px] border-primary flex items-center gap-3.5">
                    <div class="w-10 h-10 bg-primary/12 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">Plan before you spend</p>
                        <p class="text-xs text-subtle">Budget with intention</p>
                    </div>
                </div>

                <!-- Card 2 - Share -->
                <div class="bg-surface-elevated rounded-xl p-4 border-l-[3px] border-info flex items-center gap-3.5">
                    <div class="w-10 h-10 bg-info/12 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">Share with family</p>
                        <p class="text-xs text-subtle">Budget together, stay aligned</p>
                    </div>
                </div>

                <!-- Card 3 - Track -->
                <div class="bg-surface-elevated rounded-xl p-4 border-l-[3px] border-warning flex items-center gap-3.5">
                    <div class="w-10 h-10 bg-warning/12 rounded-[10px] flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-body text-sm">See where it goes</p>
                        <p class="text-xs text-subtle">Track spending in real-time</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="space-y-3 mt-5">
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
