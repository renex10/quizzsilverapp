<template>
  <div>

    <!-- Estado vacío -->
    <div
      v-if="!exams.data || exams.data.length === 0"
      class="text-center py-16 text-gray-400"
    >
      <svg class="mx-auto w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-sm font-medium">No hay evaluaciones</p>
      <p class="text-xs mt-1">Creá una nueva evaluación con el botón de arriba.</p>
    </div>

    <!-- Tabla -->
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Título</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Serie</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Tipo</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Versión</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Creado</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="exam in exams.data"
            :key="exam.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-4 py-3 font-medium text-gray-900">
              {{ exam.title }}
            </td>
            <td class="px-4 py-3 text-gray-600">
              {{ exam.series?.title ?? '—' }}
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                           font-mono bg-indigo-50 text-indigo-700">
                {{ tipoLabel(exam.type) }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500">
              {{ exam.version }}
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center gap-1.5">
                <ExamStatusBadge :status="exam.status" />
                <!-- Badge examen madre — visible cuando is_final_exam = true -->
                <span
                  v-if="exam.is_final_exam"
                  class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded
                         text-xs font-medium bg-purple-100 text-purple-700"
                  title="Examen madre — sus preguntas alimentan los mini quizzes de los topics"
                >
                  ⭐ Final
                </span>
              </div>
            </td>
            <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
              {{ exam.created_at }}
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2 justify-end">
                <!-- Ver detalle -->
                <Link
                  :href="route('admin.exams.show', exam.id)"
                  class="text-gray-400 hover:text-indigo-600 transition-colors"
                  title="Ver detalle"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </Link>

                <!-- Editar -->
                <Link
                  :href="route('admin.exams.edit', exam.id)"
                  class="text-gray-400 hover:text-yellow-600 transition-colors"
                  title="Editar"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </Link>

                <!-- Eliminar -->
                <button
                  @click="$emit('eliminar', exam)"
                  class="text-gray-400 hover:text-red-600 transition-colors"
                  title="Eliminar"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7
                         m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div
      v-if="exams.last_page > 1"
      class="flex items-center justify-between mt-4"
    >
      <p class="text-xs text-gray-500">
        Mostrando {{ exams.from }}–{{ exams.to }} de {{ exams.total }} evaluaciones
      </p>
      <div class="flex gap-1">
        <Link
          v-for="link in exams.links"
          :key="link.label"
          :href="link.url ?? '#'"
          :class="[
            'px-3 py-1 text-xs rounded border transition-colors',
            link.active
              ? 'bg-indigo-600 text-white border-indigo-600'
              : link.url
              ? 'border-gray-300 text-gray-600 hover:bg-gray-50'
              : 'border-gray-200 text-gray-300 cursor-not-allowed pointer-events-none',
          ]"
          preserve-scroll
          v-html="link.label"
        />
      </div>
    </div>

  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ExamStatusBadge from './ExamStatusBadge.vue';

defineProps({
  exams: {
    type: Object,
    required: true,
    // Estructura Laravel paginator:
    // { data: [], current_page, last_page, from, to, total, links: [] }
  },
});

defineEmits(['eliminar']);

const tipoLabel = (type) => {
  const map = {
    single_choice:   'Opción única',
    multiple_choice: 'Opción múltiple',
    true_false:      'Verdadero/Falso',
    ordering:        'Ordenamiento',
    matching:        'Relacionar',
  };
  return map[type] ?? type;
};
</script>