<script setup>
import { computed, watch, onUnmounted } from 'vue';
import TutorialProgressBar from '@/Components/Tutorial/TutorialProgressBar.vue';
import TutorialBanner from '@/Components/Tutorial/TutorialBanner.vue';
import CoachBubble from '@/Components/Tutorial/CoachBubble.vue';

const props = defineProps({
    active: { type: Boolean, default: false },
    track: { type: String, required: true }, // 'learn' or 'setup'
    step: { type: String, required: true }, // current step ID
    steps: { type: Array, required: true }, // step definitions
});

const emit = defineEmits(['primary', 'secondary', 'exit']);

const currentStepIndex = computed(() => {
    return props.steps.findIndex(s => s.id === props.step);
});

const currentStepData = computed(() => {
    return props.steps[currentStepIndex.value] || null;
});

const currentStepNumber = computed(() => {
    return currentStepIndex.value + 1;
});

// Manage highlight class on target elements
let previousTarget = null;

function clearHighlight() {
    if (previousTarget) {
        previousTarget.classList.remove('tutorial-highlight');
        previousTarget = null;
    }
    // Remove all dimmed elements
    document.querySelectorAll('.tutorial-dim').forEach(el => {
        el.classList.remove('tutorial-dim');
    });
}

function applyHighlight(selector) {
    clearHighlight();

    if (!selector) return;

    const target = document.querySelector(selector);
    if (!target) return;

    target.classList.add('tutorial-highlight');
    previousTarget = target;
}

watch(
    () => [props.active, props.step],
    ([isActive]) => {
        if (isActive && currentStepData.value?.target) {
            // Wait a tick for DOM to settle
            requestAnimationFrame(() => {
                applyHighlight(currentStepData.value.target);
            });
        } else {
            clearHighlight();
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    clearHighlight();
});
</script>

<template>
    <template v-if="active && currentStepData">
        <!-- Dim backdrop -->
        <Teleport to="body">
            <div
                v-if="currentStepData.target"
                class="fixed inset-0 bg-black/50 z-40 pointer-events-none"
            />
        </Teleport>

        <!-- Progress bar at top -->
        <TutorialProgressBar
            :current-step="currentStepNumber"
            :total-steps="steps.length"
            :track="track"
            @exit="$emit('exit')"
        />

        <!-- Banner below header -->
        <TutorialBanner
            :track="track"
            @exit="$emit('exit')"
        />

        <!-- Coach bubble -->
        <CoachBubble
            :title="currentStepData.title"
            :message="currentStepData.message"
            :primary-action="currentStepData.primaryAction || 'Got It \u2192'"
            :secondary-action="currentStepData.secondaryAction"
            :current-step="currentStepNumber"
            :total-steps="steps.length"
            :action-only="currentStepData.actionOnly || false"
            @primary="$emit('primary')"
            @secondary="$emit('secondary')"
        />
    </template>
</template>
