<script setup>
defineProps({
    amount: {
        type: Number,
        required: true,
    },
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};
</script>

<template>
    <div class="bg-gradient-to-br from-budget-primary to-budget-primary-light rounded-card p-4 text-white">
        <div class="text-sm font-medium text-budget-header/80">Available to Budget</div>
        <div class="text-3xl font-bold font-mono mt-1">
            {{ formatCurrency(amount) }}
        </div>
    </div>
</template>
