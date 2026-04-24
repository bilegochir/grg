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
</script>

<template>
    <aside class="space-y-3 xl:sticky xl:top-20 xl:self-start">
        <section class="app-panel overflow-hidden">
            <div class="border-b border-border/70 px-4 py-4">
                <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Workspace</h2>
                <p class="mt-1 text-sm text-muted-foreground">Tasks and supporting files.</p>
            </div>

            <div class="divide-y divide-border/70">
                <Collapsible v-slot="{ open }" :default-open="tasks.length > 0 && tasks.length <= 3">
                    <div>
                        <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-muted/40">
                            <div>
                                <p class="text-sm font-semibold text-foreground">Tasks</p>
                                <p class="text-xs text-muted-foreground">{{ tasks.length }} open items</p>
                            </div>

                            <ChevronDown
                                class="size-4 text-muted-foreground transition-transform"
                                :class="{ 'rotate-180': open }"
                            />
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <div class="border-t border-border/70 px-4 py-4">
                                <div
                                    v-if="tasks.length === 0"
                                    class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
                                >
                                    No tasks yet.
                                </div>

                                <div v-else class="space-y-2.5">
                                    <div
                                        v-for="task in tasks"
                                        :key="task.id"
                                        class="rounded-lg border border-border/70 bg-background p-3"
                                    >
                                        <p class="text-sm font-medium text-foreground">
                                            {{ task.title }}
                                        </p>

                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ task.status_label }} · {{ task.priority_label }} · {{ task.assignee_name || 'Unassigned' }}
                                        </p>

                                        <p class="mt-1 text-xs text-muted-foreground">
                                            Due {{ formatDateOnly(task.due_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CollapsibleContent>
                    </div>
                </Collapsible>

                <Collapsible v-slot="{ open }" :default-open="attachmentsCount > 0 && attachmentsCount <= 2">
                    <div>
                        <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-muted/40">
                            <div>
                                <p class="text-sm font-semibold text-foreground">Attachments</p>
                                <p class="text-xs text-muted-foreground">{{ attachmentsCount }} files</p>
                            </div>

                            <ChevronDown
                                class="size-4 text-muted-foreground transition-transform"
                                :class="{ 'rotate-180': open }"
                            />
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <div class="border-t border-border/70 px-4 py-4">
                                <slot name="attachments" />
                            </div>
                        </CollapsibleContent>
                    </div>
                </Collapsible>
            </div>
        </section>
    </aside>
</template>
