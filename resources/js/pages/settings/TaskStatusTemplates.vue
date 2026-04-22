<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    templates: Array<{
        status_key: string;
        label: string;
    }>;
}>();

const page = usePage<SharedData>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Task statuses',
        href: '/settings/task-statuses',
    },
];

const defaultLabel = (statusKey: string) =>
    ({
        todo: 'To do',
        in_progress: 'In progress',
        done: 'Done',
    })[statusKey] ?? statusKey;

const form = useForm({
    templates: props.templates.map((template) => ({
        status_key: template.status_key,
        label: template.label,
    })),
});

const submit = () => {
    form.put(route('settings.task-statuses.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Task statuses" />

        <SettingsLayout>
            <div class="flex flex-col gap-4">
                <HeadingSmall title="Task statuses" />

                <div v-if="page.props.flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ page.props.flash.success }}
                </div>

                <form class="app-panel overflow-hidden" @submit.prevent="submit">
                    <div class="border-b border-border px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-foreground">Status labels</h2>
                            </div>
                            <Button type="submit" :disabled="form.processing">Save</Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[680px] text-sm">
                            <thead class="bg-muted/40 text-left text-[11px] uppercase tracking-[0.14em] text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-2 font-medium">System status</th>
                                    <th class="px-3 py-2 font-medium">Company label</th>
                                    <th class="px-4 py-2 font-medium">Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(template, index) in form.templates"
                                    :key="template.status_key"
                                    class="border-t border-border/70 transition-colors hover:bg-muted/20"
                                >
                                    <td class="px-4 py-3 font-medium text-foreground">
                                        {{ defaultLabel(template.status_key) }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <Input
                                            v-model="template.label"
                                            class="h-8"
                                            :placeholder="defaultLabel(template.status_key)"
                                        />
                                        <InputError :message="form.errors[`templates.${index}.label`]" class="mt-2" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted-foreground">
                                        {{ template.status_key }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
