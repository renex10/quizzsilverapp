<!--
=============================================================================
  LessonList.vue
  Ubicación: resources/js/Components/Admin/Topics/Lessons/LessonList.vue
=============================================================================

  ¿QUÉ HACE ESTE COMPONENTE?
  ──────────────────────────
  Muestra la lista completa de lecciones de un topic y permite
  reordenarlas con drag & drop.

  Es el equivalente de TopicSortableList pero para lecciones.
  La lógica de drag & drop es idéntica — API nativa HTML5,
  sin librerías externas, con indicador visual de posición.

  DIFERENCIAS RESPECTO A TopicSortableList:
  ──────────────────────────────────────────
    • Usa LessonCard en lugar de TopicCard
    • El estado vacío invita a crear la primera lección
    • El banner de "Nuevo orden sin guardar" es el mismo patrón
    • El botón "Nueva lección" está integrado al pie de la lista

  FLUJO DEL REORDENAMIENTO:
  ──────────────────────────
    1. Admin arrastra una lección por el handle ⠿
    2. Aparece línea indicadora azul en la posición destino
    3. Al soltar: el array local se reordena visualmente
    4. Aparece banner "Nuevo orden sin guardar" con botones
    5. Admin hace click en "Guardar orden"
    6. Emite 'reordered' con el array de IDs en nuevo orden
    7. El padre (Topics/Show.vue) hace PATCH al servidor

  PROPS QUE RECIBE:
  ─────────────────
    lessons   → array de lecciones ordenadas por 'order'
    guardando → boolean — si el servidor está procesando el reorder

  EMITS:
  ──────
    reordered      → array de IDs en nuevo orden
    edit-lesson    → lesson object — el padre abre LessonModal
    delete-lesson  → lesson object — el padre pide confirmación
    nueva-leccion  → sin payload — el padre abre LessonModal vacío

  DEPENDENCIAS:
  ─────────────
    • LessonCard.vue (componente hijo)
    • API nativa HTML5 Drag and Drop
    • Vue 3 Composition API
    • Tailwind CSS
=============================================================================
-->

