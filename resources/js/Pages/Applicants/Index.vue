<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import TagBadge from '@/Components/TagBadge.vue';
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

        <div class="ui-page-body space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/30 p-4">
                    <form class="flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
                        <div class="relative flex-1 min-w-[240px]">
                            <AppIcon name="search" :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                id="search"
                                v-model="filterForm.search"
                                type="text"
                                class="ui-input !h-9 !pl-9 text-[13px]"
                                placeholder="Search applicants..."
                            />
                        </div>

                        <select id="tag" v-model="filterForm.tag" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Tags</option>
                            <option v-for="tag in tags" :key="tag.slug" :value="tag.slug">
                                {{ tag.name }}
                            </option>
                        </select>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="ui-button-secondary !h-9 px-4 text-[12px]">Filter</button>
                            <button type="button" class="ui-button-ghost !h-9 px-4 text-[12px]" @click="filterForm.reset(); applyFilters();">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div v-if="applicants.data.length" class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Applicant</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Passport</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Nationality</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Tags</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Notes</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="applicant in applicants.data" :key="applicant.id" class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ applicant.name }}</p>
                                    <p class="text-[11px] text-slate-500">
                                        {{ applicant.email || 'No email yet' }} • {{ applicant.phone || 'No phone yet' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ applicant.passport_number || 'Not added yet' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ applicant.nationality || 'Not added yet' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        <TagBadge v-for="tag in applicant.tags" :key="tag.id" :label="tag.name" :color="tag.color" />
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ applicant.notes_count ?? 0 }} notes</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('applicants.show', applicant.id)" class="ui-button-ghost !h-8 px-3 text-[12px]">
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
                    title="No applicants yet"
                    description="Applicants will appear here once you convert a qualified lead into an active case profile."
                />

                <div v-if="applicants.data.length" class="p-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-[12px] text-slate-500">
                    <p>{{ resultsLabel }}</p>
                    <PaginationLinks :links="applicants.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
