<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    maxHeight: { type: String, default: '85vh' },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

// Track visual viewport to handle mobile keyboard
const viewportHeight = ref(window.innerHeight);
const viewportOffsetTop = ref(0);

const updateViewport = () => {
    const vv = window.visualViewport;
    if (vv) {
        viewportHeight.value = vv.height;
        viewportOffsetTop.value = vv.offsetTop;
    }
};

watch(() => props.show, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : null;
    if (isOpen && window.visualViewport) {
        updateViewport();
        window.visualViewport.addEventListener('resize', updateViewport);
        window.visualViewport.addEventListener('scroll', updateViewport);
    } else if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', updateViewport);
        window.visualViewport.removeEventListener('scroll', updateViewport);
    }
});

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
    if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', updateViewport);
        window.visualViewport.removeEventListener('scroll', updateViewport);
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed left-0 right-0 z-50 flex items-end justify-center"
                :style="{ top: viewportOffsetTop + 'px', height: viewportHeight + 'px' }"
                @click.self="close"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50" @click="close" />

                <!-- Sheet -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform translate-y-full"
                    enter-to-class="transform translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="transform translate-y-0"
                    leave-to-class="transform translate-y-full"
                >
                    <div
                        v-if="show"
                        class="relative w-full bg-surface rounded-t-2xl flex flex-col"
                        :style="{ maxHeight: (viewportHeight * 0.85) + 'px' }"
                    >
                        <!-- Handle -->
                        <div class="flex justify-center pt-3 pb-1">
                            <div class="w-10 h-1 bg-border-strong rounded-full" />
                        </div>

                        <!-- Header -->
                        <div v-if="title || $slots.header" class="px-4 pb-3 border-b border-border">
                            <slot name="header">
                                <h3 class="text-lg font-semibold text-body text-center">{{ title }}</h3>
                            </slot>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div v-if="$slots.footer" class="border-t border-border p-4 safe-area-bottom">
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: max(16px, env(safe-area-inset-bottom));
}
</style>
