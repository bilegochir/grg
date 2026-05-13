<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SlideOver from '@/Components/SlideOver.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TagBadge from '@/Components/TagBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    lead: Object,
    statuses: Array,
    sources: Array,
    tags: Array,
});

const showEdit = ref(false);
const showBackgroundEdit = ref(false);

const blankEducation = () => ({
    institution: '',
    degree: '',
    field_of_study: '',
    start_date: '',
    end_date: '',
    notes: '',
});

const blankExperience = () => ({
    company: '',
    title: '',
    location: '',
    start_date: '',
    end_date: '',
    is_current: false,
    notes: '',
});

const editForm = useForm({
    first_name: props.lead.name.split(' ')[0] ?? '',
    last_name: props.lead.name.split(' ').slice(1).join(' '),
    email: props.lead.email ?? '',
    phone: props.lead.phone ?? '',
    date_of_birth: props.lead.date_of_birth ?? '',
    source: props.lead.source.value,
    country_of_citizenship: props.lead.country_of_citizenship ?? '',
    interested_visa_type: props.lead.interested_visa_type ?? '',
    education_history: props.lead.education_history?.length ? props.lead.education_history.map((item) => ({
        institution: item.institution ?? '',
        degree: item.degree ?? '',
        field_of_study: item.field_of_study ?? '',
        start_date: item.start_date ?? '',
        end_date: item.end_date ?? '',
        notes: item.notes ?? '',
    })) : [],
    work_experience: props.lead.work_experience?.length ? props.lead.work_experience.map((item) => ({
        company: item.company ?? '',
        title: item.title ?? '',
        location: item.location ?? '',
        start_date: item.start_date ?? '',
        end_date: item.end_date ?? '',
        is_current: Boolean(item.is_current),
        notes: item.notes ?? '',
    })) : [],
    tag_ids: props.lead.tags.map(t => t.id),
});

const addEducation = () => {
    editForm.education_history.push(blankEducation());
};

const removeEducation = (index) => {
    editForm.education_history.splice(index, 1);
};

const addExperience = () => {
    editForm.work_experience.push(blankExperience());
};

const removeExperience = (index) => {
    editForm.work_experience.splice(index, 1);
};

const submitEdit = () => {
    editForm.patch(route('leads.update', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEdit.value = false;
            showBackgroundEdit.value = false;
        },
    });
};

// ── Unified timeline (notes + status changes + activity), newest first ─────
const timeline = computed(() => {
    const notes = (props.lead.notes || []).map((n) => ({
        ...n,
        _type: 'note',
        _ts: new Date(n.created_at).getTime(),
    }));
    const statusHistory = (props.lead.status_history || []).map((s) => ({
        ...s,
        _type: 'status',
        _ts: new Date(s.changed_at).getTime(),
    }));
    const activities = (props.lead.activities || []).map((a) => ({
        ...a,
        _type: 'activity',
        _ts: new Date(a.created_at).getTime(),
    }));
    return [...notes, ...statusHistory, ...activities].sort((a, b) => b._ts - a._ts);
});

const timelineTypeLabel = (type) => {
    if (type === 'note') return 'Note';
    if (type === 'status') return 'Stage change';
    return 'Activity';
};

const timelineRowTone = (type) => {
    if (type === 'status') return 'bg-blue-50/60';
    if (type === 'activity') return 'bg-slate-50';
    return 'bg-white';
};

const timelineTypeTone = (type) => {
    if (type === 'status') return 'text-blue-700';
    if (type === 'activity') return 'text-slate-600';
    return 'text-amber-700';
};

const timelineBody = (entry) => {
    if (entry._type === 'note') return entry.body;
    if (entry._type === 'status') return `${entry.from_status || 'Created'} -> ${entry.to_status}`;
    return entry.description;
};

const timelineActor = (entry) => {
    if (entry._type === 'note') return entry.author;
    if (entry._type === 'status') return entry.changed_by;
    return entry.causer;
};

const timelineTimestamp = (entry) => {
    if (entry._type === 'status') return entry.changed_at;
    return entry.created_at;
};

