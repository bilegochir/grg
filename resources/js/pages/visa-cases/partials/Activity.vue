<script setup lang="ts">
import { computed } from 'vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{ notesCount?: number; timelineCount?: number }>(),
    { notesCount: 0, timelineCount: 0 }
);

const sections = [
    { key: 'notes',    title: 'Notes',    description: 'Add context for this case' },
    { key: 'timeline', title: 'Timeline', description: 'Latest case activity first' },
] as const;

const summary = computed(() =>
    [
        props.notesCount    && `${props.notesCount} note${props.notesCount       !== 1 ? 's' : ''}`,
        props.timelineCount && `${props.timelineCount} event${props.timelineCount !== 1 ? 's' : ''}`,
    ].filter(Boolean).join(' · ')
);
</script>

<template>
    <section class="app-panel p-3.5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Case activity</h2>
            <p v-if="summary" class="text-xs text-muted-foreground">{{ summary }}</p>
        </div>

        <div class="mt-3 divide-y divide-border/70 border-t border-border/70">
            <Collapsible v-for="section in sections" :key="section.key" v-slot="{ open }" :default-open="true">
                <CollapsibleTrigger class="flex w-full items-center justify-between py-3 text-left hover:text-foreground">
                    <div>
                        <p class="text-sm font-semibold text-foreground">{{ section.title }}</p>
                        <p class="text-xs text-muted-foreground">{{ section.description }}</p>
                    </div>
                    <ChevronDown
                        class="size-4 text-muted-foreground transition-transform"
                        :class="{ 'rotate-180': open }"
                    />
                </CollapsibleTrigger>

                <CollapsibleContent>
                    <div class="border-t border-border/70 pb-3 pt-3">
                        <slot :name="section.key" />
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </section>
</template>
