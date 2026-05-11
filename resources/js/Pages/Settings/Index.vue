<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SlideOver from '@/Components/SlideOver.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useLocale } from '@/lib/i18n';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    businessSetting: Object,
    countries: Array,
    visaTypes: Array,
    workflowStages: Array,
    taskTemplates: Array,
    documentTemplates: Array,
    communicationTemplates: Array,
    branches: Array,
    locales: Array,
    smsProviders: Array,
    appLocale: String,
});

const { t } = useLocale(props.appLocale ?? 'en');

const showCountry = ref(false);
const showVisaType = ref(false);
const showTemplate = ref(false);
const showMessageTemplate = ref(false);
const showWorkflowStage = ref(false);
const showTaskTemplate = ref(false);

const editingCountryId = ref(null);
const editingVisaTypeId = ref(null);
const editingTemplateId = ref(null);
const editingMessageTemplateId = ref(null);
const editingWorkflowStageId = ref(null);
const editingTaskTemplateId = ref(null);
const selectedVisaTypeId = ref(props.visaTypes[0]?.id ?? '');
const activeTab = ref('business');
const visaTypeSearch = ref('');
const visaTypeCountryFilter = ref('all');
const visaTypeServiceScopeFilter = ref('all');

const businessForm = useForm({
    business_name: props.businessSetting.business_name ?? '',
    contact_email: props.businessSetting.contact_email ?? '',
    contact_phone: props.businessSetting.contact_phone ?? '',
    contact_address: props.businessSetting.contact_address ?? '',
    default_locale: props.businessSetting.default_locale ?? 'en',
    sms_provider: props.businessSetting.sms_provider ?? 'log',
    sms_sender: props.businessSetting.sms_sender ?? '',
    multi_branch_enabled: props.businessSetting.multi_branch_enabled ?? false,
    logo: null,
});

const countryForm = useForm({
    name: '',
    slug: '',
    is_active: true,
});

const visaTypeForm = useForm({
    target_country_id: props.countries[0]?.id ?? '',
    name: '',
    code: '',
    official_subclass: '',
    slug: '',
    is_active: true,
    submission_sla_days: '',
    decision_sla_days: '',
    validity_months: '',
    stay_duration_days: '',
    entry_type: '',
    service_scope: '',
    priority_support: true,
    dependants_allowed: false,
    biometrics_required: false,
    interview_required: false,
    medical_required: false,
    police_clearance_required: false,
    financial_proof_required: false,
    checklist_intro: '',
    portal_guidance: '',
    official_reference_url: '',
    official_summary: '',
    official_requirements_input: '',
    notes: '',
});

const templateForm = useForm({
    visa_type_id: props.visaTypes[0]?.id ?? '',
    name: '',
    slug: '',
    description: '',
    category: '',
    client_instructions: '',
    agent_guidance: '',
    sample_hint: '',
    accepted_file_types_input: 'pdf, jpg, png',
    max_files: 1,
    max_file_size_mb: 20,
    due_days: '',
    is_repeatable: false,
    position: 1,
    is_required: true,
    tracks_expiry: false,
});

const messageTemplateForm = useForm({
    name: '',
    key: '',
    channel: 'email',
    locale: props.businessSetting.default_locale ?? 'en',
    subject: '',
    body: '',
    is_active: true,
});

const workflowStageForm = useForm({
    visa_type_id: props.visaTypes[0]?.id ?? '',
    name: '',
    slug: '',
    position: 1,
    color: 'blue',
    is_default: false,
    is_closed: false,
});

const taskTemplateForm = useForm({
    visa_type_id: props.visaTypes[0]?.id ?? '',
    visa_workflow_stage_id: '',
    name: '',
    slug: '',
    description: '',
    position: 1,
    due_days: '',
    is_required: true,
    is_client_visible: false,
});

