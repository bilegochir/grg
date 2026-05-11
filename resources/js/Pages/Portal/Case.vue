<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    business: Object,
    applicant: Object,
    case: Object,
});

const portalCase = props.case;
const activeTab = ref('overview');
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

const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'documents', label: 'Documents' },
    { key: 'billing', label: 'Billing' },
    { key: 'messages', label: 'Messages' },
];

const openTasks = computed(() => portalCase.tasks.filter((task) => task.status !== 'completed'));
const completedTasks = computed(() => portalCase.tasks.filter((task) => task.status === 'completed'));
const totalPaid = computed(() => portalCase.invoices.reduce((sum, invoice) => sum + Number(invoice.paid_amount), 0).toFixed(2));

const workflowStateClasses = (state) => {
    if (state === 'completed') return 'border-green-200 bg-green-50 text-green-700';
    if (state === 'current') return 'border-brand-primary/30 bg-brand-primary/10 text-brand-primary';

    return 'border-slate-200 bg-white text-brand-muted';
};

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

    return 'bg-amber-50 text-amber-700';
};

const invoiceStatusClasses = (status) => {
    if (status === 'paid') return 'bg-green-50 text-green-700';
    if (status === 'partially_paid') return 'bg-blue-50 text-blue-700';
    if (status === 'overdue') return 'bg-red-50 text-red-700';

    return 'bg-amber-50 text-amber-700';
};

const timelineIcon = (type) => {
    if (type === 'payment') return 'check';
    if (type === 'document') return 'note';
    if (type === 'appointment') return 'clock';

    return 'sparkle';
};
</script>

