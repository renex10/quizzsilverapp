<!--
=============================================================================
  TopicVisibility.vue
  Ubicación: resources/js/Components/Admin/Topics/TopicVisibility.vue
=============================================================================

  ¿QUÉ HACE ESTE COMPONENTE?
  ──────────────────────────
  Controla el campo is_public del topic mediante un toggle switch.
  Este campo determina si el topic es visible en la landing pública
  (sin necesidad de estar registrado) o si solo lo ven los estudiantes
  autenticados.

  ES MÁS QUE UN SIMPLE TOGGLE:
  ─────────────────────────────
  El componente explica con claridad las CONSECUENCIAS de cada estado.
  El administrador no tiene que saber qué significa "is_public = true"
  técnicamente — el componente le muestra exactamente qué verá cada
  tipo de usuario.

  ESTADO OFF (is_public = false):
    → El topic solo aparece dentro del catálogo para estudiantes registrados
    → No es visible en la landing pública
    → Caso de uso: contenido avanzado, material de práctica interno

  ESTADO ON (is_public = true):
    → El topic aparece en la landing pública SIN necesidad de login
    → Los visitantes pueden ver las lecciones marcadas como is_preview
    → Es el "gancho" para invitar a los visitantes a registrarse
    → Caso de uso: topics introductorios, contenido de muestra

  DECISIÓN DE DISEÑO:
  ───────────────────
  Se decidió crear este componente separado (en lugar de un simple
  checkbox en TopicForm) porque:

    1. La explicación contextual es larga y específica — inline en el
       formulario principal ensuciaría el layout.

    2. La lógica visual del toggle (colores, animaciones, estados)
       es suficiente para justificar su propio componente.

    3. En el futuro podrían agregarse condiciones más complejas:
       "Requiere is_featured en la serie para ser visible" o
       "Mínimo una lesson con is_preview para poder publicar".
       Con este componente separado, esas validaciones se agregan aquí
       sin tocar el formulario principal.

  PROPS QUE RECIBE:
  ─────────────────
    modelValue   → boolean — el estado actual de is_public
    seriePublica → boolean — si la serie padre está publicada (published_at != null)
                             Si la serie no está publicada, el topic público
                             tampoco será visible aunque is_public = true.
                             El componente avisa esta situación.
    previewCount → number  — cantidad de lessons con is_preview = true
                             Si is_public = true pero no hay lecciones preview,
                             el visitante verá el topic vacío. El componente
                             también avisa esto.

  EMITS:
  ──────
    update:modelValue → cuando el admin cambia el estado del toggle

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
    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/60">
      <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
        <span
          class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-md flex items-center
                 justify-center text-xs font-bold"
        >3</span>
        Visibilidad
      </h3>
      <p class="text-xs text-gray-500 mt-0.5 ml-8">
        Define quién puede ver este topic
      </p>
    </div>

    <div class="px-5 py-5 space-y-4">

      <!-- ── Toggle principal ──────────────────────────────────────────── -->
      <div class="flex items-start justify-between gap-4">

        <!-- Etiqueta y descripción -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-800">
            Visible en la landing pública
          </p>
          <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
            Los visitantes sin cuenta podrán ver este topic y sus
            lecciones de preview
          </p>
        </div>

        <!-- Toggle switch -->
        <button
          type="button"
          role="switch"
          :aria-checked="modelValue"
          @click="$emit('update:modelValue', !modelValue)"
          class="shrink-0 relative inline-flex h-6 w-11 items-center rounded-full
                 transition-colors duration-300 focus:outline-none focus:ring-2
                 focus:ring-indigo-400 focus:ring-offset-2"
          :class="modelValue ? 'bg-indigo-600' : 'bg-gray-300'"
        >
          <!-- Círculo del toggle -->
          <span
            class="inline-block h-4 w-4 rounded-full bg-white shadow-sm
                   transform transition-transform duration-300"
            :class="modelValue ? 'translate-x-6' : 'translate-x-1'"
          ></span>
        </button>

      </div>

      <!-- ── Panel de estado — cambia según el valor del toggle ─────────── -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
        mode="out-in"
      >

        <!-- ESTADO: Público (is_public = true) -->
        <div
          v-if="modelValue"
          key="publico"
          class="rounded-xl border border-green-200 bg-green-50 p-4 space-y-3"
        >
          <!-- Indicador de estado -->
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
            <p class="text-xs font-semibold text-green-700">Visible públicamente</p>
          </div>

          <!-- Quién ve qué -->
          <div class="space-y-2">
            <div class="flex items-start gap-2 text-xs text-green-800">
              <span class="shrink-0 mt-0.5">👥</span>
              <span>
                <strong>Visitantes sin cuenta:</strong> ven el topic y las
                lecciones con <em>is_preview = true</em>. Al terminar se los
                invita a registrarse para ver el contenido completo.
              </span>
            </div>
            <div class="flex items-start gap-2 text-xs text-green-800">
              <span class="shrink-0 mt-0.5">🎓</span>
              <span>
                <strong>Estudiantes registrados:</strong> ven todas las lecciones
                y pueden hacer el mini quiz de refuerzo.
              </span>
            </div>
          </div>

          <!-- Advertencia: serie no publicada -->
          <div
            v-if="!seriePublica"
            class="flex items-start gap-2 p-2.5 bg-amber-50 border border-amber-200
                   rounded-lg"
          >
            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-500"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
                   1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34
                   16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-xs text-amber-700">
              La serie padre no está publicada. Este topic no será visible
              en la landing aunque esté marcado como público. Publicá la serie
              desde su formulario de edición.
            </p>
          </div>

          <!-- Advertencia: sin lecciones preview -->
          <div
            v-else-if="previewCount === 0"
            class="flex items-start gap-2 p-2.5 bg-amber-50 border border-amber-200
                   rounded-lg"
          >
            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-500"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
                   1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34
                   16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-xs text-amber-700">
              No hay lecciones marcadas como preview. Los visitantes verán
              el topic pero sin contenido de muestra. Marcá al menos una
              lección como <em>"visible sin login"</em> desde el editor de lecciones.
            </p>
          </div>

          <!-- Todo OK: tiene lecciones preview -->
          <div
            v-else
            class="flex items-center gap-2 text-xs text-green-700"
          >
            <svg class="w-3.5 h-3.5 text-green-500 shrink-0"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>
              <strong>{{ previewCount }}</strong>
              lección{{ previewCount !== 1 ? 'es' : '' }} de preview disponible{{ previewCount !== 1 ? 's' : '' }}
              para visitantes
            </span>
          </div>

        </div>

        <!-- ESTADO: Privado (is_public = false) -->
        <div
          v-else
          key="privado"
          class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-2"
        >
          <!-- Indicador de estado -->
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-gray-400"></div>
            <p class="text-xs font-semibold text-gray-600">Solo para registrados</p>
          </div>

          <!-- Quién ve qué -->
          <div class="space-y-2">
            <div class="flex items-start gap-2 text-xs text-gray-600">
              <span class="shrink-0 mt-0.5">👥</span>
              <span>
                <strong>Visitantes sin cuenta:</strong> no ven este topic
                en la landing pública.
              </span>
            </div>
            <div class="flex items-start gap-2 text-xs text-gray-600">
              <span class="shrink-0 mt-0.5">🎓</span>
              <span>
                <strong>Estudiantes registrados:</strong> ven el topic completo
                con todas las lecciones y el mini quiz.
              </span>
            </div>
          </div>

          <!-- Sugerencia si es el primer topic de la serie -->
          <div
            v-if="esPrimero"
            class="flex items-start gap-2 p-2.5 bg-indigo-50 border border-indigo-200
                   rounded-lg mt-1"
          >
            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-indigo-500"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-indigo-700">
              Considerá hacer público al menos el primer topic de la serie.
              Los visitantes podrán ver una muestra del contenido y se
              sentirán más motivados a registrarse.
            </p>
          </div>

        </div>

      </Transition>

    </div>
  </div>
