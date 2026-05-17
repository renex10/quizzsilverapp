<!-- resources/js/Components/Student/Catalog/SeriesGroup.vue -->
<template>
  <div class="mb-10">

    <!-- Cabecera de la serie -->
    <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-3 mb-5">
      <div class="min-w-0">
        <h2 class="text-xl font-bold text-gray-800 leading-tight">
          {{ group.series.title }}
        </h2>
        <p v-if="group.series.description" class="text-sm text-gray-500 mt-0.5">
          {{ group.series.description }}
        </p>
        <div class="flex items-center gap-2 mt-2">
          <span class="inline-block bg-indigo-100 text-indigo-700 text-xs
                       font-medium px-2.5 py-0.5 rounded-full">
            {{ group.series.domain }}
          </span>
          <span class="text-xs text-gray-400">
            {{ group.exams.length }} versión{{ group.exams.length !== 1 ? 'es' : '' }}
          </span>
        </div>
      </div>

      <!-- Resumen de progreso en la serie -->
      <div v-if="seriesProgress.total > 0"
        class="shrink-0 text-right text-xs text-gray-500">
        <p class="font-medium text-gray-700">
          {{ seriesProgress.passed }} / {{ seriesProgress.total }} aprobadas
        </p>
        <div class="w-24 h-1.5 bg-gray-200 rounded-full mt-1 overflow-hidden">
          <div
            class="h-full bg-green-500 rounded-full transition-all duration-500"
            :style="{ width: `${seriesProgress.percent}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Grid de tarjetas de exámenes -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <ExamCatalogCard
        v-for="exam in group.exams"
        :key="exam.id"
        :exam="exam"
      />
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue';
import ExamCatalogCard from './ExamCatalogCard.vue';

const props = defineProps({
  group: {
    type: Object,
    required: true,
    // { series: { id, title, description, domain }, exams: [...] }
  },
});

// Progreso del estudiante dentro de esta serie
const seriesProgress = computed(() => {
  const total  = props.group.exams.filter(e => e.attempts_count > 0).length;
  const passed = props.group.exams.filter(e => e.personal_status === 'passed').length;
  return {
    total,
    passed,
    percent: total > 0 ? Math.round((passed / props.group.exams.length) * 100) : 0,
  };
});
</script>