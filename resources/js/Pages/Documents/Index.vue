<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    documents: Object,
    filters: Object,
    summary: Object,
    statuses: Array,
    countries: Array,
    agents: Array,
});

const filtersForm = useForm({
    search: props.filters.search ?? '',
    bucket: props.filters.bucket ?? 'all',
    status: props.filters.status ?? '',
    country: props.filters.country ?? '',
    assigned_to: props.filters.assigned_to ?? '',
});

const documentForms = reactive(
    Object.fromEntries(
        props.documents.data.map((document) => [
            document.id,
            {
                status: document.status.value,
                expiry_date: document.expiry_date ?? '',
                rejection_reason: document.rejection_reason ?? '',
                processing: false,
                uploading: false,
            },
        ]),
    ),
);

const fileInputs = ref({});

const humanFileSize = (size) => {
    if (!size) return null;

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = size;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }

    return `${value >= 10 || unitIndex === 0 ? Math.round(value) : value.toFixed(1)} ${units[unitIndex]}`;
};

const resultsLabel = computed(() => {
    if (!props.documents.total) {
        return '0 documents';
    }

    return `${props.documents.from}-${props.documents.to} of ${props.documents.total} documents`;
});

const statusBadgeClass = (status) => ({
    'ui-status-badge-new': status === 'pending',
    'ui-status-badge-contacted': status === 'uploaded',
    'ui-status-badge-approved': status === 'verified',
    'ui-status-badge-rejected': status === 'rejected',
});

const documentSections = computed(() => {
    const review = props.documents.data.filter((document) => ['pending', 'uploaded', 'rejected'].includes(document.status.value));
    const expiring = props.documents.data.filter((document) => document.expiry_state === 'expiring_soon' || document.expiry_state === 'expired');
    const verified = props.documents.data.filter((document) => document.status.value === 'verified' && document.expiry_state !== 'expiring_soon' && document.expiry_state !== 'expired');

    return [
        {
            key: 'review',
            title: 'Needs review',
            description: 'Uploads and document records that still need a verification decision.',
            documents: review,
            tone: 'border-amber-200 bg-amber-50/30',
        },
        {
            key: 'expiring',
            title: 'Expiry watch',
            description: 'Documents that are expired or coming up soon and may block submission or decision readiness.',
            documents: expiring,
            tone: 'border-rose-200 bg-rose-50/30',
        },
        {
            key: 'verified',
            title: 'Verified',
            description: 'Documents that are already accepted and currently healthy.',
            documents: verified,
            tone: 'border-slate-200 bg-slate-50/30',
        },
    ].filter((section) => section.documents.length);
});

const filterSummaryCards = computed(() => ([
    {
        key: 'all',
        label: 'All documents',
        value: props.summary.total,
        active: filtersForm.bucket === 'all',
        onClick: () => {
            filtersForm.bucket = 'all';
            filtersForm.status = '';
            applyFilters();
        },
    },
    {
        key: 'review',
        label: 'Needs review',
        value: props.summary.pending + props.summary.uploaded,
        active: filtersForm.bucket === 'review',
        onClick: () => {
            filtersForm.bucket = 'review';
            filtersForm.status = '';
            applyFilters();
        },
    },
    {
        key: 'expiring',
        label: 'Expiring soon',
        value: props.summary.expired + props.summary.expiring_soon,
        active: filtersForm.bucket === 'expiring',
        onClick: () => {
            filtersForm.bucket = 'expiring';
            filtersForm.status = '';
            applyFilters();
        },
    },
    {
        key: 'verified',
        label: 'Verified',
        value: props.summary.verified,
        active: filtersForm.bucket === 'verified',
        onClick: () => {
            filtersForm.bucket = 'verified';
            filtersForm.status = 'verified';
            applyFilters();
        },
    },
]));

const applyFilters = () => {
    filtersForm.get(route('documents.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filtersForm.search = '';
    filtersForm.bucket = 'all';
    filtersForm.status = '';
    filtersForm.country = '';
    filtersForm.assigned_to = '';
    applyFilters();
};

const saveDocument = (document) => {
    const form = documentForms[document.id];
    form.processing = true;

    router.patch(route('cases.documents.status.update', { case: document.case.id, document: document.id }), {
        status: form.status,
        expiry_date: form.expiry_date || null,
        rejection_reason: form.status === 'rejected' ? form.rejection_reason : null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            form.processing = false;
        },
    });
};

const markVerified = (document) => {
    documentForms[document.id].status = 'verified';
    saveDocument(document);
};

const triggerUpload = (documentId) => {
    fileInputs.value[documentId]?.click();
};

const uploadDocument = (document, event) => {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    documentForms[document.id].uploading = true;

    router.post(route('cases.documents.upload', { case: document.case.id, document: document.id }), {
        file,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            documentForms[document.id].uploading = false;
            event.target.value = '';
        },
    });
};
</script>

