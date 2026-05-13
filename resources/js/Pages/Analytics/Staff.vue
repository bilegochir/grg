<template>
  <AppLayout title="Staff Analytics">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Staff Analytics</h1>
            <p class="mt-2 text-gray-600">Team productivity and performance metrics</p>
          </div>
          <button @click="exportData('csv')" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
          </button>
        </div>

        <div class="bg-white rounded-lg shadow">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff Member</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closed Cases</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created Cases</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed Tasks</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending Tasks</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y">
                <tr v-for="staff in productivity" :key="staff.user_id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <p class="font-medium text-gray-900">{{ staff.name }}</p>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-green-600 font-semibold">{{ staff.closed_cases }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-blue-600 font-semibold">{{ staff.created_cases }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-purple-600 font-semibold">{{ staff.completed_tasks }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                      {{ staff.pending_tasks }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-20 bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" :style="{ width: `${Math.min(100, (staff.closed_cases / Math.max(1, staff.closed_cases + staff.pending_tasks)) * 100)}%` }"></div>
                      </div>
                      <span class="ml-2 text-xs text-gray-600">{{ Math.round((staff.closed_cases / Math.max(1, staff.closed_cases + staff.pending_tasks)) * 100) }}%</span>
                    </div>
                  </td>
                </tr>
                <tr v-if="!productivity || productivity.length === 0">
                  <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    No staff data available
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  components: {
    AppLayout
  },
  props: {
    productivity: Array,
  },
  methods: {
    exportData(format) {
      window.location.href = `/api/analytics/export-report?type=staff&format=${format}`
    }
  }
}
</script>
