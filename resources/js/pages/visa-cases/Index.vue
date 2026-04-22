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

const institutionRequirementKey = (country: string, visaType: string) => `${country}::${visaType}`;

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

const page = usePage<SharedData>();
const isCreateDialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Visa Cases',
        href: '/visa-cases',
    },
];

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
        if (availableVisaTypes.value.includes(createForm.visa_type)) {
            return;
        }

        createForm.visa_type = '';
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
        <div class="flex flex-col gap-3 p-3 md:p-4">
            <div v-if="page.props.flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ page.props.flash.success }}
            </div>

            <section class="app-panel overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-border px-3.5 py-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-slate-950 dark:text-slate-50">Visa cases</h2>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <span>{{ visaCases.length }} cases</span>
                        <Button type="button" class="gap-2" @click="isCreateDialogOpen = true">
                            <Plus class="size-4" />
                            New case
                        </Button>
                    </div>
                </div>

                <div class="border-b border-border px-3.5 py-3">
                    <form class="grid gap-2 md:grid-cols-[minmax(0,1fr)_180px_180px_auto_auto]" @submit.prevent="submitFilters">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="filterForm.search" class="pl-8" placeholder="Search reference, client, visa, school, country, or assignee" />
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

                        <select
                            v-model="filterForm.country"
                            class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                        >
                            <option value="">All countries</option>
                            <option v-for="country in requirementCountries" :key="country" :value="country">
                                {{ country }}
                            </option>
                        </select>

                        <Button type="submit" variant="outline">Apply</Button>
                        <Button v-if="hasActiveFilters" type="button" variant="ghost" class="gap-2" @click="resetFilters">
                            <X class="size-4" />
                            Clear
                        </Button>
                    </form>
                </div>

                <div v-if="visaCases.length === 0" class="px-3.5 py-4 text-sm text-muted-foreground">
                    {{ hasActiveFilters ? 'No visa cases match the current filters.' : 'No visa cases yet.' }}
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-border text-left text-[11px] uppercase tracking-[0.16em] text-muted-foreground">
                                <th class="px-3.5 py-2 font-medium">Reference</th>
                                <th class="px-3 py-2 font-medium">Client</th>
                                <th class="px-3 py-2 font-medium">Visa type</th>
                                <th class="px-3 py-2 font-medium">Destination</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 font-medium">Assignee</th>
                                <th class="px-3 py-2 font-medium">Submitted</th>
                                <th class="px-3 py-2 font-medium">Decision</th>
                                <th class="px-3.5 py-2 text-right font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="visaCase in visaCases"
                                :key="visaCase.id"
                                class="border-b border-border/70 align-top transition-colors hover:bg-muted/40"
                            >
                                <td class="px-3.5 py-2.5 font-medium text-foreground">{{ visaCase.reference_code }}</td>
                                <td class="px-3 py-2.5 text-muted-foreground">{{ visaCase.client_name || 'No client linked' }}</td>
                                <td class="px-3 py-2.5 text-muted-foreground">{{ visaCase.visa_type }}</td>
                                <td class="px-3 py-2.5 text-muted-foreground">{{ visaCase.destination_country }}</td>
                                <td class="px-3 py-2.5">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="statusClasses(visaCase.status)">
                                        {{ visaCase.status_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-muted-foreground">{{ visaCase.assignee_name || 'Unassigned' }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-muted-foreground">{{ formatDate(visaCase.submitted_at) }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-muted-foreground">{{ formatDate(visaCase.decision_at) }}</td>
                                <td class="px-3.5 py-2.5 text-right">
                                    <Button as-child variant="ghost" size="sm" class="gap-2">
                                        <Link :href="route('visa-cases.show', visaCase.id)">
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
                <DialogScrollContent class="max-w-4xl p-0">
                    <DialogHeader class="border-b border-border px-6 py-4">
                        <DialogTitle>New visa case</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4 px-6 py-5" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="client_id">Client</Label>
                                <select
                                    id="client_id"
                                    v-model="createForm.client_id"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option value="">Select a client</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.full_name }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.client_id" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="assigned_user_id">Assigned agent</Label>
                                <select
                                    id="assigned_user_id"
                                    v-model="createForm.assigned_user_id"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option value="">Unassigned</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.assigned_user_id" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="destination_country">Destination</Label>
                                <select
                                    id="destination_country"
                                    v-model="createForm.destination_country"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option value="">Select a country</option>
                                    <option v-for="country in requirementCountries" :key="country" :value="country">
                                        {{ country }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.destination_country" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="visa_type">Visa type</Label>
                                <select
                                    id="visa_type"
                                    v-model="createForm.visa_type"
                                    :disabled="createForm.destination_country === ''"
                                    class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <option value="">{{ createForm.destination_country === '' ? 'Select country first' : 'Select a visa' }}</option>
                                    <option v-for="visaType in availableVisaTypes" :key="visaType" :value="visaType">
                                        {{ visaType }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.visa_type" />
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

                            <div v-if="needsInstitutionName" class="grid gap-1.5 md:col-span-2">
                                <Label for="institution_name">University or college</Label>
                                <Input id="institution_name" v-model="createForm.institution_name" placeholder="University of Melbourne" />
                                <InputError :message="createForm.errors.institution_name" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="submitted_at">Submitted</Label>
                                <Input id="submitted_at" v-model="createForm.submitted_at" type="date" />
                                <InputError :message="createForm.errors.submitted_at" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="decision_at">Decision</Label>
                                <Input id="decision_at" v-model="createForm.decision_at" type="date" />
                                <InputError :message="createForm.errors.decision_at" />
                            </div>

                            <div class="grid gap-1.5 md:col-span-2">
                                <Label for="notes">Notes</Label>
                                <textarea
                                    id="notes"
                                    v-model="createForm.notes"
                                    rows="4"
                                    class="min-h-24 rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                    placeholder="Case summary, blockers, or filing context"
                                />
                                <InputError :message="createForm.errors.notes" />
                            </div>
                        </div>

                        <DialogFooter class="border-t border-border pt-4">
                            <Button type="button" variant="ghost" @click="isCreateDialogOpen = false">Cancel</Button>
                            <Button :disabled="createForm.processing">Create case</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
