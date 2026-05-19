<!--
=============================================================================
  TopicForm.vue
  Ubicación: resources/js/Components/Admin/Topics/TopicForm.vue
=============================================================================

  ¿QUÉ HACE ESTE COMPONENTE?
  ──────────────────────────
  Contiene los campos de texto base de un topic: título, descripción y orden.
  Es el bloque más simple del formulario pero el más importante, ya que el
  título es el único campo verdaderamente obligatorio.

  Se diseñó como componente separado por dos razones:
    1. Se reutiliza idéntico en Create.vue y Edit.vue sin duplicar código.
    2. Mantiene la vista padre limpia — ella solo orquesta, este componente
       se ocupa del HTML y la validación visual de estos tres campos.

  PATRÓN QUE USA:
  ───────────────
  Usa v-model con un objeto completo (modelValue) en lugar de props
  individuales por campo. Esto permite que el padre pase directamente
  el objeto `form` de Inertia useForm() y este componente lo actualice
  campo a campo sin romper la reactividad.

  El componente NUNCA modifica el modelValue directamente.
  Siempre emite 'update:modelValue' con una copia actualizada del objeto.
  Esto sigue el principio de flujo de datos unidireccional de Vue.

  CAMPOS QUE MANEJA:
  ──────────────────
    title       → Requerido. Nombre del topic. Ej: "Señales de Tránsito"
    description → Opcional. Texto corto para cards en la landing (máx ~200 chars).
    order       → Requerido. Número entero >= 1. Define la posición del topic
                  dentro de la serie. El admin puede reordenar visualmente
                  después desde el Index con drag & drop, pero el número aquí
                  establece el punto de partida.

  PROPS QUE RECIBE:
  ─────────────────
    modelValue → objeto con al menos { title, description, order }
    errors     → objeto con los errores de validación (form.errors de Inertia)
    nextOrder  → número sugerido para el campo orden (el siguiente disponible
                 en la serie — lo calcula el controlador y evita que el admin
                 tenga que adivinarlo)

  EMITS:
  ──────
    update:modelValue → cuando cualquier campo cambia

  DEPENDENCIAS:
  ─────────────
    • Vue 3 Composition API
    • Tailwind CSS
    • Sin librerías externas
=============================================================================
-->

