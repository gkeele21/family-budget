<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useSpeechRecognition } from '@/Composables/useSpeechRecognition.js';
import Button from '@/Components/Base/Button.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'created']);

const { isListening, transcript, interimTranscript, error: speechError, start, stop, abort, reset, onEnd } = useSpeechRecognition();

// Overlay states: idle, listening, processing, error, success
const state = ref('idle');
const errorMessage = ref('');
const createdGroups = ref([]);

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

// Clear silence timer once speech is detected
watch([transcript, interimTranscript], () => {
    if (state.value === 'listening' && (transcript.value || interimTranscript.value)) {
        clearSilenceTimer();
    }
});

// Start listening when overlay opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        startListening();
    } else {
        clearSilenceTimer();
        state.value = 'idle';
        errorMessage.value = '';
        createdGroups.value = [];
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
        const { data } = await window.axios.post(route('categories.voice.parse'), {
            transcript: text,
        });

        if (data.status === 'created') {
            createdGroups.value = data.groups;
            state.value = 'success';

            setTimeout(() => {
                emit('created', { groups: data.groups });
            }, 1500);
        } else {
            state.value = 'error';
            errorMessage.value = data.message || "Couldn't understand that.";
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

// Display text: show interim while listening, final when done
const displayTranscript = () => {
    if (interimTranscript.value) {
        return transcript.value
            ? `${transcript.value} ${interimTranscript.value}`
            : interimTranscript.value;
    }
    return transcript.value;
};

const isServiceError = () => errorMessage.value.includes('AI service') || errorMessage.value.includes('credits');

const totalCategories = () => {
    return createdGroups.value.reduce((sum, g) => sum + g.categories.length, 0);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
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
                        Say something like "Bills group with Rent $1500, Electric $150"
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
                    <p class="text-body font-semibold mb-1">Creating categories...</p>
                    <p v-if="transcript" class="text-muted text-sm italic">
                        "{{ transcript }}"
                    </p>
                </div>

                <!-- SUCCESS STATE -->
                <div v-else-if="state === 'success'" class="relative w-full max-w-sm bg-surface rounded-2xl p-8">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-body font-semibold">
                            Created {{ createdGroups.length }} group{{ createdGroups.length !== 1 ? 's' : '' }},
                            {{ totalCategories() }} categor{{ totalCategories() !== 1 ? 'ies' : 'y' }}
                        </p>
                    </div>
                    <div class="space-y-3">
                        <div v-for="group in createdGroups" :key="group.id">
                            <p class="text-xs font-semibold text-warning uppercase tracking-wide mb-1">
                                {{ group.name }}
                                <span v-if="!group.is_new" class="text-subtle font-normal normal-case">(existing)</span>
                            </p>
                            <div class="space-y-0.5">
                                <div
                                    v-for="cat in group.categories"
                                    :key="cat.id"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-body">
                                        {{ cat.icon || '📁' }} {{ cat.name }}
                                    </span>
                                    <span v-if="cat.default_amount" class="text-muted font-mono text-xs">
                                        {{ formatCurrency(cat.default_amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
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
