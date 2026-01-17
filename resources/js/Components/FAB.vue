<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        default: null,
    },
});

defineEmits(['click']);
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        @click="$emit('click')"
        class="fixed bottom-24 right-4 w-14 h-14 bg-gradient-to-br from-budget-primary to-budget-primary-light rounded-full shadow-lg flex items-center justify-center text-white z-40 hover:shadow-xl transition-shadow"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
    </component>
</template>
