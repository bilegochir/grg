<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface RequirementAttachment {
    id: number;
    original_name: string;
    mime_type: null | string;
    size: string;
    uploaded_by: null | string;
    created_at: null | string;
    download_url: string;
}

interface RequirementItem {
    id: number;
    category: null | string;
    label: string;
    help_text: null | string;
    is_required: boolean;
    status: string;
    status_label: string;
    due_at: null | string;
    requested_at: null | string;
    received_at: null | string;
    reviewed_at: null | string;
    review_notes: null | string;
    is_completed: boolean;
    completed_at: null | string;
    attachments: RequirementAttachment[];
}

interface RequirementTemplate {
    label: string;
    region: string;
    country_name: string;
    visa_type: string;
    description: null | string;
    source_url: null | string;
    source_checked_at: null | string;
    processing_time_summary: null | string;
    fee_summary: null | string;
    stay_summary: null | string;
}

interface Option {
    value: string;
    label: string;
}

interface DetailItem {
    label: string;
    value: string;
}

const props = defineProps<{
    visaCaseId: number;
    template: null | RequirementTemplate;
    items: RequirementItem[];
    statusOptions: Option[];
}>();

const updatingRequirementId = ref<number | null>(null);
const uploadingRequirementId = ref<number | null>(null);

const inputClass =
    'flex h-9 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15 disabled:cursor-not-allowed disabled:opacity-60';

const completedCount = computed((): number => props.items.filter((item) => item.is_completed).length);

const completionPercent = computed((): number => {
    if (props.items.length === 0) return 0;

    return Math.round((completedCount.value / props.items.length) * 100);
});

const templateHighlights = computed((): DetailItem[] => {
    if (!props.template) return [];

    return [
        { label: 'Region', value: props.template.region },
        { label: 'Processing', value: props.template.processing_time_summary ?? '' },
        { label: 'Stay', value: props.template.stay_summary ?? '' },
        { label: 'Fee', value: props.template.fee_summary ?? '' },
    ].filter((detail) => detail.value !== '');
});

const formatDate = (value: null | string): string => {
    if (!value) return 'Not set';

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
    }).format(new Date(`${value.slice(0, 10)}T00:00:00`));
};

const itemDatesMap = computed<Record<number, DetailItem[]>>(() => {
    return Object.fromEntries(
        props.items.map((item) => [
            item.id,
            [
                { label: 'Requested', value: item.requested_at ? formatDate(item.requested_at) : '' },
                { label: 'Received', value: item.received_at ? formatDate(item.received_at) : '' },
                { label: 'Reviewed', value: item.reviewed_at ? formatDate(item.reviewed_at) : '' },
                { label: 'Due', value: item.due_at ? formatDate(item.due_at) : '' },
            ].filter((detail) => detail.value !== ''),
        ]),
    );
});

const hasExtraDetails = (item: RequirementItem): boolean =>
    item.help_text !== null || item.review_notes !== null || item.attachments.length > 0;

const patchRequirement = (requirementId: number, payload: Record<string, unknown>): void => {
    updatingRequirementId.value = requirementId;

    router.patch(route('visa-cases.requirements.update', [props.visaCaseId, requirementId]), payload, {
        preserveScroll: true,
        onFinish: () => {
            updatingRequirementId.value = null;
        },
    });
};

const toggleRequirement = (requirementId: number, isCompleted: boolean): void => {
    patchRequirement(requirementId, { is_completed: isCompleted });
};

const updateStatus = (requirementId: number, event: Event): void => {
    const value = (event.target as HTMLSelectElement).value;

    patchRequirement(requirementId, { status: value });
};

const updateDueDate = (requirementId: number, event: Event): void => {
    const value = (event.target as HTMLInputElement).value;

    patchRequirement(requirementId, {
        due_at: value === '' ? null : value,
    });
};

const uploadAttachment = (requirementId: number, event: Event): void => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    uploadingRequirementId.value = requirementId;

    router.post(
        route('visa-cases.requirements.attachments.store', [props.visaCaseId, requirementId]),
        { attachment: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                input.value = '';
                uploadingRequirementId.value = null;
            },
        },
    );
};

const statusClasses = (status: string): string =>
    ({
        pending: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900/70 dark:text-neutral-200',
        requested: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-200',
        received: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        verified: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        waived: 'bg-violet-100 text-violet-800 dark:bg-violet-950/70 dark:text-violet-200',
    })[status] ?? 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900/70 dark:text-neutral-200';
</script>

