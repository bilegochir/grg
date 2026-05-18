<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { useLocale } from '@/lib/i18n';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    business: Object,
    applicant: Object,
    case: Object,
});

const portalCase = props.case;
const allowedTabs = ['overview', 'documents', 'billing', 'messages'];
const initialTab = typeof window !== 'undefined'
    ? new URLSearchParams(window.location.search).get('tab')
    : null;
const activeTab = ref(allowedTabs.includes(initialTab) ? initialTab : 'overview');
const messageForm = useForm({
    body: '',
});
const selectedFiles = reactive({});
const uploading = reactive({});

const sendMessage = () => {
    messageForm.post(route('portal.cases.messages.store', props.case.id), {
        preserveScroll: true,
        onSuccess: () => messageForm.reset(),
    });
};

const uploadDocument = (documentId) => {
    if (!selectedFiles[documentId]) {
        return;
    }

    uploading[documentId] = true;

    router.post(
        route('portal.cases.documents.upload', { case: props.case.id, document: documentId }),
        { file: selectedFiles[documentId] },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                uploading[documentId] = false;
                selectedFiles[documentId] = null;
            },
        },
    );
};

const openTasks = computed(() => portalCase.tasks.filter((task) => task.status !== 'completed'));
const completedTasks = computed(() => portalCase.tasks.filter((task) => task.status === 'completed'));
const totalPaid = computed(() => portalCase.invoices.reduce((sum, invoice) => sum + Number(invoice.paid_amount), 0).toFixed(2));
const orderedDocuments = computed(() => [
    ...portalCase.documents.filter((document) => document.status_value !== 'verified'),
    ...portalCase.documents.filter((document) => document.status_value === 'verified'),
]);
const pendingDocuments = computed(() => orderedDocuments.value.filter((document) => document.status_value !== 'verified'));
const verifiedDocuments = computed(() => orderedDocuments.value.filter((document) => document.status_value === 'verified'));
const nextAttentionCount = computed(() => openTasks.value.length + portalCase.summary.documents_waiting_count);
const latestMessages = computed(() => [...portalCase.messages].sort((a, b) => new Date(b.sent_at).getTime() - new Date(a.sent_at).getTime()));
const priorityDocuments = computed(() => orderedDocuments.value.filter((document) => document.status_value === 'pending' || document.status_value === 'rejected'));
const overdueInvoices = computed(() => portalCase.invoices.filter((invoice) => invoice.status === 'overdue'));
const waitingPaymentInvoices = computed(() => portalCase.invoices.filter((invoice) => invoice.status === 'sent' || invoice.status === 'partially_paid'));
const nextAction = computed(() => portalCase.next_action);
const reminders = computed(() => portalCase.reminders || []);
const primaryActionLabel = computed(() => nextAction.value?.button_label || 'Open overview');
const primaryActionTab = computed(() => nextAction.value?.target_tab || 'overview');
const nextActionSummary = computed(() => nextAction.value?.description || 'Everything important is up to date right now.');

const caseStatus = computed(() => {
    if (nextAction.value?.target_tab === 'documents') {
        return {
            label: 'Action needed',
            tone: 'portal-chip-warm',
            helper: 'Your case is waiting on documents from you.',
        };
    }

    if (nextAction.value?.target_tab === 'messages') {
        return {
            label: 'New update',
            tone: 'portal-chip-brand',
            helper: 'Your visa team has sent you a message to review.',
        };
    }

    if (nextAction.value?.target_tab === 'billing') {
        return {
            label: 'Payment review',
            tone: 'portal-chip-muted',
            helper: 'Your case has invoice items that still need attention.',
        };
    }

    if (openTasks.value.length) {
        return {
            label: 'In progress',
            tone: 'portal-chip-brand',
            helper: 'There are still a few open checklist steps on this case.',
        };
    }

    return {
        label: 'Waiting on team',
        tone: 'portal-chip-success',
        helper: 'You are up to date. Your visa team is working on the next step.',
    };
});

