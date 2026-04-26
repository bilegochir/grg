<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown } from 'lucide-vue-next';

defineProps<{
    notesCount: number;
    timelineCount: number;
}>();
</script>

<template>
    <section class="app-panel p-3.5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Case activity</h2>
            <p class="text-xs text-muted-foreground">{{ notesCount }} notes · {{ timelineCount }} events</p>
        </div>

        <div class="mt-3 divide-y divide-border/70 border-t border-border/70">
            <Collapsible v-slot="{ open }" :default-open="true">
                <div>
                    <CollapsibleTrigger class="flex w-full items-center justify-between py-3 text-left hover:text-foreground">
                        <div>
                            <p class="text-sm font-semibold text-foreground">Notes</p>
                            <p class="text-xs text-muted-foreground">Add context for this case</p>
                        </div>

                        <ChevronDown
                            class="size-4 text-muted-foreground transition-transform"
                            :class="{ 'rotate-180': open }"
                        />
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <div class="border-t border-border/70 pb-3 pt-3">
                            <slot name="notes" />
                        </div>
                    </CollapsibleContent>
                </div>
            </Collapsible>

            <Collapsible v-slot="{ open }" :default-open="true">
                <div>
                    <CollapsibleTrigger class="flex w-full items-center justify-between py-3 text-left hover:text-foreground">
                        <div>
                            <p class="text-sm font-semibold text-foreground">Timeline</p>
                            <p class="text-xs text-muted-foreground">Latest case activity first</p>
                        </div>

                        <ChevronDown
                            class="size-4 text-muted-foreground transition-transform"
                            :class="{ 'rotate-180': open }"
                        />
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <div class="border-t border-border/70 pb-3 pt-3">
                            <slot name="timeline" />
                        </div>
                    </CollapsibleContent>
                </div>
            </Collapsible>
        </div>
    </section>
</template>
