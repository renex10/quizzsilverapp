<!--
=============================================================================
  TopicSortableList.vue
  Ubicación: resources/js/Components/Admin/Topics/TopicSortableList.vue
=============================================================================

  ¿QUÉ HACE ESTE COMPONENTE?
  ──────────────────────────
  Muestra la lista completa de topics de una serie y permite al
  administrador REORDENARLOS arrastrando y soltando (drag & drop).

  Cada fila de la lista es un componente TopicCard.vue. Este componente
  se encarga únicamente de la LÓGICA DEL ORDEN — no sabe nada de edición
  ni eliminación. Esas acciones se delegan al padre (Admin/Topics/Index.vue).

  ¿CÓMO FUNCIONA EL DRAG & DROP?
  ────────────────────────────────
  Se usa la API nativa de HTML5 (draggable, dragstart, dragover, drop)
  sin ninguna librería externa. El flujo es:

    1. El admin agarra el handle (⠿) de un TopicCard
    2. dragstart  → guardamos el ID del topic que se está moviendo
    3. dragover   → marcamos visualmente cuál es la posición de destino
    4. drop       → calculamos el nuevo orden y lo emitimos al padre
    5. dragend    → limpiamos el estado visual

  Al soltar, el componente NO hace la petición HTTP por sí mismo.
  Emite el evento 'reordered' con el array de IDs en el nuevo orden
  y el padre (Index.vue) es responsable de hacer el PATCH al servidor.

  PROPS QUE RECIBE:
  ─────────────────
    topics    → array de objetos topic ordenados por su campo 'order'
    guardando → boolean — si el servidor está procesando el guardado
    seriesId  → number  — ID de la serie padre, pasado en cascada a TopicCard
                          para construir route('admin.topics.show', { series, topic })

  EMITS:
  ──────
    reordered → array de IDs en el nuevo orden
    edit      → topic object
    delete    → topic object

  DEPENDENCIAS:
  ─────────────
    • TopicCard.vue (componente hijo)
    • API nativa HTML5 Drag and Drop
    • Vue 3 Composition API
    • Tailwind CSS
=============================================================================
-->

<template>
  <div class="space-y-2">

    <!-- ── Estado vacío ────────────────────────────────────────────────── -->
    <div
      v-if="topics.length === 0"
      class="flex flex-col items-center justify-center py-16 text-center
             border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50"
    >
      <div class="text-4xl mb-3">📭</div>
      <p class="text-sm font-medium text-gray-500">Esta serie no tiene topics aún</p>
      <p class="text-xs text-gray-400 mt-1">
        Creá el primer topic para empezar a estructurar el contenido
      </p>
    </div>

    <!-- ── Lista de topics ─────────────────────────────────────────────── -->
    <template v-else>

      <!-- Instrucción de drag (solo visible si hay más de 1 topic) -->
      <p
        v-if="topics.length > 1"
        class="flex items-center gap-1.5 text-xs text-gray-400 px-1 mb-3"
      >
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
          <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7
                   9a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 16a2
                   2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
        </svg>
        Arrastrá los topics por el ícono ⠿ para cambiar el orden
      </p>

      <!-- Contenedor de la lista con zona de drop -->
      <div
        class="space-y-2"
        @dragover.prevent
        @drop.prevent
      >
        <div
          v-for="(topic, index) in listaLocal"
          :key="topic.id"
          class="relative"
        >
          <!-- Indicador de posición de destino — ARRIBA -->
          <div
            v-if="dropIndex === index && dragOverPosition === 'top'"
            class="absolute -top-1 left-0 right-0 h-0.5 bg-indigo-500 rounded-full
                   z-10 shadow-sm"
          >
            <div class="absolute -left-1 -top-1 w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
            <div class="absolute -right-1 -top-1 w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
          </div>

          <!-- La card del topic -->
          <div
            :draggable="true"
            @dragstart="onDragStart($event, topic.id, index)"
            @dragover.prevent="onDragOver($event, index)"
            @drop.prevent="onDrop(index)"
            @dragend="onDragEnd"
            class="transition-all duration-150"
            :class="{
              'opacity-50 scale-[0.98]': draggingId === topic.id,
              'cursor-grabbing':         draggingId === topic.id,
            }"
          >
            <TopicCard
              :topic="topic"
              :series-id="seriesId"
              :index="index"
              @edit="$emit('edit', $event)"
              @delete="$emit('delete', $event)"
              @drag="() => {}"
            />
          </div>

          <!-- Indicador de posición de destino — ABAJO -->
          <div
            v-if="dropIndex === index && dragOverPosition === 'bottom'"
            class="absolute -bottom-1 left-0 right-0 h-0.5 bg-indigo-500 rounded-full
                   z-10 shadow-sm"
          >
            <div class="absolute -left-1 -top-1 w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
            <div class="absolute -right-1 -top-1 w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
          </div>

        </div>
      </div>

      <!-- Indicador de cambios sin guardar -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1"
      >
        <div
          v-if="hayOrdenModificado"
          class="flex items-center justify-between mt-3 px-4 py-3 bg-indigo-50
                 border border-indigo-200 rounded-xl"
        >
          <div class="flex items-center gap-2 text-sm text-indigo-700">
            <svg class="w-4 h-4 text-indigo-500 shrink-0"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
            Nuevo orden sin guardar
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="resetearOrden"
              class="text-xs text-gray-500 hover:text-gray-700 transition-colors"
            >
              Deshacer
            </button>
            <button
              type="button"
              @click="guardarOrden"
              :disabled="guardando"
              class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600
                     hover:bg-indigo-700 text-white text-xs font-semibold
                     rounded-lg transition-colors disabled:opacity-50"
            >
              <svg
                v-if="guardando"
                class="animate-spin w-3 h-3"
                fill="none" viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10"
                  stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ guardando ? 'Guardando...' : 'Guardar orden' }}
            </button>
          </div>
        </div>
      </Transition>

    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TopicCard from './TopicCard.vue';

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  /**
   * Array de topics ya ordenados por el campo 'order'.
   * El controlador los devuelve en el orden correcto.
   * Este componente trabaja con una copia local para el drag & drop.
   */
  topics: {
    type: Array,
    required: true,
  },

  /** Indica si el servidor está procesando el guardado del nuevo orden */
  guardando: {
    type: Boolean,
    default: false,
  },

  /**
   * ID de la serie padre.
   * Se pasa en cascada desde Index.vue → TopicSortableList → TopicCard
   * para que TopicCard pueda construir la ruta anidada:
   *   route('admin.topics.show', { series: seriesId, topic: topic.id })
   */
  seriesId: {
    type: Number,
    required: true,
  },
});

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits([
  'reordered',
  'edit',
  'delete',
]);

