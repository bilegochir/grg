<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Dialog, DialogFooter, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface RequirementItem {
    id: number;
    category: null | string;
    label: string;
    help_text: null | string;
    is_required: boolean;
}

interface RequirementTemplate {
    id: number;
    region: string;
    country_name: string;
    visa_type: string;
    visa_code: null | string;
    requires_institution_name: boolean;
    label: string;
    description: null | string;
    source_url: null | string;
    source_checked_at: null | string;
    processing_time_summary: null | string;
    fee_summary: null | string;
    stay_summary: null | string;
    is_active: boolean;
    items: RequirementItem[];
}

interface RequirementRow {
    category: string;
    label: string;
    help_text: string;
    is_required: boolean;
}

const props = defineProps<{
    templates: RequirementTemplate[];
}>();

const page = usePage<SharedData>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Visa requirements',
        href: '/settings/visa-requirements',
    },
];

const regionOptions = ['Australia', 'Europe', 'Asia'] as const;
const isDialogOpen = ref(false);
const editingKey = ref<string | null>(null);
const search = ref('');
const regionFilter = ref<'All' | 'Australia' | 'Europe' | 'Asia'>('All');
const reviewFilter = ref<'all' | 'needs_review' | 'reviewed'>('all');
const expandedRequirementRows = ref<Record<number, boolean>>({ 0: true });

const blankRequirement = (): RequirementRow => ({
    category: '',
    label: '',
    help_text: '',
    is_required: true,
});

const blankSourceCheckedAt = () => new Date().toISOString().slice(0, 10);

const resetForm = () => {
    form.region = 'Australia';
    form.country_name = 'Australia';
    form.visa_type = '';
    form.visa_code = '';
    form.requires_institution_name = false;
    form.description = '';
    form.source_url = '';
    form.source_checked_at = blankSourceCheckedAt();
    form.processing_time_summary = '';
    form.fee_summary = '';
    form.stay_summary = '';
    form.requirements = [blankRequirement()];
    form.clearErrors();
    expandedRequirementRows.value = { 0: true };
};

const form = useForm({
    region: 'Australia',
    country_name: 'Australia',
    visa_type: '',
    visa_code: '',
    requires_institution_name: false,
    description: '',
    source_url: '',
    source_checked_at: blankSourceCheckedAt(),
    processing_time_summary: '',
    fee_summary: '',
    stay_summary: '',
    requirements: [blankRequirement()],
});

const dialogTitle = computed(() => (editingKey.value === null ? 'New template' : 'Edit template'));
const filteredTemplates = computed(() =>
    props.templates.filter((template) => {
        const query = search.value.trim().toLowerCase();
        const matchesSearch =
            query === '' ||
            [template.country_name, template.visa_type, template.visa_code ?? '', template.region].join(' ').toLowerCase().includes(query);
        const matchesRegion = regionFilter.value === 'All' || template.region === regionFilter.value;
        const matchesReview =
            reviewFilter.value === 'all' ||
            (reviewFilter.value === 'reviewed' && template.source_checked_at !== null) ||
            (reviewFilter.value === 'needs_review' && template.source_checked_at === null);

        return matchesSearch && matchesRegion && matchesReview;
    }),
);

const submit = () => {
    form.post(route('settings.visa-requirements.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isDialogOpen.value = false;
            editingKey.value = null;
            resetForm();
        },
    });
};

const addRequirement = () => {
    form.requirements.push(blankRequirement());
    expandedRequirementRows.value = {
        ...expandedRequirementRows.value,
        [form.requirements.length - 1]: true,
    };
};

const removeRequirement = (index: number) => {
    if (form.requirements.length === 1) {
        form.requirements[0] = blankRequirement();
        expandedRequirementRows.value = { 0: true };
        return;
    }

    const nextExpandedRows = Object.entries(expandedRequirementRows.value).reduce<Record<number, boolean>>((carry, [key, value]) => {
        const numericKey = Number(key);

        if (!value || numericKey === index) {
            return carry;
        }

        carry[numericKey > index ? numericKey - 1 : numericKey] = true;

        return carry;
    }, {});

    form.requirements.splice(index, 1);
    expandedRequirementRows.value =
        Object.keys(nextExpandedRows).length > 0 ? nextExpandedRows : { [Math.min(index, form.requirements.length - 1)]: true };
};

