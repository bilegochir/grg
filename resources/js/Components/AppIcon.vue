<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    size: {
        type: [Number, String],
        default: 20,
    },
    class: {
        type: String,
        default: '',
    },
});

const icons = {
    dashboard: [
        { type: 'rect', x: '3', y: '3', width: '7', height: '7' },
        { type: 'rect', x: '14', y: '3', width: '7', height: '7' },
        { type: 'rect', x: '14', y: '14', width: '7', height: '7' },
        { type: 'rect', x: '3', y: '14', width: '7', height: '7' },
    ],
    users: [
        { type: 'path', d: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2' },
        { type: 'circle', cx: '9', cy: '7', r: '4' },
        { type: 'path', d: 'M22 21v-2a4 4 0 0 0-3-3.87' },
        { type: 'path', d: 'M16 3.13a4 4 0 0 1 0 7.75' },
    ],
    leads: [
        { type: 'path', d: 'M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z' },
        { type: 'circle', cx: '12', cy: '10', r: '3' },
    ],
    applicants: [
        { type: 'path', d: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2' },
        { type: 'circle', cx: '9', cy: '7', r: '4' },
    ],
    search: [
        { type: 'circle', cx: '11', cy: '11', r: '8' },
        { type: 'path', d: 'm21 21-4.3-4.3' },
    ],
    bell: [
        { type: 'path', d: 'M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9' },
        { type: 'path', d: 'M10.3 21a1.94 1.94 0 0 0 3.4 0' },
    ],
    settings: [
        { type: 'circle', cx: '12', cy: '12', r: '3' },
        { type: 'path', d: 'M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 1-2 0 1.65 1.65 0 0 0-1-.6 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-.6-1 1.65 1.65 0 0 1 0-2 1.65 1.65 0 0 0 .6-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33 1.65 1.65 0 0 0 1-.6 1.65 1.65 0 0 1 2 0 1.65 1.65 0 0 0 1 .6 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82 1.65 1.65 0 0 0 .6 1 1.65 1.65 0 0 1 0 2 1.65 1.65 0 0 0-.6 1z' },
    ],
    chevronLeft: [{ type: 'path', d: 'm15 18-6-6 6-6' }],
    chevronRight: [{ type: 'path', d: 'm9 18 6-6-6-6' }],
    chevronDown: [{ type: 'path', d: 'm6 9 6 6 6-6' }],
    plus: [
        { type: 'line', x1: '12', y1: '5', x2: '12', y2: '19' },
        { type: 'line', x1: '5', y1: '12', x2: '19', y2: '12' },
    ],
    arrowRight: [
        { type: 'path', d: 'M5 12h14' },
        { type: 'path', d: 'm12 5 7 7-7 7' },
    ],
    note: [
        { type: 'path', d: 'M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z' },
        { type: 'polyline', points: '14 2 14 8 20 8' },
    ],
    sparkle: [
        { type: 'path', d: 'm12 3 1.912 5.813a2 2 0 0 0 1.275 1.275L21 12l-5.813 1.912a2 2 0 0 0-1.275 1.275L12 21l-1.912-5.813a2 2 0 0 0-1.275-1.275L3 12l5.813-1.912a2 2 0 0 0 1.275-1.275L12 3Z' },
    ],
    menu: [
        { type: 'line', x1: '4', y1: '12', x2: '20', y2: '12' },
        { type: 'line', x1: '4', y1: '6', x2: '20', y2: '6' },
        { type: 'line', x1: '4', y1: '18', x2: '20', y2: '18' },
    ],
    close: [
        { type: 'path', d: 'M18 6 6 18' },
        { type: 'path', d: 'm6 6 12 12' },
    ],
    inbox: [
        { type: 'polyline', points: '22 12 16 12 14 15 10 15 8 12 2 12' },
        { type: 'path', d: 'M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z' },
    ],
    clock: [
        { type: 'circle', cx: '12', cy: '12', r: '10' },
        { type: 'polyline', points: '12 6 12 12 16 14' },
    ],
    check: [{ type: 'polyline', points: '20 6 9 17 4 12' }],
    alert: [
        { type: 'path', d: 'm21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z' },
        { type: 'line', x1: '12', y1: '9', x2: '12', y2: '13' },
        { type: 'line', x1: '12', y1: '17', x2: '12.01', y2: '17' },
    ],
    info: [
        { type: 'circle', cx: '12', cy: '12', r: '10' },
        { type: 'line', x1: '12', y1: '16', x2: '12', y2: '12' },
        { type: 'line', x1: '12', y1: '8', x2: '12.01', y2: '8' },
    ],
    map: [
        { type: 'polygon', points: '1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6' },
        { type: 'line', x1: '8', y1: '2', x2: '8', y2: '18' },
        { type: 'line', x1: '16', y1: '6', x2: '16', y2: '22' },
    ],
    tasks: [
        { type: 'path', d: 'm9 11 3 3L22 4' },
        { type: 'path', d: 'M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11' },
    ],
    document: [
        { type: 'path', d: 'M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z' },
        { type: 'polyline', points: '14 2 14 8 20 8' },
        { type: 'line', x1: '8', y1: '13', x2: '16', y2: '13' },
        { type: 'line', x1: '8', y1: '17', x2: '16', y2: '17' },
    ],
    shield: [{ type: 'path', d: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z' }],
    mail: [
        { type: 'rect', width: '20', height: '16', x: '2', y: '4', rx: '2' },
        { type: 'path', d: 'm22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7' },
    ],
    email: [
        { type: 'rect', width: '20', height: '16', x: '2', y: '4', rx: '2' },
        { type: 'path', d: 'm22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7' },
    ],
    phone: [{ type: 'path', d: 'M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z' }],
};

const elements = computed(() => icons[props.name] ?? []);
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        :class="props.class"
        aria-hidden="true"
    >
        <template v-for="(el, index) in elements" :key="index">
            <component :is="el.type" v-bind="el" />
        </template>
    </svg>
</template>
