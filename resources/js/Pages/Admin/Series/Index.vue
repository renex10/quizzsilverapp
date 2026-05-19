<!-- resources/js/Pages/Admin/Series/Index.vue -->
<script setup>
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  series: { type: Array, default: () => [] },
  // [{ id, title, description, domain, exams_count, topics_count, created_at }]
  // NUEVO: topics_count — requiere withCount('topics') en SeriesController@index
});

const eliminar = async (serie) => {
  if (serie.exams_count > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'No se puede eliminar',
      text: `"${serie.title}" tiene ${serie.exams_count} evaluación${serie.exams_count !== 1 ? 'es' : ''} asociada${serie.exams_count !== 1 ? 's' : ''}.`,
      confirmButtonColor: '#6366f1',
    });
    return;
  }

  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar serie?',
    html: `Vas a eliminar <strong>${serie.title}</strong>. Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
  });

  if (result.isConfirmed) {
    router.delete(route('admin.series.destroy', serie.id), {
      onSuccess: () => Swal.fire({
        icon: 'success', title: 'Serie eliminada', timer: 2000,
        showConfirmButton: false, timerProgressBar: true,
      }),
      onError: () => Swal.fire({
        icon: 'error', title: 'Error al eliminar',
        text: 'No se pudo eliminar la serie.', confirmButtonColor: '#ef4444',
      }),
    });
  }
};
</script>

<template>
  <AdminLayout title="Series">

    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Series</h2>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ series.length }} serie{{ series.length !== 1 ? 's' : '' }} registrada{{ series.length !== 1 ? 's' : '' }}
          </p>
        </div>
        <Link
          :href="route('admin.series.create')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700
                 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nueva serie
        </Link>
      </div>
    </template>

    <!-- Estado vacío -->
    <div v-if="series.length === 0" class="text-center py-20 text-gray-400">
      <div class="text-5xl mb-4">📚</div>
      <p class="text-lg font-medium text-gray-500">No hay series creadas</p>
      <p class="text-sm mt-1">Creá una serie para agrupar evaluaciones relacionadas.</p>
      <Link
        :href="route('admin.series.create')"
        class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-indigo-600
               text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors"
      >
        Crear primera serie
      </Link>
    </div>

    <!-- Grid de series -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="serie in series"
        :key="serie.id"
        class="bg-white rounded-xl border border-gray-200 shadow-sm
               hover:shadow-md hover:border-indigo-200 transition-all duration-200 p-5
               flex flex-col justify-between"
      >
        <!-- Cabecera -->
        <div class="flex items-start justify-between gap-2 mb-3">
          <div class="min-w-0">
            <h3 class="text-base font-semibold text-gray-900 truncate">{{ serie.title }}</h3>
            <p v-if="serie.description" class="text-sm text-gray-500 mt-0.5 line-clamp-2">
              {{ serie.description }}
            </p>
          </div>
          <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full
                       text-xs font-medium bg-indigo-100 text-indigo-700">
            {{ serie.domain }}
          </span>
        </div>

        <!-- Contadores -->
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 mb-4">

          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293
                   l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>
            </svg>
            <span>
              <strong class="text-gray-700">{{ serie.exams_count }}</strong>
              evaluación{{ serie.exams_count !== 1 ? 'es' : '' }}
            </span>
          </div>

          <span class="text-gray-300">·</span>

          <!-- ── Contador de topics — NUEVO ── -->
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            <span>
              <strong class="text-gray-700">{{ serie.topics_count ?? 0 }}</strong>
              topic{{ (serie.topics_count ?? 0) !== 1 ? 's' : '' }}
            </span>
          </div>

          <span class="text-gray-300">·</span>
          <span class="text-xs text-gray-400">{{ serie.created_at }}</span>

        </div>

        <!-- Acciones -->
        <div class="space-y-2 pt-3 border-t border-gray-100">

          <!-- ── Botón principal: Gestionar topics — NUEVO ── -->
          <Link
            :href="route('admin.topics.index', { series: serie.id })"
            class="w-full flex items-center justify-center gap-2 px-3 py-2
                   text-sm font-semibold text-indigo-700 bg-indigo-50
                   border border-indigo-200 rounded-lg hover:bg-indigo-100
                   transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            Gestionar topics y contenido
            <span
              v-if="(serie.topics_count ?? 0) > 0"
              class="ml-auto bg-indigo-200 text-indigo-800 text-xs font-bold
                     px-1.5 py-0.5 rounded-full"
            >
              {{ serie.topics_count }}
            </span>
          </Link>

          <!-- Acciones secundarias -->
          <div class="flex items-center gap-2">
            <Link
              :href="route('admin.series.edit', serie.id)"
              class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5
                     text-sm text-gray-600 border border-gray-300 rounded-lg
                     hover:bg-gray-50 transition-colors"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2
                     2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Editar
            </Link>
            <button
              @click="eliminar(serie)"
              class="flex items-center justify-center gap-1.5 px-3 py-1.5 text-sm
                     text-red-600 border border-red-200 rounded-lg hover:bg-red-50
                     transition-colors disabled:opacity-40"
              :disabled="serie.exams_count > 0"
              :title="serie.exams_count > 0 ? 'No se puede eliminar — tiene evaluaciones' : 'Eliminar'"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5
                     4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              Eliminar
            </button>
          </div>

        </div>
      </div>
    </div>

  </AdminLayout>
</template>