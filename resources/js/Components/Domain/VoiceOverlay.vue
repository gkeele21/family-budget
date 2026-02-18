<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useSpeechRecognition } from '@/Composables/useSpeechRecognition.js';
import Button from '@/Components/Base/Button.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'created']);

const { isListening, transcript, interimTranscript, error: speechError, start, stop, abort, reset, onEnd } = useSpeechRecognition();

// Overlay states: idle, listening, processing, clarification, review, creating, error, success
const state = ref('idle');
const errorMessage = ref('');
const createdTransactions = ref([]);
const batchId = ref(null);

// Accumulated review items: { data: {...}, display: {...}, id: string }
const reviewItems = ref([]);

// Silence timeout — auto-close if nothing heard for 5s, or auto-stop after 5s pause
let silenceTimer = null;

function startSilenceTimer() {
    clearSilenceTimer();
    silenceTimer = setTimeout(() => {
        if (state.value !== 'listening') return;

        if (transcript.value || interimTranscript.value) {
            // User spoke then went silent — auto-finish
            stop();
        } else {
            // Never spoke at all — show error
            abort();
            state.value = 'error';
            errorMessage.value = "I didn't hear anything. Try again?";
        }
    }, 5000);
}

function clearSilenceTimer() {
    if (silenceTimer) {
        clearTimeout(silenceTimer);
        silenceTimer = null;
    }
}

// Restart silence timer on speech activity
watch([transcript, interimTranscript], () => {
    if (state.value === 'listening') {
        startSilenceTimer();
    }
});

// Clarification state
const clarifications = ref([]);
const sessionContext = ref('');
const currentClarificationIndex = ref(0);
const clarificationAnswers = ref([]);

// Start listening when overlay opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        startListening();
    } else {
        // Reset everything when closed
        clearSilenceTimer();
        state.value = 'idle';
        errorMessage.value = '';
        createdTransactions.value = [];
        batchId.value = null;
        reviewItems.value = [];
        clarifications.value = [];
        sessionContext.value = '';
        currentClarificationIndex.value = 0;
        clarificationAnswers.value = [];
        reset();
    }
});

// Handle escape key — don't close if user has accumulated data
const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        if (state.value === 'review' || state.value === 'creating') return;
        if (state.value === 'error' && reviewItems.value.length > 0) {
            backToReview();
            return;
        }
        handleClose();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

// When speech ends, send to backend
onEnd(async (finalTranscript) => {
    if (!finalTranscript.trim()) {
        state.value = 'error';
        errorMessage.value = "I didn't hear anything. Try again?";
        return;
    }
    await sendToBackend(finalTranscript);
});

function startListening() {
    state.value = 'listening';
    reset();
    start();
    startSilenceTimer();
}

async function sendToBackend(text) {
    state.value = 'processing';

    try {
        const { data } = await window.axios.post(route('transactions.voice.parse'), {
            transcript: text,
            preview: true,
        });

        if (data.status === 'previewed') {
            const newItems = data.transactions.map(tx => ({
                ...tx,
                id: crypto.randomUUID(),
            }));
            reviewItems.value.push(...newItems);
            state.value = 'review';
        } else if (data.status === 'clarification_needed') {
            state.value = 'clarification';
            clarifications.value = data.clarifications;
            sessionContext.value = data.session_context;
            currentClarificationIndex.value = 0;
            clarificationAnswers.value = [];
        } else {
            state.value = 'error';
            errorMessage.value = data.message || "Couldn't understand that.";
        }
    } catch (e) {
        state.value = 'error';
        errorMessage.value = 'Something went wrong. Please try again.';
    }
}

async function selectClarification(option) {
    const current = clarifications.value[currentClarificationIndex.value];

    clarificationAnswers.value.push({
        transaction_index: current.transaction_index,
        field: current.field,
        value: option.id,
    });

    // More clarifications to go?
    if (currentClarificationIndex.value < clarifications.value.length - 1) {
        currentClarificationIndex.value++;
        return;
    }

    // All answered — send to backend in preview mode
    state.value = 'processing';

    try {
        const { data } = await window.axios.post(route('transactions.voice.clarify'), {
            session_context: sessionContext.value,
            answers: clarificationAnswers.value,
            preview: true,
        });

        if (data.status === 'previewed') {
            const newItems = data.transactions.map(tx => ({
                ...tx,
                id: crypto.randomUUID(),
            }));
            reviewItems.value.push(...newItems);
            state.value = 'review';
        } else {
            state.value = 'error';
            errorMessage.value = data.message || 'Something went wrong.';
        }
    } catch (e) {
        state.value = 'error';
        errorMessage.value = 'Something went wrong. Please try again.';
    }
}

