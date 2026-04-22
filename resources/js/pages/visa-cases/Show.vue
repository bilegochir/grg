<script setup lang="ts">
import ActivityTimeline from '@/components/crm/ActivityTimeline.vue';
import AttachmentsPanel from '@/components/crm/AttachmentsPanel.vue';
import NotesPanel from '@/components/crm/NotesPanel.vue';
import VisaRequirementsChecklist from '@/components/crm/VisaRequirementsChecklist.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Dialog, DialogFooter, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ChevronDown, Pencil } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface VisaCaseDetail {
    id: number;
    client_id: number;
    assigned_user_id: null | number;
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

interface VisaCaseTask {
    id: number;
    title: string;
    status_label: string;
    priority_label: string;
    assignee_name: null | string;
    due_at: null | string;
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

interface TimelineItem {
    type: string;
    title: string;
    description: string;
    created_at: string;
    meta: Record<string, null | string>;
}

const institutionRequirementKey = (country: string, visaType: string) => `${country}::${visaType}`;

const props = defineProps<{
    visaCase: VisaCaseDetail;
    clients: Array<{ id: number; full_name: string }>;
    users: Array<{ id: number; name: string }>;
    requirementCountries: string[];
    institutionRequirements: Record<string, boolean>;
    visaTypesByCountry: Record<string, string[]>;
    statusOptions: Array<{ value: string; label: string }>;
    requirementStatusOptions: Array<{ value: string; label: string }>;
    requirementTemplate: null | RequirementTemplate;
    requirements: RequirementItem[];
    tasks: VisaCaseTask[];
    timeline: TimelineItem[];
}>();

const page = usePage<SharedData>();
const isEditDialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Visa Cases',
        href: '/visa-cases',
    },
    {
        title: props.visaCase.reference_code,
        href: `/visa-cases/${props.visaCase.id}`,
    },
];

const form = useForm({
    client_id: String(props.visaCase.client_id),
    assigned_user_id: props.visaCase.assigned_user_id ? String(props.visaCase.assigned_user_id) : '',
    visa_type: props.visaCase.visa_type,
    destination_country: props.visaCase.destination_country,
    institution_name: props.visaCase.institution_name ?? '',
    status: props.visaCase.status,
    submitted_at: props.visaCase.submitted_at ? props.visaCase.submitted_at.slice(0, 10) : '',
    decision_at: props.visaCase.decision_at ? props.visaCase.decision_at.slice(0, 10) : '',
});

const availableVisaTypes = computed(() => props.visaTypesByCountry[form.destination_country] ?? []);
const formRequiresInstitutionName = computed(
    () => props.institutionRequirements[institutionRequirementKey(form.destination_country, form.visa_type)] ?? false,
);
const caseRequiresInstitutionName = computed(
    () => props.institutionRequirements[institutionRequirementKey(props.visaCase.destination_country, props.visaCase.visa_type)] ?? false,
);
const completedRequirementsCount = computed(() => props.requirements.filter((item) => item.is_completed).length);
const attachmentsCount = computed(() => props.visaCase.attachments.length);
const heroMeta = computed(() => {
    const items = [
        { label: 'Client', value: props.visaCase.client_name || 'No client linked' },
        { label: 'Destination', value: props.visaCase.destination_country },
        { label: 'Visa', value: props.visaCase.visa_type },
        { label: 'Agent', value: props.visaCase.assignee_name || 'Unassigned' },
        { label: 'Requirements', value: `${completedRequirementsCount.value}/${props.requirements.length}` },
        { label: 'Tasks', value: `${props.tasks.length}` },
        { label: 'Files', value: `${attachmentsCount.value}` },
        { label: 'Submitted', value: props.visaCase.submitted_at ? formatDate(props.visaCase.submitted_at) : 'Not set' },
    ];

    if (caseRequiresInstitutionName.value) {
        items.splice(3, 0, {
            label: 'School',
            value: props.visaCase.institution_name || 'Not set',
        });
    }

    return items;
});

watch(
    () => form.destination_country,
    () => {
        if (availableVisaTypes.value.includes(form.visa_type)) {
            return;
        }

        form.visa_type = '';
    },
);

watch(
    () => form.visa_type,
    () => {
        if (!formRequiresInstitutionName.value) {
            form.institution_name = '';
        }
    },
);

const submit = () => {
    form.patch(route('visa-cases.update', props.visaCase.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
        },
    });
};

const formatDate = (value: null | string) =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Not set';

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
</script>

