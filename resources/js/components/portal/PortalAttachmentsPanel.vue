<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

interface AttachmentItem {
    id: number;
    original_name: string;
    mime_type: null | string;
    size: string;
    uploaded_by: null | string;
    created_at: null | string;
    download_url: string;
}

const props = defineProps<{
    title: string;
    routeName: string;
    routeParameter: number | string | Array<number | string>;
    attachments: AttachmentItem[];
    inputId: string;
    embedded?: boolean;
}>();

const form = useForm<{
    attachment: File | null;
}>({
    attachment: null,
});

const submit = () => {
    form.post(route(props.routeName, props.routeParameter), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();

            const input = document.getElementById(props.inputId) as HTMLInputElement | null;

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
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Not set';
</script>

<template>
    <section :class="embedded ? '' : 'rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_18px_60px_-36px_rgba(15,23,42,0.35)] backdrop-blur lg:p-6'">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Additional files</p>
                <h2 class="mt-2 text-lg font-semibold tracking-tight text-slate-950">{{ title }}</h2>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                {{ attachments.length }} file{{ attachments.length === 1 ? '' : 's' }}
            </span>
        </div>

        <form class="mt-5 rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50/80 p-4" @submit.prevent="submit">
            <div class="flex flex-col gap-3">
                <div class="grid gap-1.5">
                    <Label :for="inputId">Upload document</Label>
                    <input
                        :id="inputId"
                        type="file"
                        class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"
                        @change="onFileChange"
                    />
                    <p class="text-xs text-slate-500">PDF, JPG, PNG, DOC, DOCX, XLS, XLSX up to 10 MB.</p>
                    <InputError :message="form.errors.attachment" />
                </div>

                <Button :disabled="form.processing || !form.attachment" class="w-full sm:w-auto">Upload file</Button>
            </div>
        </form>

        <div class="mt-5 space-y-2">
            <div v-if="attachments.length === 0" class="rounded-[1.5rem] bg-slate-50 px-4 py-3 text-sm text-slate-500">
                No additional files uploaded yet.
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="attachment in attachments"
                    :key="attachment.id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-slate-200 bg-white px-4 py-3"
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
    </section>
</template>
