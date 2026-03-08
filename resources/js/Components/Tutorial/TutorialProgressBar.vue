<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentStep: { type: Number, required: true },
    totalSteps: { type: Number, required: true },
    track: { type: String, required: true }, // 'learn' or 'setup'
});

defineEmits(['exit']);

const progressPercent = computed(() => {
    if (props.totalSteps === 0) return 0;
    return (props.currentStep / props.totalSteps) * 100;
});

const progressColorClass = computed(() => {
    return props.track === 'learn' ? 'bg-success' : 'bg-info';
});
</script>

<template>
    <div class="bg-surface-header border-b border-border flex items-center gap-3 px-3 py-2">
        <!-- Close button -->
        <button
            type="button"
            class="w-8 h-8 flex items-center justify-center rounded-full text-subtle hover:bg-surface-overlay transition-colors min-h-0"
            @click="$emit('exit')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Progress track -->
        <div class="flex-1 h-1 bg-border rounded-full overflow-hidden">
            <div
                class="h-full rounded-full transition-all duration-300"
                :class="progressColorClass"
                :style="{ width: progressPercent + '%' }"
            />
        </div>

        <!-- Step counter -->
        <span class="text-xs text-subtle font-medium whitespace-nowrap">
            {{ currentStep }} / {{ totalSteps }}
        </span>
    </div>
</template>
