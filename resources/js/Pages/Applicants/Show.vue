<script setup>
import AppCard from '@/Components/AppCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TagBadge from '@/Components/TagBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    applicant: Object,
});

const blankTravelHistory = () => ({
    country: '',
    purpose: '',
    year: '',
});

// ── Derived helpers ────────────────────────────────────────────────────────

const activeCaseCount = computed(() => props.applicant.visa_cases.length);

const identityFields = computed(() => ([
    props.applicant.email,
    props.applicant.phone,
    props.applicant.nationality,
    props.applicant.country_of_residence,
]));

const passportFields = computed(() => ([
    props.applicant.passport_number,
    props.applicant.passport_country,
    props.applicant.passport_issued_at,
    props.applicant.passport_expires_at,
]));

const identityFieldCount = computed(() => identityFields.value.filter(Boolean).length);
const passportFieldCount = computed(() => passportFields.value.filter(Boolean).length);

const identityStatus = computed(() => {
    if (identityFieldCount.value === identityFields.value.length) {
        return {
            label: 'Complete',
            className: 'bg-emerald-100 text-emerald-700',
            helper: 'The core identity profile is fully captured.',
        };
    }

    if (identityFieldCount.value > 0) {
        return {
            label: 'Partial',
            className: 'bg-amber-100 text-amber-700',
            helper: 'A few profile details still need to be filled in.',
        };
    }

    return {
        label: 'Missing',
        className: 'bg-slate-200 text-slate-700',
        helper: 'No identity details have been added yet.',
    };
});

const passportStatus = computed(() => {
    if (passportFieldCount.value === passportFields.value.length) {
        return {
            label: 'Complete',
            className: 'bg-emerald-100 text-emerald-700',
            helper: 'All core passport details are on file.',
        };
    }

    if (passportFieldCount.value > 0) {
        return {
            label: 'Partial',
            className: 'bg-amber-100 text-amber-700',
            helper: 'Some passport details are still missing.',
        };
    }

    return {
        label: 'Missing',
        className: 'bg-slate-200 text-slate-700',
        helper: 'No passport details have been added yet.',
    };
});

const passportExpiringSoon = computed(() => {
    if (!props.applicant.passport_expires_at) return false;
    const expiry = new Date(props.applicant.passport_expires_at);
    const sixMonthsOut = new Date();
    sixMonthsOut.setMonth(sixMonthsOut.getMonth() + 6);
    return expiry <= sixMonthsOut;
});

// Unified activity + notes thread, newest first
const unifiedTimeline = computed(() => {
    const notes = (props.applicant.notes || []).map((n) => ({
        ...n,
        _type: 'note',
        _ts: new Date(n.created_at).getTime(),
    }));
    const activities = (props.applicant.activities || []).map((a) => ({
        ...a,
        _type: 'activity',
        _ts: new Date(a.created_at).getTime(),
    }));
    return [...notes, ...activities].sort((a, b) => b._ts - a._ts);
});

const timelineTypeLabel = (type) => (type === 'note' ? 'Note' : 'Activity');

const timelineRowTone = (type) => (type === 'note' ? 'bg-white' : 'bg-slate-50');

const timelineTypeTone = (type) => (type === 'note' ? 'text-amber-700' : 'text-slate-600');

const timelineBody = (entry) => (entry._type === 'note' ? entry.body : entry.description);

const timelineActor = (entry) => (entry._type === 'note' ? entry.author : entry.causer);

const showTravelHistoryForm = ref(false);
const travelHistoryForm = useForm({
    travel_history: props.applicant.travel_history?.length
        ? props.applicant.travel_history.map((entry) => ({
              country: entry.country ?? '',
              purpose: entry.purpose ?? '',
              year: entry.year ?? '',
          }))
        : [],
});

const addTravelHistoryEntry = () => {
    travelHistoryForm.travel_history.push(blankTravelHistory());
};

const removeTravelHistoryEntry = (index) => {
    travelHistoryForm.travel_history.splice(index, 1);
};

const submitTravelHistory = () => {
    travelHistoryForm.patch(route('applicants.travel-history.update', props.applicant.id), {
        preserveScroll: true,
        onSuccess: () => {
            showTravelHistoryForm.value = false;
        },
    });
};

// ── Portal copy ────────────────────────────────────────────────────────────
const copiedInvite = ref(false);
const copyInviteUrl = () => {
    navigator.clipboard.writeText(props.applicant.portal.invite_url).then(() => {
        copiedInvite.value = true;
        setTimeout(() => { copiedInvite.value = false; }, 2000);
    });
};

const copiedLogin = ref(false);
const copyLoginUrl = () => {
    navigator.clipboard.writeText(props.applicant.portal.login_url).then(() => {
        copiedLogin.value = true;
        setTimeout(() => { copiedLogin.value = false; }, 2000);
    });
};

