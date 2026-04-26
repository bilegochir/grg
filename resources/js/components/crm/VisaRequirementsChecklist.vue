<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Paperclip, Upload } from 'lucide-vue-next';
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
    'h-7 rounded-md border border-input bg-background px-2 py-1 text-xs focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15 disabled:cursor-not-allowed disabled:opacity-60';

const completedCount = computed(() => props.items.filter((i) => i.is_completed).length);

const completionPercent = computed(() => {
    if (!props.items.length) return 0;
    return Math.round((completedCount.value / props.items.length) * 100);
});

const templateHighlights = computed((): DetailItem[] => {
    if (!props.template) return [];
    return [
        { label: 'Processing', value: props.template.processing_time_summary ?? '' },
        { label: 'Stay',       value: props.template.stay_summary ?? '' },
        { label: 'Fee',        value: props.template.fee_summary ?? '' },
    ].filter((d) => d.value !== '');
});

const formatDate = (value: null | string): string => {
    if (!value) return '';
    return new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(
        new Date(`${value.slice(0, 10)}T00:00:00`),
    );
};

// FIX: derive per-item dates once, keyed by id, so we never call this twice per render.
const itemDatesMap = computed(() => {
    const map = new Map<number, DetailItem[]>();
    for (const item of props.items) {
        map.set(item.id, [
            { label: 'Requested', value: formatDate(item.requested_at) },
            { label: 'Received',  value: formatDate(item.received_at) },
            { label: 'Reviewed',  value: formatDate(item.reviewed_at) },
            { label: 'Due',       value: formatDate(item.due_at) },
        ].filter((d) => d.value !== ''));
    }
    return map;
});

const isDueSoon = (item: RequirementItem): boolean => {
    if (!item.due_at || item.is_completed) return false;
    const daysUntilDue = (new Date(item.due_at).getTime() - Date.now()) / 86_400_000;
    return daysUntilDue <= 7;
};

const patchRequirement = (requirementId: number, payload: Record<string, unknown>): void => {
    updatingRequirementId.value = requirementId;
    router.patch(
        route('visa-cases.requirements.update', [props.visaCaseId, requirementId]),
        payload,
        {
            preserveScroll: true,
            onFinish: () => { updatingRequirementId.value = null; },
        },
    );
};

const toggleRequirement = (id: number, checked: boolean) => patchRequirement(id, { is_completed: checked });
const updateStatus   = (id: number, e: Event) => patchRequirement(id, { status: (e.target as HTMLSelectElement).value });
const updateDueDate  = (id: number, e: Event) => {
    const v = (e.target as HTMLInputElement).value;
    patchRequirement(id, { due_at: v || null });
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
            onFinish: () => { input.value = ''; uploadingRequirementId.value = null; },
        },
    );
};

const statusClasses = (status: string): string =>
    ({
        pending:   'bg-neutral-100 text-neutral-600 dark:bg-neutral-900/70 dark:text-neutral-300',
        requested: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-200',
        received:  'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        verified:  'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        waived:    'bg-violet-100 text-violet-800 dark:bg-violet-950/70 dark:text-violet-200',
    })[status] ?? 'bg-neutral-100 text-neutral-600 dark:bg-neutral-900/70 dark:text-neutral-300';

const isBusy = (item: RequirementItem) =>
    updatingRequirementId.value === item.id || uploadingRequirementId.value === item.id;

const busyLabel = (item: RequirementItem): string | null => {
    if (updatingRequirementId.value === item.id) return 'Saving…';
    if (uploadingRequirementId.value === item.id) return 'Uploading…';
    return null;
};
</script>