const applicantActionItems = computed(() => {
    const items = [];

    if (priorityDocuments.value.length) {
        items.push({
            key: 'documents',
            title: `Send ${priorityDocuments.value.length} document${priorityDocuments.value.length === 1 ? '' : 's'}`,
            body: 'These files are still missing or need to be corrected before your case can move forward.',
            cta: 'Open documents',
            tab: 'documents',
        });
    }

    if (openTasks.value.length) {
        items.push({
            key: 'checklist',
            title: `Review ${openTasks.value.length} checklist item${openTasks.value.length === 1 ? '' : 's'}`,
            body: 'These are the remaining steps your team still needs help with or is waiting to confirm.',
            cta: 'Review checklist',
            tab: 'overview',
        });
    }

    if (waitingPaymentInvoices.value.length || overdueInvoices.value.length) {
        const invoiceCount = waitingPaymentInvoices.value.length + overdueInvoices.value.length;

        items.push({
            key: 'billing',
            title: `Review ${invoiceCount} invoice${invoiceCount === 1 ? '' : 's'}`,
            body: overdueInvoices.value.length ? 'At least one payment is overdue.' : 'A payment is waiting to be reviewed.',
            cta: 'Open billing',
            tab: 'billing',
        });
    }

    return items;
});

const latestTimelineItems = computed(() => portalCase.timeline.slice(0, 4));
const actionCards = computed(() => [
    {
        key: 'documents',
        title: 'Documents',
        emphasis: pendingDocuments.value.length ? `${pendingDocuments.value.length} still needed` : `${verifiedDocuments.value.length}/${portalCase.summary.documents_total} verified`,
        helper: pendingDocuments.value.length ? 'Send or replace the files your team still needs.' : 'Your current requested files are already in place.',
        button: pendingDocuments.value.length ? 'Open documents' : 'Review documents',
        tab: 'documents',
        active: primaryActionTab.value === 'documents',
    },
    {
        key: 'checklist',
        title: 'Checklist',
        emphasis: openTasks.value.length ? `${openTasks.value.length} step${openTasks.value.length === 1 ? '' : 's'} open` : 'All steps complete',
        helper: openTasks.value.length ? 'Review the remaining tasks connected to this case.' : 'There are no outstanding checklist items right now.',
        button: 'Open overview',
        tab: 'overview',
        active: primaryActionTab.value === 'overview' && openTasks.value.length > 0,
    },
    {
        key: 'billing',
        title: 'Billing',
        emphasis: waitingPaymentInvoices.value.length || overdueInvoices.value.length ? `$${portalCase.summary.balance_due} due` : 'No payment pending',
        helper: waitingPaymentInvoices.value.length || overdueInvoices.value.length ? 'Review invoices or payment details for this case.' : 'No invoice action is needed from you right now.',
        button: 'Open billing',
        tab: 'billing',
        active: primaryActionTab.value === 'billing',
    },
]);

const quickFacts = computed(() => [
    {
        label: 'Current stage',
        value: portalCase.stage || 'In progress',
        helper: 'Your case is moving through this step now.',
    },
    {
        label: 'Next appointment',
        value: portalCase.summary.next_appointment_at || 'Nothing scheduled yet',
        helper: 'If your team books a meeting, it will show here.',
    },
    {
        label: 'Balance due',
        value: `$${portalCase.summary.balance_due}`,
        helper: overdueInvoices.value.length ? 'A payment is overdue.' : 'Any invoice payments will appear here.',
    },
]);

const openPrimaryTab = () => {
    activeTab.value = primaryActionTab.value;
};

const openTab = (tab) => {
    activeTab.value = allowedTabs.includes(tab) ? tab : 'overview';
};

const tabs = computed(() => [
    { key: 'overview', label: t('common.overview') },
    { key: 'documents', label: `${t('common.documents')} (${pendingDocuments.value.length})` },
    { key: 'billing', label: `${t('common.billing')} (${portalCase.invoices.length})` },
    { key: 'messages', label: `${t('common.messages')} (${portalCase.messages.length})` },
]);

const workflowCompletedCount = computed(() => portalCase.workflow.filter((step) => step.state === 'completed').length);
const currentWorkflowStep = computed(() => portalCase.workflow.find((step) => step.state === 'current') ?? null);

const documentStatusClasses = (status) => {
    if (status === 'verified') return 'bg-green-50 text-green-700';
    if (status === 'uploaded') return 'bg-blue-50 text-blue-700';
    if (status === 'rejected') return 'bg-red-50 text-red-700';

    return 'bg-slate-100 text-slate-600';
};

const taskStatusClasses = (status) => {
    if (status === 'completed') return 'bg-green-50 text-green-700';
    if (status === 'in_progress') return 'bg-blue-50 text-blue-700';
    if (status === 'skipped') return 'bg-slate-100 text-slate-600';

    return 'bg-blue-50 text-blue-700';
};