<template>
    <section class="app-panel overflow-hidden">
        <div class="border-b border-border/70 px-4 py-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">
                        Requirements
                    </h2>

                    <p v-if="!template" class="mt-1 text-sm text-muted-foreground">
                        No requirement template matched this destination and visa type yet.
                    </p>

                    <p v-else-if="template.description" class="mt-1 max-w-3xl text-sm text-muted-foreground">
                        {{ template.description }}
                    </p>
                </div>

                <div v-if="items.length > 0" class="w-full lg:w-auto lg:min-w-[260px]">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-foreground">
                            {{ completedCount }}/{{ items.length }} complete
                        </p>

                        <span class="text-sm font-medium text-muted-foreground">
                            {{ completionPercent }}%
                        </span>
                    </div>

                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-slate-900 transition-all dark:bg-slate-100"
                            :style="{ width: `${completionPercent}%` }"
                        />
                    </div>
                </div>
            </div>

            <div v-if="templateHighlights.length > 0 || template?.source_url" class="mt-3 flex flex-wrap gap-x-5 gap-y-2 border-t border-border/70 pt-3">
                <div
                    v-for="detail in templateHighlights"
                    :key="detail.label"
                    class="flex items-center gap-2"
                >
                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        {{ detail.label }}
                    </span>

                    <span class="ml-2 text-sm text-foreground">
                        {{ detail.value }}
                    </span>
                </div>

                <div v-if="template?.source_url" class="flex items-center gap-2">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        Source
                    </span>

                    <a
                        :href="template.source_url"
                        target="_blank"
                        rel="noreferrer"
                        class="ml-2 text-sm text-foreground underline underline-offset-4"
                    >
                        Official guidance
                    </a>

                    <span v-if="template.source_checked_at" class="ml-2 text-xs text-muted-foreground">
                        Checked {{ formatDate(template.source_checked_at) }}
                    </span>
                </div>
            </div>
        </div>

        <div v-if="items.length === 0" class="px-4 py-6 text-sm text-muted-foreground">
            Pick one of the supported country and visa combinations to generate a requirement workflow.
        </div>

        <div v-else class="divide-y divide-border/70">
            <article
                v-for="item in items"
                :key="item.id"
                class="border-l-4 px-4 py-3 transition-colors"
                :class="[
                    item.is_completed
                        ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/10'
                        : 'border-transparent hover:bg-muted/10',
                    updatingRequirementId === item.id || uploadingRequirementId === item.id
                        ? 'pointer-events-none opacity-60'
                        : '',
                ]"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start gap-3">
                            <input
                                :checked="item.is_completed"
                                class="mt-1 size-4 shrink-0 rounded border-border text-foreground focus:ring-ring/20"
                                type="checkbox"
                                :disabled="updatingRequirementId === item.id"
                                @change="toggleRequirement(item.id, ($event.target as HTMLInputElement).checked)"
                            />

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="text-sm font-medium text-foreground"
                                        :class="{ 'line-through decoration-muted-foreground/60': item.is_completed }"
                                    >
                                        {{ item.label }}
                                    </p>

                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="statusClasses(item.status)">
                                        {{ item.status_label }}
                                    </span>

                                    <span
                                        v-if="item.is_required"
                                        class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                                    >
                                        Required
                                    </span>

                                    <span
                                        v-if="item.category"
                                        class="rounded-full border border-border/70 bg-background px-2 py-0.5 text-[11px] text-muted-foreground"
                                    >
                                        {{ item.category }}
                                    </span>

                                    <span
                                        v-if="item.attachments.length > 0"
                                        class="rounded-full border border-border/70 bg-background px-2 py-0.5 text-[11px] text-muted-foreground"
                                    >
                                        {{ item.attachments.length }} file{{ item.attachments.length === 1 ? '' : 's' }}
                                    </span>

                                    <span
                                        v-if="updatingRequirementId === item.id"
                                        class="rounded-full bg-background px-2 py-0.5 text-[11px] text-muted-foreground"
                                    >
                                        Saving...
                                    </span>

                                    <span
                                        v-if="uploadingRequirementId === item.id"
                                        class="rounded-full bg-background px-2 py-0.5 text-[11px] text-muted-foreground"
                                    >
                                        Uploading...
                                    </span>

                                    <label
                                        class="ml-auto inline-flex cursor-pointer items-center rounded-md border border-border/70 bg-background px-2.5 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    >
                                        Upload
                                        <input class="hidden" type="file" @change="uploadAttachment(item.id, $event)" />
                                    </label>
                                </div>

                                <div v-if="itemDatesMap[item.id]?.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="detail in itemDatesMap[item.id]"
                                        :key="`${item.id}-${detail.label}`"
                                        class="rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground"
                                    >
                                        {{ detail.label }} {{ detail.value }}
                                    </span>
                                </div>

                                <details
                                    v-if="hasExtraDetails(item)"
                                    :open="!item.is_completed"
                                    class="group mt-2 rounded-lg border border-border/70 bg-muted/15 open:bg-muted/25"
                                >
                                    <summary
                                        class="flex cursor-pointer list-none flex-wrap items-center gap-2 px-3 py-2 text-xs font-medium text-muted-foreground marker:content-none"
                                    >
                                        <span class="rounded-full border border-border/70 bg-background px-2 py-0.5">
                                            Details
                                        </span>

                                        <span v-if="item.help_text">Guidance</span>
                                        <span v-if="item.review_notes">Notes</span>
                                        <span v-if="item.attachments.length > 0">Files</span>
                                    </summary>

                                    <div class="space-y-3 border-t border-border/70 px-3 py-3">
                                        <p v-if="item.help_text" class="text-sm text-muted-foreground">
                                            {{ item.help_text }}
                                        </p>

                                        <p
                                            v-if="item.review_notes"
                                            class="rounded-lg border border-border/70 bg-background px-3 py-2 text-sm text-muted-foreground"
                                        >
                                            {{ item.review_notes }}
                                        </p>

                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                                Files
                                            </p>

                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <a
                                                    v-for="attachment in item.attachments"
                                                    :key="attachment.id"
                                                    :href="attachment.download_url"
                                                    class="rounded-full border border-border bg-background px-3 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                >
                                                    {{ attachment.original_name }}
                                                </a>

                                                <span v-if="item.attachments.length === 0" class="text-sm text-muted-foreground">
                                                    No files yet
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 lg:w-[300px] lg:flex-none">
                        <select
                            :value="item.status"
                            :class="inputClass"
                            :disabled="updatingRequirementId === item.id"
                            @change="updateStatus(item.id, $event)"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>

                        <input
                            :value="item.due_at ?? ''"
                            type="date"
                            :class="inputClass"
                            :disabled="updatingRequirementId === item.id"
                            @change="updateDueDate(item.id, $event)"
                        />
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
