<!--
=============================================================================
  LessonModal.vue
  Ubicación: resources/js/Components/Admin/Topics/Lessons/LessonModal.vue
=============================================================================

  ¿QUÉ HACE ESTE COMPONENTE?
  ──────────────────────────
  Modal de 2 pasos para crear o editar una lección.
  Usa el MultiStepModal existente del proyecto como base estructural.

  POR QUÉ UN MODAL EN LUGAR DE UNA PÁGINA SEPARADA:
  ────────────────────────────────────────────────────
  Las lecciones siempre viven en el contexto de un topic. El admin
  no debería salir de la vista Topics/Show para cargar una lección.
  El modal mantiene el contexto visual y reduce la navegación.

  LOS 2 PASOS:
  ────────────
    Paso 1 — Datos básicos
      • Título (obligatorio)
      • Orden dentro del topic (con sugerencia automática)
      • Duración estimada en minutos
      • is_preview — si la lección es visible sin login

    Paso 2 — Contenido Markdown
      • Editor textarea en la izquierda
      • Preview renderizado en la derecha (en tiempo real con marked.js)
      • La preview muestra exactamente cómo verá el estudiante el contenido
      • Barra de ayuda con los atajos Markdown más comunes

  CÓMO FUNCIONA EL EDITOR:
  ─────────────────────────
  Se usa la librería `marked` (ya disponible en el proyecto) para
  convertir el texto Markdown a HTML en tiempo real. El preview
  aplica clases de prose de Tailwind para que el resultado sea
  legible y bien formateado.

  El admin escribe en el lado izquierdo y ve el resultado en el
  lado derecho sin necesidad de guardar primero.

  MODO CREAR vs EDITAR:
  ──────────────────────
  El componente detecta automáticamente el modo según si recibe
  una prop `lesson` con datos o no:
    • Sin lesson (null) → modo CREAR → POST al servidor
    • Con lesson → modo EDITAR → PATCH al servidor

  PROPS QUE RECIBE:
  ─────────────────
    modelValue → boolean — si el modal está abierto
    lesson     → objeto | null — si null, modo crear; si tiene datos, modo editar
    topicId    → number — ID del topic al que pertenece la lección
    nextOrder  → number — el siguiente número de orden disponible

  EMITS:
  ──────
    update:modelValue → para cerrar el modal (v-model)
    saved             → cuando la lección fue guardada exitosamente
                        el padre recarga la lista de lecciones

  DEPENDENCIAS:
  ─────────────
    • marked (npm install marked) — renderizado Markdown a HTML
    • useForm de Inertia — manejo del formulario y errores
    • Vue 3 Composition API
    • Tailwind CSS + plugin @tailwindcss/typography (prose)
=============================================================================
-->

