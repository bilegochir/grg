<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SlideOver from '@/Components/SlideOver.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    case: Object,
});

const caseRecord = props.case;

const showTaskSlideOver = ref(false);
const showMessageSlideOver = ref(false);
const showAppointmentSlideOver = ref(false);
const showInvoiceSlideOver = ref(false);

const selectedFiles = reactive({});
const uploading = reactive({});
const expandedPayments = reactive({});

const stageForm = useForm({
    stage_id: caseRecord.stage?.id ?? '',
});

const internalNoteForm = useForm({
    body: '',
    is_client_visible: false,
});

const communicationForm = useForm({
    direction: 'outbound',
    sender_type: 'staff',
    channel: 'email',
    notification_event: 'messages',
    send_notification: true,
    subject: '',
    body: '',
});

const appointmentForm = useForm({
    title: '',
    appointment_type: caseRecord.appointment_types?.[0] ?? 'consultation',
    status: caseRecord.appointment_statuses?.[0] ?? 'scheduled',
    location: '',
    meeting_link: '',
    starts_at: '',
    ends_at: '',
    notes: '',
    assigned_to_user_id: '',
});

const invoiceForm = useForm({
    status: caseRecord.invoice_statuses?.[1] ?? 'sent',
    currency: 'USD',
    issued_at: '',
    due_at: '',
    client_message: '',
    notes: '',
    line_items: [{ label: 'Visa service fee', amount: '' }],
});

const taskCreateForm = useForm({
    title: '',
    description: '',
    assigned_to_user_id: '',
    due_at: '',
    stage_id: '',
});

const documentStatusForms = reactive(
    Object.fromEntries(
        caseRecord.documents.map((document) => [
            document.id,
            {
                status: document.status.value,
                expiry_date: document.expiry_date ?? '',
                rejection_reason: document.rejection_reason ?? '',
                processing: false,
            },
        ]),
    ),
);

const paymentForms = reactive(
    Object.fromEntries(
        caseRecord.invoices.map((invoice) => [
            invoice.id,
            {
                amount: invoice.balance_due,
                method: caseRecord.payment_methods?.[0] ?? 'bank_transfer',
                reference: '',
                notes: '',
                paid_at: '',
                processing: false,
            },
        ]),
    ),
);

const totalBalanceDue = computed(() =>
    caseRecord.invoices.reduce((sum, invoice) => sum + Number(invoice.balance_due || 0), 0).toFixed(2));

const completedTaskCount = computed(() => caseRecord.tasks.filter((task) => task.status === 'completed').length);
const openTaskCount = computed(() => caseRecord.tasks.filter((task) => task.status !== 'completed').length);
const verifiedDocumentCount = computed(() => caseRecord.documents.filter((document) => document.status.value === 'verified').length);
const pendingDocumentCount = computed(() => caseRecord.documents.filter((document) => document.status.value !== 'verified').length);

const documentCompletion = computed(() =>
    caseRecord.documents.length
        ? Math.round((verifiedDocumentCount.value / caseRecord.documents.length) * 100)
        : 0);

const unifiedThread = computed(() => {
    const messages = (caseRecord.messages || []).map((message) => ({
        id: `message-${message.id}`,
        actor: message.sender_name || 'System',
        kind: 'Message',
        body: message.body,
        timestamp: message.sent_at,
        icon: 'mail',
        _ts: new Date(message.sent_at).getTime(),
    }));

    const internalNotes = (caseRecord.internal_notes || []).map((note) => ({
        id: `note-${note.id}`,
        actor: note.author || 'System',
        kind: 'Internal note',
        body: note.body,
        timestamp: note.created_at,
        icon: 'document',
        _ts: new Date(note.created_at).getTime(),
    }));

    const clientNotes = (caseRecord.client_notes || []).map((note) => ({
        id: `client-note-${note.id}`,
        actor: note.author || 'System',
        kind: 'Client note',
        body: note.body,
        timestamp: note.created_at,
        icon: 'document',
        _ts: new Date(note.created_at).getTime(),
    }));

    const activities = (caseRecord.activities || []).map((activity) => ({
        id: `activity-${activity.id}`,
        actor: activity.causer || 'System',
        kind: 'Activity',
        body: activity.description,
        timestamp: activity.created_at,
        icon: 'clock',
        _ts: new Date(activity.created_at).getTime(),
    }));

    return [...messages, ...internalNotes, ...clientNotes, ...activities].sort((a, b) => b._ts - a._ts);
});

