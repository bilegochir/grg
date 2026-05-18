<script setup>
defineProps({
    title: {
        type: String,
        default: null,
    },
    subtitle: {
        type: String,
        default: null,
    },
    padded: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <section class="rounded-2xl bg-white shadow-[0_10px_24px_rgba(15,23,42,0.05)] ring-1 ring-slate-200/70">
        <header
            v-if="title || subtitle || $slots.action"
            class="flex items-start justify-between gap-4 border-b border-slate-100/90 px-6 py-4"
        >
            <div class="min-w-0">
                <h2 v-if="title" class="text-[15px] font-bold tracking-tight text-slate-900">
                    {{ title }}
                </h2>
                <p v-if="subtitle" class="mt-1 text-[13px] font-medium text-slate-500 leading-relaxed">
                    {{ subtitle }}
                </p>
            </div>
            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" />
            </div>
        </header>
        <div :class="padded ? 'px-6 py-6' : ''">
            <slot />
        </div>
    </section>
</template>
