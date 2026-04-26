<script setup lang="ts">
import AppNotificationMenu from '@/components/AppNotificationMenu.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const currentTitle = computed(() => props.breadcrumbs[props.breadcrumbs.length - 1]?.title ?? 'Workspace');
</script>

<template>
    <header class="sticky top-0 z-20 shrink-0 app-topbar rounded-t-2xl">
        <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 md:px-5">
            <div class="flex min-w-0 items-start gap-3">
                <SidebarTrigger
                    class="mt-0.5 h-9 w-9 rounded-lg border border-border bg-card text-muted-foreground shadow-none hover:bg-muted"
                />

                <div class="min-w-0">
                    <h1 class="truncate text-[1.4rem] font-semibold tracking-tight text-slate-950 dark:text-slate-50">
                        {{ currentTitle }}
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <AppNotificationMenu />
            </div>
        </div>
    </header>
</template>
