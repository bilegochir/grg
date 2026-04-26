<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Paperclip, Pencil } from 'lucide-vue-next';
import { computed } from 'vue';

interface VisaCaseDetail {
    id: number;
    reference_code: string;
    visa_type: string;
    destination_country: string;
    institution_name: null | string;
    status: string;
    status_label: string;
    client_name: null | string;
    assignee_name: null | string;
    submitted_at: null | string;
    decision_at: null | string;
}

const props = defineProps<{
    visaCase: VisaCaseDetail;
    requirementsCount: number;
    completedRequirementsCount: number;
    tasksCount: number;
    attachmentsCount: number;
    institutionRequirements: Record<string, boolean>;
}>();

defineEmits<{
    edit: [];
}>();

const institutionRequirementKey = (country: string, visaType: string) => `${country}::${visaType}`;

const requiresInstitutionName = computed(
    () => props.institutionRequirements[institutionRequirementKey(props.visaCase.destination_country, props.visaCase.visa_type)] ?? false,
);

const heroBadge = computed(() => {
    const code = props.visaCase.reference_code.replace(/[^a-z]/gi, '').slice(0, 2).toUpperCase();
    return code || 'VC';
});

const requirementsProgress = computed(() => {
    if (!props.requirementsCount) return 0;
    return Math.round((props.completedRequirementsCount / props.requirementsCount) * 100);
});

// SVG ring: r=13, circumference ≈ 81.68
const ringCircumference = 2 * Math.PI * 13;
const ringDasharray = computed(() => {
    const filled = (requirementsProgress.value / 100) * ringCircumference;
    return `${filled.toFixed(2)} ${ringCircumference.toFixed(2)}`;
});

const formatDateOnly = (value: null | string) => {
    if (!value) return 'Not set';
    return new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(`${value.slice(0, 10)}T00:00:00`));
};

const statusClasses: Record<string, string> = {
    intake: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
    documents_pending: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-200',
    ready_to_file: 'bg-violet-100 text-violet-800 dark:bg-violet-950/70 dark:text-violet-200',
    submitted: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-200',
    closed: 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200',
};

const statusClass = computed(() => statusClasses[props.visaCase.status] ?? 'bg-secondary text-secondary-foreground');

const metaItems = computed(() => {
    const items = [
        { label: 'Agent', value: props.visaCase.assignee_name || 'Unassigned' },
        { label: 'Submitted', value: formatDateOnly(props.visaCase.submitted_at) },
        { label: 'Decision', value: formatDateOnly(props.visaCase.decision_at) },
    ];
    if (requiresInstitutionName.value) {
        items.unshift({ label: 'School', value: props.visaCase.institution_name || 'Not set' });
    }
    return items;
});
</script>

<template>
    <section class="app-panel overflow-hidden rounded-xl border border-border/60 bg-card">
        <!-- Header row -->
        <div class="flex items-start gap-3.5 px-5 pt-5 pb-0">
            <div
                class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-border/70 bg-muted/40 text-xs font-semibold tracking-widest text-foreground"
            >
                {{ heroBadge }}
            </div>

            <div class="min-w-0 flex-1 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-[17px] font-medium tracking-tight text-foreground">
                        {{ visaCase.client_name ?? '—' }}
                    </h1>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold" :class="statusClass">
                        {{ visaCase.status_label }}
                    </span>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ visaCase.destination_country }} · {{ visaCase.visa_type }}
                </p>
            </div>

            <!-- Actions pinned top-right -->
            <div class="flex shrink-0 items-center gap-2">
                <Button size="sm" class="h-8 gap-1.5 rounded-lg px-3 text-xs" @click="$emit('edit')">
                    <Pencil class="size-3.5" />
                    Edit
                </Button>
                <Button as-child variant="ghost" size="sm" class="h-8 gap-1.5 rounded-lg px-3 text-xs">
                    <Link href="/visa-cases">
                        <ArrowLeft class="size-3.5" />
                        Cases
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Filing details -->
        <dl class="grid gap-x-5 gap-y-3 px-5 py-4 sm:grid-cols-2 lg:grid-cols-4 border-t border-border/50 mt-4">
            <div v-for="item in metaItems" :key="item.label" class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                    {{ item.label }}
                </dt>
                <dd class="mt-0.5 truncate text-sm font-medium text-foreground">
                    {{ item.value }}
                </dd>
            </div>
        </dl>

        <!-- Stats bar -->
        <div class="grid grid-cols-3 border-t border-border/50">
            <!-- Requirements with progress ring -->
            <div class="flex items-center gap-3 border-r border-border/50 px-4 py-3">
                <div class="relative size-8 shrink-0">
                    <svg width="32" height="32" viewBox="0 0 32 32">
                        <circle cx="16" cy="16" r="13" fill="none" stroke="currentColor" class="text-border" stroke-width="3" />
                        <circle
                            cx="16" cy="16" r="13" fill="none"
                            class="text-emerald-500"
                            stroke="currentColor"
                            stroke-width="3"
                            :stroke-dasharray="ringDasharray"
                            stroke-linecap="round"
                            transform="rotate(-90 16 16)"
                        />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-[9px] font-semibold text-foreground">
                        {{ requirementsProgress }}%
                    </span>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">Requirements</p>
                    <p class="text-sm font-medium text-foreground">
                        {{ completedRequirementsCount }} of {{ requirementsCount }} done
                    </p>
                </div>
            </div>

            <!-- Tasks -->
            <div class="flex items-center gap-3 border-r border-border/50 px-4 py-3">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-950/50">
                    <FileText class="size-4 text-sky-600 dark:text-sky-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">Tasks</p>
                    <p class="text-sm font-medium text-foreground">{{ tasksCount }} open</p>
                </div>
            </div>

            <!-- Attachments -->
            <div class="flex items-center gap-3 px-4 py-3">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted/60">
                    <Paperclip class="size-4 text-muted-foreground" />
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">Attachments</p>
                    <p class="text-sm font-medium text-foreground">{{ attachmentsCount }} files</p>
                </div>
            </div>
        </div>
    </section>
</template>
