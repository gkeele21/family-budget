<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextField from '@/Components/Form/TextField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import Button from '@/Components/Base/Button.vue';

const currentStep = ref(1);
const totalSteps = 3;

const currentMonth = new Date().toISOString().slice(0, 7); // "2026-02"

const form = useForm({
    budget_name: '',
    start_month: currentMonth,
    account_name: '',
    account_type: 'checking',
    account_balance: '',
    use_template: 'starter',
});

const formatMonthLabel = (monthStr) => {
    const [year, month] = monthStr.split('-');
    const date = new Date(year, month - 1, 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const adjustStartMonth = (delta) => {
    const [year, month] = form.start_month.split('-').map(Number);
    const d = new Date(year, month - 1 + delta, 1);
    form.start_month = d.toISOString().slice(0, 7);
};

const canProceedStep1 = computed(() => form.budget_name.length > 0);
const canProceedStep2 = computed(() => true); // Account is optional
const canProceedStep3 = computed(() => true); // Template selection

const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submit = () => {
    form.post(route('onboarding.store'));
};

const stepConfig = computed(() => {
    const configs = {
        1: { colorVar: '--color-primary', colorClass: 'primary' },
        2: { colorVar: '--color-info', colorClass: 'info' },
        3: { colorVar: '--color-warning', colorClass: 'warning' },
    };
    return configs[currentStep.value];
});

const accountTypes = [
    { value: 'checking', label: 'Checking', icon: '🏦' },
    { value: 'savings', label: 'Savings', icon: '💰' },
    { value: 'credit_card', label: 'Credit Card', icon: '💳' },
    { value: 'cash', label: 'Cash', icon: '💵' },
];

const templates = [
    {
        value: 'starter',
        label: 'Starter Template',
        description: 'Common categories for bills, everyday spending, savings, and debt. About 15 categories to get you going.',
    },
    {
        value: 'none',
        label: 'Start Fresh',
        description: 'No categories — build your budget from scratch. Best if you know exactly what you want.',
    },
];
</script>

<template>
    <Head title="Setup" />

    <div class="min-h-screen bg-surface-header flex flex-col">
        <!-- Progress Bar -->
        <div class="bg-surface border-b border-border px-6 py-4" style="padding-top: max(1rem, env(safe-area-inset-top) + 0.5rem);">
            <div class="max-w-md mx-auto">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-body">Step {{ currentStep }} of {{ totalSteps }}</span>
                    <Link
                        :href="route('onboarding.skip')"
                        method="post"
                        as="button"
                        class="text-sm text-subtle hover:text-body"
                    >
                        Skip setup
                    </Link>
                </div>
                <div class="h-2 bg-border rounded-full overflow-hidden">
                    <div
                        class="h-full bg-primary transition-all duration-300"
                        :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
                    ></div>
                </div>
            </div>
        </div>
        <!-- Colored accent bar -->
        <div
            class="h-1 transition-all duration-300"
            :style="{ background: `linear-gradient(90deg, rgb(var(${stepConfig.colorVar})), transparent)` }"
        ></div>

        <!-- Step Content -->
        <div class="flex-1 flex flex-col items-center px-6 py-8">
            <div class="w-full max-w-md">
                <!-- Step 1: Budget Name -->
                <div v-if="currentStep === 1" class="space-y-6">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center flex-shrink-0"
                            :style="{ border: `2px solid rgb(var(${stepConfig.colorVar}))` }"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" :style="{ stroke: `rgb(var(${stepConfig.colorVar}))` }" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-body mb-0.5">Name your budget</h2>
                            <p class="text-sm text-subtle">Choose a name. You can change this later.</p>
                        </div>
                    </div>

                    <div class="bg-surface rounded-card overflow-hidden">
                        <TextField
                            v-model="form.budget_name"
                            label="Budget Name"
                            placeholder="e.g., My Budget"
                            variant="subtle"
                            :border-bottom="false"
                            :error="form.errors.budget_name"
                        />
                    </div>

                    <!-- Start Month -->
                    <div>
                        <div class="text-xs font-semibold text-subtle uppercase tracking-wide mb-2 px-1">
                            Start Month
                        </div>
                        <div class="bg-surface rounded-card p-4 flex items-center justify-between">
                            <button type="button" @click="adjustStartMonth(-1)" class="p-2 rounded-full hover:bg-surface-overlay">
                                <svg class="w-5 h-5 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <span class="font-medium text-body">{{ formatMonthLabel(form.start_month) }}</span>
                            <button type="button" @click="adjustStartMonth(1)" class="p-2 rounded-full hover:bg-surface-overlay">
                                <svg class="w-5 h-5 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-subtle mt-1 px-1">When should your budget history begin?</p>
                    </div>
                </div>

                <!-- Step 2: First Account -->
                <div v-if="currentStep === 2" class="space-y-6">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center flex-shrink-0"
                            :style="{ border: `2px solid rgb(var(${stepConfig.colorVar}))` }"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" :style="{ stroke: `rgb(var(${stepConfig.colorVar}))` }" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-body mb-0.5">Add your first account</h2>
                            <p class="text-sm text-subtle">Add a checking, savings, or credit card.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Account Name & Balance -->
                        <div class="bg-surface rounded-card overflow-hidden">
                            <TextField
                                v-model="form.account_name"
                                label="Account Name"
                                placeholder="e.g., Main Checking"
                                variant="subtle"
                            />
                            <AmountField
                                v-model="form.account_balance"
                                label="Current Balance"
                                :color-by-type="false"
                                placeholder="0.00"
                                :border-bottom="false"
                            />
                        </div>

                        <!-- Account Type -->
                        <div>
                            <div class="text-xs font-semibold text-subtle uppercase tracking-wide mb-2 px-1">
                                Account Type
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    v-for="type in accountTypes"
                                    :key="type.value"
                                    type="button"
                                    @click="form.account_type = type.value"
                                    class="flex flex-col items-center p-3 rounded-xl border-2 transition-colors bg-surface"
                                    :class="form.account_type === type.value
                                        ? 'border-primary bg-primary/10'
                                        : 'border-border'"
                                >
                                    <span class="text-2xl mb-1">{{ type.icon }}</span>
                                    <span
                                        :class="[
                                            'text-xs font-semibold',
                                            form.account_type === type.value ? 'text-primary' : 'text-subtle'
                                        ]"
                                    >{{ type.label }}</span>
                                </button>
                            </div>
                        </div>

                        <p class="text-sm text-subtle text-center">
                            You can skip this step and add accounts later.
                        </p>
                    </div>
                </div>

                <!-- Step 3: Category Template -->
                <div v-if="currentStep === 3" class="space-y-6">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="w-14 h-14 rounded-full flex items-center justify-center flex-shrink-0"
                            :style="{ border: `2px solid rgb(var(${stepConfig.colorVar}))` }"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" :style="{ stroke: `rgb(var(${stepConfig.colorVar}))` }" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-body mb-0.5">Choose your categories</h2>
                            <p class="text-sm text-subtle">Start with a template. Customize anytime.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button
                            v-for="template in templates"
                            :key="template.value"
                            type="button"
                            @click="form.use_template = template.value"
                            class="w-full text-left p-4 border-2 rounded-card transition-colors bg-surface"
                            :class="form.use_template === template.value
                                ? 'border-warning bg-warning/5'
                                : 'border-border hover:border-border-strong'"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-semibold text-body">{{ template.label }}</span>
                                <div
                                    v-if="form.use_template === template.value"
                                    class="w-5 h-5 bg-warning rounded-full flex items-center justify-center"
                                >
                                    <svg class="w-3 h-3 text-inverse" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-sm text-subtle">{{ template.description }}</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="bg-surface border-t border-border px-6 py-4">
            <div class="max-w-md mx-auto flex gap-3">
                <Button
                    v-if="currentStep > 1"
                    variant="secondary"
                    @click="prevStep"
                    class="flex-1"
                    size="lg"
                >
                    Back
                </Button>

                <Button
                    v-if="currentStep < totalSteps"
                    @click="nextStep"
                    :disabled="(currentStep === 1 && !canProceedStep1) || (currentStep === 2 && !canProceedStep2)"
                    class="flex-1"
                    size="lg"
                >
                    Continue
                </Button>

                <Button
                    v-if="currentStep === totalSteps"
                    @click="submit"
                    :loading="form.processing"
                    class="flex-1"
                    size="lg"
                >
                    Get Started
                </Button>
            </div>
        </div>
    </div>
</template>
