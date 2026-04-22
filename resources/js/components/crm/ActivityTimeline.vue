<script setup lang="ts">
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
</script>

<template>
    <section :class="props.embedded ? '' : 'app-panel p-3.5'">
        <div v-if="!props.embedded">
            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">{{ title }}</h2>
        </div>

        <div :class="props.embedded ? 'space-y-3' : 'mt-3 space-y-3'">
            <div v-if="props.items.length === 0" class="text-sm text-muted-foreground">No activity yet.</div>
            <div v-else class="space-y-2.5">
                <div
                    v-for="item in props.items"
                    :key="`${item.type}-${item.created_at}-${item.title}`"
                    class="border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ item.title }}</p>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ item.description }}</p>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ formatDate(item.created_at) }}</p>
                    </div>

                    <div v-if="Object.values(item.meta).some(Boolean)" class="mt-1.5 flex flex-wrap gap-1.5">
                        <span v-for="(value, key) in item.meta" :key="key" class="px-2 py-1 text-xs text-muted-foreground" v-show="value">
                            {{ key }}: {{ value }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