const submitStage = () => {
    stageForm.patch(route('cases.stage.update', caseRecord.id), {
        preserveScroll: true,
    });
};

const markTaskDone = (taskId) => {
    router.patch(
        route('cases.tasks.update', { case: caseRecord.id, task: taskId }),
        { status: 'completed' },
        { preserveScroll: true },
    );
};

const submitInternalNote = () => {
    internalNoteForm.post(route('cases.notes.store', caseRecord.id), {
        preserveScroll: true,
        onSuccess: () => internalNoteForm.reset(),
    });
};

const submitTask = () => {
    taskCreateForm.post(route('cases.tasks.store', caseRecord.id), {
        preserveScroll: true,
        onSuccess: () => {
            taskCreateForm.reset();
            showTaskSlideOver.value = false;
        },
    });
};

const submitCommunication = () => {
    communicationForm
        .transform((data) => ({
            ...data,
            sender_type: data.direction === 'inbound' ? 'applicant' : 'staff',
            send_notification: data.direction === 'outbound' && data.channel !== 'portal' ? data.send_notification : false,
        }))
        .post(route('cases.messages.store', caseRecord.id), {
            preserveScroll: true,
            onSuccess: () => {
                communicationForm.reset();
                communicationForm.direction = 'outbound';
                communicationForm.sender_type = 'staff';
                communicationForm.channel = 'email';
                communicationForm.notification_event = 'messages';
                communicationForm.send_notification = true;
                showMessageSlideOver.value = false;
            },
        });
};

const submitAppointment = () => {
    appointmentForm.post(route('cases.appointments.store', caseRecord.id), {
        preserveScroll: true,
        onSuccess: () => {
            appointmentForm.reset();
            appointmentForm.appointment_type = caseRecord.appointment_types?.[0] ?? 'consultation';
            appointmentForm.status = caseRecord.appointment_statuses?.[0] ?? 'scheduled';
            showAppointmentSlideOver.value = false;
        },
    });
};

const submitInvoice = () => {
    invoiceForm.post(route('cases.invoices.store', caseRecord.id), {
        preserveScroll: true,
        onSuccess: () => {
            invoiceForm.reset();
            invoiceForm.status = caseRecord.invoice_statuses?.[1] ?? 'sent';
            invoiceForm.currency = 'USD';
            invoiceForm.line_items = [{ label: 'Visa service fee', amount: '' }];
            showInvoiceSlideOver.value = false;
        },
    });
};

const updateDocumentStatus = (documentId) => {
    documentStatusForms[documentId].processing = true;

    router.patch(
        route('cases.documents.status.update', { case: caseRecord.id, document: documentId }),
        {
            status: documentStatusForms[documentId].status,
            expiry_date: documentStatusForms[documentId].expiry_date || null,
            rejection_reason: documentStatusForms[documentId].rejection_reason || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                documentStatusForms[documentId].processing = false;
            },
        },
    );
};

