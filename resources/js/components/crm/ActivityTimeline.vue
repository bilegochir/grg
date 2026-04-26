<script setup lang="ts">
import {
    Briefcase,
    CheckSquare,
    FileText,
    MessageSquareText,
    PencilLine,
    type LucideIcon,
    UserPlus,
} from 'lucide-vue-next';

interface TimelineItem {
    type: string;
    title: string;
    description: string;
    created_at: string;
    meta: Record<string, null | string>;
}

const props = withDefaults(
    defineProps<{
        title: string;
        items: TimelineItem[];
        embedded?: boolean;
    }>(),
    {
        embedded: false,
    },
);

const formatDate = (value: string) => new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));

const metaLabelMap: Record<string, string> = {
    author: '',
    uploader: 'Uploaded by',
    assignee: 'Assigned to',
    priority: 'Priority',
    status: 'Status',
    country: 'Country',
    size: 'Size',
};

const appearanceByType: Record<
    string,
    {
        label: string;
        icon: LucideIcon;
        iconClass: string;
        rowClass: string;
        descriptionClass: string;
    }
> = {
    note: {
        label: 'Note',
        icon: MessageSquareText,
        iconClass: 'bg-sky-100 text-sky-700',
        rowClass: 'border-sky-200/80 bg-sky-50/70',
        descriptionClass: 'text-slate-950',
    },
    client_updated: {
        label: 'Client update',
        icon: PencilLine,
        iconClass: 'bg-amber-100 text-amber-700',
        rowClass: 'border-amber-200/80 bg-amber-50/60',
        descriptionClass: 'text-slate-900',
    },
    visa_case_updated: {
        label: 'Case update',
        icon: PencilLine,
        iconClass: 'bg-amber-100 text-amber-700',
        rowClass: 'border-amber-200/80 bg-amber-50/60',
        descriptionClass: 'text-slate-900',
    },
    attachment: {
        label: 'File',
        icon: FileText,
        iconClass: 'bg-emerald-100 text-emerald-700',
        rowClass: 'border-emerald-200/80 bg-emerald-50/60',
        descriptionClass: 'text-slate-950',
    },
    task: {
        label: 'Task',
        icon: CheckSquare,
        iconClass: 'bg-violet-100 text-violet-700',
        rowClass: 'border-violet-200/80 bg-violet-50/60',
        descriptionClass: 'text-slate-900',
    },
    visa_case: {
        label: 'Case opened',
        icon: Briefcase,
        iconClass: 'bg-slate-200 text-slate-700',
        rowClass: 'border-slate-200 bg-slate-50/80',
        descriptionClass: 'text-slate-900',
    },
    visa_case_created: {
        label: 'Case opened',
        icon: Briefcase,
        iconClass: 'bg-slate-200 text-slate-700',
        rowClass: 'border-slate-200 bg-slate-50/80',
        descriptionClass: 'text-slate-900',
    },
    client_created: {
        label: 'Client created',
        icon: UserPlus,
        iconClass: 'bg-slate-200 text-slate-700',
        rowClass: 'border-slate-200 bg-slate-50/80',
        descriptionClass: 'text-slate-900',
    },
};

const appearanceFor = (type: string) =>
    appearanceByType[type] ?? {
        label: 'Activity',
        icon: PencilLine,
        iconClass: 'bg-slate-200 text-slate-700',
        rowClass: 'border-slate-200 bg-slate-50/80',
        descriptionClass: 'text-slate-900',
    };

const visibleMetaEntries = (meta: Record<string, null | string>) => Object.entries(meta).filter(([, value]) => Boolean(value));

const formatMeta = (key: string, value: null | string) => {
    if (!value) {
        return '';
    }

    const label = metaLabelMap[key] ?? key;

    return label ? `${label}: ${value}` : value;
};

const metaSummary = (meta: Record<string, null | string>) => visibleMetaEntries(meta).map(([key, value]) => formatMeta(key, value)).join(' • ');
</script>

<template>
    <section :class="props.embedded ? '' : 'app-panel p-3.5'">
        <div v-if="!props.embedded">
            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">{{ title }}</h2>
        </div>

        <div :class="props.embedded ? 'space-y-2' : 'mt-3 space-y-2'">
            <div v-if="props.items.length === 0" class="text-sm text-muted-foreground">No activity yet.</div>
            <div v-else class="space-y-1.5">
                <div
                    v-for="item in props.items"
                    :key="`${item.type}-${item.created_at}-${item.title}`"
                    class="rounded-xl border px-2.5 py-2"
                    :class="appearanceFor(item.type).rowClass"
                >
                    <div class="flex items-start gap-2.5">
                        <div
                            class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg"
                            :class="appearanceFor(item.type).iconClass"
                        >
                            <component :is="appearanceFor(item.type).icon" class="size-3.5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                                    {{ appearanceFor(item.type).label }}
                                </span>
                                <span class="text-xs text-slate-400">•</span>
                                <p class="text-xs text-slate-500">{{ formatDate(item.created_at) }}</p>
                            </div>

                            <p class="mt-0.5 text-sm leading-5" :class="appearanceFor(item.type).descriptionClass">
                                {{ item.description || item.title }}
                            </p>

                            <p v-if="visibleMetaEntries(item.meta).length > 0" class="mt-1 text-[11px] leading-4 text-slate-500">
                                {{ metaSummary(item.meta) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
