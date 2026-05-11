<script setup>
import AppCard from '@/Components/AppCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TagBadge from '@/Components/TagBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    applicants: Object,
    filters: Object,
    tags: Array,
});

const filterForm = useForm({
    search: props.filters.search ?? '',
    tag: props.filters.tag ?? '',
});

const resultsLabel = computed(() => {
    if (!props.applicants.total) {
        return '0 results';
    }

    return `${props.applicants.from}-${props.applicants.to} of ${props.applicants.total} results`;
});

const applyFilters = () => {
    router.get(route('applicants.index'), filterForm.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head title="Applicants" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Applicants</p>
                    <h1 class="ui-header-title">Applicant records.</h1>
                    <p class="ui-header-copy">
                        Review passport details, notes, and travel history without overwhelming the team.
                    </p>
                </div>
            </div>
        </template>

        <AppCard>
            <div class="space-y-6">
                <div class="grid gap-4 lg:grid-cols-[1.4fr,0.8fr,auto] lg:items-end">
                    <div>
                        <InputLabel for="search" value="Search applicants" />
                        <TextInput id="search" v-model="filterForm.search" placeholder="Search by name, email, or passport number" />
                    </div>
                    <div>
                        <InputLabel for="tag" value="Tag segment" />
                        <select id="tag" v-model="filterForm.tag" class="ui-select">
                            <option value="">All tags</option>
                            <option v-for="tag in tags" :key="tag.slug" :value="tag.slug">
                                {{ tag.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" class="ui-button-secondary" @click="applyFilters">Filter</button>
                        <button
                            type="button"
                            class="ui-button-ghost"
                            @click="
                                filterForm.reset();
                                applyFilters();
                            "
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <div class="hidden overflow-hidden rounded-lg border border-brand-border md:block">
                    <table class="min-w-full divide-y divide-brand-border">
                        <thead class="ui-table-head">
                            <tr>
                                <th class="px-6 py-4 text-left">Applicant</th>
                                <th class="px-6 py-4 text-left">Passport</th>
                                <th class="px-6 py-4 text-left">Nationality</th>
                                <th class="px-6 py-4 text-left">Segment</th>
                                <th class="px-6 py-4 text-left">Profile health</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border bg-white">
                            <tr v-for="applicant in applicants.data" :key="applicant.id" class="ui-row-hover">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-brand-text">{{ applicant.name }}</p>
                                    <p class="mt-1 text-sm text-brand-muted">
                                        {{ applicant.email || 'No email yet' }} • {{ applicant.phone || 'No phone yet' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-sm text-brand-muted">{{ applicant.passport_number || 'Not added yet' }}</td>
                                <td class="px-6 py-4 text-sm text-brand-muted">{{ applicant.nationality || 'Not added yet' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <TagBadge
                                            v-for="tag in applicant.tags"
                                            :key="tag.id"
                                            :label="tag.name"
                                            :color="tag.color"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <StatusBadge :label="`${applicant.notes_count ?? 0} notes`" color="blue" />
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('applicants.show', applicant.id)" class="text-sm font-medium text-brand-primary hover:underline">
                                        Open profile
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-4 md:hidden">
                    <div v-for="applicant in applicants.data" :key="applicant.id" class="rounded-lg border border-brand-border bg-white p-4 shadow-card">
                        <p class="font-medium text-brand-text">{{ applicant.name }}</p>
                        <p class="mt-1 text-sm text-brand-muted">{{ applicant.passport_number || 'Passport not added yet' }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <TagBadge
                                v-for="tag in applicant.tags"
                                :key="tag.id"
                                :label="tag.name"
                                :color="tag.color"
                            />
                        </div>
                        <Link :href="route('applicants.show', applicant.id)" class="mt-4 inline-flex text-sm font-medium text-brand-primary">
                            Open profile
                        </Link>
                    </div>
                </div>

                <EmptyState
                    v-if="!applicants.data.length"
                    icon="users"
                    title="No applicants yet"
                    description="Applicants will appear here once you convert a qualified lead into an active case profile."
                />

                <div v-if="applicants.data.length" class="flex flex-col gap-4 border-t border-brand-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-brand-muted">{{ resultsLabel }}</p>
                    <PaginationLinks :links="applicants.links" />
                </div>
            </div>
        </AppCard>
    </AuthenticatedLayout>
</template>
