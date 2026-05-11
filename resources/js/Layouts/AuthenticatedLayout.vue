<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);
const can = (permission) => page.props.auth.user?.permissions?.includes(permission);
const notifications = computed(() => page.props.notifications ?? { recent_count: 0, items: [] });
const hasNotifications = computed(() => notifications.value.items.length > 0);
const recentNotificationCount = ref(page.props.notifications?.recent_count ?? 0);
const markingNotificationsSeen = ref(false);
const profileMenuOpen = ref(false);
const searchOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchInput = ref(null);
let searchTimeout = null;

const notificationTone = (event) => {
    if (!event) {
        return 'bg-brand-primary/10 text-brand-primary';
    }

    if (event.includes('rejected') || event.includes('failed')) {
        return 'bg-brand-danger/10 text-brand-danger';
    }

    if (event.includes('approved') || event.includes('verified')) {
        return 'bg-brand-success/10 text-brand-success';
    }

    if (event.includes('submitted') || event.includes('review')) {
        return 'bg-brand-warning/10 text-brand-warning';
    }

    return 'bg-brand-primary/10 text-brand-primary';
};

watch(
    () => page.props.notifications?.recent_count,
    (value) => {
        recentNotificationCount.value = value ?? 0;
    },
);

const markNotificationsSeen = async (isOpen) => {
    if (!isOpen || recentNotificationCount.value === 0 || markingNotificationsSeen.value) {
        return;
    }

    markingNotificationsSeen.value = true;

    try {
        await window.axios.post(route('notifications.mark-seen'));
        recentNotificationCount.value = 0;
    } finally {
        markingNotificationsSeen.value = false;
    }
};

const sections = [
    {
        label: 'Main',
        items: [
            { name: 'Dashboard', href: route('dashboard'), current: 'dashboard', icon: 'dashboard', permission: 'dashboard.view' },
        ],
    },
    {
        label: 'Clients',
        items: [
            { name: 'Leads', href: route('leads.index'), current: 'leads.*', icon: 'leads', permission: 'leads.view' },
            { name: 'Applicants', href: route('applicants.index'), current: 'applicants.*', icon: 'applicants', permission: 'applicants.view' },
            { name: 'Cases', href: route('cases.index'), current: 'cases.*', icon: 'users', permission: 'cases.view' },
        ],
    },
    {
        label: 'Operations',
        items: [
            { name: 'Documents', href: route('documents.index'), current: 'documents.*', icon: 'note', permission: 'documents.review' },
            { name: 'Tasks', href: route('tasks.index'), current: 'tasks.*', icon: 'check', permission: 'cases.view' },
            { name: 'Appointments', href: route('appointments.index'), current: 'appointments.*', icon: 'clock', permission: 'cases.view' },
        ],
    },
    {
        label: 'Finance',
        items: [
            { name: 'Invoices', href: route('invoices.index'), current: 'invoices.*', icon: 'inbox', permission: 'finance.view' },
        ],
    },
    {
        label: 'Admin',
        items: [
            { name: 'Staff', href: route('staff.index'), current: 'staff.*', icon: 'users', permission: 'staff.manage' },
            { name: 'Reports', href: route('reports.index'), current: 'reports.*', icon: 'dashboard', permission: 'dashboard.view' },
        ],
    },
];

const visibleSections = computed(() => sections
    .map((section) => ({
        ...section,
        items: section.items.filter((item) => !item.permission || can(item.permission)),
    }))
    .filter((section) => section.items.length));

const pageTitle = computed(() => {
    const component = page.component ?? '';

    if (component.startsWith('Leads/')) {
        return 'Leads';
    }

    if (component.startsWith('Applicants/')) {
        return 'Applicants';
    }

    if (component.startsWith('Cases/')) {
        return 'Cases';
    }

    if (component.startsWith('Appointments/')) {
        return 'Appointments';
    }

    if (component.startsWith('Documents/')) {
        return 'Documents';
    }

    if (component.startsWith('Tasks/')) {
        return 'Tasks';
    }

    if (component.startsWith('Invoices/')) {
        return 'Invoices';
    }

    if (component.startsWith('Settings/')) {
        return 'Settings';
    }

    if (component.startsWith('Staff/')) {
        return 'Staff';
    }

    if (component.startsWith('Reports/')) {
        return 'Reports';
    }

    return 'Dashboard';
});

