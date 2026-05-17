<!-- resources/js/Pages/Admin/Stats/Index.vue -->
<template>
  <AdminLayout title="Estadísticas">

    <template #header>
      <h2 class="text-2xl font-bold text-gray-800">📊 Estadísticas Globales</h2>
      <p class="text-sm text-gray-500 mt-0.5">Visión completa del rendimiento de la plataforma</p>
    </template>

    <div class="space-y-6">

      <!-- ─── Tarjetas de resumen global ──────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <MetricCard icon="👥" label="Estudiantes"   :value="global.total_students ?? 0"  color="indigo" />
        <MetricCard icon="📋" label="Evaluaciones"  :value="global.total_exams ?? 0"     color="blue" />
        <MetricCard icon="📚" label="Series"        :value="global.total_series ?? 0"    color="purple" />
        <MetricCard icon="🎯" label="Intentos"      :value="global.total_attempts ?? 0"  color="orange" />
        <MetricCard
          icon="✅" label="Tasa de aprobación"
          :value="global.global_approval_rate ?? 0" suffix="%"
          color="green"
        />
      </div>

      <!-- ─── Intentos por mes + Distribución de puntajes ─────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <LineChart
            title="📈 Intentos completados por mes"
            :labels="monthlyLabels"
            :datasets="monthlyDatasets"
            :y-min="0"
            :y-max="monthlyMax"
            y-label="Intentos"
            :height="260"
          />
        </div>
        <BarChart
          title="📊 Distribución de puntajes"
          :labels="Object.keys(score_distribution ?? {})"
          :datasets="scoreDistDatasets"
          :y-min="0"
          :y-max="scoreDistMax"
          y-label="Cantidad"
          :height="260"
        />
      </div>

      <!-- ─── Aprobación por serie (barras horizontales) ───────────────────── -->
      <BarChart
        title="🏆 Tasa de aprobación por serie"
        :labels="seriesLabels"
        :datasets="seriesApprovalDatasets"
        :horizontal="true"
        :y-min="0"
        :y-max="100"
        :height="Math.max(200, (performance_by_series ?? []).length * 45)"
      />

      <!-- ─── Top exámenes más reprobados + Top estudiantes ───────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Top exámenes más reprobados -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h3 class="text-base font-semibold text-gray-800 mb-4">
            ⚠️ Evaluaciones con mayor reprobación
          </h3>
          <div v-if="(most_failed_exams ?? []).length === 0"
            class="text-center py-8 text-gray-400 text-sm">
            Sin datos suficientes
          </div>
          <div v-else class="space-y-3">
            <div v-for="exam in most_failed_exams" :key="exam.id"
              class="flex items-center gap-3">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ exam.title }}</p>
                <p class="text-xs text-gray-400 truncate">{{ exam.series }}</p>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-bold text-red-600">{{ exam.fail_rate }}%</p>
                <p class="text-xs text-gray-400">{{ exam.total_attempts }} intentos</p>
              </div>
              <div class="w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden shrink-0">
                <div class="h-full bg-red-500 rounded-full"
                  :style="{ width: exam.fail_rate + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Dona global aprobados vs reprobados -->
        <DoughnutChart
          title="🥧 Aprobados vs Reprobados (global)"
          :labels="['Aprobados', 'Reprobados']"
          :datasets="globalDonutDatasets"
        />

      </div>

      <!-- ─── Top estudiantes ──────────────────────────────────────────────── -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">🏅 Top estudiantes por actividad</h3>
        <div v-if="(top_students ?? []).length === 0"
          class="text-center py-8 text-gray-400 text-sm">
          Sin datos suficientes
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="text-left px-3 py-2 text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="text-left px-3 py-2 text-xs font-medium text-gray-500 uppercase">Estudiante</th>
                <th class="text-right px-3 py-2 text-xs font-medium text-gray-500 uppercase">Intentos</th>
                <th class="text-right px-3 py-2 text-xs font-medium text-gray-500 uppercase">Aprobados</th>
                <th class="text-right px-3 py-2 text-xs font-medium text-gray-500 uppercase">Tasa</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="(student, i) in top_students" :key="student.id"
                class="hover:bg-gray-50 transition-colors">
                <td class="px-3 py-2.5 text-gray-400 font-medium">{{ i + 1 }}</td>
                <td class="px-3 py-2.5">
                  <p class="font-medium text-gray-800">{{ student.name }}</p>
                  <p class="text-xs text-gray-400">{{ student.email }}</p>
                </td>
                <td class="px-3 py-2.5 text-right text-gray-600">{{ student.total_attempts }}</td>
                <td class="px-3 py-2.5 text-right text-green-600 font-medium">{{ student.approved_attempts }}</td>
                <td class="px-3 py-2.5 text-right">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="student.approval_rate >= 70
                      ? 'bg-green-100 text-green-700'
                      : 'bg-red-100 text-red-700'">
                    {{ student.approval_rate }}%
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import AdminLayout        from '@/Layouts/AdminLayout.vue';
import MetricCard         from '@/Components/Common/Stats/MetricCard.vue';
import LineChart          from '@/Components/Common/Stats/LineChart.vue';
import BarChart           from '@/Components/Common/Stats/BarChart.vue';
import DoughnutChart      from '@/Components/Common/Stats/DoughnutChart.vue';

