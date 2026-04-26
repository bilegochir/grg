<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { BriefcaseBusiness, LayoutGrid, ListTodo, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Clients',
        href: '/clients',
        icon: Users,
    },
    {
        title: 'Visa cases',
        href: '/visa-cases',
        icon: BriefcaseBusiness,
    },
    {
        title: 'Tasks',
        href: '/tasks',
        icon: ListTodo,
    },
];

const page = usePage<SharedData>();
const companyName = computed(() => page.props.auth.agency?.name ?? 'Workspace');
const companyMonogram = computed(() =>
    companyName.value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join(''),
);
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="sidebar"
        class="bg-sidebar text-sidebar-foreground shadow-[inset_-1px_0_0_hsl(var(--sidebar-border))]"
    >
        <div class="px-3 pb-2 pt-3 group-data-[collapsible=icon]:px-2.5">
            <div class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 group-data-[collapsible=icon]:justify-center">
                <div class="flex size-7 shrink-0 items-center justify-center rounded-md bg-amber-500/90 text-[11px] font-semibold text-white">
                    {{ companyMonogram || 'WS' }}
                </div>
                <div class="min-w-0 group-data-[collapsible=icon]:hidden">
                    <p class="truncate text-sm font-medium text-sidebar-foreground">{{ companyName }}</p>
                </div>
            </div>
        </div>

        <SidebarContent class="bg-transparent px-3 py-4 group-data-[collapsible=icon]:px-2.5">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border bg-transparent px-3 pb-3 pt-3 group-data-[collapsible=icon]:px-2.5">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
