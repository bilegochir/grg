<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" :class="className" v-bind="$attrs">
        <path
            fill="currentColor"
            d="M24 6C14.1 6 7 13.2 7 24s7.1 18 17 18c7.6 0 13.6-3.8 16.5-10.1a1.8 1.8 0 0 0-1.6-2.5h-8.5a1.8 1.8 0 0 0-1.6 1
            c-1.3 2.2-3.7 3.5-6.8 3.5-5.7 0-9.6-4-9.6-9.9s4-10 9.8-10c3 0 5.5 1.2 7 3.4.3.5.9.8 1.5.8h8.6a1.8 1.8 0 0 0 1.6-2.6
            C37.8 9.8 31.7 6 24 6Zm0 15.5a2 2 0 0 0 0 4h8v1.2c0 1.1.9 2 2 2h5.2a2 2 0 0 0 2-2V20a2 2 0 0 0-2-2H24a2 2 0 0 0 0 4h11.2v3.5H24Z"
        />
    </svg>
</template>