const selectedVisaType = computed(() => props.visaTypes.find((visaType) => visaType.id === Number(selectedVisaTypeId.value)) ?? null);
const visaTypeCountries = computed(() => props.countries.filter((country) => country.visa_types_count > 0));
const filteredVisaTypes = computed(() => {
    const query = visaTypeSearch.value.trim().toLowerCase();

    return [...props.visaTypes]
        .filter((visaType) => {
            if (visaTypeCountryFilter.value !== 'all' && String(visaType.country.id) !== visaTypeCountryFilter.value) {
                return false;
            }

            if (visaTypeServiceScopeFilter.value !== 'all' && (visaType.service_scope ?? '') !== visaTypeServiceScopeFilter.value) {
                return false;
            }

            if (!query) {
                return true;
            }

            const haystack = [
                visaType.name,
                visaType.code,
                visaType.slug,
                visaType.official_subclass,
                visaType.country?.name,
                visaType.official_summary,
                ...(visaType.official_requirements ?? []),
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return haystack.includes(query);
        })
        .sort((left, right) => {
            const countryCompare = (left.country?.name ?? '').localeCompare(right.country?.name ?? '');

            if (countryCompare !== 0) {
                return countryCompare;
            }

            return (left.official_subclass ?? left.name).localeCompare(right.official_subclass ?? right.name, undefined, {
                numeric: true,
                sensitivity: 'base',
            });
        });
});
const filteredTemplates = computed(() => props.documentTemplates.filter((template) => {
    if (!selectedVisaTypeId.value) {
        return true;
    }

    return template.visa_type_id === Number(selectedVisaTypeId.value);
}));

const filteredWorkflowStages = computed(() => props.workflowStages.filter((stage) => {
    if (!selectedVisaTypeId.value) {
        return true;
    }

    return stage.visa_type_id === Number(selectedVisaTypeId.value);
}));

const filteredTaskTemplates = computed(() => props.taskTemplates.filter((template) => {
    if (!selectedVisaTypeId.value) {
        return true;
    }

    return template.visa_type_id === Number(selectedVisaTypeId.value);
}));

const resetCountryForm = () => {
    editingCountryId.value = null;
    countryForm.reset();
    countryForm.is_active = true;
};

const resetVisaTypeForm = () => {
    editingVisaTypeId.value = null;
    visaTypeForm.reset();
    visaTypeForm.target_country_id = props.countries[0]?.id ?? '';
    visaTypeForm.code = '';
    visaTypeForm.official_subclass = '';
    visaTypeForm.is_active = true;
    visaTypeForm.submission_sla_days = '';
    visaTypeForm.decision_sla_days = '';
    visaTypeForm.validity_months = '';
    visaTypeForm.stay_duration_days = '';
    visaTypeForm.entry_type = '';
    visaTypeForm.service_scope = '';
    visaTypeForm.priority_support = true;
    visaTypeForm.dependants_allowed = false;
    visaTypeForm.biometrics_required = false;
    visaTypeForm.interview_required = false;
    visaTypeForm.medical_required = false;
    visaTypeForm.police_clearance_required = false;
    visaTypeForm.financial_proof_required = false;
    visaTypeForm.checklist_intro = '';
    visaTypeForm.portal_guidance = '';
    visaTypeForm.official_reference_url = '';
    visaTypeForm.official_summary = '';
    visaTypeForm.official_requirements_input = '';
    visaTypeForm.notes = '';
};

const resetTemplateForm = () => {
    editingTemplateId.value = null;
    templateForm.reset();
    templateForm.visa_type_id = Number(selectedVisaTypeId.value) || props.visaTypes[0]?.id || '';
    templateForm.category = '';
    templateForm.client_instructions = '';
    templateForm.agent_guidance = '';
    templateForm.sample_hint = '';
    templateForm.accepted_file_types_input = 'pdf, jpg, png';
    templateForm.max_files = 1;
    templateForm.max_file_size_mb = 20;
    templateForm.due_days = '';
    templateForm.is_repeatable = false;
    templateForm.position = filteredTemplates.value.length + 1 || 1;
    templateForm.is_required = true;
    templateForm.tracks_expiry = false;
};

const resetMessageTemplateForm = () => {
    editingMessageTemplateId.value = null;
    messageTemplateForm.reset();
    messageTemplateForm.channel = 'email';
    messageTemplateForm.locale = props.businessSetting.default_locale ?? 'en';
    messageTemplateForm.is_active = true;
};

const resetWorkflowStageForm = () => {
    editingWorkflowStageId.value = null;
    workflowStageForm.reset();
    workflowStageForm.visa_type_id = Number(selectedVisaTypeId.value) || props.visaTypes[0]?.id || '';
    workflowStageForm.position = filteredWorkflowStages.value.length + 1 || 1;
    workflowStageForm.color = 'blue';
    workflowStageForm.is_default = false;
    workflowStageForm.is_closed = false;
};

const resetTaskTemplateForm = () => {
    editingTaskTemplateId.value = null;
    taskTemplateForm.reset();
    taskTemplateForm.visa_type_id = Number(selectedVisaTypeId.value) || props.visaTypes[0]?.id || '';
    taskTemplateForm.visa_workflow_stage_id = '';
    taskTemplateForm.position = filteredTaskTemplates.value.length + 1 || 1;
    taskTemplateForm.due_days = '';
    taskTemplateForm.is_required = true;
    taskTemplateForm.is_client_visible = false;
};

const openCountryCreate = () => {
    activeTab.value = 'destinations';
    resetCountryForm();
    showCountry.value = true;
};

const openCountryEdit = (country) => {
    activeTab.value = 'destinations';
    editingCountryId.value = country.id;
    countryForm.name = country.name;
    countryForm.slug = country.slug;
    countryForm.is_active = country.is_active;
    showCountry.value = true;
};

const openVisaTypeCreate = () => {
    activeTab.value = 'destinations';
    resetVisaTypeForm();
    showVisaType.value = true;
};

const openVisaTypeEdit = (visaType) => {
    activeTab.value = 'destinations';
    selectedVisaTypeId.value = visaType.id;
    editingVisaTypeId.value = visaType.id;
    visaTypeForm.target_country_id = visaType.country.id;
    visaTypeForm.name = visaType.name;
    visaTypeForm.code = visaType.code ?? '';
    visaTypeForm.official_subclass = visaType.official_subclass ?? '';
    visaTypeForm.slug = visaType.slug;
    visaTypeForm.is_active = visaType.is_active;
    visaTypeForm.submission_sla_days = visaType.submission_sla_days ?? '';
    visaTypeForm.decision_sla_days = visaType.decision_sla_days ?? '';
    visaTypeForm.validity_months = visaType.validity_months ?? '';
    visaTypeForm.stay_duration_days = visaType.stay_duration_days ?? '';
    visaTypeForm.entry_type = visaType.entry_type ?? '';
    visaTypeForm.service_scope = visaType.service_scope ?? '';
    visaTypeForm.priority_support = visaType.priority_support;
    visaTypeForm.dependants_allowed = visaType.dependants_allowed;
    visaTypeForm.biometrics_required = visaType.biometrics_required;
    visaTypeForm.interview_required = visaType.interview_required;
    visaTypeForm.medical_required = visaType.medical_required;
    visaTypeForm.police_clearance_required = visaType.police_clearance_required;
    visaTypeForm.financial_proof_required = visaType.financial_proof_required;
    visaTypeForm.checklist_intro = visaType.checklist_intro ?? '';
    visaTypeForm.portal_guidance = visaType.portal_guidance ?? '';
    visaTypeForm.official_reference_url = visaType.official_reference_url ?? '';
    visaTypeForm.official_summary = visaType.official_summary ?? '';
    visaTypeForm.official_requirements_input = (visaType.official_requirements ?? []).join('\n');
    visaTypeForm.notes = visaType.notes ?? '';
    showVisaType.value = true;
};

const openTemplateCreate = () => {
    activeTab.value = 'checklists';
    resetTemplateForm();
    showTemplate.value = true;
};

const openTemplateEdit = (template) => {
    activeTab.value = 'checklists';
    editingTemplateId.value = template.id;
    templateForm.visa_type_id = template.visa_type_id;
    templateForm.name = template.name;
    templateForm.slug = template.slug;
    templateForm.description = template.description ?? '';
    templateForm.category = template.category ?? '';
    templateForm.client_instructions = template.client_instructions ?? '';
    templateForm.agent_guidance = template.agent_guidance ?? '';
    templateForm.sample_hint = template.sample_hint ?? '';
    templateForm.accepted_file_types_input = (template.accepted_file_types ?? []).join(', ');
    templateForm.max_files = template.max_files ?? 1;
    templateForm.max_file_size_mb = template.max_file_size_mb ?? 20;
    templateForm.due_days = template.due_days ?? '';
    templateForm.is_repeatable = template.is_repeatable;
    templateForm.position = template.position;
    templateForm.is_required = template.is_required;
    templateForm.tracks_expiry = template.tracks_expiry;
    selectedVisaTypeId.value = template.visa_type_id;
    showTemplate.value = true;
};

const openMessageTemplateCreate = () => {
    activeTab.value = 'messaging';
    resetMessageTemplateForm();
    showMessageTemplate.value = true;
};

const openWorkflowStageCreate = () => {
    activeTab.value = 'workflow';
    resetWorkflowStageForm();
    showWorkflowStage.value = true;
};

const openWorkflowStageEdit = (stage) => {
    activeTab.value = 'workflow';
    editingWorkflowStageId.value = stage.id;
    workflowStageForm.visa_type_id = stage.visa_type_id;
    workflowStageForm.name = stage.name;
    workflowStageForm.slug = stage.slug;
    workflowStageForm.position = stage.position;
    workflowStageForm.color = stage.color;
    workflowStageForm.is_default = stage.is_default;
    workflowStageForm.is_closed = stage.is_closed;
    selectedVisaTypeId.value = stage.visa_type_id;
    showWorkflowStage.value = true;
};

const openTaskTemplateCreate = () => {
    activeTab.value = 'workflow';
    resetTaskTemplateForm();
    showTaskTemplate.value = true;
};

const openTaskTemplateEdit = (taskTemplate) => {
    activeTab.value = 'workflow';
    editingTaskTemplateId.value = taskTemplate.id;
    taskTemplateForm.visa_type_id = taskTemplate.visa_type_id;
    taskTemplateForm.visa_workflow_stage_id = taskTemplate.stage_id ?? '';
    taskTemplateForm.name = taskTemplate.name;
    taskTemplateForm.slug = taskTemplate.slug;
    taskTemplateForm.description = taskTemplate.description ?? '';
    taskTemplateForm.position = taskTemplate.position;
    taskTemplateForm.due_days = taskTemplate.due_days ?? '';
    taskTemplateForm.is_required = taskTemplate.is_required;
    taskTemplateForm.is_client_visible = taskTemplate.is_client_visible;
    selectedVisaTypeId.value = taskTemplate.visa_type_id;
    showTaskTemplate.value = true;
};

const openMessageTemplateEdit = (template) => {
    activeTab.value = 'messaging';
    editingMessageTemplateId.value = template.id;
    messageTemplateForm.name = template.name;
    messageTemplateForm.key = template.key;
    messageTemplateForm.channel = template.channel;
    messageTemplateForm.locale = template.locale;
    messageTemplateForm.subject = template.subject ?? '';
    messageTemplateForm.body = template.body;
    messageTemplateForm.is_active = template.is_active;
    showMessageTemplate.value = true;
};

const saveBusinessSettings = () => {
    businessForm
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post(route('settings.business.update'), {
            forceFormData: true,
            preserveScroll: true,
        });
};

const saveCountry = () => {
    if (editingCountryId.value) {
        countryForm.patch(route('settings.countries.update', editingCountryId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showCountry.value = false;
                resetCountryForm();
            },
        });
        return;
    }

    countryForm.post(route('settings.countries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCountry.value = false;
            resetCountryForm();
        },
    });
};

