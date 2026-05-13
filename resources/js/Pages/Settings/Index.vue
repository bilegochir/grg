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
import DeleteUserForm from '../Profile/Partials/DeleteUserForm.vue';
import UpdatePasswordForm from '../Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '../Profile/Partials/UpdateProfileInformationForm.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    businessSetting: Object,
    countries: Array,
    visaTypes: Array,
    workflowStages: Array,
    taskTemplates: Array,
    documentTemplates: Array,
    communicationTemplates: Array,
    formTemplates: Array,
    availableFields: Object,
    branches: Array,
    locales: Array,
    smsProviders: Array,
    appLocale: String,
    mustVerifyEmail: Boolean,
    status: String,
});

const { t } = useLocale();
const localeOptions = computed(() => props.locales.map((locale) => ({
    ...locale,
    label: locale.value === 'mn' ? t('common.mongolian') : t('common.english'),
})));

const showCountry = ref(false);
const showVisaType = ref(false);
const showTemplate = ref(false);
const showMessageTemplate = ref(false);
const showWorkflowStage = ref(false);
const showTaskTemplate = ref(false);
const showFormTemplate = ref(false);

const editingCountryId = ref(null);
const editingVisaTypeId = ref(null);
const editingTemplateId = ref(null);
const editingMessageTemplateId = ref(null);
const editingFormTemplateId = ref(null);
const editingWorkflowStageId = ref(null);
const editingTaskTemplateId = ref(null);
const selectedVisaTypeId = ref(props.visaTypes[0]?.id ?? '');
const canManage = computed(() => usePage().props.auth.user.permissions.includes('settings.manage'));
const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || (canManage.value ? 'business' : 'profile'));
const visaTypeSearch = ref('');
const visaTypeCountryFilter = ref('all');
const visaTypeServiceScopeFilter = ref('all');
const visaTypeReviewFilter = ref('all');
const expandedVisaTypes = ref([]);
const logoPreviewUrl = ref(props.businessSetting.logo_url ?? null);

const toggleVisaTypeExpansion = (id) => {
    if (expandedVisaTypes.value.includes(id)) {
        expandedVisaTypes.value = expandedVisaTypes.value.filter(i => i !== id);
    } else {
        expandedVisaTypes.value.push(id);
    }
};

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
    official_last_reviewed_at: '',
    policy_effective_date: '',
    official_change_notes: '',
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

            if (visaTypeReviewFilter.value === 'needs_review' && !visaType.needs_review) {
                return false;
            }

            if (visaTypeReviewFilter.value === 'reviewed' && visaType.needs_review) {
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

const visaTypesByCountry = computed(() => {
    const groups = [];
    filteredVisaTypes.value.forEach((visaType) => {
        let group = groups.find(g => g.country.id === visaType.country.id);
        if (!group) {
            group = {
                country: visaType.country,
                items: []
            };
            groups.push(group);
        }
        group.items.push(visaType);
    });
    return groups;
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
    visaTypeForm.official_last_reviewed_at = '';
    visaTypeForm.policy_effective_date = '';
    visaTypeForm.official_change_notes = '';
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
    activeTab.value = 'countries';
    resetCountryForm();
    showCountry.value = true;
};

const openCountryEdit = (country) => {
    activeTab.value = 'countries';
    editingCountryId.value = country.id;
    countryForm.name = country.name;
    countryForm.slug = country.slug;
    countryForm.is_active = country.is_active;
    showCountry.value = true;
};

const openVisaTypeCreate = () => {
    activeTab.value = 'visa-types';
    resetVisaTypeForm();
    showVisaType.value = true;
};

const openVisaTypeEdit = (visaType) => {
    activeTab.value = 'visa-types';
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
    visaTypeForm.official_last_reviewed_at = visaType.official_last_reviewed_at ?? '';
    visaTypeForm.policy_effective_date = visaType.policy_effective_date ?? '';
    visaTypeForm.official_change_notes = visaType.official_change_notes ?? '';
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
            onSuccess: () => {
                logoPreviewUrl.value = usePage().props.businessSetting?.logo_url ?? logoPreviewUrl.value;
                businessForm.logo = null;
            },
        });
};