<template>
  <!-- Overlay del modal -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="cerrar"
      >
        <!-- Fondo oscuro -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <!-- Panel del modal -->
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
        >
          <div
            v-if="modelValue"
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl
                   max-h-[90vh] flex flex-col overflow-hidden"
            @click.stop
          >

            <!-- ── Encabezado del modal ──────────────────────────────── -->
            <div class="flex items-center justify-between px-6 py-4
                        border-b border-gray-100 shrink-0">
              <div class="flex items-center gap-3">
                <!-- Indicador de paso -->
                <div class="flex items-center gap-1.5">
                  <button
                    v-for="n in 2"
                    :key="n"
                    type="button"
                    @click="pasoActual = n - 1"
                    :disabled="n - 1 > pasoMaxAlcanzado"
                    class="w-2 h-2 rounded-full transition-all duration-200"
                    :class="n - 1 === pasoActual
                      ? 'bg-indigo-600 w-6'
                      : n - 1 <= pasoMaxAlcanzado
                      ? 'bg-indigo-300 hover:bg-indigo-400'
                      : 'bg-gray-200'"
                  ></button>
                </div>
                <h3 class="text-base font-semibold text-gray-800">
                  {{ modoEditar ? 'Editar lección' : 'Nueva lección' }}
                </h3>
                <span class="text-xs text-gray-400">
                  Paso {{ pasoActual + 1 }} de 2 — {{ tituloPaso }}
                </span>
              </div>
              <button
                type="button"
                @click="cerrar"
                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600
                       hover:bg-gray-100 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- ── Contenido del paso ───────────────────────────────── -->
            <div class="flex-1 overflow-y-auto">

              <!-- PASO 1: Datos básicos -->
              <div v-show="pasoActual === 0" class="p-6 space-y-5">

                <!-- Título -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Título <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.title"
                    type="text"
                    placeholder="Ej. Señales Reglamentarias"
                    maxlength="255"
                    class="w-full border rounded-xl px-3 py-2.5 text-sm
                           focus:outline-none focus:ring-2 transition-all"
                    :class="form.errors.title
                      ? 'border-red-400 focus:ring-red-200'
                      : 'border-gray-300 focus:ring-indigo-200 focus:border-indigo-400'"
                  />
                  <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">
                    {{ form.errors.title }}
                  </p>
                </div>

                <!-- Orden + Duración en fila -->
                <div class="grid grid-cols-2 gap-4">

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                      Orden <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                      <input
                        v-model.number="form.order"
                        type="number"
                        min="1"
                        max="99"
                        class="w-20 border border-gray-300 rounded-xl px-3 py-2.5
                               text-sm text-center focus:outline-none focus:ring-2
                               focus:ring-indigo-200 focus:border-indigo-400"
                      />
                      <button
                        v-if="nextOrder && form.order !== nextOrder"
                        type="button"
                        @click="form.order = nextOrder"
                        class="text-xs text-indigo-600 hover:text-indigo-700
                               underline underline-offset-2"
                      >
                        Usar {{ nextOrder }}
                      </button>
                      <span
                        v-else-if="form.order === nextOrder"
                        class="text-xs text-green-600 flex items-center gap-1"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Siguiente
                      </span>
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                      Duración (minutos)
                    </label>
                    <input
                      v-model.number="form.duration_minutes"
                      type="number"
                      min="1"
                      max="120"
                      class="w-full border border-gray-300 rounded-xl px-3 py-2.5
                             text-sm focus:outline-none focus:ring-2
                             focus:ring-indigo-200 focus:border-indigo-400"
                    />
                    <p class="mt-1 text-xs text-gray-400">Estimación para el estudiante</p>
                  </div>

                </div>

                <!-- Toggle is_preview -->
                <div class="flex items-start justify-between gap-4 p-4 rounded-xl
                            border border-gray-200 bg-gray-50">
                  <div>
                    <p class="text-sm font-medium text-gray-800">
                      Visible sin login (preview)
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                      Los visitantes sin cuenta podrán leer esta lección en la landing pública.
                      Usá esto para mostrar contenido de muestra que invite a registrarse.
                    </p>
                  </div>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="form.is_preview"
                    @click="form.is_preview = !form.is_preview"
                    class="shrink-0 relative inline-flex h-6 w-11 items-center
                           rounded-full transition-colors duration-300 focus:outline-none
                           focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2"
                    :class="form.is_preview ? 'bg-indigo-600' : 'bg-gray-300'"
                  >
                    <span
                      class="inline-block h-4 w-4 rounded-full bg-white shadow-sm
                             transform transition-transform duration-300"
                      :class="form.is_preview ? 'translate-x-6' : 'translate-x-1'"
                    ></span>
                  </button>
                </div>

              </div>

              <!-- PASO 2: Editor Markdown -->
              <div v-show="pasoActual === 1" class="flex flex-col h-full">

                <!-- Barra de ayuda Markdown -->
                <div class="flex items-center gap-1 px-4 py-2 bg-gray-50
                            border-b border-gray-100 flex-wrap">
                  <span class="text-xs text-gray-400 mr-2">Ayuda:</span>
                  <button
                    v-for="tip in markdownTips"
                    :key="tip.label"
                    type="button"
                    @click="insertarMarkdown(tip.insert)"
                    class="px-2 py-0.5 text-xs font-mono bg-white border border-gray-200
                           rounded hover:bg-gray-50 hover:border-gray-300 transition-colors
                           text-gray-600"
                    :title="tip.desc"
                  >
                    {{ tip.label }}
                  </button>
                </div>

                <!-- Editor + Preview lado a lado -->
                <div class="grid grid-cols-2 flex-1 min-h-0" style="height: 420px;">

                  <!-- Editor -->
                  <div class="flex flex-col border-r border-gray-100">
                    <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-100 flex items-center gap-2">
                      <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                      <span class="text-xs text-gray-400 font-medium">Markdown</span>
                    </div>
                    <textarea
                      ref="editorRef"
                      v-model="form.content"
                      placeholder="## Título de la lección&#10;&#10;Escribí el contenido en Markdown...&#10;&#10;- Lista de puntos&#10;- Otro punto&#10;&#10;**Negrita**, *cursiva*, `código`"
                      class="flex-1 w-full p-4 text-sm font-mono text-gray-700
                             resize-none focus:outline-none leading-relaxed"
                      spellcheck="false"
                    ></textarea>
                  </div>

                  <!-- Preview renderizado -->
                  <div class="flex flex-col overflow-hidden">
                    <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-100 flex items-center gap-2">
                      <span class="w-2 h-2 rounded-full bg-green-400"></span>
                      <span class="text-xs text-gray-400 font-medium">Vista previa</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                      <div
                        v-if="previewHtml"
                        class="prose prose-sm prose-indigo max-w-none text-gray-700"
                        v-html="previewHtml"
                      ></div>
                      <div v-else class="flex items-center justify-center h-full">
                        <p class="text-xs text-gray-300 italic">
                          La preview aparece mientras escribís
                        </p>
                      </div>
                    </div>
                  </div>

                </div>

                <!-- Contador de caracteres -->
                <div class="px-4 py-1.5 border-t border-gray-100 bg-gray-50
                            flex items-center justify-between">
                  <p v-if="form.errors.content" class="text-xs text-red-600">
                    {{ form.errors.content }}
                  </p>
                  <span v-else class="text-xs text-gray-400">
                    Mínimo 50 caracteres de contenido
                  </span>
                  <span
                    class="text-xs"
                    :class="form.content.length < 50 ? 'text-amber-500' : 'text-gray-400'"
                  >
                    {{ form.content.length }} caracteres
                  </span>
                </div>

              </div>

            </div>

            <!-- ── Pie del modal: navegación ────────────────────────── -->
            <div class="flex items-center justify-between px-6 py-4
                        border-t border-gray-100 shrink-0">

              <!-- Botón anterior -->
              <button
                v-if="pasoActual > 0"
                type="button"
                @click="pasoActual--"
                class="flex items-center gap-1.5 px-4 py-2 text-sm text-gray-600
                       border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Anterior
              </button>
              <div v-else></div>

              <div class="flex items-center gap-3">

                <!-- Cancelar -->
                <button
                  type="button"
                  @click="cerrar"
                  class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
                >
                  Cancelar
                </button>

                <!-- Siguiente / Guardar -->
                <button
                  type="button"
                  @click="avanzarOGuardar"
                  :disabled="!puedeAvanzar || form.processing"
                  class="flex items-center gap-2 px-5 py-2 bg-gradient-to-r
                         from-indigo-600 to-purple-600 hover:from-indigo-700
                         hover:to-purple-700 text-white text-sm font-semibold
                         rounded-xl shadow-sm transition-all duration-200
                         disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg
                    v-if="form.processing"
                    class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"
                  >
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                      stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ labelBotonAvanzar }}
                </button>

              </div>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { marked } from 'marked';

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  /** Si el modal está abierto */
  modelValue: { type: Boolean, default: false },
  /**
   * Lección a editar. Si es null, el modal opera en modo CREAR.
   * { id, title, order, duration_minutes, is_preview, content }
   */
  lesson:    { type: Object,  default: null },
  /** ID del topic al que pertenece la lección */
  topicId:   { type: Number,  required: true },
  /** Siguiente número de orden disponible */
  nextOrder: { type: Number,  default: 1 },
});

