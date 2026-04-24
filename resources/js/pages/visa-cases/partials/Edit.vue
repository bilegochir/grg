<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogFooter, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface VisaCaseDetail {
    id: number;
    client_id: number;
    assigned_user_id: null | number;
    visa_type: string;
    destination_country: string;
    institution_name: null | string;
    status: string;
    submitted_at: null | string;
    decision_at: null | string;
}

const props = defineProps<{
    open: boolean;
    visaCase: VisaCaseDetail;
    clients: Array<{ id: number; full_name: string }>;
    users: Array<{ id: number; name: string }>;
    requirementCountries: string[];
    institutionRequirements: Record<string, boolean>;
    visaTypesByCountry: Record<string, string[]>;
    statusOptions: Array<{ value: string; label: string }>;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const institutionRequirementKey = (country: string, visaType: string) => `${country}::${visaType}`;

const selectClass =
    'flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15 disabled:cursor-not-allowed disabled:opacity-60';

const form = useForm({
    client_id: String(props.visaCase.client_id),
    assigned_user_id: props.visaCase.assigned_user_id ? String(props.visaCase.assigned_user_id) : '',
    visa_type: props.visaCase.visa_type,
    destination_country: props.visaCase.destination_country,
    institution_name: props.visaCase.institution_name ?? '',
    status: props.visaCase.status,
    submitted_at: props.visaCase.submitted_at ? props.visaCase.submitted_at.slice(0, 10) : '',
    decision_at: props.visaCase.decision_at ? props.visaCase.decision_at.slice(0, 10) : '',
});

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const availableVisaTypes = computed(() => props.visaTypesByCountry[form.destination_country] ?? []);

const formRequiresInstitutionName = computed(
    () => props.institutionRequirements[institutionRequirementKey(form.destination_country, form.visa_type)] ?? false,
);

const resetFormFromCase = () => {
    form.defaults({
        client_id: String(props.visaCase.client_id),
        assigned_user_id: props.visaCase.assigned_user_id ? String(props.visaCase.assigned_user_id) : '',
        visa_type: props.visaCase.visa_type,
        destination_country: props.visaCase.destination_country,
        institution_name: props.visaCase.institution_name ?? '',
        status: props.visaCase.status,
        submitted_at: props.visaCase.submitted_at ? props.visaCase.submitted_at.slice(0, 10) : '',
        decision_at: props.visaCase.decision_at ? props.visaCase.decision_at.slice(0, 10) : '',
    });

    form.reset();
    form.clearErrors();
};

watch(
    () => props.open,
    (open) => {
        if (open) {
            resetFormFromCase();
        }
    },
);

watch(
    () => form.destination_country,
    () => {
        if (availableVisaTypes.value.includes(form.visa_type)) {
            return;
        }

        form.visa_type = '';
    },
);

watch(
    () => form.visa_type,
    () => {
        if (!formRequiresInstitutionName.value) {
            form.institution_name = '';
        }
    },
);

const close = () => {
    dialogOpen.value = false;
};

const submit = () => {
    form.patch(route('visa-cases.update', props.visaCase.id), {
        preserveScroll: true,
        onSuccess: () => {
            close();
        },
    });
};
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogScrollContent class="max-w-4xl p-0">
            <DialogHeader class="border-b border-border px-6 py-4">
                <DialogTitle>Edit visa case</DialogTitle>
            </DialogHeader>

            <form class="grid gap-4 px-6 py-5" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-1.5 md:col-span-2">
                        <Label for="client_id">Client</Label>

                        <select id="client_id" v-model="form.client_id" :class="selectClass">
                            <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                                {{ client.full_name }}
                            </option>
                        </select>

                        <InputError :message="form.errors.client_id" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="assigned_user_id">Assigned agent</Label>

                        <select id="assigned_user_id" v-model="form.assigned_user_id" :class="selectClass">
                            <option value="">Unassigned</option>

                            <option v-for="user in users" :key="user.id" :value="String(user.id)">
                                {{ user.name }}
                            </option>
                        </select>

                        <InputError :message="form.errors.assigned_user_id" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="status">Status</Label>

                        <select id="status" v-model="form.status" :class="selectClass">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>

                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="destination_country">Destination</Label>

                        <select id="destination_country" v-model="form.destination_country" :class="selectClass">
                            <option v-for="country in requirementCountries" :key="country" :value="country">
                                {{ country }}
                            </option>
                        </select>

                        <InputError :message="form.errors.destination_country" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="visa_type">Visa type</Label>

                        <select
                            id="visa_type"
                            v-model="form.visa_type"
                            :disabled="form.destination_country === ''"
                            :class="selectClass"
                        >
                            <option value="">
                                {{ form.destination_country === '' ? 'Select country first' : 'Select a visa' }}
                            </option>

                            <option v-for="visaType in availableVisaTypes" :key="visaType" :value="visaType">
                                {{ visaType }}
                            </option>
                        </select>

                        <InputError :message="form.errors.visa_type" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="submitted_at">Submitted</Label>
                        <Input id="submitted_at" v-model="form.submitted_at" type="date" />
                        <InputError :message="form.errors.submitted_at" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="decision_at">Decision</Label>
                        <Input id="decision_at" v-model="form.decision_at" type="date" />
                        <InputError :message="form.errors.decision_at" />
                    </div>

                    <div v-if="formRequiresInstitutionName" class="grid gap-1.5 md:col-span-2">
                        <Label for="institution_name">University or college</Label>

                        <Input
                            id="institution_name"
                            v-model="form.institution_name"
                            placeholder="University of Melbourne"
                        />

                        <InputError :message="form.errors.institution_name" />
                    </div>
                </div>

                <DialogFooter class="border-t border-border pt-4">
                    <Button type="button" variant="ghost" @click="close">
                        Cancel
                    </Button>

                    <Button :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogScrollContent>
    </Dialog>
</template>
