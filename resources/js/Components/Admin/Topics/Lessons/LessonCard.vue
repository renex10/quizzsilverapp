<template>
  <div
    class="group relative bg-white rounded-xl border border-gray-200
           shadow-sm hover:shadow-md hover:border-gray-300
           transition-all duration-200 overflow-hidden"
  >
    <!-- Indicador de orden (barra izquierda numerada) -->
    <div
      class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b
             from-indigo-400 to-purple-500 rounded-l-xl"
    ></div>
 
    <div class="pl-4 pr-3 py-3">
      <div class="flex items-start gap-3">
 
        <!-- Número de orden -->
        <div
          class="shrink-0 w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600
                 text-xs font-bold flex items-center justify-center mt-0.5"
        >
          {{ lesson.order }}
        </div>
 
        <!-- Contenido principal -->
        <div class="flex-1 min-w-0">
 
          <!-- Fila superior: título + badges -->
          <div class="flex items-start gap-2 flex-wrap">
            <h4 class="text-sm font-semibold text-gray-900 leading-snug flex-1 min-w-0">
              {{ lesson.title }}
            </h4>
 
            <!-- Badge: preview público -->
            <span
              class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5
                     rounded-full text-xs font-medium"
              :class="lesson.is_preview
                ? 'bg-green-100 text-green-700'
                : 'bg-gray-100 text-gray-500'"
            >
              <span
                class="w-1.5 h-1.5 rounded-full"
                :class="lesson.is_preview ? 'bg-green-500' : 'bg-gray-400'"
              ></span>
              {{ lesson.is_preview ? 'Preview público' : 'Solo registrados' }}
            </span>
          </div>
 
          <!-- Extracto del contenido -->
          <p
            v-if="lesson.excerpt"
            class="text-xs text-gray-400 mt-0.5 line-clamp-1 leading-relaxed"
          >
            {{ lesson.excerpt }}
          </p>
 
          <!-- Metadatos inferiores -->
          <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
 
            <!-- Duración -->
            <span class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ lesson.duration_minutes ?? 5 }} min
            </span>
 
            <!-- Slug (referencia técnica) -->
            <span class="font-mono truncate max-w-[140px] hidden sm:block">
              /{{ lesson.slug }}
            </span>
 
          </div>
        </div>
 
        <!-- Acciones + handle de drag -->
        <div class="shrink-0 flex items-center gap-1">
 
          <!-- Botón editar -->
          <button
            type="button"
            @click="$emit('edit', lesson)"
            class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600
                   hover:bg-indigo-50 transition-colors"
            title="Editar lección"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                   m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>
 
          <!-- Botón eliminar -->
          <button
            type="button"
            @click="$emit('delete', lesson)"
            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600
                   hover:bg-red-50 transition-colors"
            title="Eliminar lección"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                   L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
 
          <!-- Handle de drag -->
          <div
            class="opacity-0 group-hover:opacity-100 transition-opacity duration-150
                   cursor-grab active:cursor-grabbing p-1.5 text-gray-300
                   hover:text-gray-500"
            :draggable="true"
            @dragstart="$emit('drag', lesson.id)"
            title="Arrastrar para reordenar"
          >
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7
                       9a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 16a2
                       2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
            </svg>
          </div>
 
        </div>
      </div>
    </div>
  </div>
</template>
 
<script setup>
defineProps({
  /**
   * Objeto de la lección a mostrar.
   * {
   *   id:               number,
   *   title:            string,
   *   slug:             string,
   *   order:            number,
   *   duration_minutes: number,
   *   is_preview:       boolean,
   *   excerpt:          string | null,  ← primeras palabras del contenido Markdown
   * }
   */
  lesson: {
    type: Object,
    required: true,
  },
});
 
defineEmits(['edit', 'delete', 'drag']);
</script>
