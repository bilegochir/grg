<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { BriefcaseBusiness, LayoutGrid, ListTodo, Users } from 'lucide-vue-next';

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
        title: 'Visa Cases',
        href: '/visa-cases',
        icon: BriefcaseBusiness,
    },
    {
        title: 'Tasks',
        href: '/tasks',
        icon: ListTodo,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar" class="bg-sidebar text-sidebar-foreground shadow-[inset_-1px_0_0_hsl(var(--sidebar-border)/0.75)]">
        <SidebarContent class="bg-transparent px-2 py-4 group-data-[collapsible=icon]:px-2">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/70 bg-transparent px-2 pb-3 pt-3 group-data-[collapsible=icon]:px-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