const saveVisaType = () => {
    visaTypeForm.transform((data) => ({
        ...data,
        official_requirements: data.official_requirements_input
            .split('\n')
            .map((value) => value.trim())
            .filter(Boolean),
    }));

    if (editingVisaTypeId.value) {
        visaTypeForm.patch(route('settings.visa-types.update', editingVisaTypeId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showVisaType.value = false;
                resetVisaTypeForm();
            },
        });
        return;
    }

    visaTypeForm.post(route('settings.visa-types.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showVisaType.value = false;
            resetVisaTypeForm();
        },
    });
};

const saveTemplate = () => {
    templateForm.transform((data) => ({
        ...data,
        accepted_file_types: data.accepted_file_types_input
            .split(',')
            .map((value) => value.trim())
            .filter(Boolean),
        due_days: data.due_days === '' ? null : data.due_days,
    }));

    if (editingTemplateId.value) {
        templateForm.patch(route('settings.document-templates.update', editingTemplateId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showTemplate.value = false;
                resetTemplateForm();
            },
        });
        return;
    }

    templateForm.post(route('settings.document-templates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showTemplate.value = false;
            resetTemplateForm();
        },
    });
};

const saveMessageTemplate = () => {
    if (editingMessageTemplateId.value) {
        messageTemplateForm.patch(route('settings.communication-templates.update', editingMessageTemplateId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showMessageTemplate.value = false;
                resetMessageTemplateForm();
            },
        });
        return;
    }

    messageTemplateForm.post(route('settings.communication-templates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showMessageTemplate.value = false;
            resetMessageTemplateForm();
        },
    });
};

const saveWorkflowStage = () => {
    if (editingWorkflowStageId.value) {
        workflowStageForm.patch(route('settings.workflow-stages.update', editingWorkflowStageId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showWorkflowStage.value = false;
                resetWorkflowStageForm();
            },
        });
        return;
    }

    workflowStageForm.post(route('settings.workflow-stages.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showWorkflowStage.value = false;
            resetWorkflowStageForm();
        },
    });
};

const saveTaskTemplate = () => {
    taskTemplateForm.transform((data) => ({
        ...data,
        due_days: data.due_days === '' ? null : data.due_days,
        visa_workflow_stage_id: data.visa_workflow_stage_id || null,
    }));

    if (editingTaskTemplateId.value) {
        taskTemplateForm.patch(route('settings.task-templates.update', editingTaskTemplateId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showTaskTemplate.value = false;
                resetTaskTemplateForm();
            },
        });
        return;
    }

    taskTemplateForm.post(route('settings.task-templates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showTaskTemplate.value = false;
            resetTaskTemplateForm();
        },
    });
};

const destroyResource = (routeName, resourceId) => {
    router.delete(route(routeName, resourceId), {
        preserveScroll: true,
    });
};

const templateRequirementSummary = (template) => {
    const parts = [];

    if (template.max_files > 1) {
        parts.push(`Up to ${template.max_files} files`);
    } else {
        parts.push('Single file');
    }

    parts.push(`${template.max_file_size_mb}MB max`);

    if ((template.accepted_file_types ?? []).length) {
        parts.push(template.accepted_file_types.map((type) => type.toUpperCase()).join(', '));
    }

    return parts.join(' • ');
};

const visaTypeScopeLabel = (serviceScope) => {
    const labels = {
        new_application: 'New application',
        renewal: 'Renewal',
        extension: 'Extension',
        multi_step_case: 'Multi-step case',
    };

    return labels[serviceScope] ?? 'General';
};

const openVisaTypeWorkflow = (visaType) => {
    selectedVisaTypeId.value = visaType.id;
    activeTab.value = 'workflow';
};

const openVisaTypeChecklist = (visaType) => {
    selectedVisaTypeId.value = visaType.id;
    activeTab.value = 'checklists';
};

