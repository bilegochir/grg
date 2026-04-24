<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, FileText, ListTodo, Pencil } from 'lucide-vue-next';
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

const requirementProgress = computed(() => {
    if (props.requirementsCount === 0) return 0;

    return Math.round((props.completedRequirementsCount / props.requirementsCount) * 100);
});

const formatDateOnly = (value: null | string) => {
    if (!value) return 'Not set';

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
    }).format(new Date(`${value.slice(0, 10)}T00:00:00`));
};

const statusClasses = (status: string) =>
    ({
        intake: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
        documents_pending: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-200',
        ready_to_file: 'bg-violet-100 text-violet-800 dark:bg-violet-950/70 dark:text-violet-200',
        submitted: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-200',
        closed: 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200',
    })[status] ?? 'bg-secondary text-secondary-foreground';

const metaItems = computed(() => {
    const items = [
        { label: 'Client', value: props.visaCase.client_name || 'No client linked' },
        { label: 'Destination', value: props.visaCase.destination_country },
        { label: 'Visa', value: props.visaCase.visa_type },
        { label: 'Agent', value: props.visaCase.assignee_name || 'Unassigned' },
        { label: 'Submitted', value: formatDateOnly(props.visaCase.submitted_at) },
        { label: 'Decision', value: formatDateOnly(props.visaCase.decision_at) },
    ];

    if (requiresInstitutionName.value) {
        items.splice(3, 0, {
            label: 'School',
            value: props.visaCase.institution_name || 'Not set',
        });
    }

    return items;
});
</script>

<template>
    <section class="app-panel overflow-hidden">
        <div class="space-y-4 px-4 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">
                            {{ visaCase.reference_code }}
                        </h1>

                        <span
                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                            :class="statusClasses(visaCase.status)"
                        >
                            {{ visaCase.status_label }}
                        </span>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        {{ visaCase.client_name || 'No client linked' }} · {{ visaCase.destination_country }} · {{ visaCase.visa_type }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button type="button" size="sm" class="gap-2" @click="$emit('edit')">
                        <Pencil class="size-4" />
                        Edit case
                    </Button>

                    <Button as-child variant="outline" size="sm" class="gap-2">
                        <Link href="/visa-cases">
                            <ArrowLeft class="size-4" />
                            Back
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-border/70 bg-muted/30 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-muted-foreground">Requirements</p>
                            <p class="mt-1 text-lg font-semibold text-foreground">
                                {{ completedRequirementsCount }}/{{ requirementsCount }}
                            </p>
                        </div>

                        <div class="rounded-full bg-background p-2 text-muted-foreground">
                            <CheckCircle2 class="size-4" />
                        </div>
                    </div>

                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-background">
                        <div
                            class="h-full rounded-full bg-foreground transition-all"
                            :style="{ width: `${requirementProgress}%` }"
                        />
                    </div>

                    <p class="mt-1.5 text-xs text-muted-foreground">{{ requirementProgress }}% complete</p>
                </div>

                <div class="rounded-xl border border-border/70 bg-muted/30 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-muted-foreground">Open tasks</p>
                            <p class="mt-1 text-lg font-semibold text-foreground">{{ tasksCount }}</p>
                        </div>

                        <div class="rounded-full bg-background p-2 text-muted-foreground">
                            <ListTodo class="size-4" />
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-muted-foreground">
                        Track follow-ups and internal case work.
                    </p>
                </div>

                <div class="rounded-xl border border-border/70 bg-muted/30 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-muted-foreground">Files</p>
                            <p class="mt-1 text-lg font-semibold text-foreground">{{ attachmentsCount }}</p>
                        </div>

                        <div class="rounded-full bg-background p-2 text-muted-foreground">
                            <FileText class="size-4" />
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-muted-foreground">
                        Uploaded case documents and evidence.
                    </p>
                </div>
            </div>

            <dl class="grid gap-x-4 gap-y-3 border-t border-border/70 pt-3 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="item in metaItems" :key="item.label" class="min-w-0">
                    <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        {{ item.label }}
                    </dt>
                    <dd class="mt-1 truncate text-sm text-foreground">
                        {{ item.value }}
                    </dd>
                </div>
            </dl>
        </div>
    </section>
</template>
