<script setup>
import AppIcon from '@/Components/AppIcon.vue';
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

const navItems = ['Product', 'Resources', 'Company'];

const mockNavItems = [
    { label: 'Leads', active: false },
    { label: 'Applicants', active: true },
    { label: 'Cases', active: false },
    { label: 'Documents', active: false },
];

const metrics = [
    {
        title: 'Lead conversion',
        value: '62%',
        change: '+5%',
        tone: 'text-slate-900',
        bars: [90, 74, 58, 82],
    },
    {
        title: 'Active cases',
        value: '124',
        change: '+8',
        tone: 'text-slate-500',
        line: [34, 34, 62, 28, 50, 36, 30, 46, 46],
    },
];

const activityItems = [
    { name: 'Student visa review', detail: '3 documents waiting' },
    { name: 'Partner case lodgement', detail: 'Ready for final check' },
    { name: 'Skilled migration intake', detail: 'Consultation booked' },
];

const linePath = (points) => {
    const stepX = 100 / Math.max(points.length - 1, 1);

    return points
        .map((point, index) => {
            const x = index * stepX;
            const y = 100 - point;

            return `${index === 0 ? 'M' : 'L'} ${x},${y}`;
        })
        .join(' ');
};
</script>

<template>
    <Head :title="t('pages.welcome.title')" />

    <div class="min-h-screen bg-white text-slate-900">
        <div class="mx-auto min-h-screen max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">
            <div class="min-h-[calc(100vh-3rem)] bg-white px-6 py-6 sm:px-8 lg:px-10">
                <header class="flex flex-wrap items-center justify-between gap-4 py-2">
                    <div class="flex items-center gap-8">
                        <Link href="/" class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white">
                                <ApplicationLogo class="h-7 w-7 fill-current" />
                            </div>
                            <span class="text-base font-semibold tracking-tight text-slate-950">gereg</span>
                        </Link>

                        <nav class="hidden items-center gap-8 md:flex">
                            <a
                                v-for="item in navItems"
                                :key="item"
                                href="#"
                                class="text-[15px] font-medium text-slate-700 transition hover:text-slate-950"
                            >
                                {{ item }}
                            </a>
                        </nav>
                    </div>

                    <nav v-if="canLogin" class="flex items-center gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="ui-button-secondary !rounded-xl !border-slate-300 !px-4"
                        >
                            {{ t('pages.welcome.openDashboard') }}
                        </Link>

                        <template v-else>
                            <Link :href="route('login')" class="px-3 py-2 text-[15px] font-medium text-slate-700 transition hover:text-slate-950">
                                {{ t('common.logIn') }}
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                            >
                                <PrimaryButton class="!min-h-[44px] !rounded-xl !bg-slate-900 !px-5 !text-[15px] hover:!bg-slate-800">
                                    {{ t('pages.welcome.createAccount') }}
                                </PrimaryButton>
                            </Link>
                        </template>
                    </nav>
                </header>

                <main class="pb-6 pt-10 sm:pt-14">
                    <section class="mx-auto max-w-4xl text-center">
                        <div class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">
                            {{ t('pages.welcome.heroKicker') }}
                        </div>

                        <h1 class="mx-auto mt-8 max-w-4xl text-5xl font-semibold tracking-[-0.06em] text-slate-950 sm:text-6xl lg:text-7xl">
                            Help your visa team work with more clarity.
                        </h1>
                        <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-slate-500 sm:text-xl">
                            A calm workspace for leads, applicants, cases, and documents, designed to keep every next step clear.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                            <template v-if="$page.props.auth.user">
                                <Link :href="route('dashboard')">
                                    <PrimaryButton class="!min-h-[48px] !rounded-xl !bg-slate-900 !px-6 !text-[15px] hover:!bg-slate-800">
                                        {{ t('common.goToDashboard') }}
                                    </PrimaryButton>
                                </Link>
                            </template>
                            <template v-else>
                                <Link :href="route('login')">
                                    <SecondaryButton class="!min-h-[48px] !rounded-xl !border-slate-300 !bg-white !px-6 !text-[15px]">
                                        {{ t('pages.welcome.loginToAgency') }}
                                    </SecondaryButton>
                                </Link>
                                <Link v-if="canRegister" :href="route('register')">
                                    <PrimaryButton class="!min-h-[48px] !rounded-xl !bg-slate-900 !px-6 !text-[15px] hover:!bg-slate-800">
                                        {{ t('pages.welcome.createStaffAccount') }}
                                    </PrimaryButton>
                                </Link>
                            </template>
                        </div>
                    </section>

                    <section class="mx-auto mt-16 max-w-6xl overflow-hidden rounded-[28px] bg-slate-50">
                        <div class="grid gap-0 lg:grid-cols-[240px_minmax(0,1fr)]">
                            <aside class="bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white">
                                            <ApplicationLogo class="h-6 w-6 fill-current" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-950">gereg</p>
                                            <p class="text-xs text-slate-500">Workspace</p>
                                        </div>
                                    </div>
                                    <AppIcon name="chevronLeft" :size="16" class="text-slate-400" />
                                </div>

                                <div class="mt-6 space-y-1.5">
                                    <div
                                        v-for="item in mockNavItems"
                                        :key="item.label"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium"
                                        :class="item.active ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'"
                                    >
                                        <AppIcon :name="item.active ? 'dashboard' : 'note'" :size="16" />
                                        <span>{{ item.label }}</span>
                                    </div>
                                </div>
                            </aside>

                            <div class="bg-white p-4 sm:p-5">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950">Hello, team</p>
                                        <p class="mt-1 text-sm text-slate-500">A quick view of pipeline, casework, and document movement.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                            <button class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
                                            Edit view
                                        </button>
                                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                                            Share
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-4 xl:grid-cols-[1fr,1fr,0.9fr]">
                                    <div
                                        v-for="metric in metrics"
                                        :key="metric.title"
                                        class="rounded-[24px] bg-slate-50 p-5"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-medium text-slate-500">{{ metric.title }}</p>
                                                <div class="mt-3 flex items-baseline gap-2">
                                                    <p class="text-4xl font-semibold tracking-[-0.05em] text-slate-950">{{ metric.value }}</p>
                                                    <span class="text-sm font-medium" :class="metric.tone">{{ metric.change }}</span>
                                                </div>
                                            </div>
                                            <div class="rounded-xl bg-white px-3 py-2 text-sm text-slate-600 shadow-sm">
                                                1 month
                                            </div>
                                        </div>

                                        <div v-if="metric.bars" class="mt-5 space-y-3">
                                            <div
                                                v-for="(bar, index) in metric.bars"
                                                :key="`${metric.title}-bar-${index}`"
                                                class="flex items-center gap-3"
                                            >
                                                <span class="w-20 text-sm text-slate-500">
                                                    {{ ['Qualified', 'Ready', 'Submitted', 'Approved'][index] }}
                                                </span>
                                                <div class="h-2 flex-1 rounded-full bg-slate-100">
                                                    <div class="h-2 rounded-full bg-slate-900" :style="{ width: `${bar}%` }"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="mt-5">
                                            <svg viewBox="0 0 100 100" class="h-36 w-full">
                                                <path d="M 0 100 L 0 66 L 12.5 66 L 25 38 L 37.5 72 L 50 50 L 62.5 64 L 75 70 L 87.5 54 L 100 54 L 100 100 Z" fill="rgba(15,23,42,0.08)" />
                                                <path :d="linePath(metric.line)" fill="none" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="rounded-[24px] bg-slate-50 p-5">
                                        <p class="text-sm font-medium text-slate-500">Recent movement</p>
                                        <div class="mt-4 space-y-4">
                                            <div
                                                v-for="item in activityItems"
                                                :key="item.name"
                                                class="flex items-start gap-3"
                                            >
                                                <div class="mt-1 h-2.5 w-2.5 rounded-full bg-slate-300"></div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-slate-900">{{ item.name }}</p>
                                                    <div class="mt-1 inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-600">
                                                        {{ item.detail }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer class="flex flex-wrap items-center justify-between gap-3 py-5 text-sm text-slate-500">
                    <p>Professional, clear, and easy to understand.</p>
                    <div class="flex items-center gap-4">
                        <span>Laravel {{ laravelVersion }}</span>
                        <span>PHP {{ phpVersion }}</span>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>
