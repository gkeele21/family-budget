<script setup>
import { computed } from 'vue';

const props = defineProps({
    track: { type: String, required: true }, // 'learn' or 'setup'
});

defineEmits(['exit']);

const isLearn = computed(() => props.track === 'learn');

const bannerClasses = computed(() => {
    return isLearn.value
        ? 'bg-warning/10 border-b border-warning/20'
        : 'bg-info/10 border-b border-info/20';
});

const bannerText = computed(() => {
    return isLearn.value ? 'Tutorial Budget' : 'Setup Guide Active';
});

const bannerEmoji = computed(() => {
    return isLearn.value ? '\u{1F4DA}' : '\u{1F680}';
});

const exitText = computed(() => {
    return isLearn.value ? 'Exit tutorial' : 'Exit guide';
});
</script>

<template>
    <div :class="bannerClasses" class="flex items-center justify-between px-4 py-2">
        <span class="text-sm font-medium text-body">
            {{ bannerEmoji }} {{ bannerText }}
        </span>
        <button
            type="button"
            class="text-xs text-muted underline min-h-0"
            @click="$emit('exit')"
        >
            {{ exitText }}
        </button>
    </div>
</template>
