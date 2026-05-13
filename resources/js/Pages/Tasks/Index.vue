<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    tasks: Object,
    filters: Object,
    summary: Object,
    agents: Array,
    statuses: Array,
});

const filtersForm = useForm({
    search: props.filters.search ?? '',
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

const applyFilters = () => {
    filtersForm.get(route('tasks.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filtersForm.reset();
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
            <!-- Filters & List -->
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/30 p-4">
                    <form class="flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
                        <input
                            v-model="filtersForm.search"
                            type="text"
                            class="ui-input !h-9 text-[13px] min-w-[240px] flex-1"
                            placeholder="Search tasks, cases..."
                        />

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

                <div v-if="tasks.data.length" class="divide-y divide-slate-100">
                    <div
                        v-for="task in tasks.data"
                        :key="task.id"
                        class="p-4 hover:bg-slate-50/30 transition-colors group"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[15px] font-bold text-slate-900 leading-tight group-hover:text-black transition-colors">{{ task.name }}</p>
                                    <span 
                                        class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-slate-100 text-slate-600': task.status === 'pending',
                                            'bg-blue-50 text-blue-700': task.status === 'in_progress',
                                            'bg-emerald-50 text-emerald-700': task.status === 'completed',
                                            'bg-amber-50 text-amber-700': task.status === 'skipped',
                                        }"
                                    >
                                        {{ statusLabel(task.status) }}
                                    </span>
                                    <span v-if="task.overdue" class="rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-600 uppercase tracking-wider">
                                        Overdue
                                    </span>
                                </div>

                                <p v-if="task.description" class="mt-1.5 text-[13px] text-slate-500 leading-relaxed line-clamp-2">
                                    {{ task.description }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1.5 items-center text-[12px] text-slate-400">
                                    <span class="font-bold text-slate-600">{{ task.case.applicant_name }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                    <span class="font-medium">{{ task.case.reference_code }}</span>
                                    <span v-if="task.stage" class="flex items-center gap-1.5">
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        {{ task.stage }}
                                    </span>
                                    <span v-if="task.due_at" class="flex items-center gap-1.5" :class="task.overdue ? 'text-rose-500 font-bold' : ''">
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        Due {{ task.due_at }}
                                    </span>
                                    <span v-if="task.assignee" class="flex items-center gap-1.5">
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        {{ task.assignee }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    type="button"
                                    class="ui-button-ghost !h-8 px-3 text-[12px] opacity-0 group-hover:opacity-100 transition-opacity"
                                    :disabled="taskForms[task.id].processing"
                                    @click="markDone(task)"
                                >
                                    Mark done
                                </button>
                                <Link :href="route('cases.show', task.case.id)" class="ui-button-secondary !h-8 px-3 text-[12px]">
                                    Open case
                                </Link>
                            </div>
                        </div>

                        <!-- Row Quick Actions -->
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-50 pt-4">
                            <select v-model="taskForms[task.id].status" class="ui-select !h-8 text-[12px] w-auto bg-slate-50/50 border-transparent">
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>

                            <select v-model="taskForms[task.id].assigned_to_user_id" class="ui-select !h-8 text-[12px] w-auto bg-slate-50/50 border-transparent">
                                <option value="">Unassigned</option>
                                <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                    {{ agent.name }}
                                </option>
                            </select>

                            <input
                                v-model="taskForms[task.id].due_at"
                                type="date"
                                class="ui-input !h-8 text-[12px] w-auto bg-slate-50/50 border-transparent"
                            />

                            <button
                                type="button"
                                class="text-[12px] font-bold text-slate-400 hover:text-slate-900 transition-colors px-2"
                                :disabled="taskForms[task.id].processing"
                                @click="saveTask(task)"
                            >
                                {{ taskForms[task.id].processing ? 'Saving...' : 'Update' }}
                            </button>
                        </div>
                    </div>
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
                    <div class="flex flex-wrap gap-1.5">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="link in tasks.links"
                            :key="link.label"
                            :href="link.url"
                            class="rounded px-2.5 py-1.5 border transition-all duration-200"
                            :class="link.active ? 'border-slate-900 bg-slate-900 text-white font-bold' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
