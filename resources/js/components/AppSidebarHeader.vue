<script setup lang="ts">
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage<SharedData>();
const agencyName = computed(() => page.props.auth.agency?.name ?? 'Company workspace');
const currentTitle = computed(() => props.breadcrumbs[props.breadcrumbs.length - 1]?.title ?? 'Workspace');
</script>

<template>
    <header
        class="sticky top-0 z-20 shrink-0 border-b border-border/70 bg-background/95 px-5 backdrop-blur supports-[backdrop-filter]:bg-background/90"
    >
        <div class="flex h-14 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <SidebarTrigger class="-ml-1 h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted" />
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-semibold tracking-tight text-slate-950 dark:text-slate-50">{{ currentTitle }}</h1>
                </div>
            </div>
            <p class="hidden max-w-56 truncate text-sm text-muted-foreground md:block">
                {{ agencyName }}
            </p>
        </div>
    </header>
</template>
