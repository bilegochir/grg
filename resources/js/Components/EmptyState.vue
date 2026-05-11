<script setup>
import AppIcon from '@/Components/AppIcon.vue';

defineProps({
    icon: {
        type: String,
        default: 'inbox',
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-neutral text-brand-primary">
            <AppIcon :name="icon" :size="24" />
        </div>
        <h3 class="mt-5 text-xl font-semibold tracking-tight text-brand-text">
            {{ title }}
        </h3>
        <p class="mt-2 max-w-md text-sm leading-6 text-brand-muted">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-6">
            <slot name="action" />
        </div>
    </div>
</template>
