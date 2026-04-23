<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PortalAttachmentsPanel from '@/components/portal/PortalAttachmentsPanel.vue';
import PortalRequirementCard from '@/components/portal/PortalRequirementCard.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown, KeyRound } from 'lucide-vue-next';
import { computed } from 'vue';

interface PortalCompany {
    name: string;
    email: null | string;
    phone: null | string;
    website: null | string;
}

interface PortalClient {
    full_name: string;
    email: null | string;
    phone: null | string;
    passport_number: null | string;
    passport_expiry_date: null | string;
    current_address: null | string;
    nationality: null | string;
}

interface PortalAttachment {
    id: number;
    original_name: string;
    mime_type: null | string;
    size: string;
    uploaded_by: null | string;
    created_at: null | string;
    download_url: string;
}

interface PortalRequirement {
    id: number;
    category: null | string;
    label: string;
    help_text: null | string;
    is_required: boolean;
    status: string;
    status_label: string;
    due_at: null | string;
    is_completed: boolean;
    attachments: PortalAttachment[];
}

interface PortalVisaCase {
    id: number;
    reference_code: string;
    visa_type: string;
    destination_country: string;
    status: string;
    status_label: string;
    progress_percent: number;
    completed_requirements_count: number;
    total_requirements_count: number;
    submitted_at: null | string;
    decision_at: null | string;
    next_due_at: null | string;
    requirements: PortalRequirement[];
}

interface OutstandingRequirement extends PortalRequirement {
    key: string;
    visa_case_id: number;
    case_reference: string;
    case_summary: string;
}

const props = defineProps<{
    portal: {
        token: string;
        company: PortalCompany;
        client: PortalClient;
        clientAttachments: PortalAttachment[];
        visaCases: PortalVisaCase[];
    };
}>();

const page = usePage<SharedData>();

const form = useForm({
    full_name: props.portal.client.full_name,
    email: props.portal.client.email ?? '',
    phone: props.portal.client.phone ?? '',
    passport_number: props.portal.client.passport_number ?? '',
    passport_expiry_date: props.portal.client.passport_expiry_date ?? '',
    current_address: props.portal.client.current_address ?? '',
    nationality: props.portal.client.nationality ?? '',
});

const submit = () => {
    form.patch(route('portal.profile.update', props.portal.token), {
        preserveScroll: true,
    });
};

const overallProgress = computed(() => {
    if (props.portal.visaCases.length === 0) {
        return 0;
    }

    return Math.round(
        props.portal.visaCases.reduce((sum, visaCase) => sum + visaCase.progress_percent, 0) / props.portal.visaCases.length,
    );
});

const outstandingRequirements = computed<OutstandingRequirement[]>(() =>
    props.portal.visaCases
        .flatMap((visaCase) =>
            visaCase.requirements
                .filter((requirement) => requirement.is_required && !requirement.is_completed)
                .map((requirement) => ({
                    ...requirement,
                    key: `${visaCase.id}-${requirement.id}`,
                    visa_case_id: visaCase.id,
                    case_reference: visaCase.reference_code,
                    case_summary: `${visaCase.destination_country} • ${visaCase.visa_type}`,
                })),
        )
        .sort((left, right) => {
            if (!left.due_at && !right.due_at) {
                return left.label.localeCompare(right.label);
            }

            if (!left.due_at) {
                return 1;
            }

            if (!right.due_at) {
                return -1;
            }

            return left.due_at.localeCompare(right.due_at);
        }),
);

const totalUploadedFiles = computed(
    () =>
        props.portal.clientAttachments.length +
        props.portal.visaCases.reduce((sum, visaCase) => sum + visaCase.requirements.reduce((caseSum, requirement) => caseSum + requirement.attachments.length, 0), 0),
);

const clientSummary = computed(() => [
    { label: 'Email', value: props.portal.client.email || 'Not added yet' },
    { label: 'Phone', value: props.portal.client.phone || 'Not added yet' },
    { label: 'Passport', value: props.portal.client.passport_number || 'Not added yet' },
    { label: 'Nationality', value: props.portal.client.nationality || 'Not added yet' },
]);

const formatDate = (value: null | string) =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(value)) : 'Not set';

