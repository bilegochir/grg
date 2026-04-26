<script setup lang="ts">
import ActivityTimeline from '@/components/crm/ActivityTimeline.vue';
import AttachmentsPanel from '@/components/crm/AttachmentsPanel.vue';
import NotesPanel from '@/components/crm/NotesPanel.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Dialog, DialogFooter, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Briefcase,
    Check,
    ChevronDown,
    Copy,
    ExternalLink,
    GraduationCap,
    Pencil,
    Plus,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ClientFamilyMember {
    relationship: null | string;
    full_name: null | string;
    date_of_birth: null | string;
    nationality: null | string;
    occupation: null | string;
    is_accompanying: boolean;
}

interface ClientEducationRecord {
    institution: null | string;
    qualification: null | string;
    field_of_study: null | string;
    country: null | string;
    start_date: null | string;
    end_date: null | string;
    is_current: boolean;
}

interface ClientWorkExperience {
    employer: null | string;
    job_title: null | string;
    country: null | string;
    start_date: null | string;
    end_date: null | string;
    is_current: boolean;
    summary: null | string;
}

interface ClientDetail {
    id: number;
    full_name: string;
    email: null | string;
    phone: null | string;
    date_of_birth: null | string;
    passport_number: null | string;
    passport_expiry_date: null | string;
    marital_status: null | string;
    occupation: null | string;
    current_address: null | string;
    nationality: null | string;
    destination_country: null | string;
    lead_source: null | string;
    status: string;
    status_label: string;
    owner_name: null | string;
    portal_url: string;
    portal_login_url: string;
    family_members: ClientFamilyMember[];
    education_history: ClientEducationRecord[];
    work_experiences: ClientWorkExperience[];
    notes: Array<{ id: number; body: string; author_name: null | string; created_at: null | string }>;
    attachments: Array<{
        id: number;
        original_name: string;
        mime_type: null | string;
        size: string;
        uploaded_by: null | string;
        created_at: null | string;
        download_url: string;
    }>;
}

interface ClientVisaCase {
    id: number;
    reference_code: string;
    visa_type: string;
    status_label: string;
    assignee_name: null | string;
    show_url: string;
}

interface ClientTask {
    id: number;
    title: string;
    status_label: string;
    priority_label: string;
    assignee_name: null | string;
    due_at: null | string;
}

interface TimelineItem {
    type: string;
    title: string;
    description: string;
    created_at: string;
    meta: Record<string, null | string>;
}

interface Option {
    value: string;
    label: string;
}

interface DetailItem {
    label: string;
    value: string;
    empty: boolean;
    warn?: boolean;
    span?: number;
    href?: string;
}

const props = defineProps<{
    client: ClientDetail;
    maritalStatusOptions: Option[];
    statusOptions: Option[];
    visaCases: ClientVisaCase[];
    tasks: ClientTask[];
    timeline: TimelineItem[];
}>();

const page = usePage<SharedData>();
const isEditDialogOpen = ref(false);
const copiedPortalLoginLink = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: props.client.full_name, href: `/clients/${props.client.id}` },
];

const blankFamilyMember    = (): ClientFamilyMember    => ({ relationship: '', full_name: '', date_of_birth: '', nationality: '', occupation: '', is_accompanying: false });
const blankEducationRecord = (): ClientEducationRecord => ({ institution: '', qualification: '', field_of_study: '', country: '', start_date: '', end_date: '', is_current: false });
const blankWorkExperience  = (): ClientWorkExperience  => ({ employer: '', job_title: '', country: '', start_date: '', end_date: '', is_current: false, summary: '' });

const cloneRecords = <T,>(records: T[]): T[] => records.map((r) => ({ ...r }));

const form = useForm({
    full_name:            props.client.full_name,
    email:                props.client.email ?? '',
    phone:                props.client.phone ?? '',
    date_of_birth:        props.client.date_of_birth ?? '',
    passport_number:      props.client.passport_number ?? '',
    passport_expiry_date: props.client.passport_expiry_date ?? '',
    marital_status:       props.client.marital_status ?? '',
    occupation:           props.client.occupation ?? '',
    current_address:      props.client.current_address ?? '',
    nationality:          props.client.nationality ?? '',
    destination_country:  props.client.destination_country ?? '',
    lead_source:          props.client.lead_source ?? '',
    status:               props.client.status,
    family_members:    props.client.family_members.length    > 0 ? cloneRecords(props.client.family_members)    : [blankFamilyMember()],
    education_history: props.client.education_history.length > 0 ? cloneRecords(props.client.education_history) : [blankEducationRecord()],
    work_experiences:  props.client.work_experiences.length  > 0 ? cloneRecords(props.client.work_experiences)  : [blankWorkExperience()],
});

