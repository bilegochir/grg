<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SlideOver from '@/Components/SlideOver.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    cases: Object,
    filters: Object,
    countries: Array,
    priorities: Array,
    caseMeta: Object,
});

const urlParams = new URLSearchParams(window.location.search);
const showCreate = ref(urlParams.get('create') === 'true');

const filterForm = useForm({
    search: props.filters.search ?? '',
    country: props.filters.country ?? '',
    priority: props.filters.priority ?? '',
});

const createForm = useForm({
    applicant_id: '',
    visa_type_id: '',
    branch_id: '',
    assigned_to_user_id: '',
    priority: 'normal',
    expected_submission_at: '',
    expected_decision_at: '',
    internal_note: '',
    client_note: '',
});

const resultsLabel = computed(() => {
    if (!props.cases.total) {
        return '0 results';
    }

    return `${props.cases.from}-${props.cases.to} of ${props.cases.total} results`;
});

const applicantOptions = computed(() => props.caseMeta.applicants.map((applicant) => ({
    id: applicant.id,
    label: `${applicant.first_name} ${applicant.last_name}`.trim(),
})));

const visaTypeOptions = computed(() => props.caseMeta.visaTypes.map((visaType) => ({
    id: visaType.id,
    label: `${visaType.country.name} • ${visaType.name}`,
})));