<template>
    <PortalLayout :title="portalCase.reference_code" :business="business" :applicant="applicant">
        <section class="rounded-[28px] border border-orange-100 bg-white px-6 py-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)] sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="ui-kicker text-orange-500">Your case</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h1 class="text-[32px] leading-tight">{{ portalCase.reference_code }}</h1>
                        <span class="rounded-full bg-orange-50 px-3 py-1 text-sm font-medium text-orange-700">
                            {{ portalCase.stage || 'In progress' }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-brand-muted">{{ portalCase.country }} • {{ portalCase.visa_type }}</p>
                    <p class="mt-4 max-w-3xl text-base leading-7 text-brand-text">{{ portalCase.stage_copy }}</p>
                    <p class="mt-3 text-sm font-medium text-brand-text">{{ portalCase.next_step_copy }}</p>
                </div>

                <Link :href="route('portal.dashboard')" class="ui-button-secondary">Back to overview</Link>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-sm text-brand-muted">Progress</p>
                    <p class="mt-2 text-2xl font-semibold text-brand-text">{{ portalCase.progress_percent }}%</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-sm text-brand-muted">Documents</p>
                    <p class="mt-2 text-2xl font-semibold text-brand-text">{{ portalCase.summary.documents_verified }}/{{ portalCase.summary.documents_total }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-sm text-brand-muted">Checklist</p>
                    <p class="mt-2 text-2xl font-semibold text-brand-text">{{ portalCase.summary.completed_tasks_count }}/{{ portalCase.tasks.length }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-sm text-brand-muted">Balance due</p>
                    <p class="mt-2 text-2xl font-semibold text-brand-text">${{ portalCase.summary.balance_due }}</p>
                </div>
            </div>

            <div class="mt-6">
                <div class="h-2 rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-brand-primary transition-all" :style="{ width: `${portalCase.progress_percent}%` }"></div>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="step in portalCase.workflow"
                        :key="step.id"
                        class="rounded-2xl border px-4 py-3"
                        :class="workflowStateClasses(step.state)"
                    >
                        <p class="text-sm font-medium">{{ step.name }}</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_320px]">
            <div class="space-y-6">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="ui-tab-button"
                        :class="activeTab === tab.key ? 'ui-tab-button-active' : 'ui-tab-button-inactive'"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div v-if="activeTab === 'overview'" class="space-y-6">
                    <AppCard title="Checklist">
                        <div v-if="portalCase.tasks.length" class="space-y-3">
                            <div
                                v-for="task in portalCase.tasks"
                                :key="task.id"
                                class="rounded-2xl border border-slate-200 px-4 py-4"
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
                        </div>
                        <EmptyState
                            v-else
                            icon="check"
                            title="Nothing pending right now"
                            description="Your team does not need any checklist items from you at the moment."
                        />
                    </AppCard>

                    <AppCard title="Appointments">
                        <div v-if="portalCase.appointments.length" class="space-y-3">
                            <div v-for="appointment in portalCase.appointments" :key="appointment.id" class="rounded-2xl border border-slate-200 px-4 py-4">
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
                                        Join meeting
                                    </a>
                                </div>
                            </div>
                        </div>
                        <EmptyState
                            v-else
                            icon="clock"
                            title="No appointments yet"
                            description="If your team schedules a consultation or embassy step, it will show up here."
                        />
                    </AppCard>

                    <AppCard title="Recent updates">
                        <div v-if="portalCase.timeline.length" class="space-y-4">
                            <div v-for="item in portalCase.timeline" :key="`${item.type}-${item.title}-${item.at}`" class="flex gap-4">
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
                            title="No updates yet"
                            description="Progress updates will appear here as your case moves forward."
                        />
                    </AppCard>
                </div>

                <div v-else-if="activeTab === 'documents'" class="space-y-6">
                    <AppCard title="Document checklist">
                        <template #action>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                                {{ portalCase.summary.documents_verified }}/{{ portalCase.summary.documents_total }} verified
                            </div>
                        </template>

                        <div v-if="portalCase.documents.length" class="space-y-4">
                            <div
                                v-for="document in portalCase.documents"
                                :key="document.id"
                                class="rounded-[20px] border border-slate-200 bg-white px-4 py-4"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="font-medium text-brand-text">{{ document.name }}</p>
                                            <span class="rounded-full px-3 py-1 text-xs font-medium" :class="documentStatusClasses(document.status_value)">
                                                {{ document.status }}
                                            </span>
                                        </div>
                                        <p v-if="document.client_instructions" class="mt-2 text-sm leading-6 text-brand-text">
                                            {{ document.client_instructions }}
                                        </p>
                                        <p class="mt-2 text-sm text-brand-muted">{{ document.status_copy }}</p>
                                    </div>
                                    <span v-if="document.is_required" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                        Required
                                    </span>
                                </div>

                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-brand-muted">
                                        {{ document.accepted_file_types.join(', ').toUpperCase() || 'PDF or image files' }}
                                        <span v-if="document.max_file_size_mb"> • {{ document.max_file_size_mb }}MB max</span>
                                    </div>
                                    <div v-if="document.sample_hint || document.expiry_date" class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-brand-muted">
                                        <span v-if="document.sample_hint">{{ document.sample_hint }}</span>
                                        <span v-if="document.sample_hint && document.expiry_date"> • </span>
                                        <span v-if="document.expiry_date">Expires {{ document.expiry_date }}</span>
                                    </div>
                                </div>

                                <div v-if="document.rejection_reason" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                    <p class="text-sm font-medium text-red-700">What needs fixing</p>
                                    <p class="mt-1 text-sm leading-6 text-red-700">{{ document.rejection_reason }}</p>
                                </div>

                                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4">
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
                                                :disabled="uploading[document.id]"
                                                @click="uploadDocument(document.id)"
                                            >
                                                {{ uploading[document.id] ? 'Uploading...' : 'Send file' }}
                                            </button>
                                            <a v-if="document.latest_version" :href="document.latest_version.download_url" class="ui-button-secondary">
                                                View file
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <EmptyState
                            v-else
                            icon="note"
                            title="No documents requested right now"
                            description="If your team needs something from you, it will show up here."
                        />
                    </AppCard>
                </div>

                <div v-else-if="activeTab === 'billing'" class="space-y-6">
                    <AppCard title="Payments and invoices">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-sm text-brand-muted">Total paid</p>
                                <p class="mt-2 text-2xl font-semibold text-brand-text">${{ totalPaid }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-sm text-brand-muted">Balance due</p>
                                <p class="mt-2 text-2xl font-semibold text-brand-text">${{ portalCase.summary.balance_due }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-sm text-brand-muted">Invoices</p>
                                <p class="mt-2 text-2xl font-semibold text-brand-text">{{ portalCase.invoices.length }}</p>
                            </div>
                        </div>

                        <div v-if="portalCase.invoices.length" class="mt-5 space-y-4">
                            <div v-for="invoice in portalCase.invoices" :key="invoice.id" class="rounded-[20px] border border-slate-200 px-4 py-4">
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

                                <div v-if="invoice.line_items.length" class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                                    <div
                                        v-for="(lineItem, index) in invoice.line_items"
                                        :key="`${invoice.id}-${index}`"
                                        class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-3 text-sm last:border-b-0"
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

                        <div v-if="portalCase.messages.length" class="mt-6 space-y-3">
                            <div
                                v-for="message in portalCase.messages"
                                :key="message.id"
                                class="rounded-2xl px-4 py-4"
                                :class="message.direction === 'inbound' ? 'bg-orange-50' : 'bg-slate-50'"
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
                <AppCard title="At a glance">
                    <div class="space-y-4 text-sm">
                        <div class="rounded-xl bg-slate-50 px-4 py-4">
                            <p class="text-brand-muted">Current stage</p>
                            <p class="mt-1 font-medium text-brand-text">{{ portalCase.stage || 'In progress' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-4">
                            <p class="text-brand-muted">Still needed</p>
                            <p class="mt-1 font-medium text-brand-text">{{ openTasks.length }} checklist items</p>
                            <p class="mt-1 font-medium text-brand-text">{{ portalCase.summary.documents_waiting_count }} documents</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-4">
                            <p class="text-brand-muted">Next appointment</p>
                            <p class="mt-1 font-medium text-brand-text">
                                {{ portalCase.summary.next_appointment_at || 'Nothing scheduled yet' }}
                            </p>
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Need help?">
                    <p class="text-sm leading-6 text-brand-text">
                        If something is unclear, send a message in the portal and your visa team will guide you on the next step.
                    </p>
                    <div class="mt-4 space-y-2 text-sm text-brand-muted">
                        <p v-if="business.email">{{ business.email }}</p>
                        <p v-if="business.phone">{{ business.phone }}</p>
                    </div>
                </AppCard>
            </div>
        </div>
    </PortalLayout>
</template>
