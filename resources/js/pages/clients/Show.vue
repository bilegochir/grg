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
import { ArrowLeft, ChevronDown, Pencil, Plus } from 'lucide-vue-next';
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: '/clients',
    },
    {
        title: props.client.full_name,
        href: `/clients/${props.client.id}`,
    },
];

const blankFamilyMember = (): ClientFamilyMember => ({
    relationship: '',
    full_name: '',
    date_of_birth: '',
    nationality: '',
    occupation: '',
    is_accompanying: false,
});

const blankEducationRecord = (): ClientEducationRecord => ({
    institution: '',
    qualification: '',
    field_of_study: '',
    country: '',
    start_date: '',
    end_date: '',
    is_current: false,
});

const blankWorkExperience = (): ClientWorkExperience => ({
    employer: '',
    job_title: '',
    country: '',
    start_date: '',
    end_date: '',
    is_current: false,
    summary: '',
});

const form = useForm({
    full_name: props.client.full_name,
    email: props.client.email ?? '',
    phone: props.client.phone ?? '',
    date_of_birth: props.client.date_of_birth ?? '',
    passport_number: props.client.passport_number ?? '',
    passport_expiry_date: props.client.passport_expiry_date ?? '',
    marital_status: props.client.marital_status ?? '',
    occupation: props.client.occupation ?? '',
    current_address: props.client.current_address ?? '',
    nationality: props.client.nationality ?? '',
    destination_country: props.client.destination_country ?? '',
    lead_source: props.client.lead_source ?? '',
    status: props.client.status,
    family_members: props.client.family_members.length > 0 ? structuredClone(props.client.family_members) : [blankFamilyMember()],
    education_history: props.client.education_history.length > 0 ? structuredClone(props.client.education_history) : [blankEducationRecord()],
    work_experiences: props.client.work_experiences.length > 0 ? structuredClone(props.client.work_experiences) : [blankWorkExperience()],
});

const primaryItems = computed(() => [
    { label: 'Email', value: props.client.email || 'Not set' },
    { label: 'Phone', value: props.client.phone || 'Not set' },
    { label: 'Occupation', value: props.client.occupation || 'Not set' },
    { label: 'Destination', value: props.client.destination_country || 'Not set' },
    { label: 'Lead source', value: props.client.lead_source || 'Not set' },
    { label: 'Owner', value: props.client.owner_name || 'Unassigned' },
]);

const identityItems = computed(() => [
    { label: 'Date of birth', value: formatDateOnly(props.client.date_of_birth) },
    { label: 'Nationality', value: props.client.nationality || 'Not set' },
    { label: 'Passport', value: props.client.passport_number || 'Not set' },
    { label: 'Passport expiry', value: formatDateOnly(props.client.passport_expiry_date) },
    { label: 'Marital status', value: formatValue(props.client.marital_status) },
]);

const submit = () => {
    form.patch(route('clients.update', props.client.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
        },
    });
};

const addFamilyMember = () => {
    form.family_members.push(blankFamilyMember());
};

const removeFamilyMember = (index: number) => {
    if (form.family_members.length === 1) {
        form.family_members[0] = blankFamilyMember();
        return;
    }

    form.family_members.splice(index, 1);
};

const addEducationRecord = () => {
    form.education_history.push(blankEducationRecord());
};

const removeEducationRecord = (index: number) => {
    if (form.education_history.length === 1) {
        form.education_history[0] = blankEducationRecord();
        return;
    }

    form.education_history.splice(index, 1);
};

const addWorkExperience = () => {
    form.work_experiences.push(blankWorkExperience());
};

const removeWorkExperience = (index: number) => {
    if (form.work_experiences.length === 1) {
        form.work_experiences[0] = blankWorkExperience();
        return;
    }

    form.work_experiences.splice(index, 1);
};

const formatDate = (value: null | string) =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'No due date';

const formatDateOnly = (value: null | string) => (value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(value)) : 'Not set');

const formatDateRange = (start: null | string, end: null | string, isCurrent: boolean) => {
    if (!start && !end && !isCurrent) {
        return 'Dates not set';
    }

    const startLabel = start ? formatDateOnly(start) : 'Unknown start';
    const endLabel = isCurrent ? 'Present' : end ? formatDateOnly(end) : 'Unknown end';

    return `${startLabel} - ${endLabel}`;
};

