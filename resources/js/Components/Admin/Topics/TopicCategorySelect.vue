<!--
=============================================================================
  TopicCategorySelect.vue
  Ubicación: resources/js/Components/Admin/Topics/TopicCategorySelect.vue
=============================================================================

  ¿QUÉ HACE ESTE COMPONENTE?
  ──────────────────────────
  Permite al administrador conectar un topic con las preguntas del examen
  final de la serie a través del campo "exam_category".

  Esta conexión es el PUENTE central de la plataforma:

      Topic "Señales de Tránsito"
          exam_category = "Señalética"
                ↕  coincide
      Pregunta del examen → category: "Señalética"

  Cuando el admin selecciona una categoría, el componente le muestra
  inmediatamente cuántas preguntas del examen final corresponden a esa
  categoría. Así sabe de antemano si el mini quiz tendrá preguntas o no.

  ESTADOS POSIBLES:
  ─────────────────
    1. Sin examen final publicado en la serie
       → Aviso amarillo explicando que debe existir un examen madre
       → El select queda deshabilitado

    2. Con examen final pero sin categorías en el JSON
       → Aviso naranja: el examen no tiene categorías definidas

    3. Con categorías disponibles
       → Select habilitado con las categorías extraídas del json_schema
       → Al seleccionar: feedback verde con el conteo de preguntas

    4. Categoría seleccionada con 0 preguntas
       → Aviso rojo: la categoría existe pero no tiene preguntas asociadas

  PROPS QUE RECIBE:
  ─────────────────
    modelValue     → string | null — la categoría seleccionada actualmente
    examCategories → array de objetos { category, count }
                     generado por el controlador desde el json_schema
    error          → string | null — mensaje de error de validación

  EMITS:
  ──────
    update:modelValue → cuando el admin cambia la selección

  ¿QUIÉN GENERA examCategories?
  ──────────────────────────────
  El Admin\TopicController@create extrae las categorías únicas del
  json_schema del examen final de la serie:

    $categories = collect($exam->json_schema['questions'] ?? [])
        ->groupBy('category')
        ->map(fn($group, $cat) => ['category' => $cat, 'count' => $group->count()])
        ->values();

  Si no hay examen final publicado, pasa un array vacío y el componente
  lo maneja mostrando el aviso correspondiente.

  DEPENDENCIAS:
  ─────────────
    • Vue 3 Composition API
    • Tailwind CSS
    • Sin librerías externas
=============================================================================
-->

