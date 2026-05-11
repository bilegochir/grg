<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

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

const applyFilters = () => {
    filtersForm.get(route('documents.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filtersForm.reset();
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
                    <h1 class="ui-header-title text-[28px] tracking-tight">Compliance & Verification</h1>
                    <p class="ui-header-copy text-[14px] leading-relaxed">
                        Track applicant uploads, verify authenticity, and manage document lifecycles for active cases.
                    </p>
                </div>
            </div>
        </template>

        <div class="ui-page-body space-y-6">
            <!-- Filters & List -->
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/30 p-4">
                    <form class="flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
                        <input
                            v-model="filtersForm.search"
                            type="text"
                            class="ui-input !h-9 text-[13px] min-w-[200px] flex-1"
                            placeholder="Search documents, applicants..."
                        />

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

                <div v-if="documents.data.length" class="divide-y divide-slate-100">
                    <div
                        v-for="document in documents.data"
                        :key="document.id"
                        class="p-4 hover:bg-slate-50/30 transition-colors group"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[15px] font-bold text-slate-900 leading-tight group-hover:text-black transition-colors">{{ document.name }}</p>
                                    <span 
                                        class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-slate-100 text-slate-600': document.status.value === 'pending',
                                            'bg-blue-50 text-blue-700': document.status.value === 'uploaded',
                                            'bg-emerald-50 text-emerald-700': document.status.value === 'verified',
                                            'bg-rose-50 text-rose-700': document.status.value === 'rejected',
                                        }"
                                    >
                                        {{ document.status.label }}
                                    </span>
                                    <span v-if="document.category" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                        {{ document.category }}
                                    </span>
                                    <span v-if="document.expiry_state === 'expired'" class="rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-600 uppercase tracking-wider">
                                        Expired
                                    </span>
                                </div>

                                <p v-if="document.description" class="mt-1.5 text-[13px] text-slate-500 leading-relaxed max-w-2xl">
                                    {{ document.description }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1.5 items-center text-[12px] text-slate-400">
                                    <span class="font-bold text-slate-600">{{ document.case.applicant_name }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                    <span class="font-medium text-slate-500">{{ document.case.reference_code }}</span>
                                    <span v-if="document.case.country_name" class="flex items-center gap-1.5">
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        {{ document.case.country_name }}
                                    </span>
                                    <span v-if="document.case.current_stage" class="flex items-center gap-1.5">
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        {{ document.case.current_stage }}
                                    </span>
                                    <span v-if="document.expiry_date" class="flex items-center gap-1.5" :class="document.expiry_state === 'expired' ? 'text-rose-500 font-bold' : ''">
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        Expires {{ document.expiry_date }}
                                    </span>
                                </div>

                                <div v-if="document.latest_version || document.rejection_reason" class="mt-4 space-y-2">
                                    <div v-if="document.latest_version" class="inline-flex items-center gap-2 rounded-lg bg-slate-50 border border-slate-100 px-3 py-2 text-[12px]">
                                        <AppIcon name="document" :size="14" class="text-slate-400" />
                                        <span class="font-bold text-slate-900">{{ document.latest_version.original_name }}</span>
                                        <span class="text-slate-400">v{{ document.latest_version.version_number }} · {{ humanFileSize(document.latest_version.size) }}</span>
                                    </div>
                                    <p v-if="document.rejection_reason" class="text-[12px] font-medium text-rose-600 flex items-center gap-1.5">
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
                                    class="ui-button-ghost !h-8 px-3 text-[12px] opacity-0 group-hover:opacity-100 transition-opacity"
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
                                    class="ui-button-secondary !h-8 px-3 text-[12px] bg-slate-900 text-white border-transparent hover:bg-black transition-colors"
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

                        <!-- Row Quick Actions -->
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-50 pt-4">
                            <select v-model="documentForms[document.id].status" class="ui-select !h-8 text-[12px] w-auto bg-slate-50/50 border-transparent">
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>

                            <input
                                v-model="documentForms[document.id].expiry_date"
                                type="date"
                                class="ui-input !h-8 text-[12px] w-auto bg-slate-50/50 border-transparent"
                                :disabled="!document.tracks_expiry"
                            />

                            <input
                                v-model="documentForms[document.id].rejection_reason"
                                type="text"
                                class="ui-input !h-8 text-[12px] flex-1 min-w-[200px] bg-slate-50/50 border-transparent"
                                placeholder="Reason if rejected"
                                :disabled="documentForms[document.id].status !== 'rejected'"
                            />

                            <button
                                type="button"
                                class="text-[12px] font-bold text-slate-400 hover:text-slate-900 transition-colors px-2"
                                :disabled="documentForms[document.id].processing"
                                @click="saveDocument(document)"
                            >
                                {{ documentForms[document.id].processing ? 'Saving...' : 'Update' }}
                            </button>
                        </div>
                    </div>
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
                    <div class="flex flex-wrap gap-1.5">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="link in documents.links"
                            :key="link.label"
                            :href="link.url"
                            class="rounded px-2.5 py-1.5 border transition-all duration-200"
                            :class="link.active ? 'border-slate-900 bg-slate-900 text-white font-bold' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
