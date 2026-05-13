<template>
  <AppLayout title="Custom Reports">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Custom Reports</h1>
            <p class="mt-2 text-gray-600">Build and save custom analytics reports</p>
          </div>
          <button @click="showNewReportModal = true" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Report
          </button>
        </div>

        <div v-if="!reports || reports.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
          <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <p class="text-gray-500 text-lg mb-4">No custom reports yet</p>
          <button @click="showNewReportModal = true" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Create your first report
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="report in reports" :key="report.id" class="bg-white rounded-lg shadow hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ report.name }}</h3>
                <p class="text-sm text-gray-500">{{ report.description }}</p>
              </div>
              <button @click="toggleFavorite(report)" class="text-gray-400 hover:text-yellow-500">
                <svg class="w-5 h-5" :fill="report.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </button>
            </div>

            <div class="mb-4 p-3 bg-gray-50 rounded">
              <p class="text-xs text-gray-600">Type: <span class="font-semibold text-gray-900">{{ report.type }}</span></p>
              <p class="text-xs text-gray-600 mt-1">Created: {{ new Date(report.created_at).toLocaleDateString() }}</p>
            </div>

            <div class="flex space-x-2">
              <button @click="runReport(report)" class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded text-sm font-medium">
                Run
              </button>
              <button @click="deleteReport(report)" class="flex-1 px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded text-sm font-medium">
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

export default {
  components: {
    AppLayout
  },
  data() {
    return {
      reports: [],
      showNewReportModal: false,
      newReport: {
        name: '',
        description: '',
        type: 'cases'
      }
    }
  },
  mounted() {
    this.loadReports()
  },
  methods: {
    async loadReports() {
      try {
        const response = await fetch('/api/analytics/reports')
        this.reports = await response.json()
      } catch (error) {
        console.error('Error loading reports:', error)
      }
    },
    async toggleFavorite(report) {
      try {
        await fetch(`/api/analytics/reports/${report.id}`, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ is_favorite: !report.is_favorite })
        })
        report.is_favorite = !report.is_favorite
      } catch (error) {
        console.error('Error updating report:', error)
      }
    },
    async deleteReport(report) {
      if (!confirm('Are you sure you want to delete this report?')) return
      
      try {
        await fetch(`/api/analytics/reports/${report.id}`, { method: 'DELETE' })
        this.reports = this.reports.filter(r => r.id !== report.id)
      } catch (error) {
        console.error('Error deleting report:', error)
      }
    },
    runReport(report) {
      window.location.href = `/api/analytics/reports/${report.id}/run`
    }
  }
}
</script>
