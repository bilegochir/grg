<script setup>
import AppCard from '@/Components/AppCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { useLocale } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';

defineProps({
    business: Object,
    applicant: Object,
    summary: Object,
    cases: Array,
});

const progressTone = (percent) => {
    if (percent >= 100) return 'bg-green-500';
    if (percent >= 60) return 'bg-brand-primary';

    return 'bg-orange-500';
};

const attentionCount = (item) => item.documents_waiting_count + item.open_tasks_count;
const { t } = useLocale();
</script>

<template>
    <PortalLayout :title="t('pages.portal.applicantPortal')" :business="business" :applicant="applicant">
        <section class="rounded-[28px] border border-orange-100 bg-white px-6 py-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)] sm:px-8 sm:py-8">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,360px)] lg:items-center">
                <div>
                    <p class="ui-kicker text-orange-500">{{ t('pages.portal.helloName', 'Hello :name', { name: applicant.name }) }}</p>
                    <h1 class="mt-2 max-w-2xl text-[32px] leading-tight">{{ t('pages.portal.dashboardHeading') }}</h1>
                    <p class="mt-4 max-w-2xl text-base text-brand-muted">
                        {{ t('pages.portal.dashboardDescription') }}
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                            {{ summary.documents_waiting_count }} document{{ summary.documents_waiting_count === 1 ? '' : 's' }} waiting
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                            {{ summary.open_tasks_count }} checklist item{{ summary.open_tasks_count === 1 ? '' : 's' }} open
                        </span>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-sm text-brand-muted">{{ t('pages.portal.openCases') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-brand-text">{{ summary.cases_count }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-sm text-brand-muted">{{ t('pages.portal.waitingOnYou') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-brand-text">{{ summary.documents_waiting_count + summary.open_tasks_count }}</p>
                        <p class="mt-1 text-sm text-brand-muted">{{ t('pages.portal.documentsAndChecklist') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 sm:col-span-2 lg:col-span-1">
                        <p class="text-sm text-brand-muted">{{ t('pages.portal.outstandingBalance') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-brand-text">${{ summary.balance_due }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8">
            <AppCard :title="t('pages.portal.yourCases')" :padded="false">
                <div v-if="cases.length" class="divide-y divide-slate-200">
                    <Link
                        v-for="item in cases"
                        :key="item.id"
                        :href="route('portal.cases.show', item.id)"
                        class="block px-5 py-5 transition-colors hover:bg-slate-50"
                    >
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <p class="text-base font-semibold text-brand-text">{{ item.reference_code }}</p>
                                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700">
                                        {{ item.stage || t('pages.portal.inProgress') }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-brand-muted">{{ item.country }} • {{ item.visa_type }}</p>
                                <p class="mt-4 max-w-2xl text-sm leading-6 text-brand-text">{{ item.stage_copy }}</p>
                                <p class="mt-3 text-sm font-medium text-brand-text">{{ item.next_step }}</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span v-if="attentionCount(item)" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                                        {{ attentionCount(item) }} thing{{ attentionCount(item) === 1 ? '' : 's' }} needs attention
                                    </span>
                                    <span v-if="item.documents_waiting_count" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                        {{ item.documents_waiting_count }} document{{ item.documents_waiting_count === 1 ? '' : 's' }} waiting
                                    </span>
                                </div>
                            </div>

                            <div class="w-full max-w-sm space-y-4 lg:w-[340px]">
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-brand-muted">{{ t('pages.portal.progress') }}</span>
                                        <span class="font-medium text-brand-text">{{ item.progress_percent }}%</span>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full transition-all" :class="progressTone(item.progress_percent)" :style="{ width: `${item.progress_percent}%` }"></div>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-xl border border-slate-200 px-3 py-3">
                                        <p class="text-xs uppercase tracking-[0.12em] text-brand-muted">Docs</p>
                                        <p class="mt-1 text-sm font-semibold text-brand-text">{{ item.documents_verified }}/{{ item.documents_total }}</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 px-3 py-3">
                                        <p class="text-xs uppercase tracking-[0.12em] text-brand-muted">Tasks</p>
                                        <p class="mt-1 text-sm font-semibold text-brand-text">{{ item.open_tasks_count }}</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 px-3 py-3">
                                        <p class="text-xs uppercase tracking-[0.12em] text-brand-muted">Balance</p>
                                        <p class="mt-1 text-sm font-semibold text-brand-text">${{ item.balance_due }}</p>
                                    </div>
                                </div>

                                <p v-if="item.latest_message_at" class="text-sm text-brand-muted">Last update {{ item.latest_message_at }}</p>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="px-5 py-6">
                    <EmptyState
                        icon="users"
                        :title="t('pages.portal.noActiveCasesTitle')"
                        :description="t('pages.portal.noActiveCasesDescription')"
                    />
                </div>
            </AppCard>
        </section>
    </PortalLayout>
</template>
