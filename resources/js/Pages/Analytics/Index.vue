<template>
  <AppLayout title="Advanced Analytics">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Advanced Analytics</h1>
          <p class="mt-2 text-gray-600">Comprehensive reporting and analytics dashboard</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <Link href="/analytics/cases" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
              <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm text-gray-600">Case Analytics</p>
                  <p class="text-lg font-semibold text-gray-900">Performance Metrics</p>
                </div>
              </div>
            </div>
          </Link>

          <Link href="/analytics/finance" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
              <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm text-gray-600">Finance Analytics</p>
                  <p class="text-lg font-semibold text-gray-900">Revenue & Invoices</p>
                </div>
              </div>
            </div>
          </Link>

          <Link href="/analytics/staff" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
              <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0a6 6 0 11-12 0 6 6 0 0112 0z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm text-gray-600">Staff Analytics</p>
                  <p class="text-lg font-semibold text-gray-900">Team Performance</p>
                </div>
              </div>
            </div>
          </Link>

          <Link href="/analytics/leads" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
              <div class="flex items-center">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm text-gray-600">Lead Analytics</p>
                  <p class="text-lg font-semibold text-gray-900">Conversion Funnel</p>
                </div>
              </div>
            </div>
          </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
              <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Stats</h2>
              <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <span class="text-gray-600">Recent Reports</span>
                  <span class="font-semibold text-gray-900">View all analytics across different dimensions</span>
                </div>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <span class="text-gray-600">Custom Reports</span>
                  <Link href="/analytics/custom-reports" class="text-blue-600 hover:text-blue-900 font-medium">Build →</Link>
                </div>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <span class="text-gray-600">Data Export</span>
                  <span class="text-sm text-gray-500">CSV, JSON formats available</span>
                </div>
              </div>
            </div>
          </div>

          <div>
            <div class="bg-white rounded-lg shadow p-6">
              <h2 class="text-xl font-semibold text-gray-900 mb-4">Getting Started</h2>
              <ul class="space-y-3 text-sm text-gray-600">
                <li class="flex items-start">
                  <span class="inline-block w-5 h-5 bg-blue-100 rounded-full text-center text-blue-600 text-xs font-bold mr-3 mt-0.5">1</span>
                  <span>Select a report category above</span>
                </li>
                <li class="flex items-start">
                  <span class="inline-block w-5 h-5 bg-blue-100 rounded-full text-center text-blue-600 text-xs font-bold mr-3 mt-0.5">2</span>
                  <span>Apply date filters to focus on specific periods</span>
                </li>
                <li class="flex items-start">
                  <span class="inline-block w-5 h-5 bg-blue-100 rounded-full text-center text-blue-600 text-xs font-bold mr-3 mt-0.5">3</span>
                  <span>Export data or save as custom report</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

export default {
  components: {
    AppLayout,
    Link
  }
}
</script>