</template>

<script setup>
// ─── Props ────────────────────────────────────────────────────────────────────

defineProps({
  /**
   * Estado actual del campo is_public.
   * true  → visible en la landing sin login
   * false → solo para estudiantes registrados
   */
  modelValue: {
    type: Boolean,
    default: false,
  },

  /**
   * Si la serie padre tiene published_at != null.
   * Un topic público en una serie no publicada no aparece en la landing.
   * El componente avisa esta situación para que el admin lo sepa.
   */
  seriePublica: {
    type: Boolean,
    default: false,
  },

  /**
   * Cantidad de lecciones del topic que tienen is_preview = true.
   * Si is_public = true pero previewCount = 0, el visitante verá
   * el topic vacío. El componente advierte sobre esta situación.
   * En Create.vue siempre será 0 (aún no hay lecciones).
   * En Edit.vue refleja el estado real.
   */
  previewCount: {
    type: Number,
    default: 0,
  },

  /**
   * Si este topic es el primero de la serie (order = 1).
   * Se usa para mostrar la sugerencia de hacer público al menos
   * el primer topic cuando el toggle está en OFF.
   */
  esPrimero: {
    type: Boolean,
    default: false,
  },
});

// ─── Emits ────────────────────────────────────────────────────────────────────

defineEmits(['update:modelValue']);
</script>