<script setup>
defineProps({
    padding: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div
        class="bg-budget-card rounded-card shadow-sm"
        :class="{ 'p-4': padding }"
    >
        <slot />
    </div>
</template>