<template>
    <section class="app-panel overflow-hidden rounded-xl border border-border/60 bg-card">

        <!-- ── Header ── -->
        <header class="space-y-3 border-b border-border/60 px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-[15px] font-medium text-foreground">Requirements</h2>
                    <p v-if="!template" class="mt-0.5 text-sm text-muted-foreground">
                        No template matched this destination and visa type yet.
                    </p>
                    <p v-else-if="template.description" class="mt-0.5 max-w-xl text-sm text-muted-foreground">
                        {{ template.description }}
                    </p>
                    <p v-else-if="templateHighlights.length" class="mt-0.5 text-sm text-muted-foreground">
                        <span v-for="(h, i) in templateHighlights" :key="h.label">
                            <span v-if="i > 0" class="mx-1.5 text-border">·</span>
                            <span class="text-muted-foreground/60">{{ h.label }}</span>
                            {{ h.value }}
                        </span>
                    </p>
                </div>
            </div>

            <div v-if="template?.source_url" class="flex items-center gap-3 text-xs text-muted-foreground">
                <a
                    :href="template.source_url"
                    target="_blank"
                    rel="noreferrer"
                    class="text-sky-600 hover:underline dark:text-sky-400"
                >
                    Official guidance ↗
                </a>
                <span v-if="template.source_checked_at">Checked {{ formatDate(template.source_checked_at) }}</span>
            </div>
        </header>

        <!-- ── Empty state ── -->
        <div v-if="!items.length" class="px-5 py-8 text-center text-sm text-muted-foreground">
            Pick a supported country and visa type to generate a requirement workflow.
        </div>

        <!-- ── Items ── -->
        <ul v-else class="divide-y divide-border/60">
            <li
                v-for="item in items"
                :key="item.id"
                class="relative flex flex-col transition-colors"
                :class="[
                    item.is_completed ? 'bg-emerald-50/40 dark:bg-emerald-950/10' : 'hover:bg-muted/10',
                    isBusy(item) ? 'pointer-events-none opacity-55' : '',
                ]"
            >
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 w-[3px] transition-colors"
                    :class="item.is_completed ? 'bg-emerald-500' : 'bg-transparent'"
                />

                <div class="flex items-start gap-3 px-5 py-3">

                    <!-- Checkbox -->
                    <button
                        type="button"
                        class="mt-0.5 flex size-[18px] shrink-0 items-center justify-center rounded transition-colors"
                        :class="item.is_completed
                            ? 'bg-emerald-500 text-white'
                            : 'border border-border/70 bg-background hover:border-border'"
                        :disabled="updatingRequirementId === item.id"
                        @click="toggleRequirement(item.id, !item.is_completed)"
                    >
                        <Check v-if="item.is_completed" class="size-3" />
                    </button>

                    <!-- Main content -->
                    <div class="min-w-0 flex-1 space-y-1.5">

                        <!-- Label + badges row -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                class="text-sm font-medium text-foreground"
                                :class="{ 'line-through decoration-muted-foreground/50': item.is_completed }"
                            >
                                {{ item.label }}
                            </span>

                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="statusClasses(item.status)"
                            >
                                {{ item.status_label }}
                            </span>

                            <span
                                v-if="item.is_required"
                                class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
                            >
                                Required
                            </span>

                            <span
                                v-if="item.category"
                                class="rounded-full border border-border/60 bg-background px-2 py-0.5 text-[11px] text-muted-foreground"
                            >
                                {{ item.category }}
                            </span>

                            <span
                                v-if="item.attachments.length"
                                class="inline-flex items-center gap-1 rounded-full border border-border/60 bg-background px-2 py-0.5 text-[11px] text-muted-foreground"
                            >
                                <Paperclip class="size-2.5" />
                                {{ item.attachments.length }} file{{ item.attachments.length === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <!-- Dates row -->
                        <div v-if="itemDatesMap.get(item.id)?.length" class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span
                                v-for="d in itemDatesMap.get(item.id)"
                                :key="d.label"
                                class="text-[11px]"
                                :class="d.label === 'Due' && isDueSoon(item)
                                    ? 'rounded-full bg-rose-50 px-2 py-0.5 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                                    : 'text-muted-foreground'"
                            >
                                <span class="text-muted-foreground/50">{{ d.label }}</span> {{ d.value }}
                            </span>
                        </div>

                        <!-- Help text / notes / attachments -->
                        <div
                            v-if="item.help_text || item.review_notes || item.attachments.length"
                            class="space-y-2 rounded-lg border border-border/50 bg-muted/20 px-3 py-2.5 text-sm text-muted-foreground"
                        >
                            <p v-if="item.help_text">{{ item.help_text }}</p>

                            <blockquote
                                v-if="item.review_notes"
                                class="border-l-2 border-border pl-2.5 text-foreground/70"
                            >
                                {{ item.review_notes }}
                            </blockquote>

                            <div v-if="item.attachments.length" class="flex flex-wrap gap-1.5">
                                <a
                                    v-for="a in item.attachments"
                                    :key="a.id"
                                    :href="a.download_url"
                                    class="inline-flex items-center gap-1 rounded-md border border-border/60 bg-background px-2 py-0.5 text-xs text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                >
                                    {{ a.original_name }}
                                </a>
                            </div>
                        </div>

                        <!-- Bottom row: upload + busy indicator -->
                        <div class="flex items-center gap-3">
                            <!--
                                FIX: guard upload with :class disabled state separately from
                                pointer-events-none on the <li>, since <label> ignores CSS
                                pointer-events on its child <input> in some browsers.
                            -->
                            <label
                                class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-muted-foreground transition-colors hover:text-foreground"
                                :class="{ 'pointer-events-none opacity-50': isBusy(item) }"
                            >
                                <Upload class="size-3.5" />
                                Upload file
                                <input
                                    type="file"
                                    class="hidden"
                                    :disabled="isBusy(item)"
                                    @change="uploadAttachment(item.id, $event)"
                                />
                            </label>

                            <!--
                                FIX: busy label moved out of the badge row into its own
                                dedicated bottom row so it never interrupts the pill layout.
                            -->
                            <span
                                v-if="busyLabel(item)"
                                class="animate-pulse text-[11px] text-muted-foreground"
                            >
                                {{ busyLabel(item) }}
                            </span>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="flex shrink-0 flex-col gap-1.5 self-start pt-0.5 sm:flex-row sm:items-center">
                        <select
                            :value="item.status"
                            :class="inputClass"
                            :disabled="updatingRequirementId === item.id"
                            @change="updateStatus(item.id, $event)"
                        >
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
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
            </li>
        </ul>
    </section>
</template>
