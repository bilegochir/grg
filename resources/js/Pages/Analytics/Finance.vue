<template>
  <AppLayout title="Finance Analytics">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Finance Analytics</h1>
            <p class="mt-2 text-gray-600">Revenue, invoicing, and payment analysis</p>
          </div>
          <button @click="exportData('csv')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <MetricCard title="Total Invoiced" :value="`$${formatCurrency(metrics.total_invoiced)}`" :change="+12.5" />
          <MetricCard title="Total Paid" :value="`$${formatCurrency(metrics.total_paid)}`" :change="+8.3" />
          <MetricCard title="Outstanding" :value="`$${formatCurrency(metrics.outstanding)}`" :change="+2.1" />
          <MetricCard title="Paid %" :value="`${metrics.paid_percentage}%`" :change="+3.2" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Invoice Aging</h2>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-3 bg-red-50 rounded">
                <span class="text-sm text-red-800 font-medium">Overdue</span>
                <span class="font-semibold text-red-600">{{ invoiceAging.overdue }}</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-yellow-50 rounded">
                <span class="text-sm text-yellow-800 font-medium">Due in 30 days</span>
                <span class="font-semibold text-yellow-600">{{ invoiceAging.due_30 }}</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-blue-50 rounded">
                <span class="text-sm text-blue-800 font-medium">Due in 60 days</span>
                <span class="font-semibold text-blue-600">{{ invoiceAging.due_60 }}</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-green-50 rounded">
                <span class="text-sm text-green-800 font-medium">Future invoices</span>
                <span class="font-semibold text-green-600">{{ invoiceAging.future }}</span>
              </div>
            </div>
          </div>

          <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Monthly Revenue Trend</h2>
            <div class="space-y-2">
              <div v-for="month in revenueByMonth" :key="month.month" class="flex items-center">
                <span class="w-20 text-xs text-gray-600">{{ month.month }}</span>
                <div class="flex-1 bg-gray-100 rounded h-6 relative" :style="{ width: 'auto' }">
                  <div class="bg-green-500 h-full rounded" :style="{ width: `${calculateWidth(month.revenue)}%` }"></div>
                  <span class="absolute inset-0 flex items-center px-2 text-xs font-semibold text-gray-900">
                    ${{ formatCurrency(month.revenue) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import MetricCard from '@/Components/Analytics/MetricCard.vue'

export default {
  components: {
    AppLayout,
    MetricCard
  },
  props: {
    metrics: Object,
    revenueByMonth: Array,
    invoiceAging: Object,
  },
  methods: {
    exportData(format) {
      window.location.href = `/api/analytics/export-report?type=finance&format=${format}`
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value).replace('$', '')
    },
    calculateWidth(value) {
      const max = Math.max(...this.revenueByMonth.map(m => m.revenue))
      return (value / max) * 100
    }
  }
}
</script>
