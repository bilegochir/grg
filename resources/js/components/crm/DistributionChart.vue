<script setup lang="ts">
import { computed } from 'vue';

interface ChartItem {
    color: string;
    label: string;
    value: number;
}

const props = withDefaults(
    defineProps<{
        emptyLabel?: string;
        items: ChartItem[];
    }>(),
    {
        emptyLabel: 'No data yet.',
    },
);

const total = computed(() => props.items.reduce((sum, item) => sum + item.value, 0));
const maxValue = computed(() => Math.max(...props.items.map((item) => item.value), 0));

const percentageFor = (value: number) => (total.value > 0 ? Math.round((value / total.value) * 100) : 0);
const widthFor = (value: number) => (maxValue.value > 0 ? `${(value / maxValue.value) * 100}%` : '0%');
</script>

<template>
    <div class="space-y-3">
        <div v-if="total === 0" class="rounded-lg bg-muted/70 px-4 py-4 text-sm text-muted-foreground">
            {{ emptyLabel }}
        </div>

        <div v-else class="space-y-3">
            <div v-for="item in items" :key="item.label" class="space-y-1.5">
                <div class="flex items-center justify-between gap-3 text-[13px]">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: item.color }"></span>
                        <span class="truncate font-medium text-foreground">{{ item.label }}</span>
                    </div>
                    <div class="flex shrink-0 items-center gap-2 text-muted-foreground">
                        <span>{{ item.value }}</span>
                        <span>{{ percentageFor(item.value) }}%</span>
                    </div>
                </div>

                <div class="h-1.5 rounded-full bg-muted">
                    <div class="h-1.5 rounded-full" :style="{ width: widthFor(item.value), backgroundColor: item.color }"></div>
                </div>
            </div>
        </div>
    </div>
</template>