const formatValue = (value: null | string) => {
    if (!value) {
        return 'Not set';
    }

    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};

const statusClasses = (status: string) =>
    ({
        lead: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
        qualified: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        closed: 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200',
    })[status] ?? 'bg-secondary text-secondary-foreground';
</script>

<template>
    <Head :title="client.full_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-3 p-3 md:p-4">
            <div v-if="page.props.flash.success" class="bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ page.props.flash.success }}
            </div>

            <section class="app-panel overflow-hidden">
                <div class="flex flex-col gap-3 px-4 py-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">{{ client.full_name }}</h1>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="statusClasses(client.status)">
                                {{ client.status_label }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                            <span>{{ client.owner_name || 'Unassigned owner' }}</span>
                            <span>{{ visaCases.length }} cases</span>
                            <span>{{ tasks.length }} tasks</span>
                            <span>{{ client.attachments.length }} files</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" class="gap-2" @click="isEditDialogOpen = true">
                            <Pencil class="size-4" />
                            Edit profile
                        </Button>
                        <Button as-child variant="ghost" class="gap-2">
                            <Link href="/clients">
                                <ArrowLeft class="size-4" />
                                Back
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>

            <div class="grid gap-3 xl:grid-cols-[minmax(0,1.3fr)_320px]">
                <div class="space-y-3">
                    <section class="app-panel p-3.5">
                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                            <div class="space-y-3">
                                <div>
                                    <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Overview</h2>
                                </div>

                                <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                                    <div v-for="item in primaryItems" :key="item.label" class="space-y-1">
                                        <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">{{ item.label }}</dt>
                                        <dd class="text-sm text-foreground">{{ item.value }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Identity</h2>
                                </div>

                                <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                                    <div v-for="item in identityItems" :key="item.label" class="space-y-1">
                                        <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">{{ item.label }}</dt>
                                        <dd class="text-sm text-foreground">{{ item.value }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div v-if="client.current_address" class="mt-4 border-t border-border pt-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Address</p>
                            <p class="mt-1 text-sm text-foreground">{{ client.current_address }}</p>
                        </div>
                    </section>

                    <section class="app-panel p-3.5">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Background</h2>
                        </div>

                        <div class="mt-4 space-y-2">
                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-3 py-2.5 text-left">
                                        <div>
                                            <p class="text-sm font-medium text-foreground">Family information</p>
                                            <p class="text-xs text-muted-foreground">{{ client.family_members.length }} records</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-3 py-3">
                                            <div v-if="client.family_members.length === 0" class="text-sm text-muted-foreground">
                                                No family information added.
                                            </div>
                                            <div v-else class="space-y-2.5">
                                                <div
                                                    v-for="(member, index) in client.family_members"
                                                    :key="`${member.full_name}-${index}`"
                                                    class="border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0"
                                                >
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="font-medium text-foreground">{{ member.full_name || 'Unnamed family member' }}</p>
                                                        <span class="text-sm text-muted-foreground">{{
                                                            member.relationship || 'Relationship not set'
                                                        }}</span>
                                                        <span
                                                            v-if="member.is_accompanying"
                                                            class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground"
                                                        >
                                                            Accompanying
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 text-sm text-muted-foreground">
                                                        {{ member.nationality || 'Nationality not set' }} •
                                                        {{ member.occupation || 'Occupation not set' }} •
                                                        {{ formatDateOnly(member.date_of_birth) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-3 py-2.5 text-left">
                                        <div>
                                            <p class="text-sm font-medium text-foreground">Education</p>
                                            <p class="text-xs text-muted-foreground">{{ client.education_history.length }} records</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-3 py-3">
                                            <div v-if="client.education_history.length === 0" class="text-sm text-muted-foreground">
                                                No education history added.
                                            </div>
                                            <div v-else class="space-y-2.5">
                                                <div
                                                    v-for="(record, index) in client.education_history"
                                                    :key="`${record.institution}-${index}`"
                                                    class="border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0"
                                                >
                                                    <p class="font-medium text-foreground">{{ record.institution || 'Institution not set' }}</p>
                                                    <p class="mt-1 text-sm text-muted-foreground">
                                                        {{ record.qualification || 'Qualification not set' }}
                                                        <span v-if="record.field_of_study"> • {{ record.field_of_study }}</span>
                                                        <span v-if="record.country"> • {{ record.country }}</span>
                                                    </p>
                                                    <p class="mt-1 text-xs text-muted-foreground">
                                                        {{ formatDateRange(record.start_date, record.end_date, record.is_current) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-3 py-2.5 text-left">
                                        <div>
                                            <p class="text-sm font-medium text-foreground">Work experience</p>
                                            <p class="text-xs text-muted-foreground">{{ client.work_experiences.length }} records</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-3 py-3">
                                            <div v-if="client.work_experiences.length === 0" class="text-sm text-muted-foreground">
                                                No work experience added.
                                            </div>
                                            <div v-else class="space-y-2.5">
                                                <div
                                                    v-for="(experience, index) in client.work_experiences"
                                                    :key="`${experience.employer}-${index}`"
                                                    class="border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0"
                                                >
                                                    <p class="font-medium text-foreground">
                                                        {{ experience.job_title || 'Role not set' }}
                                                        <span class="text-muted-foreground"> • {{ experience.employer || 'Employer not set' }}</span>
                                                    </p>
                                                    <p class="mt-1 text-sm text-muted-foreground">
                                                        {{ experience.country || 'Country not set' }} •
                                                        {{ formatDateRange(experience.start_date, experience.end_date, experience.is_current) }}
                                                    </p>
                                                    <p v-if="experience.summary" class="mt-1 text-sm text-muted-foreground">
                                                        {{ experience.summary }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>
                        </div>
                    </section>

                    <ActivityTimeline title="Timeline" :items="timeline" />
                </div>

                <div class="space-y-3">
                    <NotesPanel title="Notes" route-name="clients.notes.store" :route-parameter="client.id" :notes="client.notes" />

                    <AttachmentsPanel
                        title="Attachments"
                        route-name="clients.attachments.store"
                        :route-parameter="client.id"
                        :attachments="client.attachments"
                    />

                    <section class="app-panel p-3.5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Visa cases</h2>
                            <span class="text-sm text-muted-foreground">{{ visaCases.length }}</span>
                        </div>

                        <div class="mt-3">
                            <div v-if="visaCases.length === 0" class="text-sm text-muted-foreground">No visa cases yet.</div>
                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="visaCase in visaCases"
                                    :key="visaCase.id"
                                    class="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0"
                                >
                                    <div>
                                        <p class="font-medium text-foreground">{{ visaCase.reference_code }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ visaCase.visa_type }} • {{ visaCase.status_label }} • {{ visaCase.assignee_name || 'Unassigned' }}
                                        </p>
                                    </div>
                                    <Button as-child variant="ghost" size="sm">
                                        <Link :href="visaCase.show_url">Open</Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="app-panel p-3.5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Tasks</h2>
                            <span class="text-sm text-muted-foreground">{{ tasks.length }}</span>
                        </div>

                        <div class="mt-3">
                            <div v-if="tasks.length === 0" class="text-sm text-muted-foreground">No tasks yet.</div>
                            <div v-else class="space-y-2.5">
                                <div v-for="task in tasks" :key="task.id" class="border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0">
                                    <p class="font-medium text-foreground">{{ task.title }}</p>
                                    <p class="mt-0.5 text-sm text-muted-foreground">
                                        {{ task.status_label }} • {{ task.priority_label }} • {{ task.assignee_name || 'Unassigned' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ formatDate(task.due_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <DialogScrollContent class="max-w-5xl p-0">
                    <DialogHeader class="border-b border-border px-6 py-4">
                        <DialogTitle>Edit client profile</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-5 px-6 py-5" @submit.prevent="submit">
                        <section class="grid gap-4 lg:grid-cols-2">
                            <div class="grid gap-4 rounded-lg border border-border p-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Basic details</h3>
                                </div>

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
                                        <select
                                            id="status"
                                            v-model="form.status"
                                            class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                        >
                                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
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

                            <div class="grid gap-4 rounded-lg border border-border p-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Identity</h3>
                                </div>

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
                                        <select
                                            id="marital_status"
                                            v-model="form.marital_status"
                                            class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                        >
                                            <option value="">Not set</option>
                                            <option v-for="option in maritalStatusOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.marital_status" />
                                    </div>

                                    <div class="grid gap-1.5 sm:col-span-2">
                                        <Label for="current_address">Current address</Label>
                                        <textarea
                                            id="current_address"
                                            v-model="form.current_address"
                                            rows="3"
                                            class="min-h-20 rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                        />
                                        <InputError :message="form.errors.current_address" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="space-y-3">
                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">Family information</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-4 py-4">
                                            <div class="mb-3 flex justify-end">
                                                <Button type="button" variant="outline" size="sm" class="gap-2" @click="addFamilyMember">
                                                    <Plus class="size-4" />
                                                    Add family member
                                                </Button>
                                            </div>
                                            <div class="space-y-3">
                                                <div
                                                    v-for="(member, index) in form.family_members"
                                                    :key="index"
                                                    class="rounded-lg border border-border p-3"
                                                >
                                                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_relationship_${index}`">Relationship</Label>
                                                            <Input
                                                                :id="`family_relationship_${index}`"
                                                                v-model="member.relationship"
                                                                placeholder="Spouse"
                                                            />
                                                            <InputError :message="form.errors[`family_members.${index}.relationship`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_name_${index}`">Full name</Label>
                                                            <Input
                                                                :id="`family_name_${index}`"
                                                                v-model="member.full_name"
                                                                placeholder="Member name"
                                                            />
                                                            <InputError :message="form.errors[`family_members.${index}.full_name`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_birth_${index}`">Birth date</Label>
                                                            <Input :id="`family_birth_${index}`" v-model="member.date_of_birth" type="date" />
                                                            <InputError :message="form.errors[`family_members.${index}.date_of_birth`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_nationality_${index}`">Nationality</Label>
                                                            <Input
                                                                :id="`family_nationality_${index}`"
                                                                v-model="member.nationality"
                                                                placeholder="Nationality"
                                                            />
                                                            <InputError :message="form.errors[`family_members.${index}.nationality`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`family_occupation_${index}`">Occupation</Label>
                                                            <Input
                                                                :id="`family_occupation_${index}`"
                                                                v-model="member.occupation"
                                                                placeholder="Occupation"
                                                            />
                                                            <InputError :message="form.errors[`family_members.${index}.occupation`]" />
                                                        </div>
                                                        <div class="flex items-end justify-between gap-3">
                                                            <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                                                <input
                                                                    v-model="member.is_accompanying"
                                                                    type="checkbox"
                                                                    class="size-4 rounded border-border"
                                                                />
                                                                Accompanying
                                                            </label>
                                                            <Button type="button" variant="ghost" size="sm" @click="removeFamilyMember(index)"
                                                                >Remove</Button
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">Education</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-4 py-4">
                                            <div class="mb-3 flex justify-end">
                                                <Button type="button" variant="outline" size="sm" class="gap-2" @click="addEducationRecord">
                                                    <Plus class="size-4" />
                                                    Add education
                                                </Button>
                                            </div>
                                            <div class="space-y-3">
                                                <div
                                                    v-for="(record, index) in form.education_history"
                                                    :key="index"
                                                    class="rounded-lg border border-border p-3"
                                                >
                                                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`education_institution_${index}`">Institution</Label>
                                                            <Input
                                                                :id="`education_institution_${index}`"
                                                                v-model="record.institution"
                                                                placeholder="University"
                                                            />
                                                            <InputError :message="form.errors[`education_history.${index}.institution`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`education_qualification_${index}`">Qualification</Label>
                                                            <Input
                                                                :id="`education_qualification_${index}`"
                                                                v-model="record.qualification"
                                                                placeholder="Bachelor"
                                                            />
                                                            <InputError :message="form.errors[`education_history.${index}.qualification`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`education_field_${index}`">Field</Label>
                                                            <Input
                                                                :id="`education_field_${index}`"
                                                                v-model="record.field_of_study"
                                                                placeholder="Business"
                                                            />
                                                            <InputError :message="form.errors[`education_history.${index}.field_of_study`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`education_country_${index}`">Country</Label>
                                                            <Input
                                                                :id="`education_country_${index}`"
                                                                v-model="record.country"
                                                                placeholder="Country"
                                                            />
                                                            <InputError :message="form.errors[`education_history.${index}.country`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`education_start_${index}`">Start</Label>
                                                            <Input :id="`education_start_${index}`" v-model="record.start_date" type="date" />
                                                            <InputError :message="form.errors[`education_history.${index}.start_date`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`education_end_${index}`">End</Label>
                                                            <Input
                                                                :id="`education_end_${index}`"
                                                                v-model="record.end_date"
                                                                type="date"
                                                                :disabled="record.is_current"
                                                            />
                                                            <InputError :message="form.errors[`education_history.${index}.end_date`]" />
                                                        </div>
                                                        <div class="flex items-end justify-between gap-3 md:col-span-2 xl:col-span-3">
                                                            <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                                                <input
                                                                    v-model="record.is_current"
                                                                    type="checkbox"
                                                                    class="size-4 rounded border-border"
                                                                />
                                                                Current study
                                                            </label>
                                                            <Button type="button" variant="ghost" size="sm" @click="removeEducationRecord(index)"
                                                                >Remove</Button
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div class="rounded-lg border border-border">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">Work experience</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border px-4 py-4">
                                            <div class="mb-3 flex justify-end">
                                                <Button type="button" variant="outline" size="sm" class="gap-2" @click="addWorkExperience">
                                                    <Plus class="size-4" />
                                                    Add experience
                                                </Button>
                                            </div>
                                            <div class="space-y-3">
                                                <div
                                                    v-for="(experience, index) in form.work_experiences"
                                                    :key="index"
                                                    class="rounded-lg border border-border p-3"
                                                >
                                                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_employer_${index}`">Employer</Label>
                                                            <Input
                                                                :id="`work_employer_${index}`"
                                                                v-model="experience.employer"
                                                                placeholder="Employer"
                                                            />
                                                            <InputError :message="form.errors[`work_experiences.${index}.employer`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_title_${index}`">Role</Label>
                                                            <Input
                                                                :id="`work_title_${index}`"
                                                                v-model="experience.job_title"
                                                                placeholder="Job title"
                                                            />
                                                            <InputError :message="form.errors[`work_experiences.${index}.job_title`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_country_${index}`">Country</Label>
                                                            <Input :id="`work_country_${index}`" v-model="experience.country" placeholder="Country" />
                                                            <InputError :message="form.errors[`work_experiences.${index}.country`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_start_${index}`">Start</Label>
                                                            <Input :id="`work_start_${index}`" v-model="experience.start_date" type="date" />
                                                            <InputError :message="form.errors[`work_experiences.${index}.start_date`]" />
                                                        </div>
                                                        <div class="grid gap-1.5">
                                                            <Label :for="`work_end_${index}`">End</Label>
                                                            <Input
                                                                :id="`work_end_${index}`"
                                                                v-model="experience.end_date"
                                                                type="date"
                                                                :disabled="experience.is_current"
                                                            />
                                                            <InputError :message="form.errors[`work_experiences.${index}.end_date`]" />
                                                        </div>
                                                        <div class="flex items-end justify-between gap-3">
                                                            <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                                                <input
                                                                    v-model="experience.is_current"
                                                                    type="checkbox"
                                                                    class="size-4 rounded border-border"
                                                                />
                                                                Current role
                                                            </label>
                                                            <Button type="button" variant="ghost" size="sm" @click="removeWorkExperience(index)"
                                                                >Remove</Button
                                                            >
                                                        </div>
                                                        <div class="grid gap-1.5 md:col-span-2 xl:col-span-3">
                                                            <Label :for="`work_summary_${index}`">Summary</Label>
                                                            <textarea
                                                                :id="`work_summary_${index}`"
                                                                v-model="experience.summary"
                                                                rows="2"
                                                                class="min-h-16 rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                                                placeholder="Role summary"
                                                            />
                                                            <InputError :message="form.errors[`work_experiences.${index}.summary`]" />
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
                            <Button :disabled="form.processing">Save changes</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