// ─── Estado local del drag & drop ─────────────────────────────────────────────

const listaLocal       = ref([...props.topics]);
const draggingId       = ref(null);
const draggingIndex    = ref(null);
const dropIndex        = ref(null);
const dragOverPosition = ref(null);

// ─── Detectar si el orden fue modificado ─────────────────────────────────────

const ordenOriginal = computed(() => props.topics.map((t) => t.id));

const hayOrdenModificado = computed(() => {
  const ordenActual = listaLocal.value.map((t) => t.id);
  return JSON.stringify(ordenActual) !== JSON.stringify(ordenOriginal.value);
});

// ─── Handlers del Drag & Drop ─────────────────────────────────────────────────

const onDragStart = (event, topicId, index) => {
  draggingId.value    = topicId;
  draggingIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', String(topicId));
};

const onDragOver = (event, index) => {
  if (draggingIndex.value === null || draggingIndex.value === index) return;
  dropIndex.value = index;
  const rect = event.currentTarget.getBoundingClientRect();
  dragOverPosition.value = event.clientY < rect.top + rect.height / 2 ? 'top' : 'bottom';
};

const onDrop = (targetIndex) => {
  if (draggingIndex.value === null || draggingIndex.value === targetIndex) {
    onDragEnd();
    return;
  }

  let destinoReal = targetIndex;
  if (dragOverPosition.value === 'bottom') destinoReal = targetIndex + 1;
  if (draggingIndex.value < destinoReal)   destinoReal -= 1;

  const nuevaLista = [...listaLocal.value];
  const [itemMovido] = nuevaLista.splice(draggingIndex.value, 1);
  nuevaLista.splice(destinoReal, 0, itemMovido);
  listaLocal.value = nuevaLista;
  onDragEnd();
};

const onDragEnd = () => {
  draggingId.value = draggingIndex.value = dropIndex.value = dragOverPosition.value = null;
};

// ─── Guardar y deshacer orden ─────────────────────────────────────────────────

const guardarOrden  = () => emit('reordered', listaLocal.value.map((t) => t.id));
const resetearOrden = () => { listaLocal.value = [...props.topics]; };
</script>