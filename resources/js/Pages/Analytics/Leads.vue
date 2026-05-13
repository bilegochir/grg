<template>
  <AppLayout title="Lead Analytics">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Lead Analytics</h1>
            <p class="mt-2 text-gray-600">Lead conversion funnel and performance metrics</p>
          </div>
          <button @click="exportData('csv')" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <MetricCard title="Conversion Rate" :value="`${conversionRate.conversion_rate}%`" :change="+4.1" />
          <MetricCard title="Total Leads" :value="conversionRate.total_leads" :change="+15" />
          <MetricCard title="Converted" :value="conversionRate.converted" :change="+8" />
          <MetricCard title="Avg Days" :value="`${conversionRate.avg_days_to_convert}d`" :change="-2" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Conversion Funnel</h2>
            <div class="space-y-4">
              <div v-for="(stage, index) in funnel" :key="stage.status" class="relative">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-900">{{ stage.label }}</span>
                  <span class="text-sm text-gray-600">{{ stage.count }} leads</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3" :style="{ opacity: 1 - (index * 0.15) }">
                  <div class="bg-blue-600 h-3 rounded-full" :style="{ width: `${calculatePercentage(index)}%` }"></div>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                  {{ calculateDropoff(index) }}% from previous
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Key Metrics</h2>
            <div class="space-y-4">
              <div class="p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-600">Conversion Rate</p>
                <p class="text-2xl font-bold text-blue-600">{{ conversionRate.conversion_rate }}%</p>
                <p class="text-xs text-gray-500 mt-1">of total leads converted to cases</p>
              </div>
              <div class="p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-gray-600">Avg Time to Convert</p>
                <p class="text-2xl font-bold text-green-600">{{ conversionRate.avg_days_to_convert }} days</p>
                <p class="text-xs text-gray-500 mt-1">from lead creation to conversion</p>
              </div>
              <div class="p-4 bg-purple-50 rounded-lg">
                <p class="text-sm text-gray-600">Active Leads</p>
                <p class="text-2xl font-bold text-purple-600">{{ funnel[0].count - conversionRate.converted }}</p>
                <p class="text-xs text-gray-500 mt-1">currently in pipeline</p>
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
    funnel: Array,
    conversionRate: Object,
  },
  methods: {
    exportData(format) {
      window.location.href = `/api/analytics/export-report?type=leads&format=${format}`
    },
    calculatePercentage(index) {
      if (!this.funnel || this.funnel.length === 0) return 0
      const first = this.funnel[0].count
      const current = this.funnel[index].count
      return (current / first) * 100
    },
    calculateDropoff(index) {
      if (index === 0) return 0
      const prev = this.funnel[index - 1].count
      const current = this.funnel[index].count
      return prev > 0 ? Math.round(((prev - current) / prev) * 100) : 0
    }
  }
}
</script>