const updateLogoPreview = (event) => {
    const file = event.target.files?.[0] ?? null;

    businessForm.logo = file;

    if (!file) {
        logoPreviewUrl.value = props.businessSetting.logo_url ?? null;
        return;
    }

    logoPreviewUrl.value = URL.createObjectURL(file);
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
        official_last_reviewed_at: data.official_last_reviewed_at || null,
        policy_effective_date: data.policy_effective_date || null,
        official_change_notes: data.official_change_notes || null,
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
                key: 'visa-types',
                label: 'Visa Types',
                icon: 'document',
            },
            {
                key: 'countries',
                label: 'Countries',
                icon: 'map',
            },
        ],
    },
    {
        label: 'Account',
        items: [
            {
                key: 'profile',
                label: 'Profile',
                icon: 'users',
            },
        ],
    },
    {
        label: 'Infrastructure',
        items: [
            {
                key: 'workflow',
                label: 'Workflows',
                icon: 'tasks',
                description: 'Automation stages',
            },
            {
                key: 'checklists',
                label: 'Checklists',
                icon: 'document',
                description: 'Document requirements',
            },
            {
                key: 'forms',
                label: 'Gov. Forms',
                icon: 'document',
                description: 'PDF form templates',
            },
            {
                key: 'messaging',
                label: 'Messaging',
                icon: 'mail',
                description: 'Email & SMS templates',
            },
        ],
    },
];

const visibleSettingGroups = computed(() => {
    return settingGroups.filter(group => {
        if (group.label === 'Workspace' && !canManage.value) return false;
        if (group.label === 'Infrastructure' && !canManage.value) return false;
        return true;
    });
});

// ── Form Templates ────────────────────────────────────────────────────────────
const formTemplateForm = useForm({
    visa_type_id: props.visaTypes[0]?.id ?? '',
    name: '',
    description: '',
    pdf: null,
    field_mapping: {},
    _method: 'POST',
});

const editingFormTemplate = ref(null);
const formTemplateEditForm = useForm({
    name: '',
    description: '',
    field_mapping: {},
    is_active: true,
});

const formTemplateVisaFilter = ref(props.visaTypes[0]?.id ?? '');

const filteredFormTemplates = computed(() =>
    props.formTemplates?.filter(t =>
        !formTemplateVisaFilter.value || t.visa_type_id === formTemplateVisaFilter.value
    ) ?? []
);

const openFormTemplateCreate = () => {
    editingFormTemplateId.value = null;
    formTemplateForm.reset();
    formTemplateForm.visa_type_id = props.visaTypes[0]?.id ?? '';
    showFormTemplate.value = true;
};

const openFormTemplateEdit = (template) => {
    editingFormTemplateId.value = template.id;
    editingFormTemplate.value = template;
    formTemplateEditForm.name = template.name;
    formTemplateEditForm.description = template.description ?? '';
    formTemplateEditForm.field_mapping = { ...template.field_mapping };
    formTemplateEditForm.is_active = template.is_active;
    showFormTemplate.value = true;
};

const addMappingRow = () => {
    formTemplateForm.field_mapping = { ...formTemplateForm.field_mapping, '': '' };
};

const addEditMappingRow = () => {
    formTemplateEditForm.field_mapping = { ...formTemplateEditForm.field_mapping, '': '' };
};

const updateMappingKey = (form, oldKey, newKey) => {
    const val = form.field_mapping[oldKey];
    const updated = {};
    Object.keys(form.field_mapping).forEach(k => {
        updated[k === oldKey ? newKey : k] = form.field_mapping[k];
    });
    form.field_mapping = updated;
};

const removeMappingRow = (form, key) => {
    const updated = { ...form.field_mapping };
    delete updated[key];
    form.field_mapping = updated;
};

const saveFormTemplate = () => {
    if (editingFormTemplateId.value) {
        formTemplateEditForm.patch(
            route('settings.form-templates.update', editingFormTemplateId.value),
            { preserveScroll: true, onSuccess: () => { showFormTemplate.value = false; } },
        );
    } else {
        formTemplateForm.post(route('settings.form-templates.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { showFormTemplate.value = false; formTemplateForm.reset(); },
        });
    }
};
</script>

