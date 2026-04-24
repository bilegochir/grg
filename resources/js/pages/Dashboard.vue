<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ArrowUpRight, MapPin } from 'lucide-vue-next';

interface DashboardStatBlock {
    totalClients: number;
    activeCases: number;
    openTasks: number;
    overdueTasks: number;
}

interface DashboardClient {
    id: number;
    full_name: string;
    status: string;
    status_label: string;
    destination_country: null | string;
    created_at: null | string;
}

interface DashboardTask {
    id: number;
    title: string;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    due_at: null | string;
    client_name: null | string;
    assignee_name: null | string;
}

interface DashboardVisaCase {
    id: number;
    reference_code: string;
    visa_type: string;
    status: string;
    status_label: string;
    destination_country: string;
    client_name: null | string;
    assignee_name: null | string;
}

defineProps<{
    stats: DashboardStatBlock;
    recentClients: DashboardClient[];
    upcomingTasks: DashboardTask[];
    pipeline: DashboardVisaCase[];
}>();

const page = usePage<SharedData>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const formatDate = (value: null | string) => (value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(value)) : 'No date set');

const recentClientStatusClasses = (status: string) =>
    (
        ({
            lead: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
            qualified: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
            active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
            closed: 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200',
        }) as const
    )[status.trim().toLowerCase() as 'active' | 'closed' | 'lead' | 'qualified'] ?? 'bg-secondary text-secondary-foreground';

const taskPriorityClasses = (priority: string) =>
    (
        ({
            low: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
            medium: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
            high: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-200',
            urgent: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-200',
        }) as const
    )[priority.trim().toLowerCase() as 'high' | 'low' | 'medium' | 'urgent'] ?? 'bg-secondary text-secondary-foreground';

const taskStatusClasses = (status: string) =>
    (
        ({
            todo: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
            in_progress: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
            done: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        }) as const
    )[status.trim().toLowerCase() as 'done' | 'in_progress' | 'todo'] ?? 'bg-secondary text-secondary-foreground';

