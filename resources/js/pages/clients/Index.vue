<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogFooter, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowRight, Plus, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ClientRecord {
    id: number;
    full_name: string;
    email: null | string;
    phone: null | string;
    nationality: null | string;
    destination_country: null | string;
    lead_source: null | string;
    status: string;
    status_label: string;
    owner_name: null | string;
    visa_cases_count: number;
    open_tasks_count: number;
    created_at: null | string;
}

interface Option {
    value: string;
    label: string;
}

const props = defineProps<{
    clients: ClientRecord[];
    filters: {
        search: string;
        status: string;
    };
    maritalStatusOptions: Option[];
    statusOptions: Option[];
}>();

const page = usePage<SharedData>();
const isCreateDialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
];

const filterForm = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? 'all',
});

const createForm = useForm({
    full_name: '',
    email: '',
    phone: '',
    nationality: '',
    destination_country: '',
    lead_source: '',
    status: 'lead',
    notes: '',
});

const hasActiveFilters = computed(() => filterForm.search.trim() !== '' || filterForm.status !== 'all');

const clientInitials = (value: string) =>
    value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

const submitFilters = () => {
    router.get(
        route('clients.index'),
        {
            search: filterForm.search || undefined,
            status: filterForm.status !== 'all' ? filterForm.status : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = 'all';
    submitFilters();
};

const submit = () => {
    createForm.post(route('clients.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.status = 'lead';
            isCreateDialogOpen.value = false;
        },
    });
};

const formatDate = (value: null | string) => (value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(value)) : 'Unknown');

const statusClasses = (status: string) =>
    ({
        lead: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
        qualified: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        closed: 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200',
    })[status] ?? 'bg-secondary text-secondary-foreground';
</script>

