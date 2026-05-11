<script setup>
import AppIcon from '@/Components/AppIcon.vue';

defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <button class="ui-button-primary">
        <span
            v-if="loading"
            class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
        ></span>
        <AppIcon v-else-if="icon" :name="icon" :size="16" />
        <span>
            <slot />
        </span>
    </button>
</template>