// Initials — join('') not join(' ')
const clientInitials = computed(() =>
    props.client.full_name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join(''),
);

// ── Formatters ────────────────────────────────────────────────────────────────

const formatDate = (value: null | string): string =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '';

const formatDateOnly = (value: null | string): string =>
    value
        ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(`${value.slice(0, 10)}T00:00:00`))
        : '';

const formatDateRange = (start: null | string, end: null | string, isCurrent: boolean): string => {
    if (!start && !end && !isCurrent) return 'Dates not set';
    const s = start ? formatDateOnly(start) : 'Unknown start';
    const e = isCurrent ? 'Present' : end ? formatDateOnly(end) : 'Unknown end';
    return `${s} – ${e}`;
};

const formatValue = (value: null | string): string => {
    if (!value) return '';
    return value.split('_').map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join(' ');
};

const formatCountLabel = (count: number, singular: string, plural: string) =>
    `${count} ${count === 1 ? singular : plural}`;

// ── Passport expiry warning (< 6 months) ─────────────────────────────────────

const passportExpirySoon = computed(() => {
    if (!props.client.passport_expiry_date) return false;
    const months = (new Date(props.client.passport_expiry_date).getTime() - Date.now()) / (1000 * 60 * 60 * 24 * 30);
    return months < 6;
});

// ── Detail items — mosaic grid ────────────────────────────────────────────────

const detailItems = computed((): DetailItem[] => [
    { label: 'Email',          value: props.client.email           || 'Not set', empty: !props.client.email,           href: props.client.email ? `mailto:${props.client.email}` : undefined },
    { label: 'Phone',          value: props.client.phone           || 'Not set', empty: !props.client.phone,           href: props.client.phone ? `tel:${props.client.phone}` : undefined },
    { label: 'Nationality',    value: props.client.nationality     || 'Not set', empty: !props.client.nationality },
    { label: 'Date of birth',  value: formatDateOnly(props.client.date_of_birth) || 'Not set', empty: !props.client.date_of_birth },
    { label: 'Passport',       value: props.client.passport_number || 'Not set', empty: !props.client.passport_number },
    { label: 'Passport expiry',value: formatDateOnly(props.client.passport_expiry_date) || 'Not set', empty: !props.client.passport_expiry_date, warn: passportExpirySoon.value },
    { label: 'Occupation',     value: props.client.occupation      || 'Not set', empty: !props.client.occupation },
    { label: 'Marital status', value: formatValue(props.client.marital_status) || 'Not set', empty: !props.client.marital_status },
    { label: 'Lead source',    value: props.client.lead_source     || 'Not set', empty: !props.client.lead_source },
    { label: 'Owner',          value: props.client.owner_name      || 'Unassigned', empty: !props.client.owner_name },
    { label: 'Destination',    value: props.client.destination_country || 'Not set', empty: !props.client.destination_country },
    { label: 'Address',        value: props.client.current_address || 'Not set', empty: !props.client.current_address, span: 2 },
]);

// ── Quick stats bar ───────────────────────────────────────────────────────────

const quickStats = computed(() => [
    { label: 'Cases', value: props.visaCases.length },
    { label: 'Tasks', value: props.tasks.length },
    { label: 'Files', value: props.client.attachments.length },
]);

// ── Background summaries ──────────────────────────────────────────────────────

const firstRecord = <T extends Record<string, unknown>>(
    records: T[],
    primaryKey: keyof T,
    fallbackKey: keyof T,
): string => {
    const first = records[0];
    if (!first) return 'No records';
    const val = (first[primaryKey] || first[fallbackKey]) as string | null | undefined;
    if (!val) return 'No records';
    return records.length > 1 ? `${val} +${records.length - 1}` : val;
};

const backgroundSummaries = computed(() => ({
    family:    firstRecord(props.client.family_members,    'full_name',   'relationship'),
    education: firstRecord(props.client.education_history, 'institution', 'qualification'),
    work:      firstRecord(props.client.work_experiences,  'job_title',   'employer'),
}));

