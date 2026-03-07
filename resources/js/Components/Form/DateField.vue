<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import FormRow from './FormRow.vue';
import BottomSheet from '@/Components/Base/BottomSheet.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'Date' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    borderBottom: { type: Boolean, default: true },
    min: { type: String, default: null },
    max: { type: String, default: null },
    clearable: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'clear']);

const isOpen = ref(false);

// Temporary values while picker is open
const tempMonth = ref(0);
const tempDay = ref(1);
const tempYear = ref(new Date().getFullYear());

// Refs for wheel containers
const monthWheelRef = ref(null);
const dayWheelRef = ref(null);
const yearWheelRef = ref(null);

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const currentYear = new Date().getFullYear();
const years = computed(() => {
    const start = currentYear - 5;
    const end = currentYear + 10;
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const daysInMonth = computed(() => {
    return new Date(tempYear.value, tempMonth.value + 1, 0).getDate();
});

const days = computed(() => {
    return Array.from({ length: daysInMonth.value }, (_, i) => i + 1);
});

// Adjust day if it exceeds days in selected month
watch([tempMonth, tempYear], () => {
    if (tempDay.value > daysInMonth.value) {
        tempDay.value = daysInMonth.value;
    }
});

const openPicker = () => {
    if (props.disabled) return;

    // Initialize from current value or today
    if (props.modelValue) {
        const date = new Date(props.modelValue + 'T00:00:00');
        tempMonth.value = date.getMonth();
        tempDay.value = date.getDate();
        tempYear.value = date.getFullYear();
    } else {
        const today = new Date();
        tempMonth.value = today.getMonth();
        tempDay.value = today.getDate();
        tempYear.value = today.getFullYear();
    }

    isOpen.value = true;

    // Wait for BottomSheet (Teleport + Transition) to render wheel elements
    nextTick(() => {
        requestAnimationFrame(() => {
            scrollToSelected();
        });
    });
};

const closePicker = () => {
    isOpen.value = false;
};

const confirm = () => {
    const month = String(tempMonth.value + 1).padStart(2, '0');
    const day = String(tempDay.value).padStart(2, '0');
    const dateString = `${tempYear.value}-${month}-${day}`;
    emit('update:modelValue', dateString);
    closePicker();
};

const ITEM_HEIGHT = 44;

const scrollToSelected = () => {
    if (monthWheelRef.value) {
        const monthIndex = tempMonth.value;
        monthWheelRef.value.scrollTop = monthIndex * ITEM_HEIGHT;
    }
    if (dayWheelRef.value) {
        const dayIndex = tempDay.value - 1;
        dayWheelRef.value.scrollTop = dayIndex * ITEM_HEIGHT;
    }
    if (yearWheelRef.value) {
        const yearIndex = years.value.indexOf(tempYear.value);
        yearWheelRef.value.scrollTop = yearIndex * ITEM_HEIGHT;
    }
};

const handleScroll = (wheel, type) => {
    const scrollTop = wheel.scrollTop;
    const index = Math.round(scrollTop / ITEM_HEIGHT);

    if (type === 'month') {
        const clampedIndex = Math.max(0, Math.min(index, 11));
        tempMonth.value = clampedIndex;
    } else if (type === 'day') {
        const clampedIndex = Math.max(0, Math.min(index, daysInMonth.value - 1));
        tempDay.value = clampedIndex + 1;
    } else if (type === 'year') {
        const clampedIndex = Math.max(0, Math.min(index, years.value.length - 1));
        tempYear.value = years.value[clampedIndex];
    }
};

const snapToItem = (wheel, type) => {
    let index;
    if (type === 'month') {
        index = tempMonth.value;
    } else if (type === 'day') {
        index = tempDay.value - 1;
    } else if (type === 'year') {
        index = years.value.indexOf(tempYear.value);
    }

    wheel.scrollTo({
        top: index * ITEM_HEIGHT,
        behavior: 'smooth'
    });
};

const selectItem = (type, value) => {
    if (type === 'month') {
        tempMonth.value = value;
        nextTick(() => {
            if (monthWheelRef.value) {
                monthWheelRef.value.scrollTo({
                    top: value * ITEM_HEIGHT,
                    behavior: 'smooth'
                });
            }
        });
    } else if (type === 'day') {
        tempDay.value = value;
        nextTick(() => {
            if (dayWheelRef.value) {
                dayWheelRef.value.scrollTo({
                    top: (value - 1) * ITEM_HEIGHT,
                    behavior: 'smooth'
                });
            }
        });
    } else if (type === 'year') {
        tempYear.value = value;
        nextTick(() => {
            if (yearWheelRef.value) {
                const index = years.value.indexOf(value);
                yearWheelRef.value.scrollTo({
                    top: index * ITEM_HEIGHT,
                    behavior: 'smooth'
                });
            }
        });
    }
};

// Format date for display
const displayValue = computed(() => {
    if (!props.modelValue) return null;

    const date = new Date(props.modelValue + 'T00:00:00');
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    today.setHours(0, 0, 0, 0);
    yesterday.setHours(0, 0, 0, 0);

    if (date.getTime() === today.getTime()) {
        return `Today, ${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
    } else if (date.getTime() === yesterday.getTime()) {
        return `Yesterday, ${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
    }

    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
});

// Format the currently selected temp date for the confirm button
const tempDisplayValue = computed(() => {
    const date = new Date(tempYear.value, tempMonth.value, tempDay.value);
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
});
</script>

<template>
    <FormRow :label="label" :border-bottom="borderBottom" :error="error">
        <button
            type="button"
            @click="openPicker"
            :disabled="disabled"
            class="flex items-center gap-1 text-sm font-medium"
            :class="[
                displayValue ? 'text-secondary' : 'text-subtle',
                disabled ? 'opacity-50' : '',
            ]"
        >
            <span>{{ displayValue || 'Select date' }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-subtle shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <button
            v-if="clearable && modelValue"
            type="button"
            @click.stop="emit('update:modelValue', ''); emit('clear')"
            class="ml-2 text-subtle text-lg leading-none"
        >
            ×
        </button>
    </FormRow>

    <BottomSheet :show="isOpen" :title="label" @close="closePicker">
        <div class="relative">
            <!-- Selection highlight bar -->
            <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 h-11 bg-surface-overlay rounded-lg pointer-events-none z-0" />

            <!-- Wheels container -->
            <div class="flex relative z-10" style="height: 220px;">
                <!-- Month wheel -->
                <div
                    ref="monthWheelRef"
                    class="flex-1 overflow-y-auto scroll-smooth hide-scrollbar"
                    style="scroll-snap-type: y mandatory;"
                    @scroll="handleScroll($event.target, 'month')"
                    @scrollend="snapToItem($event.target, 'month')"
                >
                    <!-- Top padding -->
                    <div style="height: 88px;" />

                    <div
                        v-for="(month, index) in months"
                        :key="month"
                        class="flex items-center justify-center cursor-pointer transition-all duration-150"
                        :class="[
                            index === tempMonth ? 'text-body font-semibold text-lg' : 'text-subtle text-base'
                        ]"
                        style="height: 44px; scroll-snap-align: center;"
                        @click="selectItem('month', index)"
                    >
                        {{ month }}
                    </div>

                    <!-- Bottom padding -->
                    <div style="height: 88px;" />
                </div>

                <!-- Day wheel -->
                <div
                    ref="dayWheelRef"
                    class="w-16 overflow-y-auto scroll-smooth hide-scrollbar"
                    style="scroll-snap-type: y mandatory;"
                    @scroll="handleScroll($event.target, 'day')"
                    @scrollend="snapToItem($event.target, 'day')"
                >
                    <!-- Top padding -->
                    <div style="height: 88px;" />

                    <div
                        v-for="day in days"
                        :key="day"
                        class="flex items-center justify-center cursor-pointer transition-all duration-150"
                        :class="[
                            day === tempDay ? 'text-body font-semibold text-lg' : 'text-subtle text-base'
                        ]"
                        style="height: 44px; scroll-snap-align: center;"
                        @click="selectItem('day', day)"
                    >
                        {{ day }}
                    </div>

                    <!-- Bottom padding -->
                    <div style="height: 88px;" />
                </div>

                <!-- Year wheel -->
                <div
                    ref="yearWheelRef"
                    class="w-20 overflow-y-auto scroll-smooth hide-scrollbar"
                    style="scroll-snap-type: y mandatory;"
                    @scroll="handleScroll($event.target, 'year')"
                    @scrollend="snapToItem($event.target, 'year')"
                >
                    <!-- Top padding -->
                    <div style="height: 88px;" />

                    <div
                        v-for="year in years"
                        :key="year"
                        class="flex items-center justify-center cursor-pointer transition-all duration-150"
                        :class="[
                            year === tempYear ? 'text-body font-semibold text-lg' : 'text-subtle text-base'
                        ]"
                        style="height: 44px; scroll-snap-align: center;"
                        @click="selectItem('year', year)"
                    >
                        {{ year }}
                    </div>

                    <!-- Bottom padding -->
                    <div style="height: 88px;" />
                </div>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                @click="confirm"
                class="w-full bg-primary text-body py-3 rounded-xl font-semibold"
            >
                Select {{ tempDisplayValue }}
            </button>
        </template>
    </BottomSheet>
</template>

<style scoped>
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>