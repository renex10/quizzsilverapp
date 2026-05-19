<!--
=============================================================================
  Admin/Topics/Show.vue
  Ubicación: resources/js/Pages/Admin/Topics/Show.vue
=============================================================================

  ¿QUÉ HACE ESTA VISTA?
  ──────────────────────
  Es el centro de operaciones de un topic. Desde aquí el admin puede:

    1. Ver el resumen del topic (título, categoría, visibilidad, cobertura)
    2. Gestionar todas sus lecciones (crear, editar, reordenar, eliminar)
    3. Acceder rápidamente a la edición del topic sin salir del contexto

  POR QUÉ ES UNA VISTA SEPARADA Y NO PARTE DE EDIT:
  ───────────────────────────────────────────────────
  Edit.vue es para cambiar los metadatos del topic (título, color, categoría).
  Show.vue es para gestionar el CONTENIDO del topic (las lecciones).
  Son responsabilidades diferentes — mantenerlas separadas evita
  que Edit.vue se vuelva una vista sobrecargada.

  FLUJO DE LLEGADA:
  ──────────────────
  Admin/Topics/Index.vue → click en ícono 👁 o en el título del topic
      ↓
  Admin/Topics/Show.vue  ← estamos aquí
      ↓
  LessonModal (se abre sobre esta vista, no navega a otra página)

  PROPS QUE RECIBE (desde TopicController@show):
  ───────────────────────────────────────────────
    topic → objeto completo con sus lecciones (ver detalle en script)

  LO QUE NO HACE:
  ────────────────
  ✘ No edita los metadatos del topic (eso es Edit.vue)
  ✘ No navega fuera de la página al gestionar lecciones (todo es modal)
  ✘ No muestra el contenido de las lecciones al estudiante (eso es Student/Topic/Show.vue)
=============================================================================
-->