<template>
    <Head title="Documents" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Documents</p>
                    <h1 class="ui-header-title">Compliance & Verification</h1>
                    <p class="ui-header-copy text-[14px] leading-relaxed">
                        Track applicant uploads, verify authenticity, and manage document lifecycles for active cases.
                    </p>
                </div>
            </div>
        </template>

        <div class="ui-page-body space-y-6">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <button
                    v-for="card in filterSummaryCards"
                    :key="card.key"
                    type="button"
                    class="rounded-xl border px-4 py-4 text-left transition-colors"
                    :class="card.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white hover:bg-slate-50'"
                    @click="card.onClick"
                >
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em]" :class="card.active ? 'text-white/70' : 'text-slate-400'">
                        {{ card.label }}
                    </p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight" :class="card.active ? 'text-white' : 'text-slate-900'">
                        {{ card.value }}
                    </p>
                </button>
            </div>

            <div v-if="summary.expired" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-rose-800">Expired documents need attention</p>
                        <p class="mt-1 text-sm text-rose-700">
                            {{ summary.expired }} document{{ summary.expired === 1 ? '' : 's' }} already expired and may be blocking active cases.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="ui-button-secondary !h-9 px-4 text-[12px] !border-rose-200 !bg-white !text-rose-700 hover:!bg-rose-100"
                        @click="filtersForm.bucket = 'expiring'; filtersForm.status = ''; applyFilters();"
                    >
                        Review expired items
                    </button>
                </div>
            </div>

            <!-- Filters & List -->
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/30 p-4">
                    <form class="flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
                        <div class="relative min-w-[200px] flex-1">
                            <AppIcon name="search" :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="filtersForm.search"
                                type="text"
                                class="ui-input !h-9 !pl-9 text-[13px] w-full"
                                placeholder="Search documents, applicants..."
                            />
                        </div>

                        <select v-model="filtersForm.status" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Statuses</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>

                        <select v-model="filtersForm.country" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Countries</option>
                            <option v-for="country in countries" :key="country.id" :value="country.slug">
                                {{ country.name }}
                            </option>
                        </select>

                        <select v-model="filtersForm.assigned_to" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Assignees</option>
                            <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                {{ agent.name }}
                            </option>
                        </select>

                        <div class="flex items-center gap-2">
                            <PrimaryButton type="submit" class="!h-9 px-4 text-[12px]">Apply</PrimaryButton>
                            <button type="button" class="ui-button-ghost !h-9 px-4 text-[12px]" @click="resetFilters">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div v-if="documents.data.length" class="space-y-6 p-4">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50/50 px-4 py-3 text-[12px] text-slate-500">
                        <p class="font-medium uppercase tracking-wider">{{ resultsLabel }}</p>
                        <p>Review uploads, verify evidence, and update expiry metadata from one queue.</p>
                    </div>

                    <section
                        v-for="section in documentSections"
                        :key="section.key"
                        class="rounded-xl border"
                        :class="section.tone"
                    >
                        <div class="border-b border-slate-200/70 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">{{ section.title }}</p>
                            <p class="mt-1 text-[12px] text-slate-500">{{ section.description }}</p>
                        </div>

                        <div class="divide-y divide-slate-200/70">
                            <div
                                v-for="document in section.documents"
                                :key="document.id"
                                class="p-4 transition-colors hover:bg-white/60"
                            >
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-[15px] font-semibold leading-tight text-slate-900">{{ document.name }}</p>
                                            <span class="ui-status-badge" :class="statusBadgeClass(document.status.value)">
                                                <span class="ui-status-badge-dot"></span>
                                                {{ document.status.label }}
                                            </span>
                                            <span v-if="document.category" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-500">
                                                {{ document.category }}
                                            </span>
                                            <span v-if="document.expiry_state === 'expired'" class="ui-status-badge ui-status-badge-rejected">
                                                <span class="ui-status-badge-dot"></span>
                                                Expired
                                            </span>
                                            <span v-else-if="document.expiry_state === 'expiring_soon'" class="ui-status-badge ui-status-badge-qualified">
                                                <span class="ui-status-badge-dot"></span>
                                                Expiring soon
                                            </span>
                                        </div>

                                        <p v-if="document.description" class="mt-2 max-w-2xl text-[13px] leading-relaxed text-slate-500">
                                            {{ document.description }}
                                        </p>

                                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[12px] text-slate-500">
                                            <span class="font-medium text-slate-700">{{ document.case.applicant_name }}</span>
                                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                            <span>{{ document.case.reference_code }}</span>
                                            <span v-if="document.case.country_name" class="flex items-center gap-1.5">
                                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                                {{ document.case.country_name }}
                                            </span>
                                            <span v-if="document.case.current_stage" class="flex items-center gap-1.5">
                                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                                {{ document.case.current_stage }}
                                            </span>
                                            <span v-if="document.case.assigned_to" class="flex items-center gap-1.5">
                                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                                {{ document.case.assigned_to }}
                                            </span>
                                            <span v-if="document.expiry_date" class="flex items-center gap-1.5" :class="document.expiry_state === 'expired' ? 'font-semibold text-rose-600' : document.expiry_state === 'expiring_soon' ? 'font-semibold text-amber-700' : ''">
                                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                                Expires {{ document.expiry_date }}
                                            </span>
                                        </div>

                                        <div v-if="document.expiry_state === 'expired'" class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[12px] font-medium text-rose-700">
                                            This document is expired. Renew or replace it before relying on it for case progress.
                                        </div>
                                        <div v-else-if="document.expiry_state === 'expiring_soon'" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[12px] font-medium text-amber-700">
                                            This document expires soon. Review whether a refreshed version is needed for the case.
                                        </div>

                                        <div v-if="document.latest_version || document.rejection_reason" class="mt-4 space-y-2">
                                            <div v-if="document.latest_version" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[12px]">
                                                <AppIcon name="document" :size="14" class="text-slate-400" />
                                                <span class="font-semibold text-slate-900">{{ document.latest_version.original_name }}</span>
                                                <span class="text-slate-400">v{{ document.latest_version.version_number }} · {{ humanFileSize(document.latest_version.size) }}</span>
                                            </div>
                                            <p v-if="document.rejection_reason" class="flex items-center gap-1.5 text-[12px] font-medium text-rose-600">
                                                <AppIcon name="alert" :size="12" />
                                                Rejected: {{ document.rejection_reason }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        <input
                                            :ref="(el) => { if (el) fileInputs[document.id] = el; }"
                                            type="file"
                                            class="hidden"
                                            @change="uploadDocument(document, $event)"
                                        />
                                        <button
                                            type="button"
                                            class="ui-button-ghost !h-8 px-3 text-[12px]"
                                            :disabled="documentForms[document.id].uploading"
                                            @click="triggerUpload(document.id)"
                                        >
                                            {{ documentForms[document.id].uploading ? 'Uploading...' : (document.latest_version ? 'Replace' : 'Upload') }}
                                        </button>
                                        <a
                                            v-if="document.latest_version"
                                            :href="document.latest_version.download_url"
                                            class="ui-button-secondary !h-8 px-3 text-[12px]"
                                        >
                                            Download
                                        </a>
                                        <button
                                            v-if="document.status.value !== 'verified' && document.latest_version"
                                            type="button"
                                            class="ui-button-secondary !h-8 px-3 text-[12px]"
                                            :disabled="documentForms[document.id].processing"
                                            @click="markVerified(document)"
                                        >
                                            Verify
                                        </button>
                                        <Link :href="route('cases.show', document.case.id)" class="ui-button-secondary !h-8 px-3 text-[12px]">
                                            Open case
                                        </Link>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 border-t border-slate-200/70 pt-4 md:grid-cols-[minmax(0,170px)_minmax(0,180px)_minmax(0,1fr)_auto]">
                                    <select v-model="documentForms[document.id].status" class="ui-select !h-8 text-[12px]">
                                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                                            {{ status.label }}
                                        </option>
                                    </select>

                                    <input
                                        v-model="documentForms[document.id].expiry_date"
                                        type="date"
                                        class="ui-input !h-8 text-[12px]"
                                        :disabled="!document.tracks_expiry"
                                    />

                                    <input
                                        v-model="documentForms[document.id].rejection_reason"
                                        type="text"
                                        class="ui-input !h-8 text-[12px]"
                                        placeholder="Reason if rejected"
                                        :disabled="documentForms[document.id].status !== 'rejected'"
                                    />

                                    <div class="flex items-center justify-start md:justify-end">
                                        <button
                                            type="button"
                                            class="ui-button-secondary !h-8 px-3 text-[12px]"
                                            :disabled="documentForms[document.id].processing"
                                            @click="saveDocument(document)"
                                        >
                                            {{ documentForms[document.id].processing ? 'Saving...' : 'Save changes' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <EmptyState
                    v-else
                    icon="note"
                    title="No Documents"
                    description="No document checklist items found matching your current filters."
                />

                <div v-if="documents.links?.length > 3" class="p-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-[12px] text-slate-500">
                    <p>
                        Showing {{ documents.from ?? 0 }}-{{ documents.to ?? 0 }} of {{ documents.total ?? 0 }} documents
                    </p>
                    <PaginationLinks :links="documents.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
