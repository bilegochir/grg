<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    invoices: Array,
});
</script>

<template>
    <Head title="Invoices" />

    <AuthenticatedLayout>
        <template #header>
            <div class="ui-page-header">
                <div class="max-w-3xl">
                    <p class="ui-kicker">Invoices</p>
                    <h1 class="ui-header-title text-[28px] tracking-tight">Billing & Revenue</h1>
                    <p class="ui-header-copy text-[14px] leading-relaxed">
                        Track what has been issued, what is due, and what still needs follow-up across your portfolio.
                    </p>
                </div>
            </div>
        </template>

        <div class="ui-page-body">
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div v-if="invoices.length" class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Invoice</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Applicant & Case</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Balance Due</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Status</th>
                                <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ invoice.number }}</p>
                                    <p v-if="invoice.due_at" class="text-[11px] text-slate-500">Due {{ invoice.due_at }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ invoice.applicant_name }}</p>
                                    <p class="text-[11px] text-slate-500">{{ invoice.case_reference }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ invoice.currency }} {{ invoice.balance_due }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span 
                                        class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700': invoice.status === 'paid',
                                            'bg-amber-50 text-amber-700': invoice.status === 'partially_paid',
                                            'bg-slate-100 text-slate-500': invoice.status === 'draft',
                                            'bg-blue-50 text-blue-700': invoice.status === 'sent',
                                            'bg-rose-50 text-rose-700': invoice.status === 'overdue',
                                        }"
                                    >
                                        {{ invoice.status.replaceAll('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('cases.show', invoice.case_id)" class="ui-button-ghost !h-8 px-3 text-[12px]">
                                        View Case
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState
                    v-else
                    icon="inbox"
                    title="No invoices found"
                    description="When you issue invoices from cases, they will appear in this centralized view."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
