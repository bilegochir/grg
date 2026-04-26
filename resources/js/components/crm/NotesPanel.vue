<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

interface NoteItem {
    id: number;
    body: string;
    author_name: null | string;
    created_at: null | string;
}

const props = withDefaults(
    defineProps<{
        title: string;
        routeName: string;
        routeParameter: number;
        notes: NoteItem[];
        embedded?: boolean;
        showList?: boolean;
    }>(),
    {
        embedded: false,
        showList: true,
    },
);

const form = useForm({
    body: '',
});

const submit = () => {
    form.post(route(props.routeName, props.routeParameter), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
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
                    <Label for="note-body">New note</Label>
                    <textarea
                        id="note-body"
                        v-model="form.body"
                        rows="3"
                        class="min-h-20 rounded-md border border-input bg-background px-2.5 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                        placeholder="Add internal context, client updates, or follow-up details"
                    />
                    <InputError :message="form.errors.body" />
                </div>

                <Button :disabled="form.processing">Save note</Button>
            </form>

            <div v-if="props.showList" class="space-y-2.5">
                <div v-if="notes.length === 0" class="text-sm text-muted-foreground">No notes yet.</div>
                <div v-else class="space-y-2.5">
                    <div v-for="note in notes" :key="note.id" class="border-b border-border/70 pb-2.5 last:border-b-0 last:pb-0">
                        <p class="whitespace-pre-wrap text-sm">{{ note.body }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ note.author_name || 'Unknown author' }} • {{ formatDate(note.created_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
