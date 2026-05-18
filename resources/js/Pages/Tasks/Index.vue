<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    tasks: Object,
    filters: Object,
    summary: Object,
    agents: Array,
    statuses: Array,
});

const filtersForm = useForm({
    search: props.filters.search ?? '',
    bucket: props.filters.bucket ?? 'all',
    status: props.filters.status ?? '',
    assigned_to: props.filters.assigned_to ?? '',
});

const taskForms = reactive(
    Object.fromEntries(
        props.tasks.data.map((task) => [
            task.id,
            {
                status: task.status,
                assigned_to_user_id: task.assigned_to_user_id ?? '',
                due_at: task.due_at ?? '',
                processing: false,
            },
        ]),
    ),
);

const statusLabel = (status) => ({
    pending: 'Pending',
    in_progress: 'In progress',
    completed: 'Completed',
    skipped: 'Skipped',
}[status] ?? status);

const statusBadgeClass = (status) => ({
    'ui-status-badge-new': status === 'pending',
    'ui-status-badge-contacted': status === 'in_progress',
    'ui-status-badge-approved': status === 'completed',
    'ui-status-badge-rejected': status === 'skipped',
});

const resultsLabel = computed(() => {
    if (!props.tasks.total) {
        return '0 tasks';
    }

    return `${props.tasks.from}-${props.tasks.to} of ${props.tasks.total} tasks`;
});

const taskSections = computed(() => {
    const overdue = props.tasks.data.filter((task) => task.overdue);
    const active = props.tasks.data.filter((task) => !task.overdue && ['pending', 'in_progress'].includes(task.status));
    const done = props.tasks.data.filter((task) => ['completed', 'skipped'].includes(task.status));

    return [
        {
            key: 'overdue',
            title: 'Needs attention',
            description: 'Overdue work that is most likely blocking case progress.',
            tasks: overdue,
            tone: 'border-rose-200 bg-rose-50/40',
        },
        {
            key: 'active',
            title: 'Active queue',
            description: 'Tasks currently being worked or waiting to be picked up.',
            tasks: active,
            tone: 'border-slate-200 bg-white',
        },
        {
            key: 'done',
            title: 'Completed on this page',
            description: 'Finished or intentionally skipped items.',
            tasks: done,
            tone: 'border-slate-200 bg-slate-50/40',
        },
    ].filter((section) => section.tasks.length);
});

const filterSummaryCards = computed(() => ([
    {
        key: 'total',
        label: 'All tasks',
        value: props.summary.total,
        active: filtersForm.bucket === 'all',
        onClick: () => {
            filtersForm.bucket = 'all';
            filtersForm.status = '';
            applyFilters();
        },
    },
    {
        key: 'open',
        label: 'Open',
        value: props.summary.open,
        active: filtersForm.bucket === 'open',
        onClick: () => {
            filtersForm.bucket = 'open';
            filtersForm.status = '';
            applyFilters();
        },
    },
    {
        key: 'overdue',
        label: 'Overdue',
        value: props.summary.overdue,
        active: filtersForm.bucket === 'overdue',
        onClick: () => {
            filtersForm.bucket = 'overdue';
            filtersForm.status = '';
            applyFilters();
        },
    },
    {
        key: 'completed',
        label: 'Completed',
        value: props.summary.completed,
        active: filtersForm.bucket === 'completed',
        onClick: () => {
            filtersForm.bucket = 'completed';
            filtersForm.status = 'completed';
            applyFilters();
        },
    },
]));

