<!--
=============================================================================
  TopicCard.vue
  Ubicación: resources/js/Components/Admin/Topics/TopicCard.vue
=============================================================================

  ¿QUÉ HACE ESTE COMPONENTE?
  ──────────────────────────
  Representa visualmente UN topic dentro del listado de topics de una serie.
  Es la "tarjeta" que el administrador ve en Admin/Topics/Index.vue.

  Cada card muestra de un vistazo:
    • El ícono y color del topic (identidad visual en la landing)
    • El título y descripción corta
    • La categoría del examen a la que está conectado (exam_category)
    • Cuántas lecciones tiene cargadas
    • Si está visible en la landing pública o no (is_public)
    • El orden dentro de la serie

  ACCIONES QUE EXPONE:
  ────────────────────
    @edit   → el padre navega a Admin/Topics/Edit.vue
    @delete → el padre pide confirmación y hace DELETE
    @drag   → el padre actualiza el orden (drag & drop en TopicSortableList)

  NAVEGACIÓN DIRECTA:
  ───────────────────
    Botón 👁 → navega a Admin/Topics/Show.vue (gestión de lecciones)
    Requiere la prop seriesId para construir la ruta anidada correctamente.

  PROPS QUE RECIBE:
  ─────────────────
    topic    → objeto con los datos del topic
    seriesId → ID de la serie padre (requerido para rutas anidadas Ziggy)

  DEPENDENCIAS:
  ─────────────
    • Link de @inertiajs/vue3 (para navegación SPA)
    • Tailwind CSS
    • Vue 3 Composition API
=============================================================================
-->

<template>
  <div
    class="group relative bg-white rounded-2xl border border-gray-200
           shadow-sm hover:shadow-md hover:border-gray-300
           transition-all duration-200 overflow-hidden"
  >

    <!-- Franja de color del topic (izquierda) -->
    <div
      class="absolute left-0 top-0 bottom-0 w-1.5 rounded-l-2xl transition-all
             duration-300 group-hover:w-2"
      :style="{ backgroundColor: topic.color || '#6366f1' }"
    ></div>

    <!-- Contenido principal -->
    <div class="pl-5 pr-4 py-4">

      <!-- Fila superior: ícono + título + badge + acciones rápidas -->
      <div class="flex items-start gap-3">

        <!-- Ícono del topic -->
        <div
          class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center
                 text-xl shadow-sm"
          :style="{ backgroundColor: colorSuave }"
        >
          {{ topic.icon || '📄' }}
        </div>

        <!-- Título y descripción -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-sm font-semibold text-gray-900 truncate">
              {{ topic.title }}
            </h3>

            <!-- Badge: visible en landing / solo registrados -->
            <span
              class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5
                     rounded-full text-xs font-medium"
              :class="topic.is_public
                ? 'bg-green-100 text-green-700'
                : 'bg-gray-100 text-gray-500'"
            >
              <span
                class="w-1.5 h-1.5 rounded-full"
                :class="topic.is_public ? 'bg-green-500' : 'bg-gray-400'"
              ></span>
              {{ topic.is_public ? 'Público' : 'Solo registrados' }}
            </span>
          </div>

          <p
            v-if="topic.description"
            class="text-xs text-gray-500 mt-0.5 line-clamp-2 leading-relaxed"
          >
            {{ topic.description }}
          </p>
        </div>

        <!-- Botón ver lecciones — navega a Topics/Show.vue -->
        <Link
          :href="route('admin.topics.show', { series: seriesId, topic: topic.id })"
          class="shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-purple-600
                 hover:bg-purple-50 transition-colors"
          title="Gestionar lecciones"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542
                 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
        </Link>

        <!-- Handle de drag (solo visible al hacer hover) -->
        <div
          class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity
                 duration-150 cursor-grab active:cursor-grabbing p-1
                 text-gray-400 hover:text-gray-600"
          :draggable="true"
          @dragstart="$emit('drag', topic.id)"
          title="Arrastrar para reordenar"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0
                     0 0-4zM7 9a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2
                     0 0 0 0-4zM7 16a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4
                     2 2 0 0 0 0-4z"/>
          </svg>
        </div>

      </div>

      <!-- Fila inferior: metadatos + acciones -->
      <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">

        <!-- Metadatos -->
        <div class="flex items-center gap-3 text-xs text-gray-400 flex-wrap">

          <!-- Orden dentro de la serie -->
          <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
            </svg>
            Orden {{ topic.order }}
          </span>

          <!-- Cantidad de lecciones -->
          <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0
                   01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>
            </svg>
            {{ topic.lessons_count ?? 0 }}
            {{ (topic.lessons_count ?? 0) === 1 ? 'lección' : 'lecciones' }}
          </span>

          <!-- Categoría del examen (si existe) -->
          <span
            v-if="topic.exam_category"
            class="flex items-center gap-1 bg-indigo-50 text-indigo-600
                   px-2 py-0.5 rounded-full"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101
                   m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            {{ topic.exam_category }}
          </span>

          <!-- Aviso si no tiene categoría asignada -->
          <span
            v-else
            class="flex items-center gap-1 text-amber-500"
            title="Sin categoría — el mini quiz no funcionará hasta asignar una"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
                   1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34
                   16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Sin categoría
          </span>

        </div>

        <!-- Acciones: editar y eliminar -->
        <div class="flex items-center gap-1 shrink-0">

          <!-- Botón editar -->
          <button
            type="button"
            @click="$emit('edit', topic)"
            class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600
                   hover:bg-indigo-50 transition-colors"
            title="Editar topic"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                   m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>

          <!-- Botón eliminar -->
          <button
            type="button"
            @click="$emit('delete', topic)"
            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600
                   hover:bg-red-50 transition-colors"
            :title="tieneContenido
              ? 'No se puede eliminar — tiene lecciones cargadas'
              : 'Eliminar topic'"
            :disabled="tieneContenido"
            :class="{ 'opacity-40 cursor-not-allowed': tieneContenido }"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                   L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>

        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  /**
   * Objeto del topic con los datos a mostrar.
   * {
   *   id, title, slug, description, icon, color,
   *   order, is_public, exam_category, lessons_count
   * }
   */
  topic: {
    type: Object,
    required: true,
  },

  /**
   * ID de la serie padre.
   * Requerido para construir la ruta anidada admin.topics.show
   * que necesita { series: id, topic: id }.
   * Se pasa en cascada desde Index.vue → TopicSortableList → TopicCard.
   */
  seriesId: {
    type: Number,
    required: true,
  },
});

// ─── Emits ────────────────────────────────────────────────────────────────────

defineEmits([
  'edit',    // payload: topic object  → el padre navega a Edit
  'delete',  // payload: topic object  → el padre pide confirmación
  'drag',    // payload: topic.id      → el padre maneja el reordenamiento
]);

// ─── Computadas ───────────────────────────────────────────────────────────────

const colorSuave = computed(() => {
  const hex = props.topic.color || '#6366f1';
  return hex + '20';
});

const tieneContenido = computed(() => (props.topic.lessons_count ?? 0) > 0);
</script>