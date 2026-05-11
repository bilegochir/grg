<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    appointments: Array,
});
</script>

<template>
    <Head title="Appointments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Appointments</p>
                    <h1 class="ui-header-title text-[28px] tracking-tight">Schedule & Touchpoints</h1>
                    <p class="ui-header-copy text-[14px] leading-relaxed">
                        Keep consultations, embassy touchpoints, and follow-ups visible across the team.
                    </p>
                </div>
            </div>
        </template>

        <div class="ui-page-body">
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div v-if="appointments.length" class="divide-y divide-slate-100">
                    <div
                        v-for="appointment in appointments"
                        :key="appointment.id"
                        class="p-4 hover:bg-slate-50/30 transition-colors group"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-3">
                                    <p class="text-[15px] font-bold text-slate-900 leading-tight group-hover:text-black transition-colors">
                                        {{ appointment.title }}
                                    </p>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                        {{ appointment.status }}
                                    </span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1.5 items-center text-[12px] text-slate-400">
                                    <span class="font-bold text-slate-600">{{ appointment.applicant_name }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                    <span class="font-medium text-slate-500">{{ appointment.case_reference }}</span>
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        <AppIcon name="clock" :size="12" class="text-slate-300" />
                                        {{ appointment.starts_at }}
                                    </span>
                                    <span v-if="appointment.location" class="flex items-center gap-1.5 text-slate-500 font-medium">
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        <AppIcon name="map" :size="12" class="text-slate-300" />
                                        {{ appointment.location }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <Link :href="route('cases.show', appointment.case_id)" class="ui-button-secondary !h-8 px-3 text-[12px]">
                                    Open case
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <EmptyState
                    v-else
                    icon="clock"
                    title="No appointments scheduled"
                    description="Schedule your first consultation or embassy visit from an active case."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
