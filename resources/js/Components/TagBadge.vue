<script setup>
const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    color: {
        type: String,
        default: 'slate',
    },
});

const colorClasses = {
    slate: 'bg-slate-100 text-slate-700',
    blue: 'bg-blue-50 text-blue-700',
    amber: 'bg-amber-50 text-amber-700',
    violet: 'bg-purple-50 text-purple-700',
    emerald: 'bg-green-50 text-green-700',
    rose: 'bg-red-50 text-red-700',
};
</script>

<template>
    <span
        class="inline-flex rounded-sm px-2.5 py-1 text-xs font-medium"
        :class="colorClasses[props.color] ?? colorClasses.slate"
    >
        {{ label }}
    </span>
</template>
