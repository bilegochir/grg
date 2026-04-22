<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

const sidebarNavGroups: Array<{ title: string; items: NavItem[] }> = [
    {
        title: 'Workspace',
        items: [
            {
                title: 'Company',
                href: '/settings/agency',
            },
            {
                title: 'Profile',
                href: '/settings/profile',
            },
            {
                title: 'Password',
                href: '/settings/password',
            },
            {
                title: 'Appearance',
                href: '/settings/appearance',
            },
        ],
    },
    {
        title: 'Workflow',
        items: [
            {
                title: 'Visa requirements',
                href: '/settings/visa-requirements',
            },
            {
                title: 'Visa statuses',
                href: '/settings/visa-statuses',
            },
            {
                title: 'Task statuses',
                href: '/settings/task-statuses',
            },
            {
                title: 'Task templates',
                href: '/settings/task-templates',
            },
        ],
    },
];

const currentPath = window.location.pathname;
const isActive = (href: string) => currentPath === href;
</script>

<template>
    <div class="px-4 py-4">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
            <aside class="w-full max-w-xl shrink-0 lg:w-56">
                <div class="app-panel overflow-hidden lg:sticky lg:top-20">
                    <div class="border-b border-border px-4 py-3">
                        <p class="text-sm font-semibold text-foreground">Settings</p>
                    </div>

                    <nav class="space-y-4 px-3 py-3">
                        <div v-for="group in sidebarNavGroups" :key="group.title" class="space-y-1.5">
                            <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                {{ group.title }}
                            </p>

                            <Button
                                v-for="item in group.items"
                                :key="item.href"
                                variant="ghost"
                                :class="[
                                    'h-9 w-full justify-start rounded-lg px-2.5 text-sm',
                                    isActive(item.href) ? 'bg-muted font-medium text-foreground' : 'text-muted-foreground hover:text-foreground',
                                ]"
                                as-child
                            >
                                <Link :href="item.href">
                                    {{ item.title }}
                                </Link>
                            </Button>
                        </div>
                    </nav>
                </div>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="min-w-0 flex-1">
                <section class="space-y-8">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
