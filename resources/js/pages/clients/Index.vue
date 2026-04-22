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
    {
        title: 'Clients',
        href: '/clients',
    },
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
        <div class="flex flex-col gap-3 p-3 md:p-4">
            <div v-if="page.props.flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ page.props.flash.success }}
            </div>

            <section class="app-panel overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-border px-3.5 py-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-slate-950 dark:text-slate-50">Clients</h2>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <span>{{ clients.length }} clients</span>
                        <Button type="button" class="gap-2" @click="isCreateDialogOpen = true">
                            <Plus class="size-4" />
                            New client
                        </Button>
                    </div>
                </div>

                <div class="border-b border-border px-3.5 py-3">
                    <form class="grid gap-2 md:grid-cols-[minmax(0,1fr)_180px_auto_auto]" @submit.prevent="submitFilters">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="filterForm.search"
                                class="pl-8"
                                placeholder="Search name, email, phone, passport, occupation, destination, or source"
                            />
                        </div>

                        <select
                            v-model="filterForm.status"
                            class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                        >
                            <option value="all">All statuses</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>

                        <Button type="submit" variant="outline">Apply</Button>
                        <Button v-if="hasActiveFilters" type="button" variant="ghost" class="gap-2" @click="resetFilters">
                            <X class="size-4" />
                            Clear
                        </Button>
                    </form>
                </div>

                <div v-if="clients.length === 0" class="px-3.5 py-4 text-sm text-muted-foreground">
                    {{ hasActiveFilters ? 'No clients match the current filters.' : 'No clients yet.' }}
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-border text-left text-[11px] uppercase tracking-[0.16em] text-muted-foreground">
                                <th class="px-3.5 py-2 font-medium">Client</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 font-medium">Destination</th>
                                <th class="px-3 py-2 font-medium">Nationality</th>
                                <th class="px-3 py-2 font-medium">Source</th>
                                <th class="px-3 py-2 text-center font-medium">Cases</th>
                                <th class="px-3 py-2 text-center font-medium">Tasks</th>
                                <th class="px-3 py-2 font-medium">Created</th>
                                <th class="px-3.5 py-2 text-right font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="client in clients"
                                :key="client.id"
                                class="border-b border-border/70 align-top transition-colors hover:bg-muted/40"
                            >
                                <td class="px-3.5 py-2.5">
                                    <div class="min-w-0">
                                        <p class="font-medium text-foreground">{{ client.full_name }}</p>
                                        <p class="mt-0.5 text-[13px] text-muted-foreground">
                                            {{ client.email || client.phone || 'No contact method yet' }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="statusClasses(client.status)">
                                        {{ client.status_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-muted-foreground">{{ client.destination_country || 'Not set' }}</td>
                                <td class="px-3 py-2.5 text-muted-foreground">{{ client.nationality || 'Not set' }}</td>
                                <td class="px-3 py-2.5 text-muted-foreground">{{ client.lead_source || 'Unknown' }}</td>
                                <td class="px-3 py-2.5 text-center text-muted-foreground">{{ client.visa_cases_count }}</td>
                                <td class="px-3 py-2.5 text-center text-muted-foreground">{{ client.open_tasks_count }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-muted-foreground">{{ formatDate(client.created_at) }}</td>
                                <td class="px-3.5 py-2.5 text-right">
                                    <Button as-child variant="ghost" size="sm" class="gap-2">
                                        <Link :href="route('clients.show', client.id)">
                                            Open
                                            <ArrowRight class="size-4" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <Dialog v-model:open="isCreateDialogOpen">
                <DialogScrollContent class="max-w-3xl p-0">
                    <DialogHeader class="border-b border-border px-6 py-4">
                        <DialogTitle>New client</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4 px-6 py-5" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-1.5 md:col-span-2">
                                <Label for="full_name">Full name</Label>
                                <Input id="full_name" v-model="createForm.full_name" placeholder="Amina Batsukh" />
                                <InputError :message="createForm.errors.full_name" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="createForm.email" type="email" placeholder="client@example.com" />
                                <InputError :message="createForm.errors.email" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="phone">Phone</Label>
                                <Input id="phone" v-model="createForm.phone" placeholder="+976..." />
                                <InputError :message="createForm.errors.phone" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="nationality">Nationality</Label>
                                <Input id="nationality" v-model="createForm.nationality" placeholder="Mongolian" />
                                <InputError :message="createForm.errors.nationality" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="destination_country">Destination</Label>
                                <Input id="destination_country" v-model="createForm.destination_country" placeholder="Australia" />
                                <InputError :message="createForm.errors.destination_country" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="createForm.status"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.status" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="lead_source">Lead source</Label>
                                <Input id="lead_source" v-model="createForm.lead_source" placeholder="Referral" />
                                <InputError :message="createForm.errors.lead_source" />
                            </div>

                            <div class="grid gap-1.5 md:col-span-2">
                                <Label for="notes">Notes</Label>
                                <textarea
                                    id="notes"
                                    v-model="createForm.notes"
                                    rows="4"
                                    class="min-h-24 rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                    placeholder="Short intake notes or context"
                                />
                                <InputError :message="createForm.errors.notes" />
                            </div>
                        </div>

                        <DialogFooter class="border-t border-border pt-4">
                            <Button type="button" variant="ghost" @click="isCreateDialogOpen = false">Cancel</Button>
                            <Button :disabled="createForm.processing">Create client</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
