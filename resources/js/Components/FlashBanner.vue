<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    success: {
        type: String,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
});

const visible = ref(false);
let hideTimeout = null;

const message = computed(() => props.success || props.error || null);
const isSuccess = computed(() => Boolean(props.success));

const clearHideTimeout = () => {
    if (hideTimeout) {
        clearTimeout(hideTimeout);
        hideTimeout = null;
    }
};

const dismiss = () => {
    visible.value = false;
    clearHideTimeout();
};

watch(
    message,
    (value) => {
        clearHideTimeout();

        if (!value) {
            visible.value = false;
            return;
        }

        visible.value = true;
        hideTimeout = setTimeout(() => {
            visible.value = false;
            hideTimeout = null;
        }, isSuccess.value ? 4000 : 6000);
    },
    { immediate: true },
);

onBeforeUnmount(() => clearHideTimeout());
</script>

<template>
    <div v-if="visible && message" class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-end px-4">
        <div
            class="pointer-events-auto w-full max-w-[320px] rounded-xl bg-white px-4 py-3 text-sm text-brand-text shadow-dropdown"
            :class="isSuccess ? 'border-l-4 border-brand-success' : 'border-l-4 border-brand-danger'"
            role="status"
            aria-live="polite"
        >
            <div class="flex items-start gap-3">
                <span
                    class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full"
                    :class="isSuccess ? 'bg-green-50 text-brand-success' : 'bg-red-50 text-brand-danger'"
                >
                    <AppIcon :name="isSuccess ? 'check' : 'alert'" :size="16" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ isSuccess ? 'Saved' : 'Heads up' }}</p>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">{{ message }}</p>
                </div>
                <button
                    type="button"
                    class="rounded-md p-1 text-brand-muted transition hover:bg-slate-100 hover:text-brand-text"
                    @click="dismiss"
                >
                    <AppIcon name="close" :size="14" />
                </button>
            </div>
        </div>
    </div>
</template>
