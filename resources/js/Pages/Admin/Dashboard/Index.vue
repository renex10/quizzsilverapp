<!-- resources/js/Pages/Admin/Dashboard/Index.vue -->
<script setup>
import { ref, computed } from 'vue';
import AdminLayout          from '@/Layouts/AdminLayout.vue';
import BotonCrearQuiz       from '@/Components/Admin/BotonCrearQuiz.vue';
import CrearQuizOrquestador from '@/Components/Admin/Dashboard/Form/CreaQuiz/CrearQuizOrquestador.vue';

// Componentes comunes de estadísticas
import MetricCard           from '@/Components/Common/Stats/MetricCard.vue';
import BarChart             from '@/Components/Common/Stats/BarChart.vue';

// Componentes específicos del dashboard admin
import RecentAttemptsTable  from '@/Components/Admin/Dashboard/RecentAttemptsTable.vue';
import FailedExamsWidget    from '@/Components/Admin/Dashboard/FailedExamsWidget.vue';

// ─────────────────────────────────────────────────────────────────────────────
// Props desde DashboardController@index
// ─────────────────────────────────────────────────────────────────────────────
const props = defineProps({
  metrics: {
    type: Object,
    default: () => ({}),
    // { total_exams, total_series, total_students, attempts_last_30_days,
    //   global_approval_rate, today_attempts, pending_exams }
  },
  recent_attempts: { type: Array,  default: () => [] },
  failed_exams:    { type: Array,  default: () => [] },
  daily_attempts:  { type: Array,  default: () => [] },
});

// ─────────────────────────────────────────────────────────────────────────────
// Modal de creación
// ─────────────────────────────────────────────────────────────────────────────
const orquestadorRef = ref(null);
const abrirModal = () => { orquestadorRef.value?.openModal(); };

// ─────────────────────────────────────────────────────────────────────────────
// Gráfico de intentos últimos 7 días
// ─────────────────────────────────────────────────────────────────────────────
const dailyLabels = computed(() =>
  props.daily_attempts.map(d => {
    const [, , day] = d.date.split('-');
    return day + '/' + d.date.split('-')[1]; // dd/mm
  })
);

const dailyMax = computed(() =>
  Math.max(5, ...props.daily_attempts.map(d => d.attempts)) + 1
);

const dailyDatasets = computed(() => [{
  label:           'Intentos completados',
  data:            props.daily_attempts.map(d => d.attempts),
  backgroundColor: props.daily_attempts.map(d =>
    d.attempts > 0 ? '#6366f1' : '#e5e7eb'
  ),
  borderRadius:    8,
  borderSkipped:   false,
}]);
</script>

<template>
  <AdminLayout title="Dashboard">

    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
          <p class="text-sm text-gray-500 mt-0.5">Panel de administración</p>
        </div>
        <BotonCrearQuiz @open-modal="abrirModal" />
      </div>
    </template>

    <div class="space-y-6">

      <!-- ─── Métricas principales ──────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <MetricCard
          icon="📋" label="Evaluaciones"
          :value="metrics.total_exams ?? 0"
          color="indigo"
        />
        <MetricCard
          icon="📚" label="Series"
          :value="metrics.total_series ?? 0"
          color="purple"
        />
        <MetricCard
          icon="👥" label="Estudiantes"
          :value="metrics.total_students ?? 0"
          color="blue"
        />
        <MetricCard
          icon="✅" label="Tasa de aprobación"
          :value="metrics.global_approval_rate ?? 0" suffix="%"
          color="green"
        />
      </div>

      <!-- ─── Métricas secundarias ──────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <MetricCard
          icon="🎯" label="Intentos hoy"
          :value="metrics.today_attempts ?? 0"
          color="orange"
        />
        <MetricCard
          icon="📅" label="Intentos (30 días)"
          :value="metrics.attempts_last_30_days ?? 0"
          color="yellow"
        />
        <MetricCard
          icon="⏳" label="Borradores pendientes"
          :value="metrics.pending_exams ?? 0"
          :subtext="(metrics.pending_exams ?? 0) > 0 ? 'sin publicar' : 'todo publicado'"
          color="red"
        />
      </div>

      <!-- ─── Gráfico 7 días + Exámenes más reprobados ──────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <BarChart
            title="📊 Intentos completados — últimos 7 días"
            :labels="dailyLabels"
            :datasets="dailyDatasets"
            :y-min="0"
            :y-max="dailyMax"
            y-label="Intentos"
            :height="220"
          />
        </div>
        <FailedExamsWidget :exams="failed_exams" />
      </div>

      <!-- ─── Actividad reciente ────────────────────────────────────────── -->
      <RecentAttemptsTable :attempts="recent_attempts" />

    </div>

    <!-- Orquestador del formulario multipaso — no mover -->
    <CrearQuizOrquestador ref="orquestadorRef" />

  </AdminLayout>
</template>