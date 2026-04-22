<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="grid min-h-svh bg-background lg:grid-cols-[420px_minmax(0,1fr)]">
        <div class="hidden border-r border-border bg-muted/30 px-10 py-12 lg:flex lg:flex-col">
            <Link :href="route('home')" class="text-sm font-semibold tracking-tight text-foreground">
                {{ name }}
            </Link>
            <div class="mt-16 space-y-3">
                <h2 class="text-2xl font-semibold tracking-tight">Simple CRM for small visa teams</h2>
            </div>
        </div>
        <div class="flex items-center justify-center px-6 py-10">
            <div class="w-full max-w-sm rounded-xl border border-border bg-card p-6">
                <div class="flex flex-col space-y-2">
                    <h1 class="text-xl font-semibold tracking-tight" v-if="title">{{ title }}</h1>
                </div>
                <div class="mt-6">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