<template>
  <div class="space-y-2">

    <!-- ── Estado vacío ────────────────────────────────────────────────── -->
    <div
      v-if="lessons.length === 0"
      class="flex flex-col items-center justify-center py-12 text-center
             border-2 border-dashed border-gray-200 rounded-xl bg-gray-50"
    >
      <div class="text-3xl mb-2">📝</div>
      <p class="text-sm font-medium text-gray-500">Este topic no tiene lecciones</p>
      <p class="text-xs text-gray-400 mt-0.5 mb-3">
        Creá la primera lección para empezar a cargar contenido
      </p>
      <button
        type="button"
        @click="$emit('nueva-leccion')"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600
               hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg
               transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 4v16m8-8H4"/>
        </svg>
        Nueva lección
      </button>
    </div>

    <!-- ── Lista de lecciones ──────────────────────────────────────────── -->
    <template v-else>

      <!-- Instrucción de drag -->
      <p
        v-if="lessons.length > 1"
        class="flex items-center gap-1.5 text-xs text-gray-400 px-1 mb-2"
      >
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
          <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7
                   9a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 16a2
                   2 0 1 0 0 4 2 2 0 0 0 0-4zm6 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
        </svg>
        Arrastrá por el ícono ⠿ para reordenar
      </p>

      <!-- Contenedor drag & drop -->
      <div class="space-y-2" @dragover.prevent @drop.prevent>
        <div
          v-for="(lesson, index) in listaLocal"
          :key="lesson.id"
          class="relative"
        >
          <!-- Indicador posición — ARRIBA -->
          <div
            v-if="dropIndex === index && dragOverPosition === 'top'"
            class="absolute -top-1 left-0 right-0 h-0.5 bg-indigo-500 rounded-full z-10"
          >
            <div class="absolute -left-1 -top-1 w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
            <div class="absolute -right-1 -top-1 w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
          </div>

          <!-- Lesson card -->
          <div
            :draggable="true"
            @dragstart="onDragStart($event, lesson.id, index)"
            @dragover.prevent="onDragOver($event, index)"
            @drop.prevent="onDrop(index)"
            @dragend="onDragEnd"
            class="transition-all duration-150"
            :class="{ 'opacity-50 scale-[0.98]': draggingId === lesson.id }"
          >
            <LessonCard
              :lesson="lesson"
              @edit="$emit('edit-lesson', $event)"
              @delete="$emit('delete-lesson', $event)"
              @drag="() => {}"
            />
          </div>

          <!-- Indicador posición — ABAJO -->
          <div
            v-if="dropIndex === index && dragOverPosition === 'bottom'"
            class="absolute -bottom-1 left-0 right-0 h-0.5 bg-indigo-500 rounded-full z-10"
          >
            <div class="absolute -left-1 -top-1 w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
            <div class="absolute -right-1 -top-1 w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
          </div>
        </div>
      </div>

      <!-- Banner de orden sin guardar -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="hayOrdenModificado"
          class="flex items-center justify-between mt-2 px-4 py-3 bg-indigo-50
                 border border-indigo-200 rounded-xl"
        >
          <div class="flex items-center gap-2 text-sm text-indigo-700">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
            Nuevo orden sin guardar
          </div>
          <div class="flex items-center gap-2">
            <button type="button" @click="resetearOrden"
              class="text-xs text-gray-500 hover:text-gray-700 transition-colors">
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
              <svg v-if="guardando" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ guardando ? 'Guardando...' : 'Guardar orden' }}
            </button>
          </div>
        </div>
      </Transition>

      <!-- Botón nueva lección al pie -->
      <button
        type="button"
        @click="$emit('nueva-leccion')"
        class="w-full flex items-center justify-center gap-2 py-2.5 mt-1
               border-2 border-dashed border-gray-200 rounded-xl text-sm
               text-gray-400 hover:border-indigo-300 hover:text-indigo-600
               hover:bg-indigo-50 transition-all duration-150"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Agregar lección
      </button>

    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import LessonCard from './LessonCard.vue';

const props = defineProps({
  lessons:   { type: Array,   required: true },
  guardando: { type: Boolean, default: false },
});

const emit = defineEmits(['reordered', 'edit-lesson', 'delete-lesson', 'nueva-leccion']);

// ─── Estado drag & drop ───────────────────────────────────────────────────────

const listaLocal        = ref([...props.lessons]);
const draggingId        = ref(null);
const draggingIndex     = ref(null);
const dropIndex         = ref(null);
const dragOverPosition  = ref(null);

const ordenOriginal     = computed(() => props.lessons.map((l) => l.id));
const hayOrdenModificado = computed(() =>
  JSON.stringify(listaLocal.value.map((l) => l.id)) !== JSON.stringify(ordenOriginal.value)
);

const onDragStart = (event, lessonId, index) => {
  draggingId.value    = lessonId;
  draggingIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', String(lessonId));
};

const onDragOver = (event, index) => {
  if (draggingIndex.value === null || draggingIndex.value === index) return;
  dropIndex.value = index;
  const rect  = event.currentTarget.getBoundingClientRect();
  dragOverPosition.value = event.clientY < rect.top + rect.height / 2 ? 'top' : 'bottom';
};

const onDrop = (targetIndex) => {
  if (draggingIndex.value === null || draggingIndex.value === targetIndex) { onDragEnd(); return; }
  let destino = dragOverPosition.value === 'bottom' ? targetIndex + 1 : targetIndex;
  if (draggingIndex.value < destino) destino -= 1;
  const lista = [...listaLocal.value];
  const [item] = lista.splice(draggingIndex.value, 1);
  lista.splice(destino, 0, item);
  listaLocal.value = lista;
  onDragEnd();
};

const onDragEnd = () => {
  draggingId.value = draggingIndex.value = dropIndex.value = dragOverPosition.value = null;
};

const guardarOrden   = () => emit('reordered', listaLocal.value.map((l) => l.id));
const resetearOrden  = () => { listaLocal.value = [...props.lessons]; };
</script>