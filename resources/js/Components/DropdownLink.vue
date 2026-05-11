<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-3 py-1.5 text-start text-[12px] font-medium leading-5 text-slate-600 transition duration-150 ease-in-out hover:bg-slate-100/80 hover:text-slate-900 focus:bg-slate-100 focus:text-slate-900 focus:outline-none"
    >
        <slot />
    </Link>
</template>
