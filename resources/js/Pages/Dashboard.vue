<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useLocale } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    stats: Array,
    attentionItems: Array,
    activity: Array,
    leadFlow: Array,
    sourceDistribution: Array,
    slaAlerts: Array,
    hasData: Boolean,
});

const { t } = useLocale();

import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    ArcElement,
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const leadFlowData = computed(() => ({
    labels: props.leadFlow.map(i => i.date),
    datasets: [
        {
            label: 'Leads',
            data: props.leadFlow.map(i => i.count),
            fill: true,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.05)',
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointBackgroundColor: '#10b981',
        },
    ],
}));

const leadFlowOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: '#1e293b',
            titleFont: { size: 12, weight: 'bold' },
            bodyFont: { size: 12 },
            padding: 10,
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { size: 10 }, color: '#94a3b8' },
        },
        y: {
            beginAtZero: true,
            grid: { color: '#f1f5f9' },
            ticks: { font: { size: 10 }, color: '#94a3b8', stepSize: 1 },
        },
    },
};

const sourceData = computed(() => ({
    labels: props.sourceDistribution.map(i => i.label),
    datasets: [
        {
            data: props.sourceDistribution.map(i => i.count),
            backgroundColor: [
                '#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#f43f5e'
            ],
            borderRadius: 4,
            barThickness: 20,
        },
    ],
}));

const sourceOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { display: false },
        },
        y: {
            grid: { display: false },
            ticks: { font: { size: 11, weight: '500' }, color: '#475569' },
        },
    },
};
</script>

<template>
    <Head :title="t('pages.dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">{{ t('pages.dashboard.kicker') }}</p>
                    <h1 class="ui-header-title text-[28px] tracking-tight">{{ t('pages.dashboard.heading') }}</h1>
                    <p class="ui-header-copy text-[14px] leading-relaxed">{{ t('pages.dashboard.description') }}</p>
                </div>
            </div>
        </template>

        <div class="ui-page-body space-y-8">
            <EmptyState
                v-if="!hasData"
                icon="sparkle"
                :title="t('pages.dashboard.emptyTitle')"
                :description="t('pages.dashboard.emptyDescription')"
            >
                <template #action>
                    <Link :href="route('leads.index')">
                        <PrimaryButton icon="plus">{{ t('common.addFirstLead') }}</PrimaryButton>
                    </Link>
                </template>
            </EmptyState>

            <template v-else>
                <!-- Performance Bar -->
                <div class="flex flex-wrap divide-x divide-slate-100 rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <Link
                        v-for="stat in stats"
                        :key="stat.label"
                        :href="stat.href"
                        class="flex-1 min-w-[200px] p-5 hover:bg-slate-50/50 transition-colors"
                    >
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900 leading-none">{{ stat.value }}</p>
                        <p class="mt-3 text-[11px] font-medium text-slate-500 leading-tight">{{ stat.trend }}</p>
                    </Link>
                </div>

                <div class="grid gap-8 lg:grid-cols-12">
                    <!-- Main column -->
                    <div class="lg:col-span-8 space-y-8">
                        <!-- Lead Flow Chart -->
                        <div>
                            <div class="mb-4">
                                <h2 class="text-[16px] font-bold text-slate-900">Lead Flow</h2>
                                <p class="text-[12px] text-slate-500 mt-0.5">New inquiries captured over the last 14 days.</p>
                            </div>
                            <div class="h-[240px] rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                                <Line :data="leadFlowData" :options="leadFlowOptions" />
                            </div>
                        </div>

                        <!-- Attention Items -->
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-[16px] font-bold text-slate-900">What needs attention today</h2>
                            </div>
                            <div v-if="attentionItems.length" class="grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="item in attentionItems"
                                    :key="item.label"
                                    class="group rounded-lg border border-slate-200 bg-white p-4 shadow-sm hover:border-brand-primary/30 transition-all"
                                >
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-slate-900">{{ item.label }}</p>
                                            <p class="mt-1 text-[12px] text-slate-500 leading-normal">{{ item.description }}</p>
                                        </div>
                                        <div 
                                            class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                            :class="{
                                                'bg-blue-50 text-blue-700': item.color === 'blue',
                                                'bg-amber-50 text-amber-700': item.color === 'amber',
                                                'bg-emerald-50 text-emerald-700': item.color === 'emerald',
                                            }"
                                        >
                                            {{ item.badge }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="rounded-lg border border-dashed border-slate-200 p-8 text-center">
                                <p class="text-sm text-slate-400">Nothing urgent right now.</p>
                            </div>
                        </div>

                        <!-- SLA Alerts -->
                        <div v-if="slaAlerts.length">
                            <div class="mb-4">
                                <h2 class="text-[16px] font-bold text-slate-900">Operations Alerts</h2>
                                <p class="text-[12px] text-slate-500 mt-0.5">Items that have sat too long and need intervention.</p>
                            </div>
                            <div class="space-y-2">
                                <Link
                                    v-for="item in slaAlerts"
                                    :key="item.key"
                                    :href="item.href"
                                    class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm hover:bg-slate-50 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-2 rounded-full bg-rose-500"></div>
                                        <div>
                                            <p class="text-[13px] font-bold text-slate-900">{{ item.label }}</p>
                                            <p class="text-[11px] text-slate-500">{{ item.description }}</p>
                                        </div>
                                    </div>
                                    <AppIcon name="chevronRight" :size="14" class="text-slate-300" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar column -->
                    <div class="lg:col-span-4 space-y-8">
                        <!-- Source Breakdown Chart -->
                        <div>
                            <div class="mb-4">
                                <h2 class="text-[16px] font-bold text-slate-900">Source Breakdown</h2>
                            </div>
                            <div class="h-[180px] rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                                <Bar :data="sourceData" :options="sourceOptions" />
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div>
                            <div class="mb-4">
                                <h2 class="text-[16px] font-bold text-slate-900">Recent Activity</h2>
                            </div>
                            <div v-if="activity.length" class="space-y-4">
                                <div
                                    v-for="item in activity"
                                    :key="item.id"
                                    class="relative pl-6 before:absolute before:left-[7px] before:top-2 before:h-full before:w-px before:bg-slate-100 last:before:hidden"
                                >
                                    <div class="absolute left-0 top-1.5 h-[15px] w-[15px] rounded-full border-2 border-white bg-slate-200 ring-2 ring-slate-50"></div>
                                    <div>
                                        <p class="text-[12px] leading-relaxed text-slate-700">
                                            <span class="font-bold text-slate-900">{{ item.causer }}</span> {{ item.description }}
                                            <span v-if="item.subject_name" class="text-brand-primary font-medium"> ({{ item.subject_name }})</span>
                                        </p>
                                        <p class="mt-1 text-[10px] text-slate-400 uppercase tracking-wider font-bold">{{ item.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-sm text-slate-400 italic">
                                No recent activity yet.
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
