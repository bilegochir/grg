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
import { computed, ref, watch } from 'vue';

interface Option {
    value: string;
    label: string;
}

interface VisaCaseRecord {
    id: number;
    reference_code: string;
    visa_type: string;
    destination_country: string;
    institution_name: null | string;
    status: string;
    status_label: string;
    client_name: null | string;
    assignee_name: null | string;
    submitted_at: null | string;
    decision_at: null | string;
}

const props = defineProps<{
    visaCases: VisaCaseRecord[];
    clients: Array<{ id: number; full_name: string }>;
    users: Array<{ id: number; name: string }>;
    requirementCountries: string[];
    institutionRequirements: Record<string, boolean>;
    visaTypesByCountry: Record<string, string[]>;
    statusOptions: Option[];
    filters: {
        search: string;
        status: string;
        country: string;
    };
}>();

const institutionRequirementKey = (country: string, visaType: string) => `${country}::${visaType}`;

const page = usePage<SharedData>();
const isCreateDialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Visa Cases', href: '/visa-cases' }];

const filterForm = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? 'all',
    country: props.filters.country ?? '',
});

const createForm = useForm({
    client_id: '',
    assigned_user_id: '',
    visa_type: '',
    destination_country: '',
    institution_name: '',
    status: 'intake',
    submitted_at: '',
    decision_at: '',
    notes: '',
});

const availableVisaTypes = computed(() => props.visaTypesByCountry[createForm.destination_country] ?? []);
const needsInstitutionName = computed(
    () => props.institutionRequirements[institutionRequirementKey(createForm.destination_country, createForm.visa_type)] ?? false,
);
const hasActiveFilters = computed(() => filterForm.search.trim() !== '' || filterForm.status !== 'all' || filterForm.country !== '');

watch(
    () => createForm.destination_country,
    () => {
        if (!availableVisaTypes.value.includes(createForm.visa_type)) {
            createForm.visa_type = '';
        }
    },
);

watch(
    () => createForm.visa_type,
    () => {
        if (!needsInstitutionName.value) {
            createForm.institution_name = '';
        }
    },
);

const submitFilters = () => {
    router.get(
        route('visa-cases.index'),
        {
            search: filterForm.search || undefined,
            status: filterForm.status !== 'all' ? filterForm.status : undefined,
            country: filterForm.country || undefined,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = 'all';
    filterForm.country = '';
    submitFilters();
};

const submit = () => {
    createForm.post(route('visa-cases.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.status = 'intake';
            isCreateDialogOpen.value = false;
        },
    });
};

const formatDate = (value: null | string) => (value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(value)) : 'Pending');

const statusClasses = (status: string) =>
    ({
        intake: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900',
        documents_pending: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-200',
        ready_to_file: 'bg-violet-100 text-violet-800 dark:bg-violet-950/70 dark:text-violet-200',
        submitted: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-200',
        approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200',
        rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-200',
        closed: 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200',
    })[status] ?? 'bg-secondary text-secondary-foreground';
</script>

