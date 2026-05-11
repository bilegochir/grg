<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message" aria-live="polite">
        <p class="mt-2 flex items-center gap-2 text-xs text-brand-danger">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-red-50 text-[10px] font-semibold text-brand-danger">!</span>
            {{ message }}
        </p>
    </div>
</template>
