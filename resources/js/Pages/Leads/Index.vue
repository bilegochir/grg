<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SlideOver from '@/Components/SlideOver.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    leads: Object,
    filters: Object,
    statuses: Array,
    sources: Array,
    tags: Array,
});

const urlParams = new URLSearchParams(window.location.search);
const showCreate = ref(urlParams.get('create') === 'true');

const filterForm = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    source: props.filters.source ?? '',
});

const createForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    source: props.sources[0]?.value ?? '',
    status: props.statuses[0]?.value ?? '',
    country_of_citizenship: '',
    interested_visa_type: '',
    education_history: [],
    work_experience: [],
    tag_ids: [],
    note: '',
});

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

const addEducation = () => {
    createForm.education_history.push(blankEducation());
};

const removeEducation = (index) => {
    createForm.education_history.splice(index, 1);
};

const addExperience = () => {
    createForm.work_experience.push(blankExperience());
};

const removeExperience = (index) => {
    createForm.work_experience.splice(index, 1);
};

const resultsLabel = computed(() => {
    if (!props.leads.total) {
        return '0 results';
    }

    return `${props.leads.from}-${props.leads.to} of ${props.leads.total} results`;
});

const applyFilters = () => {
    router.get(route('leads.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const submit = () => {
    createForm.post(route('leads.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Leads" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Leads</p>
                    <h1 class="ui-header-title text-[28px] tracking-tight">Lead Pipeline</h1>
                    <p class="ui-header-copy text-[14px] leading-relaxed">
                        Track potential applicants from initial inquiry to case conversion. Focus on high-intent leads and pending follow-ups.
                    </p>
                </div>
            </div>
        </template>

        <div class="ui-page-body space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/30 p-4">
                    <form class="flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
                        <div class="relative flex-1 min-w-[240px]">
                            <AppIcon name="search" :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="filterForm.search"
                                type="text"
                                class="ui-input !h-9 !pl-9 text-[13px]"
                                placeholder="Search leads..."
                            />
                        </div>

                        <select v-model="filterForm.status" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Statuses</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>

                        <select v-model="filterForm.source" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Sources</option>
                            <option v-for="source in sources" :key="source.value" :value="source.value">
                                {{ source.label }}
                            </option>
                        </select>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="ui-button-ghost !h-9 px-4 text-[12px]" @click="applyFilters">Filter</button>
                            <button type="button" class="ui-button-ghost !h-9 px-4 text-[12px]" @click="filterForm.reset(); applyFilters();">
                                Reset
                            </button>
                            <div class="mx-1 h-4 w-px bg-slate-200"></div>
                            <PrimaryButton type="button" class="!h-9 px-4 text-[12px]" @click="showCreate = true">
                                Add New Lead
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div v-if="leads.data.length" class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Name</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Contact</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Status</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Source</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Added</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ lead.name }}</p>
                                    <p class="text-[11px] text-slate-500">{{ lead.interested_visa_type || 'General inquiry' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-slate-700">{{ lead.email }}</p>
                                    <p class="text-[11px] text-slate-500">{{ lead.phone }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                                        {{ lead.status.label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ lead.source.label }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ lead.created_at }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('leads.show', lead.id)" class="ui-button-ghost !h-8 px-3 text-[12px]">
                                        Open
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState
                    v-else
                    icon="users"
                    title="No Leads Found"
                    description="No leads match your current filters."
                >
                    <template #action>
                        <PrimaryButton @click="showCreate = true">Add New Lead</PrimaryButton>
                    </template>
                </EmptyState>

                <div v-if="leads.links?.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/30 text-[12px] text-slate-500">
                    <p class="font-medium uppercase tracking-wider">{{ resultsLabel }}</p>
                    <PaginationLinks :links="leads.links" />
                </div>
            </div>
        </div>

        <SlideOver
            :show="showCreate"
            width="wide"
            title="Add New Lead"
            description="Capture the essentials now, then add education or work background when it helps qualification."
            @close="showCreate = false"
        >
            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="first_name" value="First name" />
                        <TextInput id="first_name" v-model="createForm.first_name" />
                        <InputError :message="createForm.errors.first_name" />
                    </div>
                    <div>
                        <InputLabel for="last_name" value="Last name" />
                        <TextInput id="last_name" v-model="createForm.last_name" />
                        <InputError :message="createForm.errors.last_name" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" v-model="createForm.email" type="email" />
                    </div>
                    <div>
                        <InputLabel for="phone" value="Phone" />
                        <TextInput id="phone" v-model="createForm.phone" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="lead_source" value="Source" />
                        <select id="lead_source" v-model="createForm.source" class="ui-select">
                            <option v-for="source in sources" :key="source.value" :value="source.value">
                                {{ source.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="lead_status" value="Starting status" />
                        <select id="lead_status" v-model="createForm.status" class="ui-select">
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="country_of_citizenship" value="Country of citizenship" />
                        <TextInput id="country_of_citizenship" v-model="createForm.country_of_citizenship" />
                    </div>
                    <div>
                        <InputLabel for="interested_visa_type" value="Interested visa type" />
                        <TextInput id="interested_visa_type" v-model="createForm.interested_visa_type" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Tags" />
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="tag in tags"
                            :key="tag.id"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
                        >
                            <input
                                v-model="createForm.tag_ids"
                                type="checkbox"
                                :value="tag.id"
                                class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary"
                            />
                            {{ tag.name }}
                        </label>
                    </div>
                </div>

                <div>
                    <InputLabel for="note" value="First note" />
                    <textarea
                        id="note"
                        v-model="createForm.note"
                        rows="5"
                        class="ui-textarea"
                        placeholder="What did they ask for, and what should the next agent know?"
                    ></textarea>
                </div>

                <div class="space-y-4 rounded-xl border border-brand-border bg-brand-neutral/50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-brand-text">Education</p>
                            <p class="mt-1 text-sm text-brand-muted">Optional study history that helps with assessment later.</p>
                        </div>
                        <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="addEducation">Add education</button>
                    </div>

                    <div v-if="createForm.education_history.length" class="space-y-4">
                        <div v-for="(item, index) in createForm.education_history" :key="`education-${index}`" class="rounded-lg border border-brand-border bg-white p-4">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-brand-text">Education {{ index + 1 }}</p>
                                <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="removeEducation(index)">Remove</button>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel :for="`education_institution_${index}`" value="Institution" />
                                    <TextInput :id="`education_institution_${index}`" v-model="item.institution" />
                                </div>
                                <div>
                                    <InputLabel :for="`education_degree_${index}`" value="Degree" />
                                    <TextInput :id="`education_degree_${index}`" v-model="item.degree" />
                                </div>
                            </div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel :for="`education_field_${index}`" value="Field of study" />
                                    <TextInput :id="`education_field_${index}`" v-model="item.field_of_study" />
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`education_start_${index}`" value="Start date" />
                                        <TextInput :id="`education_start_${index}`" v-model="item.start_date" type="date" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`education_end_${index}`" value="End date" />
                                        <TextInput :id="`education_end_${index}`" v-model="item.end_date" type="date" />
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <InputLabel :for="`education_notes_${index}`" value="Notes" />
                                <textarea :id="`education_notes_${index}`" v-model="item.notes" rows="3" class="ui-textarea" placeholder="Achievements, GPA, graduation status, or other context."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 rounded-xl border border-brand-border bg-brand-neutral/50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-brand-text">Work experience</p>
                            <p class="mt-1 text-sm text-brand-muted">Optional employment history for skilled, student, or family pathway screening.</p>
                        </div>
                        <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="addExperience">Add experience</button>
                    </div>

                    <div v-if="createForm.work_experience.length" class="space-y-4">
                        <div v-for="(item, index) in createForm.work_experience" :key="`experience-${index}`" class="rounded-lg border border-brand-border bg-white p-4">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-brand-text">Experience {{ index + 1 }}</p>
                                <button type="button" class="ui-button-ghost !h-8 px-3 text-[12px]" @click="removeExperience(index)">Remove</button>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel :for="`experience_company_${index}`" value="Company" />
                                    <TextInput :id="`experience_company_${index}`" v-model="item.company" />
                                </div>
                                <div>
                                    <InputLabel :for="`experience_title_${index}`" value="Job title" />
                                    <TextInput :id="`experience_title_${index}`" v-model="item.title" />
                                </div>
                            </div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel :for="`experience_location_${index}`" value="Location" />
                                    <TextInput :id="`experience_location_${index}`" v-model="item.location" />
                                </div>
                                <label class="flex items-center gap-2 rounded-lg border border-brand-border px-3 py-2 text-sm text-brand-text">
                                    <input v-model="item.is_current" type="checkbox" class="rounded border-brand-border text-brand-primary focus:ring-brand-primary" />
                                    Current role
                                </label>
                            </div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel :for="`experience_start_${index}`" value="Start date" />
                                    <TextInput :id="`experience_start_${index}`" v-model="item.start_date" type="date" />
                                </div>
                                <div>
                                    <InputLabel :for="`experience_end_${index}`" value="End date" />
                                    <TextInput :id="`experience_end_${index}`" v-model="item.end_date" :disabled="item.is_current" type="date" />
                                </div>
                            </div>
                            <div class="mt-4">
                                <InputLabel :for="`experience_notes_${index}`" value="Notes" />
                                <textarea :id="`experience_notes_${index}`" v-model="item.notes" rows="3" class="ui-textarea" placeholder="Responsibilities, years of experience, or relevance to the visa pathway."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex items-center justify-between gap-3">
                    <button type="button" class="ui-button-ghost" @click="showCreate = false">Never mind</button>
                    <PrimaryButton :loading="createForm.processing" icon="plus" @click="submit">Create Lead</PrimaryButton>
                </div>
            </template>
        </SlideOver>
    </AuthenticatedLayout>
</template>
