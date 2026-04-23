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

    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.12),_transparent_28%),linear-gradient(180deg,#f8fafc_0%,#eef2ff_45%,#ffffff_100%)]">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-4 py-8 md:px-6 lg:px-8">
            <div class="grid w-full gap-6 lg:grid-cols-[minmax(0,1.15fr)_440px]">
                <section class="rounded-[2rem] border border-white/70 bg-[linear-gradient(145deg,#0f172a_0%,#132238_58%,#1e293b_100%)] px-6 py-8 text-white shadow-[0_24px_80px_-40px_rgba(15,23,42,0.6)] lg:px-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-300">Client Portal</p>
                    <h1 class="mt-4 max-w-xl text-4xl font-semibold tracking-tight">A simple place to follow your visa case and send what’s needed.</h1>
                    <p class="mt-4 max-w-xl text-sm leading-7 text-slate-300">
                        Sign in with your email and password to check progress, upload documents, and keep your details current.
                    </p>

                    <div v-if="portal.context" class="mt-8 inline-flex rounded-full border border-sky-400/20 bg-sky-400/10 px-4 py-2 text-sm text-sky-100">
                        {{ portal.context.client_name }}<span v-if="portal.context.company_name"> • {{ portal.context.company_name }}</span>
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-[1.5rem] border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Clear next steps</p>
                            <p class="mt-2 text-sm text-slate-100">Only the most important document requests appear first.</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Case updates</p>
                            <p class="mt-2 text-sm text-slate-100">See how your application is moving without extra clutter.</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Secure access</p>
                            <p class="mt-2 text-sm text-slate-100">If it is your first visit from the private portal link, you can set your password there.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_24px_80px_-40px_rgba(15,23,42,0.35)] backdrop-blur lg:p-8">
                    <div v-if="page.props.flash.success" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ page.props.flash.success }}
                    </div>

                    <div class="space-y-2" :class="page.props.flash.success ? 'mt-5' : ''">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Sign in</p>
                        <h2 class="text-3xl font-semibold tracking-tight text-slate-950">Open your portal</h2>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div class="grid gap-1.5">
                            <Label for="email">Email address</Label>
                            <Input id="email" v-model="form.email" type="email" autocomplete="email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="password">Password</Label>
                            <Input id="password" v-model="form.password" type="password" autocomplete="current-password" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <input v-model="form.portal_token" type="hidden" />

                        <Button :disabled="form.processing" class="w-full">Continue to portal</Button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</template>