const openSearch = () => {
    searchOpen.value = true;
    setTimeout(() => searchInput.value?.focus(), 0);
};

const closeSearch = () => {
    searchOpen.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    searchLoading.value = false;
};

const runGlobalSearch = async () => {
    if (searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        searchLoading.value = false;
        return;
    }

    searchLoading.value = true;

    try {
        const response = await window.axios.get(route('search.global'), {
            params: { q: searchQuery.value.trim() },
        });

        const apiResults = response.data.results ?? [];
        searchResults.value = apiResults;
    } finally {
        searchLoading.value = false;
    }
};

watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(runGlobalSearch, 180);
});

const handleShellKeydown = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        openSearch();
    }

    if (event.key === 'Escape' && searchOpen.value) {
        closeSearch();
    }
};

onMounted(() => window.addEventListener('keydown', handleShellKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', handleShellKeydown);
    clearTimeout(searchTimeout);
});
</script>

<template>
    <div class="app-shell">
        <div class="flex min-h-screen w-full">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="sidebarOpen"
                    class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"
                    @click="sidebarOpen = false"
                ></div>
            </Transition>

            <aside
                :class="[
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                    sidebarCollapsed ? 'lg:w-16' : 'lg:w-60',
                ]"
                class="fixed inset-y-0 left-0 z-50 flex flex-col bg-brand-sidebar transition-all duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:shrink-0"
            >
                <div class="px-3 py-3">
                    <div
                        class="flex w-full items-center gap-2 rounded-md px-2 py-1.5"
                        :class="sidebarCollapsed ? 'justify-center px-0' : ''"
                    >
                        <div class="h-5 w-5 shrink-0 rounded bg-brand-primary flex items-center justify-center text-[10px] font-bold text-white shadow-sm">
                            G
                        </div>
                        <span v-if="!sidebarCollapsed" class="truncate text-[13px] font-bold text-slate-900">Gereg</span>
                    </div>
                </div>

                <div class="px-3 mb-4">
                    <button class="app-topbar-search w-full group" @click="openSearch">
                        <AppIcon name="search" :size="13" class="text-slate-400 group-hover:text-slate-600" />
                        <template v-if="!sidebarCollapsed">
                            <span class="font-medium">Search</span>
                            <div class="ml-auto flex items-center gap-0.5">
                                <kbd class="inline-flex h-4 items-center rounded bg-slate-200/50 px-1 text-[9px] font-bold text-slate-500 uppercase">⌘</kbd>
                                <kbd class="inline-flex h-4 items-center rounded bg-slate-200/50 px-1 text-[9px] font-bold text-slate-500 uppercase">K</kbd>
                            </div>
                        </template>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-1 pb-6">
                    <slot v-if="$slots.sidebar" name="sidebar" />
                    
                    <template v-else>
                        <div
                            v-for="section in visibleSections"
                            :key="section.label"
                            class="mb-4 last:mb-0"
                        >
                            <p
                                v-if="!sidebarCollapsed"
                                class="app-sidebar-section-label"
                            >
                                {{ section.label }}
                            </p>

                            <div class="app-sidebar-nav-group">
                                <template v-for="item in section.items" :key="item.name">
                                    <Link
                                        v-if="!item.disabled"
                                        :href="item.href"
                                        class="app-sidebar-item"
                                        :class="[
                                            { 'app-sidebar-item-active': route().current(item.current) },
                                            sidebarCollapsed ? 'justify-center px-0 mx-2' : '',
                                        ]"
                                        :title="sidebarCollapsed ? item.name : null"
                                    >
                                        <AppIcon :name="item.icon" :size="16" class="shrink-0" />
                                        <span v-if="!sidebarCollapsed">{{ item.name }}</span>
                                    </Link>
                                    <span
                                        v-else
                                        class="app-sidebar-item cursor-not-allowed opacity-40"
                                        :class="sidebarCollapsed ? 'justify-center px-0 mx-2' : ''"
                                        :title="sidebarCollapsed ? item.name : null"
                                    >
                                        <AppIcon :name="item.icon" :size="16" class="shrink-0" />
                                        <span v-if="!sidebarCollapsed">{{ item.name }}</span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </nav>

                <div class="mt-auto p-3 space-y-0.5">
                    <Dropdown
                        :placement="sidebarCollapsed ? 'right' : 'top'"
                        align="left"
                        width="56"
                        content-classes="py-1"
                        @open-change="(value) => { profileMenuOpen = value; }"
                    >
                        <template #trigger>
                            <button
                                class="app-sidebar-footer-trigger"
                                :class="[
                                    sidebarCollapsed ? 'justify-center px-0' : '',
                                    profileMenuOpen ? 'app-sidebar-footer-trigger-active' : '',
                                ]"
                                :title="sidebarCollapsed ? $page.props.auth.user.name : null"
                            >
                                <template v-if="!sidebarCollapsed">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="h-5 w-5 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                                            <span class="text-[9px] font-bold text-slate-600 uppercase">{{ $page.props.auth.user.name.charAt(0) }}</span>
                                        </div>
                                        <span class="truncate">{{ $page.props.auth.user.name }}</span>
                                    </div>
                                    <AppIcon
                                        name="chevronDown"
                                        :size="12"
                                        class="shrink-0 text-slate-400 transition-transform duration-200"
                                        :class="profileMenuOpen ? 'rotate-180' : ''"
                                    />
                                </template>
                                <div v-else class="h-6 w-6 rounded-md bg-slate-200 flex items-center justify-center">
                                    <AppIcon name="settings" :size="14" />
                                </div>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-3 py-2 border-b border-slate-100">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Account</p>
                            </div>
                            <div class="py-1">
                                <DropdownLink :href="route('settings.index')">
                                    Settings
                                </DropdownLink>
                                <Link :href="route('logout')" method="post" as="button" class="block w-full px-3 py-1.5 text-left text-[13px] font-medium text-red-600 transition hover:bg-red-50 hover:text-red-700">
                                    Log out
                                </Link>
                            </div>
                        </template>
                    </Dropdown>
                </div>
            </aside>

            <div class="ui-shell-container flex flex-col">
                <header class="z-30 border-b border-slate-100 bg-white/80 backdrop-blur-md">
                    <div class="flex h-11 items-center justify-between gap-6 px-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <button
                                class="inline-flex items-center justify-center rounded-md border border-slate-200 p-1 text-slate-500 transition-all hover:bg-slate-50 lg:hidden"
                                @click="sidebarOpen = true"
                            >
                                <AppIcon name="menu" :size="14" />
                            </button>
                            <div class="flex items-center gap-1.5 text-[12px] font-medium text-slate-500 min-w-0">
                                <span class="hover:text-slate-900 cursor-pointer transition-colors">{{ pageTitle }}</span>
                                <AppIcon name="chevronRight" :size="10" class="text-slate-300" />
                                <span class="text-slate-900 truncate">Overview</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Dropdown placement="bottom" align="right" width="80" content-classes="p-0 overflow-hidden" @open-change="markNotificationsSeen">
                                <template #trigger>
                                    <button class="relative inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600">
                                        <AppIcon name="bell" :size="15" />
                                        <span
                                            v-if="recentNotificationCount > 0"
                                            class="absolute right-0.5 top-0.5 h-2 w-2 rounded-full bg-brand-primary border-2 border-white"
                                        ></span>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="w-80 max-w-[calc(100vw-2rem)]">
                                        <div class="border-b border-slate-100 px-4 py-3 bg-slate-50/50">
                                            <p class="text-[12px] font-bold text-slate-900">Notifications</p>
                                        </div>

                                        <div v-if="hasNotifications" class="max-h-[26rem] overflow-y-auto py-1">
                                            <component
                                                :is="item.href ? Link : 'div'"
                                                v-for="item in notifications.items"
                                                :key="item.id"
                                                :href="item.href"
                                                class="block px-4 py-3 transition hover:bg-slate-50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-[13px] font-medium leading-relaxed text-slate-800">{{ item.description }}</p>
                                                        <p class="mt-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ item.created_at }}</p>
                                                    </div>
                                                </div>
                                            </component>
                                        </div>

                                        <div v-else class="px-4 py-8 text-center">
                                            <p class="text-[12px] font-medium text-slate-500">No new notifications</p>
                                        </div>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                <main class="flex-1 overflow-y-auto bg-white">
                    <FlashBanner :success="$page.props.flash.success" :error="$page.props.flash.error" />
                    <div class="px-8 py-6">
                        <slot />
                    </div>
                </main>
            </div>

            <TransitionRoot :show="searchOpen" as="template">
                <Dialog as="div" class="relative z-[70]" @close="closeSearch">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-200"
                        enter-from="opacity-0"
                        enter-to="opacity-100"
                        leave="ease-in duration-150"
                        leave-from="opacity-100"
                        leave-to="opacity-0"
                    >
                        <div class="fixed inset-0 bg-slate-950/20 backdrop-blur-[2px]" />
                    </TransitionChild>

                    <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6 md:p-20">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-200"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="ease-in duration-150"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="mx-auto max-w-2xl transform divide-y divide-slate-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-900/5 transition-all">
                                <div class="relative">
                                    <AppIcon
                                        name="search"
                                        class="pointer-events-none absolute left-4 top-3.5 h-4 w-4 text-slate-400"
                                        aria-hidden="true"
                                    />
                                    <input
                                        ref="searchInput"
                                        v-model="searchQuery"
                                        type="text"
                                        class="h-11 w-full border-0 bg-transparent pl-11 pr-4 text-slate-900 placeholder:text-slate-400 focus:ring-0 text-[14px]"
                                        placeholder="Type a command or search..."
                                    />
                                </div>

                                <div class="max-h-[32rem] overflow-y-auto p-2">
                                    <div v-if="searchLoading" class="flex items-center justify-center py-12">
                                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-brand-primary/30 border-t-brand-primary"></div>
                                    </div>

                                    <div v-else-if="searchQuery.trim().length < 1" class="py-2">
                                        <p class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">Recent</p>
                                        <div class="mt-1 space-y-0.5">
                                            <button class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-[13px] font-medium text-slate-700 hover:bg-slate-100">
                                                <AppIcon name="tasks" :size="14" class="text-slate-400" />
                                                <span>Go to Dashboard</span>
                                            </button>
                                            <button class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-[13px] font-medium text-slate-700 hover:bg-slate-100">
                                                <AppIcon name="applicants" :size="14" class="text-slate-400" />
                                                <span>Search applicants...</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else-if="searchResults.length" class="space-y-0.5">
                                        <Link
                                            v-for="result in searchResults"
                                            :key="`${result.type}-${result.href}-${result.title}`"
                                            :href="result.href"
                                            class="flex items-center gap-3 rounded-md px-3 py-2 text-left transition hover:bg-slate-100 group"
                                            @click="closeSearch"
                                        >
                                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-50 text-slate-400 group-hover:bg-white transition-colors">
                                                <AppIcon :name="result.icon ?? 'sparkle'" :size="13" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-[13px] font-medium text-slate-900">{{ result.title }}</p>
                                                <p v-if="result.subtitle" class="truncate text-[11px] text-slate-500">{{ result.subtitle }}</p>
                                            </div>
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                {{ result.type }}
                                            </span>
                                        </Link>
                                    </div>

                                    <div v-else class="px-6 py-12 text-center">
                                        <p class="text-[13px] font-medium text-slate-500">No results found for "{{ searchQuery }}"</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between bg-slate-50 px-4 py-2 text-[10px] text-slate-500">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-1.5">
                                            <kbd class="inline-flex h-4 items-center rounded border border-slate-200 bg-white px-1 font-bold text-slate-400">↑↓</kbd>
                                            <span>to navigate</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <kbd class="inline-flex h-4 items-center rounded border border-slate-200 bg-white px-1 font-bold text-slate-400">↵</kbd>
                                            <span>to select</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <kbd class="inline-flex h-4 items-center rounded border border-slate-200 bg-white px-1 font-bold text-slate-400">ESC</kbd>
                                        <span>to close</span>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </Dialog>
            </TransitionRoot>
        </div>
    </div>
</template>