// Pre-filled fields summary for the convert CTA
const prefilledCount = computed(() => {
    const fields = [
        props.lead.name,
        props.lead.email,
        props.lead.phone,
        props.lead.country_of_citizenship,
    ];
    return fields.filter(Boolean).length;
});

const leadOverviewCompletion = computed(() => {
    const checks = [
        Boolean(props.lead.email),
        Boolean(props.lead.phone),
        Boolean(props.lead.source?.value),
        Boolean(props.lead.interested_visa_type),
        Boolean(props.lead.country_of_citizenship),
        Boolean(props.lead.education_history?.length),
        Boolean(props.lead.work_experience?.length),
    ];

    return {
        completed: checks.filter(Boolean).length,
        total: checks.length,
    };
});

const leadOverviewTone = computed(() => {
    const ratio = leadOverviewCompletion.value.completed / leadOverviewCompletion.value.total;

    if (ratio >= 0.85) {
        return {
            badge: 'Ready to convert',
            badgeClass: 'bg-emerald-100 text-emerald-700',
            panelClass: 'border-emerald-200 bg-emerald-50/70',
        };
    }

    if (ratio >= 0.45) {
        return {
            badge: 'Needs a little more detail',
            badgeClass: 'bg-amber-100 text-amber-700',
            panelClass: 'border-amber-200 bg-amber-50/70',
        };
    }

    return {
        badge: 'Early stage lead',
        badgeClass: 'bg-slate-200 text-slate-700',
        panelClass: 'border-slate-200 bg-slate-50/80',
    };
});

const formatDateRange = (start, end, current = false) => {
    if (!start && !end && !current) return 'Dates not added';
    if (start && current) return `${start} - Present`;
    if (start && end) return `${start} - ${end}`;
    return start || end || 'Dates not added';
};

// ── Status form ────────────────────────────────────────────────────────────
const statusForm = useForm({
    status: props.lead.status.value,
});

const submitStatus = () => {
    statusForm.patch(route('leads.status.update', props.lead.id), {
        preserveScroll: true,
    });
};

// ── Note form ──────────────────────────────────────────────────────────────
const showNoteForm = ref(false);

const noteForm = useForm({ body: '' });

const submitNote = () => {
    noteForm.post(route('leads.notes.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => {
            noteForm.reset();
            showNoteForm.value = false;
        },
    });
};

// ── Convert form ───────────────────────────────────────────────────────────
const showConvert = ref(false);

const convertForm = useForm({
    first_name:          props.lead.name.split(' ')[0] ?? '',
    last_name:           props.lead.name.split(' ').slice(1).join(' '),
    email:               props.lead.email ?? '',
    phone:               props.lead.phone ?? '',
    date_of_birth:       props.lead.date_of_birth ?? '',
    nationality:         props.lead.country_of_citizenship ?? '',
    country_of_residence: '',
    passport_number:     '',
    passport_country:    '',
    passport_issued_at:  '',
    passport_expires_at: '',
    travel_history:      [],
});

const convert = () => {
    convertForm.post(route('leads.convert', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => { showConvert.value = false; },
    });
};

// Track which convert form section is complete for visual feedback
const identityComplete = computed(() =>
    convertForm.first_name && convertForm.last_name && convertForm.email,
);
const passportAny = computed(() =>
    convertForm.passport_number || convertForm.passport_country || convertForm.passport_expires_at,
);
</script>

