<script setup>
import AppCard from '@/Components/AppCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { useLocale } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    business: Object,
    applicant: Object,
    summary: Object,
    cases: Array,
});

const progressTone = (percent) => {
    if (percent >= 100) return 'bg-green-500';
    if (percent >= 60) return 'bg-slate-900';

    return 'bg-slate-400';
};

const attentionCount = (item) => item.documents_waiting_count + item.open_tasks_count + item.unread_messages_count;
const paymentAttentionCount = (item) => (Number(item.balance_due) > 0 ? 1 : 0);
const reminderCount = (item) => item.reminders?.filter((reminder) => reminder.type !== 'clear').length ?? 0;
const caseAttentionScore = (item) => (item.documents_waiting_count * 4) + (paymentAttentionCount(item) * 3) + (item.unread_messages_count * 2) + item.open_tasks_count;
const caseStatus = (item) => {
    if (item.next_action?.target_tab === 'documents') {
        return {
            label: 'Action needed',
            tone: 'portal-chip-warm',
            summary: `${item.documents_waiting_count} document${item.documents_waiting_count === 1 ? '' : 's'} waiting from you`,
        };
    }

    if (item.next_action?.target_tab === 'billing') {
        return {
            label: 'Payment pending',
            tone: 'portal-chip-muted',
            summary: 'Invoice is waiting for payment attention',
        };
    }

    if (item.next_action?.target_tab === 'messages') {
        return {
            label: 'Message waiting',
            tone: 'portal-chip-brand',
            summary: `${item.unread_messages_count} unread update${item.unread_messages_count === 1 ? '' : 's'} from your team`,
        };
    }

    if (item.open_tasks_count) {
        return {
            label: 'Checklist open',
            tone: 'portal-chip-muted',
            summary: `${item.open_tasks_count} checklist item${item.open_tasks_count === 1 ? '' : 's'} still open`,
        };
    }

    return { label: 'Up to date', tone: 'portal-chip-success', summary: 'No applicant action needed right now' };
};

const nextActionCopy = (item) => item.next_action?.description || 'Everything important is up to date right now.';
const nextActionLabel = (item) => item.next_action?.label || 'You are up to date';
const caseHref = (item) => `${route('portal.cases.show', item.id)}?tab=${item.next_action?.target_tab || 'overview'}`;

const sortedCases = computed(() => [...props.cases].sort((a, b) => {
    const scoreDiff = caseAttentionScore(b) - caseAttentionScore(a);

    if (scoreDiff !== 0) {
        return scoreDiff;
    }

    return Number(a.progress_percent) - Number(b.progress_percent);
}));

const casesNeedingAction = computed(() => sortedCases.value.filter((item) => caseAttentionScore(item) > 0));
const upToDateCases = computed(() => sortedCases.value.filter((item) => caseAttentionScore(item) === 0));
const priorityCase = computed(() => sortedCases.value[0] ?? null);
const { t } = useLocale();
</script>

