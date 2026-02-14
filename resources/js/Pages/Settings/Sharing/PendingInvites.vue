<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    invites: Array,
});

const acceptInvite = (token) => {
    router.post(route('sharing.accept-invite'), { token }, {
        preserveScroll: true,
    });
};

const declineInvite = (token) => {
    if (confirm('Decline this invite? You will need a new invite to join this budget.')) {
        router.post(route('sharing.decline-invite'), { token }, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Budget Invites" />

    <AppLayout>
        <template #title>Invites</template>
        <template #header-left>
            <Link :href="route('settings.index')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <div class="p-4 space-y-6">
            <!-- Invites List -->
            <div v-if="invites.length > 0" class="space-y-4">
                <p class="text-sm text-subtle text-center">
                    You've been invited to share a budget
                </p>

                <div
                    v-for="invite in invites"
                    :key="invite.id"
                    class="bg-surface rounded-card p-4 space-y-4"
                >
                    <div class="text-center">
                        <div class="text-4xl mb-2">
                            <span class="inline-block animate-bounce">!</span>
                        </div>
                        <h3 class="text-lg font-semibold text-body">{{ invite.budget_name }}</h3>
                        <p class="text-sm text-subtle">
                            Invited by {{ invite.invited_by }} {{ invite.created_at }}
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button
                            @click="declineInvite(invite.token)"
                            class="flex-1 py-3 bg-surface-overlay text-body rounded-card font-medium hover:bg-border-strong transition-colors"
                        >
                            Decline
                        </button>
                        <button
                            @click="acceptInvite(invite.token)"
                            class="flex-1 py-3 bg-primary text-body rounded-card font-medium hover:bg-primary/90 transition-colors"
                        >
                            Join Budget
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <div class="mb-4 flex justify-center">
                    <svg class="w-10 h-10 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-body mb-2">No pending invites</h3>
                <p class="text-subtle">
                    You don't have any budget invites at the moment.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
