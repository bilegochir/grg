<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useLocale } from '@/lib/i18n';
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

const { t } = useLocale();

const featureCards = [
    {
        title: t('pages.welcome.featureLeadTitle'),
        description: t('pages.welcome.featureLeadDescription'),
    },
    {
        title: t('pages.welcome.featureCaseTitle'),
        description: t('pages.welcome.featureCaseDescription'),
    },
    {
        title: t('pages.welcome.featureDocsTitle'),
        description: t('pages.welcome.featureDocsDescription'),
    },
];
</script>

<template>
    <Head :title="t('pages.welcome.title')" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
            <header class="flex items-center justify-between py-4">
                <Link href="/" class="flex items-center gap-3">
                    <ApplicationLogo class="h-11 w-11 fill-current text-brand-primary" />
                    <div>
                        <p class="text-lg font-semibold tracking-tight">Agency</p>
                        <p class="text-sm text-slate-500">{{ t('pages.welcome.tagline') }}</p>
                    </div>
                </Link>

                <nav v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="ui-button-secondary"
                    >
                        {{ t('pages.welcome.openDashboard') }}
                    </Link>

                    <template v-else>
                        <Link :href="route('login')" class="ui-button-secondary">
                            {{ t('common.logIn') }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="ui-button-primary"
                        >
                            {{ t('pages.welcome.createAccount') }}
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="flex flex-1 flex-col justify-center py-10 lg:py-16">
                <section class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div class="space-y-6">
                        <div class="inline-flex items-center rounded-full bg-brand-primary/10 px-4 py-2 text-sm font-medium text-brand-primary">
                            {{ t('pages.welcome.heroKicker') }}
                        </div>

                        <div class="space-y-4">
                            <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">
                                {{ t('pages.welcome.heroTitle') }}
                            </h1>
                            <p class="max-w-2xl text-lg leading-8 text-slate-600">
                                {{ t('pages.welcome.heroDescription') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                            >
                                <PrimaryButton>{{ t('common.goToDashboard') }}</PrimaryButton>
                            </Link>
                            <template v-else>
                                <Link :href="route('login')">
                                    <PrimaryButton>{{ t('pages.welcome.loginToAgency') }}</PrimaryButton>
                                </Link>
                                <Link v-if="canRegister" :href="route('register')">
                                    <SecondaryButton>{{ t('pages.welcome.createStaffAccount') }}</SecondaryButton>
                                </Link>
                            </template>
                        </div>

                        <div class="flex flex-wrap items-center gap-6 pt-2 text-sm text-slate-500">
                            <span>Laravel {{ laravelVersion }}</span>
                            <span>PHP {{ phpVersion }}</span>
                            <span>{{ t('pages.welcome.workflowReady') }}</span>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ t('pages.welcome.snapshot') }}</p>
                                <h2 class="text-2xl font-semibold tracking-tight text-slate-950">{{ t('pages.welcome.workspace') }}</h2>
                            </div>
                            <div class="rounded-full bg-orange-50 px-3 py-1 text-sm font-medium text-orange-600">
                                {{ t('pages.welcome.humanFirst') }}
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-slate-500">{{ t('pages.welcome.openVisaCases') }}</p>
                                        <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">24</p>
                                    </div>
                                    <div class="rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700">
                                        {{ t('pages.welcome.thisWeek') }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-sm font-medium text-slate-500">{{ t('pages.welcome.awaitingDocuments') }}</p>
                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">8</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-sm font-medium text-slate-500">{{ t('pages.welcome.priorityReviews') }}</p>
                                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">3</p>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 p-4">
                                <p class="text-sm font-medium text-slate-500">{{ t('pages.welcome.recentActivity') }}</p>
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