<template>
  <div class="space-y-2">

    <!-- Etiqueta -->
    <label class="block text-sm font-medium text-gray-700">
      Categoría del examen
      <span class="ml-1 text-xs font-normal text-gray-400">(opcional)</span>
    </label>

    <!-- Descripción contextual -->
    <p class="text-xs text-gray-500 leading-relaxed">
      Conecta este topic con las preguntas del examen final de la serie.
      Al finalizar una lección, el estudiante puede responder un mini quiz
      con preguntas de esta categoría.
    </p>

    <!-- ── Estado 1: Sin examen final publicado ── -->
    <div
      v-if="!tieneExamen"
      class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200
             rounded-xl text-sm text-amber-800"
    >
      <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500"
        fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732
             4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <div>
        <p class="font-medium">Esta serie no tiene un examen final publicado</p>
        <p class="text-xs text-amber-700 mt-0.5">
          Para habilitar el mini quiz, primero crea un examen con
          <strong>is_final_exam = true</strong> y publicalo. Podés asignar
          la categoría después desde la edición del topic.
        </p>
      </div>
    </div>

    <!-- ── Estado 2: Examen sin categorías en el JSON ── -->
    <div
      v-else-if="tieneExamen && examCategories.length === 0"
      class="flex items-start gap-3 p-3 bg-orange-50 border border-orange-200
             rounded-xl text-sm text-orange-800"
    >
      <svg class="w-4 h-4 mt-0.5 shrink-0 text-orange-500"
        fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <div>
        <p class="font-medium">El examen final no tiene categorías definidas</p>
        <p class="text-xs text-orange-700 mt-0.5">
          Las preguntas del examen no tienen el campo <strong>category</strong>
          en su JSON. Editá el examen y asigná una categoría a cada pregunta
          para habilitar esta función.
        </p>
      </div>
    </div>

    <!-- ── Estado 3 y 4: Select de categorías ── -->
    <div v-else class="space-y-2">

      <!-- Select -->
      <div class="relative">
        <select
          :value="modelValue"
          @change="$emit('update:modelValue', $event.target.value || null)"
          class="w-full appearance-none border rounded-xl px-3 py-2.5 pr-10
                 text-sm focus:outline-none focus:ring-2 transition-all duration-150
                 bg-white"
          :class="error
            ? 'border-red-400 focus:ring-red-200'
            : 'border-gray-300 focus:ring-indigo-200 focus:border-indigo-400'"
        >
          <option value="">Sin categoría (el mini quiz quedará deshabilitado)</option>
          <option
            v-for="item in examCategories"
            :key="item.category"
            :value="item.category"
          >
            {{ item.category }} — {{ item.count }} pregunta{{ item.count !== 1 ? 's' : '' }}
          </option>
        </select>

        <!-- Chevron decorativo -->
        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>
      </div>

      <!-- Feedback dinámico según la selección -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-1"
      >

        <!-- Sin selección -->
        <div
          v-if="!modelValue"
          class="flex items-center gap-2 text-xs text-gray-400 px-1"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Seleccioná una categoría para habilitar el mini quiz en este topic
        </div>

        <!-- Categoría con preguntas → verde -->
        <div
          v-else-if="preguntasDisponibles > 0"
          class="flex items-center gap-2 text-xs text-green-700 bg-green-50
                 border border-green-200 rounded-lg px-3 py-2"
        >
          <svg class="w-3.5 h-3.5 shrink-0 text-green-500"
            fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span>
            Mini quiz habilitado con
            <strong>{{ preguntasDisponibles }}
            pregunta{{ preguntasDisponibles !== 1 ? 's' : '' }}</strong>
            disponibles en esta categoría.
            Se mostrarán hasta 5 de forma aleatoria.
          </span>
        </div>

        <!-- Categoría con 0 preguntas → rojo -->
        <div
          v-else-if="modelValue && preguntasDisponibles === 0"
          class="flex items-center gap-2 text-xs text-red-700 bg-red-50
                 border border-red-200 rounded-lg px-3 py-2"
        >
          <svg class="w-3.5 h-3.5 shrink-0 text-red-500"
            fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span>
            La categoría <strong>"{{ modelValue }}"</strong> no tiene preguntas
            asociadas en el examen final. El mini quiz no funcionará.
          </span>
        </div>

      </Transition>

    </div>

    <!-- Error de validación del formulario -->
    <p v-if="error" class="flex items-center gap-1 text-xs text-red-600">
      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0
             00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      {{ error }}
    </p>

  </div>
</template>

<script setup>
import { computed } from 'vue';

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  /**
   * La categoría actualmente seleccionada.
   * null o string vacío = sin categoría asignada.
   */
  modelValue: {
    type: String,
    default: null,
  },

  /**
   * Lista de categorías únicas extraídas del json_schema del examen final.
   * Si está vacío puede significar:
   *   a) No hay examen final publicado en la serie
   *   b) El examen no tiene el campo 'category' en sus preguntas
   *
   * Estructura de cada elemento:
   *   { category: "Señalética", count: 4 }
   */
  examCategories: {
    type: Array,
    default: () => [],
  },

  /**
   * Indica si la serie tiene un examen final publicado.
   * Lo determina el controlador antes de pasar los datos a la vista.
   * Controla si mostramos el select o el aviso de "sin examen final".
   */
  tieneExamen: {
    type: Boolean,
    default: false,
  },

  /**
   * Mensaje de error de validación (viene de form.errors.exam_category).
   */
  error: {
    type: String,
    default: null,
  },
});

// ─── Emits ────────────────────────────────────────────────────────────────────

defineEmits(['update:modelValue']);

// ─── Computadas ───────────────────────────────────────────────────────────────

/**
 * Cuántas preguntas tiene disponibles la categoría seleccionada.
 * Se usa para el feedback visual (verde / rojo).
 * Devuelve 0 si no hay selección o si la categoría no se encuentra.
 */
const preguntasDisponibles = computed(() => {
  if (!props.modelValue) return 0;
  const match = props.examCategories.find(
    (item) => item.category === props.modelValue
  );
  return match?.count ?? 0;
});
</script>