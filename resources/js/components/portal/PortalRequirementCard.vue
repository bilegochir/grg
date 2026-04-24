<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

interface RequirementAttachment {
    id: number;
    original_name: string;
    mime_type: null | string;
    size: string;
    uploaded_by: null | string;
    created_at: null | string;
    download_url: string;
}

interface PortalRequirement {
    id: number;
    category: null | string;
    label: string;
    help_text: null | string;
    is_required: boolean;
    status: string;
    status_label: string;
    due_at: null | string;
    is_completed: boolean;
    attachments: RequirementAttachment[];
}

const props = defineProps<{
    portalToken: string;
    visaCaseId: number;
    requirement: PortalRequirement;
    contextLabel?: null | string;
}>();

const form = useForm<{
    attachment: File | null;
}>({
    attachment: null,
});

const submit = () => {
    form.post(route('portal.requirements.attachments.store', [props.portalToken, props.visaCaseId, props.requirement.id]), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            const input = document.getElementById(`portal-requirement-attachment-${props.requirement.id}`) as HTMLInputElement | null;

            if (input) {
                input.value = '';
            }
        },
    });
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.attachment = target.files?.[0] ?? null;
};

const formatDate = (value: null | string) =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'No due date';

const statusClasses = (status: string) =>
    ({
        pending: 'bg-neutral-100 text-neutral-700',
        requested: 'bg-amber-100 text-amber-800',
        received: 'bg-sky-100 text-sky-800',
        verified: 'bg-emerald-100 text-emerald-800',
        waived: 'bg-violet-100 text-violet-800',
    })[status] ?? 'bg-neutral-100 text-neutral-700';
</script>

<template>
    <article class="rounded-2xl border border-slate-200 bg-white p-4">
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p v-if="contextLabel" class="text-sm font-medium text-slate-500">{{ contextLabel }}</p>
                    <h4 class="mt-1 text-lg font-semibold text-slate-950">{{ requirement.label }}</h4>
                    <p v-if="requirement.help_text" class="mt-2 text-sm leading-6 text-slate-600">{{ requirement.help_text }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span v-if="requirement.category" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                        {{ requirement.category }}
                    </span>
                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusClasses(requirement.status)">
                        {{ requirement.status_label }}
                    </span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Status</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ requirement.is_completed ? 'Completed' : 'Still needed' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Due date</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ formatDate(requirement.due_at) }}</p>
                </div>
            </div>

            <form class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4" @submit.prevent="submit">
                <div class="grid gap-3">
                    <div class="grid gap-2">
                        <Label :for="`portal-requirement-attachment-${requirement.id}`">Choose a file to upload</Label>
                        <input
                            :id="`portal-requirement-attachment-${requirement.id}`"
                            type="file"
                            class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700"
                            @change="onFileChange"
                        />
                        <p class="text-xs text-slate-500">Accepted: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX up to 10 MB.</p>
                        <InputError :message="form.errors.attachment" />
                    </div>

                    <Button :disabled="form.processing || !form.attachment" class="h-11 w-full text-base sm:w-auto">Upload this document</Button>
                </div>
            </form>

            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Files already uploaded</p>
                <div v-if="requirement.attachments.length === 0" class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    No files uploaded yet.
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="attachment in requirement.attachments"
                        :key="attachment.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ attachment.original_name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ attachment.uploaded_by || 'Client portal' }} • {{ attachment.size }} • {{ formatDate(attachment.created_at) }}
                            </p>
                        </div>
                        <Button as-child variant="outline" size="sm">
                            <a :href="attachment.download_url">Download</a>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
