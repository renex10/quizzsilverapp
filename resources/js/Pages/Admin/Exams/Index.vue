<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BotonCrearQuiz from '@/Components/Admin/BotonCrearQuiz.vue';
import CrearQuizOrquestador from '@/Components/Admin/Dashboard/Form/CreaQuiz/CrearQuizOrquestador.vue';
import ExamFilters from '@/Components/Admin/Exams/ExamFilters.vue';
import ExamTable from '@/Components/Admin/Exams/ExamTable.vue';

// ---------------------------------------------------------------
// Props que llegan desde ExamController@index
// ---------------------------------------------------------------
const props = defineProps({
  exams: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({ series_id: null, status: null }),
  },
  seriesList: {
    type: Array,
    default: () => [],
  },
});

// ---------------------------------------------------------------
// Ref del orquestador para abrir el modal desde el botón
// ---------------------------------------------------------------
const orquestadorRef = ref(null);

const abrirModal = () => {
  orquestadorRef.value?.openModal();
};

// ---------------------------------------------------------------
// Filtros locales — sincronizados con los que vienen del backend
// ---------------------------------------------------------------
const seriesId = ref(props.filters.series_id ?? null);
const status   = ref(props.filters.status   ?? null);
const search   = ref(props.filters.search   ?? '');

// Cuando cambia cualquier filtro, hacer request a Laravel con Inertia
// preservando el scroll y el estado de la página
watch([seriesId, status, search], () => {
  router.get(
    route('admin.exams.index'),
    {
      series_id: seriesId.value  || undefined,
      status:    status.value    || undefined,
      search:    search.value    || undefined,
    },
    {
      preserveState:  true,
      preserveScroll: true,
      replace:        true,
    }
  );
});

// ---------------------------------------------------------------
// Eliminar examen con confirmación SweetAlert
// ---------------------------------------------------------------
const eliminarExamen = async (exam) => {
  const result = await Swal.fire({
    icon:              'warning',
    title:             '¿Eliminar evaluación?',
    html:              `Vas a eliminar <strong>${exam.title}</strong>.<br>Esta acción no se puede deshacer.`,
    showCancelButton:  true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText:  'Cancelar',
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#6b7280',
  });

  if (!result.isConfirmed) return;

  router.delete(route('admin.exams.destroy', exam.id), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon:              'success',
        title:             'Evaluación eliminada',
        text:              `"${exam.title}" fue eliminada correctamente.`,
        timer:             3000,
        timerProgressBar:  true,
        showConfirmButton: false,
      });
    },
    onError: () => {
      Swal.fire({
        icon:  'error',
        title: 'No se puede eliminar',
        text:  'Esta evaluación tiene intentos registrados y no puede eliminarse.',
        confirmButtonColor: '#6366f1',
      });
    },
  });
};
</script>

<template>
  <AdminLayout title="Evaluaciones">

    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Evaluaciones</h2>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ exams.total ?? 0 }} evaluación{{ (exams.total ?? 0) !== 1 ? 'es' : '' }} en total
          </p>
        </div>
        <BotonCrearQuiz @open-modal="abrirModal" />
      </div>
    </template>

    <!-- Filtros -->
    <div class="mb-5">
      <ExamFilters
        v-model:series-id="seriesId"
        v-model:status="status"
        v-model:search="search"
        :series-list="seriesList"
      />
    </div>

    <!-- Tabla -->
    <ExamTable
      :exams="exams"
      @eliminar="eliminarExamen"
    />

    <!-- Orquestador del modal de creación -->
    <CrearQuizOrquestador ref="orquestadorRef" />

  </AdminLayout>
</template>