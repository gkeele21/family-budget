import { computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { learnSteps, setupSteps } from '@/Config/tutorialSteps.js';

export function useTutorial() {
    const page = usePage();

    const track = computed(() => page.props.auth?.user?.tutorial_track ?? null);
    const currentStepId = computed(() => page.props.auth?.user?.tutorial_step ?? null);
    const isActive = computed(() => track.value !== null);

    const steps = computed(() => {
        if (track.value === 'learn') return learnSteps;
        if (track.value === 'setup') return setupSteps;
        return [];
    });

    const currentStepIndex = computed(() => {
        if (!currentStepId.value || !steps.value.length) return -1;
        return steps.value.findIndex((s) => s.id === currentStepId.value);
    });

    const currentStep = computed(() => {
        if (currentStepIndex.value === -1) return null;
        return steps.value[currentStepIndex.value];
    });

    const totalSteps = computed(() => steps.value.length);

    const canAutoAdvance = computed(() => currentStep.value?.autoAdvance ?? false);

    function advance() {
        const nextIndex = currentStepIndex.value + 1;
        if (nextIndex >= steps.value.length) {
            complete();
            return;
        }
        const nextStepId = steps.value[nextIndex].id;
        router.put('/tutorial/step', { step: nextStepId }, { preserveScroll: true });
    }

    function goToStep(stepId) {
        router.put('/tutorial/step', { step: stepId }, { preserveScroll: true });
    }

    function complete() {
        router.post('/tutorial/complete', { track: track.value }, { preserveScroll: true });
    }

    function skip() {
        complete();
        router.visit('/budget');
    }

    function watchForCompletion() {
        watch(
            () => page.props,
            (props) => {
                if (!isActive.value || !currentStep.value) return;
                if (!currentStep.value.autoAdvance || !currentStep.value.actionCheck) return;

                if (currentStep.value.actionCheck(props)) {
                    advance();
                }
            },
            { deep: true },
        );
    }

    return {
        isActive,
        track,
        currentStepId,
        currentStep,
        currentStepIndex,
        totalSteps,
        steps,
        canAutoAdvance,
        advance,
        skip,
        complete,
        goToStep,
        watchForCompletion,
    };
}

export default useTutorial;