const applyFilters = () => {
    router.get(route('cases.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const submit = () => {
    createForm.post(route('cases.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
            createForm.branch_id = '';
            createForm.priority = 'normal';
        },
    });
};
</script>

<template>
    <Head title="Cases" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Cases</p>
                    <h1 class="ui-header-title text-[28px] tracking-tight">Embassy Workflow</h1>
                    <p class="ui-header-copy text-[14px] leading-relaxed">
                        Track every visa application from initial review to final decision. See ownership, stage, and urgency at a glance.
                    </p>
                </div>
            </div>
        </template>

        <div class="ui-page-body space-y-6">
            <!-- Filters & Table -->
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/30 p-4">
                    <form class="flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
                        <input
                            v-model="filterForm.search"
                            type="text"
                            class="ui-input !h-9 text-[13px] min-w-[240px] flex-1"
                            placeholder="Reference, applicant, visa type..."
                        />

                        <select v-model="filterForm.country" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Countries</option>
                            <option v-for="country in countries" :key="country.slug" :value="country.slug">
                                {{ country.name }}
                            </option>
                        </select>

                        <select v-model="filterForm.priority" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Priorities</option>
                            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">
                                {{ priority.label }}
                            </option>
                        </select>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="ui-button-ghost !h-9 px-4 text-[12px]" @click="applyFilters">Filter</button>
                            <button type="button" class="ui-button-ghost !h-9 px-4 text-[12px]" @click="filterForm.reset(); applyFilters();">
                                Reset
                            </button>
                            <div class="mx-1 h-4 w-px bg-slate-200"></div>
                            <PrimaryButton type="button" class="!h-9 px-4 text-[12px]" @click="showCreate = true">
                                Create Case
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div v-if="cases.data.length" class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Reference</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Applicant</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Visa Type</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Stage</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Priority</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Owner</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="visaCase in cases.data" :key="visaCase.id" class="hover:bg-slate-50/30 transition-colors group">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ visaCase.reference_code }}</p>
                                    <p class="text-[11px] text-slate-500 uppercase font-medium">{{ visaCase.country.name }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-slate-900 font-medium">{{ visaCase.applicant_name }}</p>
                                    <p class="text-[11px] text-slate-500">{{ visaCase.branch || 'Corporate' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-slate-600 truncate max-w-[180px] block">{{ visaCase.visa_type }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="visaCase.stage" class="flex items-center gap-2">
                                        <div class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: visaCase.stage.color }"></div>
                                        <span class="font-medium text-slate-700">{{ visaCase.stage.name }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">No stage</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-rose-50 text-rose-700': visaCase.priority.value === 'high',
                                            'bg-blue-50 text-blue-700': visaCase.priority.value === 'normal',
                                            'bg-slate-100 text-slate-600': visaCase.priority.value === 'low',
                                        }"
                                    >
                                        {{ visaCase.priority.label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200 uppercase">
                                            {{ (visaCase.assigned_to || 'UN')[0] }}
                                        </div>
                                        <span class="text-slate-600">{{ visaCase.assigned_to || 'Unassigned' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('cases.show', visaCase.id)" class="ui-button-ghost !h-8 px-3 text-[12px]">
                                        Open
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState
                    v-else
                    icon="map"
                    title="No Cases Found"
                    description="No active visa cases match your current filters."
                >
                    <template #action>
                        <PrimaryButton @click="showCreate = true">Create new case</PrimaryButton>
                    </template>
                </EmptyState>

                <div v-if="cases.links?.length > 3" class="p-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-[12px] text-slate-500">
                    <p>{{ resultsLabel }}</p>
                    <PaginationLinks :links="cases.links" />
                </div>
            </div>
        </div>

        <SlideOver
            :show="showCreate"
            width="wide"
            title="Create New Case"
            description="Choose the country and visa type, then set expectations so the team knows what should happen next."
            @close="showCreate = false"
        >
            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-4">
                    <div>
                        <InputLabel for="applicant_id" value="Applicant" />
                        <select id="applicant_id" v-model="createForm.applicant_id" class="ui-select">
                            <option value="">Choose an applicant</option>
                            <option v-for="applicant in applicantOptions" :key="applicant.id" :value="applicant.id">
                                {{ applicant.label }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.applicant_id" />
                    </div>
                    <div>
                        <InputLabel for="visa_type_id" value="Visa type" />
                        <select id="visa_type_id" v-model="createForm.visa_type_id" class="ui-select">
                            <option value="">Choose a visa type</option>
                            <option v-for="visaType in visaTypeOptions" :key="visaType.id" :value="visaType.id">
                                {{ visaType.label }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.visa_type_id" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="branch_id" value="Branch" />
                        <select id="branch_id" v-model="createForm.branch_id" class="ui-select">
                            <option value="">Use assigned staff branch</option>
                            <option v-for="branch in caseMeta.branches" :key="branch.id" :value="branch.id">
                                {{ branch.name }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.branch_id" />
                    </div>
                    <div>
                        <InputLabel for="assigned_to_user_id" value="Assigned agent" />
                        <select id="assigned_to_user_id" v-model="createForm.assigned_to_user_id" class="ui-select">
                            <option value="">Assign later</option>
                            <option v-for="staff in caseMeta.staff" :key="staff.id" :value="staff.id">
                                {{ staff.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="priority" value="Priority" />
                        <select id="priority" v-model="createForm.priority" class="ui-select">
                            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">
                                {{ priority.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="expected_submission_at" value="Expected submission date" />
                        <TextInput id="expected_submission_at" v-model="createForm.expected_submission_at" type="date" />
                    </div>
                    <div>
                        <InputLabel for="expected_decision_at" value="Expected decision date" />
                        <TextInput id="expected_decision_at" v-model="createForm.expected_decision_at" type="date" />
                        <InputError :message="createForm.errors.expected_decision_at" />
                    </div>
                </div>

                <div>
                    <InputLabel for="internal_note" value="Internal note" />
                    <textarea
                        id="internal_note"
                        v-model="createForm.internal_note"
                        rows="4"
                        class="ui-textarea"
                        placeholder="Context for staff only: risk flags, missing documents, or staffing instructions."
                    ></textarea>
                </div>

                <div>
                    <InputLabel for="client_note" value="Client-visible note" />
                    <textarea
                        id="client_note"
                        v-model="createForm.client_note"
                        rows="4"
                        class="ui-textarea"
                        placeholder="Optional note the applicant could safely see later."
                    ></textarea>
                </div>
            </form>

            <template #footer>
                <div class="flex items-center justify-between gap-3">
                    <button type="button" class="ui-button-ghost" @click="showCreate = false">Never mind</button>
                    <PrimaryButton :loading="createForm.processing" icon="plus" @click="submit">Create case</PrimaryButton>
                </div>
            </template>
        </SlideOver>
    </AuthenticatedLayout>
</template>