const emit = defineEmits(['update:modelValue', 'saved']);

// ─── Estado ───────────────────────────────────────────────────────────────────

const pasoActual       = ref(0);
const pasoMaxAlcanzado = ref(0);
const editorRef        = ref(null);

const modoEditar = computed(() => !!props.lesson);

// ─── Formulario ───────────────────────────────────────────────────────────────

const form = useForm({
  title:            '',
  order:            props.nextOrder,
  duration_minutes: 5,
  is_preview:       false,
  content:          '',
});

/**
 * Cuando el modal se abre en modo editar, prellenamos el form.
 * Cuando se abre en modo crear, reseteamos todo.
 */
watch(() => props.modelValue, (abierto) => {
  if (!abierto) return;
  pasoActual.value       = 0;
  pasoMaxAlcanzado.value = 0;

  if (props.lesson) {
    form.title            = props.lesson.title ?? '';
    form.order            = props.lesson.order ?? props.nextOrder;
    form.duration_minutes = props.lesson.duration_minutes ?? 5;
    form.is_preview       = props.lesson.is_preview ?? false;
    form.content          = props.lesson.content ?? '';
  } else {
    form.reset();
    form.order = props.nextOrder;
  }
});

// ─── Preview Markdown en tiempo real ─────────────────────────────────────────

marked.setOptions({ breaks: true, gfm: true });

const previewHtml = computed(() => {
  if (!form.content) return '';
  try {
    return marked(form.content);
  } catch {
    return '<p class="text-red-500 text-sm">Error al renderizar el Markdown</p>';
  }
});

// ─── Ayuda Markdown ───────────────────────────────────────────────────────────