const loadTemplate = (template: RequirementTemplate) => {
    editingKey.value = `${template.country_name}:${template.visa_type}`;
    form.region = template.region;
    form.country_name = template.country_name;
    form.visa_type = template.visa_type;
    form.visa_code = template.visa_code ?? '';
    form.requires_institution_name = template.requires_institution_name;
    form.description = template.description ?? '';
    form.source_url = template.source_url ?? '';
    form.source_checked_at = template.source_checked_at ?? blankSourceCheckedAt();
    form.processing_time_summary = template.processing_time_summary ?? '';
    form.fee_summary = template.fee_summary ?? '';
    form.stay_summary = template.stay_summary ?? '';
    form.requirements = template.items.map((item) => ({
        category: item.category ?? '',
        label: item.label,
        help_text: item.help_text ?? '',
        is_required: item.is_required,
    }));
    form.clearErrors();
    expandedRequirementRows.value = form.requirements.reduce<Record<number, boolean>>((carry, _requirement, index) => {
        carry[index] = index === 0;

        return carry;
    }, {});
    isDialogOpen.value = true;
};

const markReviewed = (templateId: number) => {
    router.patch(
        route('settings.visa-requirements.review', templateId),
        {},
        {
            preserveScroll: true,
        },
    );
};

const openCreateDialog = () => {
    editingKey.value = null;
    resetForm();
    isDialogOpen.value = true;
};

const setRequirementRowOpen = (index: number, open: boolean) => {
    expandedRequirementRows.value = {
        ...expandedRequirementRows.value,
        [index]: open,
    };
};

