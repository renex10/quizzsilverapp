<!-- resources/js/Components/Admin/Dashboard/FailedExamsWidget.vue -->
<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-semibold text-gray-800">⚠️ Mayor tasa de reprobación</h3>
      <span class="text-xs text-gray-400">Top 5</span>
    </div>

    <div v-if="exams.length === 0"
      class="text-center py-8 text-gray-400 text-sm">
      Sin datos suficientes.
    </div>

    <div v-else class="space-y-3">
      <div v-for="exam in exams" :key="exam.id"
        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">

        <!-- Barra de reprobación -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm font-medium text-gray-800 truncate pr-2">
              {{ exam.title }}
            </p>
            <span class="shrink-0 text-sm font-bold text-red-600">
              {{ exam.fail_rate }}%
            </span>
          </div>
          <div class="flex items-center gap-2">
            <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="exam.fail_rate >= 70 ? 'bg-red-500' : exam.fail_rate >= 40 ? 'bg-orange-400' : 'bg-yellow-400'"
                :style="{ width: exam.fail_rate + '%' }"
              ></div>
            </div>
            <span class="shrink-0 text-xs text-gray-400">
              {{ exam.failed_attempts }}/{{ exam.total_attempts }}
            </span>
          </div>
          <p class="text-xs text-gray-400 mt-0.5 truncate">{{ exam.series_title }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  exams: { type: Array, default: () => [] },
});
</script>