<script setup>
import AppCard from '@/Components/AppCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Account</p>
                    <h1 class="ui-header-title">Profile settings.</h1>
                    <p class="ui-header-copy">
                        Keep your personal details and sign-in settings aligned with the rest of the workspace.
                    </p>
                </div>
            </div>
        </template>

        <div class="ui-page-body">
            <div class="grid gap-3 lg:grid-cols-3">
                <div class="rounded-xl border border-black/6 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-brand-text">Identity</p>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">Update the name and email your teammates see.</p>
                </div>
                <div class="rounded-xl border border-black/6 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-brand-text">Security</p>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">Refresh your password before issues turn into blockers.</p>
                </div>
                <div class="rounded-xl border border-black/6 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-brand-text">Account removal</p>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">Reserve destructive actions for when they are truly needed.</p>
                </div>
            </div>

            <AppCard title="Profile information" subtitle="The basics your workspace uses to identify and reach you.">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-2xl"
                />
            </AppCard>

            <AppCard title="Password and security" subtitle="Use a strong password that is hard to guess and easy to rotate when needed.">
                <UpdatePasswordForm class="max-w-2xl" />
            </AppCard>

            <AppCard title="Danger zone" subtitle="Low-frequency actions that should stay visible but separate from everyday account edits.">
                <DeleteUserForm class="max-w-2xl" />
            </AppCard>
        </div>
    </AuthenticatedLayout>
</template>