async function createAll() {
    state.value = 'creating';

    try {
        const { data } = await window.axios.post(route('transactions.voice.batch-create'), {
            transactions: reviewItems.value.map(item => item.data),
        });

        if (data.status === 'created') {
            createdTransactions.value = data.transactions;
            batchId.value = data.batch_id;
            state.value = 'success';

            setTimeout(() => {
                emit('created', {
                    transactions: data.transactions,
                    batchId: data.batch_id,
                });
            }, 1200);
        } else {
            state.value = 'error';
            errorMessage.value = data.message || 'Failed to create transactions.';
        }
    } catch (e) {
        state.value = 'error';
        errorMessage.value = 'Something went wrong. Please try again.';
    }
}

function removeReviewItem(itemId) {
    reviewItems.value = reviewItems.value.filter(item => item.id !== itemId);
    if (reviewItems.value.length === 0) {
        handleClose();
    }
}

function addMore() {
    startListening();
}

function backToReview() {
    state.value = 'review';
    errorMessage.value = '';
}

function handleClose() {
    if (isListening.value) {
        abort();
    }
    emit('close');
}

function finishListening() {
    stop();
}

function tryAgain() {
    startListening();
}

const currentClarification = () => clarifications.value[currentClarificationIndex.value];

const hasAccumulatedItems = () => reviewItems.value.length > 0;

const isServiceError = () => errorMessage.value.includes('AI service') || errorMessage.value.includes('credits');

// Display text: show interim while listening, final when done
const displayTranscript = () => {
    if (interimTranscript.value) {
        return transcript.value
            ? `${transcript.value} ${interimTranscript.value}`
            : interimTranscript.value;
    }
    return transcript.value;
};

// Format transcript with line breaks before dollar amounts for readability
const formattedTranscript = () => {
    if (!transcript.value) return '';
    return transcript.value.replace(/\s+(?=\$\d)/g, '\n');
};

// Format date like the Transactions Index page
const formatDate = (dateStr) => {
    const date = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) return 'Today';
    if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