<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

    <!-- Encabezado de la sección -->
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
      <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
        <span
          class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-md flex items-center
                 justify-center text-xs font-bold"
        >1</span>
        Información del topic
      </h3>
      <p class="text-xs text-gray-500 mt-0.5 ml-8">
        Datos básicos que identifican el topic dentro de la serie
      </p>
    </div>

    <!-- Campos -->
    <div class="px-6 py-5 space-y-5">

      <!-- Título -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Título
          <span class="text-red-500 ml-0.5">*</span>
        </label>
        <input
          type="text"
          :value="modelValue.title"
          @input="update('title', $event.target.value)"
          placeholder="Ej. Señales de Tránsito"
          maxlength="255"
          class="w-full border rounded-xl px-3 py-2.5 text-sm focus:outline-none
                 focus:ring-2 transition-all duration-150"
          :class="errors.title
            ? 'border-red-400 bg-red-50 focus:ring-red-200'
            : 'border-gray-300 bg-white focus:ring-indigo-200 focus:border-indigo-400'"
        />

        <!-- Contador de caracteres + error -->
        <div class="flex items-center justify-between mt-1">
          <p v-if="errors.title" class="text-xs text-red-600 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0
                   012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clip-rule="evenodd"/>
            </svg>
            {{ errors.title }}
          </p>
          <span v-else class="text-xs text-gray-400">
            El título aparece en la card del topic y en la URL de la landing
          </span>
          <span
            class="text-xs shrink-0 ml-2"
            :class="(modelValue.title?.length ?? 0) > 220
              ? 'text-amber-500'
              : 'text-gray-400'"
          >
            {{ modelValue.title?.length ?? 0 }}/255
          </span>
        </div>
      </div>

      <!-- Descripción -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Descripción
          <span class="text-xs font-normal text-gray-400 ml-1">(opcional)</span>
        </label>
        <textarea
          :value="modelValue.description"
          @input="update('description', $event.target.value)"
          placeholder="Ej. Señales reglamentarias, preventivas e informativas. Forma, color y significado de cada tipo."
          rows="3"
          maxlength="500"
          class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm
                 focus:outline-none focus:ring-2 focus:ring-indigo-200
                 focus:border-indigo-400 transition-all duration-150 resize-none"
          :class="errors.description ? 'border-red-400 bg-red-50' : 'border-gray-300'"
        ></textarea>

        <div class="flex items-center justify-between mt-1">
          <p v-if="errors.description" class="text-xs text-red-600">
            {{ errors.description }}
          </p>
          <span v-else class="text-xs text-gray-400">
            Texto corto para la card — se ve en la landing y en el catálogo
          </span>
          <span
            class="text-xs shrink-0 ml-2"
            :class="(modelValue.description?.length ?? 0) > 450
              ? 'text-amber-500'
              : 'text-gray-400'"
          >
            {{ modelValue.description?.length ?? 0 }}/500
          </span>
        </div>
      </div>

      <!-- Orden -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Orden dentro de la serie
          <span class="text-red-500 ml-0.5">*</span>
        </label>

        <div class="flex items-center gap-3">
          <input
            type="number"
            :value="modelValue.order"
            @input="update('order', parseInt($event.target.value) || 1)"
            min="1"
            max="99"
            class="w-24 border rounded-xl px-3 py-2.5 text-sm text-center
                   focus:outline-none focus:ring-2 transition-all duration-150"
            :class="errors.order
              ? 'border-red-400 bg-red-50 focus:ring-red-200'
              : 'border-gray-300 bg-white focus:ring-indigo-200 focus:border-indigo-400'"
          />

          <!-- Sugerencia de orden -->
          <div
            v-if="nextOrder && modelValue.order !== nextOrder"
            class="flex items-center gap-1.5 text-xs text-gray-400"
          >
            <span>El siguiente disponible es</span>
            <button
              type="button"
              @click="update('order', nextOrder)"
              class="text-indigo-600 hover:text-indigo-700 font-medium
                     underline underline-offset-2 transition-colors"
            >
              {{ nextOrder }}
            </button>
          </div>

          <span
            v-else-if="nextOrder && modelValue.order === nextOrder"
            class="flex items-center gap-1 text-xs text-green-600"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5 13l4 4L19 7"/>
            </svg>
            Siguiente disponible
          </span>
        </div>

        <p v-if="errors.order" class="mt-1 text-xs text-red-600">
          {{ errors.order }}
        </p>
        <p v-else class="mt-1 text-xs text-gray-400">
          Define en qué posición aparece el topic dentro de la serie.
          Podés reordenarlos visualmente desde el listado con drag & drop.
        </p>
      </div>

    </div>
  </div>
</template>

<script setup>
// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  /**
   * Objeto del formulario. Se usa como v-model en la vista padre.
   * Se espera que tenga al menos: { title, description, order }
   *
   * El componente trabaja con una copia — nunca muta el prop directamente.
   * Siempre emite 'update:modelValue' con el objeto actualizado.
   */
  modelValue: {
    type: Object,
    required: true,
  },

  /**
   * Errores de validación del formulario (form.errors de Inertia).
   * Puede tener: { title: "...", description: "...", order: "..." }
   */
  errors: {
    type: Object,
    default: () => ({}),
  },

  /**
   * El siguiente número de orden disponible en la serie.
   * Lo calcula el controlador: Topic::where('series_id', $id)->max('order') + 1
   * Si no se pasa, el campo de sugerencia no aparece.
   */
  nextOrder: {
    type: Number,
    default: null,
  },
});

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits(['update:modelValue']);

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Actualiza un campo del modelValue sin mutar el prop.
 * Emite una copia nueva del objeto con el campo modificado.
 *
 * @param {string} field - nombre del campo a actualizar
 * @param {*}      value - nuevo valor del campo
 */
const update = (field, value) => {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
};
</script>