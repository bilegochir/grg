<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    portal: {
        portalToken: null | string;
        context: null | {
            company_name: null | string;
            client_name: string;
        };
    };
}>();

const page = usePage<SharedData>();

const form = useForm({
    email: '',
    password: '',
    portal_token: props.portal.portalToken ?? '',
});

const submit = () => {
    form.post(route('portal.login.store'));
};
</script>

<template>
    <Head title="Client portal login" />

    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto flex min-h-screen w-full max-w-5xl items-center px-4 py-8 md:px-6">
            <div class="grid w-full gap-5 lg:grid-cols-[minmax(0,1fr)_420px]">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 lg:p-8">
                    <p class="text-sm font-medium text-sky-700">Customer portal</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">A simple place to check your visa case</h1>
                    <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                        Use this portal to see what documents are still needed, upload files, and keep your contact details up to date.
                    </p>

                    <div v-if="portal.context" class="mt-5 inline-flex rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm text-sky-700">
                        {{ portal.context.client_name }}<span v-if="portal.context.company_name"> • {{ portal.context.company_name }}</span>
                    </div>

                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-950">1. Sign in</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Use your email and password to open your private portal.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-950">2. Upload documents</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">We show the important requested files first.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-950">3. Check progress</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">See how your application is moving without extra clutter.</p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        First visit from your private link? Open that link first and create your password there.
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 lg:p-8">
                    <div v-if="page.props.flash.success" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ page.props.flash.success }}
                    </div>

                    <div class="space-y-2" :class="page.props.flash.success ? 'mt-5' : ''">
                        <p class="text-sm font-medium text-slate-500">Sign in</p>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Open your portal</h2>
                        <p class="text-sm leading-6 text-slate-600">Enter the same email you used with your agency.</p>
                    </div>

                    <form class="mt-6 space-y-5" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input id="email" v-model="form.email" type="email" autocomplete="email" class="h-11 text-base" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">Password</Label>
                            <Input id="password" v-model="form.password" type="password" autocomplete="current-password" class="h-11 text-base" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <input v-model="form.portal_token" type="hidden" />

                        <Button :disabled="form.processing" class="h-11 w-full text-base">Continue</Button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</template>