// ── Form submission ───────────────────────────────────────────────────────────

const submit = () => {
    form.patch(route('clients.update', props.client.id), {
        preserveScroll: true,
        onSuccess: () => { isEditDialogOpen.value = false; },
    });
};

const addFamilyMember    = () => form.family_members.push(blankFamilyMember());
const addEducationRecord = () => form.education_history.push(blankEducationRecord());
const addWorkExperience  = () => form.work_experiences.push(blankWorkExperience());

const removeFamilyMember = (i: number) => {
    if (form.family_members.length === 1) { form.family_members[0] = blankFamilyMember(); return; }
    form.family_members.splice(i, 1);
};
const removeEducationRecord = (i: number) => {
    if (form.education_history.length === 1) { form.education_history[0] = blankEducationRecord(); return; }
    form.education_history.splice(i, 1);
};
const removeWorkExperience = (i: number) => {
    if (form.work_experiences.length === 1) { form.work_experiences[0] = blankWorkExperience(); return; }
    form.work_experiences.splice(i, 1);
};

// ── Portal copy ───────────────────────────────────────────────────────────────

const copyPortalLoginLink = async () => {
    if (!globalThis.navigator?.clipboard) return;
    await globalThis.navigator.clipboard.writeText(props.client.portal_login_url);
    copiedPortalLoginLink.value = true;
    globalThis.setTimeout(() => { copiedPortalLoginLink.value = false; }, 2000);
};

// ── Status / badge classes ────────────────────────────────────────────────────

const statusClasses = (status: string) =>
    ({
        lead:      'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
        qualified: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        active:    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        closed:    'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200',
    })[status] ?? 'bg-secondary text-secondary-foreground';

const taskStatusClasses = (status: string) =>
    ({
        Completed:     'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        'In progress': 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        Todo:          'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200',
        Blocked:       'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-200',
    })[status] ?? 'bg-muted text-muted-foreground';

const taskPriorityClasses = (priority: string) =>
    ({
        High:   'text-rose-600 dark:text-rose-300',
        Medium: 'text-amber-600 dark:text-amber-300',
        Low:    'text-slate-400 dark:text-slate-500',
    })[priority] ?? 'text-muted-foreground';

// ── Shared form element classes ───────────────────────────────────────────────

const selectClass   = 'flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15';
const textareaClass = 'min-h-20 w-full rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15';
</script>

