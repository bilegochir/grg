<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<SharedData>();
const permissions = computed(() => page.props.auth.permissions);

const sidebarNavGroups = computed<Array<{ title: string; items: NavItem[] }>>(() => {
    const groups: Array<{ title: string; items: NavItem[] }> = [
        {
            title: 'Workspace',
            items: [
                ...(permissions.value?.manage_company_settings
                    ? [
                          {
                              title: 'Company',
                              href: '/settings/agency',
                          },
                          {
                              title: 'Team',
                              href: '/settings/team',
                          },
                      ]
                    : []),
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
    ];

    if (permissions.value?.manage_workflow_settings) {
        groups.push({
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
        });
    }

    return groups;
});

const currentPath = window.location.pathname;
const isActive = (href: string) => currentPath === href;
</script>

<template>
        <div class="px-4 py-4">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
                <aside class="w-full max-w-xl shrink-0 lg:w-56">
                    <div class="lg:sticky lg:top-24">
                        <div class="mb-4">
                            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-muted-foreground">Settings</p>
                        </div>

                        <nav class="space-y-5">
                            <div v-for="group in sidebarNavGroups" :key="group.title" class="space-y-1.5">
                                <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                    {{ group.title }}
                                </p>

                                <Button
                                    v-for="item in group.items"
                                    :key="item.href"
                                    variant="ghost"
                                    :class="[
                                        'h-10 w-full justify-start rounded-2xl px-3 text-sm',
                                        isActive(item.href)
                                            ? 'bg-card font-medium text-foreground shadow-sm ring-1 ring-border/80'
                                            : 'text-muted-foreground hover:bg-muted/25 hover:text-foreground',
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
