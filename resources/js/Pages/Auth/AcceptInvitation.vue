<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
</script>

<template>
    <GuestLayout>
        <Head title="Accept Invitation" />

        <div class="space-y-6">
            <div>
                <p class="ui-kicker">Team invitation</p>
                <h1 class="mt-2 text-2xl font-semibold text-brand-text">Join the workspace</h1>
                <p class="mt-2 text-sm leading-6 text-brand-muted">
                    You’ve been invited as {{ invitation.role }}<span v-if="invitation.branch"> for {{ invitation.branch }}</span>.
                </p>
                <p class="mt-1 text-sm leading-6 text-brand-muted">This invitation expires {{ invitation.expires_at }}.</p>
            </div>

            <form class="space-y-5" @submit.prevent="form.post(route('team-invitations.store', invitation.token))">
                <div>
                    <InputLabel for="name" value="Your name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirm password" />
                    <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                </div>

                <PrimaryButton :loading="form.processing">Join workspace</PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
