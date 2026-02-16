<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useSpeechRecognition } from '@/Composables/useSpeechRecognition.js';
import Button from '@/Components/Base/Button.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'created']);

const { isListening, transcript, interimTranscript, error: speechError, start, stop, abort, reset, onEnd } = useSpeechRecognition();

// Overlay states: idle, listening, processing, clarification, error, success
const state = ref('idle');
const errorMessage = ref('');
const createdTransactions = ref([]);
const batchId = ref(null);

// Silence timeout — auto-close if nothing heard for 5s
let silenceTimer = null;

function startSilenceTimer() {
    clearSilenceTimer();
    silenceTimer = setTimeout(() => {
        if (state.value === 'listening' && !transcript.value && !interimTranscript.value) {
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

// Reset silence timer whenever speech is detected
watch([transcript, interimTranscript], () => {
    if (state.value === 'listening' && (transcript.value || interimTranscript.value)) {
        clearSilenceTimer();
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
        clarifications.value = [];
        sessionContext.value = '';
        currentClarificationIndex.value = 0;
        clarificationAnswers.value = [];
        reset();
    }
});

// Handle escape key
const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
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
        });

        if (data.status === 'created') {
            handleSuccess(data);
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

function handleSuccess(data) {
    createdTransactions.value = data.transactions;
    batchId.value = data.batch_id;
    state.value = 'success';

    setTimeout(() => {
        emit('created', {
            transactions: data.transactions,
            batchId: data.batch_id,
        });
    }, 1200);
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

    // All answered — send to backend
    state.value = 'processing';

    try {
        const { data } = await window.axios.post(route('transactions.voice.clarify'), {
            session_context: sessionContext.value,
            answers: clarificationAnswers.value,
        });

        if (data.status === 'created') {
            handleSuccess(data);
        } else {
            state.value = 'error';
            errorMessage.value = data.message || 'Something went wrong.';
        }
    } catch (e) {
        state.value = 'error';
        errorMessage.value = 'Something went wrong. Please try again.';
    }
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
                    @click="(state === 'listening' || state === 'error') ? handleClose() : null"
                />

                <!-- LISTENING STATE -->
                <div v-if="state === 'listening'" class="relative w-full max-w-sm bg-surface rounded-2xl p-8 text-center">
                    <!-- Pulsing mic -->
                    <div class="relative w-20 h-20 mx-auto mb-5">
                        <div class="voice-pulse-ring"></div>
                        <div class="voice-pulse-ring voice-pulse-ring-2"></div>
                        <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center absolute top-2 left-2">
                            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </div>
                    </div>

                    <p class="text-body font-semibold mb-1">Listening...</p>
                    <p v-if="displayTranscript()" class="text-muted text-sm italic mb-4 min-h-[2.5rem]">
                        "{{ displayTranscript() }}"
                    </p>
                    <p v-else class="text-subtle text-sm mb-4 min-h-[2.5rem]">
                        Say something like "I spent $45 at Shell on gas"
                    </p>
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
                    <div class="w-12 h-12 mx-auto mb-5 border-3 border-border border-t-primary rounded-full animate-spin"></div>
                    <p class="text-body font-semibold mb-1">Creating transactions...</p>
                    <p v-if="transcript" class="text-muted text-sm italic">
                        "{{ transcript }}"
                    </p>
                </div>

                <!-- SUCCESS STATE -->
                <div v-else-if="state === 'success'" class="relative w-full max-w-sm bg-surface rounded-2xl p-8 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-body font-semibold mb-2">
                        Created {{ createdTransactions.length }} transaction{{ createdTransactions.length !== 1 ? 's' : '' }}
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
                    <p class="text-body font-semibold text-center mb-1">
                        {{ currentClarification()?.message }}
                    </p>
                    <p class="text-muted text-sm italic text-center mb-5">
                        "{{ transcript }}"
                    </p>

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
                    <div class="text-4xl mb-4">
                        {{ speechError === 'not-allowed' ? '🔇' : isServiceError() ? '⚠️' : '😕' }}
                    </div>
                    <p class="text-body font-semibold mb-2">
                        {{ speechError === 'not-allowed' ? 'Microphone access denied' : isServiceError() ? 'Something went wrong' : "Couldn't understand that" }}
                    </p>
                    <p class="text-muted text-sm mb-5">
                        {{ speechError === 'not-allowed'
                            ? 'Allow microphone access in your browser settings.'
                            : errorMessage
                        }}
                    </p>
                    <div v-if="speechError !== 'not-allowed'" class="flex gap-3">
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
.voice-pulse-ring {
    position: absolute;
    inset: 0;
    border: 2px solid rgb(var(--color-primary));
    border-radius: 50%;
    animation: voice-pulse 1.5s ease-out infinite;
    opacity: 0.6;
}

.voice-pulse-ring-2 {
    animation-delay: 0.5s;
    opacity: 0.4;
}

@keyframes voice-pulse {
    0% { transform: scale(1); opacity: 0.6; }
    100% { transform: scale(1.5); opacity: 0; }
}

.border-3 {
    border-width: 3px;
}
</style>
