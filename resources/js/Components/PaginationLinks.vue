<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <Component
            :is="link.url ? Link : 'span'"
            v-for="link in links"
            :key="`${link.label}-${link.url}`"
            :href="link.url"
            class="rounded-md px-3.5 py-2 text-sm transition"
            :class="[
                link.active
                    ? 'bg-brand-primary text-white'
                    : link.url
                      ? 'bg-white text-brand-text ring-1 ring-brand-border hover:bg-brand-neutral'
                      : 'bg-slate-100 text-brand-placeholder',
            ]"
            v-html="link.label"
        />
    </div>
</template>
