<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import BottomSheet from '@/Components/Base/BottomSheet.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const display = ref('0');
const previousValue = ref(null);
const operator = ref(null);
const waitingForOperand = ref(false);
const history = ref('');

const formattedDisplay = computed(() => {
    const num = parseFloat(display.value);
    if (isNaN(num)) return display.value;
    // Show up to 10 decimal places, but trim trailing zeros
    if (display.value.includes('.') && waitingForOperand.value === false && display.value.endsWith('.')) {
        return formatNumber(display.value.slice(0, -1)) + '.';
    }
    if (display.value.includes('.') && !waitingForOperand.value) {
        const parts = display.value.split('.');
        return formatNumber(parts[0]) + '.' + parts[1];
    }
    return formatNumber(num);
});

function formatNumber(num) {
    const n = typeof num === 'string' ? parseFloat(num) : num;
    if (isNaN(n)) return '0';
    return n.toLocaleString('en-US', { maximumFractionDigits: 10 });
}

function inputDigit(digit) {
    if (waitingForOperand.value) {
        display.value = String(digit);
        waitingForOperand.value = false;
    } else {
        display.value = display.value === '0' ? String(digit) : display.value + digit;
    }
}

function inputDecimal() {
    if (waitingForOperand.value) {
        display.value = '0.';
        waitingForOperand.value = false;
        return;
    }
    if (!display.value.includes('.')) {
        display.value += '.';
    }
}

function clear() {
    display.value = '0';
    previousValue.value = null;
    operator.value = null;
    waitingForOperand.value = false;
    history.value = '';
}

function toggleSign() {
    const num = parseFloat(display.value);
    if (num !== 0) {
        display.value = String(-num);
    }
}

function inputPercent() {
    const num = parseFloat(display.value);
    display.value = String(num / 100);
}

function performOperation(nextOperator) {
    const current = parseFloat(display.value);

    if (previousValue.value !== null && !waitingForOperand.value) {
        const result = calculate(previousValue.value, current, operator.value);
        display.value = String(result);
        previousValue.value = result;
        history.value = formatNumber(result) + ' ' + getOperatorSymbol(nextOperator);
    } else {
        previousValue.value = current;
        history.value = formatNumber(current) + ' ' + getOperatorSymbol(nextOperator);
    }

    operator.value = nextOperator;
    waitingForOperand.value = true;
}

function equals() {
    if (previousValue.value === null || operator.value === null) return;

    const current = parseFloat(display.value);
    const result = calculate(previousValue.value, current, operator.value);

    history.value = formatNumber(previousValue.value) + ' ' + getOperatorSymbol(operator.value) + ' ' + formatNumber(current) + ' =';
    display.value = String(result);
    previousValue.value = null;
    operator.value = null;
    waitingForOperand.value = true;
}

function calculate(a, b, op) {
    switch (op) {
        case '+': return a + b;
        case '-': return a - b;
        case '*': return a * b;
        case '/': return b !== 0 ? a / b : 0;
        default: return b;
    }
}

function getOperatorSymbol(op) {
    switch (op) {
        case '+': return '+';
        case '-': return '−';
        case '*': return '×';
        case '/': return '÷';
        default: return '';
    }
}

const isActiveOperator = (op) => operator.value === op && waitingForOperand.value;

const btnBase = 'flex items-center justify-center rounded-xl font-semibold active:scale-95 transition-transform select-none';

function handleKeydown(e) {
    if (e.key >= '0' && e.key <= '9') {
        e.preventDefault();
        inputDigit(parseInt(e.key));
    } else if (e.key === '.') {
        e.preventDefault();
        inputDecimal();
    } else if (e.key === '+') {
        e.preventDefault();
        performOperation('+');
    } else if (e.key === '-') {
        e.preventDefault();
        performOperation('-');
    } else if (e.key === '*') {
        e.preventDefault();
        performOperation('*');
    } else if (e.key === '/') {
        e.preventDefault();
        performOperation('/');
    } else if (e.key === 'Enter' || e.key === '=') {
        e.preventDefault();
        equals();
    } else if (e.key === 'Escape') {
        e.preventDefault();
        clear();
    } else if (e.key === 'Backspace') {
        e.preventDefault();
        if (!waitingForOperand.value && display.value.length > 1) {
            display.value = display.value.slice(0, -1);
        } else {
            display.value = '0';
        }
    } else if (e.key === '%') {
        e.preventDefault();
        inputPercent();
    }
}

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        window.addEventListener('keydown', handleKeydown);
    } else {
        window.removeEventListener('keydown', handleKeydown);
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <BottomSheet :show="show" title="Calculator" @close="emit('close')">
        <div class="px-4 pb-4 pt-2">
            <!-- Display -->
            <div class="bg-surface-inset rounded-xl p-4 mb-4">
                <div class="text-right text-xs text-subtle h-5 font-mono">{{ history }}</div>
                <div class="text-right text-3xl font-bold text-body font-mono truncate">{{ formattedDisplay }}</div>
            </div>

            <!-- Button Grid -->
            <div class="grid grid-cols-4 gap-2">
                <!-- Row 1: AC, +/-, %, ÷ -->
                <button :class="[btnBase, 'h-14 bg-surface-inset text-body text-lg']" @click="clear">AC</button>
                <button :class="[btnBase, 'h-14 bg-surface-inset text-body text-lg']" @click="toggleSign">+/−</button>
                <button :class="[btnBase, 'h-14 bg-surface-inset text-body text-lg']" @click="inputPercent">%</button>
                <button
                    :class="[btnBase, 'h-14 text-xl', isActiveOperator('/') ? 'bg-white text-primary' : 'bg-primary text-white']"
                    @click="performOperation('/')"
                >÷</button>

                <!-- Row 2: 7, 8, 9, × -->
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(7)">7</button>
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(8)">8</button>
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(9)">9</button>
                <button
                    :class="[btnBase, 'h-14 text-xl', isActiveOperator('*') ? 'bg-white text-primary' : 'bg-primary text-white']"
                    @click="performOperation('*')"
                >×</button>

                <!-- Row 3: 4, 5, 6, − -->
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(4)">4</button>
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(5)">5</button>
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(6)">6</button>
                <button
                    :class="[btnBase, 'h-14 text-xl', isActiveOperator('-') ? 'bg-white text-primary' : 'bg-primary text-white']"
                    @click="performOperation('-')"
                >−</button>

                <!-- Row 4: 1, 2, 3, + -->
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(1)">1</button>
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(2)">2</button>
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDigit(3)">3</button>
                <button
                    :class="[btnBase, 'h-14 text-xl', isActiveOperator('+') ? 'bg-white text-primary' : 'bg-primary text-white']"
                    @click="performOperation('+')"
                >+</button>

                <!-- Row 5: 0 (wide), ., = -->
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border col-span-2']" @click="inputDigit(0)">0</button>
                <button :class="[btnBase, 'h-14 bg-surface text-body text-xl border border-border']" @click="inputDecimal">.</button>
                <button :class="[btnBase, 'h-14 bg-success text-white text-xl']" @click="equals">=</button>
            </div>
        </div>
    </BottomSheet>
</template>
