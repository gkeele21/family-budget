<script setup>
import Button from '@/Components/Base/Button.vue';

defineProps({
    title: { type: String, required: true },
    message: { type: String, required: true },
    primaryAction: { type: String, required: true },
    secondaryAction: { type: String, default: null },
    currentStep: { type: Number, required: true },
    totalSteps: { type: Number, required: true },
    actionOnly: { type: Boolean, default: false },
});

defineEmits(['primary', 'secondary']);
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-0 left-0 right-0 z-50 px-3 pb-3 safe-area-bottom">
            <div class="bg-surface rounded-2xl border border-border shadow-lg overflow-hidden">
                <!-- Header: Avatar + Name + Step -->
                <div class="flex items-center gap-2.5 px-4 pt-3.5">
                    <div class="w-10 h-10 rounded-full border-2 border-primary flex-shrink-0 overflow-hidden">
                        <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-body">Budget Guy</div>
                        <div class="text-xs text-subtle">{{ title }}</div>
                    </div>
                </div>

                <!-- Message -->
                <div class="px-4 pt-3 pb-4">
                    <p class="text-sm text-muted leading-relaxed" v-html="message" />
                </div>

                <!-- Actions -->
                <div class="flex gap-2 px-4 pb-3.5">
                    <Button
                        v-if="!actionOnly && secondaryAction"
                        variant="ghost"
                        size="sm"
                        :full-width="true"
                        @click="$emit('secondary')"
                    >
                        {{ secondaryAction }}
                    </Button>
                    <Button
                        variant="primary"
                        size="sm"
                        :full-width="true"
                        @click="$emit('primary')"
                    >
                        {{ primaryAction }}
                    </Button>
                </div>

                <!-- Progress Dots -->
                <div class="flex gap-0.5 px-4 pb-3.5">
                    <div
                        v-for="step in totalSteps"
                        :key="step"
                        class="flex-1 h-[3px] rounded-full"
                        :class="{
                            'bg-primary': step < currentStep,
                            'bg-warning': step === currentStep,
                            'bg-border': step > currentStep,
                        }"
                    />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: max(12px, env(safe-area-inset-bottom));
}
</style>