<template>
    <PortalLayout :title="t('pages.portal.applicantPortal')" :business="business" :applicant="applicant">
        <section class="portal-hero">
            <div class="portal-summary-card">
                <p class="ui-kicker text-slate-500">{{ t('pages.portal.helloName', 'Hello :name', { name: applicant.name }) }}</p>
                <h1 class="mt-1.5 text-[21px] font-semibold tracking-tight text-slate-900">{{ t('pages.portal.dashboardHeading') }}</h1>
                <p class="mt-2 max-w-2xl text-[13px] leading-5 text-slate-500">
                    {{ t('pages.portal.dashboardDescription') }}
                </p>

                <div class="mt-4 grid gap-3 md:grid-cols-4">
                    <div class="portal-stat-card">
                        <p class="text-[12px] text-slate-500">{{ t('pages.portal.openCases') }}</p>
                        <p class="mt-1.5 text-[18px] font-semibold tracking-tight text-slate-900">{{ summary.cases_count }}</p>
                    </div>
                    <div class="portal-stat-card">
                        <p class="text-[12px] text-slate-500">Reminders</p>
                        <p class="mt-1.5 text-[18px] font-semibold tracking-tight text-slate-900">{{ summary.reminders_count }}</p>
                    </div>
                    <div class="portal-stat-card">
                        <p class="text-[12px] text-slate-500">Unread messages</p>
                        <p class="mt-1.5 text-[18px] font-semibold tracking-tight text-slate-900">{{ summary.unread_messages_count }}</p>
                    </div>
                    <div class="portal-stat-card">
                        <p class="text-[12px] text-slate-500">{{ t('pages.portal.outstandingBalance') }}</p>
                        <p class="mt-1.5 text-[18px] font-semibold tracking-tight text-slate-900">${{ summary.balance_due }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 md:flex-row md:items-end md:justify-between">
                    <div class="min-w-0">
                        <p class="text-[12px] uppercase tracking-[0.12em] text-slate-500">Next action</p>
                        <template v-if="priorityCase">
                            <p class="mt-1.5 text-[15px] font-semibold text-slate-900">{{ nextActionLabel(priorityCase) }}</p>
                            <p class="mt-1 text-[13px] leading-5 text-slate-500">{{ nextActionCopy(priorityCase) }}</p>
                            <p class="mt-1.5 text-[13px] text-slate-500">{{ priorityCase.reference_code }} • {{ priorityCase.country }} • {{ priorityCase.visa_type }}</p>
                            <div v-if="priorityCase.reminders?.length" class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="reminder in priorityCase.reminders.slice(0, 3)"
                                    :key="`${priorityCase.id}-${reminder.type}`"
                                    type="button"
                                    class="rounded-full bg-slate-100 px-3 py-1.5 text-[12px] font-medium text-slate-700"
                                >
                                    {{ reminder.title }}
                                </button>
                            </div>
                        </template>
                        <p v-else class="mt-1.5 text-[15px] font-semibold text-slate-900">
                            Your cases look up to date right now.
                        </p>
                    </div>
                    <Link
                        v-if="priorityCase"
                        :href="caseHref(priorityCase)"
                        class="portal-inline-link"
                    >
                        {{ priorityCase.next_action?.button_label || 'Open case' }}
                    </Link>
                </div>
            </div>
        </section>

        <section class="mt-8 space-y-8">
            <div v-if="casesNeedingAction.length">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="portal-section-heading">Needs your attention</h2>
                        <p class="mt-1 text-sm text-slate-500">Open the case with the highlighted next step first.</p>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <Link
                        v-for="(item, index) in casesNeedingAction"
                        :key="item.id"
                        :href="caseHref(item)"
                        class="portal-case-card"
                        :class="index === 0 ? 'portal-case-card-active' : ''"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm text-slate-500">{{ item.country }} • {{ item.visa_type }}</p>
                        <p class="mt-1.5 text-[19px] font-semibold tracking-tight text-slate-900">{{ item.reference_code }}</p>
                            </div>
                            <span class="!py-0.5 !text-xs" :class="caseStatus(item).tone">
                                {{ caseStatus(item).label }}
                            </span>
                        </div>

                        <p class="mt-3 text-[15px] font-semibold text-slate-900">{{ nextActionLabel(item) }}</p>
                        <p class="mt-1.5 text-[13px] leading-5 text-slate-600">{{ nextActionCopy(item) }}</p>

                        <div v-if="item.reminders?.length" class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="reminder in item.reminders.slice(0, 2)"
                                :key="`${item.id}-${reminder.type}`"
                                class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600"
                            >
                                {{ reminder.title }}
                            </span>
                        </div>

                        <div class="mt-4 h-2 rounded-full bg-white/70">
                            <div class="h-2 rounded-full transition-all" :class="progressTone(item.progress_percent)" :style="{ width: `${item.progress_percent}%` }"></div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-slate-500">
                            <span><span class="font-semibold text-slate-900">{{ item.progress_percent }}%</span> progress</span>
                            <span><span class="font-semibold text-slate-900">{{ item.documents_verified }}/{{ item.documents_total }}</span> documents</span>
                            <span v-if="item.unread_messages_count"><span class="font-semibold text-slate-900">{{ item.unread_messages_count }}</span> unread messages</span>
                            <span v-if="item.latest_message_at">Last update {{ item.latest_message_at }}</span>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                            <p class="text-[13px] font-medium text-slate-700">{{ item.stage || t('pages.portal.inProgress') }}</p>
                            <span class="text-[13px] font-medium text-teal-700">Open case</span>
                        </div>
                    </Link>
                </div>
            </div>

            <div v-if="upToDateCases.length">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="portal-section-heading">Everything else</h2>
                        <p class="mt-1 text-sm text-slate-500">Cases your team is already progressing for you.</p>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <Link
                        v-for="item in upToDateCases"
                        :key="item.id"
                        :href="caseHref(item)"
                        class="portal-case-card"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm text-slate-500">{{ item.country }} • {{ item.visa_type }}</p>
                                <p class="mt-1.5 text-[19px] font-semibold tracking-tight text-slate-900">{{ item.reference_code }}</p>
                            </div>
                            <span class="portal-chip-success !py-0.5 !text-xs">Up to date</span>
                        </div>

                        <p class="mt-3 text-[13px] leading-5 text-slate-600">Your team is working on this case. We’ll show you anything new here when action is needed.</p>

                        <div class="mt-4 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full transition-all" :class="progressTone(item.progress_percent)" :style="{ width: `${item.progress_percent}%` }"></div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-slate-500">
                            <span><span class="font-semibold text-slate-900">{{ item.progress_percent }}%</span> progress</span>
                            <span><span class="font-semibold text-slate-900">{{ item.stage || t('pages.portal.inProgress') }}</span> stage</span>
                            <span v-if="item.latest_message_at">Last update {{ item.latest_message_at }}</span>
                        </div>
                    </Link>
                </div>
            </div>

            <AppCard v-if="!cases.length" :title="t('pages.portal.yourCases')">
                <EmptyState
                    icon="users"
                    :title="t('pages.portal.noActiveCasesTitle')"
                    :description="t('pages.portal.noActiveCasesDescription')"
                />
            </AppCard>
        </section>
    </PortalLayout>
</template>
