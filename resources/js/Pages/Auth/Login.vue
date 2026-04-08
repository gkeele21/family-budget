<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextField from '@/Components/Form/TextField.vue';
import Button from '@/Components/Base/Button.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    username: '',
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
            <TextField
                v-model="form.username"
                label="Username"
                placeholder="yourname"
                autocomplete="username"
                autofocus
                required
                :error="form.errors.username"
            />
            <TextField
                v-model="form.password"
                label="Password"
                type="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
                :border-bottom="false"
                :error="form.errors.password"
            />

            <div class="mt-6">
                <Button type="submit" :loading="form.processing" full-width size="lg">
                    Sign In
                </Button>
            </div>

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
