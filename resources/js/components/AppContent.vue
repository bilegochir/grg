<script setup lang="ts">
import { SidebarInset } from '@/components/ui/sidebar';
import { computed } from 'vue';

interface Props {
    variant?: 'header' | 'sidebar';
    class?: string;
}

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" class="min-h-svh bg-transparent" :class="className">
        <div class="app-shell-surface px-2 py-2 md:px-3 md:py-3">
            <div class="mx-auto flex min-h-[calc(100svh-1rem)] w-full max-w-[1600px] flex-col rounded-2xl border border-border bg-card shadow-[0_1px_2px_rgba(15,23,42,0.04)] md:min-h-[calc(100svh-1.5rem)]">
                <slot />
            </div>
        </div>
    </SidebarInset>
    <main v-else class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl" :class="className">
        <slot />
    </main>
</template>