<template>
    <Head :title="client.full_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 md:p-4">

            <!-- Flash message -->
            <div
                v-if="page.props.flash.success"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300"
            >
                {{ page.props.flash.success }}
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_320px]">

                <!-- ── Left column ── -->
                <div class="space-y-4">

                    <!-- Header + stats + details — unified panel -->
                    <section class="app-panel overflow-hidden">

                        <!-- Avatar / name / actions -->
                        <div class="flex flex-col gap-3 px-5 pt-5 pb-4 sm:flex-row sm:items-start">
                            <div
                                class="flex size-14 shrink-0 items-center justify-center rounded-xl border border-border/80 bg-muted/30 text-base font-semibold tracking-wide text-foreground"
                            >
                                {{ clientInitials }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">
                                        {{ client.full_name }}
                                    </h1>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-medium" :class="statusClasses(client.status)">
                                        {{ client.status_label }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ client.destination_country || 'No destination set' }}
                                    <span class="mx-2 text-border">·</span>
                                    {{ client.owner_name || 'Unassigned' }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <Button type="button" size="sm" class="gap-1.5 rounded-lg" @click="isEditDialogOpen = true">
                                        <Pencil class="size-3.5" />
                                        Edit profile
                                    </Button>
                                    <Button as-child variant="ghost" size="sm" class="gap-1.5 rounded-lg">
                                        <Link href="/clients">
                                            <ArrowLeft class="size-3.5" />
                                            Back
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick stats bar -->
                        <div class="grid grid-cols-3 divide-x divide-border/60 border-t border-border/60">
                            <div v-for="stat in quickStats" :key="stat.label" class="bg-muted/20 px-5 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                    {{ stat.label }}
                                </p>
                                <p class="mt-0.5 text-lg font-medium tabular-nums text-foreground">{{ stat.value }}</p>
                            </div>
                        </div>

                        <!-- Details mosaic grid -->
                        <div class="grid grid-cols-3 divide-x divide-y divide-border/60 border-t border-border/60">
                            <div
                                v-for="item in detailItems"
                                :key="item.label"
                                :class="['px-5 py-3', item.span === 2 && 'col-span-2']"
                            >
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                    {{ item.label }}
                                </dt>
                                <dd
                                    class="mt-0.5 text-[13px]"
                                    :class="
                                        item.warn  ? 'font-medium text-amber-600 dark:text-amber-400' :
                                        item.empty ? 'text-muted-foreground/40' :
                                                     'text-foreground'
                                    "
                                >
                                    <a
                                        v-if="item.href && !item.empty"
                                        :href="item.href"
                                        class="text-sky-600 hover:underline dark:text-sky-400"
                                    >
                                        {{ item.value }}
                                    </a>
                                    <template v-else>{{ item.value }}</template>

                                    <!-- Passport expiry warning pill -->
                                    <span
                                        v-if="item.warn"
                                        class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                                    >
                                        Expiring soon
                                    </span>
                                </dd>
                            </div>
                        </div>
                    </section>

                    <!-- Background panel -->
                    <section class="app-panel overflow-hidden">
                        <div class="px-5 py-3">
                            <h2 class="text-sm font-semibold text-foreground">Background</h2>
                        </div>

                        <div class="divide-y divide-border/70 border-t border-border/70">

                            <!-- Family -->
                            <Collapsible v-slot="{ open }" :default-open="false">
                                <CollapsibleTrigger
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/30"
                                >
                                    <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-muted/60">
                                        <Users class="size-3.5 text-muted-foreground" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[13px] font-medium text-foreground">Family</span>
                                            <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                                                {{ formatCountLabel(client.family_members.length, 'record', 'records') }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 truncate text-[11px] text-muted-foreground">
                                            {{ backgroundSummaries.family }}
                                        </p>
                                    </div>
                                    <ChevronDown
                                        class="size-3.5 shrink-0 text-muted-foreground transition-transform"
                                        :class="{ 'rotate-180': open }"
                                    />
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <div class="space-y-2 bg-muted/20 px-4 pb-3 pt-2">
                                        <p v-if="!client.family_members.length" class="text-[13px] text-muted-foreground">
                                            No family information added.
                                        </p>
                                        <div
                                            v-for="(member, i) in client.family_members"
                                            :key="`${member.full_name}-${i}`"
                                            class="rounded-lg border border-border/60 bg-background px-3 py-2.5"
                                        >
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <p class="text-[13px] font-medium text-foreground">
                                                    {{ member.full_name || 'Unnamed member' }}
                                                </p>
                                                <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                                                    {{ member.relationship || 'Relationship not set' }}
                                                </span>
                                                <span
                                                    v-if="member.is_accompanying"
                                                    class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"
                                                >
                                                    Accompanying
                                                </span>
                                            </div>
                                            <p class="mt-1 text-[11px] text-muted-foreground">
                                                {{ member.nationality || 'Nationality not set' }}
                                                · {{ member.occupation  || 'Occupation not set' }}
                                                · {{ formatDateOnly(member.date_of_birth) || 'DOB not set' }}
                                            </p>
                                        </div>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>

                            <!-- Education -->
                            <Collapsible v-slot="{ open }" :default-open="false">
                                <CollapsibleTrigger
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/30"
                                >
                                    <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-muted/60">
                                        <GraduationCap class="size-3.5 text-muted-foreground" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[13px] font-medium text-foreground">Education</span>
                                            <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                                                {{ formatCountLabel(client.education_history.length, 'record', 'records') }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 truncate text-[11px] text-muted-foreground">
                                            {{ backgroundSummaries.education }}
                                        </p>
                                    </div>
                                    <ChevronDown
                                        class="size-3.5 shrink-0 text-muted-foreground transition-transform"
                                        :class="{ 'rotate-180': open }"
                                    />
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <div class="space-y-2 bg-muted/20 px-4 pb-3 pt-2">
                                        <p v-if="!client.education_history.length" class="text-[13px] text-muted-foreground">
                                            No education history added.
                                        </p>
                                        <div
                                            v-for="(record, i) in client.education_history"
                                            :key="`${record.institution}-${i}`"
                                            class="rounded-lg border border-border/60 bg-background px-3 py-2.5"
                                        >
                                            <p class="text-[13px] font-medium text-foreground">
                                                {{ record.institution || 'Institution not set' }}
                                            </p>
                                            <p class="mt-0.5 text-[13px] text-muted-foreground">
                                                {{ record.qualification || 'Qualification not set' }}
                                                <span v-if="record.field_of_study"> · {{ record.field_of_study }}</span>
                                                <span v-if="record.country"> · {{ record.country }}</span>
                                            </p>
                                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                                {{ formatDateRange(record.start_date, record.end_date, record.is_current) }}
                                            </p>
                                        </div>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>

                            <!-- Work experience -->
                            <Collapsible v-slot="{ open }" :default-open="false">
                                <CollapsibleTrigger
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/30"
                                >
                                    <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-muted/60">
                                        <Briefcase class="size-3.5 text-muted-foreground" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[13px] font-medium text-foreground">Work experience</span>
                                            <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                                                {{ formatCountLabel(client.work_experiences.length, 'record', 'records') }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 truncate text-[11px] text-muted-foreground">
                                            {{ backgroundSummaries.work }}
                                        </p>
                                    </div>
                                    <ChevronDown
                                        class="size-3.5 shrink-0 text-muted-foreground transition-transform"
                                        :class="{ 'rotate-180': open }"
                                    />
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <div class="space-y-2 bg-muted/20 px-4 pb-3 pt-2">
                                        <p v-if="!client.work_experiences.length" class="text-[13px] text-muted-foreground">
                                            No work experience added.
                                        </p>
                                        <div
                                            v-for="(exp, i) in client.work_experiences"
                                            :key="`${exp.employer}-${i}`"
                                            class="rounded-lg border border-border/60 bg-background px-3 py-2.5"
                                        >
                                            <p class="text-[13px] font-medium text-foreground">
                                                {{ exp.job_title || 'Role not set' }}
                                                <span class="font-normal text-muted-foreground">
                                                    · {{ exp.employer || 'Employer not set' }}
                                                </span>
                                            </p>
                                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                                {{ exp.country || 'Country not set' }}
                                                · {{ formatDateRange(exp.start_date, exp.end_date, exp.is_current) }}
                                            </p>
                                            <p v-if="exp.summary" class="mt-1 text-[11px] text-muted-foreground">
                                                {{ exp.summary }}
                                            </p>
                                        </div>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        </div>
                    </section>

                    <ActivityTimeline title="Timeline" :items="timeline" />
                </div>

                <!-- ── Right column ── -->
                <div class="space-y-4 xl:sticky xl:top-24">

                    <!-- Portal access -->
                    <section class="app-panel p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-foreground">Portal access</h2>
                            <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">Client</span>
                        </div>

                        <div class="mt-3 space-y-3">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Login URL</p>
                                <!-- Truncated with tooltip — readable in a narrow sidebar -->
                                <p
                                    class="mt-1.5 truncate font-mono text-xs text-muted-foreground"
                                    :title="client.portal_login_url"
                                >
                                    {{ client.portal_login_url }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Button as-child variant="outline" size="sm" class="justify-start gap-2">
                                    <a :href="client.portal_url" target="_blank" rel="noreferrer">
                                        <ExternalLink class="size-3.5" />
                                        Open portal
                                    </a>
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="justify-start gap-2"
                                    :class="copiedPortalLoginLink ? 'text-emerald-600 dark:text-emerald-400' : ''"
                                    @click="copyPortalLoginLink"
                                >
                                    <Check v-if="copiedPortalLoginLink" class="size-3.5 text-emerald-500" />
                                    <Copy v-else class="size-3.5" />
                                    {{ copiedPortalLoginLink ? 'Copied!' : 'Copy login URL' }}
                                </Button>
                            </div>
                        </div>
                    </section>

                    <NotesPanel
                        title="Add note"
                        route-name="clients.notes.store"
                        :route-parameter="client.id"
                        :notes="client.notes"
                        :show-list="false"
                    />

                    <AttachmentsPanel
                        title="Attachments"
                        route-name="clients.attachments.store"
                        :route-parameter="client.id"
                        :attachments="client.attachments"
                    />

                    <!-- Visa cases -->
                    <section class="app-panel p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-foreground">Visa cases</h2>
                            <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">
                                {{ visaCases.length }}
                            </span>
                        </div>

                        <div class="mt-3">
                            <div
                                v-if="!visaCases.length"
                                class="rounded-lg border border-dashed border-border/80 bg-muted/20 px-4 py-5 text-center text-[13px] text-muted-foreground"
                            >
                                No visa cases yet.
                            </div>
                            <div v-else class="divide-y divide-border/70">
                                <div
                                    v-for="visaCase in visaCases"
                                    :key="visaCase.id"
                                    class="flex items-center justify-between gap-3 py-2.5"
                                >
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <p class="text-[13px] font-medium text-foreground">{{ visaCase.reference_code }}</p>
                                            <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                                                {{ visaCase.status_label }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-[11px] text-muted-foreground">
                                            {{ visaCase.visa_type }} · {{ visaCase.assignee_name || 'Unassigned' }}
                                        </p>
                                    </div>
                                    <!-- Compact arrow link — doesn't compete with case info -->
                                    <Button as-child variant="ghost" size="sm" class="h-7 shrink-0 px-2.5 text-xs text-muted-foreground hover:text-foreground">
                                        <Link :href="visaCase.show_url">Open →</Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Tasks -->
                    <section class="app-panel p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-foreground">Tasks</h2>
                            <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">
                                {{ tasks.length }}
                            </span>
                        </div>

                        <div class="mt-3">
                            <div
                                v-if="!tasks.length"
                                class="rounded-lg border border-dashed border-border/80 bg-muted/20 px-4 py-5 text-center text-[13px] text-muted-foreground"
                            >
                                No tasks yet.
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="task in tasks"
                                    :key="task.id"
                                    class="rounded-lg border border-border/70 px-3 py-2.5"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <p class="truncate text-[13px] font-medium text-foreground">{{ task.title }}</p>
                                                <span
                                                    class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                                    :class="taskStatusClasses(task.status_label)"
                                                >
                                                    {{ task.status_label }}
                                                </span>
                                            </div>
                                            <!-- Due date + assignee on one line — no orphaned third row -->
                                            <p class="mt-1 text-[11px] text-muted-foreground">
                                                <template v-if="task.due_at">Due {{ formatDate(task.due_at) }}</template>
                                                <template v-else>No due date</template>
                                                <span class="mx-1.5 text-border">·</span>
                                                {{ task.assignee_name || 'Unassigned' }}
                                            </p>
                                        </div>
                                        <span
                                            class="shrink-0 text-[11px] font-medium"
                                            :class="taskPriorityClasses(task.priority_label)"
                                        >
                                            {{ task.priority_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- ── Edit dialog ── -->
            <Dialog v-model:open="isEditDialogOpen">
                <DialogScrollContent class="max-w-5xl p-0">
                    <DialogHeader class="border-b border-border px-6 py-4">
                        <DialogTitle>Edit client profile</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-5 px-6 py-5" @submit.prevent="submit">

                        <!-- Basic + Identity -->
                        <section class="grid gap-4 lg:grid-cols-2">

                            <!-- Basic details -->
                            <div class="grid content-start gap-4 rounded-lg border border-border p-4">
                                <h3 class="text-sm font-semibold text-foreground">Basic details</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-1.5 sm:col-span-2">
                                        <Label for="full_name">Full name</Label>
                                        <Input id="full_name" v-model="form.full_name" />
                                        <InputError :message="form.errors.full_name" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="email">Email</Label>
                                        <Input id="email" v-model="form.email" type="email" />
                                        <InputError :message="form.errors.email" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="phone">Phone</Label>
                                        <Input id="phone" v-model="form.phone" />
                                        <InputError :message="form.errors.phone" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="status">Status</Label>
                                        <select id="status" v-model="form.status" :class="selectClass">
                                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                        </select>
                                        <InputError :message="form.errors.status" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="lead_source">Lead source</Label>
                                        <Input id="lead_source" v-model="form.lead_source" />
                                        <InputError :message="form.errors.lead_source" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="occupation">Occupation</Label>
                                        <Input id="occupation" v-model="form.occupation" />
                                        <InputError :message="form.errors.occupation" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="destination_country">Destination</Label>
                                        <Input id="destination_country" v-model="form.destination_country" />
                                        <InputError :message="form.errors.destination_country" />
                                    </div>
                                </div>
                            </div>

                            <!-- Identity -->
                            <div class="grid content-start gap-4 rounded-lg border border-border p-4">
                                <h3 class="text-sm font-semibold text-foreground">Identity</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-1.5">
                                        <Label for="date_of_birth">Date of birth</Label>
                                        <Input id="date_of_birth" v-model="form.date_of_birth" type="date" />
                                        <InputError :message="form.errors.date_of_birth" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="nationality">Nationality</Label>
                                        <Input id="nationality" v-model="form.nationality" />
                                        <InputError :message="form.errors.nationality" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="passport_number">Passport number</Label>
                                        <Input id="passport_number" v-model="form.passport_number" />
                                        <InputError :message="form.errors.passport_number" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="passport_expiry_date">Passport expiry</Label>
                                        <Input id="passport_expiry_date" v-model="form.passport_expiry_date" type="date" />
                                        <InputError :message="form.errors.passport_expiry_date" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="marital_status">Marital status</Label>
                                        <select id="marital_status" v-model="form.marital_status" :class="selectClass">
                                            <option value="">Not set</option>
                                            <option v-for="opt in maritalStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                        </select>
                                        <InputError :message="form.errors.marital_status" />
                                    </div>
                                    <div class="grid gap-1.5 sm:col-span-2">
                                        <Label for="current_address">Current address</Label>
                                        <textarea id="current_address" v-model="form.current_address" rows="3" :class="textareaClass" />
                                        <InputError :message="form.errors.current_address" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Background collapsibles -->
                        <div class="space-y-3">

                            <!-- Family -->
                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <p class="text-sm font-semibold text-foreground">Family information</p>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-4 py-4">
                                            <div class="mb-3 flex justify-end">
                                                <Button type="button" variant="outline" size="sm" class="gap-2" @click="addFamilyMember">
                                                    <Plus class="size-3.5" /> Add family member
                                                </Button>
                                            </div>
                                            <div class="space-y-3">
                                                <div v-for="(member, i) in form.family_members" :key="i" class="rounded-lg border border-border p-3">
                                                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_relationship_${i}`">Relationship</Label>
                                                            <Input :id="`family_relationship_${i}`" v-model="member.relationship" placeholder="Spouse" />
                                                            <InputError :message="form.errors[`family_members.${i}.relationship`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_name_${i}`">Full name</Label>
                                                            <Input :id="`family_name_${i}`" v-model="member.full_name" placeholder="Member name" />
                                                            <InputError :message="form.errors[`family_members.${i}.full_name`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_birth_${i}`">Date of birth</Label>
                                                            <Input :id="`family_birth_${i}`" v-model="member.date_of_birth" type="date" />
                                                            <InputError :message="form.errors[`family_members.${i}.date_of_birth`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_nationality_${i}`">Nationality</Label>
                                                            <Input :id="`family_nationality_${i}`" v-model="member.nationality" placeholder="Nationality" />
                                                            <InputError :message="form.errors[`family_members.${i}.nationality`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_occupation_${i}`">Occupation</Label>
                                                            <Input :id="`family_occupation_${i}`" v-model="member.occupation" placeholder="Occupation" />
                                                            <InputError :message="form.errors[`family_members.${i}.occupation`]" />
                                                        </div>
                                                        <div class="flex items-end justify-between gap-3">
                                                            <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                                                <input v-model="member.is_accompanying" type="checkbox" class="size-4 rounded border-border" />
                                                                Accompanying
                                                            </label>
                                                            <Button type="button" variant="ghost" size="sm" @click="removeFamilyMember(i)">
                                                                Remove
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            <!-- Education -->
                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <p class="text-sm font-semibold text-foreground">Education</p>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-4 py-4">
                                            <div class="mb-3 flex justify-end">
                                                <Button type="button" variant="outline" size="sm" class="gap-2" @click="addEducationRecord">
                                                    <Plus class="size-3.5" /> Add education
                                                </Button>
                                            </div>
                                            <div class="space-y-3">
                                                <div v-for="(record, i) in form.education_history" :key="i" class="rounded-lg border border-border p-3">
                                                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`edu_institution_${i}`">Institution</Label>
                                                            <Input :id="`edu_institution_${i}`" v-model="record.institution" placeholder="University" />
                                                            <InputError :message="form.errors[`education_history.${i}.institution`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`edu_qualification_${i}`">Qualification</Label>
                                                            <Input :id="`edu_qualification_${i}`" v-model="record.qualification" placeholder="Bachelor's" />
                                                            <InputError :message="form.errors[`education_history.${i}.qualification`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`edu_field_${i}`">Field of study</Label>
                                                            <Input :id="`edu_field_${i}`" v-model="record.field_of_study" placeholder="Business" />
                                                            <InputError :message="form.errors[`education_history.${i}.field_of_study`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`edu_country_${i}`">Country</Label>
                                                            <Input :id="`edu_country_${i}`" v-model="record.country" placeholder="Country" />
                                                            <InputError :message="form.errors[`education_history.${i}.country`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`edu_start_${i}`">Start</Label>
                                                            <Input :id="`edu_start_${i}`" v-model="record.start_date" type="date" />
                                                            <InputError :message="form.errors[`education_history.${i}.start_date`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`edu_end_${i}`">End</Label>
                                                            <Input :id="`edu_end_${i}`" v-model="record.end_date" type="date" :disabled="record.is_current" />
                                                            <InputError :message="form.errors[`education_history.${i}.end_date`]" />
                                                        </div>
                                                        <div class="flex items-end justify-between gap-3 md:col-span-2 xl:col-span-3">
                                                            <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                                                <input v-model="record.is_current" type="checkbox" class="size-4 rounded border-border" />
                                                                Currently studying
                                                            </label>
                                                            <Button type="button" variant="ghost" size="sm" @click="removeEducationRecord(i)">
                                                                Remove
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            <!-- Work experience -->
                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <p class="text-sm font-semibold text-foreground">Work experience</p>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-4 py-4">
                                            <div class="mb-3 flex justify-end">
                                                <Button type="button" variant="outline" size="sm" class="gap-2" @click="addWorkExperience">
                                                    <Plus class="size-3.5" /> Add experience
                                                </Button>
                                            </div>
                                            <div class="space-y-3">
                                                <div v-for="(exp, i) in form.work_experiences" :key="i" class="rounded-lg border border-border p-3">
                                                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_employer_${i}`">Employer</Label>
                                                            <Input :id="`work_employer_${i}`" v-model="exp.employer" placeholder="Employer" />
                                                            <InputError :message="form.errors[`work_experiences.${i}.employer`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_title_${i}`">Role</Label>
                                                            <Input :id="`work_title_${i}`" v-model="exp.job_title" placeholder="Job title" />
                                                            <InputError :message="form.errors[`work_experiences.${i}.job_title`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_country_${i}`">Country</Label>
                                                            <Input :id="`work_country_${i}`" v-model="exp.country" placeholder="Country" />
                                                            <InputError :message="form.errors[`work_experiences.${i}.country`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_start_${i}`">Start</Label>
                                                            <Input :id="`work_start_${i}`" v-model="exp.start_date" type="date" />
                                                            <InputError :message="form.errors[`work_experiences.${i}.start_date`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_end_${i}`">End</Label>
                                                            <Input :id="`work_end_${i}`" v-model="exp.end_date" type="date" :disabled="exp.is_current" />
                                                            <InputError :message="form.errors[`work_experiences.${i}.end_date`]" />
                                                        </div>
                                                        <div class="flex items-end justify-between gap-3">
                                                            <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                                                <input v-model="exp.is_current" type="checkbox" class="size-4 rounded border-border" />
                                                                Current role
                                                            </label>
                                                            <Button type="button" variant="ghost" size="sm" @click="removeWorkExperience(i)">
                                                                Remove
                                                            </Button>
                                                        </div>
                                                        <div class="grid gap-1.5 md:col-span-2 xl:col-span-3">
                                                            <Label :for="`work_summary_${i}`">Summary</Label>
                                                            <textarea
                                                                :id="`work_summary_${i}`"
                                                                v-model="exp.summary"
                                                                rows="2"
                                                                :class="textareaClass"
                                                                placeholder="Role summary"
                                                            />
                                                            <InputError :message="form.errors[`work_experiences.${i}.summary`]" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>
                        </div>

                        <DialogFooter class="border-t border-border pt-4">
                            <Button type="button" variant="ghost" @click="isEditDialogOpen = false">Cancel</Button>
                            <Button :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : 'Save changes' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>

        </div>
    </AppLayout>
</template>