const uploadDocument = (documentId) => {
    if (!selectedFiles[documentId]) return;

    uploading[documentId] = true;

    router.post(
        route('cases.documents.upload', { case: caseRecord.id, document: documentId }),
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

const onFileChange = (documentId, event) => {
    selectedFiles[documentId] = event.target.files?.[0] ?? null;

    if (selectedFiles[documentId]) {
        uploadDocument(documentId);
    }
};

const onDropFile = (documentId, event) => {
    event.preventDefault();
    const file = event.dataTransfer.files?.[0] ?? null;

    if (file) {
        selectedFiles[documentId] = file;
        uploadDocument(documentId);
    }
};

const submitPayment = (invoiceId) => {
    paymentForms[invoiceId].processing = true;

    router.post(
        route('invoices.payments.store', invoiceId),
        paymentForms[invoiceId],
        {
            preserveScroll: true,
            onFinish: () => {
                paymentForms[invoiceId].processing = false;
                expandedPayments[invoiceId] = false;
            },
        },
    );
};

const sendAppointmentReminder = (appointmentId) => {
    router.post(route('cases.appointments.remind', { case: caseRecord.id, appointment: appointmentId }), {}, { preserveScroll: true });
};

const sendInvoiceReminder = (invoiceId) => {
    router.post(route('invoices.remind', invoiceId), {}, { preserveScroll: true });
};

const addInvoiceLineItem = () => {
    invoiceForm.line_items.push({ label: '', amount: '' });
};

const removeInvoiceLineItem = (index) => {
    invoiceForm.line_items.splice(index, 1);
};

const invoiceHasBalance = (invoice) => Number(invoice.balance_due) > 0;

const formatTimestamp = (value) => {
    if (!value) return 'Not set';

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;

    return parsed.toLocaleString();
};

const documentRequirementSummary = (document) => {
    const parts = [];
    parts.push(document.max_files > 1 ? `Up to ${document.max_files} files` : 'Single file');
    parts.push(`${document.max_file_size_mb}MB max`);

    if (document.accepted_file_types?.length) {
        parts.push(document.accepted_file_types.join(', ').toUpperCase());
    }

    return parts.join(' • ');
};

const formatMoney = (currency, amount) => `${currency} ${amount}`;
</script>

<template>
    <Head :title="`Case ${caseRecord.reference_code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Case</p>
                    <h1 class="text-[32px] font-bold tracking-tight text-slate-900 leading-tight">{{ caseRecord.reference_code }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                            {{ caseRecord.stage?.name || 'No stage' }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                            {{ caseRecord.priority.label }}
                        </span>
                        <span class="text-sm text-brand-muted">{{ caseRecord.country.name }} • {{ caseRecord.visa_type }}</span>
                        <span class="text-sm text-brand-muted">• {{ caseRecord.applicant.name }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Link v-if="caseRecord.applicant?.id" :href="route('applicants.show', caseRecord.applicant.id)" class="ui-button-secondary">
                        View Applicant
                    </Link>
                    <button type="button" class="ui-button-secondary" @click="showMessageSlideOver = true">Message</button>
                    <button type="button" class="ui-button-secondary" @click="showAppointmentSlideOver = true">Appointment</button>
                    <PrimaryButton @click="showTaskSlideOver = true">Add Task</PrimaryButton>
                </div>
            </div>
        </template>

        <div class="ui-detail-grid">
            <div class="space-y-6">
                <AppCard title="Summary" subtitle="A short snapshot of what this case is and what still matters right now.">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Applicant</p>
                            <p class="mt-2 font-medium text-brand-text">{{ caseRecord.applicant.name }}</p>
                            <p class="mt-1 text-sm text-brand-muted">{{ caseRecord.applicant.email || 'No email on file yet' }}</p>
                        </div>
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Next milestone</p>
                            <p class="mt-2 font-medium text-brand-text">{{ caseRecord.expected_submission_at || 'Not scheduled' }}</p>
                            <p class="mt-1 text-sm text-brand-muted">Decision target: {{ caseRecord.expected_decision_at || 'Not scheduled' }}</p>
                        </div>
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Attention needed</p>
                            <p class="mt-2 font-medium text-brand-text">{{ openTaskCount }} open tasks</p>
                            <p class="mt-1 text-sm text-brand-muted">{{ pendingDocumentCount }} pending documents</p>
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Activity" subtitle="The main working thread for this case: notes, messages, uploads, and system updates.">
                    <template #action>
                        <button type="button" class="ui-button-secondary" @click="showMessageSlideOver = true">Message client</button>
                    </template>

                    <form class="mb-5 space-y-3" @submit.prevent="submitInternalNote">
                        <div>
                            <InputLabel value="Add note" />
                            <textarea v-model="internalNoteForm.body" rows="4" class="ui-textarea" placeholder="Capture what changed, what was promised, or what still needs follow-up."></textarea>
                            <InputError :message="internalNoteForm.errors.body" />
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label class="flex items-center gap-2 text-sm text-brand-muted">
                                <input v-model="internalNoteForm.is_client_visible" type="checkbox" class="rounded text-brand-primary" />
                                Client visible
                            </label>
                            <PrimaryButton :loading="internalNoteForm.processing">Save note</PrimaryButton>
                        </div>
                    </form>

                    <div v-if="unifiedThread.length" class="space-y-4">
                        <div v-for="item in unifiedThread" :key="item.id" class="flex items-start gap-4 rounded-lg border border-brand-border px-4 py-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-neutral text-brand-muted">
                                <AppIcon :name="item.icon" :size="18" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-brand-text">{{ item.actor }}</p>
                                    <span class="text-sm text-brand-muted">• {{ item.kind }}</span>
                                    <span class="text-sm text-brand-muted">• {{ formatTimestamp(item.timestamp) }}</span>
                                </div>
                                <p class="mt-2 text-sm leading-relaxed text-brand-text">{{ item.body }}</p>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else icon="document" title="No activity yet" description="Notes, messages, and system updates will appear here." />
                </AppCard>

                <AppCard title="Tasks" subtitle="Keep the next actions lightweight and easy to complete.">
                    <template #action>
                        <button type="button" class="ui-button-secondary" @click="showTaskSlideOver = true">+ Add task</button>
                    </template>

                    <div v-if="caseRecord.tasks.length" class="space-y-3">
                        <div v-for="task in caseRecord.tasks" :key="task.id" class="flex items-start justify-between gap-4 rounded-lg border border-brand-border px-4 py-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium" :class="task.status === 'completed' ? 'text-brand-muted line-through' : 'text-brand-text'">{{ task.name }}</p>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ task.status.replaceAll('_', ' ') }}</span>
                                </div>
                                <p v-if="task.description" class="mt-1 text-sm text-brand-muted">{{ task.description }}</p>
                                <p class="mt-2 text-sm text-brand-muted">
                                    {{ task.assigned_to || 'Unassigned' }}
                                    <span v-if="task.due_at">• Due {{ task.due_at }}</span>
                                    <span v-if="task.stage_name">• {{ task.stage_name }}</span>
                                </p>
                            </div>
                            <PrimaryButton v-if="task.status !== 'completed'" class="shrink-0 !h-9 px-4" @click="markTaskDone(task.id)">Mark done</PrimaryButton>
                        </div>
                    </div>
                    <EmptyState v-else icon="task" title="No tasks yet" description="Add a task to capture the next action for this case." />
                </AppCard>

                <AppCard title="Documents" subtitle="Keep uploads and review simple: one row per requirement, one place to act.">
                    <template #action>
                        <Link :href="route('cases.documents.zip', caseRecord.id)" class="ui-button-secondary">Download All</Link>
                    </template>

                    <div class="mb-5 grid gap-4 md:grid-cols-3">
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Completion</p>
                            <p class="mt-2 text-2xl font-bold text-brand-text">{{ documentCompletion }}%</p>
                        </div>
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Pending</p>
                            <p class="mt-2 text-2xl font-bold text-brand-text">{{ pendingDocumentCount }}</p>
                        </div>
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Verified</p>
                            <p class="mt-2 text-2xl font-bold text-brand-text">{{ verifiedDocumentCount }}</p>
                        </div>
                    </div>

                    <div v-if="caseRecord.documents.length" class="space-y-4">
                        <div v-for="document in caseRecord.documents" :key="document.id" class="rounded-lg border border-brand-border p-4">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-medium text-brand-text">{{ document.name }}</p>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ document.status.label }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-brand-muted">{{ documentRequirementSummary(document) }}</p>
                                    <p v-if="document.latest_version" class="mt-2 text-sm text-brand-muted">
                                        Latest file: {{ document.latest_version.original_name }} (v{{ document.latest_version.version_number }})
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a v-if="document.latest_version" :href="document.latest_version.download_url" class="ui-button-ghost !h-8 px-3 text-[12px]">Download</a>
                                </div>
                            </div>

                            <input type="file" :id="`file-${document.id}`" class="sr-only" @change="onFileChange(document.id, $event)" />
                            <label :for="`file-${document.id}`" class="mt-4 block cursor-pointer rounded-lg border border-dashed border-brand-border bg-brand-neutral px-4 py-5 text-center" @dragover.prevent @drop="onDropFile(document.id, $event)">
                                <p class="font-medium text-brand-text">{{ document.latest_version ? 'Upload new version' : 'Upload document' }}</p>
                                <p class="mt-1 text-sm text-brand-muted">Click to browse or drag a file here.</p>
                                <p v-if="uploading[document.id]" class="mt-2 text-sm font-medium text-brand-primary">Uploading...</p>
                            </label>

                            <form class="mt-4 grid gap-4 md:grid-cols-3" @submit.prevent="updateDocumentStatus(document.id)">
                                <div>
                                    <InputLabel value="Status" />
                                    <select v-model="documentStatusForms[document.id].status" class="ui-select">
                                        <option v-for="status in caseRecord.document_statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Expiry date" />
                                    <input v-model="documentStatusForms[document.id].expiry_date" type="date" class="ui-input" />
                                </div>
                                <div>
                                    <InputLabel value="Rejection reason" />
                                    <input v-model="documentStatusForms[document.id].rejection_reason" class="ui-input" />
                                </div>
                                <div class="md:col-span-3">
                                    <PrimaryButton class="!h-10 px-4" :loading="documentStatusForms[document.id].processing">Save document status</PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                    <EmptyState v-else icon="document" title="No documents yet" description="Case documents will appear here once the checklist is available." />
                </AppCard>
            </div>

            <div class="space-y-6">
                <AppCard title="Case details" subtitle="Editable metadata that supports the main workflow.">
                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Current stage" />
                            <select v-model="stageForm.stage_id" class="ui-select" @change="submitStage">
                                <option v-for="stage in caseRecord.workflow" :key="stage.id" :value="stage.id">{{ stage.name }}</option>
                            </select>
                        </div>
                        <div class="ui-meta-list">
                            <p>Assigned to: {{ caseRecord.assigned_to || 'Unassigned' }}</p>
                            <p>Branch: {{ caseRecord.branch || 'Corporate' }}</p>
                            <p>Submission target: {{ caseRecord.expected_submission_at || 'Not scheduled' }}</p>
                            <p>Decision target: {{ caseRecord.expected_decision_at || 'Not scheduled' }}</p>
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Progress" subtitle="A small sidebar snapshot, not a dashboard.">
                    <div class="space-y-4">
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Open tasks</p>
                            <p class="mt-2 text-lg font-medium text-brand-text">{{ openTaskCount }}</p>
                        </div>
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Document progress</p>
                            <p class="mt-2 text-lg font-medium text-brand-text">{{ documentCompletion }}%</p>
                        </div>
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="ui-kicker">Outstanding balance</p>
                            <p class="mt-2 text-lg font-medium text-brand-text">{{ formatMoney('USD', totalBalanceDue) }}</p>
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Billing" subtitle="Only the financial details that need attention.">
                    <template #action>
                        <button type="button" class="ui-button-secondary" @click="showInvoiceSlideOver = true">Create invoice</button>
                    </template>
                    <div v-if="caseRecord.invoices.length" class="space-y-4">
                        <div v-for="invoice in caseRecord.invoices" :key="invoice.id" class="rounded-lg border border-brand-border px-4 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-brand-text">Invoice #{{ invoice.number || invoice.id }}</p>
                                    <p class="mt-1 text-sm text-brand-muted">Issued {{ invoice.issued_at || 'Not set' }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ invoice.status.replaceAll('_', ' ') }}</span>
                            </div>
                            <p class="mt-2 text-sm text-brand-muted">Balance {{ formatMoney(invoice.currency, invoice.balance_due) }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="sendInvoiceReminder(invoice.id)">Send reminder</button>
                                <button v-if="invoiceHasBalance(invoice)" type="button" class="ui-button-secondary !h-8 px-3 text-[12px]" @click="expandedPayments[invoice.id] = !expandedPayments[invoice.id]">
                                    {{ expandedPayments[invoice.id] ? 'Cancel' : 'Record payment' }}
                                </button>
                            </div>
                            <form v-if="expandedPayments[invoice.id]" class="mt-4 grid gap-4" @submit.prevent="submitPayment(invoice.id)">
                                <div>
                                    <InputLabel value="Amount" />
                                    <input v-model="paymentForms[invoice.id].amount" class="ui-input" />
                                </div>
                                <div>
                                    <InputLabel value="Method" />
                                    <select v-model="paymentForms[invoice.id].method" class="ui-select">
                                        <option v-for="method in caseRecord.payment_methods" :key="method" :value="method">{{ method }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Reference" />
                                    <input v-model="paymentForms[invoice.id].reference" class="ui-input" />
                                </div>
                                <div>
                                    <InputLabel value="Paid at" />
                                    <input v-model="paymentForms[invoice.id].paid_at" type="datetime-local" class="ui-input" />
                                </div>
                                <PrimaryButton class="!h-10 px-4" :loading="paymentForms[invoice.id].processing">Save payment</PrimaryButton>
                            </form>
                        </div>
                    </div>
                    <EmptyState v-else icon="inbox" title="No invoices yet" description="Create invoices here when the case reaches a billing step." />
                </AppCard>

                <AppCard title="Appointments" subtitle="Keep scheduled events visible but secondary.">
                    <template #action>
                        <button type="button" class="ui-button-secondary" @click="showAppointmentSlideOver = true">Schedule</button>
                    </template>
                    <div v-if="caseRecord.appointments.length" class="space-y-3">
                        <div v-for="appointment in caseRecord.appointments" :key="appointment.id" class="rounded-lg border border-brand-border px-4 py-4">
                            <p class="font-medium text-brand-text">{{ appointment.title }}</p>
                            <p class="mt-1 text-sm text-brand-muted">{{ formatTimestamp(appointment.starts_at) }}</p>
                            <p class="mt-1 text-sm text-brand-muted">{{ appointment.location || appointment.meeting_link || 'Location not added yet' }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ appointment.status }}</span>
                                <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="sendAppointmentReminder(appointment.id)">Send reminder</button>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else icon="clock" title="No appointments yet" description="Schedule interviews, biometrics, or consultations from here." />
                </AppCard>

                <AppCard title="Applicant contact" subtitle="Fast access to the person connected to this case.">
                    <div class="ui-meta-list">
                        <p>{{ caseRecord.applicant.name }}</p>
                        <p>{{ caseRecord.applicant.email || 'No email on file yet' }}</p>
                        <p>{{ caseRecord.applicant.phone || 'No phone number on file yet' }}</p>
                    </div>
                    <template #action>
                        <Link v-if="caseRecord.applicant?.id" :href="route('applicants.show', caseRecord.applicant.id)" class="text-sm font-medium text-brand-primary hover:underline">
                            Open applicant →
                        </Link>
                    </template>
                </AppCard>
            </div>
        </div>

        <SlideOver :show="showTaskSlideOver" title="Create task" description="Add the next action your team needs to complete for this case." @close="showTaskSlideOver = false">
            <form id="case-task-form" class="space-y-6" @submit.prevent="submitTask">
                <div>
                    <InputLabel value="Task title" />
                    <input v-model="taskCreateForm.title" class="ui-input" />
                    <InputError :message="taskCreateForm.errors.title" />
                </div>
                <div>
                    <InputLabel value="Description" />
                    <textarea v-model="taskCreateForm.description" class="ui-textarea" rows="4"></textarea>
                    <InputError :message="taskCreateForm.errors.description" />
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Due date" />
                        <input v-model="taskCreateForm.due_at" type="date" class="ui-input" />
                        <InputError :message="taskCreateForm.errors.due_at" />
                    </div>
                    <div>
                        <InputLabel value="Assigned to" />
                        <select v-model="taskCreateForm.assigned_to_user_id" class="ui-select">
                            <option value="">Unassigned</option>
                            <option v-for="user in caseRecord.staff_options" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                        <InputError :message="taskCreateForm.errors.assigned_to_user_id" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Stage" />
                    <select v-model="taskCreateForm.stage_id" class="ui-select">
                        <option value="">Current stage</option>
                        <option v-for="stage in caseRecord.workflow" :key="stage.id" :value="stage.id">{{ stage.name }}</option>
                    </select>
                    <InputError :message="taskCreateForm.errors.stage_id" />
                </div>
            </form>
            <template #footer>
                <PrimaryButton form="case-task-form" type="submit" class="w-full" :loading="taskCreateForm.processing">Create task</PrimaryButton>
            </template>
        </SlideOver>

        <SlideOver :show="showMessageSlideOver" title="Message client" description="Send or log communication on the case thread." @close="showMessageSlideOver = false">
            <form id="case-message-form" class="space-y-6" @submit.prevent="submitCommunication">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Direction" />
                        <select v-model="communicationForm.direction" class="ui-select">
                            <option value="outbound">Outbound</option>
                            <option value="inbound">Inbound</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Channel" />
                        <select v-model="communicationForm.channel" class="ui-select">
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="portal">Portal</option>
                        </select>
                    </div>
                </div>
                <div>
                    <InputLabel value="Subject" />
                    <input v-model="communicationForm.subject" class="ui-input" :placeholder="`Case Update: ${caseRecord.reference_code}`" />
                    <InputError :message="communicationForm.errors.subject" />
                </div>
                <div>
                    <InputLabel value="Message" />
                    <textarea v-model="communicationForm.body" class="ui-textarea" rows="8"></textarea>
                    <InputError :message="communicationForm.errors.body" />
                </div>
                <label class="flex items-center gap-2 text-sm text-brand-muted">
                    <input v-model="communicationForm.send_notification" type="checkbox" class="rounded text-brand-primary" />
                    Send notification to the applicant
                </label>
            </form>
            <template #footer>
                <PrimaryButton form="case-message-form" type="submit" class="w-full" :loading="communicationForm.processing">Save message</PrimaryButton>
            </template>
        </SlideOver>

        <SlideOver :show="showAppointmentSlideOver" title="Schedule appointment" description="Add an appointment tied to this case." @close="showAppointmentSlideOver = false">
            <form id="case-appointment-form" class="space-y-6" @submit.prevent="submitAppointment">
                <div>
                    <InputLabel value="Title" />
                    <input v-model="appointmentForm.title" class="ui-input" />
                    <InputError :message="appointmentForm.errors.title" />
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Type" />
                        <select v-model="appointmentForm.appointment_type" class="ui-select">
                            <option v-for="type in caseRecord.appointment_types" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Status" />
                        <select v-model="appointmentForm.status" class="ui-select">
                            <option v-for="status in caseRecord.appointment_statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Starts at" />
                        <input v-model="appointmentForm.starts_at" type="datetime-local" class="ui-input" />
                        <InputError :message="appointmentForm.errors.starts_at" />
                    </div>
                    <div>
                        <InputLabel value="Ends at" />
                        <input v-model="appointmentForm.ends_at" type="datetime-local" class="ui-input" />
                        <InputError :message="appointmentForm.errors.ends_at" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Location" />
                    <input v-model="appointmentForm.location" class="ui-input" />
                    <InputError :message="appointmentForm.errors.location" />
                </div>
                <div>
                    <InputLabel value="Meeting link" />
                    <input v-model="appointmentForm.meeting_link" class="ui-input" />
                    <InputError :message="appointmentForm.errors.meeting_link" />
                </div>
                <div>
                    <InputLabel value="Assigned to" />
                    <select v-model="appointmentForm.assigned_to_user_id" class="ui-select">
                        <option value="">Unassigned</option>
                        <option v-for="user in caseRecord.staff_options" :key="user.id" :value="user.id">{{ user.name }}</option>
                    </select>
                    <InputError :message="appointmentForm.errors.assigned_to_user_id" />
                </div>
            </form>
            <template #footer>
                <PrimaryButton form="case-appointment-form" type="submit" class="w-full" :loading="appointmentForm.processing">Save appointment</PrimaryButton>
            </template>
        </SlideOver>

        <SlideOver :show="showInvoiceSlideOver" title="Create invoice" description="Generate a simple invoice for this case." @close="showInvoiceSlideOver = false">
            <form id="case-invoice-form" class="space-y-6" @submit.prevent="submitInvoice">
                <div class="space-y-4">
                    <div v-for="(item, index) in invoiceForm.line_items" :key="index" class="grid gap-3 md:grid-cols-[1fr_140px_auto]">
                        <div>
                            <InputLabel value="Description" />
                            <input v-model="item.label" class="ui-input" />
                        </div>
                        <div>
                            <InputLabel value="Amount" />
                            <input v-model="item.amount" type="number" min="0" step="0.01" class="ui-input" />
                        </div>
                        <div class="flex items-end">
                            <button type="button" class="ui-button-ghost !h-10 px-3 text-[12px]" @click="removeInvoiceLineItem(index)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="text-sm font-medium text-brand-primary hover:underline" @click="addInvoiceLineItem">+ Add line item</button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Status" />
                        <select v-model="invoiceForm.status" class="ui-select">
                            <option v-for="status in caseRecord.invoice_statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Currency" />
                        <input v-model="invoiceForm.currency" maxlength="3" class="ui-input" />
                        <InputError :message="invoiceForm.errors.currency" />
                    </div>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Issued at" />
                        <input v-model="invoiceForm.issued_at" type="date" class="ui-input" />
                    </div>
                    <div>
                        <InputLabel value="Due date" />
                        <input v-model="invoiceForm.due_at" type="date" class="ui-input" />
                        <InputError :message="invoiceForm.errors.due_at" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Client message" />
                    <textarea v-model="invoiceForm.client_message" rows="3" class="ui-textarea"></textarea>
                </div>
            </form>
            <template #footer>
                <PrimaryButton form="case-invoice-form" type="submit" class="w-full" :loading="invoiceForm.processing">Create invoice</PrimaryButton>
            </template>
        </SlideOver>
    </AuthenticatedLayout>
</template>
