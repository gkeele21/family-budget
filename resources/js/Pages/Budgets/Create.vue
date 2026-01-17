<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    default_monthly_income: '',
});

const submit = () => {
    form.post(route('budgets.store'));
};
</script>

<template>
    <Head title="Create Budget" />

    <div class="min-h-screen bg-budget-background flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-budget-card rounded-card p-6 shadow-lg">
                <div class="text-center mb-6">
                    <div class="text-4xl mb-2">💰</div>
                    <h1 class="text-2xl font-bold text-budget-header">Create Your Budget</h1>
                    <p class="text-budget-text-secondary mt-2">Let's set up your first budget to start tracking your finances.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-budget-text mb-1">
                            Budget Name
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g., Family Budget, Personal Budget"
                            class="w-full px-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                            required
                        />
                        <p v-if="form.errors.name" class="text-budget-expense text-sm mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="income" class="block text-sm font-medium text-budget-text mb-1">
                            Expected Monthly Income (optional)
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-budget-text-secondary">$</span>
                            <input
                                id="income"
                                v-model="form.default_monthly_income"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-card focus:ring-2 focus:ring-budget-primary focus:border-transparent"
                            />
                        </div>
                        <p class="text-budget-text-secondary text-sm mt-1">This helps with budget planning.</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 bg-gradient-to-r from-budget-primary to-budget-primary-light text-white font-semibold rounded-card hover:shadow-lg transition-shadow disabled:opacity-50"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Budget' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
