<script setup>
import { ref } from 'vue';

const props = defineProps({
    actionWidth: { type: Number, default: 80 },
});

const emit = defineEmits(['action', 'swipe-open']);

const offsetX = ref(0);
const isAnimating = ref(false);
const isOpen = ref(false);

let startX = 0;
let startY = 0;
let isSwiping = false;
let directionDecided = false;
let justSwiped = false;

const onTouchStart = (e) => {
    const touch = e.touches[0];
    startX = touch.clientX;
    startY = touch.clientY;
    isSwiping = false;
    directionDecided = false;
    isAnimating.value = false;
};

const onTouchMove = (e) => {
    const touch = e.touches[0];
    const deltaX = touch.clientX - startX;
    const deltaY = touch.clientY - startY;

    if (!directionDecided) {
        const absDX = Math.abs(deltaX);
        const absDY = Math.abs(deltaY);
        if (absDX < 8 && absDY < 8) return;
        directionDecided = true;
        if (absDY > absDX) {
            // Vertical scroll — abort swipe handling
            return;
        }
        isSwiping = true;
    }

    if (!isSwiping) return;

    e.preventDefault();
    // Clamp between -actionWidth and 0 (or allow small overscroll from open position)
    const baseOffset = isOpen.value ? -props.actionWidth : 0;
    const raw = baseOffset + deltaX;
    offsetX.value = Math.max(-props.actionWidth, Math.min(0, raw));
};

const onTouchEnd = () => {
    if (!isSwiping) {
        // If we were open and user taps (no swipe), close it
        if (isOpen.value) {
            close();
        }
        return;
    }

    isAnimating.value = true;
    justSwiped = true;
    setTimeout(() => { justSwiped = false; }, 100);

    // Snap open if past 40% threshold, otherwise snap closed
    if (Math.abs(offsetX.value) > props.actionWidth * 0.4) {
        offsetX.value = -props.actionWidth;
        isOpen.value = true;
        emit('swipe-open');
    } else {
        offsetX.value = 0;
        isOpen.value = false;
    }
};

const close = () => {
    isAnimating.value = true;
    offsetX.value = 0;
    isOpen.value = false;
};

const preventClickDuringSwipe = (e) => {
    if (justSwiped || isSwiping) {
        e.preventDefault();
        e.stopImmediatePropagation();
    }
};

const onAction = () => {
    emit('action');
    close();
};

defineExpose({ reset: close });
</script>

<template>
    <div class="relative overflow-hidden rounded-card">
        <!-- Delete action revealed behind -->
        <div
            class="absolute inset-y-0 right-0 flex items-center justify-center bg-expense"
            :style="{ width: actionWidth + 'px' }"
            @click.stop="onAction"
        >
            <span class="text-inverse font-semibold text-sm">Delete</span>
        </div>

        <!-- Sliding content layer -->
        <div
            :style="{
                transform: `translateX(${offsetX}px)`,
                transition: isAnimating ? 'transform 0.2s ease-out' : 'none',
            }"
            @touchstart.passive="onTouchStart"
            @touchmove="onTouchMove"
            @touchend="onTouchEnd"
            @click.capture="preventClickDuringSwipe"
            @transitionend="isAnimating = false"
        >
            <slot />
        </div>
    </div>
</template>
