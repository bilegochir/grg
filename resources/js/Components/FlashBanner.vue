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
const tone = computed(() => isSuccess.value
    ? {
        title: 'Saved',
        icon: 'check',
        shell: 'border-brand-success/20 bg-white',
        accent: 'bg-brand-success',
        iconWrap: 'bg-green-50 text-brand-success',
    }
    : {
        title: 'Heads up',
        icon: 'alert',
        shell: 'border-brand-danger/20 bg-white',
        accent: 'bg-brand-danger',
        iconWrap: 'bg-red-50 text-brand-danger',
    });

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
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div v-if="visible && message" class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4 sm:justify-end">
            <div
                class="pointer-events-auto relative w-full max-w-[420px] overflow-hidden rounded-2xl border px-4 py-3.5 text-sm text-brand-text shadow-dropdown backdrop-blur"
                :class="tone.shell"
                :role="isSuccess ? 'status' : 'alert'"
                :aria-live="isSuccess ? 'polite' : 'assertive'"
            >
                <div class="absolute inset-x-0 top-0 h-1" :class="tone.accent"></div>

                <div class="flex items-start gap-3">
                    <span
                        class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                        :class="tone.iconWrap"
                    >
                        <AppIcon :name="tone.icon" :size="16" />
                    </span>

                    <div class="min-w-0 flex-1 pr-2">
                        <p class="text-[13px] font-semibold text-brand-text">{{ tone.title }}</p>
                        <p class="mt-1 text-[13px] leading-5 text-brand-muted">{{ message }}</p>
                    </div>

                    <button
                        type="button"
                        class="rounded-md p-1 text-brand-muted transition hover:bg-slate-100 hover:text-brand-text focus-visible:ring-2 focus-visible:ring-brand-primary/20"
                        aria-label="Dismiss notification"
                        @click="dismiss"
                    >
                        <AppIcon name="close" :size="14" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