<template>
    <Head :title="visaCase.reference_code" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-3 p-3 md:p-4">
            <div v-if="page.props.flash.success" class="bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ page.props.flash.success }}
            </div>

            <section class="app-panel overflow-hidden">
                <div class="space-y-3 px-4 py-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-lg font-semibold tracking-tight text-slate-950 dark:text-slate-50">{{ visaCase.reference_code }}</h1>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="statusClasses(visaCase.status)">
                                    {{ visaCase.status_label }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" size="sm" class="gap-2" @click="isEditDialogOpen = true">
                                <Pencil class="size-4" />
                                Edit
                            </Button>
                            <Button as-child variant="ghost" size="sm" class="gap-2">
                                <Link href="/visa-cases">
                                    <ArrowLeft class="size-4" />
                                    Back
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <dl class="grid gap-x-4 gap-y-3 border-t border-border/70 pt-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div v-for="item in heroMeta" :key="item.label" class="min-w-0">
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ item.label }}</dt>
                            <dd class="mt-1 truncate text-sm text-foreground">{{ item.value }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <div class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_320px]">
                <div class="space-y-3">
                    <VisaRequirementsChecklist
                        :visa-case-id="visaCase.id"
                        :template="requirementTemplate"
                        :items="requirements"
                        :status-options="requirementStatusOptions"
                    />

                    <section class="app-panel overflow-hidden">
                        <div class="border-b border-border/70 px-4 py-4">
                            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Case activity</h2>
                        </div>

                        <div class="divide-y divide-border/70">
                            <Collapsible v-slot="{ open }" :default-open="true">
                                <div>
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">Notes</p>
                                            <p class="text-xs text-muted-foreground">{{ visaCase.notes.length }} entries</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border/70 px-4 py-4">
                                            <NotesPanel
                                                embedded
                                                title="Notes"
                                                route-name="visa-cases.notes.store"
                                                :route-parameter="visaCase.id"
                                                :notes="visaCase.notes"
                                            />
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            <Collapsible v-slot="{ open }" :default-open="false">
                                <div>
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">Timeline</p>
                                            <p class="text-xs text-muted-foreground">{{ timeline.length }} events</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border/70 px-4 py-4">
                                            <ActivityTimeline embedded title="Timeline" :items="timeline" />
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>
                        </div>
                    </section>
                </div>

                <div class="space-y-3 xl:sticky xl:top-20 xl:self-start">
                    <section class="app-panel overflow-hidden">
                        <div class="border-b border-border/70 px-4 py-4">
                            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Workspace</h2>
                        </div>

                        <div class="divide-y divide-border/70">
                            <Collapsible v-slot="{ open }" :default-open="tasks.length > 0 && tasks.length <= 3">
                                <div>
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">Tasks</p>
                                            <p class="text-xs text-muted-foreground">{{ tasks.length }} open items</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border/70 px-4 py-4">
                                            <div v-if="tasks.length === 0" class="text-sm text-muted-foreground">No tasks yet.</div>
                                            <div v-else class="space-y-2.5">
                                                <div
                                                    v-for="task in tasks"
                                                    :key="task.id"
                                                    class="border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0"
                                                >
                                                    <p class="font-medium text-foreground">{{ task.title }}</p>
                                                    <p class="mt-0.5 text-sm text-muted-foreground">
                                                        {{ task.status_label }} • {{ task.priority_label }} • {{ task.assignee_name || 'Unassigned' }}
                                                    </p>
                                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ formatDate(task.due_at) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            <Collapsible v-slot="{ open }" :default-open="attachmentsCount > 0 && attachmentsCount <= 2">
                                <div>
                                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">Attachments</p>
                                            <p class="text-xs text-muted-foreground">{{ attachmentsCount }} files</p>
                                        </div>
                                        <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div class="border-t border-border/70 px-4 py-4">
                                            <AttachmentsPanel
                                                embedded
                                                title="Attachments"
                                                route-name="visa-cases.attachments.store"
                                                :route-parameter="visaCase.id"
                                                :attachments="visaCase.attachments"
                                            />
                                        </div>
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>
                        </div>
                    </section>
                </div>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <DialogScrollContent class="max-w-4xl p-0">
                    <DialogHeader class="border-b border-border px-6 py-4">
                        <DialogTitle>Edit visa case</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4 px-6 py-5" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-1.5 md:col-span-2">
                                <Label for="client_id">Client</Label>
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                                        {{ client.full_name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.client_id" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="assigned_user_id">Assigned agent</Label>
                                <select
                                    id="assigned_user_id"
                                    v-model="form.assigned_user_id"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option value="">Unassigned</option>
                                    <option v-for="user in users" :key="user.id" :value="String(user.id)">
                                        {{ user.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.assigned_user_id" />
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
                                <Label for="destination_country">Destination</Label>
                                <select
                                    id="destination_country"
                                    v-model="form.destination_country"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option v-for="country in requirementCountries" :key="country" :value="country">
                                        {{ country }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.destination_country" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="visa_type">Visa type</Label>
                                <select
                                    id="visa_type"
                                    v-model="form.visa_type"
                                    :disabled="form.destination_country === ''"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <option value="">{{ form.destination_country === '' ? 'Select country first' : 'Select a visa' }}</option>
                                    <option v-for="visaType in availableVisaTypes" :key="visaType" :value="visaType">
                                        {{ visaType }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.visa_type" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="submitted_at">Submitted</Label>
                                <Input id="submitted_at" v-model="form.submitted_at" type="date" />
                                <InputError :message="form.errors.submitted_at" />
                            </div>

                            <div v-if="formRequiresInstitutionName" class="grid gap-1.5 md:col-span-2">
                                <Label for="institution_name">University or college</Label>
                                <Input id="institution_name" v-model="form.institution_name" placeholder="University of Melbourne" />
                                <InputError :message="form.errors.institution_name" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="decision_at">Decision</Label>
                                <Input id="decision_at" v-model="form.decision_at" type="date" />
                                <InputError :message="form.errors.decision_at" />
                            </div>
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
