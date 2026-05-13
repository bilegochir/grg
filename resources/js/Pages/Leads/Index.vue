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

const pathwayOptions = ['Student', 'Visitor', 'Partner', 'Skilled', 'Employer-sponsored', 'Other'];

const createForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    source: props.sources[0]?.value ?? '',
    status: props.statuses[0]?.value ?? '',
    country_of_citizenship: '',
    pathway_interest: '',
    current_country: '',
    target_intake_date: '',
    interested_visa_type: '',
    tag_ids: [],
    note: '',
});

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
                    <h1 class="ui-header-title">Lead Pipeline</h1>
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
            description="Capture the essentials now, including likely pathway and timing, then deepen eligibility later if the lead progresses."
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

                <div>
                    <InputLabel for="date_of_birth" value="Date of birth" />
                    <TextInput id="date_of_birth" v-model="createForm.date_of_birth" type="date" />
                    <InputError :message="createForm.errors.date_of_birth" />
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
                        <InputError :message="createForm.errors.country_of_citizenship" />
                    </div>
                    <div>
                        <InputLabel for="pathway_interest" value="Pathway interest" />
                        <select id="pathway_interest" v-model="createForm.pathway_interest" class="ui-select">
                            <option value="">Choose a pathway</option>
                            <option v-for="pathway in pathwayOptions" :key="pathway" :value="pathway">
                                {{ pathway }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.pathway_interest" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="current_country" value="Current country" />
                        <TextInput id="current_country" v-model="createForm.current_country" placeholder="Mongolia" />
                        <InputError :message="createForm.errors.current_country" />
                    </div>
                    <div>
                        <InputLabel for="interested_visa_type" value="Interested visa type" />
                        <TextInput id="interested_visa_type" v-model="createForm.interested_visa_type" />
                        <InputError :message="createForm.errors.interested_visa_type" />
                    </div>
                </div>

                <div>
                    <InputLabel for="target_intake_date" value="Target intake or travel date" />
                    <TextInput id="target_intake_date" v-model="createForm.target_intake_date" type="date" />
                    <InputError :message="createForm.errors.target_intake_date" />
                    <p class="ui-helper">Helpful for Australia timing without making intake too heavy.</p>
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
