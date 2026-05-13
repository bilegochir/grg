<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-gray-600 text-sm font-medium">{{ title }}</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ value }}</p>
      </div>
      <div :class="[
        'w-12 h-12 rounded-lg flex items-center justify-center',
        change >= 0 ? 'bg-green-100' : 'bg-red-100'
      ]">
        <svg :class="['w-6 h-6', change >= 0 ? 'text-green-600' : 'text-red-600']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="change >= 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
        </svg>
      </div>
    </div>
    <div v-if="change" class="mt-4">
      <span :class="[
        'text-sm font-semibold',
        change >= 0 ? 'text-green-600' : 'text-red-600'
      ]">
        {{ change >= 0 ? '+' : '' }}{{ change }}%
      </span>
      <span class="text-xs text-gray-500 ml-2">vs. previous period</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    title: String,
    value: [String, Number],
    change: Number
  }
}
</script>
