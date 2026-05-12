<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useLocale } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    invitation: Object,
});

const form = useForm({
    name: props.invitation.name ?? '',
    email: props.invitation.email,
    password: '',
    password_confirmation: '',
});

const { t } = useLocale();
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.acceptInvitation.title')" />

        <div class="space-y-6">
            <div>
                <p class="ui-kicker">{{ t('auth.acceptInvitation.kicker') }}</p>
                <h1 class="mt-2 text-2xl font-semibold text-brand-text">{{ t('auth.acceptInvitation.heading') }}</h1>
                <p class="mt-2 text-sm leading-6 text-brand-muted">
                    {{ t('auth.acceptInvitation.invitedAs', 'You’ve been invited', { role: invitation.role }) }}
                    <span v-if="invitation.branch"> {{ t('auth.acceptInvitation.invitedBranch', 'for :branch', { branch: invitation.branch }) }}</span>.
                </p>
                <p class="mt-1 text-sm leading-6 text-brand-muted">{{ t('auth.acceptInvitation.expires', 'This invitation expires :date.', { date: invitation.expires_at }) }}</p>
            </div>

            <form class="space-y-5" @submit.prevent="form.post(route('team-invitations.store', invitation.token))">
                <div>
                    <InputLabel for="name" :value="t('auth.acceptInvitation.yourName')" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="email" :value="t('common.email')" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="password" :value="t('common.password')" />
                    <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" :value="t('auth.acceptInvitation.confirmPassword')" />
                    <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                </div>

                <PrimaryButton :loading="form.processing">{{ t('auth.acceptInvitation.joinWorkspace') }}</PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