const visaCaseStatusClasses = (status: string) =>
    (
        ({
            intake: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
            documents_pending: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-200',
            ready_to_file: 'bg-violet-100 text-violet-800 dark:bg-violet-950/70 dark:text-violet-200',
            submitted: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
            approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
            rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-200',
            closed: 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200',
        }) as const
    )[status.trim().toLowerCase() as 'approved' | 'closed' | 'documents_pending' | 'intake' | 'ready_to_file' | 'rejected' | 'submitted'] ??
    'bg-secondary text-secondary-foreground';
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-3 p-3 md:p-4">
            <div
                v-if="page.props.flash.success"
                class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-200"
            >
                {{ page.props.flash.success }}
            </div>

            <section class="flex flex-col gap-1">
                <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">Overview</h2>
            </section>

            <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-4">
                <section class="app-panel px-3 py-2.5">
                    <p class="text-2xl font-semibold tracking-tight">{{ stats.totalClients }}</p>
                    <p class="mt-0.5 text-sm text-muted-foreground">Clients</p>
                </section>
                <section class="app-panel px-3 py-2.5">
                    <p class="text-2xl font-semibold tracking-tight">{{ stats.activeCases }}</p>
                    <p class="mt-0.5 text-sm text-muted-foreground">Active cases</p>
                </section>
                <section class="app-panel px-3 py-2.5">
                    <p class="text-2xl font-semibold tracking-tight">{{ stats.openTasks }}</p>
                    <p class="mt-0.5 text-sm text-muted-foreground">Open tasks</p>
                </section>
                <section class="app-panel px-3 py-2.5">
                    <p class="text-2xl font-semibold tracking-tight text-amber-700 dark:text-amber-300">{{ stats.overdueTasks }}</p>
                    <p class="mt-0.5 text-sm text-muted-foreground">Overdue</p>
                </section>
            </div>

            <div class="grid gap-3 xl:grid-cols-2">
                <section class="app-panel overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-border/70 px-3.5 py-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Upcoming tasks</h2>
                            <span
                                class="inline-flex min-w-6 items-center justify-center rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                            >
                                {{ upcomingTasks.length }}
                            </span>
                        </div>
                        <Button as-child variant="ghost" size="sm" class="gap-2 self-start md:self-auto">
                            <Link href="/tasks">
                                All tasks
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                    </div>

                    <div v-if="upcomingTasks.length === 0" class="flex flex-col items-start gap-3 px-3.5 py-6 text-sm text-muted-foreground">
                        <p>No open tasks yet.</p>
                        <Button as-child variant="outline" size="sm">
                            <Link href="/tasks">Open tasks</Link>
                        </Button>
                    </div>

                    <div v-else class="space-y-2.5 p-3.5">
                        <div v-for="task in upcomingTasks" :key="task.id" class="rounded-xl border border-border/70 bg-background p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-foreground sm:text-[15px]">{{ task.title }}</p>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ task.client_name || 'General task' }}</p>
                                </div>
                                <span
                                    class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-medium"
                                    :class="taskPriorityClasses(task.priority)"
                                >
                                    {{ task.priority_label }}
                                </span>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="taskStatusClasses(task.status)">
                                    {{ task.status_label }}
                                </span>
                                <span class="text-[11px] text-muted-foreground">{{ task.assignee_name || 'Unassigned' }}</span>
                                <span class="text-[11px] text-muted-foreground">Due {{ formatDate(task.due_at) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="app-panel overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-border/70 px-3.5 py-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Recent cases</h2>
                            <span
                                class="inline-flex min-w-6 items-center justify-center rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                            >
                                {{ pipeline.length }}
                            </span>
                        </div>
                        <Button as-child variant="ghost" size="sm" class="gap-2 self-start md:self-auto">
                            <Link href="/visa-cases">
                                All cases
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                    </div>

                    <div v-if="pipeline.length === 0" class="flex flex-col items-start gap-3 px-3.5 py-6 text-sm text-muted-foreground">
                        <p>No cases yet.</p>
                        <Button as-child variant="outline" size="sm">
                            <Link href="/visa-cases">Open cases</Link>
                        </Button>
                    </div>

                    <div v-else class="space-y-2.5 p-3.5">
                        <Link
                            v-for="visaCase in pipeline"
                            :key="visaCase.id"
                            :href="route('visa-cases.show', visaCase.id)"
                            class="group block rounded-xl border border-border/70 bg-background p-4 transition-colors hover:bg-muted/30"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-foreground sm:text-[15px]">{{ visaCase.reference_code }}</p>
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        {{ visaCase.client_name || 'No client linked' }} • {{ visaCase.visa_type }}
                                    </p>
                                </div>
                                <span
                                    class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-medium"
                                    :class="visaCaseStatusClasses(visaCase.status)"
                                >
                                    {{ visaCase.status_label }}
                                </span>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground"
                                >
                                    <MapPin class="size-3" />
                                    {{ visaCase.destination_country }}
                                </span>
                                <span class="text-[11px] text-muted-foreground">{{ visaCase.assignee_name || 'Unassigned' }}</span>
                            </div>
                        </Link>
                    </div>
                </section>
            </div>

            <section class="app-panel overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-border/70 px-3.5 py-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Recent clients</h2>
                        <span
                            class="inline-flex min-w-6 items-center justify-center rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                        >
                            {{ recentClients.length }}
                        </span>
                    </div>
                    <Button as-child variant="ghost" size="sm" class="gap-2 self-start md:self-auto">
                        <Link href="/clients">
                            All clients
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </div>

                <div v-if="recentClients.length === 0" class="flex flex-col items-start gap-3 px-3.5 py-6 text-sm text-muted-foreground">
                    <p>No clients yet.</p>
                    <Button as-child variant="outline" size="sm">
                        <Link href="/clients">Open clients</Link>
                    </Button>
                </div>

                <div v-else class="grid content-start gap-2.5 p-3.5 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="client in recentClients"
                        :key="client.id"
                        :href="route('clients.show', client.id)"
                        class="group rounded-xl border border-border/70 bg-background p-4 transition-colors hover:bg-muted/30"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-slate-900/5 text-sm font-semibold text-slate-900 ring-1 ring-black/5 dark:bg-white/10 dark:text-white dark:ring-white/10"
                            >
                                {{ getInitials(client.full_name) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="truncate text-sm font-semibold text-foreground sm:text-[15px]">{{ client.full_name }}</p>
                                    <span
                                        class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="recentClientStatusClasses(client.status)"
                                    >
                                        {{ client.status_label }}
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground"
                                    >
                                        <MapPin class="size-3" />
                                        {{ client.destination_country || 'Destination not set' }}
                                    </span>
                                    <span class="text-[11px] text-muted-foreground">Added {{ formatDate(client.created_at) }}</span>
                                </div>

                                <div class="mt-3 flex items-center justify-between border-t border-border/70 pt-3 text-sm">
                                    <span class="font-medium text-foreground/80 transition-colors group-hover:text-foreground">Open profile</span>
                                    <ArrowUpRight
                                        class="size-4 text-muted-foreground transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-foreground"
                                    />
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