const requirementRowPreview = (requirement: RequirementRow) => {
    const label = requirement.label.trim();

    if (label !== '') {
        return label;
    }

    return 'Add the document or evidence required for this visa template.';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Visa requirements" />

        <SettingsLayout>
            <div class="flex flex-col gap-4">
                <HeadingSmall title="Requirement library" />

                <div v-if="page.props.flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ page.props.flash.success }}
                </div>

                <section class="app-panel overflow-hidden">
                    <div class="border-b border-border px-4 py-3">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-foreground">Templates</h2>
                            </div>
                            <Button type="button" @click="openCreateDialog">New template</Button>
                        </div>
                    </div>

                    <div class="border-b border-border px-4 py-3">
                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_160px_160px]">
                            <Input v-model="search" placeholder="Search country, visa, or code" />
                            <select
                                v-model="regionFilter"
                                class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                            >
                                <option value="All">All regions</option>
                                <option v-for="region in regionOptions" :key="region" :value="region">{{ region }}</option>
                            </select>
                            <select
                                v-model="reviewFilter"
                                class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                            >
                                <option value="all">All review states</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="needs_review">Needs review</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="props.templates.length === 0" class="px-4 py-4 text-sm text-muted-foreground">No requirement templates saved yet.</div>

                    <div v-else-if="filteredTemplates.length === 0" class="px-4 py-4 text-sm text-muted-foreground">
                        No templates match the current filters.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[960px] text-sm">
                            <thead class="bg-muted/40 text-left text-[11px] uppercase tracking-[0.14em] text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Country</th>
                                    <th class="px-3 py-2 font-medium">Visa</th>
                                    <th class="px-3 py-2 font-medium">Code</th>
                                    <th class="px-3 py-2 font-medium">School info</th>
                                    <th class="px-3 py-2 font-medium">Region</th>
                                    <th class="px-3 py-2 font-medium">Requirements</th>
                                    <th class="px-3 py-2 font-medium">Reviewed</th>
                                    <th class="px-3 py-2 font-medium">Source</th>
                                    <th class="px-4 py-2 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="template in filteredTemplates" :key="template.id" class="border-t border-border">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-foreground">{{ template.country_name }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-muted-foreground">{{ template.visa_type }}</td>
                                    <td class="px-3 py-3 text-muted-foreground">{{ template.visa_code || 'N/A' }}</td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                            :class="
                                                template.requires_institution_name
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200'
                                                    : 'bg-muted text-muted-foreground'
                                            "
                                        >
                                            {{ template.requires_institution_name ? 'Required' : 'Not needed' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                                            {{ template.region }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-muted-foreground">{{ template.items.length }}</td>
                                    <td class="px-3 py-3 text-muted-foreground">{{ template.source_checked_at || 'Not reviewed' }}</td>
                                    <td class="px-3 py-3 text-muted-foreground">
                                        <a
                                            v-if="template.source_url"
                                            :href="template.source_url"
                                            target="_blank"
                                            rel="noreferrer"
                                            class="text-foreground underline"
                                        >
                                            Official source
                                        </a>
                                        <span v-else>None</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <Button type="button" variant="ghost" size="sm" @click="loadTemplate(template)">Edit</Button>
                                            <Button type="button" variant="outline" size="sm" @click="markReviewed(template.id)"
                                                >Reviewed today</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <Dialog v-model:open="isDialogOpen">
                    <DialogScrollContent class="max-w-6xl p-0">
                        <DialogHeader class="border-b border-border px-6 py-4">
                            <DialogTitle>{{ dialogTitle }}</DialogTitle>
                        </DialogHeader>

                        <form class="grid gap-4 px-6 py-5" @submit.prevent="submit">
                            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.9fr)]">
                                <section class="grid gap-4 rounded-xl border border-border bg-muted/10 p-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Template basics</h3>
                                        <p class="text-sm text-muted-foreground">
                                            Set the visa, country, and the short internal note your team will recognize.
                                        </p>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-1.5">
                                            <Label for="region">Region</Label>
                                            <select
                                                id="region"
                                                v-model="form.region"
                                                class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                            >
                                                <option v-for="region in regionOptions" :key="region" :value="region">
                                                    {{ region }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors.region" />
                                        </div>

                                        <div class="grid gap-1.5">
                                            <Label for="country_name">Country</Label>
                                            <Input id="country_name" v-model="form.country_name" placeholder="Australia" />
                                            <InputError :message="form.errors.country_name" />
                                        </div>

                                        <div class="grid gap-1.5">
                                            <Label for="visa_type">Visa</Label>
                                            <Input id="visa_type" v-model="form.visa_type" placeholder="Visitor visa" />
                                            <InputError :message="form.errors.visa_type" />
                                        </div>

                                        <div class="grid gap-1.5">
                                            <Label for="visa_code">Visa code</Label>
                                            <Input id="visa_code" v-model="form.visa_code" placeholder="600 / C / D" />
                                            <InputError :message="form.errors.visa_code" />
                                        </div>

                                        <div class="flex items-center gap-3 rounded-lg border border-border bg-background px-3 py-2 md:col-span-2">
                                            <input
                                                id="requires_institution_name"
                                                v-model="form.requires_institution_name"
                                                type="checkbox"
                                                class="size-4 rounded border-border"
                                            />
                                            <div class="min-w-0">
                                                <Label for="requires_institution_name" class="text-sm font-medium text-foreground">
                                                    Require university or college information
                                                </Label>
                                                <p class="text-xs text-muted-foreground">
                                                    Visa cases using this template must include the institution name.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid gap-1.5 md:col-span-2">
                                            <Label for="description">Description</Label>
                                            <Input id="description" v-model="form.description" placeholder="Operational notes for this template." />
                                            <InputError :message="form.errors.description" />
                                        </div>
                                    </div>
                                </section>

                                <section class="grid gap-4 rounded-xl border border-border bg-muted/10 p-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Source and policy notes</h3>
                                        <p class="text-sm text-muted-foreground">
                                            Keep the official source and the quick summary fields together for faster reviews.
                                        </p>
                                    </div>

                                    <div class="grid gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="source_url">Official source</Label>
                                            <Input id="source_url" v-model="form.source_url" placeholder="https://..." />
                                            <InputError :message="form.errors.source_url" />
                                        </div>

                                        <div class="grid gap-1.5">
                                            <Label for="source_checked_at">Reviewed on</Label>
                                            <Input id="source_checked_at" v-model="form.source_checked_at" type="date" />
                                            <InputError :message="form.errors.source_checked_at" />
                                        </div>

                                        <div class="grid gap-1.5">
                                            <Label for="processing_time_summary">Processing summary</Label>
                                            <Input
                                                id="processing_time_summary"
                                                v-model="form.processing_time_summary"
                                                placeholder="Check official portal for timing."
                                            />
                                            <InputError :message="form.errors.processing_time_summary" />
                                        </div>

                                        <div class="grid gap-1.5">
                                            <Label for="fee_summary">Fee summary</Label>
                                            <Input id="fee_summary" v-model="form.fee_summary" placeholder="Optional fee note" />
                                            <InputError :message="form.errors.fee_summary" />
                                        </div>

                                        <div class="grid gap-1.5">
                                            <Label for="stay_summary">Stay summary</Label>
                                            <Input id="stay_summary" v-model="form.stay_summary" placeholder="Up to 90 days in 180 days" />
                                            <InputError :message="form.errors.stay_summary" />
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <section class="grid gap-3">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <Label>Requirement rows</Label>
                                    <Button type="button" variant="outline" size="sm" @click="addRequirement">Add row</Button>
                                </div>

                                <div class="space-y-2.5">
                                    <Collapsible
                                        v-for="(requirement, index) in form.requirements"
                                        :key="index"
                                        :open="expandedRequirementRows[index] ?? index === 0"
                                        @update:open="(open) => setRequirementRowOpen(index, open)"
                                    >
                                        <div class="rounded-lg border border-border bg-background">
                                            <CollapsibleTrigger class="flex w-full items-start justify-between gap-3 px-3 py-3 text-left">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="text-sm font-medium text-foreground">Row {{ index + 1 }}</p>
                                                        <span
                                                            v-if="requirement.category"
                                                            class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                                                        >
                                                            {{ requirement.category }}
                                                        </span>
                                                        <span
                                                            class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                                            :class="
                                                                requirement.is_required
                                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200'
                                                                    : 'bg-muted text-muted-foreground'
                                                            "
                                                        >
                                                            {{ requirement.is_required ? 'Required' : 'Optional' }}
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 line-clamp-1 text-sm text-muted-foreground">
                                                        {{ requirementRowPreview(requirement) }}
                                                    </p>
                                                </div>

                                                <ChevronDown
                                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground transition-transform"
                                                    :class="{ 'rotate-180': expandedRequirementRows[index] ?? index === 0 }"
                                                />
                                            </CollapsibleTrigger>

                                            <CollapsibleContent>
                                                <div class="border-t border-border px-3 py-3">
                                                    <div class="flex flex-col gap-3">
                                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                            <div class="grid gap-1.5 sm:w-[220px] sm:min-w-[220px]">
                                                                <Label :for="`requirement_category_${index}`">Category</Label>
                                                                <Input
                                                                    :id="`requirement_category_${index}`"
                                                                    v-model="requirement.category"
                                                                    placeholder="Identity"
                                                                />
                                                                <InputError :message="form.errors[`requirements.${index}.category`]" />
                                                            </div>

                                                            <div class="flex flex-wrap items-center gap-3 sm:pt-6">
                                                                <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                                                    <input
                                                                        v-model="requirement.is_required"
                                                                        type="checkbox"
                                                                        class="size-4 rounded border-border"
                                                                    />
                                                                    Required
                                                                </label>
                                                                <Button type="button" variant="ghost" size="sm" @click="removeRequirement(index)"
                                                                    >Remove</Button
                                                                >
                                                            </div>
                                                        </div>

                                                        <div class="grid gap-3">
                                                            <div class="grid gap-1.5">
                                                                <Label :for="`requirement_label_${index}`">Requirement</Label>
                                                                <textarea
                                                                    :id="`requirement_label_${index}`"
                                                                    v-model="requirement.label"
                                                                    rows="2"
                                                                    class="min-h-16 rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                                                    placeholder="Passport biodata page and all stamped visa pages"
                                                                />
                                                                <InputError :message="form.errors[`requirements.${index}.label`]" />
                                                            </div>

                                                            <div class="grid gap-1.5">
                                                                <Label :for="`requirement_help_text_${index}`">Guidance</Label>
                                                                <textarea
                                                                    :id="`requirement_help_text_${index}`"
                                                                    v-model="requirement.help_text"
                                                                    rows="2"
                                                                    class="min-h-20 rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                                                    placeholder="Explain what to verify, common issues, or what the team should ask the client to provide."
                                                                />
                                                                <InputError :message="form.errors[`requirements.${index}.help_text`]" />
                                                            </div>
                                                        </div>

                                                        <InputError :message="form.errors[`requirements.${index}.is_required`]" />
                                                    </div>
                                                </div>
                                            </CollapsibleContent>
                                        </div>
                                    </Collapsible>
                                </div>

                                <InputError :message="form.errors.requirements" />
                            </section>

                            <DialogFooter class="border-t border-border pt-4">
                                <Button type="button" variant="ghost" @click="isDialogOpen = false">Cancel</Button>
                                <Button :disabled="form.processing">Save template</Button>
                            </DialogFooter>
                        </form>
                    </DialogScrollContent>
                </Dialog>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
