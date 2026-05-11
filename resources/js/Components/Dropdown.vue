<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    placement: {
        type: String,
        default: 'bottom',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: 'bg-white py-1',
    },
});

const emit = defineEmits(['open-change']);

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        48: 'w-48',
        56: 'w-56',
        60: 'w-60',
        64: 'w-64',
        80: 'w-80',
        96: 'w-96',
    }[props.width.toString()] || 'w-48';
});

const alignmentClasses = computed(() => {
    if (props.placement === 'right') {
        return 'left-full top-0 ml-3 origin-left';
    }

    if (props.placement === 'left') {
        return 'right-full top-0 mr-3 origin-right';
    }

    if (props.placement === 'top') {
        if (props.align === 'left') {
            return 'bottom-full start-0 origin-bottom-left';
        }

        if (props.align === 'right') {
            return 'bottom-full end-0 origin-bottom-right';
        }

        return 'bottom-full origin-bottom';
    }

    if (props.align === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (props.align === 'right') {
        return 'ltr:origin-top-right rtl:origin-top-left end-0';
    } else {
        return 'origin-top';
    }
});

const placementClasses = computed(() => {
    if (props.placement === 'bottom') {
        return 'mt-2';
    }

    if (props.placement === 'top') {
        return 'mb-2';
    }

    return '';
});

const open = ref(false);

watch(open, (value) => emit('open-change', value));
</script>

<template>
    <div class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="open = false"
        ></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute z-50 rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses, placementClasses]"
                @click="open = false"
            >
                <div
                    class="rounded-lg border border-slate-200 bg-white shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1),0_20px_25px_-5px_rgba(0,0,0,0.04)] ring-1 ring-black/5"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
