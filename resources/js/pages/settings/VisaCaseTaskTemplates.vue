<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';

interface TaskTemplateRow {
    id: number | null;
    title: string;
    description: string;
    priority: string;
    due_in_days: null | number;
}

interface TaskTemplateGroup {
    label: string;
    status: string;
    tasks: Array<{
        id: number;
        title: string;
        description: null | string;
        priority: string;
        due_in_days: null | number;
    }>;
}

const props = defineProps<{
    templateGroups: TaskTemplateGroup[];
}>();

const page = usePage<SharedData>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Task templates',
        href: '/settings/task-templates',
    },
];

const priorityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' },
] as const;

const blankTask = (): TaskTemplateRow => ({
    id: null,
    title: '',
    description: '',
    priority: 'medium',
    due_in_days: null,
});

const form = useForm({
    templates: props.templateGroups.map((group) => ({
        status: group.status,
        tasks: group.tasks.length > 0
            ? group.tasks.map((task) => ({
                  id: task.id,
                  title: task.title,
                  description: task.description ?? '',
                  priority: task.priority,
                  due_in_days: task.due_in_days,
              }))
            : [blankTask()],
    })),
});

const addTask = (groupIndex: number) => {
    form.templates[groupIndex].tasks.push(blankTask());
};

const removeTask = (groupIndex: number, taskIndex: number) => {
    if (form.templates[groupIndex].tasks.length === 1) {
        form.templates[groupIndex].tasks[0] = blankTask();
        return;
    }

    form.templates[groupIndex].tasks.splice(taskIndex, 1);
};

const submit = () => {
    form.transform((data) => ({
        templates: data.templates.map((group) => ({
            status: group.status,
            tasks: group.tasks.filter((task) => task.title.trim() !== ''),
        })),
    })).put(route('settings.task-templates.store'), {
        preserveScroll: true,
    });
};

const groupPreview = (groupIndex: number) => {
    const firstFilledTask = form.templates[groupIndex].tasks.find((task) => task.title.trim() !== '');

    return firstFilledTask?.title ?? 'No task titles added yet';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Task templates" />

        <SettingsLayout>
            <div class="flex flex-col gap-4">
                <HeadingSmall title="Task templates" />

                <div v-if="page.props.flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ page.props.flash.success }}
                </div>

                <form class="flex flex-col gap-3" @submit.prevent="submit">
                    <section
                        v-for="(group, groupIndex) in props.templateGroups"
                        :key="group.status"
                        class="app-panel overflow-hidden"
                    >
                        <Collapsible v-slot="{ open }" :default-open="groupIndex === 0">
                            <div class="border-b border-border last:border-b-0">
                                <CollapsibleTrigger class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h2 class="text-sm font-semibold text-foreground">{{ group.label }}</h2>
                                            <span class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                                                {{ form.templates[groupIndex].tasks.length }} tasks
                                            </span>
                                        </div>
                                        <p class="mt-1 truncate text-xs text-muted-foreground">
                                            {{ groupPreview(groupIndex) }}
                                        </p>
                                    </div>
                                    <ChevronDown class="size-4 shrink-0 text-muted-foreground transition-transform" :class="open ? 'rotate-180' : ''" />
                                </CollapsibleTrigger>
                            </div>

                            <CollapsibleContent>
                                <div class="border-b border-border px-4 py-2.5">
                                    <div class="flex justify-end">
                                        <Button type="button" variant="outline" size="sm" @click="addTask(groupIndex)">Add task</Button>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[840px] text-[13px]">
                                        <thead class="bg-muted/30 text-left text-[11px] uppercase tracking-[0.14em] text-muted-foreground">
                                            <tr>
                                                <th class="w-12 px-4 py-2 font-medium">#</th>
                                                <th class="px-3 py-2 font-medium">Task</th>
                                                <th class="w-[140px] px-3 py-2 font-medium">Priority</th>
                                                <th class="w-[120px] px-3 py-2 font-medium">Due</th>
                                                <th class="px-3 py-2 font-medium">Notes</th>
                                                <th class="w-[86px] px-4 py-2 text-right font-medium">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(task, taskIndex) in form.templates[groupIndex].tasks"
                                                :key="`${group.status}-${taskIndex}`"
                                                class="border-t border-border/70 align-top transition-colors hover:bg-muted/20"
                                            >
                                                <td class="px-4 py-2.5 text-xs font-medium text-muted-foreground">{{ taskIndex + 1 }}</td>
                                                <td class="px-3 py-2.5">
                                                    <Input
                                                        :id="`task-title-${group.status}-${taskIndex}`"
                                                        v-model="task.title"
                                                        class="h-8"
                                                        placeholder="Task title"
                                                    />
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    <select
                                                        :id="`task-priority-${group.status}-${taskIndex}`"
                                                        v-model="task.priority"
                                                        class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                                    >
                                                        <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
                                                            {{ option.label }}
                                                        </option>
                                                    </select>
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    <Input
                                                        :id="`task-due-${group.status}-${taskIndex}`"
                                                        v-model="task.due_in_days"
                                                        class="h-8"
                                                        type="number"
                                                        min="0"
                                                        max="365"
                                                        placeholder="Days"
                                                    />
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    <textarea
                                                        :id="`task-description-${group.status}-${taskIndex}`"
                                                        v-model="task.description"
                                                        rows="1"
                                                        class="flex min-h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm leading-5 focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                                        placeholder="Optional note"
                                                    />
                                                </td>
                                                <td class="px-4 py-2.5 text-right">
                                                    <Button type="button" variant="ghost" size="sm" @click="removeTask(groupIndex, taskIndex)">Remove</Button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="px-4 py-2.5">
                                    <InputError :message="form.errors[`templates.${groupIndex}.tasks`]" />
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </section>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">Save templates</Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