<template>
    <Head :title="t('pages.settings.title')" />

    <AuthenticatedLayout>
        <template #sidebar>
            <div class="px-3 py-3">
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-2 text-[13px] font-medium text-slate-500 hover:text-slate-900 transition-colors mb-6"
                >
                    <AppIcon name="chevronLeft" :size="14" />
                    Back to app
                </Link>

                <nav class="space-y-7">
                    <div v-for="group in visibleSettingGroups" :key="group.label">
                        <p class="px-1 text-[10px] font-bold uppercase tracking-[0.05em] text-slate-400/80">
                            {{ group.label }}
                        </p>
                        <div class="mt-2.5 space-y-0.5">
                            <button
                                v-for="tab in group.items"
                                :key="tab.key"
                                type="button"
                                class="group flex w-full items-center gap-2.5 rounded-md px-2 py-1.5 text-left transition-all duration-200"
                                :class="activeTab === tab.key
                                    ? 'bg-slate-200/50 text-slate-900 font-semibold shadow-sm'
                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                                @click="activeTab = tab.key"
                            >
                                <AppIcon
                                    :name="tab.icon"
                                    :size="14"
                                    :class="activeTab === tab.key ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600'"
                                />
                                <span class="text-[13px]">{{ tab.label }}</span>
                            </button>
                        </div>
                    </div>
                </nav>

                <div class="mt-12 pt-8 border-t border-slate-100">
                    <p class="px-1 text-[10px] font-bold uppercase tracking-[0.05em] text-slate-400/80">Help & Support</p>
                    <div class="mt-4 space-y-2">
                        <a href="#" class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-[13px] text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-all">
                            <AppIcon name="document" :size="14" class="text-slate-400" />
                            Documentation
                        </a>
                        <a href="#" class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-[13px] text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-all">
                            <AppIcon name="sparkle" :size="14" class="text-slate-400" />
                            API Reference
                        </a>
                        <a href="#" class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-[13px] text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-all">
                            <AppIcon name="mail" :size="14" class="text-slate-400" />
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto w-full">
            <div class="min-w-0 flex-1">

            <div v-if="activeTab === 'profile'">
                <div class="mb-10">
                    <h2 class="text-[20px] font-semibold text-slate-900">{{ t('pages.settings.profileHeading') }}</h2>
                    <p class="mt-1 text-[13px] text-slate-500">{{ t('pages.settings.profileDescription') }}</p>
                </div>

                <div class="max-w-5xl space-y-6">
                    <AppCard>
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-2xl"
                        />
                    </AppCard>

                    <AppCard :title="t('pages.profile.passwordCardTitle')">
                        <UpdatePasswordForm class="max-w-2xl" />
                    </AppCard>

                    <AppCard :title="t('pages.profile.dangerCardTitle')">
                        <DeleteUserForm class="max-w-2xl" />
                    </AppCard>
                </div>
            </div>

            <div v-if="activeTab === 'business'">
                <div class="mb-10">
                    <h2 class="text-[20px] font-semibold text-slate-900">{{ t('pages.settings.generalHeading') }}</h2>
                    <p class="mt-1 text-[13px] text-slate-500">{{ t('pages.settings.generalDescription') }}</p>
                </div>
                <form class="space-y-6" @submit.prevent="saveBusinessSettings">
                    <div class="max-w-3xl">
                        <AppCard :title="t('settings.business.title')">
                            <div class="space-y-5">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="business_name" :value="t('pages.settings.businessName')" />
                                        <input id="business_name" v-model="businessForm.business_name" class="ui-input" />
                                        <InputError :message="businessForm.errors.business_name" />
                                    </div>
                                    <div>
                                        <InputLabel for="default_locale" :value="t('pages.settings.defaultLanguage')" />
                                        <select id="default_locale" v-model="businessForm.default_locale" class="ui-select">
                                            <option v-for="locale in localeOptions" :key="locale.value" :value="locale.value">
                                                {{ locale.label }}
                                            </option>
                                        </select>
                                        <InputError :message="businessForm.errors.default_locale" />
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="contact_email" :value="t('pages.settings.businessEmail')" />
                                        <input id="contact_email" v-model="businessForm.contact_email" type="email" class="ui-input" />
                                        <InputError :message="businessForm.errors.contact_email" />
                                    </div>
                                    <div>
                                        <InputLabel for="contact_phone" :value="t('pages.settings.businessPhone')" />
                                        <input id="contact_phone" v-model="businessForm.contact_phone" class="ui-input" />
                                        <InputError :message="businessForm.errors.contact_phone" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="contact_address" :value="t('pages.settings.businessAddress')" />
                                    <textarea id="contact_address" v-model="businessForm.contact_address" rows="3" class="ui-textarea"></textarea>
                                    <InputError :message="businessForm.errors.contact_address" />
                                </div>

                                <div>
                                    <InputLabel for="logo" :value="t('pages.settings.businessLogo')" />
                                    <div class="mt-3 flex items-center gap-4">
                                        <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl border border-brand-border bg-white">
                                            <img
                                                v-if="logoPreviewUrl"
                                                :src="logoPreviewUrl"
                                                :alt="t('pages.settings.businessLogo')"
                                                class="h-auto w-full object-cover"
                                            />
                                            <AppIcon v-else name="sparkle" :size="24" class="text-brand-primary" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-brand-text">{{ businessForm.business_name || t('pages.settings.businessLogo') }}</p>
                                        </div>
                                    </div>
                                    <input
                                        id="logo"
                                        type="file"
                                        accept="image/*"
                                        class="mt-3 block w-full text-sm text-brand-muted file:mr-4 file:rounded-md file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-text"
                                        @change="updateLogoPreview"
                                    />
                                    <InputError :message="businessForm.errors.logo" />
                                </div>
                            </div>
                        </AppCard>
                    </div>

                    <div class="mt-2 flex justify-end border-t border-slate-100 pt-6">
                        <PrimaryButton :loading="businessForm.processing">{{ t('common.saveChanges') }}</PrimaryButton>
                    </div>
                </form>
            </div>

            <div v-if="activeTab === 'visa-types'" class="max-w-5xl">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-[20px] font-semibold text-slate-900">Visa Types</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Manage specific visa categories and their requirements by country.</p>
                    </div>
                    <PrimaryButton icon="plus" @click="openVisaTypeCreate">Add visa type</PrimaryButton>
                </div>

                <div class="space-y-6">
                    <div class="sticky top-0 z-10 -mx-4 mb-6 rounded-xl border border-slate-200 bg-white/80 p-4 shadow-sm backdrop-blur-md sm:mx-0">
                        <div class="grid gap-4 md:grid-cols-[1fr,180px,180px,180px]">
                            <div class="relative">
                                <AppIcon name="search" :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input
                                    v-model="visaTypeSearch"
                                    class="ui-input !pl-9"
                                    placeholder="Search by name, code, or requirement..."
                                />
                            </div>
                            <select v-model="visaTypeCountryFilter" class="ui-select">
                                <option value="all">All countries</option>
                                <option v-for="country in visaTypeCountries" :key="country.id" :value="String(country.id)">
                                    {{ country.name }}
                                </option>
                            </select>
                            <select v-model="visaTypeServiceScopeFilter" class="ui-select">
                                <option value="all">All scopes</option>
                                <option value="new_application">New application</option>
                                <option value="renewal">Renewal</option>
                                <option value="extension">Extension</option>
                                <option value="multi_step_case">Multi-step case</option>
                            </select>
                            <select v-model="visaTypeReviewFilter" class="ui-select">
                                <option value="all">All review states</option>
                                <option value="needs_review">Needs review</option>
                                <option value="reviewed">Reviewed recently</option>
                            </select>
                        </div>
                        <div v-if="visaTypeSearch || visaTypeCountryFilter !== 'all' || visaTypeServiceScopeFilter !== 'all' || visaTypeReviewFilter !== 'all'" class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                            <span class="text-[12px] text-slate-500">{{ filteredVisaTypes.length }} results found</span>
                            <button
                                type="button"
                                class="text-[12px] font-semibold text-brand-primary hover:underline"
                                @click="visaTypeSearch = ''; visaTypeCountryFilter = 'all'; visaTypeServiceScopeFilter = 'all'; visaTypeReviewFilter = 'all'"
                            >
                                Reset filters
                            </button>
                        </div>
                    </div>

                    <div v-if="visaTypesByCountry.length" class="space-y-10">
                        <div v-for="group in visaTypesByCountry" :key="group.country.id" class="space-y-4">
                            <div class="flex items-center gap-3 px-1">
                                <h3 class="text-[14px] font-bold uppercase tracking-widest text-slate-400">{{ group.country.name }}</h3>
                                <div class="h-px flex-1 bg-slate-100"></div>
                                <span class="text-[11px] font-bold text-slate-400">{{ group.items.length }} types</span>
                            </div>

                            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                                <div v-for="visaType in group.items" :key="visaType.id" class="group">
                                    <div class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50/50">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-3">
                                                <button
                                                    type="button"
                                                    class="flex items-center gap-2.5 text-left transition-colors"
                                                    @click="toggleVisaTypeExpansion(visaType.id)"
                                                >
                                                    <AppIcon
                                                        name="chevronRight"
                                                        :size="12"
                                                        class="transition-transform duration-200 text-slate-400"
                                                        :class="{ 'rotate-90': expandedVisaTypes.includes(visaType.id) }"
                                                    />
                                                    <p class="text-[14px] font-bold text-slate-900">{{ visaType.name }}</p>
                                                </button>
                                                <span v-if="visaType.official_subclass" class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">Subclass {{ visaType.official_subclass }}</span>
                                                <span v-if="visaType.code" class="text-[11px] font-mono font-bold text-slate-400">{{ visaType.code }}</span>
                                                <span :class="visaType.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider">
                                                    {{ visaType.is_active ? 'Live' : 'Paused' }}
                                                </span>
                                                <span :class="visaType.needs_review ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'" class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider">
                                                    {{ visaType.review_status_label }}
                                                </span>
                                            </div>
                                            <div class="mt-1 flex items-center gap-4 text-[12px] text-slate-500">
                                                <span>{{ visaTypeScopeLabel(visaType.service_scope) }}</span>
                                                <span v-if="visaType.submission_sla_days">• {{ visaType.submission_sla_days }}d SLA</span>
                                                <span v-if="visaType.official_requirements?.length">• {{ visaType.official_requirements.length }} requirements</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button type="button" class="ui-button-ghost h-8 px-2.5" title="Edit details" @click="openVisaTypeEdit(visaType)">
                                                <AppIcon name="edit" :size="14" />
                                            </button>
                                            <button type="button" class="ui-button-ghost h-8 px-2.5" title="Workflow" @click="openVisaTypeWorkflow(visaType)">
                                                <AppIcon name="tasks" :size="14" />
                                            </button>
                                            <button type="button" class="ui-button-ghost h-8 px-2.5" title="Checklist" @click="openVisaTypeChecklist(visaType)">
                                                <AppIcon name="document" :size="14" />
                                            </button>
                                            <a v-if="visaType.official_reference_url" :href="visaType.official_reference_url" target="_blank" class="ui-button-ghost h-8 px-2.5" title="Official page">
                                                <AppIcon name="externalLink" :size="14" />
                                            </a>
                                            <button type="button" class="ui-button-danger h-8 px-2.5" title="Delete" @click="destroyResource('settings.visa-types.destroy', visaType.id)">
                                                <AppIcon name="trash" :size="14" />
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="expandedVisaTypes.includes(visaType.id)" class="border-t border-slate-50 bg-slate-50/30 px-12 py-5">
                                        <div class="grid gap-8 lg:grid-cols-2">
                                            <div v-if="visaType.official_summary">
                                                <h4 class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Official Summary</h4>
                                                <p class="mt-2 text-[13px] leading-relaxed text-slate-600">{{ visaType.official_summary }}</p>
                                            </div>
                                            <div v-if="visaType.official_requirements?.length">
                                                <h4 class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Key Requirements</h4>
                                                <ul class="mt-3 space-y-2">
                                                    <li v-for="req in visaType.official_requirements" :key="req" class="flex items-start gap-2.5 text-[12px] text-slate-600">
                                                        <AppIcon name="checkCircle" :size="14" class="mt-0.5 text-emerald-500" />
                                                        {{ req }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="mt-6 flex flex-wrap gap-4 pt-4 border-t border-slate-100">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Last reviewed</span>
                                                <span class="text-[13px] font-medium text-slate-700">{{ visaType.official_last_reviewed_at || 'Not reviewed yet' }}</span>
                                            </div>
                                            <div v-if="visaType.policy_effective_date" class="flex flex-col">
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Policy effective</span>
                                                <span class="text-[13px] font-medium text-slate-700">{{ visaType.policy_effective_date }}</span>
                                            </div>
                                            <div v-if="visaType.decision_sla_days" class="flex flex-col">
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Decision SLA</span>
                                                <span class="text-[13px] font-medium text-slate-700">{{ visaType.decision_sla_days }} days</span>
                                            </div>
                                            <div v-if="visaType.stay_duration_days" class="flex flex-col">
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Stay Duration</span>
                                                <span class="text-[13px] font-medium text-slate-700">{{ visaType.stay_duration_days }} days</span>
                                            </div>
                                            <div v-if="visaType.validity_months" class="flex flex-col">
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Typical Validity</span>
                                                <span class="text-[13px] font-medium text-slate-700">{{ visaType.validity_months }} months</span>
                                            </div>
                                        </div>
                                        <div v-if="visaType.official_change_notes" class="mt-4 border-t border-slate-100 pt-4">
                                            <h4 class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Recent change notes</h4>
                                            <p class="mt-2 whitespace-pre-line text-[13px] leading-relaxed text-slate-600">{{ visaType.official_change_notes }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="!visaTypes.length" class="rounded-xl border border-dashed border-slate-200 p-12 text-center">
                        <AppIcon name="sparkle" :size="32" class="mx-auto mb-4 text-slate-300" />
                        <p class="text-[14px] font-medium text-slate-900">No visa types configured</p>
                        <p class="mt-1 text-[13px] text-slate-500">Start by adding a visa type for your active countries.</p>
                    </div>

                    <div v-else class="rounded-xl border border-dashed border-slate-200 p-12 text-center">
                        <p class="text-[14px] font-medium text-slate-900">No matching visa types</p>
                        <p class="mt-1 text-[13px] text-slate-500">Try adjusting your filters or search terms.</p>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'countries'" class="max-w-5xl">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-[20px] font-semibold text-slate-900">Target Countries</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Define the countries where you provide visa services.</p>
                    </div>
                    <PrimaryButton icon="plus" @click="openCountryCreate">Add country</PrimaryButton>
                </div>

                <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div v-for="country in countries" :key="country.id" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50/50">
                        <div>
                            <div class="flex items-center gap-3">
                                <p class="text-[14px] font-bold text-slate-900">{{ country.name }}</p>
                                <span :class="country.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                                    {{ country.is_active ? 'Live' : 'Paused' }}
                                </span>
                            </div>
                            <p class="mt-1 text-[12px] text-slate-500">{{ country.slug }} • {{ country.visa_types_count }} visa types</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="ui-button-ghost h-8" @click="openCountryEdit(country)">Edit</button>
                            <button type="button" class="ui-button-danger h-8" @click="destroyResource('settings.countries.destroy', country.id)">Remove</button>
                        </div>
                    </div>

                    <div v-if="!countries.length" class="px-6 py-12 text-center">
                        <AppIcon name="map" :size="32" class="mx-auto mb-4 text-slate-300" />
                        <p class="text-[14px] font-medium text-slate-900">No countries defined yet</p>
                        <p class="mt-1 text-[13px] text-slate-500">Add a country to start configuring visa types.</p>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'workflow'" class="space-y-12">
                <div>
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-slate-900">Workflow Stages</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Define the operational path for cases of this visa type.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <PrimaryButton icon="plus" @click="openWorkflowStageCreate">Add stage</PrimaryButton>
                        </div>
                    </div>

                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <select v-model="selectedVisaTypeId" class="ui-select h-8 min-w-[200px]">
                                <option v-for="visaType in visaTypes" :key="visaType.id" :value="visaType.id">
                                    {{ visaType.country.name }} • {{ visaType.name }}
                                </option>
                            </select>
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

            <div v-if="activeTab === 'checklists'">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-[20px] font-semibold text-slate-900">Document Requirements</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Configure the checklist of documents required for this visa type.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <PrimaryButton icon="plus" @click="openTemplateCreate">Add requirement</PrimaryButton>
                    </div>
                </div>

                 <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <select v-model="selectedVisaTypeId" class="ui-select h-8 min-w-[200px]">
                            <option v-for="visaType in visaTypes" :key="visaType.id" :value="visaType.id">
                                {{ visaType.country.name }} • {{ visaType.name }}
                            </option>
                        </select>
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

            <div v-if="activeTab === 'messaging'">
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

            <!-- ── Gov. Forms tab ──────────────────────────────────────── -->
            <div v-if="activeTab === 'forms'">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-[20px] font-semibold text-slate-900">Government Form Templates</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Upload official PDF forms and map their fields to CRM data. Agents can then download pre-filled forms from any case.</p>
                    </div>
                    <PrimaryButton icon="plus" @click="openFormTemplateCreate">Upload form</PrimaryButton>
                </div>

                <!-- Visa type filter -->
                <div class="mb-5 flex items-center gap-3">
                    <select v-model="formTemplateVisaFilter" class="ui-select h-8 min-w-[220px]">
                        <option value="">All visa types</option>
                        <option v-for="v in visaTypes" :key="v.id" :value="v.id">{{ v.country.name }} • {{ v.name }}</option>
                    </select>
                </div>

                <div v-if="filteredFormTemplates.length" class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white shadow-card overflow-hidden">
                    <div v-for="ft in filteredFormTemplates" :key="ft.id" class="flex items-start justify-between gap-4 px-4 py-4 transition hover:bg-slate-50/50">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[14px] font-bold text-slate-900">{{ ft.name }}</p>
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ ft.visa_type_name }}</span>
                                <span v-if="!ft.is_active" class="rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-bold text-red-600 uppercase tracking-wider">Inactive</span>
                            </div>
                            <p class="mt-1 text-[12px] text-slate-500">{{ ft.original_filename }}</p>
                            <p v-if="ft.description" class="mt-1 text-[12px] text-slate-400">{{ ft.description }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">{{ Object.keys(ft.field_mapping ?? {}).length }} field mappings</p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" class="ui-button-ghost h-8" @click="openFormTemplateEdit(ft)">Edit</button>
                            <button type="button" class="ui-button-danger h-8" @click="destroyResource('settings.form-templates.destroy', ft.id)">Remove</button>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-lg border border-dashed border-slate-200 p-12 text-center">
                    <p class="text-[14px] font-medium text-slate-600">No form templates yet.</p>
                    <p class="mt-1 text-[13px] text-slate-500">Upload a government PDF form to get started.</p>
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
                            <div class="grid gap-5 xl:grid-cols-2">
                                <div>
                                    <InputLabel for="visa_type_official_last_reviewed_at" value="Last reviewed on" />
                                    <input id="visa_type_official_last_reviewed_at" v-model="visaTypeForm.official_last_reviewed_at" type="date" class="ui-input" />
                                    <InputError :message="visaTypeForm.errors.official_last_reviewed_at" />
                                </div>
                                <div>
                                    <InputLabel for="visa_type_policy_effective_date" value="Policy effective date" />
                                    <input id="visa_type_policy_effective_date" v-model="visaTypeForm.policy_effective_date" type="date" class="ui-input" />
                                    <InputError :message="visaTypeForm.errors.policy_effective_date" />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="visa_type_official_requirements" value="Official requirements" />
                                <textarea id="visa_type_official_requirements" v-model="visaTypeForm.official_requirements_input" rows="8" class="ui-textarea" placeholder="One requirement per line"></textarea>
                                <p class="mt-2 text-[12px] text-brand-muted">Keep one requirement on each line so the app can store and display them cleanly.</p>
                                <InputError :message="visaTypeForm.errors.official_requirements" />
                            </div>
                            <div>
                                <InputLabel for="visa_type_official_change_notes" value="Official change notes" />
                                <textarea id="visa_type_official_change_notes" v-model="visaTypeForm.official_change_notes" rows="4" class="ui-textarea" placeholder="Summarize what changed in the policy or official guidance since the last review."></textarea>
                                <InputError :message="visaTypeForm.errors.official_change_notes" />
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

        <!-- ── Government Form Template SlideOver ─────────────────────────── -->
        <SlideOver :show="showFormTemplate" width="wide" @close="showFormTemplate = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Gov. Forms</p>
                            <h2 class="mt-2 text-2xl">{{ editingFormTemplateId ? 'Edit form template' : 'Upload form template' }}</h2>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showFormTemplate = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>

                <!-- Create mode: upload PDF -->
                <form v-if="!editingFormTemplateId" class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveFormTemplate">
                    <div>
                        <InputLabel for="ft_visa_type" value="Visa type" />
                        <select id="ft_visa_type" v-model="formTemplateForm.visa_type_id" class="ui-select">
                            <option v-for="v in visaTypes" :key="v.id" :value="v.id">{{ v.country.name }} • {{ v.name }}</option>
                        </select>
                        <InputError :message="formTemplateForm.errors.visa_type_id" />
                    </div>
                    <div>
                        <InputLabel for="ft_name" value="Template name" />
                        <input id="ft_name" v-model="formTemplateForm.name" class="ui-input" placeholder="e.g. Form 80 – Personal History" />
                        <InputError :message="formTemplateForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="ft_desc" value="Description (optional)" />
                        <input id="ft_desc" v-model="formTemplateForm.description" class="ui-input" placeholder="Short note about this form" />
                    </div>
                    <div>
                        <InputLabel for="ft_pdf" value="PDF file" />
                        <input
                            id="ft_pdf"
                            type="file"
                            accept="application/pdf"
                            class="block w-full text-[13px] text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-[12px] file:font-medium"
                            @change="e => formTemplateForm.pdf = e.target.files[0]"
                        />
                        <p class="mt-1 text-[11px] text-slate-400">Upload an AcroForm PDF (government forms with fillable fields). Max 10 MB.</p>
                        <InputError :message="formTemplateForm.errors.pdf" />
                    </div>

                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-brand-border bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showFormTemplate = false">Cancel</button>
                        <PrimaryButton :loading="formTemplateForm.processing">Upload & continue</PrimaryButton>
                    </div>
                </form>

                <!-- Edit mode: map fields -->
                <form v-else class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveFormTemplate">
                    <div>
                        <InputLabel for="ft_edit_name" value="Template name" />
                        <input id="ft_edit_name" v-model="formTemplateEditForm.name" class="ui-input" />
                        <InputError :message="formTemplateEditForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="ft_edit_desc" value="Description" />
                        <input id="ft_edit_desc" v-model="formTemplateEditForm.description" class="ui-input" />
                    </div>
                    <label class="flex items-center gap-3">
                        <input v-model="formTemplateEditForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-primary" />
                        <span class="text-sm text-brand-text">Template is active</span>
                    </label>

                    <!-- Field mapper -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <InputLabel value="Field mappings" />
                            <button type="button" class="text-[12px] font-medium text-brand-primary hover:underline" @click="addEditMappingRow">+ Add row</button>
                        </div>
                        <p class="mb-3 text-[11px] text-slate-400 leading-relaxed">
                            Left column: exact PDF field name (from the form's AcroForm fields).<br />
                            Right column: the CRM data path to fill it with.
                        </p>

                        <div v-if="Object.keys(formTemplateEditForm.field_mapping).length" class="space-y-2">
                            <div v-for="(crmPath, pdfField) in formTemplateEditForm.field_mapping" :key="pdfField" class="grid grid-cols-[1fr_1fr_auto] items-center gap-2">
                                <input
                                    :value="pdfField"
                                    class="ui-input font-mono text-[12px]"
                                    placeholder="PDF field name"
                                    @change="updateMappingKey(formTemplateEditForm, pdfField, $event.target.value)"
                                />
                                <select
                                    :value="crmPath"
                                    class="ui-select text-[12px]"
                                    @change="formTemplateEditForm.field_mapping[pdfField] = $event.target.value"
                                >
                                    <option value="">— Select CRM field —</option>
                                    <template v-for="(fields, group) in availableFields" :key="group">
                                        <optgroup :label="group.charAt(0).toUpperCase() + group.slice(1)">
                                            <option v-for="(label, path) in fields" :key="path" :value="path">{{ label }}</option>
                                        </optgroup>
                                    </template>
                                </select>
                                <button type="button" class="rounded p-1 text-slate-400 hover:text-red-500 transition" @click="removeMappingRow(formTemplateEditForm, pdfField)">
                                    <AppIcon name="trash" :size="14" />
                                </button>
                            </div>
                        </div>
                        <div v-else class="rounded-lg border border-dashed border-slate-200 p-6 text-center">
                            <p class="text-[13px] text-slate-500">No mappings yet. Click "+ Add row" to map PDF fields to CRM data.</p>
                        </div>
                    </div>

                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-brand-border bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showFormTemplate = false">Cancel</button>
                        <PrimaryButton :loading="formTemplateEditForm.processing">Save mappings</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>
    </AuthenticatedLayout>
</template>
