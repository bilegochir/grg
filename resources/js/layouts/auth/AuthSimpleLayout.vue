<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('home')" class="text-base font-semibold tracking-tight text-foreground"> Gereg </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-semibold">{{ title }}</h1>
                    </div>
                </div>
                <div class="rounded-xl border border-border bg-card p-6">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
