<!-- resources/js/Pages/Student/Stats/Index.vue -->
<template>
  <StudentLayout title="Mis Estadísticas">

    <template #header>
      <h2 class="text-2xl font-bold text-gray-800">📊 Mis Estadísticas</h2>
      <p class="text-sm text-gray-500 mt-0.5">Tu progreso acumulado en todas las evaluaciones</p>
    </template>

    <div class="space-y-6">

      <!-- ─── Tarjetas de resumen ─────────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <MetricCard
          icon="📋" label="Exámenes realizados"
          :value="summary.total_exams_taken ?? 0"
          color="indigo"
        />
        <MetricCard
          icon="📈" label="Promedio general"
          :value="summary.avg_percentage ?? 0" suffix="%"
          color="blue"
        />
        <MetricCard
          icon="✅" label="Tasa de aprobación"
          :value="summary.approval_rate ?? 0" suffix="%"
          color="green"
        />
        <MetricCard
          icon="🔥" label="Racha actual"
          :value="summary.current_streak ?? 0"
          :subtext="(summary.current_streak ?? 0) > 0 ? 'aprobados consecutivos' : 'sin racha activa'"
          color="orange"
        />
      </div>

      <!-- ─── Evolución de puntajes ─────────────────────────────────────── -->
      <LineChart
        title="📈 Evolución de puntajes"
        :labels="scoreLabels"
        :datasets="scoreDatasets"
        :reference-line="80"
        y-label="Puntaje (%)"
        :height="260"
      />

      <!-- ─── Dona + Barras por serie ──────────────────────────────────── -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <DoughnutChart
          title="🥧 Aprobados vs Reprobados"
          :labels="['Aprobados', 'Reprobados']"
          :datasets="donutDatasets"
        />
        <BarChart
          title="📊 Rendimiento por serie"
          :labels="seriesLabels"
          :datasets="seriesDatasets"
          y-label="Promedio (%)"
          :height="240"
        />
      </div>

      <!-- ─── Últimos resultados ────────────────────────────────────────── -->
      <LatestResultsTable :results="latest_results ?? []" />

      <!-- ─── Preguntas más falladas ────────────────────────────────────── -->
      <FailedQuestionsTable :questions="top_failed_questions ?? []" />

      <!-- ─── Intentos abandonados recuperables ─────────────────────────── -->
      <div
        v-if="(abandoned_attempts ?? []).length > 0"
        class="bg-yellow-50 rounded-xl border border-yellow-200 p-5"
      >
        <h3 class="text-base font-semibold text-yellow-800 mb-2">
          ⚠️ Exámenes pendientes por retomar
        </h3>
        <p class="text-sm text-yellow-700 mb-3">
          Tenés intentos abandonados que podés continuar:
        </p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="attempt in abandoned_attempts"
            :key="attempt.id"
            @click="retomar(attempt.exam_id)"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600
                   text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors"
          >
            🔄 {{ attempt.exam_title }}
          </button>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import StudentLayout      from '@/Layouts/StudentLayout.vue';
import MetricCard         from '@/Components/Common/Stats/MetricCard.vue';
import LineChart          from '@/Components/Common/Stats/LineChart.vue';
import BarChart           from '@/Components/Common/Stats/BarChart.vue';
import DoughnutChart      from '@/Components/Common/Stats/DoughnutChart.vue';
import LatestResultsTable from '@/Components/Common/Stats/LatestResultsTable.vue';
import FailedQuestionsTable from '@/Components/Common/Stats/FailedQuestionsTable.vue';

const props = defineProps({
  summary:               { type: Object, default: () => ({}) },
  score_evolution:       { type: Array,  default: () => [] },
  performance_by_series: { type: Array,  default: () => [] },
  top_failed_questions:  { type: Array,  default: () => [] },
  abandoned_attempts:    { type: Array,  default: () => [] },
  passed_distribution:   { type: Object, default: () => ({ approved: 0, failed: 0 }) },
  latest_results:        { type: Array,  default: () => [] },
});

// ─── Evolución de puntajes ───────────────────────────────────────────────────
const scoreLabels = computed(() =>
  props.score_evolution.map(i => i.date)
);

const scoreDatasets = computed(() => [{
  label:           'Puntaje (%)',
  data:            props.score_evolution.map(i => i.percentage),
  borderColor:     '#6366f1',
  backgroundColor: 'rgba(99,102,241,0.1)',
  fill:            true,
  tension:         0.35,
  pointBackgroundColor: props.score_evolution.map(i =>
    i.passed ? '#22c55e' : '#ef4444'
  ),
  pointRadius: 5,
  pointHoverRadius: 7,
}]);

// ─── Dona aprobados / reprobados ─────────────────────────────────────────────
const donutDatasets = computed(() => [{
  data:            [props.passed_distribution.approved, props.passed_distribution.failed],
  backgroundColor: ['#22c55e', '#ef4444'],
  borderWidth:     2,
  borderColor:     '#ffffff',
  hoverOffset:     6,
}]);

// ─── Barras por serie ────────────────────────────────────────────────────────
const seriesLabels = computed(() =>
  props.performance_by_series.map(s => s.series_title)
);

const seriesDatasets = computed(() => [{
  label:           'Promedio (%)',
  data:            props.performance_by_series.map(s => s.avg_percentage),
  backgroundColor: '#6366f1',
  borderRadius:    6,
  borderSkipped:   false,
}]);

// ─── Retomar intento ─────────────────────────────────────────────────────────
const retomar = (examId) => {
  router.post(route('student.attempt.start', examId));
};
</script>