<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from 'lucide-vue-next';
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
        { label: 'Agent', value: props.visaCase.assignee_name || 'Unassigned' },
        { label: 'Submitted', value: formatDateOnly(props.visaCase.submitted_at) },
        { label: 'Decision', value: formatDateOnly(props.visaCase.decision_at) },
    ];

    if (requiresInstitutionName.value) {
        items.unshift({
            label: 'School',
            value: props.visaCase.institution_name || 'Not set',
        });
    }

    return items;
});
</script>

<template>
    <section class="app-panel overflow-hidden">
        <div class="grid gap-5 px-4 py-4 lg:grid-cols-[minmax(0,1.2fr)_320px] lg:px-5">
            <div class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-xl border border-border/80 bg-muted/30 text-sm font-semibold tracking-[0.18em] text-foreground shadow-sm"
                    >
                        {{ heroBadge }}
                    </div>

                    <div class="min-w-0 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">
                                {{ visaCase.reference_code }}
                            </h1>

                            <span
                                class="rounded-full px-2.5 py-1 text-[10px] font-semibold"
                                :class="statusClasses(visaCase.status)"
                            >
                                {{ visaCase.status_label }}
                            </span>
                        </div>

                        <p class="text-sm font-medium text-foreground">
                            {{ visaCase.client_name || 'No client linked' }} · {{ visaCase.destination_country }} · {{ visaCase.visa_type }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button type="button" size="sm" class="gap-2 rounded-lg px-3" @click="$emit('edit')">
                        <Pencil class="size-4" />
                        Edit case
                    </Button>

                    <Button as-child variant="ghost" size="sm" class="gap-2 rounded-lg">
                        <Link href="/visa-cases">
                            <ArrowLeft class="size-4" />
                            Back
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="border-t border-border/70 pt-3 lg:col-span-2">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-muted-foreground">Filing details</p>

                <dl class="mt-3 grid gap-x-5 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="item in metaItems" :key="item.label" class="min-w-0">
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                            {{ item.label }}
                        </dt>
                        <dd class="mt-0.5 truncate text-sm font-medium text-foreground">
                            {{ item.value }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
</template>