<template>
    <Head :title="lead.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Lead Profile</p>
                    <h1 class="text-[32px] font-bold tracking-tight text-slate-900 leading-tight">{{ lead.name }}</h1>
                    
                    <div class="mt-4 flex flex-wrap items-center gap-4">
                        <div 
                            class="rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider"
                            :class="{
                                'bg-slate-100 text-slate-500': lead.status.value === 'new',
                                'bg-blue-50 text-blue-700': lead.status.value === 'contacted',
                                'bg-emerald-50 text-emerald-700': lead.status.value === 'converted',
                                'bg-rose-50 text-rose-700': lead.status.value === 'closed',
                            }"
                        >
                            {{ lead.status.label }}
                        </div>

                        <div class="h-4 w-px bg-slate-200"></div>

                        <div class="flex items-center gap-3">
                            <a v-if="lead.email" :href="`mailto:${lead.email}`" class="text-slate-500 hover:text-brand-primary transition-colors">
                                <AppIcon name="email" :size="16" />
                            </a>
                            <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="text-slate-500 hover:text-brand-primary transition-colors">
                                <AppIcon name="phone" :size="16" />
                            </a>
                        </div>

                        <div v-if="lead.tags.length" class="flex gap-1.5">
                            <TagBadge v-for="tag in lead.tags" :key="tag.id" :label="tag.name" :color="tag.color" class="!text-[10px]" />
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <Link
                        v-if="lead.applicant"
                        :href="route('applicants.show', lead.applicant.id)"
                        class="ui-button-secondary !h-10"
                    >
                        View Applicant
                    </Link>
                    <PrimaryButton
                        v-if="!lead.applicant"
                        class="!h-10"
                        @click="showConvert = true"
                    >
                        Convert to Applicant
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="ui-detail-grid">

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- LEFT COLUMN                                              -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="space-y-6">

                <!-- Overview -->
                <AppCard title="Overview" subtitle="Context your team needs before they call, email, or convert.">
                    <template #action>
                        <button type="button" class="ui-button-ghost !h-8 px-2 text-[12px]" @click="showEdit = true">
                            Edit lead
                        </button>
                    </template>
                    <div class="space-y-6">
                        <div :class="['rounded-2xl border p-4 sm:p-5', leadOverviewTone.panelClass]">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="max-w-2xl">
                                    <p class="ui-kicker !mb-2">Lead health</p>
                                    <p class="text-sm leading-6 text-slate-600">
                                        {{ lead.applicant ? 'This lead has already been converted into an applicant record.' : 'A quick read on whether the team has enough detail to follow up or convert with confidence.' }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="leadOverviewTone.badgeClass"
                                >
                                    {{ leadOverviewTone.badge }}
                                </span>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-500">
                                <span>{{ leadOverviewCompletion.completed }} of {{ leadOverviewCompletion.total }} key fields filled</span>
                                <span class="text-slate-300">•</span>
                                <span>{{ [lead.email, lead.phone].filter(Boolean).length }} contact method{{ [lead.email, lead.phone].filter(Boolean).length === 1 ? '' : 's' }}</span>
                                <span class="text-slate-300">•</span>
                                <span>{{ (lead.education_history?.length || 0) + (lead.work_experience?.length || 0) }} background entr{{ ((lead.education_history?.length || 0) + (lead.work_experience?.length || 0)) === 1 ? 'y' : 'ies' }}</span>
                            </div>
                        </div>

                        <div class="grid gap-4 xl:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="ui-kicker !mb-0">Contact</p>
                                    <span class="text-xs font-medium text-slate-500">
                                        {{ [lead.email, lead.phone].filter(Boolean).length }}/2 available
                                    </span>
                                </div>
                                <div class="mt-4 space-y-3 text-sm">
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Email</span>
                                        <a
                                            v-if="lead.email"
                                            :href="`mailto:${lead.email}`"
                                            class="text-right text-brand-primary hover:underline"
                                        >
                                            {{ lead.email }}
                                        </a>
                                        <span v-else class="text-right text-slate-400">No email on file yet</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Date of birth</span>
                                        <span class="text-right text-slate-900">{{ lead.date_of_birth || 'Date of birth not captured yet' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <span class="font-medium text-slate-500">Phone</span>
                                        <a
                                            v-if="lead.phone"
                                            :href="`tel:${lead.phone}`"
                                            class="text-right text-brand-primary hover:underline"
                                        >
                                            {{ lead.phone }}
                                        </a>
                                        <span v-else class="text-right text-slate-400">No phone number on file yet</span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="ui-kicker !mb-0">Visa interest</p>
                                    <span class="text-xs font-medium text-slate-500">
                                        {{ lead.interested_visa_type ? 'Qualified' : 'Needs confirmation' }}
                                    </span>
                                </div>
                                <div class="mt-4 space-y-3 text-sm">
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Source</span>
                                        <span class="text-right text-slate-900">{{ lead.source.label }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Visa type</span>
                                        <span class="text-right text-slate-900">{{ lead.interested_visa_type || 'Visa type still to be confirmed' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <span class="font-medium text-slate-500">Citizenship</span>
                                        <span class="text-right text-slate-900">{{ lead.country_of_citizenship || 'Citizenship not captured yet' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Background" subtitle="Education and work history captured while qualifying the lead.">
                    <template #action>
                        <button type="button" class="ui-button-ghost !h-8 px-2 text-[12px]" @click="showBackgroundEdit = true">
                            Edit background
                        </button>
                    </template>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="ui-kicker !mb-0">Education</p>
                                <span class="text-xs text-brand-muted">{{ lead.education_history?.length || 0 }} entries</span>
                            </div>
                            <div v-if="lead.education_history?.length" class="space-y-3">
                                <div v-for="(item, index) in lead.education_history" :key="`education-view-${index}`" class="rounded-lg border border-brand-border px-4 py-4">
                                    <p class="font-medium text-brand-text">{{ item.institution || 'Institution not added' }}</p>
                                    <p class="mt-1 text-sm text-brand-muted">
                                        {{ item.degree || 'Degree not added' }}
                                        <span v-if="item.field_of_study">• {{ item.field_of_study }}</span>
                                    </p>
                                    <p class="mt-2 text-sm text-brand-muted">{{ formatDateRange(item.start_date, item.end_date) }}</p>
                                    <p v-if="item.notes" class="mt-2 text-sm leading-6 text-brand-text">{{ item.notes }}</p>
                                </div>
                            </div>
                            <EmptyState v-else icon="document" title="No education added" description="Add school or university background when it matters for this pathway." />
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="ui-kicker !mb-0">Work Experience</p>
                                <span class="text-xs text-brand-muted">{{ lead.work_experience?.length || 0 }} entries</span>
                            </div>
                            <div v-if="lead.work_experience?.length" class="space-y-3">
                                <div v-for="(item, index) in lead.work_experience" :key="`experience-view-${index}`" class="rounded-lg border border-brand-border px-4 py-4">
                                    <p class="font-medium text-brand-text">{{ item.company || 'Company not added' }}</p>
                                    <p class="mt-1 text-sm text-brand-muted">
                                        {{ item.title || 'Role not added' }}
                                        <span v-if="item.location">• {{ item.location }}</span>
                                    </p>
                                    <p class="mt-2 text-sm text-brand-muted">{{ formatDateRange(item.start_date, item.end_date, item.is_current) }}</p>
                                    <p v-if="item.notes" class="mt-2 text-sm leading-6 text-brand-text">{{ item.notes }}</p>
                                </div>
                            </div>
                            <EmptyState v-else icon="document" title="No work history added" description="Add employer background when it helps assess fit or eligibility." />
                        </div>
                    </div>
                </AppCard>

                <!-- Unified timeline: notes + status changes + activity -->
                <AppCard title="Timeline" subtitle="Notes, stage changes, and activity in one chronological thread.">
                    <template #action>
                        <button
                            type="button"
                            class="ui-button-secondary"
                            @click="showNoteForm = !showNoteForm"
                        >
                            {{ showNoteForm ? 'Cancel' : '+ Add note' }}
                        </button>
                    </template>

                    <!-- Inline note form -->
                    <div v-if="showNoteForm" class="mb-5 rounded-lg border border-dashed border-brand-border bg-brand-neutral p-4">
                        <form class="space-y-3" @submit.prevent="submitNote">
                            <div>
                                <InputLabel for="note_body" value="What should the team know?" />
                                <textarea
                                    id="note_body"
                                    v-model="noteForm.body"
                                    rows="4"
                                    class="ui-textarea"
                                    placeholder="What was said, promised, or still needs follow-up…"
                                    autofocus
                                ></textarea>
                                <InputError :message="noteForm.errors.body" />
                            </div>
                            <div class="flex gap-3">
                                <PrimaryButton :loading="noteForm.processing">Save note</PrimaryButton>
                                <button type="button" class="text-sm text-brand-muted hover:underline" @click="showNoteForm = false">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Timeline entries -->
                    <div v-if="timeline.length" class="space-y-1.5">
                        <div
                            v-for="entry in timeline"
                            :key="`${entry._type}-${entry.id}`"
                            class="rounded-2xl px-3 py-3"
                            :class="timelineRowTone(entry._type)"
                        >
                            <div class="flex items-center justify-between gap-3 text-[12px]">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="font-medium text-brand-text">{{ timelineActor(entry) }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="font-medium" :class="timelineTypeTone(entry._type)">
                                        {{ timelineTypeLabel(entry._type) }}
                                    </span>
                                </div>
                                <span class="shrink-0 text-brand-muted">{{ timelineTimestamp(entry) }}</span>
                            </div>

                            <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-brand-text">
                                {{ timelineBody(entry) }}
                            </p>
                        </div>
                    </div>

                    <EmptyState
                        v-else
                        icon="note"
                        title="Nothing logged yet"
                        description="Notes, stage changes, and automated actions will all build up here."
                    />
                </AppCard>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- RIGHT SIDEBAR                                            -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="space-y-6">

                <!-- Update stage -->
                <AppCard title="Update stage" subtitle="Keep the pipeline honest so the team sees what needs to happen next.">
                    <form class="space-y-4" @submit.prevent="submitStatus">
                        <div>
                            <InputLabel for="status" value="Current stage" />
                            <select id="status" v-model="statusForm.status" class="ui-select">
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>
                        <PrimaryButton :loading="statusForm.processing">Save stage</PrimaryButton>
                    </form>
                </AppCard>

                <!-- Convert CTA — context-rich, not buried -->
                <AppCard
                    v-if="!lead.applicant"
                    title="Convert to applicant"
                    subtitle="Open a fuller profile when the conversation is ready."
                >
                    <div class="space-y-4">
                        <!-- Pre-fill preview chips -->
                        <div class="rounded-lg bg-brand-neutral px-4 py-4">
                            <p class="mb-3 text-xs font-medium uppercase tracking-wide text-brand-muted">
                                {{ prefilledCount }} field{{ prefilledCount === 1 ? '' : 's' }} pre-filled from this lead
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-if="lead.name"
                                    class="rounded-full bg-white px-3 py-1 text-xs font-medium text-brand-text ring-1 ring-black/6"
                                >
                                    {{ lead.name }}
                                </span>
                                <span
                                    v-if="lead.email"
                                    class="rounded-full bg-white px-3 py-1 text-xs font-medium text-brand-text ring-1 ring-black/6"
                                >
                                    {{ lead.email }}
                                </span>
                                <span
                                    v-if="lead.phone"
                                    class="rounded-full bg-white px-3 py-1 text-xs font-medium text-brand-text ring-1 ring-black/6"
                                >
                                    {{ lead.phone }}
                                </span>
                                <span
                                    v-if="lead.country_of_citizenship"
                                    class="rounded-full bg-white px-3 py-1 text-xs font-medium text-brand-text ring-1 ring-black/6"
                                >
                                    {{ lead.country_of_citizenship }}
                                </span>
                            </div>
                        </div>
                        <PrimaryButton @click="showConvert = true">
                            Open applicant form
                        </PrimaryButton>
                    </div>
                </AppCard>

                <!-- Already converted notice -->
                <div
                    v-else
                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4"
                >
                    <p class="text-sm font-medium text-emerald-700">Converted to applicant</p>
                    <p class="mt-1 text-sm text-emerald-600">
                        This lead has an active applicant profile.
                    </p>
                    <Link
                        :href="route('applicants.show', lead.applicant.id)"
                        class="mt-3 inline-block text-sm font-medium text-emerald-700 hover:underline"
                    >
                        View applicant profile →
                    </Link>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!-- EDIT LEAD SLIDE-OVER                                           -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <SlideOver :show="showEdit" width="wide" @close="showEdit = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 z-10 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Edit lead</p>
                            <h2 class="mt-2 text-2xl">Update lead information</h2>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showEdit = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>

                <form class="flex-1 space-y-6 overflow-y-auto px-6 py-6" @submit.prevent="submitEdit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="edit_first_name" value="First name" />
                            <TextInput id="edit_first_name" v-model="editForm.first_name" />
                            <InputError :message="editForm.errors.first_name" />
                        </div>
                        <div>
                            <InputLabel for="edit_last_name" value="Last name" />
                            <TextInput id="edit_last_name" v-model="editForm.last_name" />
                            <InputError :message="editForm.errors.last_name" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="edit_email" value="Email" />
                            <TextInput id="edit_email" v-model="editForm.email" type="email" />
                            <InputError :message="editForm.errors.email" />
                        </div>
                        <div>
                            <InputLabel for="edit_phone" value="Phone" />
                            <TextInput id="edit_phone" v-model="editForm.phone" />
                            <InputError :message="editForm.errors.phone" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="edit_date_of_birth" value="Date of birth" />
                        <TextInput id="edit_date_of_birth" v-model="editForm.date_of_birth" type="date" />
                        <InputError :message="editForm.errors.date_of_birth" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="edit_source" value="Source" />
                            <select id="edit_source" v-model="editForm.source" class="ui-select">
                                <option v-for="source in sources" :key="source.value" :value="source.value">
                                    {{ source.label }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.source" />
                        </div>
                        <div>
                            <InputLabel for="edit_country_of_citizenship" value="Country of citizenship" />
                            <TextInput id="edit_country_of_citizenship" v-model="editForm.country_of_citizenship" />
                            <InputError :message="editForm.errors.country_of_citizenship" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="edit_interested_visa_type" value="Interested visa type" />
                        <TextInput id="edit_interested_visa_type" v-model="editForm.interested_visa_type" />
                        <InputError :message="editForm.errors.interested_visa_type" />
                    </div>

                    <div>
                        <InputLabel value="Tags" />
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="tag in tags"
                                :key="tag.id"
                                class="inline-flex items-center gap-2 rounded-full border border-brand-border px-3 py-2 text-sm text-brand-text cursor-pointer hover:bg-slate-50 transition-colors"
                            >
                                <input
                                    v-model="editForm.tag_ids"
                                    type="checkbox"
                                    :value="tag.id"
                                    class="rounded border-brand-border text-brand-primary focus:ring-brand-primary"
                                />
                                {{ tag.name }}
                            </label>
                        </div>
                    </div>
                </form>

                <div class="sticky bottom-0 flex items-center justify-between gap-3 border-t border-brand-border bg-white px-6 py-4">
                    <button type="button" class="ui-button-ghost" @click="showEdit = false">Cancel</button>
                    <PrimaryButton :loading="editForm.processing" @click="submitEdit">
                        Save changes
                    </PrimaryButton>
                </div>
            </div>
        </SlideOver>

        <SlideOver :show="showBackgroundEdit" width="wide" @close="showBackgroundEdit = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 z-10 border-b border-brand-border bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-kicker">Edit background</p>
                            <h2 class="mt-2 text-2xl">Update education and work history</h2>
                        </div>
                        <button class="rounded-md p-2 text-brand-muted hover:bg-brand-neutral" @click="showBackgroundEdit = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>

                <form class="flex-1 space-y-6 overflow-y-auto px-6 py-6" @submit.prevent="submitEdit">
                    <div class="space-y-4 rounded-xl border border-brand-border bg-brand-neutral/50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-brand-text">Education</p>
                                <p class="mt-1 text-sm text-brand-muted">Capture school, degree, and study details while qualifying the lead.</p>
                            </div>
                            <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="addEducation">Add education</button>
                        </div>

                        <div v-if="editForm.education_history.length" class="space-y-4">
                            <div v-for="(item, index) in editForm.education_history" :key="`edit-education-${index}`" class="rounded-lg border border-brand-border bg-white p-4">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-brand-text">Education {{ index + 1 }}</p>
                                    <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="removeEducation(index)">Remove</button>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`edit_education_institution_${index}`" value="Institution" />
                                        <TextInput :id="`edit_education_institution_${index}`" v-model="item.institution" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`edit_education_degree_${index}`" value="Degree" />
                                        <TextInput :id="`edit_education_degree_${index}`" v-model="item.degree" />
                                    </div>
                                </div>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`edit_education_field_${index}`" value="Field of study" />
                                        <TextInput :id="`edit_education_field_${index}`" v-model="item.field_of_study" />
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel :for="`edit_education_start_${index}`" value="Start date" />
                                            <TextInput :id="`edit_education_start_${index}`" v-model="item.start_date" type="date" />
                                        </div>
                                        <div>
                                            <InputLabel :for="`edit_education_end_${index}`" value="End date" />
                                            <TextInput :id="`edit_education_end_${index}`" v-model="item.end_date" type="date" />
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <InputLabel :for="`edit_education_notes_${index}`" value="Notes" />
                                    <textarea :id="`edit_education_notes_${index}`" v-model="item.notes" rows="3" class="ui-textarea" placeholder="Achievements, graduation status, or relevant details."></textarea>
                                </div>
                            </div>
                        </div>
                        <EmptyState v-else icon="document" title="No education added" description="Add school or university background when it matters for this pathway." />
                    </div>

                    <div class="space-y-4 rounded-xl border border-brand-border bg-brand-neutral/50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-brand-text">Work experience</p>
                                <p class="mt-1 text-sm text-brand-muted">Capture current and past roles that help assess pathway fit.</p>
                            </div>
                            <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="addExperience">Add experience</button>
                        </div>

                        <div v-if="editForm.work_experience.length" class="space-y-4">
                            <div v-for="(item, index) in editForm.work_experience" :key="`edit-experience-${index}`" class="rounded-lg border border-brand-border bg-white p-4">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-brand-text">Experience {{ index + 1 }}</p>
                                    <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="removeExperience(index)">Remove</button>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`edit_experience_company_${index}`" value="Company" />
                                        <TextInput :id="`edit_experience_company_${index}`" v-model="item.company" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`edit_experience_title_${index}`" value="Job title" />
                                        <TextInput :id="`edit_experience_title_${index}`" v-model="item.title" />
                                    </div>
                                </div>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`edit_experience_location_${index}`" value="Location" />
                                        <TextInput :id="`edit_experience_location_${index}`" v-model="item.location" />
                                    </div>
                                    <label class="flex items-center gap-2 rounded-lg border border-brand-border px-3 py-2 text-sm text-brand-text">
                                        <input v-model="item.is_current" type="checkbox" class="rounded border-brand-border text-brand-primary focus:ring-brand-primary" />
                                        Current role
                                    </label>
                                </div>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`edit_experience_start_${index}`" value="Start date" />
                                        <TextInput :id="`edit_experience_start_${index}`" v-model="item.start_date" type="date" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`edit_experience_end_${index}`" value="End date" />
                                        <TextInput :id="`edit_experience_end_${index}`" v-model="item.end_date" :disabled="item.is_current" type="date" />
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <InputLabel :for="`edit_experience_notes_${index}`" value="Notes" />
                                    <textarea :id="`edit_experience_notes_${index}`" v-model="item.notes" rows="3" class="ui-textarea" placeholder="Responsibilities, accomplishments, or relevance to the planned visa route."></textarea>
                                </div>
                            </div>
                        </div>
                        <EmptyState v-else icon="document" title="No work history added" description="Add employer background when it helps assess fit or eligibility." />
                    </div>
                </form>

                <div class="sticky bottom-0 flex items-center justify-between gap-3 border-t border-brand-border bg-white px-6 py-4">
                    <button type="button" class="ui-button-ghost" @click="showBackgroundEdit = false">Cancel</button>
                    <PrimaryButton :loading="editForm.processing" @click="submitEdit">
                        Save background
                    </PrimaryButton>
                </div>
            </div>
        </SlideOver>

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!-- CONVERT SLIDE-OVER                                             -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <SlideOver :show="showConvert" width="wide" @close="showConvert = false">
            <div class="flex h-full flex-col">
                <!-- Header -->
                <div class="sticky top-0 z-10 border-b border-brand-border bg-white px-6 py-5">
                    <p class="ui-kicker">Convert lead</p>
                    <h2 class="mt-1 text-2xl">Create applicant profile</h2>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">
                        Passport and travel details can be added later — fill in what you have now.
                    </p>
                </div>

                <!-- Form body -->
                <form class="flex-1 overflow-y-auto" @submit.prevent="convert">
                    <div class="space-y-8 px-6 py-6">

                        <!-- Section: Personal details -->
                        <div>
                            <div class="mb-4 flex items-center gap-3">
                                <p class="text-sm font-medium text-brand-text">Personal details</p>
                                <span
                                    v-if="identityComplete"
                                    class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700"
                                >
                                    Complete
                                </span>
                                <div class="flex-1 border-t border-brand-border"></div>
                            </div>
                            <div class="space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="first_name" value="First name" />
                                        <TextInput id="first_name" v-model="convertForm.first_name" />
                                        <InputError :message="convertForm.errors.first_name" />
                                    </div>
                                    <div>
                                        <InputLabel for="last_name" value="Last name" />
                                        <TextInput id="last_name" v-model="convertForm.last_name" />
                                        <InputError :message="convertForm.errors.last_name" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Contact -->
                        <div>
                            <div class="mb-4 flex items-center gap-3">
                                <p class="text-sm font-medium text-brand-text">Contact</p>
                                <div class="flex-1 border-t border-brand-border"></div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput id="email" v-model="convertForm.email" type="email" />
                                    <InputError :message="convertForm.errors.email" />
                                </div>
                                <div>
                                    <InputLabel for="phone" value="Phone" />
                                    <TextInput id="phone" v-model="convertForm.phone" />
                                    <InputError :message="convertForm.errors.phone" />
                                </div>
                            </div>
                        </div>

                        <!-- Section: Residency -->
                        <div>
                            <div class="mb-4 flex items-center gap-3">
                                <p class="text-sm font-medium text-brand-text">Residency</p>
                                <div class="flex-1 border-t border-brand-border"></div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="nationality" value="Nationality" />
                                    <TextInput id="nationality" v-model="convertForm.nationality" />
                                    <InputError :message="convertForm.errors.nationality" />
                                </div>
                                <div>
                                    <InputLabel for="country_of_residence" value="Country of residence" />
                                    <TextInput id="country_of_residence" v-model="convertForm.country_of_residence" />
                                    <InputError :message="convertForm.errors.country_of_residence" />
                                </div>
                            </div>
                        </div>

                        <!-- Section: Passport (optional) -->
                        <div>
                            <div class="mb-4 flex items-center gap-3">
                                <p class="text-sm font-medium text-brand-text">Passport</p>
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">Optional</span>
                                <span
                                    v-if="passportAny"
                                    class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700"
                                >
                                    Partially filled
                                </span>
                                <div class="flex-1 border-t border-brand-border"></div>
                            </div>
                            <div class="space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="passport_number" value="Passport number" />
                                        <TextInput id="passport_number" v-model="convertForm.passport_number" />
                                        <InputError :message="convertForm.errors.passport_number" />
                                    </div>
                                    <div>
                                        <InputLabel for="passport_country" value="Passport country" />
                                        <TextInput id="passport_country" v-model="convertForm.passport_country" />
                                        <InputError :message="convertForm.errors.passport_country" />
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="passport_issued_at" value="Issued on" />
                                        <TextInput id="passport_issued_at" v-model="convertForm.passport_issued_at" type="date" />
                                        <InputError :message="convertForm.errors.passport_issued_at" />
                                    </div>
                                    <div>
                                        <InputLabel for="passport_expires_at" value="Expires on" />
                                        <TextInput id="passport_expires_at" v-model="convertForm.passport_expires_at" type="date" />
                                        <InputError :message="convertForm.errors.passport_expires_at" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky footer -->
                    <div class="sticky bottom-0 z-10 flex items-center justify-between gap-3 border-t border-brand-border bg-white px-6 py-4">
                        <button type="button" class="text-sm text-brand-muted hover:underline" @click="showConvert = false">
                            Cancel
                        </button>
                        <PrimaryButton :loading="convertForm.processing">
                            Create applicant profile
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>
    </AuthenticatedLayout>
</template>
