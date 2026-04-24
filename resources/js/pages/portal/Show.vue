<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PortalAttachmentsPanel from '@/components/portal/PortalAttachmentsPanel.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown, KeyRound, LogOut, Mail, Phone } from 'lucide-vue-next';
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
    { label: 'Passport number', value: props.portal.client.passport_number || 'Not added yet' },
    { label: 'Passport expiry', value: formatDate(props.portal.client.passport_expiry_date) },
    { label: 'Nationality', value: props.portal.client.nationality || 'Not added yet' },
    { label: 'Address', value: props.portal.client.current_address || 'Not added yet' },
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

    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-5 px-4 py-5 md:px-6 lg:px-8">
            <div v-if="page.props.flash.success" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ page.props.flash.success }}
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 lg:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-sky-700">Customer portal</p>
                        <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Hello {{ portal.client.full_name }}</h1>
                        <p class="max-w-3xl text-base leading-7 text-slate-600">
                            This page shows only the important things: what to send, how your case is moving, and how to contact your team.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button as-child variant="outline" class="h-11 gap-2 text-base">
                            <Link :href="route('portal.password.edit', portal.token)">
                                <KeyRound class="size-4" />
                                Change password
                            </Link>
                        </Button>
                        <Button as-child variant="ghost" class="h-11 gap-2 text-base">
                            <Link :href="route('portal.logout')" method="post" as="button">
                                <LogOut class="size-4" />
                                Sign out
                            </Link>
                        </Button>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">Documents to send</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ outstandingRequirements.length }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">These are the files we still need from you right now.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">Visa cases</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ portal.visaCases.length }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">You can open each case below to see the current checklist.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">Overall progress</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ overallProgress }}%</p>
                        <div class="mt-3 h-2 rounded-full bg-white">
                            <div class="h-full rounded-full bg-slate-900" :style="{ width: `${overallProgress}%` }" />
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_360px]">
                <div class="space-y-5">
                    <section class="rounded-3xl border border-slate-200 bg-white p-5 lg:p-6">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Start here</p>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Documents you still need to send</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">If there is anything missing, it will show here first.</p>
                        </div>

                        <div v-if="outstandingRequirements.length === 0" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-5">
                            <p class="text-base font-medium text-emerald-900">You are all caught up.</p>
                            <p class="mt-1 text-sm text-emerald-700">There are no required documents waiting from you right now.</p>
                        </div>

                        <div v-else class="mt-5 overflow-x-auto">
                            <table class="w-full min-w-[720px] text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-[0.16em] text-slate-400">
                                        <th class="px-3 py-2 font-medium">Document</th>
                                        <th class="px-3 py-2 font-medium">Case</th>
                                        <th class="px-3 py-2 font-medium">Due</th>
                                        <th class="px-3 py-2 font-medium">Status</th>
                                        <th class="px-3 py-2 text-right font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="requirement in outstandingRequirements" :key="requirement.key" class="border-b border-slate-200/80 align-top">
                                        <td class="px-3 py-3">
                                            <div>
                                                <p class="font-medium text-slate-950">{{ requirement.label }}</p>
                                                <p v-if="requirement.category" class="mt-0.5 text-xs text-slate-500">{{ requirement.category }}</p>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-slate-600">
                                            <p class="font-medium text-slate-900">{{ requirement.case_reference }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ requirement.case_summary }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-slate-600">{{ formatDate(requirement.due_at) }}</td>
                                        <td class="px-3 py-3">
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                                Waiting
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <Button as-child variant="outline" size="sm">
                                                <a :href="`#case-${requirement.visa_case_id}`">Open case</a>
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5 lg:p-6">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Your cases</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Visa applications</h2>
                            </div>
                            <span class="text-sm text-slate-500">{{ portal.visaCases.length }} case{{ portal.visaCases.length === 1 ? '' : 's' }}</span>
                        </div>

                        <div v-if="portal.visaCases.length === 0" class="mt-5 rounded-2xl bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            We have not opened a visa case for you yet.
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <Collapsible v-for="visaCase in portal.visaCases" :key="visaCase.id" v-slot="{ open }" :default-open="false">
                                <article :id="`case-${visaCase.id}`" class="rounded-2xl border border-slate-200 bg-slate-50">
                                    <CollapsibleTrigger class="flex w-full flex-col gap-3 px-4 py-4 text-left sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="caseStatusClasses(visaCase.status)">
                                                    {{ visaCase.status_label }}
                                                </span>
                                                <span class="text-sm text-slate-500">{{ visaCase.destination_country }} • {{ visaCase.visa_type }}</span>
                                            </div>
                                            <h3 class="mt-3 text-lg font-semibold text-slate-950">{{ visaCase.reference_code }}</h3>
                                            <p class="mt-1 text-sm text-slate-600">
                                                {{ visaCase.completed_requirements_count }}/{{ visaCase.total_requirements_count }} checklist items complete
                                            </p>
                                        </div>

                                        <div class="space-y-2 sm:min-w-[220px] sm:text-right">
                                            <p class="text-sm text-slate-600">Next due: <span class="font-medium text-slate-950">{{ formatDate(visaCase.next_due_at) }}</span></p>
                                            <p class="text-sm text-slate-600">Submitted: <span class="font-medium text-slate-950">{{ formatDate(visaCase.submitted_at) }}</span></p>
                                            <div class="mt-2 flex items-center gap-3 sm:justify-end">
                                                <div class="h-2 flex-1 rounded-full bg-white sm:max-w-[120px]">
                                                    <div class="h-full rounded-full bg-slate-900" :style="{ width: `${visaCase.progress_percent}%` }" />
                                                </div>
                                                <span class="text-sm font-medium text-slate-700">{{ visaCase.progress_percent }}%</span>
                                                <ChevronDown class="size-4 text-slate-500 transition-transform" :class="{ 'rotate-180': open }" />
                                            </div>
                                        </div>
                                    </CollapsibleTrigger>

                                    <CollapsibleContent>
                                        <div class="border-t border-slate-200 px-4 py-4">
                                            <div class="space-y-2">
                                                <div
                                                    v-for="requirement in visaCase.requirements"
                                                    :key="requirement.id"
                                                    class="rounded-xl border border-slate-200 bg-white px-4 py-3"
                                                >
                                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                        <div>
                                                            <p class="text-sm font-medium text-slate-950">{{ requirement.label }}</p>
                                                            <p v-if="requirement.help_text" class="mt-1 text-sm text-slate-600">{{ requirement.help_text }}</p>
                                                        </div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                                                {{ requirement.status_label }}
                                                            </span>
                                                            <span
                                                                class="rounded-full px-3 py-1 text-xs font-medium"
                                                                :class="requirement.is_completed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                                            >
                                                                {{ requirement.is_completed ? 'Done' : 'Waiting' }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-slate-600">
                                                        <span>Due: {{ formatDate(requirement.due_at) }}</span>
                                                        <span>{{ requirement.attachments.length }} uploaded file{{ requirement.attachments.length === 1 ? '' : 's' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </article>
                            </Collapsible>
                        </div>
                    </section>
                </div>

                <div class="space-y-5 xl:sticky xl:top-5 xl:self-start">
                    <section class="rounded-3xl border border-slate-200 bg-white p-5 lg:p-6">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Need help?</p>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ portal.company.name }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Use these contact details if you have questions about any document or update.</p>
                        </div>

                        <div class="mt-5 space-y-3">
                            <a
                                v-if="portal.company.phone"
                                :href="`tel:${portal.company.phone}`"
                                class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-4 text-slate-900 transition hover:bg-slate-50"
                            >
                                <Phone class="size-5 text-sky-700" />
                                <div>
                                    <p class="text-sm text-slate-500">Phone</p>
                                    <p class="text-base font-medium">{{ portal.company.phone }}</p>
                                </div>
                            </a>

                            <a
                                v-if="portal.company.email"
                                :href="`mailto:${portal.company.email}`"
                                class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-4 text-slate-900 transition hover:bg-slate-50"
                            >
                                <Mail class="size-5 text-sky-700" />
                                <div>
                                    <p class="text-sm text-slate-500">Email</p>
                                    <p class="text-base font-medium">{{ portal.company.email }}</p>
                                </div>
                            </a>

                            <div v-if="!portal.company.email && !portal.company.phone && !portal.company.website" class="rounded-2xl bg-slate-50 px-4 py-4 text-sm text-slate-600">
                                Reach out to your case team if you need help with any document or update.
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-sm font-semibold text-slate-950">Uploaded files</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ totalUploadedFiles }}</p>
                            <p class="mt-2 text-sm text-slate-600">Across your checklist and your extra documents.</p>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5 lg:p-6">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Your details</p>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Profile</h2>
                        </div>

                        <dl class="mt-5 grid gap-3">
                            <div v-for="item in clientSummary" :key="item.label" class="rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ item.label }}</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ item.value }}</dd>
                            </div>
                        </dl>

                        <Collapsible v-slot="{ open }" class="mt-5">
                            <div class="rounded-2xl border border-slate-200">
                                <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left">
                                    <div>
                                        <p class="text-base font-medium text-slate-950">Update your details</p>
                                        <p class="text-sm text-slate-500">Only change these if something is different now.</p>
                                    </div>
                                    <ChevronDown class="size-4 text-slate-500 transition-transform" :class="{ 'rotate-180': open }" />
                                </CollapsibleTrigger>

                                <CollapsibleContent class="border-t border-slate-200 px-4 py-4">
                                    <form class="space-y-4" @submit.prevent="submit">
                                        <div class="grid gap-4">
                                            <div class="grid gap-2">
                                                <Label for="full_name">Full name</Label>
                                                <Input id="full_name" v-model="form.full_name" class="h-11 text-base" />
                                                <InputError :message="form.errors.full_name" />
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="email">Email</Label>
                                                <Input id="email" v-model="form.email" type="email" class="h-11 text-base" />
                                                <InputError :message="form.errors.email" />
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="phone">Phone</Label>
                                                <Input id="phone" v-model="form.phone" class="h-11 text-base" />
                                                <InputError :message="form.errors.phone" />
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="nationality">Nationality</Label>
                                                <Input id="nationality" v-model="form.nationality" class="h-11 text-base" />
                                                <InputError :message="form.errors.nationality" />
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="passport_number">Passport number</Label>
                                                <Input id="passport_number" v-model="form.passport_number" class="h-11 text-base" />
                                                <InputError :message="form.errors.passport_number" />
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="passport_expiry_date">Passport expiry</Label>
                                                <Input id="passport_expiry_date" v-model="form.passport_expiry_date" type="date" class="h-11 text-base" />
                                                <InputError :message="form.errors.passport_expiry_date" />
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="current_address">Current address</Label>
                                                <textarea
                                                    id="current_address"
                                                    v-model="form.current_address"
                                                    rows="3"
                                                    class="min-h-24 rounded-xl border border-slate-200 bg-white px-3 py-3 text-base text-slate-700 outline-none transition focus:border-slate-400"
                                                />
                                                <InputError :message="form.errors.current_address" />
                                            </div>
                                        </div>

                                        <Button :disabled="form.processing" class="h-11 w-full text-base">Save changes</Button>
                                    </form>
                                </CollapsibleContent>
                            </div>
                        </Collapsible>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5 lg:p-6">
                        <PortalAttachmentsPanel
                            title="Other documents"
                            route-name="portal.attachments.store"
                            :route-parameter="portal.token"
                            :attachments="portal.clientAttachments"
                            input-id="portal-other-attachments"
                            embedded
                        />
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
