<script setup>
import { ref } from 'vue';
import BottomSheet from '@/Components/Base/BottomSheet.vue';
import Button from '@/Components/Base/Button.vue';

defineProps({
    title: { type: String, required: true },
    content: { type: String, required: true },
    tipSlug: { type: String, default: null },
});

const isOpen = ref(false);
</script>

<template>
    <button
        type="button"
        class="w-[18px] h-[18px] rounded-full bg-surface-overlay text-subtle text-xs font-bold flex items-center justify-center min-h-0 shrink-0"
        @click="isOpen = true"
    >
        ?
    </button>

    <BottomSheet :show="isOpen" @close="isOpen = false">
        <div class="p-4">
            <!-- Header with avatar -->
            <div class="flex items-center gap-2.5 mb-3">
                <div class="w-10 h-10 rounded-full border-2 border-primary flex-shrink-0 overflow-hidden">
                    <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                </div>
                <h3 class="text-base font-semibold text-body">{{ title }}</h3>
            </div>

            <!-- Content -->
            <p class="text-sm text-muted leading-relaxed mb-4">{{ content }}</p>

            <!-- Actions -->
            <div class="flex gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    :full-width="true"
                    @click="isOpen = false"
                >
                    Got it
                </Button>
                <Button
                    v-if="tipSlug"
                    variant="primary"
                    size="sm"
                    :full-width="true"
                    :href="`/tutorial/tips/${tipSlug}`"
                >
                    Learn More &rarr;
                </Button>
            </div>
        </div>
    </BottomSheet>
</template>
