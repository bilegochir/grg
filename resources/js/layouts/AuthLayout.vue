<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <AuthLayout :title="title">
        <slot />
    </AuthLayout>
</template>
