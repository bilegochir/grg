<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leadFunnel: Array,
    leadSources: Array,
    casesByStage: Array,
    staffWorkload: Array,
    finance: Object,
    countryDemand: Array,
    alerts: Array,
});

const formatCurrency = (value) => `$${Number(value ?? 0).toFixed(2)}`;

const maxValue = (items) => Math.max(...items.map((item) => Number(item.value ?? 0)), 1);

const widthFor = (value, items) => `${(Number(value ?? 0) / maxValue(items)) * 100}%`;

const totalLeads = computed(() => props.leadFunnel.reduce((sum, item) => sum + Number(item.value ?? 0), 0));
const totalOpenCases = computed(() => props.casesByStage.reduce((sum, item) => sum + Number(item.value ?? 0), 0));
const totalOpenTasks = computed(() => props.staffWorkload.reduce((sum, item) => sum + Number(item.open_tasks_count ?? 0), 0));
const topCountry = computed(() => props.countryDemand[0] ?? null);
const topLeadSource = computed(() => props.leadSources[0] ?? null);

const summaryCards = computed(() => [
    {
        label: 'Open pipeline',
        value: totalLeads.value,
        helper: `${totalOpenCases.value} active cases in progress`,
        icon: 'dashboard',
    },
    {
        label: 'Outstanding balance',
        value: formatCurrency(props.finance.outstanding_balance),
        helper: `${props.finance.overdue_invoices ?? 0} overdue invoices`,
        icon: 'inbox',
    },
    {
        label: 'Team workload',
        value: totalOpenTasks.value,
        helper: `${props.staffWorkload.length} staff members carrying open work`,
        icon: 'tasks',
    },
    {
        label: 'Top destination',
        value: topCountry.value?.label ?? 'No demand yet',
        helper: topCountry.value ? `${topCountry.value.value} open cases` : 'Case demand will appear here',
        icon: 'map',
    },
]);