<template>
    <Head title="Visa Cases" />

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
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">Visa Cases</h2>
                        <span class="rounded-full bg-muted/50 px-2.5 py-0.5 text-xs font-medium text-muted-foreground">{{ visaCases.length }} cases</span>
                    </div>

                    <Button type="button" class="h-10 gap-2 rounded-xl px-4" @click="isCreateDialogOpen = true">
                        <Plus class="size-4" />
                        New Case
                    </Button>
                </div>
            </header>

            <section class="app-panel px-4 py-4 md:px-5">
                <form class="grid gap-3 xl:grid-cols-[minmax(0,1.5fr)_200px_200px_auto_auto]" @submit.prevent="submitFilters">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground/70" />
                        <Input
                            v-model="filterForm.search"
                            class="h-10 rounded-xl border-border/60 bg-muted/10 pl-9 focus:bg-background"
                            placeholder="Search reference, client, visa type..."
                        />
                    </div>

                    <select v-model="filterForm.status" class="flex h-10 w-full rounded-xl border border-border/60 bg-muted/10 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring/15">
                        <option value="all">All statuses</option>
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>

                    <select v-model="filterForm.country" class="flex h-10 w-full rounded-xl border border-border/60 bg-muted/10 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring/15">
                        <option value="">All countries</option>
                        <option v-for="country in requirementCountries" :key="country" :value="country">{{ country }}</option>
                    </select>

                    <Button type="submit" variant="secondary" class="h-10 rounded-xl">Apply</Button>
                    <Button v-if="hasActiveFilters" type="button" variant="ghost" class="h-10 gap-2 rounded-xl" @click="resetFilters">
                        <X class="size-4" /> Clear
                    </Button>
                </form>
            </section>

            <section class="app-panel overflow-hidden">
                <div v-if="visaCases.length === 0" class="m-4 rounded-xl border border-dashed border-border bg-muted/10 px-4 py-16 text-center">
                    <p class="text-sm text-muted-foreground">{{ hasActiveFilters ? 'No cases match your search criteria.' : 'No visa cases recorded yet.' }}</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead class="bg-muted/30">
                            <tr class="border-b border-border/60 text-[10px] uppercase tracking-widest text-muted-foreground/80">
                                <th class="px-5 py-3 font-semibold">Ref Code</th>
                                <th class="px-3 py-3 font-semibold">Client</th>
                                <th class="px-3 py-3 font-semibold">Visa Detail</th>
                                <th class="px-3 py-3 font-semibold">Status</th>
                                <th class="px-3 py-3 font-semibold">Assignee</th>
                                <th class="px-3 py-3 font-semibold">Submitted</th>
                                <th class="px-3 py-3 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40">
                            <tr v-for="visaCase in visaCases" :key="visaCase.id" class="align-top transition-colors hover:bg-muted/20">
                                <td class="px-5 py-4 font-mono text-[11px] font-semibold text-slate-900 dark:text-slate-100">{{ visaCase.reference_code }}</td>
                                <td class="px-3 py-4 font-medium text-foreground">{{ visaCase.client_name || '—' }}</td>
                                <td class="px-3 py-4">
                                    <p class="font-medium text-foreground">{{ visaCase.visa_type }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ visaCase.destination_country }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="statusClasses(visaCase.status)">
                                        {{ visaCase.status_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-muted-foreground">{{ visaCase.assignee_name || 'Unassigned' }}</td>
                                <td class="px-3 py-4 tabular-nums text-muted-foreground">{{ formatDate(visaCase.submitted_at) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <Button as-child variant="ghost" size="sm" class="h-8 gap-2 rounded-lg">
                                        <Link :href="route('visa-cases.show', visaCase.id)">View Case <ArrowRight class="size-3.5" /></Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <Dialog v-model:open="isCreateDialogOpen">
                <DialogScrollContent class="max-w-3xl p-0 overflow-hidden rounded-2xl">
                    <DialogHeader class="border-b border-border/60 px-6 py-4 bg-muted/10">
                        <DialogTitle>Initiate New Visa Case</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-5 px-6 py-6" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="client_id">Client</Label>
                                <select id="client_id" v-model="createForm.client_id" class="flex h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring/15">
                                    <option value="">Select a client</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.full_name }}</option>
                                </select>
                                <InputError :message="createForm.errors.client_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="assigned_user_id">Assignee</Label>
                                <select id="assigned_user_id" v-model="createForm.assigned_user_id" class="flex h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring/15">
                                    <option value="">Unassigned</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                            </div>

                            <div class="grid gap-2">
                                <Label for="destination_country">Destination</Label>
                                <select id="destination_country" v-model="createForm.destination_country" class="flex h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring/15">
                                    <option value="">Select a country</option>
                                    <option v-for="country in requirementCountries" :key="country" :value="country">{{ country }}</option>
                                </select>
                            </div>

                            <div class="grid gap-2">
                                <Label for="visa_type">Visa Type</Label>
                                <select id="visa_type" v-model="createForm.visa_type" :disabled="!createForm.destination_country" class="flex h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring/15 disabled:opacity-50">
                                    <option value="">{{ !createForm.destination_country ? 'Select country first' : 'Select a visa' }}</option>
                                    <option v-for="v in availableVisaTypes" :key="v" :value="v">{{ v }}</option>
                                </select>
                            </div>

                            <div v-if="needsInstitutionName" class="grid gap-2 md:col-span-2">
                                <Label for="institution_name">Institution (University/College)</Label>
                                <Input id="institution_name" v-model="createForm.institution_name" class="rounded-lg" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="submitted_at">Submission Date</Label>
                                <Input id="submitted_at" v-model="createForm.submitted_at" type="date" class="rounded-lg" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="status">Initial Status</Label>
                                <select id="status" v-model="createForm.status" class="flex h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring/15">
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label for="notes">Internal Case Notes</Label>
                                <textarea id="notes" v-model="createForm.notes" rows="3" class="min-h-20 rounded-lg border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-ring/15" />
                            </div>
                        </div>

                        <DialogFooter class="border-t border-border/60 pt-5">
                            <Button type="button" variant="ghost" @click="isCreateDialogOpen = false">Cancel</Button>
                            <Button :disabled="createForm.processing" class="px-6 rounded-lg">Create Case</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
