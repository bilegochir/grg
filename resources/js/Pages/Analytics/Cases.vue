<template>
  <AppLayout title="Case Analytics">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Case Analytics</h1>
            <p class="mt-2 text-gray-600">Performance metrics and case progression analysis</p>
          </div>
          <div class="space-x-2">
            <button @click="exportData('csv')" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export CSV
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <MetricCard title="Completion Rate" :value="`${completionRate.rate}%`" :change="+5.2" />
          <MetricCard title="Avg Duration" :value="`${averageDuration.average} days`" :change="-2.1" />
          <MetricCard title="Open Cases" :value="completionRate.total" :change="+8" />
          <MetricCard title="Closed Cases" :value="completionRate.closed" :change="+3" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Cases by Stage</h2>
            <div class="space-y-3">
              <div v-for="stage in byStage" :key="stage.id" class="flex items-center">
                <div class="w-3 h-3 rounded-full mr-3" :style="{ backgroundColor: `#${stage.color}` || '#ccc' }"></div>
                <span class="text-sm text-gray-600 flex-1">{{ stage.name }}</span>
                <span class="font-semibold text-gray-900">{{ stage.total_cases }}</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Cases by Priority</h2>
            <div class="space-y-3">
              <div v-for="priority in byPriority" :key="priority.priority" class="flex items-center justify-between p-2">
                <span class="text-sm text-gray-600">{{ priority.label }}</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">{{ priority.count }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Top Agents</h2>
            <div class="space-y-3">
              <div v-for="agent in byAgent.slice(0, 5)" :key="agent.user_id" class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <div>
                  <p class="font-medium text-gray-900">{{ agent.name }}</p>
                  <p class="text-xs text-gray-500">{{ agent.closed }} closed · {{ agent.open }} open</p>
                </div>
                <span class="text-sm font-semibold text-green-600">{{ agent.close_rate }}%</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Top Countries</h2>
            <div class="space-y-3">
              <div v-for="country in byCountry.slice(0, 5)" :key="country.country_id" class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <div>
                  <p class="font-medium text-gray-900">{{ country.name }}</p>
                  <p class="text-xs text-gray-500">{{ country.open }} open</p>
                </div>
                <span class="text-sm font-semibold text-blue-600">{{ country.total }}</span>
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
    completionRate: Object,
    averageDuration: Object,
    byStage: Array,
    byPriority: Array,
    byAgent: Array,
    byCountry: Array,
  },
  methods: {
    exportData(format) {
      window.location.href = `/api/analytics/export-report?type=cases&format=${format}`
    }
  }
}
</script>