<template>
  <AdminLayout :title="topic.title">

    <!-- ── Encabezado ──────────────────────────────────────────────────── -->
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <!-- Breadcrumb + título -->
        <div class="flex items-center gap-3">
          <Link
            :href="route('admin.topics.index', { series: topic.series.id })"
            class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600
                   transition-all duration-150"
            title="Volver a topics"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 19l-7-7 7-7"/>
            </svg>
          </Link>

          <div>
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-0.5 flex-wrap">
              <span>{{ topic.series.title }}</span>
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
              <Link
                :href="route('admin.topics.index', { series: topic.series.id })"
                class="hover:text-gray-600 transition-colors"
              >Topics</Link>
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
              <span class="text-gray-600 font-medium truncate max-w-[180px]">
                {{ topic.title }}
              </span>
            </div>

            <!-- Título con ícono del topic -->
            <div class="flex items-center gap-2">
              <span
                class="w-8 h-8 rounded-lg flex items-center justify-center text-lg shrink-0"
                :style="{ backgroundColor: (topic.color || '#6366f1') + '20' }"
              >
                {{ topic.icon || '📄' }}
              </span>
              <h2 class="text-2xl font-bold text-gray-800 leading-tight">
                {{ topic.title }}
              </h2>
            </div>
          </div>
        </div>

        <!-- Acciones del header -->
        <div class="flex items-center gap-3">

          <!-- Botón editar metadatos del topic -->
          <Link
            :href="route('admin.topics.edit', { series: topic.series.id, topic: topic.id })"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600
                   border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                   m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Editar topic
          </Link>

          <!-- Botón nueva lección -->
          <button
            type="button"
            @click="abrirModalNueva"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600
                   hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl
                   transition-colors shadow-sm hover:shadow-md"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva lección
          </button>

        </div>
      </div>
    </template>

    <!-- ── Cuerpo principal ─────────────────────────────────────────────── -->
    <div class="space-y-6 max-w-3xl">

      <!-- ── Tarjetas de resumen del topic ────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
          <p class="text-2xl font-black text-gray-800">{{ lecciones.length }}</p>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ lecciones.length === 1 ? 'Lección' : 'Lecciones' }}
          </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
          <p class="text-2xl font-black text-indigo-600">{{ leccionesPreview }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Preview público</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
          <p class="text-2xl font-black text-gray-800">{{ minutosTotales }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Minutos totales</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
          <div
            class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1
                   rounded-full"
            :class="topic.is_public
              ? 'bg-green-100 text-green-700'
              : 'bg-gray-100 text-gray-500'"
          >
            <span
              class="w-1.5 h-1.5 rounded-full"
              :class="topic.is_public ? 'bg-green-500' : 'bg-gray-400'"
            ></span>
            {{ topic.is_public ? 'Público' : 'Privado' }}
          </div>
          <p class="text-xs text-gray-500 mt-1.5">Visibilidad</p>
        </div>

      </div>

      <!-- ── Chip de categoría del examen ─────────────────────────────── -->
      <div
        v-if="topic.exam_category"
        class="flex items-center gap-2 px-4 py-2.5 bg-indigo-50 border
               border-indigo-200 rounded-xl text-sm text-indigo-700"
      >
        <svg class="w-4 h-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101
               m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>
        <span>
          Mini quiz conectado a la categoría
          <strong>"{{ topic.exam_category }}"</strong>
          del examen final
        </span>
      </div>

      <!-- ── Lista de lecciones ────────────────────────────────────────── -->
      <div>
        <h3 class="text-base font-semibold text-gray-800 mb-3">
          Lecciones
          <span class="text-sm font-normal text-gray-400 ml-1">
            ({{ lecciones.length }})
          </span>
        </h3>

        <LessonList
          :lessons="lecciones"
          :guardando="guardandoOrden"
          @reordered="guardarOrdenLecciones"
          @edit-lesson="abrirModalEditar"
          @delete-lesson="confirmarEliminarLeccion"
          @nueva-leccion="abrirModalNueva"
        />
      </div>

    </div>

    <!-- ── Modal de lección (crear / editar) ───────────────────────────── -->
    <LessonModal
      v-model="modalAbierto"
      :lesson="leccionSeleccionada"
      :topic-id="topic.id"
      :next-order="nextOrder"
      @saved="onLeccionGuardada"
    />

  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import LessonList  from '@/Components/Admin/Topics/Lessons/LessonList.vue';
import LessonModal from '@/Components/Admin/Topics/Lessons/LessonModal.vue';

// ─── Props desde TopicController@show ─────────────────────────────────────────

const props = defineProps({
  /**
   * Objeto del topic con sus lecciones cargadas.
   * {
   *   id, title, slug, description, icon, color,
   *   order, is_public, exam_category, cover_url,
   *   series: { id, title, slug },
   *   lessons: [{ id, title, slug, order, duration_minutes, is_preview, excerpt }]
   * }
   */
  topic: { type: Object, required: true },
});

// ─── Estado local ─────────────────────────────────────────────────────────────

const lecciones          = ref([...props.topic.lessons]);
const modalAbierto       = ref(false);
const leccionSeleccionada = ref(null);
const guardandoOrden     = ref(false);

// ─── Computadas ───────────────────────────────────────────────────────────────

const leccionesPreview = computed(() =>
  lecciones.value.filter((l) => l.is_preview).length
);

const minutosTotales = computed(() =>
  lecciones.value.reduce((sum, l) => sum + (l.duration_minutes ?? 0), 0)
);

const nextOrder = computed(() =>
  lecciones.value.length > 0
    ? Math.max(...lecciones.value.map((l) => l.order)) + 1
    : 1
);

// ─── Modal ────────────────────────────────────────────────────────────────────

const abrirModalNueva = () => {
  leccionSeleccionada.value = null;
  modalAbierto.value = true;
};

const abrirModalEditar = (leccion) => {
  leccionSeleccionada.value = leccion;
  modalAbierto.value = true;
};

/** Después de guardar, recargamos la página para obtener datos frescos */
const onLeccionGuardada = () => {
  router.reload({ only: ['topic'] });
};

// ─── Reordenamiento ───────────────────────────────────────────────────────────

const guardarOrdenLecciones = (idsOrdenados) => {
  guardandoOrden.value = true;

  router.patch(
    route('admin.lessons.reorder', { topic: props.topic.id }),
    { order: idsOrdenados },
    {
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire({
          icon: 'success', title: 'Orden guardado',
          timer: 1800, showConfirmButton: false, timerProgressBar: true,
        });
      },
      onError: () => {
        Swal.fire({
          icon: 'error', title: 'Error al guardar el orden',
          confirmButtonColor: '#6366f1',
        });
      },
      onFinish: () => { guardandoOrden.value = false; },
    }
  );
};

// ─── Eliminar lección ─────────────────────────────────────────────────────────

const confirmarEliminarLeccion = async (leccion) => {
  const result = await Swal.fire({
    icon:              'warning',
    title:             '¿Eliminar lección?',
    html:              `Vas a eliminar <strong>${leccion.title}</strong>.
                        <br><span class="text-sm text-gray-500">
                        Esta acción no se puede deshacer.
                        </span>`,
    showCancelButton:  true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText:  'Cancelar',
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#6b7280',
  });

  if (!result.isConfirmed) return;

  router.delete(
    route('admin.lessons.destroy', { topic: props.topic.id, lesson: leccion.id }),
    {
      preserveScroll: true,
      onSuccess: () => {
        lecciones.value = lecciones.value.filter((l) => l.id !== leccion.id);
        Swal.fire({
          icon: 'success', title: 'Lección eliminada',
          timer: 2000, showConfirmButton: false, timerProgressBar: true,
        });
      },
      onError: () => {
        Swal.fire({
          icon: 'error', title: 'Error al eliminar',
          text: 'No se pudo eliminar la lección.',
          confirmButtonColor: '#ef4444',
        });
      },
    }
  );
};
</script>