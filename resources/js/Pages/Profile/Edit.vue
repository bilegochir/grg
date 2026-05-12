<script setup>
import AppCard from '@/Components/AppCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { useLocale } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const { t } = useLocale();
</script>

<template>
    <Head :title="t('pages.profile.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">{{ t('pages.profile.kicker') }}</p>
                    <h1 class="ui-header-title">{{ t('pages.profile.heading') }}</h1>
                    <p class="ui-header-copy">{{ t('pages.profile.description') }}</p>
                </div>
            </div>
        </template>

        <div class="ui-page-body">
            <div class="grid gap-3 lg:grid-cols-3">
                <div class="rounded-xl border border-black/6 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-brand-text">{{ t('pages.profile.identityTitle') }}</p>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">{{ t('pages.profile.identityDescription') }}</p>
                </div>
                <div class="rounded-xl border border-black/6 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-brand-text">{{ t('pages.profile.securityTitle') }}</p>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">{{ t('pages.profile.securityDescription') }}</p>
                </div>
                <div class="rounded-xl border border-black/6 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-brand-text">{{ t('pages.profile.removalTitle') }}</p>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">{{ t('pages.profile.removalDescription') }}</p>
                </div>
            </div>

            <AppCard :title="t('pages.profile.infoCardTitle')" :subtitle="t('pages.profile.infoCardSubtitle')">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-2xl"
                />
            </AppCard>

            <AppCard :title="t('pages.profile.passwordCardTitle')" :subtitle="t('pages.profile.passwordCardSubtitle')">
                <UpdatePasswordForm class="max-w-2xl" />
            </AppCard>

            <AppCard :title="t('pages.profile.dangerCardTitle')" :subtitle="t('pages.profile.dangerCardSubtitle')">
                <DeleteUserForm class="max-w-2xl" />
            </AppCard>
        </div>
    </AuthenticatedLayout>
</template>
