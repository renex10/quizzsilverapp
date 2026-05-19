<!--
=============================================================================
  Admin/Topics/Index.vue
  Ubicación: resources/js/Pages/Admin/Topics/Index.vue
=============================================================================

  ¿QUÉ HACE ESTA VISTA?
  ──────────────────────
  Es el panel de gestión de topics de una serie específica.
  El administrador llega aquí desde Admin/Series/Index al hacer click
  en "Ver topics" o "Gestionar contenido" de una serie.

  RESPONSABILIDADES DE ESTA VISTA:
  ─────────────────────────────────
  ✔ Mostrar la lista de topics de la serie (delegado a TopicSortableList)
  ✔ Guardar el nuevo orden cuando el admin reordena por drag & drop
  ✔ Pedir confirmación y eliminar un topic (con SweetAlert)
  ✔ Navegar a Create y Edit (delegado a Inertia router)
  ✔ Mostrar métricas rápidas de la serie (total topics, lecciones, etc.)

  LO QUE NO HACE:
  ────────────────
  ✘ No tiene formularios inline — toda la creación y edición
    son páginas separadas (Create.vue y Edit.vue)
  ✘ No maneja la lógica de las lecciones — eso está en Lessons/Edit.vue
  ✘ No renderiza el contenido de los topics — solo la lista de gestión

  PROPS QUE RECIBE (desde Admin\TopicController@index):
  ─────────────────────────────────────────────────────
    serie  → objeto de la serie con título, dominio, published_at, etc.
    topics → array de topics con lessons_count incluido (withCount)

  FLUJO DE NAVEGACIÓN DESDE ESTA VISTA:
  ──────────────────────────────────────
    [Nuevo topic]      → /admin/series/{id}/topics/create
    [Editar topic]     → /admin/topics/{id}/edit
    [Editar lecciones] → /admin/topics/{id}/lessons/edit  (futuro)
    [← Volver]        → /admin/series (Index de series)
=============================================================================
-->