// Group review items by date, sorted newest first
const groupedReviewItems = computed(() => {
    const groups = {};
    for (const item of reviewItems.value) {
        const date = item.display.date;
        if (!groups[date]) groups[date] = [];
        groups[date].push(item);
    }
    return Object.entries(groups)
        .sort(([a], [b]) => b.localeCompare(a))
        .map(([date, items]) => ({ date, label: formatDate(date), items }));
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
                class="fixed inset-0 z-50 flex items-center justify-center p-6"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/75"
                    @click="(state === 'listening' || (state === 'error' && !hasAccumulatedItems())) ? handleClose() : null"
                />

                <!-- LISTENING STATE -->
                <div v-if="state === 'listening'" class="relative w-full max-w-sm bg-surface rounded-2xl p-8 text-center">
                    <!-- Budget Guy avatar with pulse -->
                    <div class="relative w-[88px] h-[88px] mx-auto mb-4">
                        <div class="avatar-pulse-ring"></div>
                        <div class="avatar-pulse-ring avatar-pulse-ring-2"></div>
                        <div class="avatar-pulse-ring avatar-pulse-ring-3"></div>
                        <div class="w-[72px] h-[72px] rounded-full absolute top-2 left-2 overflow-hidden shadow-[0_0_0_3px_rgb(var(--color-primary)/0.5)] z-[2]">
                            <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                        </div>
                        <div class="avatar-mic-indicator">
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </div>
                    </div>

                    <p class="text-body font-semibold mb-2">Budget Guy is listening...</p>
                    <div class="bg-surface-inset rounded-2xl rounded-bl-sm px-3.5 py-2.5 mx-2 mb-3 text-left relative">
                        <div class="absolute -top-1.5 left-5 w-3 h-3 bg-surface-inset rotate-45 rounded-sm"></div>
                        <p v-if="displayTranscript()" class="text-body text-sm italic">
                            "{{ displayTranscript() }}"
                        </p>
                        <p v-else class="text-subtle text-sm">
                            Try: "I spent $45 at Shell on gas"
                        </p>
                    </div>
                    <Button
                        v-if="displayTranscript()"
                        variant="primary"
                        full-width
                        @click="finishListening"
                    >
                        Done
                    </Button>
                    <p class="text-subtle text-xs mt-3">Tap outside to cancel</p>
                </div>

                <!-- PROCESSING STATE -->
                <div v-else-if="state === 'processing'" class="relative w-full max-w-sm bg-surface rounded-2xl p-8 text-center">
                    <div class="relative w-[88px] h-[88px] mx-auto mb-3">
                        <div class="w-[72px] h-[72px] rounded-full absolute top-2 left-2 overflow-hidden shadow-[0_0_0_3px_rgb(var(--color-primary)/0.2)] z-[2] avatar-bob">
                            <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                        </div>
                    </div>
                    <div class="inline-flex gap-1.5 bg-surface-inset rounded-2xl px-4 py-2.5 mb-3">
                        <div class="thinking-dot"></div>
                        <div class="thinking-dot"></div>
                        <div class="thinking-dot"></div>
                    </div>
                    <p class="text-body font-semibold mb-1">Budget Guy is on it...</p>
                    <p v-if="transcript" class="text-muted text-sm italic">
                        "{{ transcript }}"
                    </p>
                </div>

                <!-- REVIEW STATE (accumulated transactions) -->
                <div v-else-if="state === 'review'" class="relative w-full max-w-sm bg-surface rounded-2xl p-6">
                    <!-- Header -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-11 h-11 rounded-full overflow-hidden flex-shrink-0 shadow-[0_0_0_2px_rgb(var(--color-primary)/0.3)]">
                            <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                        </div>
                        <div>
                            <p class="text-body font-semibold text-sm">
                                {{ reviewItems.length }} transaction{{ reviewItems.length !== 1 ? 's' : '' }} ready
                            </p>
                            <p class="text-muted text-xs">Add more or create them all</p>
                        </div>
                    </div>

                    <!-- Transaction list grouped by date -->
                    <div class="space-y-3 max-h-60 overflow-y-auto mb-4">
                        <div v-for="group in groupedReviewItems" :key="group.date" class="space-y-1.5">
                            <h3 class="text-sm font-semibold text-warning px-1">{{ group.label }}</h3>
                            <div
                                v-for="item in group.items"
                                :key="item.id"
                                class="bg-surface rounded-card p-3 shadow-sm border-l-4"
                                :class="{
                                    'border-danger': item.display.type === 'expense',
                                    'border-success': item.display.type === 'income',
                                    'border-info': item.display.type === 'transfer',
                                }"
                            >
                                <div class="flex items-start justify-between">
                                    <!-- Left: Payee + Category -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-medium text-body truncate">
                                                <template v-if="item.display.type === 'transfer'">
                                                    <span class="text-info">↔</span>
                                                    Transfer to {{ item.display.to_account_name }}
                                                </template>
                                                <template v-else>{{ item.display.payee_name }}</template>
                                            </span>
                                        </div>
                                        <!-- Split categories with amounts -->
                                        <div v-if="item.display.splits" class="mt-0.5 grid grid-cols-[auto_auto] gap-x-1 gap-y-0.5 text-xs text-subtle w-fit">
                                            <template v-for="(split, si) in item.display.splits" :key="si">
                                                <span>{{ split.category }}:</span>
                                                <span>${{ split.amount.toFixed(2) }}</span>
                                            </template>
                                        </div>
                                        <!-- Single category -->
                                        <div v-else-if="item.display.category_name" class="text-xs text-subtle mt-0.5 truncate">
                                            {{ item.display.category_name }}
                                        </div>
                                        <!-- Unassigned -->
                                        <div v-else-if="item.display.type !== 'transfer'" class="text-xs text-subtle mt-0.5 truncate italic">
                                            Unassigned
                                        </div>
                                    </div>
                                    <!-- Right: Amount + Account + Remove -->
                                    <div class="flex items-start gap-2 flex-shrink-0 ml-3">
                                        <div class="text-right">
                                            <div :class="[
                                                'font-medium',
                                                item.display.type === 'expense' ? 'text-danger' : item.display.type === 'transfer' ? 'text-info' : 'text-success'
                                            ]">
                                                {{ item.display.type === 'expense' ? '-' : item.display.type === 'income' ? '+' : '' }}${{ item.display.amount.toFixed(2) }}
                                            </div>
                                            <div v-if="item.display.type !== 'transfer'" class="text-xs text-subtle mt-0.5">
                                                {{ item.display.account_name }}
                                            </div>
                                        </div>
                                        <button
                                            @click="removeReviewItem(item.id)"
                                            class="flex-shrink-0 p-1 mt-0.5 text-subtle hover:text-danger transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex gap-3">
                        <Button variant="outline" class="flex-1" @click="addMore">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            Add More
                        </Button>
                        <Button variant="primary" class="flex-1" @click="createAll">
                            Create All ({{ reviewItems.length }})
                        </Button>
                    </div>

                    <!-- Cancel link -->
                    <button
                        @click="handleClose"
                        class="w-full mt-3 text-subtle text-sm text-center py-1"
                    >
                        Cancel
                    </button>
                </div>

                <!-- CREATING STATE -->
                <div v-else-if="state === 'creating'" class="relative w-full max-w-sm bg-surface rounded-2xl p-8 text-center">
                    <div class="relative w-[88px] h-[88px] mx-auto mb-3">
                        <div class="w-[72px] h-[72px] rounded-full absolute top-2 left-2 overflow-hidden shadow-[0_0_0_3px_rgb(var(--color-primary)/0.2)] z-[2] avatar-bob">
                            <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                        </div>
                    </div>
                    <div class="inline-flex gap-1.5 bg-surface-inset rounded-2xl px-4 py-2.5 mb-3">
                        <div class="thinking-dot"></div>
                        <div class="thinking-dot"></div>
                        <div class="thinking-dot"></div>
                    </div>
                    <p class="text-body font-semibold mb-1">Creating {{ reviewItems.length }} transaction{{ reviewItems.length !== 1 ? 's' : '' }}...</p>
                </div>

                <!-- SUCCESS STATE -->
                <div v-else-if="state === 'success'" class="relative w-full max-w-sm bg-surface rounded-2xl p-8 text-center">
                    <div class="relative w-[88px] h-[88px] mx-auto mb-3">
                        <div class="absolute inset-0 border-2 border-primary/30 rounded-full shadow-[0_0_20px_rgb(var(--color-primary)/0.15)]"></div>
                        <div class="w-[72px] h-[72px] rounded-full absolute top-2 left-2 overflow-hidden shadow-[0_0_0_3px_rgb(var(--color-primary)/0.5)] z-[2]">
                            <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                        </div>
                        <div class="absolute bottom-1 right-1 w-6 h-6 bg-success rounded-full flex items-center justify-center z-[3] border-2 border-surface">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-success font-semibold mb-1">Done!</p>
                    <p class="text-muted text-sm mb-2">
                        Budget Guy created {{ createdTransactions.length }} transaction{{ createdTransactions.length !== 1 ? 's' : '' }}
                    </p>
                    <div class="space-y-1">
                        <div
                            v-for="tx in createdTransactions"
                            :key="tx.id"
                            class="text-sm text-muted"
                        >
                            {{ tx.payee }} &middot;
                            <span :class="tx.type === 'expense' ? 'text-danger' : tx.type === 'transfer' ? 'text-info' : 'text-success'" class="font-mono font-semibold">
                                {{ tx.type === 'expense' || tx.type === 'transfer' ? '-' : '+' }}${{ Math.abs(tx.amount).toFixed(2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- CLARIFICATION STATE -->
                <div v-else-if="state === 'clarification'" class="relative w-full max-w-sm bg-surface rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-11 h-11 rounded-full overflow-hidden flex-shrink-0 shadow-[0_0_0_2px_rgb(var(--color-primary)/0.3)]">
                            <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                        </div>
                        <div>
                            <p class="text-body font-semibold text-sm">
                                {{ currentClarification()?.message }}
                            </p>
                            <p class="text-muted text-xs italic">
                                "{{ transcript }}"
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <button
                            v-for="option in currentClarification()?.options"
                            :key="option.id"
                            @click="selectClarification(option)"
                            class="w-full bg-surface-overlay border border-border rounded-card p-3 text-left text-body font-medium hover:border-primary transition-colors"
                        >
                            {{ option.name }}
                        </button>
                    </div>

                    <button
                        @click="handleClose"
                        class="w-full mt-4 text-subtle text-sm text-center py-2"
                    >
                        Cancel
                    </button>
                </div>

                <!-- ERROR STATE -->
                <div v-else-if="state === 'error'" class="relative w-full max-w-sm bg-surface rounded-2xl p-8 text-center">
                    <div class="relative w-[88px] h-[88px] mx-auto mb-3">
                        <div class="absolute inset-0 border-2 border-danger/30 rounded-full"></div>
                        <div class="w-[72px] h-[72px] rounded-full absolute top-2 left-2 overflow-hidden shadow-[0_0_0_3px_rgb(var(--color-danger)/0.3)] z-[2]">
                            <img src="/images/Avatar.png" alt="Budget Guy" class="w-full h-full object-cover" />
                        </div>
                        <div class="absolute bottom-1 right-1 w-6 h-6 bg-danger rounded-full flex items-center justify-center z-[3] border-2 border-surface text-white text-xs font-bold">?</div>
                    </div>
                    <p class="text-body font-semibold mb-2">
                        {{ speechError === 'not-allowed' ? 'Microphone access denied' : isServiceError() ? 'Something went wrong' : "Budget Guy didn't catch that" }}
                    </p>
                    <p class="text-muted text-sm mb-4">
                        {{ speechError === 'not-allowed'
                            ? 'Allow microphone access in your browser settings.'
                            : errorMessage
                        }}
                    </p>
                    <div v-if="transcript && speechError !== 'not-allowed'" class="bg-surface-inset rounded-lg px-3 py-2 mb-4 text-left select-all">
                        <p class="text-subtle text-xs font-semibold mb-1">What I heard:</p>
                        <p class="text-body text-sm italic whitespace-pre-line">"{{ formattedTranscript() }}"</p>
                    </div>
                    <!-- With accumulated items: Back to List + Try Again -->
                    <div v-if="hasAccumulatedItems() && speechError !== 'not-allowed'" class="flex gap-3">
                        <Button variant="muted" class="flex-1" @click="backToReview">
                            Back to List ({{ reviewItems.length }})
                        </Button>
                        <Button variant="primary" class="flex-1" @click="tryAgain">Try Again</Button>
                    </div>
                    <!-- No accumulated items: Cancel + Try Again -->
                    <div v-else-if="speechError !== 'not-allowed'" class="flex gap-3">
                        <Button variant="muted" class="flex-1" @click="handleClose">Cancel</Button>
                        <Button variant="primary" class="flex-1" @click="tryAgain">Try Again</Button>
                    </div>
                    <Button v-else variant="muted" full-width @click="handleClose">Close</Button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.avatar-pulse-ring {
    position: absolute;
    inset: 0;
    border: 2px solid rgb(var(--color-primary));
    border-radius: 50%;
    animation: avatar-pulse 2s ease-out infinite;
}

.avatar-pulse-ring-2 { animation-delay: 0.7s; }
.avatar-pulse-ring-3 { animation-delay: 1.4s; }

@keyframes avatar-pulse {
    0% { transform: scale(1); opacity: 0.5; }
    100% { transform: scale(1.7); opacity: 0; }
}

.avatar-mic-indicator {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    background: rgb(var(--color-danger));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    border: 2px solid rgb(var(--color-surface));
    animation: mic-glow 1s ease-in-out infinite alternate;
}

@keyframes mic-glow {
    0% { box-shadow: 0 0 0 0 rgb(var(--color-danger) / 0.4); }
    100% { box-shadow: 0 0 8px 3px rgb(var(--color-danger) / 0.3); }
}

.thinking-dot {
    width: 8px;
    height: 8px;
    background: rgb(var(--color-primary));
    border-radius: 50%;
    animation: thinking-bounce 1.4s ease-in-out infinite;
}

.thinking-dot:nth-child(2) { animation-delay: 0.2s; }
.thinking-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes thinking-bounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}

.avatar-bob {
    animation: subtle-bob 2s ease-in-out infinite;
}

@keyframes subtle-bob {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}
</style>
