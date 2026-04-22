<script setup lang="ts">
interface Props {
    class?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div :class="props.class" class="flex min-w-0 items-center">
        <span
            class="hidden size-9 shrink-0 items-center justify-center rounded-2xl bg-sidebar-accent text-sm font-semibold tracking-tight text-sidebar-foreground group-data-[collapsible=icon]:flex"
        >
            G
        </span>
        <span class="truncate text-[18px] font-semibold tracking-[-0.04em] text-sidebar-foreground group-data-[collapsible=icon]:hidden">
            Gereg
        </span>
    </div>
</template>
