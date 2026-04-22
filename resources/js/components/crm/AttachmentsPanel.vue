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

const props = withDefaults(
    defineProps<{
        title: string;
        routeName: string;
        routeParameter: number;
        attachments: AttachmentItem[];
        embedded?: boolean;
    }>(),
    {
        embedded: false,
    },
);

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
            const input = document.getElementById('attachment-input') as HTMLInputElement | null;
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
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '';
</script>

<template>
    <section :class="props.embedded ? '' : 'app-panel p-3.5'">
        <div v-if="!props.embedded">
            <h2 class="text-base font-semibold text-slate-950 dark:text-slate-50">{{ title }}</h2>
        </div>

        <div :class="props.embedded ? 'space-y-3' : 'mt-3 space-y-3'">
            <form class="space-y-2.5" @submit.prevent="submit">
                <div class="grid gap-1.5">
                    <Label for="attachment-input">Upload attachment</Label>
                    <input
                        id="attachment-input"
                        type="file"
                        class="block w-full rounded-md border border-input bg-background px-2.5 py-2 text-sm"
                        @change="onFileChange"
                    />
                    <p class="text-xs text-muted-foreground">Supported: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX up to 10 MB.</p>
                    <InputError :message="form.errors.attachment" />
                </div>

                <Button :disabled="form.processing || !form.attachment">Upload file</Button>
            </form>

            <div class="space-y-2.5">
                <div v-if="attachments.length === 0" class="text-sm text-muted-foreground">No attachments yet.</div>
                <div v-else class="space-y-2.5">
                    <div
                        v-for="attachment in attachments"
                        :key="attachment.id"
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0"
                    >
                        <div>
                            <p class="font-medium">{{ attachment.original_name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ attachment.uploaded_by || 'Unknown uploader' }} • {{ attachment.size }} • {{ formatDate(attachment.created_at) }}
                            </p>
                        </div>
                        <Button as-child variant="outline" size="sm">
                            <a :href="attachment.download_url">Download</a>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
