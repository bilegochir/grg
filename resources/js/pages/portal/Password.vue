<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    portal: {
        token: string;
        company: {
            name: string;
        };
        client: {
            full_name: string;
        };
        requiresPasswordSetup: boolean;
    };
}>();

const page = usePage<SharedData>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(route('portal.password.update', props.portal.token), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head :title="props.portal.requiresPasswordSetup ? 'Create portal password' : 'Change portal password'" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.12),_transparent_28%),linear-gradient(180deg,#f8fafc_0%,#eef2ff_45%,#ffffff_100%)]">
        <div class="mx-auto flex min-h-screen w-full max-w-5xl items-center px-4 py-8 md:px-6 lg:px-8">
            <div class="grid w-full gap-6 lg:grid-cols-[minmax(0,1.05fr)_460px]">
                <section class="rounded-[2rem] border border-white/70 bg-slate-950 px-6 py-8 text-white shadow-[0_24px_80px_-40px_rgba(15,23,42,0.6)] lg:px-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-300">Portal security</p>
                    <h1 class="mt-4 text-4xl font-semibold tracking-tight">
                        {{ portal.requiresPasswordSetup ? 'Create your password' : 'Change your password' }}
                    </h1>
                    <p class="mt-4 max-w-xl text-sm leading-7 text-slate-300">
                        {{
                            portal.requiresPasswordSetup
                                ? 'You opened the portal from your private link. Create your password now so future sign-ins are simple.'
                                : 'Use this page whenever you want to update your portal password.'
                        }}
                    </p>

                    <div class="mt-8 rounded-[1.5rem] border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Account</p>
                        <p class="mt-2 text-lg font-semibold text-white">{{ portal.client.full_name }}</p>
                        <p class="mt-1 text-sm text-slate-300">{{ portal.company.name }}</p>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_24px_80px_-40px_rgba(15,23,42,0.35)] backdrop-blur lg:p-8">
                    <div v-if="page.props.flash.success" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ page.props.flash.success }}
                    </div>

                    <div class="space-y-2" :class="page.props.flash.success ? 'mt-5' : ''">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Security</p>
                        <h2 class="text-3xl font-semibold tracking-tight text-slate-950">
                            {{ portal.requiresPasswordSetup ? 'Finish setting up your portal' : 'Update your password' }}
                        </h2>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div v-if="!portal.requiresPasswordSetup" class="grid gap-1.5">
                            <Label for="current_password">Current password</Label>
                            <Input id="current_password" v-model="form.current_password" type="password" autocomplete="current-password" />
                            <InputError :message="form.errors.current_password" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="password">New password</Label>
                            <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="password_confirmation">Confirm password</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button :disabled="form.processing" class="flex-1 sm:flex-none">
                                {{ portal.requiresPasswordSetup ? 'Save password and continue' : 'Save new password' }}
                            </Button>

                            <Button v-if="!portal.requiresPasswordSetup" as-child variant="ghost">
                                <Link :href="route('portal.show', portal.token)">Back to portal</Link>
                            </Button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</template>
