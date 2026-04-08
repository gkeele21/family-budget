<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Button from '@/Components/Base/Button.vue';

const props = defineProps({
    token: String,
    budgetName: String,
    invitedBy: String,
});

const acceptInvite = () => {
    router.post(route('sharing.accept-invite'), { token: props.token });
};

const declineInvite = () => {
    if (confirm('Decline this invite? You will need a new invite link to join this budget.')) {
        router.post(route('sharing.decline-invite'), { token: props.token });
    }
};
</script>

<template>
    <Head title="Budget Invite" />

    <AppLayout>
        <template #title>Invite</template>

        <div class="p-4 flex items-center justify-center min-h-[60vh]">
            <div class="bg-surface rounded-card p-6 space-y-6 max-w-sm w-full text-center">
                <div>
                    <div class="text-4xl mb-3">&#x1f4e8;</div>
                    <h2 class="text-xl font-semibold text-body">You're invited!</h2>
                    <p class="text-subtle mt-2">
                        <span class="font-medium text-body">{{ invitedBy }}</span> has invited you to share the budget
                    </p>
                    <p class="text-lg font-semibold text-primary mt-1">{{ budgetName }}</p>
                </div>

                <div class="flex gap-3">
                    <Button
                        variant="secondary"
                        class="flex-1"
                        @click="declineInvite"
                    >
                        Decline
                    </Button>
                    <Button
                        variant="primary"
                        class="flex-1"
                        @click="acceptInvite"
                    >
                        Join Budget
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