<template>
  <AdminLayout :title="`Topics — ${serie.title}`">

    <!-- ── Encabezado de la página ──────────────────────────────────────── -->
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <!-- Breadcrumb + título -->
        <div class="flex items-center gap-3">
          <Link
            :href="route('admin.series.index')"
            class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600
                   transition-all duration-150"
            title="Volver a Series"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 19l-7-7 7-7"/>
            </svg>
          </Link>

          <div>
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-0.5">
              <span>Series</span>
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5l7 7-7 7"/>
              </svg>
              <span class="text-gray-600 font-medium truncate max-w-[200px]">
                {{ serie.title }}
              </span>
            </div>

            <h2 class="text-2xl font-bold text-gray-800 leading-tight">Topics</h2>
          </div>
        </div>

        <!-- Acciones principales -->
        <div class="flex items-center gap-3">

          <!-- Badge de estado de la serie -->
          <span
            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                   text-xs font-medium"
            :class="serie.published_at
              ? 'bg-green-100 text-green-700'
              : 'bg-gray-100 text-gray-500'"
          >
            <span
              class="w-1.5 h-1.5 rounded-full"
              :class="serie.published_at ? 'bg-green-500' : 'bg-gray-400'"
            ></span>
            {{ serie.published_at ? 'Serie publicada' : 'Serie no publicada' }}
          </span>

          <!-- Botón nuevo topic -->
          <Link
            :href="route('admin.topics.create', { series: serie.id })"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600
                   hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl
                   transition-colors shadow-sm hover:shadow-md"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo topic
          </Link>
        </div>
      </div>
    </template>

    <!-- ── Cuerpo principal ─────────────────────────────────────────────── -->
    <div class="space-y-6 max-w-4xl">

      <!-- ── Tarjetas de resumen de la serie ──────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
          <p class="text-2xl font-black text-gray-800">{{ topics.length }}</p>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ topics.length === 1 ? 'Topic' : 'Topics' }}
          </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
          <p class="text-2xl font-black text-gray-800">{{ totalLecciones }}</p>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ totalLecciones === 1 ? 'Lección' : 'Lecciones' }}
          </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
          <p class="text-2xl font-black text-indigo-600">{{ topicsPublicos }}</p>
          <p class="text-xs text-gray-500 mt-0.5">
            Públicos
          </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
          <p class="text-2xl font-black text-gray-800">{{ topicsConCategoria }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Con mini quiz</p>
        </div>

      </div>

      <!-- ── Aviso si la serie no está publicada ───────────────────────── -->
      <div
        v-if="!serie.published_at"
        class="flex items-start gap-3 px-4 py-3.5 bg-amber-50 border border-amber-200
               rounded-xl text-sm text-amber-800"
      >
        <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500"
          fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
               1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34
               16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
          <p class="font-medium">La serie no está publicada</p>
          <p class="text-xs text-amber-700 mt-0.5">
            Los topics marcados como públicos no aparecerán en la landing
            hasta que publiques la serie desde su formulario de edición.
          </p>
        </div>
      </div>

      <!-- ── Lista de topics con drag & drop ──────────────────────────── -->
      <TopicSortableList
        :topics="topics"
        :guardando="guardando"
        :series-id="serie.id"
        @reordered="guardarOrden"
        @edit="irAEditar"
        @delete="confirmarEliminar"
      />

    </div>

  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AdminLayout        from '@/Layouts/AdminLayout.vue';
import TopicSortableList  from '@/Components/Admin/Topics/TopicSortableList.vue';

// ─── Props desde Admin\TopicController@index ──────────────────────────────────

const props = defineProps({
  /**
   * Objeto de la serie propietaria de los topics.
   * { id, title, slug, domain, difficulty, published_at, ... }
   */
  serie: {
    type: Object,
    required: true,
  },

  /**
   * Array de topics de esta serie, ordenados por 'order'.
   * Cada topic incluye 'lessons_count' (withCount del controlador).
   * { id, title, slug, description, icon, color, order,
   *   is_public, exam_category, lessons_count }
   */
  topics: {
    type: Array,
    default: () => [],
  },
});

// ─── Estado local ─────────────────────────────────────────────────────────────

/** Indica si se está guardando el nuevo orden en el servidor */
const guardando = ref(false);

// ─── Métricas rápidas ─────────────────────────────────────────────────────────

const totalLecciones = computed(() =>
  props.topics.reduce((sum, t) => sum + (t.lessons_count ?? 0), 0)
);

const topicsPublicos = computed(() =>
  props.topics.filter((t) => t.is_public).length
);

const topicsConCategoria = computed(() =>
  props.topics.filter((t) => !!t.exam_category).length
);

// ─── Acciones ─────────────────────────────────────────────────────────────────

/** Navega a la página de edición del topic */
// DESPUÉS — parámetro nombrado correcto
// DESPUÉS — ambos parámetros requeridos
const irAEditar = (topic) => {
  router.get(route('admin.topics.edit', { 
    series: props.serie.id, 
    topic: topic.id 
  }));
};

/** Guarda el nuevo orden tras el drag & drop */
const guardarOrden = (idsOrdenados) => {
  guardando.value = true;

  router.patch(
    route('admin.topics.reorder', props.serie.id),
    { order: idsOrdenados },
    {
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire({
          icon:              'success',
          title:             'Orden guardado',
          timer:             1800,
          showConfirmButton: false,
          timerProgressBar:  true,
        });
      },
      onError: () => {
        Swal.fire({
          icon:               'error',
          title:              'Error al guardar el orden',
          text:               'Intentá de nuevo en un momento.',
          confirmButtonColor: '#6366f1',
        });
      },
      onFinish: () => {
        guardando.value = false;
      },
    }
  );
};

/** Pide confirmación y elimina el topic */
const confirmarEliminar = async (topic) => {
  // Si tiene lecciones, bloquear directamente (el botón ya lo muestra deshabilitado,
  // pero lo verificamos también aquí por seguridad)
  if ((topic.lessons_count ?? 0) > 0) {
    await Swal.fire({
      icon:               'warning',
      title:              'No se puede eliminar',
      html:               `<strong>${topic.title}</strong> tiene
                           ${topic.lessons_count}
                           lección${topic.lessons_count !== 1 ? 'es' : ''} cargada${topic.lessons_count !== 1 ? 's' : ''}.
                           <br><span class="text-sm text-gray-500">
                           Eliminá primero las lecciones desde el editor de contenido.
                           </span>`,
      confirmButtonColor: '#6366f1',
    });
    return;
  }

  const result = await Swal.fire({
    icon:               'warning',
    title:              '¿Eliminar topic?',
    html:               `Vas a eliminar <strong>${topic.title}</strong>.
                         <br><span class="text-sm text-gray-500">
                         Esta acción no se puede deshacer.
                         </span>`,
    showCancelButton:   true,
    confirmButtonText:  'Sí, eliminar',
    cancelButtonText:   'Cancelar',
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#6b7280',
  });

  if (!result.isConfirmed) return;

router.delete(route('admin.topics.destroy', { 
  series: props.serie.id, 
  topic: topic.id 
}), {
  preserveScroll: true,
  onSuccess: () => {
    Swal.fire({
      icon:              'success',
      title:             'Topic eliminado',
      timer:             2000,
      showConfirmButton: false,
      timerProgressBar:  true,
    });
  },
  onError: () => {
    Swal.fire({
      icon:               'error',
      title:              'Error al eliminar',
      text:               'No se pudo eliminar el topic. Intentá de nuevo.',
      confirmButtonColor: '#ef4444',
    });
  },
});
};
</script>