<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogFooter, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Check, Plus, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Option {
    value: string;
    label: string;
}

interface TaskRecord {
    id: number;
    title: string;
    description: null | string;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    due_at: null | string;
    completed_at: null | string;
    client_name: null | string;
    visa_case_reference: null | string;
    assignee_name: null | string;
}

const props = defineProps<{
    tasks: TaskRecord[];
    clients: Array<{ id: number; full_name: string }>;
    visaCases: Array<{ id: number; reference_code: string }>;
    users: Array<{ id: number; name: string }>;
    statusOptions: Option[];
    priorityOptions: Option[];
    stats: {
        open: number;
        dueToday: number;
        overdue: number;
    };
    filters: {
        search: string;
        status: string;
        priority: string;
    };
}>();

const page = usePage<SharedData>();
const isCreateDialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    },
];

const filterForm = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? 'all',
    priority: props.filters.priority ?? 'all',
});

const createForm = useForm({
    client_id: '',
    visa_case_id: '',
    assigned_user_id: '',
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    due_at: '',
});

const hasActiveFilters = computed(() => filterForm.search.trim() !== '' || filterForm.status !== 'all' || filterForm.priority !== 'all');

const submitFilters = () => {
    router.get(
        route('tasks.index'),
        {
            search: filterForm.search || undefined,
            status: filterForm.status !== 'all' ? filterForm.status : undefined,
            priority: filterForm.priority !== 'all' ? filterForm.priority : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = 'all';
    filterForm.priority = 'all';
    submitFilters();
};

const submit = () => {
    createForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.status = 'todo';
            createForm.priority = 'medium';
            isCreateDialogOpen.value = false;
        },
    });
};

const completeTask = (taskId: number) => {
    router.patch(
        route('tasks.update', taskId),
        { status: 'done' },
        {
            preserveScroll: true,
        },
    );
};

const formatDate = (value: null | string) =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'No due date';

const priorityClasses = (priority: string) =>
    ({
        low: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
        medium: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        high: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-200',
        urgent: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-200',
    })[priority] ?? 'bg-secondary text-secondary-foreground';

const statusClasses = (status: string) =>
    ({
        todo: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
        in_progress: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        done: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
    })[status] ?? 'bg-secondary text-secondary-foreground';
</script>

<template>
    <Head title="Tasks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-3 p-3 md:p-4">
            <div v-if="page.props.flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ page.props.flash.success }}
            </div>

            <section class="app-panel overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-border px-3.5 py-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-base font-semibold tracking-tight text-slate-950 dark:text-slate-50">Tasks</h1>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <span>{{ tasks.length }} tasks</span>
                        <Button type="button" class="gap-2" @click="isCreateDialogOpen = true">
                            <Plus class="size-4" />
                            New task
                        </Button>
                    </div>
                </div>

                <div class="grid gap-3 border-b border-border px-3.5 py-3 md:grid-cols-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Open</p>
                        <p class="mt-1 text-lg font-semibold tracking-tight text-foreground">{{ stats.open }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Due today</p>
                        <p class="mt-1 text-lg font-semibold tracking-tight text-foreground">{{ stats.dueToday }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Overdue</p>
                        <p class="mt-1 text-lg font-semibold tracking-tight text-amber-600 dark:text-amber-300">{{ stats.overdue }}</p>
                    </div>
                </div>

                <div class="border-b border-border px-3.5 py-3">
                    <form class="grid gap-2 md:grid-cols-[minmax(0,1fr)_180px_180px_auto_auto]" @submit.prevent="submitFilters">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="filterForm.search" class="pl-8" placeholder="Search title, client, case, assignee, or description" />
                        </div>

                        <select
                            v-model="filterForm.status"
                            class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                        >
                            <option value="all">All statuses</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>

                        <select
                            v-model="filterForm.priority"
                            class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                        >
                            <option value="all">All priorities</option>
                            <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>

                        <Button type="submit" variant="outline">Apply</Button>
                        <Button v-if="hasActiveFilters" type="button" variant="ghost" class="gap-2" @click="resetFilters">
                            <X class="size-4" />
                            Clear
                        </Button>
                    </form>
                </div>

                <div v-if="tasks.length === 0" class="px-3.5 py-4 text-sm text-muted-foreground">
                    {{ hasActiveFilters ? 'No tasks match the current filters.' : 'No tasks yet.' }}
                </div>

                <div v-else class="px-3.5 py-1">
                    <div v-for="task in tasks" :key="task.id" class="border-b border-border/70 py-2.5 last:border-b-0">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-foreground">{{ task.title }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="priorityClasses(task.priority)">
                                        {{ task.priority_label }}
                                    </span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="statusClasses(task.status)">
                                        {{ task.status_label }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    {{ task.client_name || 'General task'
                                    }}<span v-if="task.visa_case_reference"> • {{ task.visa_case_reference }}</span>
                                </p>
                                <p v-if="task.description" class="mt-1 text-sm text-muted-foreground">{{ task.description }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ task.assignee_name || 'Unassigned' }} • {{ formatDate(task.due_at) }}
                                </p>
                            </div>

                            <Button v-if="task.status !== 'done'" variant="outline" size="sm" class="gap-2" @click="completeTask(task.id)">
                                <Check class="size-4" />
                                Done
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            <Dialog v-model:open="isCreateDialogOpen">
                <DialogScrollContent class="max-w-4xl p-0">
                    <DialogHeader class="border-b border-border px-6 py-4">
                        <DialogTitle>New task</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4 px-6 py-5" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-1.5 md:col-span-2">
                                <Label for="title">Title</Label>
                                <Input id="title" v-model="createForm.title" placeholder="Request updated bank statement" />
                                <InputError :message="createForm.errors.title" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="client_id">Client</Label>
                                <select
                                    id="client_id"
                                    v-model="createForm.client_id"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option value="">No client</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.full_name }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.client_id" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="visa_case_id">Visa case</Label>
                                <select
                                    id="visa_case_id"
                                    v-model="createForm.visa_case_id"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option value="">No linked case</option>
                                    <option v-for="visaCase in visaCases" :key="visaCase.id" :value="visaCase.id">
                                        {{ visaCase.reference_code }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.visa_case_id" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="assigned_user_id">Assigned agent</Label>
                                <select
                                    id="assigned_user_id"
                                    v-model="createForm.assigned_user_id"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option value="">Unassigned</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.assigned_user_id" />
                            </div>

                            <div class="grid gap-2 md:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <Label for="status">Status</Label>
                                    <select
                                        id="status"
                                        v-model="createForm.status"
                                        class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                    >
                                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <InputError :message="createForm.errors.status" />
                                </div>

                                <div class="grid gap-1.5">
                                    <Label for="priority">Priority</Label>
                                    <select
                                        id="priority"
                                        v-model="createForm.priority"
                                        class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                    >
                                        <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <InputError :message="createForm.errors.priority" />
                                </div>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="due_at">Due</Label>
                                <Input id="due_at" v-model="createForm.due_at" type="datetime-local" />
                                <InputError :message="createForm.errors.due_at" />
                            </div>

                            <div class="grid gap-1.5 md:col-span-2">
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    v-model="createForm.description"
                                    rows="4"
                                    class="min-h-24 rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                    placeholder="Optional context or next-step details"
                                />
                                <InputError :message="createForm.errors.description" />
                            </div>
                        </div>

                        <DialogFooter class="border-t border-border pt-4">
                            <Button type="button" variant="ghost" @click="isCreateDialogOpen = false">Cancel</Button>
                            <Button :disabled="createForm.processing">Create task</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
