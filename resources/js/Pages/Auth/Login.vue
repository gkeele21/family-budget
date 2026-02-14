<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/Form/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout title="Welcome back">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 p-3 bg-success/10 border border-success/20 rounded-xl">
            <p class="text-sm text-success text-center">{{ status }}</p>
        </div>

        <form @submit.prevent="submit">
            <!-- Form Fields - FormRow Style -->
            <div class="space-y-1">
                <div class="flex items-center justify-between py-4 border-b border-border">
                    <label for="email" class="text-sm text-subtle">Email</label>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        class="text-base text-body text-right bg-transparent border-none focus:ring-0 focus:outline-none p-0 w-2/3"
                        placeholder="you@example.com"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.email" />

                <div class="flex items-center justify-between py-4 border-b border-border">
                    <label for="password" class="text-sm text-subtle">Password</label>
                    <input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        class="text-base text-body text-right bg-transparent border-none focus:ring-0 focus:outline-none p-0 w-2/3"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Forgot Password -->
            <div class="flex justify-end mt-3 mb-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-primary font-medium hover:text-primary/80 transition-colors"
                >
                    Forgot password?
                </Link>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-4 bg-primary text-white rounded-xl font-semibold text-center disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="form.processing">Signing in...</span>
                <span v-else>Sign In</span>
            </button>

            <!-- Sign Up Link -->
            <p class="mt-6 text-sm text-subtle text-center">
                Don't have an account?
                <Link
                    :href="route('register')"
                    class="text-primary font-semibold hover:text-primary/80 transition-colors"
                >
                    Sign up
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
