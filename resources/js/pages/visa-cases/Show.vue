<script setup lang="ts">
import ActivityTimeline from '@/components/crm/ActivityTimeline.vue';
import AttachmentsPanel from '@/components/crm/AttachmentsPanel.vue';
import NotesPanel from '@/components/crm/NotesPanel.vue';
import VisaRequirementsChecklist from '@/components/crm/VisaRequirementsChecklist.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Hero from './partials/Hero.vue';
import Activity from './partials/Activity.vue';
import Workspace from './partials/Workspace.vue';
import Edit from './partials/Edit.vue';

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
        title: 'Visa cases',
        href: '/visa-cases',
    },
    {
        title: props.visaCase.reference_code,
        href: `/visa-cases/${props.visaCase.id}`,
    },
];

const completedRequirementsCount = computed(() => props.requirements.filter((item) => item.is_completed).length);
const attachmentsCount = computed(() => props.visaCase.attachments.length);

const openEditDialog = () => {
    isEditDialogOpen.value = true;
};
</script>

<template>
    <Head :title="visaCase.reference_code" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-5 p-3 md:p-4">
            <div
                v-if="page.props.flash.success"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300"
            >
                {{ page.props.flash.success }}
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_360px]">
                <div class="space-y-4">
                    <Hero
                        :visa-case="visaCase"
                        :requirements-count="requirements.length"
                        :completed-requirements-count="completedRequirementsCount"
                        :tasks-count="tasks.length"
                        :attachments-count="attachmentsCount"
                        :institution-requirements="institutionRequirements"
                        @edit="openEditDialog"
                    />

                    <VisaRequirementsChecklist
                        :visa-case-id="visaCase.id"
                        :template="requirementTemplate"
                        :items="requirements"
                        :status-options="requirementStatusOptions"
                    />

                    <Activity
                        :notes-count="visaCase.notes.length"
                        :timeline-count="timeline.length"
                    >
                        <template #notes>
                            <NotesPanel
                                embedded
                                title="Add note"
                                route-name="visa-cases.notes.store"
                                :route-parameter="visaCase.id"
                                :notes="visaCase.notes"
                                :show-list="false"
                            />
                        </template>

                        <template #timeline>
                            <ActivityTimeline embedded title="Timeline" :items="timeline" />
                        </template>
                    </Activity>
                </div>

                <Workspace
                    :tasks="tasks"
                    :attachments-count="attachmentsCount"
                    :visa-case-id="visaCase.id"
                >
                    <template #attachments>
                        <AttachmentsPanel
                            embedded
                            title="Attachments"
                            route-name="visa-cases.attachments.store"
                            :route-parameter="visaCase.id"
                            :attachments="visaCase.attachments"
                        />
                    </template>
                </Workspace>
            </div>

            <Edit
                v-model:open="isEditDialogOpen"
                :visa-case="visaCase"
                :clients="clients"
                :users="users"
                :requirement-countries="requirementCountries"
                :institution-requirements="institutionRequirements"
                :visa-types-by-country="visaTypesByCountry"
                :status-options="statusOptions"
            />
        </div>
    </AppLayout>
</template>