const financeCards = computed(() => [
    {
        label: 'Outstanding balance',
        value: formatCurrency(props.finance.outstanding_balance),
        helper: 'Current unpaid amount across all invoices',
    },
    {
        label: 'Overdue invoices',
        value: props.finance.overdue_invoices ?? 0,
        helper: 'Invoices already past due date',
    },
    {
        label: 'Collected this month',
        value: formatCurrency(props.finance.paid_this_month),
        helper: 'Payments received in the current month',
    },
]);
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <div class="ui-page-body space-y-5">
            <section class="ui-page-header">
                <div>
                    <p class="ui-kicker">Reports</p>
                    <h1 class="ui-header-title">Performance & Operations</h1>
                    <p class="ui-header-copy">
                        A simple view of pipeline health, workload, finance, and where demand is building.
                    </p>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-xl border border-slate-100 bg-white px-4 py-4 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[12px] font-medium text-slate-500">{{ card.label }}</p>
                            <p class="mt-2 text-[22px] font-semibold tracking-tight text-slate-900">{{ card.value }}</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-slate-600">
                            <AppIcon :name="card.icon" :size="18" />
                        </div>
                    </div>
                    <p class="mt-3 text-[12px] leading-5 text-slate-500">{{ card.helper }}</p>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.2fr,0.8fr]">
                <AppCard title="Operational watchlist" subtitle="Cases and tasks that need attention right now.">
                    <template #action>
                        <Link :href="route('tasks.index')" class="ui-button-ghost">Open tasks</Link>
                    </template>

                    <div v-if="alerts.length" class="space-y-3">
                        <Link
                            v-for="alert in alerts"
                            :key="alert.key"
                            :href="alert.href"
                            class="flex items-start justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50/60 px-4 py-3 transition hover:border-slate-200 hover:bg-white"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900">{{ alert.label }}</p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">{{ alert.description }}</p>
                            </div>
                            <StatusBadge :label="alert.badge" :color="alert.color" />
                        </Link>
                    </div>
                    <EmptyState
                        v-else
                        icon="check"
                        title="No operational alerts"
                        description="The workflow is moving cleanly right now."
                    />
                </AppCard>

                <AppCard title="Finance snapshot" subtitle="A quick read on money in, money due, and what needs follow-up.">
                    <template #action>
                        <Link :href="route('invoices.index')" class="ui-button-ghost">Open invoices</Link>
                    </template>

                    <div class="space-y-3">
                        <div
                            v-for="item in financeCards"
                            :key="item.label"
                            class="rounded-lg border border-slate-100 bg-slate-50/60 px-4 py-4"
                        >
                            <p class="text-[12px] font-medium text-slate-500">{{ item.label }}</p>
                            <p class="mt-2 text-[22px] font-semibold tracking-tight text-slate-900">{{ item.value }}</p>
                            <p class="mt-2 text-[12px] leading-5 text-slate-500">{{ item.helper }}</p>
                        </div>
                    </div>
                </AppCard>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.15fr,0.85fr]">
                <AppCard title="Lead funnel" subtitle="How the current intake pipeline is distributed across statuses.">
                    <template #action>
                        <Link :href="route('leads.index')" class="ui-button-ghost">Open leads</Link>
                    </template>

                    <div v-if="leadFunnel.length" class="space-y-3">
                        <div
                            v-for="item in leadFunnel"
                            :key="item.label"
                            class="rounded-lg border border-slate-100 bg-white px-4 py-3"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900">{{ item.label }}</p>
                                </div>
                                <p class="text-sm text-slate-500">{{ item.value }}</p>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-primary" :style="{ width: widthFor(item.value, leadFunnel) }"></div>
                            </div>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        icon="leads"
                        title="No lead data yet"
                        description="Lead reporting will populate once intake starts moving."
                    />
                </AppCard>

                <AppCard title="Top lead sources" subtitle="Where the strongest intake is currently coming from.">
                    <template #action>
                        <span v-if="topLeadSource" class="text-[12px] text-slate-500">
                            Top source: <span class="font-medium text-slate-700">{{ topLeadSource.label }}</span>
                        </span>
                    </template>

                    <div v-if="leadSources.length" class="space-y-2">
                        <div
                            v-for="(item, index) in leadSources"
                            :key="item.label"
                            class="flex items-center justify-between gap-4 rounded-lg border border-slate-100 bg-slate-50/60 px-4 py-3"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-[12px] font-semibold text-slate-500">
                                    {{ index + 1 }}
                                </span>
                                <p class="truncate text-sm font-medium text-slate-900">{{ item.label }}</p>
                            </div>
                            <p class="text-sm text-slate-500">{{ item.value }}</p>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        icon="leads"
                        title="No lead source data yet"
                        description="Lead sources will show up once intake starts moving."
                    />
                </AppCard>
            </section>

            <section class="grid gap-5 xl:grid-cols-[0.9fr,1.1fr]">
                <AppCard title="Cases by stage" subtitle="Where open cases are sitting in the workflow today.">
                    <template #action>
                        <Link :href="route('cases.index')" class="ui-button-ghost">Open cases</Link>
                    </template>

                    <div v-if="casesByStage.length" class="space-y-2">
                        <div
                            v-for="item in casesByStage"
                            :key="item.label"
                            class="flex items-center justify-between gap-4 rounded-lg border border-slate-100 bg-slate-50/60 px-4 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ item.label }}</p>
                            </div>
                            <StatusBadge :label="`${item.value} open`" :color="item.color" />
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        icon="users"
                        title="No active cases"
                        description="Once cases are open, stage distribution will show up here."
                    />
                </AppCard>

                <AppCard title="Staff workload" subtitle="A quick comparison of open work across the team.">
                    <template #action>
                        <Link :href="route('staff.index')" class="ui-button-ghost">Open staff</Link>
                    </template>

                    <div v-if="staffWorkload.length" class="space-y-2">
                        <div
                            v-for="user in staffWorkload"
                            :key="user.id"
                            class="rounded-lg border border-slate-100 bg-white px-4 py-4"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900">{{ user.name }}</p>
                                    <p class="mt-1 text-[12px] text-slate-500">
                                        {{ user.open_cases_count }} open cases, {{ user.open_tasks_count }} open tasks
                                    </p>
                                </div>
                                <span class="text-[12px] text-slate-500">
                                    {{ user.upcoming_appointments_count }} upcoming
                                </span>
                            </div>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        icon="users"
                        title="No staff workload yet"
                        description="Assigned cases and tasks will appear here once work is underway."
                    />
                </AppCard>
            </section>

            <section>
                <AppCard title="Demand by country" subtitle="Where active case demand is concentrated.">
                    <div v-if="countryDemand.length" class="grid gap-3 md:grid-cols-2">
                        <div
                            v-for="item in countryDemand"
                            :key="item.label"
                            class="rounded-lg border border-slate-100 bg-white px-4 py-4"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-sm font-medium text-slate-900">{{ item.label }}</p>
                                <p class="text-sm text-slate-500">{{ item.value }}</p>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-primary" :style="{ width: widthFor(item.value, countryDemand) }"></div>
                            </div>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        icon="dashboard"
                        title="No case demand yet"
                        description="Country demand will appear once cases begin moving."
                    />
                </AppCard>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
