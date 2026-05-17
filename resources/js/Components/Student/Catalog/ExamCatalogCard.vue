<!-- resources/js/Components/Student/Catalog/ExamCatalogCard.vue -->
<template>
  <div class="bg-white border border-gray-200 rounded-xl shadow-sm
              hover:shadow-md hover:border-indigo-200 transition-all duration-200 p-5
              flex flex-col justify-between">

    <!-- Cabecera: título + badge -->
    <div class="flex items-start justify-between gap-2 mb-3">
      <div class="min-w-0">
        <h3 class="text-base font-semibold text-gray-900 leading-snug truncate">
          {{ exam.title }}
        </h3>
        <p class="text-xs text-gray-400 mt-0.5">Versión {{ exam.version }}</p>
      </div>
      <ExamStatusBadge :status="exam.personal_status" class="shrink-0" />
    </div>

    <!-- Descripción -->
    <p v-if="exam.description"
      class="text-sm text-gray-500 mb-3 line-clamp-2 leading-relaxed">
      {{ exam.description }}
    </p>

    <!-- Metadata del examen -->
    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-500 mb-4">
      <div class="flex items-center gap-1">
        <span>📋</span>
        <span>{{ typeLabel(exam.type) }}</span>
      </div>
      <div class="flex items-center gap-1">
        <span>❓</span>
        <span>{{ exam.questions_count }} preguntas</span>
      </div>
      <div v-if="exam.time_limit" class="flex items-center gap-1">
        <span>⏱</span>
        <span>{{ exam.time_limit }} min</span>
      </div>
      <div class="flex items-center gap-1">
        <span>🎯</span>
        <span>Mínimo {{ exam.passing_score }}%</span>
      </div>
    </div>

    <!-- Progreso del estudiante -->
    <div v-if="exam.attempts_count > 0"
      class="bg-gray-50 rounded-lg px-3 py-2 mb-4 text-xs text-gray-600 space-y-1">
      <div class="flex justify-between">
        <span>Intentos realizados</span>
        <span class="font-medium">{{ exam.attempts_count }}</span>
      </div>
      <div v-if="exam.best_score !== null" class="flex justify-between">
        <span>Mejor puntaje</span>
        <span
          class="font-semibold"
          :class="exam.best_passed ? 'text-green-600' : 'text-red-600'"
        >
          {{ exam.best_score }}%
        </span>
      </div>
    </div>

    <!-- Botón de acción -->
    <button
      @click="iniciar"
      class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg
             text-sm font-semibold transition-all duration-200"
      :class="actionConfig.classes"
    >
      {{ actionConfig.icon }} {{ actionConfig.label }}
    </button>

  </div>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ExamStatusBadge from './ExamStatusBadge.vue';

const props = defineProps({
  exam: {
    type: Object,
    required: true,
    // { id, title, description, version, type, passing_score, time_limit,
    //   questions_count, best_score, best_passed, attempts_count,
    //   personal_status, active_attempt_id, abandoned_attempt_id }
  },
});

const typeLabel = (type) => ({
  single_choice:   'Opción única',
  multiple_choice: 'Opción múltiple',
  true_false:      'Verdadero / Falso',
  ordering:        'Ordenamiento',
  matching:        'Relacionar columnas',
}[type] ?? type);

// Configuración del botón según el estado personal
const actionConfig = computed(() => {
  const map = {
    new:         { label: 'Iniciar evaluación',  icon: '🚀', classes: 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow-md' },
    in_progress: { label: 'Continuar',           icon: '▶️', classes: 'bg-yellow-500 hover:bg-yellow-600 text-white shadow-sm hover:shadow-md' },
    passed:      { label: 'Volver a intentar',   icon: '🔄', classes: 'bg-green-600 hover:bg-green-700 text-white' },
    failed:      { label: 'Reintentar',          icon: '💪', classes: 'bg-red-600 hover:bg-red-700 text-white' },
    abandoned:   { label: 'Retomar intento',     icon: '🔄', classes: 'bg-blue-600 hover:bg-blue-700 text-white' },
  };
  return map[props.exam.personal_status] ?? map.new;
});

const iniciar = () => {
  router.post(route('student.attempt.start', props.exam.id));
};
</script>