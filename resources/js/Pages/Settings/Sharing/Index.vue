<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/Base/Button.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    members: Array,
    pendingInvites: Array,
    isOwner: Boolean,
    budgetName: String,
});

const page = usePage();
const showInviteForm = ref(false);
const inviteForm = useForm({});
const copiedId = ref(null);
const newInviteUrl = ref(null);

const submitInvite = () => {
    inviteForm.post(route('sharing.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            newInviteUrl.value = page.props.flash?.inviteUrl || null;
        },
    });
};

const copyLink = async (url, id = 'new') => {
    try {
        await navigator.clipboard.writeText(url);
        copiedId.value = id;
        setTimeout(() => { copiedId.value = null; }, 2000);
    } catch {
        // Fallback for older browsers
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        copiedId.value = id;
        setTimeout(() => { copiedId.value = null; }, 2000);
    }
};

const cancelInvite = (inviteId) => {
    if (confirm('Cancel this invite?')) {
        router.delete(route('sharing.cancel-invite', inviteId), {
            preserveScroll: true,
        });
    }
};

const removeMember = (member) => {
    if (confirm(`Remove ${member.name} from this budget?`)) {
        router.delete(route('sharing.remove-member', member.id), {
            preserveScroll: true,
        });
    }
};

const getAvatarColor = (name) => {
    // Use our 5-color palette for avatars
    const colors = [
        'bg-primary',     // Brand Green
        'bg-secondary',   // Brand Blue
        'bg-success',      // Income Green
        'bg-danger',     // Expense Red
        'bg-surface-overlay', // Dark gray
    ];
    const index = name.charCodeAt(0) % colors.length;
    return colors[index];
};
</script>

<template>
    <Head title="Sharing" />

    <AppLayout>
        <template #title>Sharing</template>
        <template #header-left>
            <Link :href="route('settings.index')" class="p-2 -ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
        </template>

        <div class="p-4 space-y-6">
            <!-- Budget Name -->
            <div class="text-center">
                <p class="text-sm text-subtle">Sharing</p>
                <h2 class="text-xl font-semibold text-body">{{ budgetName }}</h2>
            </div>

            <!-- People with Access -->
            <div>
                <h3 class="text-sm font-semibold text-subtle uppercase tracking-wide px-1 mb-2">
                    People with Access
                </h3>
                <div class="bg-surface rounded-card divide-y divide-border">
                    <div
                        v-for="member in members"
                        :key="member.id"
                        class="flex items-center justify-between p-4"
                    >
                        <div class="flex items-center gap-3">
                            <!-- Avatar -->
                            <div
                                :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center text-inverse font-semibold text-sm',
                                    getAvatarColor(member.name)
                                ]"
                            >
                                {{ member.avatar }}
                            </div>
                            <div>
                                <div class="font-medium text-body flex items-center gap-2">
                                    {{ member.name }}
                                    <span
                                        v-if="member.role === 'owner'"
                                        class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full"
                                    >
                                        Owner
                                    </span>
                                    <span
                                        v-if="member.is_current_user"
                                        class="text-xs text-subtle"
                                    >
                                        (you)
                                    </span>
                                </div>
                                <div class="text-sm text-subtle">{{ member.username }}</div>
                            </div>
                        </div>
                        <!-- Remove button (only for owner, not for themselves) -->
                        <button
                            v-if="isOwner && !member.is_current_user && member.role !== 'owner'"
                            @click="removeMember(member)"
                            class="p-2 text-subtle hover:text-danger hover:bg-surface-overlay rounded-full transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pending Invites (only show if there are any or user is owner) -->
            <div v-if="isOwner && pendingInvites.length > 0">
                <h3 class="text-sm font-semibold text-subtle uppercase tracking-wide px-1 mb-2">
                    Pending Invites
                </h3>
                <div class="bg-surface rounded-card divide-y divide-border">
                    <div
                        v-for="invite in pendingInvites"
                        :key="invite.id"
                        class="p-4 space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <!-- Placeholder avatar -->
                                <div class="w-10 h-10 rounded-full bg-surface-overlay flex items-center justify-center text-subtle">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.102 1.101" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-body">Invite link</div>
                                    <div class="text-sm text-subtle">Created {{ invite.created_at }}</div>
                                </div>
                            </div>
                            <button
                                @click="cancelInvite(invite.id)"
                                class="text-sm text-subtle hover:text-danger transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                        <div class="flex items-center gap-2 pl-13">
                            <input
                                type="text"
                                :value="invite.invite_url"
                                readonly
                                class="flex-1 px-2 py-1 bg-surface-overlay border border-border rounded text-xs text-subtle truncate"
                            />
                            <button
                                @click="copyLink(invite.invite_url, invite.id)"
                                class="text-xs text-primary hover:text-primary/80 font-medium whitespace-nowrap transition-colors"
                            >
                                {{ copiedId === invite.id ? 'Copied!' : 'Copy link' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invite Form (only for owner) -->
            <div v-if="isOwner">
                <h3 class="text-sm font-semibold text-subtle uppercase tracking-wide px-1 mb-2">
                    Invite Someone
                </h3>

                <!-- Show invite link after creation -->
                <div v-if="newInviteUrl" class="bg-surface rounded-card p-4 space-y-3 mb-4">
                    <p class="text-sm font-medium text-body">Invite link created! Share it with your invitee:</p>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            :value="newInviteUrl"
                            readonly
                            class="flex-1 px-3 py-2 bg-surface-overlay border border-border rounded-lg text-sm text-body truncate"
                        />
                        <Button
                            variant="primary"
                            size="sm"
                            @click="copyLink(newInviteUrl)"
                        >
                            {{ copiedId === 'new' ? 'Copied!' : 'Copy' }}
                        </Button>
                    </div>
                    <button
                        @click="newInviteUrl = null; showInviteForm = false;"
                        class="text-sm text-subtle hover:text-body transition-colors"
                    >
                        Done
                    </button>
                </div>

                <div v-if="!showInviteForm && !newInviteUrl" class="bg-surface rounded-card">
                    <button
                        @click="showInviteForm = true"
                        class="w-full flex items-center gap-3 p-4 text-primary hover:bg-surface-overlay transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span class="font-medium">Add a person</span>
                    </button>
                </div>

                <div v-else-if="!newInviteUrl" class="bg-surface rounded-card p-4 space-y-3">
                    <p class="text-sm text-subtle">Generate a link you can share via text or email.</p>
                    <div class="flex gap-3">
                        <Button
                            variant="secondary"
                            class="flex-1"
                            @click="showInviteForm = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            class="flex-1"
                            :disabled="inviteForm.processing"
                            @click="submitInvite"
                        >
                            {{ inviteForm.processing ? 'Creating...' : 'Create Invite Link' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Non-owner message -->
            <div v-if="!isOwner" class="text-center py-4">
                <p class="text-sm text-subtle">
                    Only the budget owner can invite or remove members.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
