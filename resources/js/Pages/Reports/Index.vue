<script setup>
import AppCard from '@/Components/AppCard.vue';
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

const financeCards = computed(() => [
    { label: 'Outstanding balance', value: `$${Number(props.finance.outstanding_balance ?? 0).toFixed(2)}` },
    { label: 'Overdue invoices', value: props.finance.overdue_invoices ?? 0 },
    { label: 'Collected this month', value: `$${Number(props.finance.paid_this_month ?? 0).toFixed(2)}` },
]);

const maxValue = (items) => Math.max(...items.map((item) => item.value), 1);
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <div class="ui-page-body space-y-5">
            <section class="grid gap-5 xl:grid-cols-[1.2fr,0.8fr]">
                <AppCard title="Lead funnel">
                    <div class="space-y-4">
                        <div v-for="item in leadFunnel" :key="item.label" class="space-y-2">
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="font-medium text-brand-text">{{ item.label }}</span>
                                <span class="text-brand-muted">{{ item.value }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-brand-primary"
                                    :style="{ width: `${(item.value / maxValue(leadFunnel)) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </AppCard>

                <AppCard title="SLA pressure">
                    <div v-if="alerts.length" class="space-y-3">
                        <Link
                            v-for="alert in alerts"
                            :key="alert.key"
                            :href="alert.href"
                            class="flex items-start justify-between gap-3 rounded-xl border border-black/6 bg-[#fcfcfd] px-4 py-4 transition hover:border-black/10 hover:bg-white"
                        >
                            <div>
                                <p class="font-medium text-brand-text">{{ alert.label }}</p>
                                <p class="text-sm text-brand-muted">{{ alert.description }}</p>
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
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                <AppCard title="Lead sources">
                    <div v-if="leadSources.length" class="space-y-3">
                        <div
                            v-for="item in leadSources"
                            :key="item.label"
                            class="flex items-center justify-between rounded-xl border border-black/6 bg-[#fcfcfd] px-4 py-3"
                        >
                            <span class="text-sm font-medium text-brand-text">{{ item.label }}</span>
                            <span class="text-sm text-brand-muted">{{ item.value }}</span>
                        </div>
                    </div>
                    <EmptyState v-else icon="leads" title="No lead data yet" description="Lead sources will show up once intake starts moving." />
                </AppCard>

                <AppCard title="Cases by stage">
                    <div v-if="casesByStage.length" class="space-y-3">
                        <div
                            v-for="item in casesByStage"
                            :key="item.label"
                            class="flex items-center justify-between rounded-xl border border-black/6 bg-[#fcfcfd] px-4 py-3"
                        >
                            <span class="text-sm font-medium text-brand-text">{{ item.label }}</span>
                            <StatusBadge :label="`${item.value} open`" :color="item.color" />
                        </div>
                    </div>
                    <EmptyState v-else icon="users" title="No active cases" description="Once cases are open, stage distribution will show up here." />
                </AppCard>

                <AppCard title="Finance snapshot">
                    <div class="space-y-3">
                        <div
                            v-for="item in financeCards"
                            :key="item.label"
                            class="rounded-xl border border-black/6 bg-[#fcfcfd] px-4 py-4"
                        >
                            <p class="text-sm text-brand-muted">{{ item.label }}</p>
                            <p class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-brand-text">{{ item.value }}</p>
                        </div>
                    </div>
                </AppCard>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1fr,1fr]">
                <AppCard title="Staff workload">
                    <div v-if="staffWorkload.length" class="space-y-3">
                        <div
                            v-for="user in staffWorkload"
                            :key="user.id"
                            class="grid gap-3 rounded-xl border border-black/6 bg-[#fcfcfd] px-4 py-4 md:grid-cols-[1.4fr,0.8fr,0.8fr,0.8fr]"
                        >
                            <p class="font-medium text-brand-text">{{ user.name }}</p>
                            <p class="text-sm text-brand-muted">{{ user.open_cases_count }} open cases</p>
                            <p class="text-sm text-brand-muted">{{ user.open_tasks_count }} open tasks</p>
                            <p class="text-sm text-brand-muted">{{ user.upcoming_appointments_count }} upcoming appointments</p>
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Demand by country">
                    <div v-if="countryDemand.length" class="space-y-3">
                        <div
                            v-for="item in countryDemand"
                            :key="item.label"
                            class="space-y-2 rounded-xl border border-black/6 bg-[#fcfcfd] px-4 py-4"
                        >
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="font-medium text-brand-text">{{ item.label }}</span>
                                <span class="text-brand-muted">{{ item.value }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-brand-primary"
                                    :style="{ width: `${(item.value / maxValue(countryDemand)) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else icon="dashboard" title="No case demand yet" description="Country demand will appear once cases begin moving." />
                </AppCard>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
