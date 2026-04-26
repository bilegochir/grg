<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { NotificationItem, NotificationsState, SharedData } from '@/types';
import { Link, router, usePage, usePoll } from '@inertiajs/vue3';
import { Bell, CheckCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();

usePoll(60000, { only: ['notifications'] });

const notificationState = computed<NotificationsState>(() => page.props.notifications ?? { unread_count: 0, items: [] });
const unreadCountLabel = computed(() => (notificationState.value.unread_count > 9 ? '9+' : String(notificationState.value.unread_count)));

const formatDate = (value: null | string) =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Just now';

const markAsRead = (notification: NotificationItem) => {
    if (notification.read_at) {
        return;
    }

    router.post(
        route('notifications.read', notification.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['notifications'],
        },
    );
};

const markAllAsRead = () => {
    if (notificationState.value.unread_count === 0) {
        return;
    }

    router.post(
        route('notifications.read-all'),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['notifications'],
        },
    );
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                size="icon"
                class="relative h-9 w-9 rounded-lg border border-border bg-card text-muted-foreground shadow-none transition hover:bg-muted hover:text-foreground"
            >
                <Bell class="h-4 w-4" />
                <span
                    v-if="notificationState.unread_count > 0"
                    class="absolute -right-1.5 -top-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-slate-950 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white dark:bg-slate-100 dark:text-slate-950"
                >
                    {{ unreadCountLabel }}
                </span>
                <span class="sr-only">Open notifications</span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-[24rem] rounded-2xl border border-border/70 p-0 shadow-xl">
            <div class="flex items-start justify-between gap-4 border-b border-border/70 px-4 py-4">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-foreground">Notifications</p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            notificationState.unread_count > 0
                                ? `${notificationState.unread_count} unread update${notificationState.unread_count === 1 ? '' : 's'}`
                                : 'All caught up'
                        }}
                    </p>
                </div>

                <button
                    v-if="notificationState.unread_count > 0"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="h-3.5 w-3.5" />
                    Mark all read
                </button>
            </div>

            <div v-if="notificationState.items.length" class="max-h-[26rem] space-y-2 overflow-y-auto p-2">
                <div
                    v-for="notification in notificationState.items"
                    :key="notification.id"
                    class="rounded-2xl border p-3 transition"
                    :class="
                        notification.read_at
                            ? 'border-transparent bg-background hover:bg-muted/60'
                            : 'border-border/70 bg-muted/50 shadow-sm hover:bg-muted/80'
                    "
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full"
                            :class="notification.read_at ? 'bg-border' : 'bg-slate-950 dark:bg-slate-100'"
                        />

                        <div class="min-w-0 flex-1">
                            <Link :href="notification.open_url" method="post" as="button" class="block w-full text-left">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ notification.title }}</p>
                                        <p v-if="notification.message" class="mt-1 text-sm leading-5 text-muted-foreground">
                                            {{ notification.message }}
                                        </p>
                                        <p
                                            v-if="notification.context"
                                            class="mt-2 text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground/80"
                                        >
                                            {{ notification.context }}
                                        </p>
                                    </div>
                                    <span
                                        v-if="!notification.read_at"
                                        class="rounded-full bg-background px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-foreground shadow-sm"
                                    >
                                        New
                                    </span>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3 text-xs text-muted-foreground">
                                    <span>{{ formatDate(notification.created_at) }}</span>
                                    <span class="font-medium text-foreground">{{ notification.action_label }}</span>
                                </div>
                            </Link>

                            <div v-if="!notification.read_at" class="mt-2 flex justify-end">
                                <button
                                    type="button"
                                    class="rounded-lg px-2 py-1 text-xs font-medium text-muted-foreground transition hover:bg-background hover:text-foreground"
                                    @click="markAsRead(notification)"
                                >
                                    Mark read
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center">
                <div class="rounded-2xl border border-dashed border-border/80 bg-muted/40 p-3">
                    <Bell class="h-5 w-5 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-foreground">No notifications yet</p>
                    <p class="text-xs text-muted-foreground">Assignments and workflow updates will show up here.</p>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