const caseStatusClasses = (status: string) =>
    ({
        intake: 'bg-slate-900 text-white',
        documents_pending: 'bg-amber-100 text-amber-800',
        ready_to_file: 'bg-violet-100 text-violet-800',
        submitted: 'bg-sky-100 text-sky-800',
        approved: 'bg-emerald-100 text-emerald-800',
        rejected: 'bg-rose-100 text-rose-800',
        closed: 'bg-neutral-200 text-neutral-700',
    })[status] ?? 'bg-slate-100 text-slate-700';
</script>

<template>
    <Head :title="`${portal.company.name} Portal`" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.12),_transparent_28%),linear-gradient(180deg,#f8fafc_0%,#eef2ff_45%,#ffffff_100%)]">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-5 px-4 py-5 md:px-6 lg:px-8">
            <div v-if="page.props.flash.success" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ page.props.flash.success }}
            </div>

            <section class="overflow-hidden rounded-[2rem] border border-white/70 bg-white/90 shadow-[0_24px_80px_-40px_rgba(15,23,42,0.35)] backdrop-blur">
                <div class="grid gap-5 px-5 py-6 lg:grid-cols-[minmax(0,1.4fr)_280px] lg:px-8">
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">Client Portal</p>
                                <div>
                                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Hi {{ portal.client.full_name }}</h1>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                        We’ve kept this page focused on the few things that matter most: what’s next, what to upload, and how your case is moving.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Button as-child variant="outline" class="gap-2">
                                    <Link :href="route('portal.password.edit', portal.token)">
                                        <KeyRound class="size-4" />
                                        Change password
                                    </Link>
                                </Button>
                                <Button as-child variant="ghost">
                                    <Link :href="route('portal.logout')" method="post" as="button">Sign out</Link>
                                </Button>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Next actions</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ outstandingRequirements.length }}</p>
                                <p class="mt-1 text-sm text-slate-500">Required documents waiting on you</p>
                            </div>
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Cases</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ portal.visaCases.length }}</p>
                                <p class="mt-1 text-sm text-slate-500">Visa application{{ portal.visaCases.length === 1 ? '' : 's' }} in progress</p>
                            </div>
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Progress</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ overallProgress }}%</p>
                                <div class="mt-3 h-2 rounded-full bg-white">
                                    <div class="h-full rounded-full bg-slate-900" :style="{ width: `${overallProgress}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="rounded-[1.75rem] border border-slate-200 bg-slate-950 p-5 text-white">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-200">Your team</p>
                        <h2 class="mt-2 text-xl font-semibold">{{ portal.company.name }}</h2>
                        <div class="mt-4 space-y-3 text-sm text-slate-200">
                            <p v-if="portal.company.email">Email: <a :href="`mailto:${portal.company.email}`" class="font-medium text-white">{{ portal.company.email }}</a></p>
                            <p v-if="portal.company.phone">Phone: <a :href="`tel:${portal.company.phone}`" class="font-medium text-white">{{ portal.company.phone }}</a></p>
                            <p v-if="portal.company.website">
                                Website:
                                <a :href="portal.company.website" target="_blank" rel="noreferrer" class="font-medium text-white">{{ portal.company.website }}</a>
                            </p>
                            <p v-if="!portal.company.email && !portal.company.phone && !portal.company.website" class="text-slate-300">
                                Reach out to your case team if you need help with any document or update.
                            </p>
                        </div>

                        <div class="mt-6 rounded-[1.5rem] border border-white/10 bg-white/10 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Uploaded files</p>
                            <p class="mt-2 text-2xl font-semibold">{{ totalUploadedFiles }}</p>
                            <p class="mt-1 text-sm text-slate-200">Across your profile and document checklist.</p>
                        </div>
                    </aside>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_360px]">
                <div class="space-y-5">
                    <section class="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_18px_60px_-36px_rgba(15,23,42,0.35)] backdrop-blur lg:p-6">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">What needs your attention</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Next steps</h2>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                {{ outstandingRequirements.length }} item{{ outstandingRequirements.length === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <div v-if="outstandingRequirements.length === 0" class="mt-5 rounded-[1.75rem] border border-emerald-200 bg-emerald-50 px-5 py-5">
                            <p class="text-sm font-medium text-emerald-900">You’re all caught up.</p>
                            <p class="mt-1 text-sm text-emerald-700">There are no required documents waiting from you right now.</p>
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <PortalRequirementCard
                                v-for="requirement in outstandingRequirements"
                                :key="requirement.key"
                                :portal-token="portal.token"
                                :visa-case-id="requirement.visa_case_id"
                                :requirement="requirement"
                                :context-label="`${requirement.case_reference} • ${requirement.case_summary}`"
                            />
                        </div>
                    </section>

                    <section class="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_18px_60px_-36px_rgba(15,23,42,0.35)] backdrop-blur lg:p-6">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Overview</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Your case progress</h2>
                            </div>
                            <span class="text-sm text-slate-500">{{ portal.visaCases.length }} case{{ portal.visaCases.length === 1 ? '' : 's' }}</span>
                        </div>

                        <div v-if="portal.visaCases.length === 0" class="mt-5 rounded-[1.75rem] bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            We haven’t opened a visa case for you yet.
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <article
                                v-for="visaCase in portal.visaCases"
                                :key="visaCase.id"
                                class="rounded-[1.75rem] border border-slate-200 bg-slate-50/80 p-4"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-medium" :class="caseStatusClasses(visaCase.status)">
                                                {{ visaCase.status_label }}
                                            </span>
                                            <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                                {{ visaCase.destination_country }} • {{ visaCase.visa_type }}
                                            </span>
                                        </div>
                                        <h3 class="mt-3 text-base font-semibold text-slate-950">{{ visaCase.reference_code }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ visaCase.completed_requirements_count }}/{{ visaCase.total_requirements_count }} checklist items complete
                                        </p>
                                    </div>

                                    <div class="grid gap-2 text-sm text-slate-600 sm:min-w-[220px] sm:text-right">
                                        <p>Next due: <span class="font-medium text-slate-950">{{ formatDate(visaCase.next_due_at) }}</span></p>
                                        <p>Submitted: <span class="font-medium text-slate-950">{{ formatDate(visaCase.submitted_at) }}</span></p>
                                    </div>
                                </div>

                                <div class="mt-4 h-2 rounded-full bg-white">
                                    <div class="h-full rounded-full bg-slate-900" :style="{ width: `${visaCase.progress_percent}%` }" />
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <div class="space-y-5 xl:sticky xl:top-5 xl:self-start">
                    <section class="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_18px_60px_-36px_rgba(15,23,42,0.35)] backdrop-blur lg:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Your details</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Profile</h2>
                            </div>
                        </div>

                        <dl class="mt-5 grid gap-3">
                            <div v-for="item in clientSummary" :key="item.label" class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ item.label }}</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ item.value }}</dd>
                            </div>
                        </dl>

                        <Collapsible v-slot="{ open }" class="mt-5">
                            <div class="rounded-[1.5rem] border border-slate-200">
                                <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                    <div>
                                        <p class="text-sm font-medium text-slate-950">Update details</p>
                                        <p class="text-xs text-slate-500">Only open this when you need to make a change.</p>
                                    </div>
                                    <ChevronDown class="size-4 text-slate-500 transition-transform" :class="{ 'rotate-180': open }" />
                                </CollapsibleTrigger>

                                <CollapsibleContent class="border-t border-slate-200 px-4 py-4">
                                    <form class="space-y-4" @submit.prevent="submit">
                                        <div class="grid gap-4">
                                            <div class="grid gap-1.5">
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
                                                <Label for="current_address">Current address</Label>
                                                <textarea
                                                    id="current_address"
                                                    v-model="form.current_address"
                                                    rows="3"
                                                    class="min-h-24 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400"
                                                />
                                                <InputError :message="form.errors.current_address" />
                                            </div>
                                        </div>

                                        <Button :disabled="form.processing" class="w-full">Save changes</Button>
                                    </form>
                                </CollapsibleContent>
                            </div>
                        </Collapsible>
                    </section>

                    <Collapsible v-slot="{ open }">
                        <section class="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_18px_60px_-36px_rgba(15,23,42,0.35)] backdrop-blur lg:p-6">
                            <CollapsibleTrigger class="flex w-full items-center justify-between text-left">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Optional</p>
                                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Other documents</h2>
                                </div>
                                <ChevronDown class="size-4 text-slate-500 transition-transform" :class="{ 'rotate-180': open }" />
                            </CollapsibleTrigger>

                            <CollapsibleContent class="pt-5">
                                <PortalAttachmentsPanel
                                    title="Upload supporting files"
                                    route-name="portal.attachments.store"
                                    :route-parameter="portal.token"
                                    :attachments="portal.clientAttachments"
                                    input-id="portal-other-attachments"
                                    embedded
                                />
                            </CollapsibleContent>
                        </section>
                    </Collapsible>
                </div>
            </div>
        </div>
    </div>
</template>
