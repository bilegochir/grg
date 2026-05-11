<script setup>
defineProps({
    class: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="animate-pulse rounded-md bg-slate-200/80" :class="class"></div>
</template>
