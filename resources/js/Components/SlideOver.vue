<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import { computed, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    width: {
        type: String,
        default: 'default',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close']);
let previousBodyOverflow = '';
let bodyScrollLocked = false;

const lockBodyScroll = () => {
    if (typeof document === 'undefined' || bodyScrollLocked) {
        return;
    }

    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    bodyScrollLocked = true;
};

const unlockBodyScroll = () => {
    if (typeof document === 'undefined' || !bodyScrollLocked) {
        return;
    }

    document.body.style.overflow = previousBodyOverflow;
    bodyScrollLocked = false;
};

watch(
    () => props.show,
    (show) => {
        if (show) {
            lockBodyScroll();
            return;
        }

        unlockBodyScroll();
    },
    { immediate: true },
);

const widthClass = computed(() => ({
    default: 'max-w-[480px]',
    wide: 'max-w-[640px]',
}[props.width] ?? 'max-w-[480px]'));

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

onBeforeUnmount(() => {
    unlockBodyScroll();
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="close"></div>
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="show"
                    class="absolute inset-y-0 right-0 flex w-full"
                    :class="widthClass"
                >
                    <div class="flex h-full w-full flex-col overflow-hidden rounded-l-xl bg-white shadow-modal">
                        <template v-if="title">
                            <div class="sticky top-0 z-10 border-b border-slate-100 bg-white px-6 py-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ title }}</h2>
                                        <p v-if="description" class="mt-1 text-[13px] text-slate-500 leading-relaxed">{{ description }}</p>
                                    </div>
                                    <button 
                                        v-if="closeable" 
                                        class="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" 
                                        @click="close"
                                    >
                                        <AppIcon name="close" :size="18" />
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 overflow-y-auto px-6 pb-6">
                                <slot />
                            </div>
                            <div v-if="$slots.footer" class="sticky bottom-0 z-10 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                                <slot name="footer" />
                            </div>
                        </template>
                        <template v-else>
                            <slot />
                        </template>
                    </div>
                </aside>
            </Transition>
        </div>
    </Transition>
</template>
