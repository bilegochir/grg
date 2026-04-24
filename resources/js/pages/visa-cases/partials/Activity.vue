<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown } from 'lucide-vue-next';

defineProps<{
    notesCount: number;
    timelineCount: number;
}>();
</script>

<template>
    <section class="app-panel overflow-hidden">
        <div class="border-b border-border/70 px-4 py-4">
            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">Case activity</h2>
            <p class="mt-1 text-sm text-muted-foreground">Notes, history, and case events.</p>
        </div>

        <div class="divide-y divide-border/70">
            <Collapsible v-slot="{ open }" :default-open="true">
                <div>
                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-muted/40">
                        <div>
                            <p class="text-sm font-semibold text-foreground">Notes</p>
                            <p class="text-xs text-muted-foreground">{{ notesCount }} entries</p>
                        </div>

                        <ChevronDown
                            class="size-4 text-muted-foreground transition-transform"
                            :class="{ 'rotate-180': open }"
                        />
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <div class="border-t border-border/70 px-4 py-4">
                            <slot name="notes" />
                        </div>
                    </CollapsibleContent>
                </div>
            </Collapsible>

            <Collapsible v-slot="{ open }" :default-open="false">
                <div>
                    <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-muted/40">
                        <div>
                            <p class="text-sm font-semibold text-foreground">Timeline</p>
                            <p class="text-xs text-muted-foreground">{{ timelineCount }} events</p>
                        </div>

                        <ChevronDown
                            class="size-4 text-muted-foreground transition-transform"
                            :class="{ 'rotate-180': open }"
                        />
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <div class="border-t border-border/70 px-4 py-4">
                            <slot name="timeline" />
                        </div>
                    </CollapsibleContent>
                </div>
            </Collapsible>
        </div>
    </section>
</template>
