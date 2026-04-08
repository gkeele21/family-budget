<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextField from '@/Components/Form/TextField.vue';
import Button from '@/Components/Base/Button.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    username: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout title="Create Account">
        <Head title="Register" />

        <form @submit.prevent="submit">
            <TextField
                v-model="form.name"
                label="Name"
                placeholder="Jane Smith"
                autocomplete="name"
                autofocus
                required
                :error="form.errors.name"
            />
            <TextField
                v-model="form.username"
                label="Username"
                placeholder="jane"
                autocomplete="username"
                required
                :error="form.errors.username"
            />
            <TextField
                v-model="form.password"
                label="Password"
                type="password"
                placeholder="••••••••"
                autocomplete="new-password"
                required
                :error="form.errors.password"
            />
            <TextField
                v-model="form.password_confirmation"
                label="Confirm"
                type="password"
                placeholder="••••••••"
                autocomplete="new-password"
                required
                :border-bottom="false"
                :error="form.errors.password_confirmation"
            />

            <div class="mt-6">
                <Button type="submit" :loading="form.processing" full-width size="lg">
                    Create Account
                </Button>
            </div>

            <!-- Sign In Link -->
            <p class="mt-6 text-sm text-subtle text-center">
                Already have an account?
                <Link
                    :href="route('login')"
                    class="text-primary font-semibold hover:text-primary/80 transition-colors"
                >
                    Sign in
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
