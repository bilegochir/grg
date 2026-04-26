<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown } from 'lucide-vue-next';

interface VisaCaseTask {
    id: number;
    title: string;
    status_label: string;
    priority_label: string;
    assignee_name: null | string;
    due_at: null | string;
}

defineProps<{
    tasks: VisaCaseTask[];
    attachmentsCount: number;
    visaCaseId: number;
}>();

const formatDateOnly = (value: null | string) => {
    if (!value) return 'No due date';

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
    }).format(new Date(`${value.slice(0, 10)}T00:00:00`));
};

const taskStatusClasses = (status: string) =>
    ({
        Completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        'In progress': 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        Todo: 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200',
        Blocked: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-200',
    })[status] ?? 'bg-muted text-muted-foreground';

const taskPriorityClasses = (priority: string) =>
    ({
        High: 'text-rose-600 dark:text-rose-300',
        Medium: 'text-amber-600 dark:text-amber-300',
        Low: 'text-slate-500 dark:text-slate-400',
    })[priority] ?? 'text-muted-foreground';
</script>

<template>
    <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
        <section class="app-panel p-3.5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Workspace</h2>
                <p class="text-xs text-muted-foreground">{{ tasks.length }} tasks · {{ attachmentsCount }} files</p>
            </div>

            <div class="mt-3 divide-y divide-border/70 border-t border-border/70">
                <Collapsible v-slot="{ open }" :default-open="tasks.length > 0 && tasks.length <= 3">
                    <div>
                        <CollapsibleTrigger class="flex w-full items-center justify-between py-3 text-left hover:text-foreground">
                            <div>
                                <p class="text-sm font-semibold text-foreground">Tasks</p>
                                <p class="text-xs text-muted-foreground">Open work for this case</p>
                            </div>

                            <ChevronDown
                                class="size-4 text-muted-foreground transition-transform"
                                :class="{ 'rotate-180': open }"
                            />
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <div class="border-t border-border/70 pb-3 pt-3">
                                <div
                                    v-if="tasks.length === 0"
                                    class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
                                >
                                    No tasks yet.
                                </div>

                                <div v-else class="space-y-2">
                                    <div
                                        v-for="task in tasks"
                                        :key="task.id"
                                        class="rounded-lg border border-border/70 px-3 py-2.5"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="truncate text-sm font-medium text-foreground">
                                                        {{ task.title }}
                                                    </p>
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="taskStatusClasses(task.status_label)">
                                                        {{ task.status_label }}
                                                    </span>
                                                </div>

                                                <p class="mt-1 text-xs text-muted-foreground">
                                                    Due {{ formatDateOnly(task.due_at) }}
                                                </p>
                                            </div>
                                            <span class="text-[11px] font-medium" :class="taskPriorityClasses(task.priority_label)">
                                                {{ task.priority_label }}
                                            </span>
                                        </div>

                                        <p class="mt-1.5 text-xs text-muted-foreground">
                                            {{ task.assignee_name || 'Unassigned' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CollapsibleContent>
                    </div>
                </Collapsible>

                <Collapsible v-slot="{ open }" :default-open="attachmentsCount > 0 && attachmentsCount <= 2">
                    <div>
                        <CollapsibleTrigger class="flex w-full items-center justify-between py-3 text-left hover:text-foreground">
                            <div>
                                <p class="text-sm font-semibold text-foreground">Attachments</p>
                                <p class="text-xs text-muted-foreground">Case files and uploads</p>
                            </div>

                            <ChevronDown
                                class="size-4 text-muted-foreground transition-transform"
                                :class="{ 'rotate-180': open }"
                            />
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <div class="border-t border-border/70 pb-3 pt-3">
                                <slot name="attachments" />
                            </div>
                        </CollapsibleContent>
                    </div>
                </Collapsible>
            </div>
        </section>
    </aside>
</template>