// ── Note form ──────────────────────────────────────────────────────────────
const showNoteForm = ref(false);
const noteForm = useForm({ body: '' });
const submitNote = () => {
    noteForm.post(route('applicants.notes.store', props.applicant.id), {
        preserveScroll: true,
        onSuccess: () => {
            noteForm.reset();
            showNoteForm.value = false;
        },
    });
};

// ── Preferences form ───────────────────────────────────────────────────────
const preferenceForm = useForm({
    email_enabled: props.applicant.notification_preferences.email_enabled,
    sms_enabled:   props.applicant.notification_preferences.sms_enabled,
    locale:        props.applicant.notification_preferences.locale,
    events:        { ...props.applicant.notification_preferences.events },
});

const savePreferences = () => {
    preferenceForm.patch(
        route('applicants.notification-preferences.update', props.applicant.id),
        { preserveScroll: true },
    );
};

const notificationEvents = [
    { key: 'case_status_changes',   label: 'Case status changes' },
    { key: 'document_requests',     label: 'Document requests' },
    { key: 'payment_reminders',     label: 'Payment reminders' },
    { key: 'appointment_reminders', label: 'Appointment reminders' },
    { key: 'messages',              label: 'General case messages' },
];
</script>

<template>
    <Head :title="applicant.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Applicant profile</p>
                    <h1 class="mt-2 text-[30px]">{{ applicant.name }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <span class="text-sm text-brand-muted">
                            {{ activeCaseCount }} active case{{ activeCaseCount === 1 ? '' : 's' }}
                        </span>
                        <span v-if="applicant.nationality" class="text-sm text-brand-muted">
                            • {{ applicant.nationality }}
                        </span>
                        <!-- Passport expiry warning -->
                        <span
                            v-if="passportExpiringSoon"
                            class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            Passport expiring soon
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <TagBadge v-for="tag in applicant.tags" :key="tag.id" :label="tag.name" :color="tag.color" />
                        </div>
                    </div>
                </div>
                <Link
                    v-if="applicant.lead"
                    :href="route('leads.show', applicant.lead.id)"
                    class="ui-button-secondary"
                >
                    View source lead
                </Link>
            </div>
        </template>

        <div class="ui-detail-grid">

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- LEFT COLUMN                                              -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="space-y-6">

                <!-- Identity & Passport -->
                <AppCard title="Identity and passport" subtitle="The details your team reaches for most often.">
                    <div class="space-y-5">
                        <div
                            v-if="passportExpiringSoon && applicant.passport_expires_at"
                            class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-amber-900">Passport renewal attention needed</p>
                                    <p class="mt-1 text-sm text-amber-700">
                                        The current passport expires on {{ applicant.passport_expires_at }}. Check renewal timing before the next submission step.
                                    </p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                    Expiring soon
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-4 xl:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="ui-kicker !mb-0">Identity</p>
                                        <p class="mt-2 text-sm text-slate-500">{{ identityStatus.helper }}</p>
                                    </div>
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="identityStatus.className"
                                    >
                                        {{ identityStatus.label }}
                                    </span>
                                </div>

                                <div class="mt-4 space-y-3 text-sm">
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Email</span>
                                        <a
                                            v-if="applicant.email"
                                            :href="`mailto:${applicant.email}`"
                                            class="text-right text-brand-primary hover:underline"
                                        >
                                            {{ applicant.email }}
                                        </a>
                                        <span v-else class="text-right text-slate-400">No email on file yet</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Phone</span>
                                        <a
                                            v-if="applicant.phone"
                                            :href="`tel:${applicant.phone}`"
                                            class="text-right text-brand-primary hover:underline"
                                        >
                                            {{ applicant.phone }}
                                        </a>
                                        <span v-else class="text-right text-slate-400">No phone number on file yet</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Nationality</span>
                                        <span class="text-right text-slate-900">{{ applicant.nationality || 'Nationality not added yet' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <span class="font-medium text-slate-500">Residence</span>
                                        <span class="text-right text-slate-900">{{ applicant.country_of_residence || 'Residence not added yet' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="ui-kicker !mb-0">Passport</p>
                                        <p class="mt-2 text-sm text-slate-500">{{ passportStatus.helper }}</p>
                                    </div>
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="passportStatus.className"
                                    >
                                        {{ passportStatus.label }}
                                    </span>
                                </div>

                                <div class="mt-4 space-y-3 text-sm">
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Number</span>
                                        <span class="text-right text-slate-900">{{ applicant.passport_number || 'Passport number not added yet' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Issuing country</span>
                                        <span class="text-right text-slate-900">{{ applicant.passport_country || 'Passport country not added yet' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-3">
                                        <span class="font-medium text-slate-500">Issued on</span>
                                        <span class="text-right text-slate-900">{{ applicant.passport_issued_at || 'Issue date not added yet' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <span class="font-medium text-slate-500">Expires on</span>
                                        <span :class="passportExpiringSoon ? 'text-right font-semibold text-amber-700' : 'text-right text-slate-900'">
                                            {{ applicant.passport_expires_at || 'Expiry date not added yet' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </AppCard>

                <!-- Visa cases -->
                <AppCard title="Visa cases" subtitle="Every active case for this applicant.">
                    <div v-if="applicant.visa_cases.length" class="space-y-3">
                        <div
                            v-for="visaCase in applicant.visa_cases"
                            :key="visaCase.id"
                            class="flex items-center justify-between gap-4 rounded-lg border border-brand-border px-4 py-4"
                        >
                            <div>
                                <p class="font-medium text-brand-text">{{ visaCase.reference_code }}</p>
                                <p class="mt-1 text-sm text-brand-muted">
                                    {{ visaCase.country }} • {{ visaCase.visa_type }}
                                    <span v-if="visaCase.stage" class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                        {{ visaCase.stage }}
                                    </span>
                                </p>
                            </div>
                            <Link :href="route('cases.show', visaCase.id)" class="shrink-0 text-sm font-medium text-brand-primary hover:underline">
                                Open case →
                            </Link>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        icon="users"
                        title="No cases yet"
                        description="Cases will appear here once this applicant has an active visa workflow."
                    />
                </AppCard>

                <!-- Travel history -->
                <AppCard title="Travel history" subtitle="Prior movement and application context.">
                    <template #action>
                        <button
                            type="button"
                            class="ui-button-ghost !h-8 px-3 text-[12px]"
                            @click="showTravelHistoryForm = !showTravelHistoryForm"
                        >
                            {{ showTravelHistoryForm ? 'Cancel' : applicant.travel_history.length ? 'Edit travel history' : 'Add travel history' }}
                        </button>
                    </template>

                    <form v-if="showTravelHistoryForm" class="mb-5 space-y-3 rounded-lg border border-dashed border-brand-border bg-brand-neutral p-4" @submit.prevent="submitTravelHistory">
                        <div v-if="travelHistoryForm.travel_history.length" class="space-y-3">
                            <div
                                v-for="(entry, index) in travelHistoryForm.travel_history"
                                :key="`travel-history-${index}`"
                                class="rounded-lg border border-brand-border bg-white p-4"
                            >
                                <div class="grid gap-3 md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_120px_auto] md:items-end">
                                    <div>
                                        <InputLabel :for="`travel_country_${index}`" value="Country" />
                                        <input :id="`travel_country_${index}`" v-model="entry.country" type="text" class="ui-input" placeholder="Japan" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`travel_purpose_${index}`" value="Purpose" />
                                        <input :id="`travel_purpose_${index}`" v-model="entry.purpose" type="text" class="ui-input" placeholder="Tourism, study, business" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`travel_year_${index}`" value="Year" />
                                        <input :id="`travel_year_${index}`" v-model="entry.year" type="number" min="1900" max="2100" class="ui-input" placeholder="2024" />
                                    </div>
                                    <div class="md:pb-0.5">
                                        <button type="button" class="ui-button-ghost !h-10 w-full px-3 text-[12px]" @click="removeTravelHistoryEntry(index)">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <InputError :message="travelHistoryForm.errors.travel_history" />

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <button type="button" class="ui-button-ghost !h-9 px-3 text-[12px]" @click="addTravelHistoryEntry">
                                + Add trip
                            </button>
                            <div class="flex items-center gap-2">
                                <button type="button" class="ui-button-ghost !h-9 px-3 text-[12px]" @click="showTravelHistoryForm = false">Cancel</button>
                                <PrimaryButton :loading="travelHistoryForm.processing">Save travel history</PrimaryButton>
                            </div>
                        </div>
                    </form>

                    <div v-if="applicant.travel_history.length" class="space-y-3">
                        <div
                            v-for="(entry, index) in applicant.travel_history"
                            :key="`${entry.country}-${index}`"
                            class="flex items-start justify-between gap-4 rounded-lg bg-brand-neutral px-4 py-4"
                        >
                            <div>
                                <p class="font-medium text-brand-text">{{ entry.country }}</p>
                                <p class="mt-1 text-sm text-brand-muted">{{ entry.purpose || 'Purpose not recorded yet' }}</p>
                            </div>
                            <span v-if="entry.year" class="shrink-0 text-sm text-brand-muted">{{ entry.year }}</span>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        icon="clock"
                        title="No travel history yet"
                        description="This section will become useful once the applicant shares prior travel details."
                    />
                </AppCard>

                <!-- Unified notes + activity timeline -->
                <AppCard title="Notes & activity" subtitle="A combined record of team notes and profile changes, newest first.">
                    <template #action>
                        <button
                            type="button"
                            class="ui-button-secondary"
                            @click="showNoteForm = !showNoteForm"
                        >
                            {{ showNoteForm ? 'Cancel' : '+ Add note' }}
                        </button>
                    </template>

                    <!-- Inline note form, shown on demand -->
                    <div v-if="showNoteForm" class="mb-5 rounded-lg border border-dashed border-brand-border bg-brand-neutral p-4">
                        <form class="space-y-3" @submit.prevent="submitNote">
                            <div>
                                <InputLabel for="note_body" value="What should the team know?" />
                                <textarea
                                    id="note_body"
                                    v-model="noteForm.body"
                                    rows="4"
                                    class="ui-textarea"
                                    placeholder="Embassy concerns, missing items, what to remind them about…"
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

                    <!-- Timeline -->
                    <div v-if="unifiedTimeline.length" class="space-y-1.5">
                        <div
                            v-for="entry in unifiedTimeline"
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
                                <span class="shrink-0 text-brand-muted">{{ entry.created_at }}</span>
                            </div>

                            <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-brand-text">
                                {{ timelineBody(entry) }}
                            </p>
                        </div>
                    </div>

                    <EmptyState
                        v-else
                        icon="note"
                        title="Nothing here yet"
                        description="Notes and profile activity will appear here as a combined timeline."
                    />
                </AppCard>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- RIGHT SIDEBAR                                            -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="space-y-6">

                <!-- Applicant portal -->
                <AppCard title="Applicant portal" subtitle="The client's self-service view of their cases and documents.">
                    <div class="space-y-3">
                        <!-- Login URL -->
                        <div class="rounded-lg bg-brand-neutral px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium uppercase tracking-wide text-brand-muted">Portal login</p>
                                    <p class="mt-1 truncate text-sm text-brand-text">{{ applicant.portal.login_url }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 text-xs font-medium text-brand-primary hover:underline"
                                    @click="copyLoginUrl"
                                >
                                    {{ copiedLogin ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <!-- Active invite link -->
                        <div v-if="applicant.portal.invite_url" class="rounded-lg border border-brand-border px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium uppercase tracking-wide text-brand-muted">Active access link</p>
                                    <p class="mt-1 break-all text-sm text-brand-text">{{ applicant.portal.invite_url }}</p>
                                    <p v-if="applicant.portal.invite_expires_at" class="mt-1.5 text-xs text-brand-muted">
                                        Expires {{ applicant.portal.invite_expires_at }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 text-xs font-medium text-brand-primary hover:underline"
                                    @click="copyInviteUrl"
                                >
                                    {{ copiedInvite ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="$inertia.post(route('applicants.portal-invites.store', applicant.id), {}, { preserveScroll: true })">
                            <PrimaryButton>
                                {{ applicant.portal.invite_url ? 'Regenerate access link' : 'Generate portal access link' }}
                            </PrimaryButton>
                        </form>
                    </div>
                </AppCard>

                <!-- Notification preferences -->
                <AppCard title="Notification preferences" subtitle="Channels and events the applicant has opted into.">
                    <form class="space-y-5" @submit.prevent="savePreferences">

                        <!-- Channel toggles -->
                        <div>
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-brand-muted">Channels</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-brand-border px-3 py-3 transition hover:border-brand-primary/30">
                                    <input
                                        v-model="preferenceForm.email_enabled"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20"
                                    />
                                    <span class="text-sm text-brand-text">Email</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-brand-border px-3 py-3 transition hover:border-brand-primary/30">
                                    <input
                                        v-model="preferenceForm.sms_enabled"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20"
                                    />
                                    <span class="text-sm text-brand-text">SMS</span>
                                </label>
                            </div>
                        </div>

                        <!-- Locale -->
                        <div>
                            <InputLabel for="locale" value="Preferred locale" />
                            <select id="locale" v-model="preferenceForm.locale" class="ui-select">
                                <option v-for="locale in applicant.available_locales" :key="locale.value" :value="locale.value">
                                    {{ locale.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Event subscriptions -->
                        <div>
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-brand-muted">Events</p>
                            <div class="space-y-2">
                                <label
                                    v-for="event in notificationEvents"
                                    :key="event.key"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 transition hover:bg-brand-neutral"
                                >
                                    <input
                                        v-model="preferenceForm.events[event.key]"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20"
                                    />
                                    <span class="text-sm text-brand-text">{{ event.label }}</span>
                                </label>
                            </div>
                        </div>

                        <PrimaryButton :loading="preferenceForm.processing">Save preferences</PrimaryButton>
                    </form>
                </AppCard>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
