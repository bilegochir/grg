<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
});

const form = useForm({
    access_token: '',
});

const submit = () => {
    form.post(route('portal.login.store'));
};
</script>

<template>
    <PortalLayout
        title="Applicant portal login"
        :business="{ name: 'Gereg', email: null, phone: null }"
    >
        <div class="mx-auto max-w-xl">
            <div class="rounded-2xl border border-brand-border bg-white p-8 shadow-card">
                <p class="ui-kicker">Welcome</p>
                <h1 class="mt-2 text-[30px]">Open your visa portal</h1>

                <p v-if="status" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ status }}
                </p>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <InputLabel for="access_token" value="Secure access token" />
                        <input
                            id="access_token"
                            v-model="form.access_token"
                            type="text"
                            class="ui-input"
                            placeholder="Paste the token from your email"
                        />
                        <InputError :message="form.errors.access_token" />
                    </div>

                    <PrimaryButton :loading="form.processing">Open portal</PrimaryButton>
                </form>
            </div>
        </div>
    </PortalLayout>
</template>