const markdownTips = [
  { label: '## Título', insert: '## ',          desc: 'Encabezado H2' },
  { label: '**negrita**', insert: '****',        desc: 'Texto en negrita' },
  { label: '*cursiva*',   insert: '**',          desc: 'Texto en cursiva' },
  { label: '- Lista',     insert: '- ',          desc: 'Lista con viñeta' },
  { label: '1. Lista',    insert: '1. ',         desc: 'Lista numerada' },
  { label: '`código`',    insert: '``',          desc: 'Código inline' },
  { label: '> Cita',      insert: '> ',          desc: 'Bloque de cita' },
  { label: '---',         insert: '\n---\n',     desc: 'Línea divisoria' },
  { label: '| Tabla |',   insert: '| Col 1 | Col 2 |\n|---|---|\n| Dato | Dato |', desc: 'Tabla' },
];

const insertarMarkdown = (texto) => {
  const textarea = editorRef.value;
  if (!textarea) { form.content += texto; return; }
  const inicio = textarea.selectionStart;
  const fin    = textarea.selectionEnd;
  form.content = form.content.slice(0, inicio) + texto + form.content.slice(fin);
  // Mover cursor al final del texto insertado
  setTimeout(() => {
    textarea.selectionStart = textarea.selectionEnd = inicio + texto.length;
    textarea.focus();
  }, 0);
};

// ─── Lógica de pasos ─────────────────────────────────────────────────────────

const tituloPaso = computed(() =>
  pasoActual.value === 0 ? 'Datos básicos' : 'Contenido Markdown'
);

const puedeAvanzar = computed(() => {
  if (pasoActual.value === 0) return !!form.title?.trim();
  if (pasoActual.value === 1) return form.content.length >= 50;
  return false;
});

const labelBotonAvanzar = computed(() => {
  if (form.processing) return 'Guardando...';
  if (pasoActual.value < 1) return 'Siguiente →';
  return modoEditar.value ? 'Guardar cambios' : 'Crear lección';
});

const avanzarOGuardar = () => {
  if (pasoActual.value < 1) {
    pasoActual.value++;
    if (pasoActual.value > pasoMaxAlcanzado.value) {
      pasoMaxAlcanzado.value = pasoActual.value;
    }
    return;
  }
  guardar();
};

// ─── Guardar ─────────────────────────────────────────────────────────────────

const guardar = () => {
  if (modoEditar.value) {
    form.patch(
      route('admin.lessons.update', { topic: props.topicId, lesson: props.lesson.id }),
      {
        preserveScroll: true,
        onSuccess: () => { emit('saved'); cerrar(); },
      }
    );
  } else {
    form.post(
      route('admin.lessons.store', { topic: props.topicId }),
      {
        preserveScroll: true,
        onSuccess: () => { emit('saved'); cerrar(); },
      }
    );
  }
};

// ─── Cerrar ───────────────────────────────────────────────────────────────────

const cerrar = () => {
  if (form.processing) return;
  emit('update:modelValue', false);
};
</script>

<style scoped>
/* Estilos básicos para el preview Markdown (prose de Tailwind) */
.prose :deep(h1), .prose :deep(h2), .prose :deep(h3) {
  font-weight: 700;
  color: #1f2937;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}
.prose :deep(h2) { font-size: 1.1rem; }
.prose :deep(h3) { font-size: 1rem; }
.prose :deep(p)  { margin-bottom: 0.75rem; line-height: 1.6; }
.prose :deep(ul), .prose :deep(ol) { padding-left: 1.25rem; margin-bottom: 0.75rem; }
.prose :deep(li) { margin-bottom: 0.25rem; }
.prose :deep(strong) { font-weight: 600; color: #111827; }
.prose :deep(em) { color: #4b5563; }
.prose :deep(code) {
  font-family: monospace;
  background: #f3f4f6;
  padding: 0.1rem 0.3rem;
  border-radius: 0.25rem;
  font-size: 0.85em;
  color: #6366f1;
}
.prose :deep(blockquote) {
  border-left: 3px solid #6366f1;
  padding-left: 0.75rem;
  color: #6b7280;
  font-style: italic;
  margin: 0.75rem 0;
}
.prose :deep(table) { width: 100%; border-collapse: collapse; margin-bottom: 0.75rem; }
.prose :deep(th), .prose :deep(td) {
  border: 1px solid #e5e7eb;
  padding: 0.4rem 0.6rem;
  font-size: 0.85rem;
}
.prose :deep(th) { background: #f9fafb; font-weight: 600; }
.prose :deep(hr) { border-color: #e5e7eb; margin: 1rem 0; }
</style>