const applyFilters = () => {
    filtersForm.get(route('tasks.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filtersForm.search = '';
    filtersForm.bucket = 'all';
    filtersForm.status = '';
    filtersForm.assigned_to = '';
    applyFilters();
};

const saveTask = (task) => {
    taskForms[task.id].processing = true;

    router.patch(route('cases.tasks.update', { case: task.case.id, task: task.id }), {
        status: taskForms[task.id].status,
        assigned_to_user_id: taskForms[task.id].assigned_to_user_id || null,
        due_at: taskForms[task.id].due_at || null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            taskForms[task.id].processing = false;
        },
    });
};

const markDone = (task) => {
    taskForms[task.id].status = 'completed';
    saveTask(task);
};
</script>

<template>
    <Head title="Tasks" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Tasks</p>
                    <h1 class="ui-header-title">Team Queue</h1>
                    <p class="ui-header-copy text-[14px] leading-relaxed">
                        A centralized view of all pending case work. Process quick updates or jump into the full case for context.
                    </p>
                </div>
            </div>
        </template>

        <div class="ui-page-body space-y-6">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <button
                    v-for="card in filterSummaryCards"
                    :key="card.key"
                    type="button"
                    class="rounded-xl border px-4 py-4 text-left transition-colors"
                    :class="card.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white hover:bg-slate-50'"
                    @click="card.onClick"
                >
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em]" :class="card.active ? 'text-white/70' : 'text-slate-400'">
                        {{ card.label }}
                    </p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight" :class="card.active ? 'text-white' : 'text-slate-900'">
                        {{ card.value }}
                    </p>
                </button>
            </div>

            <!-- Filters & List -->
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/30 p-4">
                    <form class="flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
                        <div class="relative min-w-[240px] flex-1">
                            <AppIcon name="search" :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="filtersForm.search"
                                type="text"
                                class="ui-input !h-9 !pl-9 text-[13px] w-full"
                                placeholder="Search tasks, applicants, cases..."
                            />
                        </div>

                        <select v-model="filtersForm.status" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Statuses</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>

                        <select v-model="filtersForm.assigned_to" class="ui-select !h-9 text-[13px] w-auto">
                            <option value="">All Assignees</option>
                            <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                {{ agent.name }}
                            </option>
                        </select>

                        <div class="flex items-center gap-2">
                            <PrimaryButton type="submit" class="!h-9 px-4 text-[12px]">Apply</PrimaryButton>
                            <button type="button" class="ui-button-ghost !h-9 px-4 text-[12px]" @click="resetFilters">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div v-if="tasks.data.length" class="space-y-6 p-4">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50/50 px-4 py-3 text-[12px] text-slate-500">
                        <p class="font-medium uppercase tracking-wider">{{ resultsLabel }}</p>
                        <p>Quick-edit status, assignee, and due date without leaving the queue.</p>
                    </div>

                    <section
                        v-for="section in taskSections"
                        :key="section.key"
                        class="rounded-xl border"
                        :class="section.tone"
                    >
                        <div class="border-b border-slate-200/70 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">{{ section.title }}</p>
                            <p class="mt-1 text-[12px] text-slate-500">{{ section.description }}</p>
                        </div>

                        <div class="divide-y divide-slate-200/70">
                            <div
                                v-for="task in section.tasks"
                                :key="task.id"
                                class="p-4 transition-colors hover:bg-white/60"
                            >
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-[15px] font-semibold leading-tight text-slate-900">{{ task.name }}</p>
                                            <span class="ui-status-badge" :class="statusBadgeClass(task.status)">
                                                <span class="ui-status-badge-dot"></span>
                                                {{ statusLabel(task.status) }}
                                            </span>
                                            <span v-if="task.overdue" class="ui-status-badge ui-status-badge-rejected">
                                                <span class="ui-status-badge-dot"></span>
                                                Overdue
                                            </span>
                                        </div>

                                        <p v-if="task.description" class="mt-2 text-[13px] leading-relaxed text-slate-500 line-clamp-2">
                                            {{ task.description }}
                                        </p>

                                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[12px] text-slate-500">
                                            <span class="font-medium text-slate-700">{{ task.case.applicant_name }}</span>
                                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                            <span>{{ task.case.reference_code }}</span>
                                            <span v-if="task.case.current_stage" class="flex items-center gap-1.5">
                                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                                {{ task.case.current_stage }}
                                            </span>
                                            <span v-if="task.stage" class="flex items-center gap-1.5">
                                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                                {{ task.stage }}
                                            </span>
                                            <span v-if="task.due_at" class="flex items-center gap-1.5" :class="task.overdue ? 'font-semibold text-rose-600' : ''">
                                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                                Due {{ task.due_at }}
                                            </span>
                                            <span v-if="task.assignee" class="flex items-center gap-1.5">
                                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                                {{ task.assignee }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="ui-button-ghost !h-8 px-3 text-[12px]"
                                            :disabled="taskForms[task.id].processing || task.status === 'completed'"
                                            @click="markDone(task)"
                                        >
                                            Mark done
                                        </button>
                                        <Link :href="route('cases.show', task.case.id)" class="ui-button-secondary !h-8 px-3 text-[12px]">
                                            Open case
                                        </Link>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 border-t border-slate-200/70 pt-4 md:grid-cols-[minmax(0,180px)_minmax(0,200px)_minmax(0,180px)_auto]">
                                    <select v-model="taskForms[task.id].status" class="ui-select !h-8 text-[12px]">
                                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                                            {{ status.label }}
                                        </option>
                                    </select>

                                    <select v-model="taskForms[task.id].assigned_to_user_id" class="ui-select !h-8 text-[12px]">
                                        <option value="">Unassigned</option>
                                        <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                            {{ agent.name }}
                                        </option>
                                    </select>

                                    <input
                                        v-model="taskForms[task.id].due_at"
                                        type="date"
                                        class="ui-input !h-8 text-[12px]"
                                    />

                                    <div class="flex items-center justify-start md:justify-end">
                                        <button
                                            type="button"
                                            class="ui-button-secondary !h-8 px-3 text-[12px]"
                                            :disabled="taskForms[task.id].processing"
                                            @click="saveTask(task)"
                                        >
                                            {{ taskForms[task.id].processing ? 'Saving...' : 'Save changes' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <EmptyState
                    v-else
                    icon="check"
                    title="Queue Clear"
                    description="No tasks found matching your filters. You're all caught up!"
                />

                <div v-if="tasks.links?.length > 3" class="p-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-[12px] text-slate-500">
                    <p>
                        Showing {{ tasks.from ?? 0 }}-{{ tasks.to ?? 0 }} of {{ tasks.total ?? 0 }} tasks
                    </p>
                    <PaginationLinks :links="tasks.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