<template>
    <Head title="Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 md:p-4">
            <div
                v-if="page.props.flash.success"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300"
            >
                {{ page.props.flash.success }}
            </div>

            <header class="app-panel px-4 py-4 md:px-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">Clients</h2>
                        <span class="rounded-full bg-muted/50 px-2.5 py-0.5 text-xs font-medium text-muted-foreground">{{ clients.length }} records</span>
                    </div>

                    <Button type="button" class="h-10 gap-2 rounded-xl px-4" @click="isCreateDialogOpen = true">
                        <Plus class="size-4" />
                        New Client
                    </Button>
                </div>
            </header>

            <section class="app-panel px-4 py-4 md:px-5">
                <form class="grid gap-3 xl:grid-cols-[minmax(0,1.5fr)_220px_auto_auto]" @submit.prevent="submitFilters">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground/70" />
                        <Input
                            v-model="filterForm.search"
                            class="h-10 rounded-xl border-border/60 bg-muted/10 pl-9 focus:bg-background"
                            placeholder="Search name, email, phone..."
                        />
                    </div>

                    <select
                        v-model="filterForm.status"
                        class="flex h-10 w-full rounded-xl border border-border/60 bg-muted/10 px-3 py-2 text-sm focus-visible:bg-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                    >
                        <option value="all">All statuses</option>
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <Button type="submit" variant="secondary" class="h-10 rounded-xl">Apply Filters</Button>
                    <Button v-if="hasActiveFilters" type="button" variant="ghost" class="h-10 gap-2 rounded-xl" @click="resetFilters">
                        <X class="size-4" />
                        Clear
                    </Button>
                </form>
            </section>

            <section class="app-panel overflow-hidden">
                <div
                    v-if="clients.length === 0"
                    class="m-4 rounded-xl border border-dashed border-border bg-muted/10 px-4 py-16 text-center"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ hasActiveFilters ? 'No clients match the current filters.' : 'Your client list is currently empty.' }}
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead class="bg-muted/30">
                            <tr class="border-b border-border/60 text-[10px] uppercase tracking-widest text-muted-foreground/80">
                                <th class="px-5 py-3 font-semibold">Client Detail</th>
                                <th class="px-3 py-3 font-semibold">Status</th>
                                <th class="px-3 py-3 font-semibold">Destination</th>
                                <th class="px-3 py-3 font-semibold">Nationality</th>
                                <th class="px-3 py-3 font-semibold">Source</th>
                                <th class="px-3 py-3 text-center font-semibold">Cases</th>
                                <th class="px-3 py-3 text-center font-semibold">Tasks</th>
                                <th class="px-3 py-3 font-semibold">Created</th>
                                <th class="px-5 py-3 text-right font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40">
                            <tr
                                v-for="client in clients"
                                :key="client.id"
                                class="align-top transition-colors hover:bg-muted/20"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div
                                            class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-border/60 bg-background text-[11px] font-bold text-foreground"
                                        >
                                            {{ clientInitials(client.full_name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ client.full_name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                                {{ client.email || client.phone || 'No contact provided' }}
                                            </p>
                                            <p class="mt-1 text-[10px] uppercase tracking-wider text-muted-foreground/70">
                                                {{ client.owner_name || 'Unassigned' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-tight" :class="statusClasses(client.status)">
                                        {{ client.status_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-muted-foreground">{{ client.destination_country || '—' }}</td>
                                <td class="px-3 py-4 text-muted-foreground">{{ client.nationality || '—' }}</td>
                                <td class="px-3 py-4 text-muted-foreground">{{ client.lead_source || '—' }}</td>
                                <td class="px-3 py-4 text-center tabular-nums text-muted-foreground">{{ client.visa_cases_count }}</td>
                                <td class="px-3 py-4 text-center tabular-nums text-muted-foreground">{{ client.open_tasks_count }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-muted-foreground">{{ formatDate(client.created_at) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <Button as-child variant="ghost" size="sm" class="h-8 gap-2 rounded-lg hover:bg-muted/80">
                                        <Link :href="route('clients.show', client.id)">
                                            View Profile
                                            <ArrowRight class="size-3.5" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <Dialog v-model:open="isCreateDialogOpen">
                <DialogScrollContent class="max-w-2xl p-0 overflow-hidden rounded-2xl">
                    <DialogHeader class="border-b border-border/60 px-6 py-4 bg-muted/10">
                        <DialogTitle>Add New Client</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-5 px-6 py-6" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="full_name">Full Name</Label>
                                <Input id="full_name" v-model="createForm.full_name" class="rounded-lg" placeholder="John Doe" />
                                <InputError :message="createForm.errors.full_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email Address</Label>
                                <Input id="email" v-model="createForm.email" type="email" class="rounded-lg" placeholder="john@example.com" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="phone">Phone Number</Label>
                                <Input id="phone" v-model="createForm.phone" class="rounded-lg" placeholder="+976..." />
                            </div>

                            <div class="grid gap-2">
                                <Label for="nationality">Nationality</Label>
                                <Input id="nationality" v-model="createForm.nationality" class="rounded-lg" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="destination_country">Destination</Label>
                                <Input id="destination_country" v-model="createForm.destination_country" class="rounded-lg" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="status">Initial Status</Label>
                                <select
                                    id="status"
                                    v-model="createForm.status"
                                    class="flex h-9 w-full rounded-lg border border-input bg-background px-3 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="grid gap-2">
                                <Label for="lead_source">Lead Source</Label>
                                <Input id="lead_source" v-model="createForm.lead_source" class="rounded-lg" />
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label for="notes">Internal Intake Notes</Label>
                                <textarea
                                    id="notes"
                                    v-model="createForm.notes"
                                    rows="3"
                                    class="min-h-20 rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                    placeholder="Add any initial context or requirements..."
                                />
                            </div>
                        </div>

                        <DialogFooter class="border-t border-border/60 pt-5">
                            <Button type="button" variant="ghost" class="rounded-lg" @click="isCreateDialogOpen = false">Cancel</Button>
                            <Button :disabled="createForm.processing" class="rounded-lg px-6">Create Record</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
