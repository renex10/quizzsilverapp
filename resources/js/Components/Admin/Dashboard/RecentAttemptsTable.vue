<!-- resources/js/Components/Admin/Dashboard/RecentAttemptsTable.vue -->
<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-semibold text-gray-800">🕒 Actividad reciente</h3>
      <span class="text-xs text-gray-400">Últimos 10 intentos</span>
    </div>

    <div v-if="attempts.length === 0"
      class="text-center py-8 text-gray-400 text-sm">
      No hay actividad reciente.
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left px-3 py-2 text-xs font-medium text-gray-500 uppercase">Estudiante</th>
            <th class="text-left px-3 py-2 text-xs font-medium text-gray-500 uppercase">Evaluación</th>
            <th class="text-right px-3 py-2 text-xs font-medium text-gray-500 uppercase">Puntaje</th>
            <th class="text-center px-3 py-2 text-xs font-medium text-gray-500 uppercase">Estado</th>
            <th class="text-right px-3 py-2 text-xs font-medium text-gray-500 uppercase">Fecha</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="attempt in attempts" :key="attempt.id"
            class="hover:bg-gray-50 transition-colors">
            <td class="px-3 py-2.5 font-medium text-gray-800">
              {{ attempt.user_name }}
            </td>
            <td class="px-3 py-2.5 max-w-[180px]">
              <p class="text-gray-800 truncate">{{ attempt.exam_title }}</p>
              <p class="text-xs text-gray-400 truncate">{{ attempt.series_title }}</p>
            </td>
            <td class="px-3 py-2.5 text-right font-semibold"
              :class="attempt.passed ? 'text-green-600' : 'text-red-600'">
              {{ attempt.percentage ?? '—' }}%
            </td>
            <td class="px-3 py-2.5 text-center">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="attempt.passed
                  ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'"
              >
                {{ attempt.passed ? '✅ Aprobado' : '❌ Reprobado' }}
              </span>
            </td>
            <td class="px-3 py-2.5 text-right text-gray-400 text-xs whitespace-nowrap">
              {{ attempt.completed_at }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  attempts: { type: Array, default: () => [] },
});
</script>