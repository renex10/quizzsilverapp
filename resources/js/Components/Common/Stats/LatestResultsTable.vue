<!-- resources/js/Components/Common/Stats/LatestResultsTable.vue -->
<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h3 class="text-base font-semibold text-gray-800 mb-4">{{ title }}</h3>

    <div v-if="results.length === 0"
      class="text-center py-8 text-gray-400 text-sm">
      No hay resultados todavía.
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left px-3 py-2 font-medium text-gray-500 text-xs uppercase">Fecha</th>
            <th class="text-left px-3 py-2 font-medium text-gray-500 text-xs uppercase">Examen</th>
            <th class="text-right px-3 py-2 font-medium text-gray-500 text-xs uppercase">Puntaje</th>
            <th class="text-center px-3 py-2 font-medium text-gray-500 text-xs uppercase">Estado</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="(result, i) in results" :key="i"
            class="hover:bg-gray-50 transition-colors">
            <td class="px-3 py-2.5 text-gray-500 whitespace-nowrap">{{ result.date }}</td>
            <td class="px-3 py-2.5 text-gray-800 font-medium max-w-[200px] truncate">
              {{ result.exam_title }}
            </td>
            <td class="px-3 py-2.5 text-right font-semibold"
              :class="result.passed ? 'text-green-600' : 'text-red-600'">
              {{ result.percentage }}%
            </td>
            <td class="px-3 py-2.5 text-center">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="result.passed
                  ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'"
              >
                {{ result.passed ? '✅ Aprobado' : '❌ Reprobado' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title:   { type: String, default: '🕒 Últimos resultados' },
  results: { type: Array,  default: () => [] },
  // [{ date, exam_title, percentage, passed }]
});
</script>