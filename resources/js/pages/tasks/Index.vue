<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogFooter, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Plus, Search, X, Calendar, User, Briefcase, Filter, Hash } from 'lucide-vue-next';
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
        low: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        medium: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-100 dark:border-blue-800',
        high: 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 border-orange-100 dark:border-orange-800',
        urgent: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 border-red-100 dark:border-red-800',
    })[priority] ?? 'bg-secondary text-secondary-foreground';

const statusClasses = (status: string) =>
    ({
        todo: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        in_progress: 'bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800',
        done: 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
    })[status] ?? 'bg-secondary text-secondary-foreground';
</script>

<template>
    <Head title="Tasks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto space-y-6 p-4 md:p-6 lg:p-8">

            <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="page.props.flash.success" class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-400">
                    <CheckCircle2 class="size-5" />
                    <p class="text-sm font-medium">{{ page.props.flash.success }}</p>
                </div>
            </transition>

            <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Task Management</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track and manage your operational workflows</p>
                </div>
                <Button @click="isCreateDialogOpen = true" class="rounded-full shadow-lg shadow-primary/20 transition-all active:scale-95">
                    <Plus class="mr-2 size-4" />
                    Create Task
                </Button>
            </header>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Open Tasks</p>
                    <p class="mt-2 text-3xl font-bold">{{ stats.open }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Due Today</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.dueToday }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Overdue</p>
                    <p class="mt-2 text-3xl font-bold text-rose-600 dark:text-rose-400">{{ stats.overdue }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-2 shadow-sm">
                <form @submit.prevent="submitFilters" class="flex flex-col gap-2 md:flex-row">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            v-model="filterForm.search"
                            placeholder="Search tasks, clients..."
                            class="border-none bg-transparent pl-10 focus-visible:ring-0 shadow-none"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2 p-1 md:p-0">
                        <select v-model="filterForm.status" class="h-9 rounded-lg border-none bg-slate-100 dark:bg-slate-800 px-3 text-xs font-medium focus:ring-2 focus:ring-primary/20">
                            <option value="all">All Status</option>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>

                        <select v-model="filterForm.priority" class="h-9 rounded-lg border-none bg-slate-100 dark:bg-slate-800 px-3 text-xs font-medium focus:ring-2 focus:ring-primary/20">
                            <option value="all">All Priority</option>
                            <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>

                        <div class="h-6 w-[1px] bg-border mx-1 hidden md:block"></div>

                        <Button type="submit" size="sm" variant="secondary" class="rounded-lg h-9">
                            <Filter class="mr-2 size-3" /> Filter
                        </Button>

                        <Button v-if="hasActiveFilters" @click="resetFilters" type="button" size="sm" variant="ghost" class="rounded-lg h-9">
                            <X class="mr-2 size-3" /> Clear
                        </Button>
                    </div>
                </form>
            </div>

            <div class="space-y-3">
                <div v-if="tasks.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-border bg-slate-50/50 py-16 dark:bg-slate-900/20">
                    <div class="rounded-full bg-slate-100 p-4 dark:bg-slate-800">
                        <Briefcase class="size-8 text-slate-400" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">No tasks found</h3>
                    <p class="mt-1 text-sm text-slate-500">Try adjusting your filters or create a new task to get started.</p>
                </div>

                <div v-for="task in tasks" :key="task.id"
                    class="group relative flex flex-col gap-4 rounded-2xl border border-border bg-card p-5 transition-all hover:shadow-md md:flex-row md:items-center md:justify-between"
                >
                    <div class="flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ task.title }}</h3>
                            <div class="flex gap-1.5">
                                <span :class="[priorityClasses(task.priority), 'inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider']">
                                    {{ task.priority_label }}
                                </span>
                                <span :class="[statusClasses(task.status), 'inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider']">
                                    {{ task.status_label }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                            <div class="flex items-center gap-1.5">
                                <User class="size-3.5" />
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ task.client_name || 'General' }}</span>
                            </div>
                            <div v-if="task.visa_case_reference" class="flex items-center gap-1.5">
                                <Hash class="size-3.5" />
                                <span>{{ task.visa_case_reference }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Calendar class="size-3.5" />
                                <span :class="{'text-rose-500 font-medium': task.status !== 'done' && new Date(task.due_at ?? '') < new Date()}">
                                    {{ formatDate(task.due_at) }}
                                </span>
                            </div>
                        </div>

                        <p v-if="task.description" class="line-clamp-1 text-sm text-slate-500">{{ task.description }}</p>
                    </div>

                    <div class="flex items-center justify-between border-t border-border pt-4 md:border-none md:pt-0">
                        <div class="flex items-center gap-2 md:mr-4">
                            <div class="flex size-7 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary">
                                {{ task.assignee_name?.split(' ').map(n => n[0]).join('') || '?' }}
                            </div>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ task.assignee_name || 'Unassigned' }}</span>
                        </div>

                        <Button
                            v-if="task.status !== 'done'"
                            variant="outline"
                            size="sm"
                            @click="completeTask(task.id)"
                            class="rounded-full border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 dark:border-emerald-900/50 dark:hover:bg-emerald-900/20"
                        >
                            <CheckCircle2 class="mr-2 size-4" />
                            Mark Done
                        </Button>
                    </div>
                </div>
            </div>

            <Dialog v-model:open="isCreateDialogOpen">
                <DialogScrollContent class="sm:max-w-[600px] p-0 overflow-hidden rounded-3xl">
                    <DialogHeader class="bg-slate-50/50 dark:bg-slate-900/50 border-b p-6 text-left">
                        <DialogTitle class="text-xl font-bold">New Task Details</DialogTitle>
                    </DialogHeader>

                    <form @submit.prevent="submit" class="p-6">
                        <div class="grid gap-6">
                            <div class="space-y-2">
                                <Label for="title" class="text-xs font-bold uppercase tracking-wider text-slate-500">Task Title</Label>
                                <Input id="title" v-model="createForm.title" placeholder="e.g. Verify residency documents" class="h-11 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary/10" />
                                <InputError :message="createForm.errors.title" />
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Client</Label>
                                    <select v-model="createForm.client_id" class="flex h-11 w-full rounded-xl border border-slate-200 bg-background px-3 text-sm focus:outline-none focus:ring-4 focus:ring-primary/10">
                                        <option value="">No client</option>
                                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.full_name }}</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Assigned Agent</Label>
                                    <select v-model="createForm.assigned_user_id" class="flex h-11 w-full rounded-xl border border-slate-200 bg-background px-3 text-sm focus:outline-none focus:ring-4 focus:ring-primary/10">
                                        <option value="">Unassigned</option>
                                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</Label>
                                    <select v-model="createForm.status" class="flex h-11 w-full rounded-xl border border-slate-200 bg-background px-3 text-sm focus:outline-none focus:ring-4 focus:ring-primary/10">
                                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Priority</Label>
                                    <select v-model="createForm.priority" class="flex h-11 w-full rounded-xl border border-slate-200 bg-background px-3 text-sm focus:outline-none focus:ring-4 focus:ring-primary/10">
                                        <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Due Date</Label>
                                    <Input v-model="createForm.due_at" type="datetime-local" class="h-11 rounded-xl border-slate-200" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Description</Label>
                                <textarea v-model="createForm.description" rows="3" class="w-full rounded-xl border border-slate-200 bg-background p-3 text-sm focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="Provide additional details..."></textarea>
                            </div>
                        </div>

                        <DialogFooter class="mt-8 gap-2">
                            <Button type="button" variant="ghost" @click="isCreateDialogOpen = false" class="rounded-xl">Cancel</Button>
                            <Button :disabled="createForm.processing" class="rounded-xl px-8">Create Task</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
.app-panel {
    @apply bg-card rounded-2xl border border-border shadow-sm;
}
</style>
