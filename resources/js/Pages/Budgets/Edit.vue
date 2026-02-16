<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import TextField from '@/Components/Form/TextField.vue';
import AmountField from '@/Components/Form/AmountField.vue';
import Button from '@/Components/Base/Button.vue';

const props = defineProps({
    budget: Object,
});

const form = useForm({
    name: props.budget.name,
    start_month: props.budget.start_month || new Date().toISOString().slice(0, 7),
    default_monthly_income: props.budget.default_monthly_income || '',
});

const formatMonthLabel = (monthStr) => {
    if (!monthStr) return 'Not set';
    const [year, month] = monthStr.split('-');
    const date = new Date(year, month - 1, 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const adjustStartMonth = (delta) => {
    const [year, month] = form.start_month.split('-').map(Number);
    const d = new Date(year, month - 1 + delta, 1);
    form.start_month = d.toISOString().slice(0, 7);
};

const submit = () => {
    form.put(route('budgets.update-budget'));
};
</script>

<template>
    <Head title="Edit Budget" />

    <AppLayout>
        <template #title>Edit Budget</template>

        <div class="p-4 space-y-4">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="bg-surface rounded-card overflow-hidden">
                    <TextField
                        v-model="form.name"
                        label="Budget Name"
                        placeholder="e.g., My Budget"
                        variant="subtle"
                        :error="form.errors.name"
                        required
                    />
                    <AmountField
                        v-model="form.default_monthly_income"
                        label="Monthly Income"
                        color="text-secondary"
                        placeholder="0.00"
                        :border-bottom="false"
                    />
                </div>

                <p class="text-xs text-subtle px-1">
                    Monthly income is optional and helps with budget planning.
                </p>

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
                    <p class="text-xs text-subtle mt-1 px-1">Controls how far back you can navigate in the budget view.</p>
                </div>

                <Button
                    type="submit"
                    :loading="form.processing"
                    full-width
                    size="lg"
                >
                    Save
                </Button>
            </form>
        </div>
    </AppLayout>
</template>
