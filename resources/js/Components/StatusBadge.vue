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
    slate: 'bg-slate-50 text-slate-600 border-slate-200/50',
    blue: 'bg-blue-50 text-blue-600 border-blue-100/50',
    amber: 'bg-amber-50 text-amber-700 border-amber-100/50',
    violet: 'bg-purple-50 text-purple-600 border-purple-100/50',
    emerald: 'bg-emerald-50 text-emerald-700 border-emerald-100/50',
    rose: 'bg-red-50 text-red-700 border-red-100/50',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[12px] font-medium"
        :class="colorClasses[props.color] ?? colorClasses.slate"
    >
        <span class="h-1 w-1 rounded-full bg-current"></span>
        {{ label }}
    </span>
</template>