const invoiceStatusClasses = (status) => {
    if (status === 'paid') return 'bg-green-50 text-green-700';
    if (status === 'partially_paid') return 'bg-blue-50 text-blue-700';
    if (status === 'overdue') return 'bg-red-50 text-red-700';

    return 'bg-slate-100 text-slate-700';
};

const timelineIcon = (type) => {
    if (type === 'payment') return 'check';
    if (type === 'document') return 'note';
    if (type === 'appointment') return 'clock';

    return 'sparkle';
};

const clearSelectedFile = (documentId) => {
    selectedFiles[documentId] = null;
};

const { t } = useLocale();
</script>

<template>
    <PortalLayout :title="portalCase.reference_code" :business="business" :applicant="applicant">
        <section class="portal-hero">
            <div class="portal-summary-card">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="ui-kicker text-slate-500">{{ t('pages.portal.yourCase') }}</p>
                        <h1 class="mt-1.5 text-[21px] font-semibold tracking-tight text-slate-900">{{ portalCase.reference_code }}</h1>
                        <p class="mt-1 text-[13px] text-slate-500">{{ applicant.name }} • {{ portalCase.country }} • {{ portalCase.visa_type }}</p>
                    </div>

                    <Link :href="route('portal.dashboard')" class="portal-inline-link shrink-0">{{ t('common.backToOverview') }}</Link>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-4">
                    <div class="portal-stat-card">
                        <p class="text-[12px] text-slate-500">Status</p>
                        <div class="mt-1.5"><span :class="caseStatus.tone">{{ caseStatus.label }}</span></div>
                    </div>
                    <div class="portal-stat-card">
                        <p class="text-[12px] text-slate-500">Current stage</p>
                        <p class="mt-1.5 text-[16px] font-semibold tracking-tight text-slate-900">{{ portalCase.stage || t('pages.portal.inProgress') }}</p>
                    </div>
                    <div class="portal-stat-card">
                        <p class="text-[12px] text-slate-500">Documents</p>
                        <p class="mt-1.5 text-[16px] font-semibold tracking-tight text-slate-900">{{ portalCase.summary.documents_verified }}/{{ portalCase.summary.documents_total }}</p>
                    </div>
                    <div class="portal-stat-card">
                        <p class="text-[12px] text-slate-500">{{ portalCase.summary.next_appointment_at ? 'Next appointment' : 'Balance due' }}</p>
                        <p class="mt-1.5 text-[16px] font-semibold tracking-tight text-slate-900">{{ portalCase.summary.next_appointment_at || `$${portalCase.summary.balance_due}` }}</p>
                    </div>
                </div>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p class="text-[12px] uppercase tracking-[0.12em] text-slate-500">What happens next</p>
                    <p class="mt-1.5 max-w-3xl text-[13px] leading-5 text-slate-600">{{ portalCase.stage_copy }}</p>

                    <div class="mt-3 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div class="min-w-0">
                            <p class="text-[15px] font-semibold text-slate-900">{{ nextAction?.label || 'You are up to date' }}</p>
                            <p class="mt-1 text-[13px] leading-5 text-slate-600">{{ nextActionSummary }}</p>
                            <p v-if="currentWorkflowStep" class="mt-1 text-[13px] text-slate-500">
                                Current step: {{ currentWorkflowStep.name }}
                            </p>
                        </div>
                        <button type="button" class="portal-inline-link" @click="openPrimaryTab">
                            {{ primaryActionLabel }}
                        </button>
                    </div>

                    <div v-if="reminders.length" class="mt-4 grid gap-3 md:grid-cols-2">
                        <button
                            v-for="reminder in reminders"
                            :key="reminder.type"
                            type="button"
                            class="rounded-2xl bg-slate-50 px-4 py-3 text-left ring-1 ring-slate-200/70 transition hover:bg-slate-100/80"
                            @click="openTab(reminder.target_tab)"
                        >
                            <p class="text-[13px] font-medium text-slate-900">{{ reminder.title }}</p>
                            <p class="mt-1 text-[12px] leading-5 text-slate-600">{{ reminder.body }}</p>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-5 portal-choice-grid lg:grid-cols-1">
                    <button
                        v-for="card in actionCards"
                        :key="card.key"
                        type="button"
                        class="portal-choice-card text-left"
                        :class="card.active ? 'portal-choice-card-active' : ''"
                        @click="activeTab = card.tab"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[12px] text-slate-500">{{ card.title }}</p>
                                <p class="mt-1.5 text-[18px] font-semibold tracking-tight text-slate-900">{{ card.emphasis }}</p>
                            </div>
                            <span v-if="card.active" class="portal-chip-brand !py-0.5 !text-xs">Next</span>
                        </div>
                        <p class="mt-2.5 text-[13px] leading-5 text-slate-600">{{ card.helper }}</p>
                        <div class="mt-3.5 flex items-center justify-between gap-4 pt-3">
                            <span class="text-[13px] font-medium text-slate-700">{{ card.button }}</span>
                            <span class="text-[13px] font-medium text-teal-700">Open</span>
                        </div>
                    </button>

                    <button type="button" class="portal-choice-card text-left" @click="activeTab = 'messages'">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[12px] text-slate-500">Messages</p>
                                <p class="mt-1.5 text-[18px] font-semibold tracking-tight text-slate-900">{{ portalCase.messages.length }}</p>
                            </div>
                            <span class="portal-chip-muted !py-0.5 !text-xs">Support</span>
                        </div>
                        <p class="mt-2.5 text-[13px] leading-5 text-slate-600">Send a question or update to your visa team any time from the portal.</p>
                        <div class="mt-3.5 flex items-center justify-between gap-4 pt-3">
                            <span class="text-[13px] font-medium text-slate-700">Ask a question</span>
                            <span class="text-[13px] font-medium text-teal-700">Open</span>
                        </div>
                    </button>
            </div>
        </section>

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_320px]">
            <div class="space-y-6">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="ui-tab-button rounded-full px-3 py-2"
                        :class="activeTab === tab.key ? 'ui-tab-button-active' : 'ui-tab-button-inactive'"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div v-if="activeTab === 'overview'" class="space-y-6">
                    <AppCard title="Next action">
                        <div class="rounded-2xl bg-slate-50 px-4 py-4 ring-1 ring-slate-200/70">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="font-medium text-brand-text">{{ nextAction?.label || 'You are up to date' }}</p>
                                    <p class="mt-2 text-sm leading-6 text-brand-muted">{{ nextActionSummary }}</p>
                                </div>
                                <button type="button" class="ui-button-secondary whitespace-nowrap" @click="openPrimaryTab">
                                    {{ primaryActionLabel }}
                                </button>
                            </div>
                        </div>
                    </AppCard>

                    <AppCard title="What you need to do">
                        <div v-if="applicantActionItems.length" class="space-y-3">
                            <div
                                v-for="item in applicantActionItems"
                                :key="item.key"
                                class="rounded-2xl bg-white/92 px-4 py-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)] ring-1 ring-slate-200/70"
                            >
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p class="font-medium text-brand-text">{{ item.title }}</p>
                                        <p class="mt-2 text-sm leading-6 text-brand-muted">{{ item.body }}</p>
                                    </div>
                                    <button type="button" class="ui-button-secondary whitespace-nowrap" @click="activeTab = item.tab">
                                        {{ item.cta }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-2xl border border-green-200 bg-green-50 px-4 py-4">
                            <p class="font-medium text-green-900">You are up to date</p>
                            <p class="mt-2 text-sm leading-6 text-green-800">
                                There is nothing urgent for you right now. Your visa team is handling the next step and will contact you here if anything new is needed.
                            </p>
                        </div>
                    </AppCard>

                    <AppCard :title="t('common.checklist')">
                        <div v-if="openTasks.length" class="space-y-3">
                            <p class="text-sm text-brand-muted">{{ openTasks.length }} open item{{ openTasks.length === 1 ? '' : 's' }} still connected to this case.</p>
                            <div
                                v-for="task in openTasks"
                                :key="task.id"
                                class="rounded-2xl bg-slate-50/70 px-4 py-4"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="font-medium text-brand-text">{{ task.name }}</p>
                                            <span class="rounded-full px-3 py-1 text-xs font-medium" :class="taskStatusClasses(task.status)">
                                                {{ task.status_label }}
                                            </span>
                                        </div>
                                        <p v-if="task.description" class="mt-2 text-sm leading-6 text-brand-text">{{ task.description }}</p>
                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-brand-muted">
                                            <span v-if="task.due_at">Due {{ task.due_at }}</span>
                                            <span v-if="task.stage_name">{{ task.stage_name }}</span>
                                        </div>
                                    </div>
                                    <span v-if="task.is_required" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                        Required
                                    </span>
                                </div>
                            </div>
                            <details v-if="completedTasks.length" class="rounded-2xl bg-slate-50/65 px-4 py-4 ring-1 ring-slate-200/60">
                                <summary class="cursor-pointer list-none text-sm font-medium text-brand-text">
                                    {{ completedTasks.length }} completed item{{ completedTasks.length === 1 ? '' : 's' }}
                                </summary>
                                <div class="mt-4 space-y-3">
                                    <div
                                        v-for="task in completedTasks"
                                        :key="`completed-${task.id}`"
                                        class="rounded-2xl bg-white px-4 py-4 ring-1 ring-slate-200/60"
                                    >
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <p class="font-medium text-brand-text">{{ task.name }}</p>
                                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="taskStatusClasses(task.status)">
                                                        {{ task.status_label }}
                                                    </span>
                                                </div>
                                                <p v-if="task.description" class="mt-2 text-sm leading-6 text-brand-text">{{ task.description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                        <EmptyState
                            v-else
                            icon="check"
                            :title="t('pages.portal.noPendingTitle')"
                            :description="t('pages.portal.noPendingDescription')"
                        />
                    </AppCard>

                    <div class="grid gap-6 xl:grid-cols-2">
                        <AppCard :title="t('common.appointments')">
                            <div v-if="portalCase.appointments.length" class="space-y-3">
                                <div v-for="appointment in portalCase.appointments" :key="appointment.id" class="rounded-2xl bg-white/92 px-4 py-4 ring-1 ring-slate-200/70 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-brand-text">{{ appointment.title }}</p>
                                            <p class="mt-1 text-sm text-brand-muted">{{ appointment.starts_at }}</p>
                                            <p v-if="appointment.location" class="mt-1 text-sm text-brand-muted">{{ appointment.location }}</p>
                                            <p v-if="appointment.notes" class="mt-3 text-sm leading-6 text-brand-text">{{ appointment.notes }}</p>
                                        </div>
                                        <a
                                            v-if="appointment.meeting_link"
                                            :href="appointment.meeting_link"
                                            target="_blank"
                                            class="ui-button-secondary"
                                        >
                                            {{ t('pages.portal.joinMeeting') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <EmptyState
                                v-else
                                icon="clock"
                                :title="t('pages.portal.noAppointmentsTitle')"
                                :description="t('pages.portal.noAppointmentsDescription')"
                            />
                        </AppCard>

                        <AppCard :title="t('pages.portal.recentUpdates')">
                            <div v-if="latestTimelineItems.length" class="space-y-4">
                                <div v-for="item in latestTimelineItems" :key="`${item.type}-${item.title}-${item.at}`" class="flex gap-4">
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-brand-muted">
                                        <AppIcon :name="timelineIcon(item.type)" :size="16" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-brand-text">{{ item.title }}</p>
                                        <p class="mt-1 text-sm leading-6 text-brand-text">{{ item.body }}</p>
                                        <p class="mt-1 text-xs text-brand-muted">{{ item.at }}</p>
                                    </div>
                                </div>
                            </div>
                            <EmptyState
                                v-else
                                icon="sparkle"
                                :title="t('pages.portal.noUpdatesTitle')"
                                :description="t('pages.portal.noUpdatesDescription')"
                            />
                        </AppCard>
                    </div>
                </div>

                <div v-else-if="activeTab === 'documents'" class="space-y-6">
                    <AppCard :title="t('pages.portal.documentChecklist')">
                        <template #action>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                                {{ portalCase.summary.documents_verified }}/{{ portalCase.summary.documents_total }} verified
                            </div>
                        </template>

                        <div v-if="priorityDocuments.length" class="mb-4 rounded-2xl bg-blue-50 px-4 py-4 ring-1 ring-blue-200/80">
                            <p class="text-sm font-medium text-blue-900">Start with these documents</p>
                            <p class="mt-1 text-sm text-blue-700">
                                These are the files still waiting on you or need to be corrected before your case can move forward.
                            </p>
                        </div>

                        <div v-if="pendingDocuments.length" class="space-y-4">
                            <div
                                v-for="document in pendingDocuments"
                                :key="document.id"
                                class="rounded-[20px] bg-white/94 px-4 py-4 ring-1 ring-slate-200/70 shadow-[0_8px_20px_rgba(15,23,42,0.04)]"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="font-medium text-brand-text">{{ document.name }}</p>
                                            <span class="rounded-full px-3 py-1 text-xs font-medium" :class="documentStatusClasses(document.status_value)">
                                                {{ document.status }}
                                            </span>
                                        </div>
                                        <p v-if="document.what_needed" class="mt-2 text-sm leading-6 text-brand-text">
                                            {{ document.what_needed }}
                                        </p>
                                        <p class="mt-2 text-sm text-brand-muted">{{ document.status_copy }}</p>
                                    </div>
                                    <span v-if="document.is_required" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                        Required
                                    </span>
                                </div>

                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Why we need it</p>
                                        <p class="mt-1.5 text-sm leading-6 text-slate-700">
                                            {{ document.why_needed }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Accepted files</p>
                                        <p class="mt-1.5 text-sm text-slate-700">
                                            {{ document.accepted_file_types_label || 'PDF or image files' }}
                                            <span v-if="document.max_file_size_mb"> • {{ document.max_file_size_mb }}MB max</span>
                                        </p>
                                        <p v-if="document.sample_hint || document.expiry_date" class="mt-1.5 text-sm text-slate-500">
                                            <span v-if="document.sample_hint">{{ document.sample_hint }}</span>
                                            <span v-if="document.sample_hint && document.expiry_date"> • </span>
                                            <span v-if="document.expiry_date">Expires {{ document.expiry_date }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div v-if="document.what_to_fix" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                    <p class="text-sm font-medium text-red-700">What needs fixing</p>
                                    <p class="mt-1 text-sm leading-6 text-red-700">{{ document.what_to_fix }}</p>
                                </div>

                                <div class="mt-4 rounded-2xl bg-slate-50/80 px-4 py-4 ring-1 ring-slate-200/60">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-brand-text">Upload or replace file</p>
                                            <p class="mt-1 text-sm text-brand-muted">
                                                We’ll send the newest version to your visa team for review.
                                            </p>
                                            <p v-if="document.latest_version" class="mt-2 text-sm text-brand-muted">
                                                Latest file: {{ document.latest_version.original_name }}
                                                <span v-if="document.latest_version.created_at"> • {{ document.latest_version.created_at }}</span>
                                            </p>
                                        </div>
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                            <input
                                                type="file"
                                                class="block text-sm text-brand-muted"
                                                @change="selectedFiles[document.id] = $event.target.files[0]"
                                            />
                                            <button
                                                type="button"
                                                class="ui-button-primary"
                                                :disabled="uploading[document.id] || !selectedFiles[document.id]"
                                                @click="uploadDocument(document.id)"
                                            >
                                                {{ uploading[document.id] ? 'Uploading...' : 'Send file' }}
                                            </button>
                                            <button
                                                v-if="selectedFiles[document.id] && !uploading[document.id]"
                                                type="button"
                                                class="ui-button-ghost"
                                                @click="clearSelectedFile(document.id)"
                                            >
                                                Clear
                                            </button>
                                            <a v-if="document.latest_version" :href="document.latest_version.download_url" class="ui-button-secondary">
                                                View file
                                            </a>
                                        </div>
                                    </div>
                                    <p v-if="selectedFiles[document.id]" class="mt-3 text-sm text-brand-muted">
                                        Ready to send: {{ selectedFiles[document.id].name }}
                                    </p>
                                    <p v-if="uploading[document.id]" aria-live="polite" class="mt-3 text-sm font-medium text-brand-text">
                                        Uploading your file...
                                    </p>
                                </div>
                            </div>
                        </div>

                        <details v-if="verifiedDocuments.length" class="mt-4 rounded-2xl bg-slate-50/65 px-4 py-4 ring-1 ring-slate-200/60">
                            <summary class="cursor-pointer list-none text-sm font-medium text-brand-text">
                                {{ verifiedDocuments.length }} file{{ verifiedDocuments.length === 1 ? '' : 's' }} already received
                            </summary>
                            <div class="mt-4 space-y-3">
                                <div
                                    v-for="document in verifiedDocuments"
                                    :key="`verified-${document.id}`"
                                    class="rounded-2xl bg-white px-4 py-4 ring-1 ring-slate-200/60"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <p class="font-medium text-brand-text">{{ document.name }}</p>
                                                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="documentStatusClasses(document.status_value)">
                                                    {{ document.status }}
                                                </span>
                                            </div>
                                            <p class="mt-2 text-sm text-brand-muted">{{ document.status_copy }}</p>
                                        </div>
                                        <a v-if="document.latest_version" :href="document.latest_version.download_url" class="ui-button-secondary">
                                            View file
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <EmptyState
                            v-if="!portalCase.documents.length"
                            icon="note"
                            title="No documents requested right now"
                            description="If your team needs something from you, it will show up here."
                        />
                    </AppCard>
                </div>

                <div v-else-if="activeTab === 'billing'" class="space-y-6">
                    <AppCard title="Payments and invoices">
                        <div class="portal-soft-panel mb-5">
                            <p class="text-sm font-medium text-brand-text">How to pay</p>
                            <p class="mt-1 text-sm text-brand-muted">
                                Review your invoice below, then use the bank details if you are paying by transfer. If you have already paid, your team will record it here.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-white/92 px-4 py-4 ring-1 ring-slate-200/70">
                                <p class="text-sm text-brand-muted">Total paid</p>
                                <p class="mt-2 text-2xl font-semibold text-brand-text">${{ totalPaid }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/92 px-4 py-4 ring-1 ring-slate-200/70">
                                <p class="text-sm text-brand-muted">Balance due</p>
                                <p class="mt-2 text-2xl font-semibold text-brand-text">${{ portalCase.summary.balance_due }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/92 px-4 py-4 ring-1 ring-slate-200/70">
                                <p class="text-sm text-brand-muted">Invoices</p>
                                <p class="mt-2 text-2xl font-semibold text-brand-text">{{ portalCase.invoices.length }}</p>
                            </div>
                        </div>

                        <div
                            v-if="business.bank_name || business.bank_account"
                            class="mt-5 rounded-2xl bg-white/92 px-4 py-4 ring-1 ring-slate-200/70"
                        >
                            <p class="text-sm font-medium text-brand-text">Bank transfer details</p>
                            <p class="mt-1 text-sm text-brand-muted">Use these details when paying your invoice by bank transfer.</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-brand-muted">Bank name</p>
                                    <p class="mt-1 text-sm font-medium text-brand-text">{{ business.bank_name || 'Not provided yet' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-brand-muted">Bank account</p>
                                    <p class="mt-1 text-sm font-medium text-brand-text">{{ business.bank_account || 'Not provided yet' }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="portalCase.invoices.length" class="mt-5 space-y-4">
                            <div v-for="invoice in portalCase.invoices" :key="invoice.id" class="rounded-[20px] bg-white/94 px-4 py-4 ring-1 ring-slate-200/70 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="font-medium text-brand-text">{{ invoice.number }}</p>
                                            <span class="rounded-full px-3 py-1 text-xs font-medium" :class="invoiceStatusClasses(invoice.status)">
                                                {{ invoice.status_copy }}
                                            </span>
                                        </div>
                                        <p v-if="invoice.due_at" class="mt-2 text-sm text-brand-muted">Due {{ invoice.due_at }}</p>
                                        <p v-if="invoice.client_message" class="mt-3 text-sm leading-6 text-brand-text">{{ invoice.client_message }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-brand-muted">Balance due</p>
                                        <p class="mt-1 text-lg font-semibold text-brand-text">{{ invoice.currency }} {{ invoice.balance_due }}</p>
                                    </div>
                                </div>

                                <div v-if="invoice.line_items.length" class="mt-4 overflow-hidden rounded-xl bg-slate-50/80 ring-1 ring-slate-200/60">
                                    <div
                                        v-for="(lineItem, index) in invoice.line_items"
                                        :key="`${invoice.id}-${index}`"
                                        class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 text-sm last:border-b-0"
                                    >
                                        <span class="text-brand-text">{{ lineItem.description }}</span>
                                        <span class="font-medium text-brand-text">{{ invoice.currency }} {{ Number(lineItem.amount || 0).toFixed(2) }}</span>
                                    </div>
                                </div>

                                <div v-if="invoice.payments.length" class="mt-4 rounded-xl bg-slate-50 px-4 py-4">
                                    <p class="text-sm font-medium text-brand-text">Recorded payments</p>
                                    <div class="mt-3 space-y-2">
                                        <div v-for="payment in invoice.payments" :key="payment.id" class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                            <span class="text-brand-text">{{ payment.method }} payment</span>
                                            <span class="text-brand-muted">{{ payment.paid_at }} • {{ invoice.currency }} {{ payment.amount }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <EmptyState
                            v-else
                            icon="inbox"
                            title="Nothing due right now"
                            description="Any invoices your team issues will appear here with payment details."
                        />
                    </AppCard>
                </div>

                <div v-else class="space-y-6">
                    <AppCard title="Messages">
                        <form class="space-y-4" @submit.prevent="sendMessage">
                            <div>
                                <InputLabel for="portal_message" value="Message your visa team" />
                                <textarea
                                    id="portal_message"
                                    v-model="messageForm.body"
                                    rows="5"
                                    class="ui-textarea"
                                    placeholder="For example: I uploaded a clearer passport scan, or I need to move my appointment."
                                ></textarea>
                                <InputError :message="messageForm.errors.body" />
                            </div>
                            <PrimaryButton :loading="messageForm.processing">Send message</PrimaryButton>
                        </form>

                        <div v-if="latestMessages.length" class="mt-6 space-y-3">
                            <div
                                v-for="message in latestMessages"
                                :key="message.id"
                                class="rounded-2xl px-4 py-4"
                                :class="message.direction === 'inbound' ? 'bg-blue-50' : 'bg-slate-50'"
                            >
                                <p class="text-sm font-medium text-brand-text">
                                    {{ message.direction === 'inbound' ? 'You' : 'Your team' }}
                                </p>
                                <p class="mt-2 text-sm leading-6 text-brand-text">{{ message.body }}</p>
                                <p class="mt-2 text-xs text-brand-muted">{{ message.sent_at }}</p>
                            </div>
                        </div>

                        <EmptyState
                            v-else
                            icon="inbox"
                            title="No messages yet"
                            description="If you have a question, send it here and your team will reply in this thread."
                        />
                    </AppCard>
                </div>
            </div>

            <div class="space-y-6 xl:sticky xl:top-8 xl:self-start">
                <AppCard title="Case summary">
                        <div class="space-y-4 text-sm">
                            <div class="rounded-xl bg-white/92 px-4 py-4 ring-1 ring-slate-200/70">
                                <p class="text-slate-500">Next action</p>
                            <p class="mt-1 font-medium text-slate-900">{{ nextAction?.label || primaryActionLabel }}</p>
                            <p class="mt-2 text-sm text-slate-600">{{ nextActionSummary }}</p>
                            <button
                                type="button"
                                class="mt-3 text-sm font-medium text-slate-900 hover:underline"
                                @click="openPrimaryTab"
                            >
                                {{ primaryActionLabel }}
                            </button>
                        </div>
                        <div v-if="portalCase.summary.unread_messages_count" class="portal-soft-panel">
                            <p class="text-brand-muted">Unread messages</p>
                            <p class="mt-1 font-medium text-brand-text">{{ portalCase.summary.unread_messages_count }}</p>
                            <p class="mt-2 text-sm text-brand-muted">Your team has sent updates that you have not opened yet.</p>
                        </div>
                        <div v-for="fact in quickFacts" :key="fact.label" class="portal-soft-panel">
                            <p class="text-brand-muted">{{ fact.label }}</p>
                            <p class="mt-1 font-medium text-brand-text">
                                {{ fact.value }}
                            </p>
                            <p class="mt-2 text-sm text-brand-muted">{{ fact.helper }}</p>
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Contact your team">
                    <p class="text-sm leading-6 text-brand-text">
                        If something is unclear, send a message in the portal and your visa team will guide you on the next step.
                    </p>
                    <div class="mt-4 space-y-2 text-sm text-brand-muted">
                        <p v-if="business.email">{{ business.email }}</p>
                        <p v-if="business.phone">{{ business.phone }}</p>
                        <p class="text-xs text-slate-500">
                            Reminders are currently sent by
                            {{ applicant.notification_channels?.email_enabled ? ' email' : '' }}{{ applicant.notification_channels?.email_enabled && applicant.notification_channels?.sms_enabled ? ' and' : '' }}{{ applicant.notification_channels?.sms_enabled ? ' SMS' : '' }}.
                        </p>
                    </div>
                    <button type="button" class="mt-4 ui-button-secondary" @click="activeTab = 'messages'">
                        Open messages
                    </button>
                </AppCard>
            </div>
        </div>
    </PortalLayout>
</template>
