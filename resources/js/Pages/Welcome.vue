<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const featureCards = [
    {
        title: 'Lead to applicant flow',
        description: 'Capture leads, qualify them, and convert them into applicants without losing context.',
    },
    {
        title: 'Case tracking that stays human',
        description: 'Keep agents aligned with clear statuses, dates, priorities, and visible next steps.',
    },
    {
        title: 'Document handling in one place',
        description: 'Track uploads, verification, expiry dates, and applicant-ready downloads from each case.',
    },
];
</script>

<template>
    <Head title="Agency" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
            <header class="flex items-center justify-between py-4">
                <Link href="/" class="flex items-center gap-3">
                    <ApplicationLogo class="h-11 w-11 fill-current text-brand-primary" />
                    <div>
                        <p class="text-lg font-semibold tracking-tight">Agency</p>
                        <p class="text-sm text-slate-500">Warm, clear visa operations</p>
                    </div>
                </Link>

                <nav v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="ui-button-secondary"
                    >
                        Open dashboard
                    </Link>

                    <template v-else>
                        <Link :href="route('login')" class="ui-button-secondary">
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="ui-button-primary"
                        >
                            Create account
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="flex flex-1 flex-col justify-center py-10 lg:py-16">
                <section class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div class="space-y-6">
                        <div class="inline-flex items-center rounded-full bg-brand-primary/10 px-4 py-2 text-sm font-medium text-brand-primary">
                            Built for visa teams who guide real life decisions
                        </div>

                        <div class="space-y-4">
                            <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">
                                Help applicants move forward with confidence.
                            </h1>
                            <p class="max-w-2xl text-lg leading-8 text-slate-600">
                                Agency gives your team a friendly CRM for leads, applicants, visa cases,
                                and documents so every step feels organized, clear, and reassuring.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                            >
                                <PrimaryButton>Go to dashboard</PrimaryButton>
                            </Link>
                            <template v-else>
                                <Link :href="route('login')">
                                    <PrimaryButton>Log in to Agency</PrimaryButton>
                                </Link>
                                <Link v-if="canRegister" :href="route('register')">
                                    <SecondaryButton>Create staff account</SecondaryButton>
                                </Link>
                            </template>
                        </div>

                        <div class="flex flex-wrap items-center gap-6 pt-2 text-sm text-slate-500">
                            <span>Laravel {{ laravelVersion }}</span>
                            <span>PHP {{ phpVersion }}</span>
                            <span>Lead, case, and document workflows ready</span>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Today’s snapshot</p>
                                <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Agency workspace</h2>
                            </div>
                            <div class="rounded-full bg-orange-50 px-3 py-1 text-sm font-medium text-orange-600">
                                Human-first
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-slate-500">Open visa cases</p>
                                        <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">24</p>
                                    </div>
                                    <div class="rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700">
                                        +6 this week
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-sm font-medium text-slate-500">Awaiting documents</p>
                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">8</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-sm font-medium text-slate-500">Priority reviews</p>
                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">3</p>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 p-4">
                                <p class="text-sm font-medium text-slate-500">Recent activity</p>
                                <div class="mt-3 space-y-3">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-1 h-2.5 w-2.5 rounded-full bg-brand-primary"></span>
                                        <p class="text-sm leading-6 text-slate-600">
                                            Sarah moved Ahmed Al-Rashid to <span class="font-medium text-slate-900">Under Review</span>.
                                        </p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="mt-1 h-2.5 w-2.5 rounded-full bg-orange-500"></span>
                                        <p class="text-sm leading-6 text-slate-600">
                                            A passport copy was uploaded for <span class="font-medium text-slate-900">Mina Park</span>.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-16 grid gap-5 md:grid-cols-3">
                    <article
                        v-for="card in featureCards"
                        :key="card.title"
                        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_1px_3px_rgba(0,0,0,0.06),0_1px_2px_rgba(0,0,0,0.04)]"
                    >
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary">
                            <span class="text-lg font-semibold">•</span>
                        </div>
                        <h3 class="text-xl font-semibold tracking-tight text-slate-950">
                            {{ card.title }}
                        </h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">
                            {{ card.description }}
                        </p>
                    </article>
                </section>
            </main>
        </div>
    </div>
</template>