const props = defineProps({
  global:                { type: Object, default: () => ({}) },
  monthly_attempts:      { type: Array,  default: () => [] },
  performance_by_series: { type: Array,  default: () => [] },
  series_comparison:     { type: Array,  default: () => [] },
  most_failed_exams:     { type: Array,  default: () => [] },
  top_students:          { type: Array,  default: () => [] },
  score_distribution:    { type: Object, default: () => ({}) },
});

// ─── Intentos por mes ────────────────────────────────────────────────────────
const monthlyLabels = computed(() =>
  props.monthly_attempts.map(i => i.month)
);
const monthlyMax = computed(() =>
  Math.max(10, ...props.monthly_attempts.map(i => i.attempts ?? i.count ?? 0)) + 2
);
const monthlyDatasets = computed(() => [{
  label:           'Intentos completados',
  data:            props.monthly_attempts.map(i => i.attempts ?? i.count ?? 0),
  borderColor:     '#6366f1',
  backgroundColor: 'rgba(99,102,241,0.12)',
  fill:            true,
  tension:         0.35,
  pointRadius:     4,
  pointHoverRadius: 6,
}]);

// ─── Distribución de puntajes ────────────────────────────────────────────────
const scoreDistMax = computed(() =>
  Math.max(5, ...Object.values(props.score_distribution ?? {})) + 2
);
const scoreDistDatasets = computed(() => [{
  label:           'Intentos',
  data:            Object.values(props.score_distribution ?? {}),
  backgroundColor: ['#ef4444','#f97316','#eab308','#22c55e','#6366f1'],
  borderRadius:    6,
  borderSkipped:   false,
}]);

// ─── Aprobación por serie (horizontal) ──────────────────────────────────────
const seriesLabels = computed(() =>
  props.performance_by_series.map(s => s.series_title)
);
const seriesApprovalDatasets = computed(() => [{
  label:           'Tasa de aprobación (%)',
  data:            props.performance_by_series.map(s => s.approval_rate),
  backgroundColor: props.performance_by_series.map(s =>
    s.approval_rate >= 70 ? '#22c55e' : '#ef4444'
  ),
  borderRadius:    5,
  borderSkipped:   false,
}]);

// ─── Dona global ─────────────────────────────────────────────────────────────
const globalDonutDatasets = computed(() => {
  const total    = props.global.total_attempts ?? 0;
  const rate     = props.global.global_approval_rate ?? 0;
  const approved = Math.round(total * rate / 100);
  const failed   = total - approved;
  return [{
    data:            [approved, failed],
    backgroundColor: ['#22c55e', '#ef4444'],
    borderWidth:     2,
    borderColor:     '#ffffff',
    hoverOffset:     6,
  }];
});
</script>