const settingGroups = [
    {
        label: 'Workspace',
        items: [
            {
                key: 'business',
                label: 'General',
                icon: 'sparkle',
                description: 'Brand and contact',
            },
            {
                key: 'destinations',
                label: 'Destinations',
                icon: 'map',
                description: 'Countries and visa types',
            },
        ],
    },
    {
        label: 'Automations',
        items: [
            {
                key: 'workflow',
                label: 'Workflow',
                icon: 'tasks',
                description: 'Stages and tasks',
            },
            {
                key: 'checklists',
                label: 'Checklists',
                icon: 'document',
                description: 'Requirements',
            },
            {
                key: 'messaging',
                label: 'Messaging',
                icon: 'bell',
                description: 'Templates',
            },
        ],
    },
];
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div class="mb-6">
                <h1 class="text-[24px] font-semibold text-slate-900 tracking-tight">Settings</h1>
            </div>
        </template>

        <div class="flex flex-col gap-12 lg:flex-row lg:items-start">
            <aside class="w-full lg:w-56 lg:shrink-0">
                <nav class="space-y-6">
                    <div v-for="group in settingGroups" :key="group.label">
                        <p class="px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            {{ group.label }}
                        </p>
                        <div class="mt-2 space-y-0.5">
                            <button
                                v-for="tab in group.items"
                                :key="tab.key"
                                type="button"
                                class="flex w-full items-center gap-2.5 rounded-md px-3 py-1.5 text-left transition-all duration-200"
                                :class="activeTab === tab.key 
                                    ? 'bg-slate-200/60 text-slate-900 font-bold' 
                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                                @click="activeTab = tab.key"
                            >
                                <AppIcon :name="tab.icon" :size="14" :class="activeTab === tab.key ? 'text-brand-primary' : 'text-slate-400'" />
                                <span class="text-[13px]">{{ tab.label }}</span>
                            </button>
                        </div>
                    </div>
                </nav>

                <div class="mt-12 px-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Help & Support</p>
                    <div class="mt-4 space-y-3">
                        <a href="#" class="block text-[13px] text-slate-500 hover:text-slate-900 transition-colors">Documentation</a>
                        <a href="#" class="block text-[13px] text-slate-500 hover:text-slate-900 transition-colors">API Reference</a>
                        <a href="#" class="block text-[13px] text-slate-500 hover:text-slate-900 transition-colors">Contact Support</a>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">

            <div v-if="activeTab === 'business'" class="max-w-4xl">
                <div class="mb-10">
                    <h2 class="text-[20px] font-semibold text-slate-900">General Settings</h2>
                    <p class="mt-1 text-[13px] text-slate-500">Manage your business profile, contact information, and localization preferences.</p>
                </div>
                <form class="space-y-6" @submit.prevent="saveBusinessSettings">
                    <div class="grid gap-5 lg:grid-cols-[1.1fr,0.9fr]">
                        <div class="space-y-5">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="business_name" value="Business name" />
                                    <input id="business_name" v-model="businessForm.business_name" class="ui-input" />
                                    <InputError :message="businessForm.errors.business_name" />
                                </div>
                                <div>
                                    <InputLabel for="default_locale" value="Default locale" />
                                    <select id="default_locale" v-model="businessForm.default_locale" class="ui-select">
                                        <option v-for="locale in locales" :key="locale.value" :value="locale.value">
                                            {{ locale.label }}
                                        </option>
                                    </select>
                                    <InputError :message="businessForm.errors.default_locale" />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="contact_email" value="Contact email" />
                                    <input id="contact_email" v-model="businessForm.contact_email" type="email" class="ui-input" />
                                    <InputError :message="businessForm.errors.contact_email" />
                                </div>
                                <div>
                                    <InputLabel for="contact_phone" value="Contact phone" />
                                    <input id="contact_phone" v-model="businessForm.contact_phone" class="ui-input" />
                                    <InputError :message="businessForm.errors.contact_phone" />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="sms_provider" value="SMS provider" />
                                    <select id="sms_provider" v-model="businessForm.sms_provider" class="ui-select">
                                        <option v-for="provider in smsProviders" :key="provider.value" :value="provider.value">
                                            {{ provider.label }}
                                        </option>
                                    </select>
                                    <InputError :message="businessForm.errors.sms_provider" />
                                </div>
                                <div>
                                    <InputLabel for="sms_sender" value="SMS sender label" />
                                    <input id="sms_sender" v-model="businessForm.sms_sender" class="ui-input" placeholder="Agency" />
                                    <InputError :message="businessForm.errors.sms_sender" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="contact_address" value="Office address" />
                                <textarea id="contact_address" v-model="businessForm.contact_address" rows="3" class="ui-textarea"></textarea>
                                <InputError :message="businessForm.errors.contact_address" />
                            </div>
                        </div>

                        <div class="space-y-5 rounded-2xl border border-brand-border bg-brand-neutral p-5">
                            <div>
                                <InputLabel for="logo" value="Business logo" />
                                <div class="mt-3 flex items-center gap-4">
                                    <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl border border-brand-border bg-white">
                                        <img
                                            v-if="businessSetting.logo_url"
                                            :src="businessSetting.logo_url"
                                            alt="Business logo"
                                            class="h-full w-full object-cover"
                                        />
                                        <AppIcon v-else name="sparkle" :size="22" class="text-brand-primary" />
                                    </div>
                                    <input
                                        id="logo"
                                        type="file"
                                        accept="image/*"
                                        class="block w-full text-sm text-brand-muted file:mr-4 file:rounded-md file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-text"
                                        @input="businessForm.logo = $event.target.files[0]"
                                    />
                                </div>
                                <InputError :message="businessForm.errors.logo" />
                            </div>

                            <label class="flex items-start gap-3 rounded-xl border border-brand-border bg-white px-4 py-4">
                                <input v-model="businessForm.multi_branch_enabled" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                                <div>
                                    <p class="text-sm font-medium text-brand-text">Prepare for multi-branch support</p>
                                    <p class="mt-1 text-sm leading-6 text-brand-muted">
                                        The data model is ready for branch expansion later. Turn this on when you want the team to start planning branch-aware workflows.
                                    </p>
                                </div>
                            </label>

                            <div class="rounded-xl border border-dashed border-brand-border bg-slate-50/50 px-4 py-4">
                                <p class="text-sm font-medium text-brand-text">Localization-ready Vue structure</p>
                                <p class="mt-1 text-sm leading-6 text-brand-muted">
                                    This settings workspace already reads from a shared translation map, so you can expand labels and microcopy without refactoring page structure later.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end border-t border-slate-100 pt-6">
                        <PrimaryButton :loading="businessForm.processing">Save changes</PrimaryButton>
                    </div>
                </form>
            </div>

            <div v-if="activeTab === 'destinations'" class="max-w-4xl space-y-12">
                <div>
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-slate-900">Target Countries</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Define the countries where you provide visa services.</p>
                        </div>
                        <PrimaryButton icon="plus" @click="openCountryCreate">Add country</PrimaryButton>
                    </div>

                    <div class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white shadow-card">
                        <div v-for="country in countries" :key="country.id" class="flex items-center justify-between gap-4 px-4 py-3.5 transition hover:bg-slate-50/50">
                            <div>
                                <div class="flex items-center gap-3">
                                    <p class="text-[14px] font-bold text-slate-900">{{ country.name }}</p>
                                    <span :class="country.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                                        {{ country.is_active ? 'Live' : 'Paused' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-[12px] text-slate-500">{{ country.slug }} • {{ country.visa_types_count }} visa types</p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" class="ui-button-ghost h-8" @click="openCountryEdit(country)">Edit</button>
                                <button type="button" class="ui-button-danger h-8" @click="destroyResource('settings.countries.destroy', country.id)">Remove</button>
                            </div>
                        </div>

                        <div v-if="!countries.length" class="px-6 py-10 text-center">
                            <p class="text-[13px] text-slate-500">No countries defined yet.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-slate-900">Visa Types</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Manage specific visa categories and their requirements by country.</p>
                        </div>
                        <PrimaryButton icon="plus" @click="openVisaTypeCreate">Add visa type</PrimaryButton>
                    </div>

                    <div class="mb-5 rounded-lg border border-slate-200 bg-white p-4 shadow-card">
                        <div class="grid gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
                            <div>
                                <InputLabel for="visa_type_search" value="Search visas" />
                                <input id="visa_type_search" v-model="visaTypeSearch" class="ui-input" placeholder="Search by name, subclass, code, country, or requirement" />
                            </div>
                            <div>
                                <InputLabel for="visa_type_country_filter" value="Country" />
                                <select id="visa_type_country_filter" v-model="visaTypeCountryFilter" class="ui-select">
                                    <option value="all">All countries</option>
                                    <option v-for="country in visaTypeCountries" :key="country.id" :value="String(country.id)">
                                        {{ country.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="visa_type_scope_filter" value="Service scope" />
                                <select id="visa_type_scope_filter" v-model="visaTypeServiceScopeFilter" class="ui-select">
                                    <option value="all">All scopes</option>
                                    <option value="new_application">New application</option>
                                    <option value="renewal">Renewal</option>
                                    <option value="extension">Extension</option>
                                    <option value="multi_step_case">Multi-step case</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-[12px] text-slate-500">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">{{ filteredVisaTypes.length }} visas shown</span>
                            <button
                                v-if="visaTypeSearch || visaTypeCountryFilter !== 'all' || visaTypeServiceScopeFilter !== 'all'"
                                type="button"
                                class="ui-button-ghost h-8"
                                @click="visaTypeSearch = ''; visaTypeCountryFilter = 'all'; visaTypeServiceScopeFilter = 'all'"
                            >
                                Reset filters
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="visaType in filteredVisaTypes"
                            :key="visaType.id"
                            class="rounded-lg border border-slate-200 bg-white p-4 shadow-card transition hover:border-slate-300"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-[14px] font-bold text-slate-900">{{ visaType.name }}</p>
                                        <span :class="visaType.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                                            {{ visaType.is_active ? 'Live' : 'Paused' }}
                                        </span>
                                        <span v-if="visaType.official_subclass" class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">Subclass {{ visaType.official_subclass }}</span>
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ visaType.country.name }}</span>
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ visaTypeScopeLabel(visaType.service_scope) }}</span>
                                    </div>
                                    <p v-if="visaType.official_summary" class="mt-2 text-[12px] leading-5 text-slate-600">
                                        {{ visaType.official_summary }}
                                    </p>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <span v-if="visaType.code" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ visaType.code }}</span>
                                        <span v-if="visaType.submission_sla_days" class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-600">{{ visaType.submission_sla_days }}d submission SLA</span>
                                        <span v-if="visaType.decision_sla_days" class="rounded bg-violet-50 px-1.5 py-0.5 text-[10px] font-bold text-violet-700">{{ visaType.decision_sla_days }}d decision SLA</span>
                                        <span v-if="visaType.official_requirements?.length" class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">{{ visaType.official_requirements.length }} requirements</span>
                                    </div>
                                    <div v-if="visaType.official_requirements?.length" class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="requirement in visaType.official_requirements.slice(0, 3)"
                                            :key="requirement"
                                            class="rounded-full bg-slate-50 px-2.5 py-1 text-[11px] leading-5 text-slate-600"
                                        >
                                            {{ requirement }}
                                        </span>
                                        <span v-if="visaType.official_requirements.length > 3" class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                                            +{{ visaType.official_requirements.length - 3 }} more
                                        </span>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button type="button" class="ui-button-ghost h-8" @click.stop="openVisaTypeEdit(visaType)">Edit</button>
                                        <button type="button" class="ui-button-ghost h-8" @click.stop="openVisaTypeWorkflow(visaType)">Workflow</button>
                                        <button type="button" class="ui-button-ghost h-8" @click.stop="openVisaTypeChecklist(visaType)">Checklist</button>
                                        <a
                                            v-if="visaType.official_reference_url"
                                            :href="visaType.official_reference_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="ui-button-ghost inline-flex h-8 items-center"
                                        >
                                            Official page
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 border-t border-slate-100 pt-3 lg:border-t-0 lg:pt-0">
                                    <button type="button" class="ui-button-danger h-8" @click.stop="destroyResource('settings.visa-types.destroy', visaType.id)">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!filteredVisaTypes.length" class="rounded-lg border border-dashed border-slate-200 p-12 text-center">
                        <p class="text-[14px] font-medium text-slate-600">No visa types match your current filters.</p>
                        <p class="mt-1 text-[13px] text-slate-500">Try a broader search or reset the country and scope filters.</p>
                    </div>

                    <div v-if="!visaTypes.length" class="rounded-lg border border-dashed border-slate-200 p-12 text-center">
                        <p class="text-[14px] font-medium text-slate-600">No visa types configured.</p>
                        <p class="mt-1 text-[13px] text-slate-500">Start by adding a visa type for your active countries.</p>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'workflow'" class="max-w-4xl space-y-12">
                <div>
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-slate-900">Workflow Stages</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Define the operational path for cases of this visa type.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select v-model="selectedVisaTypeId" class="ui-select h-8 min-w-[200px]">
                                <option v-for="visaType in visaTypes" :key="visaType.id" :value="visaType.id">
                                    {{ visaType.country.name }} • {{ visaType.name }}
                                </option>
                            </select>
                            <PrimaryButton icon="plus" @click="openWorkflowStageCreate">Add stage</PrimaryButton>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white shadow-card overflow-hidden">
                        <div v-for="stage in filteredWorkflowStages" :key="stage.id" class="flex items-start justify-between gap-4 px-4 py-4 transition hover:bg-slate-50/50">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[14px] font-bold text-slate-900">{{ stage.name }}</p>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Step {{ stage.position }}</span>
                                    <span v-if="stage.is_default" class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-600 uppercase tracking-wider">Default</span>
                                    <span v-if="stage.is_closed" class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Closed</span>
                                </div>
                                <p class="mt-1 text-[12px] text-slate-500">{{ stage.task_templates_count }} task templates</p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" class="ui-button-ghost h-8" @click="openWorkflowStageEdit(stage)">Edit</button>
                                <button type="button" class="ui-button-danger h-8" @click="destroyResource('settings.workflow-stages.destroy', stage.id)">Remove</button>
                            </div>
                        </div>

                        <div v-if="!filteredWorkflowStages.length" class="px-6 py-12 text-center">
                            <p class="text-[13px] text-slate-500">No workflow stages yet for this visa type.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-slate-900">Task Templates</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Automate internal to-dos when a case enters a specific stage.</p>
                        </div>
                        <PrimaryButton icon="plus" @click="openTaskTemplateCreate">Add task</PrimaryButton>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div v-for="taskTemplate in filteredTaskTemplates" :key="taskTemplate.id" class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-card transition hover:border-slate-300">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-[14px] font-bold text-slate-900">{{ taskTemplate.name }}</p>
                                    <span v-if="taskTemplate.is_required" class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-600 uppercase tracking-wider">Required</span>
                                </div>
                                <p class="mt-1 text-[12px] text-slate-500">{{ taskTemplate.description || 'No description.' }}</p>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span v-if="taskTemplate.stage_name" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600 tracking-wider uppercase">{{ taskTemplate.stage_name }}</span>
                                    <span v-if="taskTemplate.due_days !== null" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600 tracking-wider uppercase">Due {{ taskTemplate.due_days }}d</span>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center gap-1.5 border-t border-slate-50 pt-3">
                                <button type="button" class="ui-button-ghost flex-1 h-8" @click="openTaskTemplateEdit(taskTemplate)">Edit</button>
                                <button type="button" class="ui-button-danger flex-1 h-8" @click="destroyResource('settings.task-templates.destroy', taskTemplate.id)">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'checklists'" class="max-w-4xl">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-[20px] font-semibold text-slate-900">Document Requirements</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Configure the checklist of documents required for this visa type.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select v-model="selectedVisaTypeId" class="ui-select h-8 min-w-[200px]">
                            <option v-for="visaType in visaTypes" :key="visaType.id" :value="visaType.id">
                                {{ visaType.country.name }} • {{ visaType.name }}
                            </option>
                        </select>
                        <PrimaryButton icon="plus" @click="openTemplateCreate">Add requirement</PrimaryButton>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white shadow-card overflow-hidden">
                    <div v-for="template in filteredTemplates" :key="template.id" class="flex items-start justify-between gap-4 px-4 py-5 transition hover:bg-slate-50/50">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[14px] font-bold text-slate-900">{{ template.name }}</p>
                                <span v-if="template.is_required" class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-600 uppercase tracking-wider">Required</span>
                                <span v-if="template.tracks_expiry" class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase tracking-wider">Expiry</span>
                                <span v-if="template.is_repeatable" class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Repeat</span>
                            </div>
                            <p class="mt-1 text-[12px] text-slate-500 leading-relaxed">{{ template.description || 'No description.' }}</p>
                            <p class="mt-2 text-[11px] font-semibold text-slate-400 uppercase tracking-widest">{{ templateRequirementSummary(template) }}</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" class="ui-button-ghost h-8" @click="openTemplateEdit(template)">Edit</button>
                            <button type="button" class="ui-button-danger h-8" @click="destroyResource('settings.document-templates.destroy', template.id)">Remove</button>
                        </div>
                    </div>

                    <div v-if="!filteredTemplates.length" class="px-6 py-12 text-center">
                        <p class="text-[13px] text-slate-500">No document requirements defined for this visa type.</p>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'messaging'" class="max-w-4xl">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-[20px] font-semibold text-slate-900">Communication Templates</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Create reusable message templates for automated and manual communications.</p>
                    </div>
                    <PrimaryButton icon="plus" @click="openMessageTemplateCreate">Add template</PrimaryButton>
                </div>

                <div class="grid gap-4">
                    <div v-for="template in communicationTemplates" :key="template.id" class="group flex flex-col rounded-lg border border-slate-200 bg-white p-4 shadow-card transition hover:border-slate-300">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[14px] font-bold text-slate-900">{{ template.name }}</p>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ template.channel }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ template.locale }}</span>
                                    <span v-if="!template.is_active" class="rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-bold text-red-600 uppercase tracking-wider">Inactive</span>
                                </div>
                                <p class="mt-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ template.key }}</p>
                                <p v-if="template.subject" class="mt-3 text-[13px] font-bold text-slate-800">Subject: {{ template.subject }}</p>
                                <p class="mt-2 text-[12px] leading-relaxed text-slate-500 line-clamp-2 group-hover:line-clamp-none transition-all duration-300">{{ template.body }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" class="ui-button-ghost h-8" @click="openMessageTemplateEdit(template)">Edit</button>
                                <button type="button" class="ui-button-danger h-8" @click="destroyResource('settings.communication-templates.destroy', template.id)">Remove</button>
                            </div>
                        </div>
                    </div>

                    <div v-if="!communicationTemplates.length" class="rounded-lg border border-dashed border-slate-200 p-12 text-center">
                        <p class="text-[14px] font-medium text-slate-600">No message templates found.</p>
                        <p class="mt-1 text-[13px] text-slate-500">Templates help you send consistent messages across all case interactions.</p>
                    </div>
                </div>
            </div>

            </div>
        </div>

        <SlideOver :show="showCountry" width="wide" @close="showCountry = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Country</p>
                            <h2 class="mt-2 text-2xl">{{ editingCountryId ? 'Edit target country' : 'Add target country' }}</h2>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showCountry = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveCountry">
                    <div>
                        <InputLabel for="country_name" value="Country name" />
                        <input id="country_name" v-model="countryForm.name" class="ui-input" />
                        <InputError :message="countryForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="country_slug" value="Slug" />
                        <input id="country_slug" v-model="countryForm.slug" class="ui-input" placeholder="auto-generated if left blank" />
                        <InputError :message="countryForm.errors.slug" />
                    </div>
                    <label class="flex items-center gap-3">
                        <input v-model="countryForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                        <span class="text-sm text-brand-text">Country is active for new work</span>
                    </label>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-brand-border bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showCountry = false">Never mind</button>
                        <PrimaryButton :loading="countryForm.processing">{{ editingCountryId ? 'Save country' : 'Add country' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>

        <SlideOver :show="showVisaType" width="wide" @close="showVisaType = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Visa type</p>
                            <h2 class="mt-2 text-2xl">{{ editingVisaTypeId ? 'Edit visa type' : 'Add visa type' }}</h2>
                            <p class="mt-2 text-sm text-brand-muted">Each new visa type automatically gets a default workflow so your case team can start using it right away.</p>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showVisaType = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveVisaType">
                    <div class="grid gap-5 xl:grid-cols-2">
                        <div>
                            <InputLabel for="visa_type_country" value="Country" />
                            <select id="visa_type_country" v-model="visaTypeForm.target_country_id" class="ui-select">
                                <option value="">Choose a country</option>
                                <option v-for="country in countries" :key="country.id" :value="country.id">
                                    {{ country.name }}
                                </option>
                            </select>
                            <InputError :message="visaTypeForm.errors.target_country_id" />
                        </div>
                        <div>
                            <InputLabel for="visa_type_name" value="Visa type name" />
                            <input id="visa_type_name" v-model="visaTypeForm.name" class="ui-input" />
                            <InputError :message="visaTypeForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="visa_type_code" value="Short code" />
                            <input id="visa_type_code" v-model="visaTypeForm.code" class="ui-input" placeholder="e.g. AU-STU" />
                            <InputError :message="visaTypeForm.errors.code" />
                        </div>
                        <div>
                            <InputLabel for="visa_type_official_subclass" value="Official subclass" />
                            <input id="visa_type_official_subclass" v-model="visaTypeForm.official_subclass" class="ui-input" placeholder="e.g. 500" />
                            <InputError :message="visaTypeForm.errors.official_subclass" />
                        </div>
                        <div>
                            <InputLabel for="visa_type_slug" value="Slug" />
                            <input id="visa_type_slug" v-model="visaTypeForm.slug" class="ui-input" placeholder="auto-generated if left blank" />
                            <InputError :message="visaTypeForm.errors.slug" />
                        </div>
                        <div class="xl:col-span-2">
                            <InputLabel for="visa_type_official_reference_url" value="Official reference URL" />
                            <input id="visa_type_official_reference_url" v-model="visaTypeForm.official_reference_url" class="ui-input" placeholder="https://immi.homeaffairs.gov.au/..." />
                            <InputError :message="visaTypeForm.errors.official_reference_url" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-brand-border bg-white px-4 py-4">
                        <p class="text-sm font-semibold text-brand-text">Official visa information</p>
                        <div class="mt-4 space-y-5">
                            <div>
                                <InputLabel for="visa_type_official_summary" value="Official summary" />
                                <textarea id="visa_type_official_summary" v-model="visaTypeForm.official_summary" rows="4" class="ui-textarea" placeholder="Add a plain-language summary based on the official government visa page."></textarea>
                                <InputError :message="visaTypeForm.errors.official_summary" />
                            </div>
                            <div>
                                <InputLabel for="visa_type_official_requirements" value="Official requirements" />
                                <textarea id="visa_type_official_requirements" v-model="visaTypeForm.official_requirements_input" rows="8" class="ui-textarea" placeholder="One requirement per line"></textarea>
                                <p class="mt-2 text-[12px] text-brand-muted">Keep one requirement on each line so the app can store and display them cleanly.</p>
                                <InputError :message="visaTypeForm.errors.official_requirements" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-brand-border bg-brand-neutral px-4 py-4">
                        <p class="text-sm font-semibold text-brand-text">Service defaults</p>
                        <div class="mt-4 grid gap-5 xl:grid-cols-2">
                            <div>
                                <InputLabel for="visa_type_entry_type" value="Entry type" />
                                <select id="visa_type_entry_type" v-model="visaTypeForm.entry_type" class="ui-select">
                                    <option value="">Not specified</option>
                                    <option value="single_entry">Single entry</option>
                                    <option value="multiple_entry">Multiple entry</option>
                                    <option value="limited_entry">Limited entry</option>
                                </select>
                                <InputError :message="visaTypeForm.errors.entry_type" />
                            </div>
                            <div>
                                <InputLabel for="visa_type_service_scope" value="Service scope" />
                                <select id="visa_type_service_scope" v-model="visaTypeForm.service_scope" class="ui-select">
                                    <option value="">Not specified</option>
                                    <option value="new_application">New application</option>
                                    <option value="renewal">Renewal</option>
                                    <option value="extension">Extension</option>
                                    <option value="multi_step_case">Multi-step case</option>
                                </select>
                                <InputError :message="visaTypeForm.errors.service_scope" />
                            </div>
                            <div>
                                <InputLabel for="visa_type_submission_sla_days" value="Expected submission lead time (days)" />
                                <input id="visa_type_submission_sla_days" v-model="visaTypeForm.submission_sla_days" type="number" min="0" class="ui-input" />
                                <InputError :message="visaTypeForm.errors.submission_sla_days" />
                            </div>
                            <div>
                                <InputLabel for="visa_type_decision_sla_days" value="Expected decision lead time (days)" />
                                <input id="visa_type_decision_sla_days" v-model="visaTypeForm.decision_sla_days" type="number" min="0" class="ui-input" />
                                <InputError :message="visaTypeForm.errors.decision_sla_days" />
                            </div>
                            <div>
                                <InputLabel for="visa_type_stay_duration_days" value="Typical stay duration (days)" />
                                <input id="visa_type_stay_duration_days" v-model="visaTypeForm.stay_duration_days" type="number" min="0" class="ui-input" />
                                <InputError :message="visaTypeForm.errors.stay_duration_days" />
                            </div>
                            <div>
                                <InputLabel for="visa_type_validity_months" value="Typical validity (months)" />
                                <input id="visa_type_validity_months" v-model="visaTypeForm.validity_months" type="number" min="0" class="ui-input" />
                                <InputError :message="visaTypeForm.errors.validity_months" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-brand-border bg-white px-4 py-4">
                        <p class="text-sm font-semibold text-brand-text">Requirements and eligibility flags</p>
                        <div class="mt-4 grid gap-3 xl:grid-cols-2">
                            <label class="flex items-start gap-3 rounded-xl border border-brand-border bg-brand-neutral px-4 py-4">
                                <input v-model="visaTypeForm.biometrics_required" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                                <span class="text-sm text-brand-text">Biometrics required</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-brand-border bg-brand-neutral px-4 py-4">
                                <input v-model="visaTypeForm.interview_required" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                                <span class="text-sm text-brand-text">Interview required</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-brand-border bg-brand-neutral px-4 py-4">
                                <input v-model="visaTypeForm.medical_required" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                                <span class="text-sm text-brand-text">Medical exam required</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-brand-border bg-brand-neutral px-4 py-4">
                                <input v-model="visaTypeForm.police_clearance_required" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                                <span class="text-sm text-brand-text">Police clearance required</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-brand-border bg-brand-neutral px-4 py-4">
                                <input v-model="visaTypeForm.financial_proof_required" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                                <span class="text-sm text-brand-text">Financial proof required</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-brand-border bg-brand-neutral px-4 py-4">
                                <input v-model="visaTypeForm.dependants_allowed" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                                <span class="text-sm text-brand-text">Dependants allowed</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-brand-border bg-brand-neutral px-4 py-4 xl:col-span-2">
                                <input v-model="visaTypeForm.priority_support" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                                <span class="text-sm text-brand-text">Allow urgent / VIP handling for this visa type</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid gap-5 xl:grid-cols-2">
                        <div>
                            <InputLabel for="visa_type_checklist_intro" value="Checklist intro" />
                            <textarea id="visa_type_checklist_intro" v-model="visaTypeForm.checklist_intro" rows="5" class="ui-textarea" placeholder="Explain what applicants should prepare before they start uploading documents."></textarea>
                            <InputError :message="visaTypeForm.errors.checklist_intro" />
                        </div>
                        <div>
                            <InputLabel for="visa_type_portal_guidance" value="Portal guidance" />
                            <textarea id="visa_type_portal_guidance" v-model="visaTypeForm.portal_guidance" rows="5" class="ui-textarea" placeholder="Set expectations applicants should see in the portal for this visa type."></textarea>
                            <InputError :message="visaTypeForm.errors.portal_guidance" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="visa_type_notes" value="Internal notes" />
                        <textarea id="visa_type_notes" v-model="visaTypeForm.notes" rows="4" class="ui-textarea" placeholder="Capture internal handling notes, caveats, or embassy-specific instructions."></textarea>
                        <InputError :message="visaTypeForm.errors.notes" />
                    </div>

                    <label class="flex items-center gap-3">
                        <input v-model="visaTypeForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                        <span class="text-sm text-brand-text">Visa type is active for new cases</span>
                    </label>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-brand-border bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showVisaType = false">Never mind</button>
                        <PrimaryButton :loading="visaTypeForm.processing">{{ editingVisaTypeId ? 'Save visa type' : 'Add visa type' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>

        <SlideOver :show="showTemplate" width="wide" @close="showTemplate = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Checklist template</p>
                            <h2 class="mt-2 text-2xl">{{ editingTemplateId ? 'Edit' : 'Add' }}</h2>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showTemplate = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveTemplate">
                    <div>
                        <InputLabel for="template_visa_type" value="Visa type" />
                        <select id="template_visa_type" v-model="templateForm.visa_type_id" class="ui-select">
                            <option value="">Choose a visa type</option>
                            <option v-for="visaType in visaTypes" :key="visaType.id" :value="visaType.id">
                                {{ visaType.country.name }} • {{ visaType.name }}
                            </option>
                        </select>
                        <InputError :message="templateForm.errors.visa_type_id" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="template_name" value="Document name" />
                            <input id="template_name" v-model="templateForm.name" class="ui-input" />
                            <InputError :message="templateForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="template_position" value="Position" />
                            <input id="template_position" v-model="templateForm.position" type="number" min="1" class="ui-input" />
                            <InputError :message="templateForm.errors.position" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="template_slug" value="Slug" />
                        <input id="template_slug" v-model="templateForm.slug" class="ui-input" placeholder="auto-generated if left blank" />
                        <InputError :message="templateForm.errors.slug" />
                    </div>
                    <div>
                        <InputLabel for="template_description" value="Helper description" />
                        <textarea id="template_description" v-model="templateForm.description" rows="3" class="ui-textarea" placeholder="Tell applicants what good looks like for this document."></textarea>
                        <InputError :message="templateForm.errors.description" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="template_category" value="Category" />
                            <input id="template_category" v-model="templateForm.category" class="ui-input" placeholder="Identity, Finance, Compliance" />
                            <InputError :message="templateForm.errors.category" />
                        </div>
                        <div>
                            <InputLabel for="template_due_days" value="Target due in days" />
                            <input id="template_due_days" v-model="templateForm.due_days" type="number" min="0" class="ui-input" placeholder="Leave blank if flexible" />
                            <InputError :message="templateForm.errors.due_days" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="template_file_types" value="Accepted file types" />
                            <input id="template_file_types" v-model="templateForm.accepted_file_types_input" class="ui-input" placeholder="pdf, jpg, png" />
                            <p class="ui-helper">Comma-separated extensions. These drive applicant guidance and upload validation.</p>
                            <InputError :message="templateForm.errors.accepted_file_types" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="template_max_files" value="Max files" />
                                <input id="template_max_files" v-model="templateForm.max_files" type="number" min="1" max="20" class="ui-input" />
                                <InputError :message="templateForm.errors.max_files" />
                            </div>
                            <div>
                                <InputLabel for="template_max_file_size" value="Max file size (MB)" />
                                <input id="template_max_file_size" v-model="templateForm.max_file_size_mb" type="number" min="1" max="100" class="ui-input" />
                                <InputError :message="templateForm.errors.max_file_size_mb" />
                            </div>
                        </div>
                    </div>
                    <div>
                        <InputLabel for="template_client_instructions" value="Applicant instructions" />
                        <textarea
                            id="template_client_instructions"
                            v-model="templateForm.client_instructions"
                            rows="4"
                            class="ui-textarea"
                            placeholder="Explain exactly what the applicant should upload and what a good submission looks like."
                        ></textarea>
                        <InputError :message="templateForm.errors.client_instructions" />
                    </div>
                    <div>
                        <InputLabel for="template_agent_guidance" value="Internal review guidance" />
                        <textarea
                            id="template_agent_guidance"
                            v-model="templateForm.agent_guidance"
                            rows="3"
                            class="ui-textarea"
                            placeholder="Internal checklist for agents reviewing this document."
                        ></textarea>
                        <InputError :message="templateForm.errors.agent_guidance" />
                    </div>
                    <div>
                        <InputLabel for="template_sample_hint" value="Sample hint" />
                        <input id="template_sample_hint" v-model="templateForm.sample_hint" class="ui-input" placeholder="For example: One colour PDF is best." />
                        <InputError :message="templateForm.errors.sample_hint" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-xl border border-brand-border px-4 py-4">
                            <input v-model="templateForm.is_required" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                            <span class="text-sm text-brand-text">Required for submission</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border border-brand-border px-4 py-4">
                            <input v-model="templateForm.tracks_expiry" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                            <span class="text-sm text-brand-text">Track expiry date</span>
                        </label>
                    </div>
                    <label class="flex items-center gap-3 rounded-xl border border-brand-border px-4 py-4">
                        <input v-model="templateForm.is_repeatable" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                        <span class="text-sm text-brand-text">Allow multiple supporting files for this requirement</span>
                    </label>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-brand-border bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showTemplate = false">Never mind</button>
                        <PrimaryButton :loading="templateForm.processing">{{ editingTemplateId ? 'Save' : 'Add' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>

        <SlideOver :show="showWorkflowStage" width="wide" @close="showWorkflowStage = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Workflow stage</p>
                            <h2 class="mt-2 text-2xl">{{ editingWorkflowStageId ? 'Edit workflow stage' : 'Add workflow stage' }}</h2>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showWorkflowStage = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveWorkflowStage">
                    <div>
                        <InputLabel for="workflow_stage_visa_type" value="Visa type" />
                        <select id="workflow_stage_visa_type" v-model="workflowStageForm.visa_type_id" class="ui-select">
                            <option value="">Choose a visa type</option>
                            <option v-for="visaType in visaTypes" :key="visaType.id" :value="visaType.id">
                                {{ visaType.country.name }} • {{ visaType.name }}
                            </option>
                        </select>
                        <InputError :message="workflowStageForm.errors.visa_type_id" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="workflow_stage_name" value="Stage name" />
                            <input id="workflow_stage_name" v-model="workflowStageForm.name" class="ui-input" />
                            <InputError :message="workflowStageForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="workflow_stage_position" value="Position" />
                            <input id="workflow_stage_position" v-model="workflowStageForm.position" type="number" min="1" class="ui-input" />
                            <InputError :message="workflowStageForm.errors.position" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="workflow_stage_slug" value="Slug" />
                            <input id="workflow_stage_slug" v-model="workflowStageForm.slug" class="ui-input" placeholder="auto-generated if left blank" />
                            <InputError :message="workflowStageForm.errors.slug" />
                        </div>
                        <div>
                            <InputLabel for="workflow_stage_color" value="Color" />
                            <select id="workflow_stage_color" v-model="workflowStageForm.color" class="ui-select">
                                <option value="slate">Slate</option>
                                <option value="blue">Blue</option>
                                <option value="amber">Amber</option>
                                <option value="violet">Violet</option>
                                <option value="emerald">Emerald</option>
                                <option value="rose">Rose</option>
                            </select>
                            <InputError :message="workflowStageForm.errors.color" />
                        </div>
                    </div>
                    <label class="flex items-center gap-3">
                        <input v-model="workflowStageForm.is_default" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                        <span class="text-sm text-brand-text">Use this as the default opening stage for new cases</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input v-model="workflowStageForm.is_closed" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                        <span class="text-sm text-brand-text">This is a closed stage</span>
                    </label>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-brand-border bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showWorkflowStage = false">Never mind</button>
                        <PrimaryButton :loading="workflowStageForm.processing">{{ editingWorkflowStageId ? 'Save stage' : 'Add stage' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>

        <SlideOver :show="showTaskTemplate" width="wide" @close="showTaskTemplate = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Task template</p>
                            <h2 class="mt-2 text-2xl">{{ editingTaskTemplateId ? 'Edit task template' : 'Add task template' }}</h2>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showTaskTemplate = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveTaskTemplate">
                    <div>
                        <InputLabel for="task_template_visa_type" value="Visa type" />
                        <select id="task_template_visa_type" v-model="taskTemplateForm.visa_type_id" class="ui-select">
                            <option value="">Choose a visa type</option>
                            <option v-for="visaType in visaTypes" :key="visaType.id" :value="visaType.id">
                                {{ visaType.country.name }} • {{ visaType.name }}
                            </option>
                        </select>
                        <InputError :message="taskTemplateForm.errors.visa_type_id" />
                    </div>
                    <div>
                        <InputLabel for="task_template_stage" value="Workflow stage" />
                        <select id="task_template_stage" v-model="taskTemplateForm.visa_workflow_stage_id" class="ui-select">
                            <option value="">Not tied to a specific stage</option>
                            <option v-for="stage in filteredWorkflowStages" :key="stage.id" :value="stage.id">
                                {{ stage.name }}
                            </option>
                        </select>
                        <InputError :message="taskTemplateForm.errors.visa_workflow_stage_id" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="task_template_name" value="Task name" />
                            <input id="task_template_name" v-model="taskTemplateForm.name" class="ui-input" />
                            <InputError :message="taskTemplateForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="task_template_position" value="Position" />
                            <input id="task_template_position" v-model="taskTemplateForm.position" type="number" min="1" class="ui-input" />
                            <InputError :message="taskTemplateForm.errors.position" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="task_template_slug" value="Slug" />
                        <input id="task_template_slug" v-model="taskTemplateForm.slug" class="ui-input" placeholder="auto-generated if left blank" />
                        <InputError :message="taskTemplateForm.errors.slug" />
                    </div>
                    <div>
                        <InputLabel for="task_template_description" value="Task description" />
                        <textarea id="task_template_description" v-model="taskTemplateForm.description" rows="4" class="ui-textarea" placeholder="What exactly should the team do for this step?"></textarea>
                        <InputError :message="taskTemplateForm.errors.description" />
                    </div>
                    <div>
                        <InputLabel for="task_template_due_days" value="Suggested due in days" />
                        <input id="task_template_due_days" v-model="taskTemplateForm.due_days" type="number" min="0" class="ui-input" placeholder="Leave blank if flexible" />
                        <InputError :message="taskTemplateForm.errors.due_days" />
                    </div>
                    <label class="flex items-center gap-3">
                        <input v-model="taskTemplateForm.is_required" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                        <span class="text-sm text-brand-text">This task is required</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input v-model="taskTemplateForm.is_client_visible" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                        <span class="text-sm text-brand-text">Applicant can see this task in the future portal</span>
                    </label>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-brand-border bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showTaskTemplate = false">Never mind</button>
                        <PrimaryButton :loading="taskTemplateForm.processing">{{ editingTaskTemplateId ? 'Save task template' : 'Add task template' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>

        <SlideOver :show="showMessageTemplate" width="wide" @close="showMessageTemplate = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Message template</p>
                            <h2 class="mt-2 text-2xl">{{ editingMessageTemplateId ? 'Edit email or SMS template' : 'Add email or SMS template' }}</h2>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showMessageTemplate = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveMessageTemplate">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="message_name" value="Template name" />
                            <input id="message_name" v-model="messageTemplateForm.name" class="ui-input" />
                            <InputError :message="messageTemplateForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="message_key" value="Template key" />
                            <input id="message_key" v-model="messageTemplateForm.key" class="ui-input" placeholder="auto-generated if left blank" />
                            <InputError :message="messageTemplateForm.errors.key" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="message_channel" value="Channel" />
                            <select id="message_channel" v-model="messageTemplateForm.channel" class="ui-select">
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                            </select>
                            <InputError :message="messageTemplateForm.errors.channel" />
                        </div>
                        <div>
                            <InputLabel for="message_locale" value="Locale" />
                            <select id="message_locale" v-model="messageTemplateForm.locale" class="ui-select">
                                <option v-for="locale in locales" :key="locale.value" :value="locale.value">
                                    {{ locale.label }}
                                </option>
                            </select>
                            <InputError :message="messageTemplateForm.errors.locale" />
                        </div>
                        <label class="flex items-center gap-3 rounded-xl border border-brand-border px-4 py-4 sm:mt-8">
                            <input v-model="messageTemplateForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20" />
                            <span class="text-sm text-brand-text">Template is active</span>
                        </label>
                    </div>

                    <div v-if="messageTemplateForm.channel === 'email'">
                        <InputLabel for="message_subject" value="Email subject" />
                        <input id="message_subject" v-model="messageTemplateForm.subject" class="ui-input" />
                        <InputError :message="messageTemplateForm.errors.subject" />
                    </div>

                    <div>
                        <InputLabel for="message_body" value="Message body" />
                        <textarea id="message_body" v-model="messageTemplateForm.body" rows="7" class="ui-textarea" placeholder="Write the reusable message here."></textarea>
                        <InputError :message="messageTemplateForm.errors.body" />
                    </div>

                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-brand-border bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showMessageTemplate = false">Never mind</button>
                        <PrimaryButton :loading="messageTemplateForm.processing">{{ editingMessageTemplateId ? 'Save template' : 'Add template' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>
    </AuthenticatedLayout>
</template>
